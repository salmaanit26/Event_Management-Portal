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

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_slot':
                try {
                    $event_id = intval($_POST['event_id']);
                    $slot_date = $_POST['slot_date'];
                    $slot_time = $_POST['slot_time'];
                    $hall_name = $_POST['hall_name'];
                    $assigned_faculty = intval($_POST['assigned_faculty']);
                    $max_capacity = intval($_POST['max_capacity']);
                    
                    $stmt = $conn->prepare("
                        INSERT INTO slots (event_id, slot_date, slot_time, hall_name, assigned_faculty, max_capacity, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
                    ");
                    $stmt->execute([$event_id, $slot_date, $slot_time, $hall_name, $assigned_faculty, $max_capacity]);
                    
                    $success_message = "Slot created successfully and faculty assigned!";
                } catch (Exception $e) {
                    $error_message = "Error creating slot: " . $e->getMessage();
                }
                break;
                
            case 'delete_slot':
                try {
                    $slot_id = intval($_POST['slot_id']);
                    $conn->prepare("DELETE FROM slots WHERE id = ?")->execute([$slot_id]);
                    $success_message = "Slot deleted successfully!";
                } catch (Exception $e) {
                    $error_message = "Error deleting slot: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get IRA-enabled events
$ira_events = $conn->query("
    SELECT e.*, u.full_name as applicant_name 
    FROM event_details e 
    LEFT JOIN users u ON e.applied_by = u.id 
    WHERE e.status = 'Approved' AND e.ira = 'YES'
    ORDER BY e.event_date ASC
")->fetchAll();

// Get all faculty members
$faculty = $conn->query("
    SELECT u.* FROM users u 
    WHERE u.role = 'faculty' 
    ORDER BY u.full_name
")->fetchAll();

// Get existing slots
$slots = $conn->query("
    SELECT s.*, e.event_name, u.full_name as faculty_name 
    FROM slots s 
    LEFT JOIN event_details e ON s.event_id = e.id 
    LEFT JOIN users u ON s.assigned_faculty = u.id 
    ORDER BY s.slot_date ASC, s.slot_time ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage IRA Slots - Admin Panel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .nav-buttons {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .nav-buttons a {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            display: inline-block;
        }
        
        .section {
            padding: 30px;
            margin: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            padding: 10px;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-success { background: #28a745; color: white; }
        
        .slots-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .slots-table th, .slots-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .slots-table th {
            background: #e9ecef;
            font-weight: 600;
        }
        
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-events {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 IRA Slot Management</h1>
            <p>Create time slots and assign faculty for IRA events</p>
        </div>
        
        <div class="nav-buttons">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="edit_reviewer.php">Manage Faculty</a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($ira_events)): ?>
            <div class="no-events">
                <h3>📋 No IRA Events Available</h3>
                <p>IRA-enabled events will appear here once admin approves them with IRA requirement.</p>
                <a href="dashboard_new.php" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <!-- Add New Slot Form -->
            <div class="section">
                <h2>➕ Create New IRA Slot</h2>
                
                <form method="post">
                    <input type="hidden" name="action" value="add_slot">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="event_id">Select IRA Event:</label>
                            <select name="event_id" id="event_id" required>
                                <option value="">Choose an event...</option>
                                <?php foreach ($ira_events as $event): ?>
                                    <option value="<?php echo $event['id']; ?>">
                                        <?php echo htmlspecialchars($event['event_name']); ?> 
                                        (<?php echo date('M d, Y', strtotime($event['event_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="slot_date">Slot Date:</label>
                            <input type="date" name="slot_date" id="slot_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="slot_time">Time Slot:</label>
                            <select name="slot_time" id="slot_time" required>
                                <option value="">Select time...</option>
                                <option value="09:00-10:00">09:00 - 10:00 AM</option>
                                <option value="10:00-11:00">10:00 - 11:00 AM</option>
                                <option value="11:00-12:00">11:00 - 12:00 PM</option>
                                <option value="12:00-13:00">12:00 - 01:00 PM</option>
                                <option value="14:00-15:00">02:00 - 03:00 PM</option>
                                <option value="15:00-16:00">03:00 - 04:00 PM</option>
                                <option value="16:00-17:00">04:00 - 05:00 PM</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="hall_name">Hall/Venue:</label>
                            <select name="hall_name" id="hall_name" required>
                                <option value="">Select hall...</option>
                                <option value="Auditorium">Main Auditorium</option>
                                <option value="Seminar Hall 1">Seminar Hall 1</option>
                                <option value="Seminar Hall 2">Seminar Hall 2</option>
                                <option value="Conference Room A">Conference Room A</option>
                                <option value="Conference Room B">Conference Room B</option>
                                <option value="Lab Block - Hall 1">Lab Block - Hall 1</option>
                                <option value="Lab Block - Hall 2">Lab Block - Hall 2</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="assigned_faculty">Assign Faculty:</label>
                            <select name="assigned_faculty" id="assigned_faculty" required>
                                <option value="">Choose faculty...</option>
                                <?php foreach ($faculty as $faculty_member): ?>
                                    <option value="<?php echo $faculty_member['id']; ?>">
                                        <?php echo htmlspecialchars($faculty_member['full_name']); ?>
                                        (<?php echo htmlspecialchars($faculty_member['department']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_capacity">Max Students per Slot:</label>
                            <input type="number" name="max_capacity" id="max_capacity" value="10" min="1" max="50" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create Slot & Assign Faculty</button>
                </form>
            </div>
            
            <!-- Existing Slots -->
            <div class="section">
                <h2>📅 Existing IRA Slots</h2>
                
                <?php if (empty($slots)): ?>
                    <p>No slots created yet. Create your first slot above.</p>
                <?php else: ?>
                    <table class="slots-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Hall</th>
                                <th>Assigned Faculty</th>
                                <th>Capacity</th>
                                <th>Registrations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slots as $slot): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($slot['event_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($slot['slot_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($slot['slot_time']); ?></td>
                                    <td><?php echo htmlspecialchars($slot['hall_name']); ?></td>
                                    <td><?php echo htmlspecialchars($slot['faculty_name']); ?></td>
                                    <td><?php echo $slot['max_capacity']; ?> students</td>
                                    <td>
                                        <?php
                                        $registered_count = $conn->prepare("SELECT COUNT(*) FROM ira_registered_students WHERE slot_id = ?");
                                        $registered_count->execute([$slot['id']]);
                                        $count = $registered_count->fetchColumn();
                                        echo $count . "/" . $slot['max_capacity'];
                                        ?>
                                    </td>
                                    <td>
                                        <a href="slot_registrations.php?slot_id=<?php echo $slot['id']; ?>" class="btn btn-info" style="margin-right: 8px;">
                                            View Registrations
                                        </a>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Delete this slot?')">
                                            <input type="hidden" name="action" value="delete_slot">
                                            <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h3>📋 Instructions:</h3>
            <ul>
                <li><strong>IRA Events:</strong> Only approved events with IRA enabled will appear in the dropdown</li>
                <li><strong>Faculty Assignment:</strong> Each slot must have an assigned faculty reviewer</li>
                <li><strong>Student Registration:</strong> Students can register for IRA slots through the IRA registration page</li>
                <li><strong>Faculty Decision:</strong> Assigned faculty will review student registrations and decide participation</li>
            </ul>
        </div>
    </div>
</body>
</html>
