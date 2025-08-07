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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
        }
        
        .event-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #e74c3c;
        }
        
        .slot-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .slot-info {
            flex-grow: 1;
        }
        
        .slot-capacity {
            color: #666;
            font-size: 14px;
        }
        
        .full-slot {
            background: #ffebee;
            border-color: #f44336;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-disabled { background: #6c757d; color: white; cursor: not-allowed; }
        
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
        
        .status-pending { color: #ffc107; }
        .status-approved { color: #28a745; }
        .status-rejected { color: #dc3545; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 IRA Registration Portal</h1>
            <p>Register for Internal Review Assessment events</p>
        </div>
        
        <div class="nav-buttons">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="status.php">My Event Suggestions</a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($event): ?>
            <!-- Specific Event Registration -->
            <div class="section">
                <div class="event-card">
                    <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></p>
                    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organizer']); ?></p>
                    <p><strong>Domain:</strong> <?php echo htmlspecialchars($event['domain']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?></p>
                    <?php if ($event['event_description']): ?>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <h3>📅 Available IRA Slots</h3>
                
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
        <div class="section">
            <h2>📋 My IRA Registrations</h2>
            
            <?php if (empty($registrations)): ?>
                <p>You haven't registered for any IRA events yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Date & Time</th>
                            <th>Hall</th>
                            <th>Faculty Reviewer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['event_name']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($reg['slot_date'])); ?><br>
                                    <small><?php echo $reg['slot_time']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reg['hall_name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['faculty_name']); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower(str_replace(' ', '-', $reg['registration_status'])); ?>">
                                        <?php echo $reg['registration_status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section" style="background: #f8f9fa; border-radius: 10px;">
            <h3>📋 How IRA Registration Works:</h3>
            <ol>
                <li><strong>Browse IRA Events:</strong> View events that require Internal Review Assessment</li>
                <li><strong>Select Time Slot:</strong> Choose from available slots created by admin</li>
                <li><strong>Register:</strong> Submit your registration for the chosen slot</li>
                <li><strong>Faculty Review:</strong> Assigned faculty will review your registration</li>
                <li><strong>Participation Decision:</strong> Faculty decides if you can attend the event</li>
            </ol>
        </div>
    </div>
</body>
</html>
