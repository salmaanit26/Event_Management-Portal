<?php
session_start();
require_once 'connection_sqlite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle admin actions
if ($user_role == 'admin' && $_POST) {
    if (isset($_POST['action'])) {
        $event_id = intval($_POST['event_id']);
        
        switch($_POST['action']) {
            case 'approve':
                $conn->prepare("UPDATE event_details SET status = 'Approved', admin_remarks = ? WHERE id = ?")
                     ->execute([$_POST['remarks'] ?? 'Event approved', $event_id]);
                $success_message = "Event approved successfully!";
                break;
                
            case 'reject':
                $conn->prepare("UPDATE event_details SET status = 'Rejected', admin_remarks = ? WHERE id = ?")
                     ->execute([$_POST['remarks'] ?? 'Event rejected', $event_id]);
                $success_message = "Event rejected with remarks!";
                break;
                
            case 'set_ira':
                $ira_status = $_POST['ira_status'] == 'yes' ? 'YES' : 'NO';
                $conn->prepare("UPDATE event_details SET ira = ?, admin_remarks = ? WHERE id = ?")
                     ->execute([$ira_status, $_POST['remarks'] ?? 'IRA status updated', $event_id]);
                $success_message = "IRA status updated!";
                break;
        }
    }
}

// Get events based on user role
if ($user_role == 'admin') {
    // Admin sees all events
    $events_query = $conn->query("
        SELECT e.*, u.full_name as applicant_name, u.department as applicant_dept, u.student_id
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        ORDER BY 
            CASE e.status 
                WHEN 'Pending' THEN 1 
                WHEN 'Approved' THEN 2 
                WHEN 'Rejected' THEN 3 
            END,
            e.created_at DESC
    ");
} else {
    // Students/Reviewers see only approved events + their own submissions
    $events_query = $conn->prepare("
        SELECT e.*, u.full_name as applicant_name, u.department as applicant_dept 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        WHERE e.status = 'Approved' OR e.applied_by = ?
        ORDER BY e.event_date ASC
    ");
    $events_query->execute([$user_id]);
}

$events = $events_query->fetchAll();

// Get IRA events for students
$ira_events = [];
if ($user_role == 'student') {
    $ira_query = $conn->query("
        SELECT e.*, u.full_name as applicant_name 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        WHERE e.status = 'Approved' AND e.ira = 'YES'
        ORDER BY e.event_date ASC
    ");
    $ira_events = $ira_query->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nav-container h1 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: #4a5568;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .page-header h2 {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: #4a5568;
            font-size: 1.1rem;
        }
        
        .events-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }
        
        .status-pending { 
            background: #fef3c7; 
            color: #92400e; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-approved { 
            background: #dcfce7; 
            color: #166534; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-rejected { 
            background: #fecaca; 
            color: #991b1b; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .admin-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .admin-actions button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-approve { 
            background: #059669; 
            color: white; 
        }
        
        .btn-approve:hover { 
            background: #047857; 
        }
        
        .btn-reject { 
            background: #dc2626; 
            color: white; 
        }
        
        .btn-reject:hover { 
            background: #b91c1c; 
        }
        
        .btn-ira { 
            background: #2563eb; 
            color: white; 
        }
        
        .btn-ira:hover { 
            background: #1d4ed8; 
        }
        
        .admin-actions input, .admin-actions select {
            padding: 0.375rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-right: 0.25rem;
        }
        
        .ira-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin: 2rem 0;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .ira-section h2 {
            color: #2d3748;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .ira-event {
            background: white;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .ira-event-info {
            flex: 1;
            min-width: 250px;
        }
        
        .ira-event-info h3 {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
        }
        
        .ira-event-details {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .ira-event-actions {
            flex-shrink: 0;
            margin-left: 1rem;
        }
        
        .register-ira-btn {
            background: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .register-ira-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid #bbf7d0;
        }
        
        .admin-instructions {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-instructions h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .admin-instructions ul {
            list-style: none;
            padding: 0;
        }
        
        .admin-instructions li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
            color: #4a5568;
        }
        
        .admin-instructions li:last-child {
            border-bottom: none;
        }
        
        .admin-instructions strong {
            color: #2d3748;
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .admin-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>🎯 Event Management Portal</h1>
            <div class="nav-links">
                <a href="dashboard.php">📊 Dashboard</a>
                <?php if ($user_role == 'student'): ?>
                    <a href="add_event.php">➕ Add Event</a>
                    <a href="status.php">📋 My Events</a>
                    <a href="ira_register.php">🎯 IRA Registration</a>
                <?php endif; ?>
                
                <?php if ($user_role == 'admin'): ?>
                    <a href="add_event.php">➕ Add Event</a>
                    <a href="status.php">📋 All Events</a>
                    <a href="admin_management.php">🛠️ Admin Panel</a>
                    <a href="manage_slots.php">⏰ Manage Slots</a>
                    <a href="edit_reviewer.php">👥 Faculty</a>
                <?php endif; ?>
                
                <?php if ($user_role == 'reviewer'): ?>
                    <a href="status.php">📋 Events</a>
                    <a href="ira_register.php">🎯 IRA Registration</a>
                <?php endif; ?>
                
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>📊 Event Management Dashboard</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo ucfirst($user_role); ?>)</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <!-- IRA Events Section for Students -->
        <?php if ($user_role == 'student' && !empty($ira_events)): ?>
            <div class="ira-section">
                <h2>🎯 IRA Events - Registration Available</h2>
                <?php foreach ($ira_events as $event): ?>
                    <div class="ira-event">
                        <div class="ira-event-info">
                            <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <div class="ira-event-details">
                                <span><strong>Date:</strong> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                <span><strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organizer']); ?></span>
                                <span><strong>Domain:</strong> <?php echo htmlspecialchars($event['domain']); ?></span>
                            </div>
                        </div>
                        <div class="ira-event-actions">
                            <a href="ira_register.php?event_id=<?php echo $event['id']; ?>" class="register-ira-btn">Register for IRA</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Main Events Table -->
        <div class="events-table">
            <div class="table-header">
                <?php if ($user_role == 'admin'): ?>
                    📋 All Event Requests - Admin Panel
                <?php else: ?>
                    📅 Available Events
                <?php endif; ?>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Requested By</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>IRA</th>
                        <?php if ($user_role == 'admin'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="<?php echo $user_role == 'admin' ? '8' : '7'; ?>" style="text-align: center; padding: 30px; color: #6b7280;">
                                No events found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <strong style="color: #1f2937;"><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                    <?php if ($event['admin_remarks']): ?>
                                        <br><small style="color: #6b7280;">💬 <?php echo htmlspecialchars($event['admin_remarks']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($event['applicant_name']); ?>
                                    <?php if (isset($event['student_id']) && $event['student_id']): ?>
                                        <br><small style="color: #6b7280;"><?php echo htmlspecialchars($event['student_id']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['applicant_dept']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['domain']); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($event['status']); ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($event['ira'] == 'YES'): ?>
                                        <span style="color: #059669; font-weight: 500;">✅ IRA</span>
                                    <?php else: ?>
                                        <span style="color: #6b7280;">❌ No IRA</span>
                                    <?php endif; ?>
                                </td>
                                
                                <?php if ($user_role == 'admin'): ?>
                                    <td>
                                        <div class="admin-actions">
                                            <?php if ($event['status'] == 'Pending'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="text" name="remarks" placeholder="Approval remarks" style="width: 120px;">
                                                    <button type="submit" class="btn-approve">Approve</button>
                                                </form>
                                                
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="text" name="remarks" placeholder="Rejection reason" style="width: 120px;">
                                                    <button type="submit" class="btn-reject">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($event['status'] == 'Approved'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <input type="hidden" name="action" value="set_ira">
                                                    <select name="ira_status">
                                                        <option value="yes" <?php echo $event['ira'] == 'YES' ? 'selected' : ''; ?>>Enable IRA</option>
                                                        <option value="no" <?php echo $event['ira'] == 'NO' ? 'selected' : ''; ?>>No IRA</option>
                                                    </select>
                                                    <input type="text" name="remarks" placeholder="IRA remarks" style="width: 100px;">
                                                    <button type="submit" class="btn-ira">Update IRA</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($user_role == 'admin'): ?>
            <div class="admin-instructions">
                <h3>📋 Admin Instructions</h3>
                <ul>
                    <li><strong>Approve/Reject:</strong> Review student event requests and approve worthy events</li>
                    <li><strong>IRA Decision:</strong> For approved events, decide if IRA (Internal Review Assessment) is needed</li>
                    <li><strong>Slot Management:</strong> Create time slots for IRA events via "Manage Slots"</li>
                    <li><strong>Faculty Assignment:</strong> Assign reviewers to halls via "Manage Reviewers"</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
