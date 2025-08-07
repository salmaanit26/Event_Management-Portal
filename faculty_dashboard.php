<?php
session_start();
require_once 'connection_sqlite.php';

// Check if user is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'faculty') {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

// Handle evaluation submission
if ($_POST && isset($_POST['submit_evaluation'])) {
    try {
        $registration_id = intval($_POST['registration_id']);
        $evaluation_status = $_POST['evaluation_status'];
        $evaluation_remarks = $_POST['evaluation_remarks'];
        $reviewer_id = $_SESSION['user_id'];
        
        // Update the registration with evaluation
        $stmt = $conn->prepare("
            UPDATE ira_registered_students 
            SET evaluation_status = ?, evaluation_remarks = ?, evaluated_by = ?, evaluated_at = datetime('now'), registration_status = ?
            WHERE id = ?
        ");
        $stmt->execute([$evaluation_status, $evaluation_remarks, $reviewer_id, $evaluation_status, $registration_id]);
        
        if ($stmt->rowCount() > 0) {
            $success_message = "Evaluation submitted successfully!";
        } else {
            $error_message = "Failed to submit evaluation. Please try again.";
        }
    } catch (Exception $e) {
        $error_message = "Error submitting evaluation: " . $e->getMessage();
    }
}

// Get all registrations for slots where this faculty is assigned
$reviewer_id = $_SESSION['user_id'];
$registrations = $conn->prepare("
    SELECT r.*, e.event_name, s.slot_date, s.slot_time, s.hall_name, u.full_name as faculty_name
    FROM ira_registered_students r 
    LEFT JOIN event_details e ON r.event_id = e.id 
    LEFT JOIN slots s ON r.slot_id = s.id 
    LEFT JOIN users u ON s.assigned_faculty = u.id 
    WHERE s.assigned_faculty = ?
    ORDER BY r.created_at DESC
");
$registrations->execute([$reviewer_id]);
$registrations = $registrations->fetchAll();

// Get reviewer info
$reviewer_info = $conn->prepare("SELECT full_name, department FROM users WHERE id = ?");
$reviewer_info->execute([$_SESSION['user_id']]);
$reviewer_info = $reviewer_info->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Evaluation Panel - Event Management</title>
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
        }
        
        .evaluation-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .student-info {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
        }
        
        .form-group {
            margin: 1.5rem 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
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
        
        .btn-danger { 
            background: #ef4444; 
            color: white; 
        }
        .btn-danger:hover { 
            background: #dc2626; 
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
        
        .status-pending { 
            background: #fef3c7; 
            color: #92400e; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-eligible { 
            background: #dcfce7; 
            color: #166534; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-not-eligible { 
            background: #fecaca; 
            color: #991b1b; 
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 500;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>👨‍🏫 Faculty Evaluation Panel</h1>
            <div class="nav-links">
                <a href="faculty_dashboard.php">📊 Dashboard</a>
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>👨‍🏫 Welcome, <?php echo htmlspecialchars($reviewer_info['full_name']); ?></h2>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($reviewer_info['department']); ?></p>
            <p>Evaluate IRA student registrations assigned to you</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($registrations); ?></div>
                <div class="stat-label">Total Assigned</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($registrations, function($r) { return $r['evaluation_status'] == 'Eligible'; })); ?></div>
                <div class="stat-label">Marked Eligible</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($registrations, function($r) { return $r['evaluation_status'] == 'Not Eligible'; })); ?></div>
                <div class="stat-label">Marked Not Eligible</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($registrations, function($r) { return !$r['evaluation_status']; })); ?></div>
                <div class="stat-label">Pending Evaluation</div>
            </div>
        </div>
        
        <?php if (empty($registrations)): ?>
            <div class="evaluation-card">
                <h3>📋 No Assignments</h3>
                <p>You currently have no IRA registrations assigned for evaluation.</p>
                <p>Please contact the admin if you believe this is an error.</p>
            </div>
        <?php else: ?>
            <?php foreach ($registrations as $reg): ?>
                <div class="evaluation-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <h3>📋 IRA Evaluation</h3>
                        <?php if ($reg['evaluation_status']): ?>
                            <span class="status-<?php echo strtolower(str_replace(' ', '-', $reg['evaluation_status'])); ?>">
                                <?php echo $reg['evaluation_status']; ?>
                            </span>
                        <?php else: ?>
                            <span class="status-pending">Pending Evaluation</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="student-info">
                        <h4>👤 Student Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($reg['student_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($reg['student_email']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($reg['student_department']); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($reg['student_year']); ?></p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 1rem 0;">
                        <div>
                            <h4>🎯 Event Details</h4>
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($reg['event_name']); ?></p>
                            <p><strong>Registration Status:</strong> <?php echo $reg['registration_status']; ?></p>
                        </div>
                        <div>
                            <h4>⏰ Slot Details</h4>
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($reg['slot_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($reg['slot_time']); ?></p>
                            <p><strong>Hall:</strong> <?php echo htmlspecialchars($reg['hall_name']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($reg['evaluation_status']): ?>
                        <!-- Show existing evaluation -->
                        <div style="background: #f1f5f9; padding: 1.5rem; border-radius: 8px; margin-top: 1rem;">
                            <h4>📝 Your Previous Evaluation</h4>
                            <p><strong>Status:</strong> <?php echo $reg['evaluation_status']; ?></p>
                            <p><strong>Remarks:</strong> <?php echo htmlspecialchars($reg['evaluation_remarks']); ?></p>
                            <p><strong>Evaluated At:</strong> <?php echo date('M d, Y H:i', strtotime($reg['evaluated_at'])); ?></p>
                        </div>
                        
                        <!-- Allow re-evaluation -->
                        <form method="post" style="margin-top: 1rem;">
                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                            <div class="form-group">
                                <label>Update Evaluation:</label>
                                <select name="evaluation_status" required>
                                    <option value="">Select Status</option>
                                    <option value="Eligible" <?php echo ($reg['evaluation_status'] == 'Eligible') ? 'selected' : ''; ?>>✅ Eligible</option>
                                    <option value="Not Eligible" <?php echo ($reg['evaluation_status'] == 'Not Eligible') ? 'selected' : ''; ?>>❌ Not Eligible</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Updated Remarks:</label>
                                <textarea name="evaluation_remarks" rows="3" placeholder="Update your evaluation remarks..."><?php echo htmlspecialchars($reg['evaluation_remarks']); ?></textarea>
                            </div>
                            <button type="submit" name="submit_evaluation" class="btn btn-primary">Update Evaluation</button>
                        </form>
                    <?php else: ?>
                        <!-- New evaluation form -->
                        <form method="post" style="margin-top: 1rem;">
                            <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                            <div class="form-group">
                                <label>Evaluation Status: *</label>
                                <select name="evaluation_status" required>
                                    <option value="">Select Evaluation Status</option>
                                    <option value="Eligible">✅ Eligible - Student meets IRA requirements</option>
                                    <option value="Not Eligible">❌ Not Eligible - Student does not meet requirements</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Evaluation Remarks: *</label>
                                <textarea name="evaluation_remarks" rows="4" required placeholder="Provide detailed feedback about the student's eligibility, performance, and any recommendations..."></textarea>
                            </div>
                            <div style="text-align: right;">
                                <button type="submit" name="submit_evaluation" class="btn btn-primary">Submit Evaluation</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
