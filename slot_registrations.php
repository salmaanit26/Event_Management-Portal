<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include('connection.php');

$success_message = "";
$error_message = "";

// Handle delete registration
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'delete_registration') {
    try {
        $registration_id = intval($_POST['registration_id']);
        $stmt = $conn->prepare("DELETE FROM ira_registered_students WHERE id = ?");
        $stmt->execute([$registration_id]);
        
        if ($stmt->rowCount() > 0) {
            $success_message = "Student registration deleted successfully!";
        } else {
            $error_message = "Failed to delete registration.";
        }
    } catch (Exception $e) {
        $error_message = "Error deleting registration: " . $e->getMessage();
    }
}

// Get slot ID from URL
$slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : null;

if (!$slot_id) {
    header("Location: manage_slots.php");
    exit();
}

// Get slot details
$slot_query = "
    SELECT s.*, e.event_name, u.full_name as faculty_name, u.email as faculty_email
    FROM slots s
    LEFT JOIN event_details e ON s.event_id = e.id
    LEFT JOIN users u ON s.assigned_faculty = u.id
    WHERE s.id = ?
";
$slot_stmt = $conn->prepare($slot_query);
$slot_stmt->execute([$slot_id]);
$slot = $slot_stmt->fetch();

if (!$slot) {
    header("Location: manage_slots.php");
    exit();
}

// Get all registrations for this slot
$registrations_query = "
    SELECT r.*, u.email as user_email, u.department as user_department
    FROM ira_registered_students r
    LEFT JOIN users u ON r.student_id = u.id
    WHERE r.slot_id = ?
    ORDER BY r.created_at DESC
";
$registrations_stmt = $conn->prepare($registrations_query);
$registrations_stmt->execute([$slot_id]);
$registrations = $registrations_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Registrations - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
        }
        
        .header h1 {
            margin-bottom: 0.5rem;
        }
        
        .header-info {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .header-info h3 {
            margin-bottom: 0.5rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .back-link:hover {
            background: #5a6268;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
        .error { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-eligible { background: #d1fae5; color: #065f46; }
        .status-not-eligible { background: #fee2e2; color: #991b1b; }
        .status-pending-review { background: #fef3c7; color: #92400e; }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
        }
        
        .student-info {
            margin-bottom: 0.5rem;
        }
        
        .student-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .student-details {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .capacity-info {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .capacity-bar {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .capacity-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .capacity-normal { background: #10b981; }
        .capacity-full { background: #f59e0b; }
        .capacity-over { background: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Slot Registration Management</h1>
            <p>Manage student registrations for specific time slot</p>
            
            <div class="header-info">
                <h3>🎯 <?php echo htmlspecialchars($slot['event_name']); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div>
                        <strong>📅 Date & Time:</strong><br>
                        <?php echo date('l, M d, Y', strtotime($slot['slot_date'])); ?><br>
                        <?php echo htmlspecialchars($slot['slot_time']); ?>
                    </div>
                    <div>
                        <strong>🏢 Venue:</strong><br>
                        <?php echo htmlspecialchars($slot['hall_name']); ?>
                    </div>
                    <div>
                        <strong>👨‍🏫 Assigned Faculty:</strong><br>
                        <?php echo htmlspecialchars($slot['faculty_name']); ?><br>
                        <small><?php echo htmlspecialchars($slot['faculty_email']); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <a href="manage_slots.php" class="back-link">← Back to Manage Slots</a>
            
            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <!-- Capacity Information -->
            <div class="capacity-info">
                <h3>📊 Slot Capacity</h3>
                <?php 
                $registered_count = count($registrations);
                $max_capacity = $slot['max_capacity'];
                $percentage = $max_capacity > 0 ? ($registered_count / $max_capacity) * 100 : 0;
                $capacity_class = $percentage >= 100 ? 'capacity-over' : ($percentage >= 80 ? 'capacity-full' : 'capacity-normal');
                ?>
                <p><strong><?php echo $registered_count; ?></strong> out of <strong><?php echo $max_capacity; ?></strong> students registered (<?php echo round($percentage, 1); ?>%)</p>
                <div class="capacity-bar">
                    <div class="capacity-fill <?php echo $capacity_class; ?>" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $registered_count; ?></div>
                    <div>Total Registrations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_filter($registrations, function($r) { return $r['registration_status'] == 'Eligible'; })); ?>
                    </div>
                    <div>Eligible Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_filter($registrations, function($r) { return $r['registration_status'] == 'Pending Review'; })); ?>
                    </div>
                    <div>Pending Review</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $max_capacity - $registered_count; ?></div>
                    <div>Available Spots</div>
                </div>
            </div>
            
            <!-- Registrations Table -->
            <?php if (!empty($registrations)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student Information</th>
                            <th>Department & Year</th>
                            <th>Registration Status</th>
                            <th>Faculty Evaluation</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-name"><?php echo htmlspecialchars($reg['student_name']); ?></div>
                                    <div class="student-details"><?php echo htmlspecialchars($reg['student_email']); ?></div>
                                    <?php if ($reg['user_email'] && $reg['user_email'] != $reg['student_email']): ?>
                                        <div class="student-details">User: <?php echo htmlspecialchars($reg['user_email']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($reg['student_department']); ?></strong><br>
                                <small><?php echo htmlspecialchars($reg['student_year']); ?></small>
                            </td>
                            <td>
                                <span class="status status-<?php echo strtolower(str_replace(' ', '-', $reg['registration_status'])); ?>">
                                    <?php echo htmlspecialchars($reg['registration_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($reg['evaluation_status']): ?>
                                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $reg['evaluation_status'])); ?>">
                                        <?php echo htmlspecialchars($reg['evaluation_status']); ?>
                                    </span>
                                    <?php if ($reg['evaluation_remarks']): ?>
                                        <br><small><?php echo htmlspecialchars($reg['evaluation_remarks']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($reg['evaluated_at']): ?>
                                        <br><small>Evaluated: <?php echo date('M d, Y', strtotime($reg['evaluated_at'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status status-pending-review">Not Evaluated</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($reg['created_at'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($reg['created_at'])); ?></small>
                            </td>
                            <td class="action-buttons">
                                <button class="btn btn-delete" onclick="deleteRegistration(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['student_name']); ?>')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>📝 No Registrations Yet</h3>
                <p>No students have registered for this slot yet.</p>
                <p>Students can register through the <strong>IRA Registration</strong> portal.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deleteRegistration(id, studentName) {
            if (confirm(`Are you sure you want to delete the registration for "${studentName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_registration">
                    <input type="hidden" name="registration_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
