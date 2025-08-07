<?php
session_start();
require_once 'connection_sqlite.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

// Handle faculty assignment
if ($_POST && isset($_POST['assign_faculty'])) {
    try {
        $registration_id = intval($_POST['registration_id']);
        $faculty_id = intval($_POST['faculty_id']);
        
        $stmt = $conn->prepare("UPDATE ira_registered_students SET assigned_reviewer = ? WHERE id = ?");
        $stmt->execute([$faculty_id, $registration_id]);
        
        $success_message = "Faculty assigned successfully!";
    } catch (Exception $e) {
        $error_message = "Error assigning faculty: " . $e->getMessage();
    }
}

// Get all faculty members
$faculty = $conn->query("SELECT id, full_name, department FROM users WHERE role = 'faculty' ORDER BY full_name")->fetchAll();

// Get all IRA registrations that need faculty assignment
$registrations = $conn->query("
    SELECT r.*, e.event_name, s.slot_date, s.slot_time, s.hall_name, u.full_name as faculty_name
    FROM ira_registered_students r 
    LEFT JOIN event_details e ON r.event_id = e.id 
    LEFT JOIN slots s ON r.slot_id = s.id 
    LEFT JOIN users u ON r.assigned_reviewer = u.id 
    ORDER BY r.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - Admin Panel</title>
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
        
        .reviewers-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .registrations-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        
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
        
        .reviewer-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1rem 0;
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
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>🎯 Event Management Portal</h1>
            <div class="nav-links">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="manage_slots.php">⏰ Manage Slots</a>
                <a href="edit_reviewer.php">👥 Faculty</a>
                <a href="status.php">📋 All Events</a>
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>👥 Faculty Management</h2>
            <p>Assign faculty to IRA registrations and manage faculty assignments</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Available Faculty Section -->
        <div class="reviewers-section">
            <h3>👨‍🏫 Available Faculty</h3>
            <?php if (empty($faculty)): ?>
                <p>No faculty found. Please add faculty to the system first.</p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <?php foreach ($faculty as $faculty_member): ?>
                        <div class="reviewer-card">
                            <h4><?php echo htmlspecialchars($faculty_member['full_name']); ?></h4>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($faculty_member['department']); ?></p>
                            <p><strong>ID:</strong> <?php echo $faculty_member['id']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- IRA Registrations Table -->
        <div class="registrations-table">
            <div class="table-header">
                📋 IRA Student Registrations - Reviewer Assignment
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Event</th>
                        <th>Slot Details</th>
                        <th>Status</th>
                        <th>Assigned Faculty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #6b7280;">
                                No IRA registrations found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($reg['student_email']); ?></small>
                                    <br><small><?php echo htmlspecialchars($reg['student_department']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($reg['slot_date'])); ?><br>
                                    <small><?php echo $reg['slot_time']; ?></small><br>
                                    <small><?php echo htmlspecialchars($reg['hall_name']); ?></small>
                                </td>
                                <td>
                                    <span class="status-<?php echo strtolower(str_replace(' ', '-', $reg['registration_status'])); ?>">
                                        <?php echo $reg['registration_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reg['faculty_name']): ?>
                                        <strong><?php echo htmlspecialchars($reg['faculty_name']); ?></strong>
                                    <?php else: ?>
                                        <em style="color: #6b7280;">Not assigned</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                        <select name="faculty_id" required style="margin-right: 0.5rem; padding: 0.25rem;">
                                            <option value="">Select Faculty</option>
                                            <?php foreach ($faculty as $faculty_member): ?>
                                                <option value="<?php echo $faculty_member['id']; ?>" 
                                                    <?php echo ($reg['assigned_reviewer'] == $faculty_member['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($faculty_member['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_faculty" class="btn btn-primary">Assign</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 16px; padding: 2rem; margin-top: 2rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
            <h3>📋 Instructions:</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 0.5rem 0; color: #4a5568;">• <strong>Assign Faculty:</strong> Select appropriate faculty for each IRA registration</li>
                <li style="padding: 0.5rem 0; color: #4a5568;">• <strong>Faculty Access:</strong> Assigned faculty will be able to evaluate students</li>
                <li style="padding: 0.5rem 0; color: #4a5568;">• <strong>Department Match:</strong> Consider assigning faculty from relevant departments</li>
                <li style="padding: 0.5rem 0; color: #4a5568;">• <strong>Workload Balance:</strong> Distribute assignments evenly among available faculty</li>
            </ul>
        </div>
    </div>
</body>
</html>
