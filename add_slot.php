<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include('connection_sqlite.php');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $slot_date = $_POST['slot_date'];
    $slot_venue = $_POST['slot_venue'];
    $slot_time = $_POST['slot_time'];
    $accommodate = intval($_POST['accommodate']);

    $sql = "INSERT INTO slots (slot_date, slot_venue, slot_time, accommodate) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $slot_date, $slot_venue, $slot_time, $accommodate);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error adding slot: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Slot</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/add_slot.css">
</head>
<body>
    <div class="ira-form">
        <div class="header">
            <h1>Slot Booking Form</h1>
            <a href="ira_register.php" style="text-decoration: none; color: black;">
                <span id="back" class="material-icons">arrow_circle_left</span>
            </a>
        </div>
        <form method="POST" action="add_slot.php">
            <div class="row">
                <div class="input-group">
                    <label for="slot_date">Slot Date</label>
                    <input type="date" id="slot_date" name="slot_date" required>
                </div>
                <div class="input-group">
                    <label for="slot_venue">Slot Venue</label>
                    <input type="text" id="slot_venue" name="slot_venue" required>
                </div>
                <div class="input-group">
                    <label for="slot_time">Slot Time</label>
                    <input type="text" id="slot_time" name="slot_time" required>
                </div>
            </div>

            <div class="row">
                <div class="input-group">
                    <label for="accommodate">No of Students Accommodate</label>
                    <input type="number" id="accommodate" name="accommodate" required>
                </div>
            </div>

            <div class="action-buttons">
                <button type="button" class="not-qualified" onclick="window.location.href='dashboard.php'">Cancel</button>
                <button type="submit" class="qualified">Add</button>
            </div>
        </form>
    </div>
</body>
</html>