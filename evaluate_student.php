<?php
session_start();

// Check if user is logged in and is a reviewer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reviewer') {
    header("Location: login.php");
    exit();
}

include('connection.php');

$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    header("Location: ira_register.php");
    exit();
}

// Fetch student details
$sql = "SELECT * FROM ira_registered_students WHERE id = ? AND assigned_reviewer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Student not found or not assigned to you!'); window.location.href='ira_register.php';</script>";
    exit();
}

$student = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $evaluation_status = $_POST['evaluation_status'];
    $evaluation_remarks = $_POST['evaluation_remarks'];
    $evaluation_date = date('Y-m-d H:i:s');
    
    $update_sql = "UPDATE ira_registered_students SET 
                   evaluation_status = ?, 
                   evaluation_remarks = ?, 
                   evaluation_date = ? 
                   WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $evaluation_status, $evaluation_remarks, $evaluation_date, $student_id);
    
    if ($update_stmt->execute()) {
        echo "<script>alert('Evaluation submitted successfully!'); window.location.href='ira_register.php';</script>";
    } else {
        echo "<script>alert('Error submitting evaluation!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Student - <?php echo htmlspecialchars($student['student_name']); ?></title>
    <link rel="stylesheet" href="css/evaluate_student.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-brand">
                <h2>Event Management Portal</h2>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="ira_register.php"><i class="fas fa-book"></i> IRA Registration</a>
                <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="main-content">
            <div class="eval-header">
                <h1>Student Evaluation</h1>
                <p>Evaluate the IRA presentation for <?php echo htmlspecialchars($student['student_name']); ?></p>
            </div>

            <div class="eval-container">
                <div class="student-info">
                    <h2>Student Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Student Name:</label>
                            <span><?php echo htmlspecialchars($student['student_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Roll Number:</label>
                            <span><?php echo htmlspecialchars($student['student_roll_no']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Department:</label>
                            <span><?php echo htmlspecialchars($student['student_department']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Year:</label>
                            <span><?php echo htmlspecialchars($student['student_year']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Event Name:</label>
                            <span><?php echo htmlspecialchars($student['event_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Competition:</label>
                            <span><?php echo htmlspecialchars($student['competition_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>PS Portal Proof:</label>
                            <span><?php echo htmlspecialchars($student['ps_portal_proof']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="evaluation-form">
                    <form method="POST">
                        <div class="form-group">
                            <label for="evaluation_status">Evaluation Result:</label>
                            <select name="evaluation_status" id="evaluation_status" required>
                                <option value="">Select Result</option>
                                <option value="pass" <?php echo ($student['evaluation_status'] == 'pass') ? 'selected' : ''; ?>>Pass</option>
                                <option value="fail" <?php echo ($student['evaluation_status'] == 'fail') ? 'selected' : ''; ?>>Fail</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="evaluation_remarks">Evaluation Remarks:</label>
                            <textarea name="evaluation_remarks" id="evaluation_remarks" rows="6" placeholder="Enter your evaluation remarks here..."><?php echo htmlspecialchars($student['evaluation_remarks'] ?? ''); ?></textarea>
                        </div>

                        <?php if (!empty($student['evaluation_date'])): ?>
                            <div class="previous-eval">
                                <p><strong>Previous Evaluation:</strong> <?php echo htmlspecialchars($student['evaluation_date']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-check"></i> Submit Evaluation
                            </button>
                            <a href="ira_register.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
