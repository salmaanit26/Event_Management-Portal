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

// Handle delete operations
if ($_POST) {
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'delete_registration':
                try {
                    $registration_id = intval($_POST['registration_id']);
                    $stmt = $conn->prepare("DELETE FROM ira_registered_students WHERE id = ?");
                    $stmt->execute([$registration_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $success_message = "IRA registration deleted successfully!";
                    } else {
                        $error_message = "Failed to delete registration.";
                    }
                } catch (Exception $e) {
                    $error_message = "Error deleting registration: " . $e->getMessage();
                }
                break;
                
            case 'delete_slot':
                try {
                    $slot_id = intval($_POST['slot_id']);
                    
                    // Check if slot has registrations
                    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM ira_registered_students WHERE slot_id = ?");
                    $check_stmt->execute([$slot_id]);
                    $registration_count = $check_stmt->fetchColumn();
                    
                    if ($registration_count > 0) {
                        $error_message = "Cannot delete slot. There are " . $registration_count . " student registrations for this slot.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM slots WHERE id = ?");
                        $stmt->execute([$slot_id]);
                        $success_message = "Slot deleted successfully!";
                    }
                } catch (Exception $e) {
                    $error_message = "Error deleting slot: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch IRA registrations
$registrations_query = "
    SELECT r.*, e.event_name, s.slot_date, s.slot_time, s.hall_name,
           u.full_name as faculty_name
    FROM ira_registered_students r
    LEFT JOIN event_details e ON r.event_id = e.id
    LEFT JOIN slots s ON r.slot_id = s.id
    LEFT JOIN users u ON s.assigned_faculty = u.id
    ORDER BY r.created_at DESC
";
$registrations = $conn->query($registrations_query)->fetchAll();

// Fetch slots
$slots_query = "
    SELECT s.*, e.event_name, u.full_name as faculty_name,
           (SELECT COUNT(*) FROM ira_registered_students WHERE slot_id = s.id) as registered_count
    FROM slots s
    LEFT JOIN event_details e ON s.event_id = e.id
    LEFT JOIN users u ON s.assigned_faculty = u.id
    ORDER BY s.slot_date DESC, s.slot_time DESC
";
$slots = $conn->query($slots_query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management Panel - Event Management</title>
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
            text-align: center;
        }
        
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .nav-tab {
            flex: 1;
            padding: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .nav-tab.active {
            background: white;
            color: #495057;
            border-bottom: 3px solid #667eea;
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
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
        
        .btn-edit { background: #3b82f6; color: white; }
        .btn-edit:hover { background: #2563eb; }
        
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
        .status-pending { background: #fef3c7; color: #92400e; }
        
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ Admin Management Panel</h1>
            <p>Manage IRA registrations and time slots</p>
        </div>
        
        <div style="padding: 1rem;">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
        
        <?php if ($success_message): ?>
            <div style="padding: 0 2rem;">
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div style="padding: 0 2rem;">
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            </div>
        <?php endif; ?>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('registrations')">📝 IRA Registrations (<?php echo count($registrations); ?>)</button>
            <button class="nav-tab" onclick="showTab('slots')">⏰ Time Slots (<?php echo count($slots); ?>)</button>
        </div>
        
        <!-- IRA Registrations Tab -->
        <div id="registrations" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($registrations); ?></div>
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
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Event</th>
                            <th>Slot</th>
                            <th>Faculty</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($reg['student_email']); ?></small><br>
                                <small><?php echo htmlspecialchars($reg['student_department']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                            <td>
                                <?php if ($reg['slot_date']): ?>
                                    <?php echo date('M d, Y', strtotime($reg['slot_date'])); ?><br>
                                    <small><?php echo htmlspecialchars($reg['slot_time']); ?></small><br>
                                    <small><?php echo htmlspecialchars($reg['hall_name']); ?></small>
                                <?php else: ?>
                                    <em>No slot assigned</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($reg['faculty_name'] ?? 'Not assigned'); ?></td>
                            <td>
                                <span class="status status-<?php echo strtolower(str_replace(' ', '-', $reg['registration_status'])); ?>">
                                    <?php echo htmlspecialchars($reg['registration_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="edit_reviewer.php?id=<?php echo $reg['id']; ?>" class="btn btn-edit">Edit</a>
                                <button class="btn btn-delete" onclick="deleteRegistration(<?php echo $reg['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #6c757d;">
                                No IRA registrations found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Slots Tab -->
        <div id="slots" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($slots); ?></div>
                    <div>Total Slots</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo array_sum(array_column($slots, 'registered_count')); ?>
                    </div>
                    <div>Total Registrations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <a href="manage_slots.php" style="color: white; text-decoration: none;">+ Add New</a>
                    </div>
                    <div>Create Slot</div>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Faculty</th>
                            <th>Capacity</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($slot['event_name']); ?></td>
                            <td>
                                <?php echo date('M d, Y', strtotime($slot['slot_date'])); ?><br>
                                <small><?php echo htmlspecialchars($slot['slot_time']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($slot['hall_name']); ?></td>
                            <td><?php echo htmlspecialchars($slot['faculty_name']); ?></td>
                            <td><?php echo $slot['max_capacity']; ?> students</td>
                            <td>
                                <span class="<?php echo $slot['registered_count'] >= $slot['max_capacity'] ? 'status-not-eligible' : 'status-eligible'; ?> status">
                                    <?php echo $slot['registered_count']; ?>/<?php echo $slot['max_capacity']; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="manage_slots.php" class="btn btn-edit">Edit</a>
                                <button class="btn btn-delete" onclick="deleteSlot(<?php echo $slot['id']; ?>, <?php echo $slot['registered_count']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($slots)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #6c757d;">
                                No slots found. <a href="manage_slots.php">Create your first slot</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function deleteRegistration(id) {
            if (confirm('Are you sure you want to delete this IRA registration? This action cannot be undone.')) {
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
        
        function deleteSlot(id, registrationCount) {
            if (registrationCount > 0) {
                alert(`Cannot delete this slot. There are ${registrationCount} student registrations for this slot. Please remove the registrations first.`);
                return;
            }
            
            if (confirm('Are you sure you want to delete this slot? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_slot">
                    <input type="hidden" name="slot_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
