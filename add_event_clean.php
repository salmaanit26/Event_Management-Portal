<?php
session_start();
include('connection_sqlite.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success = false;
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $event_name = sanitize_input($_POST['event_name']);
    $event_date = sanitize_input($_POST['event_date']);
    $event_time = sanitize_input($_POST['event_time']);
    $venue = sanitize_input($_POST['venue']);
    $description = sanitize_input($_POST['description']);
    $max_participants = (int)$_POST['max_participants'];
    $organizer_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($event_name) || empty($event_date) || empty($event_time) || empty($venue)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            $sql = "INSERT INTO event_details (event_name, event_date, event_time, venue, description, organizer_id, max_participants, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$event_name, $event_date, $event_time, $venue, $description, $organizer_id, $max_participants]);
            
            $success = true;
        } catch(PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Event Request - Event Management Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/framework.css">
    <link rel="stylesheet" href="css/add_event.css">
</head>
<body>
    <div class="container">
        <div class="form-wrapper">
            <div class="form-header">
                <div class="header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h1>New Event Request</h1>
                <p>Submit a request for approval of a new college event</p>
            </div>

            <div class="form-content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Event request submitted successfully! Your event is pending approval.
                        <a href="dashboard.php" class="btn btn-primary" style="margin-top: 10px;">Go to Dashboard</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" id="eventForm">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            <h2>Event Information</h2>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="event_name" class="form-label required">Event Name</label>
                                <input type="text" id="event_name" name="event_name" class="form-input" 
                                       placeholder="Enter the event name" required maxlength="255">
                            </div>
                            
                            <div class="form-group">
                                <label for="event_date" class="form-label required">Event Date</label>
                                <input type="date" id="event_date" name="event_date" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_time" class="form-label required">Event Time</label>
                                <input type="time" id="event_time" name="event_time" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="venue" class="form-label required">Venue</label>
                                <input type="text" id="venue" name="venue" class="form-input" 
                                       placeholder="Event venue/location" required maxlength="255">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description" class="form-label">Event Description</label>
                                <textarea id="description" name="description" class="form-textarea" 
                                          placeholder="Describe the event details, objectives, and activities" 
                                          rows="4" maxlength="1000"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_participants" class="form-label">Maximum Participants</label>
                                <input type="number" id="max_participants" name="max_participants" class="form-input" 
                                       placeholder="Expected number of participants" min="1" max="10000" value="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i>
                            Submit Event Request
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            document.getElementById('event_date').setAttribute('min', tomorrow.toISOString().split('T')[0]);
        });
    </script>
</body>
</html>
