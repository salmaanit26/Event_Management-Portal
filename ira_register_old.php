<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('connection_sqlite.php');

$user_id = $_SESSION['user_id'];

// Fetch event details
$event = null;
$event_id = null;
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    $sql = "SELECT * FROM event_details WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
}

$success_message = '';
$registration_exists = false;

// Check if the user has already registered for the event
$student_mail_id = $_SESSION['email']; // Assuming email is stored in session
$check_registration_sql = "SELECT * FROM IRA_registered_students WHERE event_id = ? AND student_mail_id = ?";
$check_stmt = $conn->prepare($check_registration_sql);
$check_stmt->bind_param("is", $event_id, $student_mail_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->bind_result($id, $event_id, $event_name, $event_date, $competition_name, $ps_level_cleared, $student_name, $student_roll_no, $student_department, $student_mail_id, $student_year_of_study, $reviewer_id);
    $check_stmt->fetch();
    $registration_exists = true;
} else {
    $registration_exists = false;
}

$check_stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$registration_exists) {
    $ps_level_cleared = $_POST['psScreenshot'];
    $student_name = $_POST['studentName'];
    $student_roll_no = $_POST['studentRollNo'];
    $student_department = $_POST['studentDepartment'];
    $student_mail_id = $_POST['studentMailId'];
    $student_year_of_study = $_POST['studentYearOfStudy'];

    $sql = "INSERT INTO IRA_registered_students (event_id, event_name, event_date, competition_name, ps_level_cleared, student_name, student_roll_no, student_department, student_mail_id, student_year_of_study) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("isssssssss", $event_id, $event['event_name'], $event['event_date'], $event['competition_name'], $ps_level_cleared, $student_name, $student_roll_no, $student_department, $student_mail_id, $student_year_of_study);
    if ($stmt->execute()) {
        $success_message = "IRA registration successful!";
        $registration_exists = true;
    } else {
        $success_message = "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRA Registration Form</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/ira_register.css">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const successMessage = document.querySelector(".success-message");
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = "none";
                }, 5000);
            }
        });
    </script>
</head>
<body>
    <div class="form-container">
        <div class="header">
            <h1>IRA Registration Form</h1>
            <a href="dashboard.php" class="back-icon"><span class="material-icons">arrow_circle_left</span></a>
        </div>
        <p class="note">*Each team member must individually participate in the IRA*</p>
        <?php if ($registration_exists): ?>
            <p>You have already registered for this event:</p>
            <p>Event Name: <?php echo htmlspecialchars($event_name); ?></p>
            <p>Event Date: <?php echo htmlspecialchars($event_date); ?></p>
            <p>Competition Name: <?php echo htmlspecialchars($competition_name); ?></p>
            <p>PS Level Cleared: <?php echo htmlspecialchars($ps_level_cleared); ?></p>
            <p>Student Name: <?php echo htmlspecialchars($student_name); ?></p>
            <p>Student Roll No: <?php echo htmlspecialchars($student_roll_no); ?></p>
            <p>Student Department: <?php echo htmlspecialchars($student_department); ?></p>
            <p>Student Mail ID: <?php echo htmlspecialchars($student_mail_id); ?></p>
            <p>Student Year of Study: <?php echo htmlspecialchars($student_year_of_study); ?></p>
        <?php else: ?>
            <form method="POST" action="ira_register.php?event_id=<?php echo htmlspecialchars($event_id); ?>">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="eventName">Event Name</label>
                        <input type="text" id="eventName" name="eventName" value="<?php echo htmlspecialchars($event['event_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="eventDate">Event Date</label>
                        <input type="date" id="eventDate" name="eventDate" value="<?php echo htmlspecialchars($event['event_date'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="competitionName">Competition Name</label>
                        <input type="text" id="competitionName" name="competitionName" value="<?php echo htmlspecialchars($event['competition_name'] ?? ''); ?>" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="psScreenshot">PS Level Cleared</label>
                        <input type="text" id="psScreenshot" name="psScreenshot" placeholder="1">
                    </div>
                    <div class="form-group">
                        <label for="studentName">Student Name</label>
                        <input type="text" id="studentName" name="studentName" placeholder="Enter your Name">
                    </div>
                    <div class="form-group">
                        <label for="studentRollNo">Student Roll NO</label>
                        <input type="text" id="studentRollNo" name="studentRollNo" placeholder="Enter your Roll No">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="studentDepartment">Student Department</label>
                        <input type="text" id="studentDepartment" name="studentDepartment" placeholder="Enter your Department">
                    </div>
                    <div class="form-group">
                        <label for="studentMailId">Student Mail id</label>
                        <input type="email" id="studentMailId" name="studentMailId" placeholder="Enter your Mail id">
                    </div>
                    <div class="form-group">
                        <label for="studentYearOfStudy">Student Year of study</label>
                        <input type="text" id="studentYearOfStudy" name="studentYearOfStudy" placeholder="Year">
                    </div>
                </div>
                <div class="buttons">
                    <button type="button" class="cancel-button" onclick="window.location.href='dashboard.php'">Cancel</button>
                    <button type="submit" class="register-button">Register</button>
                </div>
                <?php if ($success_message): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>