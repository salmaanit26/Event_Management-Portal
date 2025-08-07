<?php
session_start();
require_once 'connection_sqlite.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Get user details
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Handle slot registration
if ($_POST && isset($_POST['register_slot'])) {
    try {
        $slot_id = intval($_POST['slot_id']);
        $event_id = intval($_POST['event_id']);
        
        // Check if already registered for this event
        $check_existing = $conn->prepare("SELECT COUNT(*) FROM ira_registered_students WHERE student_id = ? AND event_id = ?");
        $check_existing->execute([$user_id, $event_id]);
        if ($check_existing->fetchColumn() > 0) {
            throw new Exception("You have already registered for this event's IRA.");
        }
        
        // Check slot capacity
        $slot_info = $conn->prepare("
            SELECT s.*, COUNT(r.id) as registered_count 
            FROM slots s 
            LEFT JOIN ira_registered_students r ON s.id = r.slot_id 
            WHERE s.id = ? 
            GROUP BY s.id
        ");
        $slot_info->execute([$slot_id]);
        $slot = $slot_info->fetch();
        
        if ($slot['registered_count'] >= $slot['max_capacity']) {
            throw new Exception("This slot is already full. Please choose another slot.");
        }
        
        // Register for the slot
        $register_stmt = $conn->prepare("
            INSERT INTO ira_registered_students 
            (event_id, slot_id, student_id, student_name, student_email, student_department, student_year, registration_status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Review', datetime('now'))
        ");
        
        $register_stmt->execute([
            $event_id,
            $slot_id,
            $user_id,
            $user['full_name'],
            $user['email'],
            $user['department'],
            $user['year_of_study'] ?? '3rd Year',
        ]);
        
        $success_message = "Successfully registered for IRA slot! Faculty will review your registration.";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get specific event if provided
$event = null;
$available_slots = [];
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    
    // Get event details
    $event_stmt = $conn->prepare("
        SELECT e.*, u.full_name as organizer_name 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        WHERE e.id = ? AND e.status = 'Approved' AND e.ira = 'YES'
    ");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch();
    
    if ($event) {
        // Get available slots for this event
        $slots_stmt = $conn->prepare("
            SELECT s.*, u.full_name as faculty_name, u.department as faculty_dept,
                   COUNT(r.id) as registered_count
            FROM slots s 
            LEFT JOIN users u ON s.assigned_faculty = u.id 
            LEFT JOIN ira_registered_students r ON s.id = r.slot_id 
            WHERE s.event_id = ? AND s.slot_date >= date('now')
            GROUP BY s.id 
            ORDER BY s.slot_date ASC, s.slot_time ASC
        ");
        $slots_stmt->execute([$event_id]);
        $available_slots = $slots_stmt->fetchAll();
    }
}

// Get all IRA events if no specific event
$all_ira_events = [];
if (!$event) {
    $all_events_stmt = $conn->query("
        SELECT e.*, u.full_name as organizer_name,
               COUNT(s.id) as slot_count
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        LEFT JOIN slots s ON e.id = s.event_id 
        WHERE e.status = 'Approved' AND e.ira = 'YES'
        GROUP BY e.id 
        ORDER BY e.event_date ASC
    ");
    $all_ira_events = $all_events_stmt->fetchAll();
}

// Get user's registrations
$my_registrations = $conn->prepare("
    SELECT r.*, e.event_name, s.slot_date, s.slot_time, s.hall_name, u.full_name as faculty_name
    FROM ira_registered_students r 
    LEFT JOIN event_details e ON r.event_id = e.id 
    LEFT JOIN slots s ON r.slot_id = s.id 
    LEFT JOIN users u ON s.assigned_faculty = u.id 
    WHERE r.student_id = ? 
    ORDER BY r.created_at DESC
");
$my_registrations->execute([$user_id]);
$registrations = $my_registrations->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRA Registration - Event Management Portal</title>
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
        
        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .event-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .event-card h2 {
            color: #2d3748;
            margin-bottom: 1rem;
        }
        
        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .event-details p {
            color: #4a5568;
            margin: 0.5rem 0;
        }
        
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .slot-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .slot-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
        }
        
        .slot-full {
            background: #fef2f2;
            border-color: #fca5a5;
        }
        
        .slot-info {
            margin-bottom: 1rem;
        }
        
        .slot-info h4 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .faculty-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary { 
            background: #3b82f6; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #2563eb; 
            transform: translateY(-1px);
        }
        
        .btn-success { 
            background: #10b981; 
            color: white; 
        }
        .btn-success:hover { 
            background: #059669; 
        }
        
        .btn-disabled { 
            background: #9ca3af; 
            color: white; 
            cursor: not-allowed; 
        }
        
        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .registrations-table {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 2rem;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .navbar {
                padding: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .nav-links a {
                padding: 0.5rem;
                text-align: center;
            }
            
            .table-header {
                padding: 1rem;
                font-size: 1.125rem;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .slots-grid {
                grid-template-columns: 1fr;
            }
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        tbody tr {
            transition: background-color 0.2s ease;
        }
        
        tbody tr:hover {
            background: #f9fafb;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
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
        
        .section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        } 
            color: #991b1b; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .capacity-indicator {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>🎯 Event Management Portal</h1>
            <div class="nav-links">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="status.php">📋 My Events</a>
                <a href="ira_register.php">🎯 IRA Registration</a>
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>🎯 IRA Registration Portal</h2>
            <p>Register for Internal Review Assessment events and track your progress</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($event): ?>
            <!-- Specific Event Registration -->
            <div class="content-section">
                <div class="event-card">
                    <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                    <div class="event-details">
                        <p><strong>📅 Date:</strong> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></p>
                        <p><strong>👤 Organizer:</strong> <?php echo htmlspecialchars($event['event_organizer']); ?></p>
                        <p><strong>🏷️ Domain:</strong> <?php echo htmlspecialchars($event['domain']); ?></p>
                        <p><strong>📋 Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?></p>
                    </div>
                    <?php if (isset($event['event_description']) && $event['event_description']): ?>
                        <p><strong>📄 Description:</strong> <?php echo htmlspecialchars($event['event_description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <h3>📅 Available IRA Slots</h3>
                
                <?php if (empty($available_slots)): ?>
                    <div class="alert alert-error">
                        ⚠️ No IRA slots are currently available for this event. Please check back later or contact the admin.
                    </div>
                <?php else: ?>
                    <div class="slots-grid">
                        <?php foreach ($available_slots as $slot): ?>
                            <?php 
                            $is_full = $slot['registered_count'] >= $slot['max_capacity'];
                            $remaining = $slot['max_capacity'] - $slot['registered_count'];
                            ?>
                            <div class="slot-card <?php echo $is_full ? 'slot-full' : ''; ?>">
                                <div class="slot-info">
                                    <h4>📅 <?php echo date('M d, Y', strtotime($slot['slot_date'])); ?></h4>
                                    <p><strong>⏰ Time:</strong> <?php echo htmlspecialchars($slot['slot_time']); ?></p>
                                    <p><strong>🏛️ Hall:</strong> <?php echo htmlspecialchars($slot['hall_name']); ?></p>
                                    <div class="capacity-indicator">
                                        👥 <?php echo $slot['registered_count']; ?>/<?php echo $slot['max_capacity']; ?> registered
                                        <?php if (!$is_full): ?>
                                            (<?php echo $remaining; ?> spots remaining)
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($slot['faculty_name']): ?>
                                        <div class="faculty-info">
                                            <strong>👨‍🏫 Assigned Faculty:</strong><br>
                                            <?php echo htmlspecialchars($slot['faculty_name']); ?><br>
                                            <small><?php echo htmlspecialchars($slot['faculty_dept']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($is_full): ?>
                                    <button class="btn btn-disabled" disabled>🚫 Slot Full</button>
                                <?php else: ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="register_slot" class="btn btn-primary" 
                                                onclick="return confirm('Are you sure you want to register for this IRA slot?')">
                                            📝 Register for IRA
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
                
                <?php if (empty($available_slots)): ?>
                    <p>No slots available for this event yet. Admin needs to create slots first.</p>
                <?php else: ?>
                    <?php foreach ($available_slots as $slot): ?>
                        <div class="slot-card <?php echo $slot['registered_count'] >= $slot['max_capacity'] ? 'full-slot' : ''; ?>">
                            <div class="slot-info">
                                <h4><?php echo date('M d, Y', strtotime($slot['slot_date'])); ?> - <?php echo $slot['slot_time']; ?></h4>
                                <p><strong>Hall:</strong> <?php echo htmlspecialchars($slot['hall_name']); ?></p>
                                <p><strong>Faculty Reviewer:</strong> <?php echo htmlspecialchars($slot['faculty_name']); ?> (<?php echo htmlspecialchars($slot['faculty_dept']); ?>)</p>
                                <div class="slot-capacity">
                                    Capacity: <?php echo $slot['registered_count']; ?>/<?php echo $slot['max_capacity']; ?> students
                                </div>
                            </div>
                            <div>
                                <?php if ($slot['registered_count'] >= $slot['max_capacity']): ?>
                                    <button class="btn btn-disabled" disabled>Slot Full</button>
                                <?php else: ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="register_slot" class="btn btn-success" 
                                                onclick="return confirm('Register for this IRA slot?')">
                                            Register for Slot
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- All IRA Events List -->
            <div class="section">
                <h2>🎯 Available IRA Events</h2>
                
                <?php if (empty($all_ira_events)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <h3>No IRA Events Available</h3>
                        <p>IRA events will appear here once admin approves events with IRA requirement.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_ira_events as $ira_event): ?>
                        <div class="event-card">
                            <h3><?php echo htmlspecialchars($ira_event['event_name']); ?></h3>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($ira_event['event_date'])); ?></p>
                                    <p><strong>Domain:</strong> <?php echo htmlspecialchars($ira_event['domain']); ?></p>
                                    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($ira_event['event_organizer']); ?></p>
                                    <p><strong>Available Slots:</strong> <?php echo $ira_event['slot_count']; ?> slots</p>
                                </div>
                                <div>
                                    <?php if ($ira_event['slot_count'] > 0): ?>
                                        <a href="ira_register.php?event_id=<?php echo $ira_event['id']; ?>" class="btn btn-primary">
                                            View Slots & Register
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>No Slots Yet</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- User's Registrations -->
        <div class="registrations-table">
            <div class="table-header">
                📋 My IRA Registrations
            </div>
            
            <?php if (empty($registrations)): ?>
                <div style="padding: 4rem 2rem; text-align: center; color: #6b7280; background: white;">
                    <div style="display: inline-block; padding: 1.5rem; background: #f3f4f6; border-radius: 50%; margin-bottom: 1.5rem;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #9ca3af;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                    </div>
                    <h3 style="margin-bottom: 0.5rem; color: #374151; font-weight: 600;">No IRA Registrations</h3>
                    <p style="margin-bottom: 1.5rem;">You haven't registered for any IRA events yet.</p>
                    <a href="ira_register.php" class="btn btn-primary" style="text-decoration: none;">
                        Browse Available Events
                    </a>
                </div>
            <?php else: ?>
                <table style="background: white;">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Assigned Faculty</th>
                            <th>Status</th>
                            <th>Evaluation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr style="background: white;">
                                <td>
                                    <div style="font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($reg['event_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: #374151;">
                                        <?php echo date('M d, Y', strtotime($reg['slot_date'])); ?>
                                    </div>
                                    <div style="font-size: 0.875rem; color: #6b7280;">
                                        <?php echo $reg['slot_time']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="color: #374151;">
                                        <?php echo htmlspecialchars($reg['hall_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: #374151;">
                                        <?php echo htmlspecialchars($reg['faculty_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $status = $reg['registration_status'];
                                    $status_class = '';
                                    if ($status == 'Eligible') {
                                        $status_class = 'status-approved';
                                    } elseif ($status == 'Not Eligible') {
                                        $status_class = 'status-rejected';
                                    } else {
                                        $status_class = 'status-pending';
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reg['evaluation_status']): ?>
                                        <div style="font-weight: 500; color: #374151;">
                                            <?php echo htmlspecialchars($reg['evaluation_status']); ?>
                                        </div>
                                        <?php if ($reg['evaluation_remarks']): ?>
                                            <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($reg['evaluation_remarks']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-style: italic;">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
