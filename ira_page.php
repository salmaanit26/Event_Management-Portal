<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('connection.php');

// Handle delete registration request (Admin only)
if ($_POST['action'] == 'delete_registration' && $_SESSION['role'] == 'admin') {
    $registration_id = intval($_POST['delete_registration_id']);
    
    try {
        // Delete the registration
        $delete_stmt = $conn->prepare("DELETE FROM ira_registered_students WHERE id = ?");
        $delete_stmt->execute([$registration_id]);
        
        if ($delete_stmt->rowCount() > 0) {
            $success_message = "IRA registration deleted successfully!";
        } else {
            $error_message = "Failed to delete registration. It may have already been removed.";
        }
    } catch (Exception $e) {
        $error_message = "Error deleting registration: " . $e->getMessage();
    }
}

// Fetch IRA details based on role
$role = $_SESSION['role'];
if ($role === 'admin' || $role === 'faculty') {
    // Admin and faculty see all registered students
    $sql = "SELECT s.*, e.event_name, sl.slot_date, sl.slot_time, sl.hall_name,
            (SELECT full_name FROM users WHERE id = sl.assigned_faculty) AS faculty_name
            FROM ira_registered_students s
            LEFT JOIN event_details e ON s.event_id = e.id
            LEFT JOIN slots sl ON s.slot_id = sl.id";
    $result = $conn->query($sql);
} else {
    // Students see only their details
    $user_email = $_SESSION['email'];
    $sql = "SELECT s.*, e.event_name, sl.slot_date, sl.slot_time, sl.hall_name,
            (SELECT full_name FROM users WHERE id = sl.assigned_faculty) AS faculty_name
            FROM ira_registered_students s
            LEFT JOIN event_details e ON s.event_id = e.id
            LEFT JOIN slots sl ON s.slot_id = sl.id
            WHERE s.student_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRA Page</title>
    <link rel="stylesheet" href="css/ira_page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="ira_screen">
        <div class="sidebar">
            <div class="nav">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
                <a href="status.php"><i class="fas fa-tasks"></i>Status</a>
                <a href="results_page.php"><i class="fas fa-chart-line"></i>Results</a>
                <a href="ira_register.php" class="active"><i class="fas fa-book"></i> IRA Registration</a>
                <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="main_content">
            <?php if (isset($success_message)): ?>
                <div class="message success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="header">
                <h1>Welcome <?php echo $_SESSION['email']; ?>,</h1>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="header-buttons">
                        <button class="add_slot_button button-standard" onclick="location.href='add_slot.php';">Add Slot</button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="ira_details">
                <h2>IRA Details</h2>
                <div class="te_container">
                    <table>
                        <thead>
                            <tr>
                                <th>S.NO</th>
                                <th>Event Name</th>
                                <th>Event Date</th>
                                <th>Competition Name</th>
                                <th>PS Portal Proof</th>
                                <th>Student Name</th>
                                <th>Student Roll No</th>
                                <th>Student Department</th>
                                <th>Student Mail ID</th>
                                <th>Year</th>
                                <th>IRA</th>
                                <th>Faculty</th>
                                <th>Slot Details</th>
                                <th>Status</th>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] == 'faculty'): ?>
                                    <th>Evaluate</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                $sno = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $sno++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['event_name'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['event_date'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['competition_name'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ps_portal_proof'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_name'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_roll_no'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_department'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_year'] ?? '') . "</td>";
                                    echo "<td><span class='registered'>REGISTERED</span></td>";
                                    echo "<td>" . (!empty($row['faculty_name']) ? htmlspecialchars($row['faculty_name']) : "Not Assigned") . "</td>";
                                    
                                    // Slot details
                                    if (!empty($row['slot_date']) && !empty($row['slot_time'])) {
                                        echo "<td>" . htmlspecialchars($row['slot_date']) . " at " . htmlspecialchars($row['slot_time']) . "</td>";
                                    } else {
                                        echo "<td><a href='slot_booking_form.php?id=" . $row['id'] . "'>Book Slot</a></td>";
                                    }
                                    
                                    // Status based on role
                                    if ($_SESSION['role'] == 'faculty') {
                                        $status = $row['evaluation_status'] ?? 'pending';
                                        if ($status == 'Eligible') {
                                            echo "<td><span class='passed'>ELIGIBLE</span></td>";
                                        } elseif ($status == 'Not Eligible') {
                                            echo "<td><span class='failed'>NOT ELIGIBLE</span></td>";
                                        } else {
                                            echo "<td><span class='in_progress'>PENDING</span></td>";
                                        }
                                    } else {
                                        $status = $row['registration_status'] ?? 'Pending Review';
                                        if ($status == 'Eligible') {
                                            echo "<td><span class='passed'>ELIGIBLE</span></td>";
                                        } elseif ($status == 'Not Eligible') {
                                            echo "<td><span class='failed'>NOT ELIGIBLE</span></td>";
                                        } else {
                                            echo "<td><span class='in_progress'>PENDING REVIEW</span></td>";
                                        }
                                    }
                                    
                                    if ($_SESSION['role'] == 'admin') {
                                        echo "<td class='action-buttons'>";
                                        echo "<a href='edit_reviewer.php?id=" . htmlspecialchars($row['id']) . "' class='edit-link'>Edit</a>";
                                        echo "<a href='#' onclick='deleteRegistration(" . $row['id'] . ")' class='delete-link'>Delete</a>";
                                        echo "</td>";
                                    }
                                    
                                    if ($_SESSION['role'] == 'faculty') {
                                        // Faculty can evaluate students in their assigned slots
                                        echo "<td><a href='faculty_dashboard.php' class='evaluate-btn'>Go to Dashboard</a></td>";
                                    }
                                    echo "</tr>";
                                }
                            } else {
                                $colspan = ($_SESSION['role'] == 'admin') ? '15' : (($_SESSION['role'] == 'faculty') ? '15' : '14');
                                echo "<tr><td colspan='" . $colspan . "'>No IRA registrations found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        function deleteRegistration(id) {
            if (confirm('Are you sure you want to delete this IRA registration? This action cannot be undone.')) {
                // Create a form and submit it to delete the registration
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'ira_register.php';
                
                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'delete_registration_id';
                idInput.value = id;
                
                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_registration';
                
                form.appendChild(idInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<?php
if (isset($stmt) && $stmt) {
    $stmt->close();
}
$conn->close();
?>
