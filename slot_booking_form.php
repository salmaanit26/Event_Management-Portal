<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('connection_sqlite.php');

$user_id = $_SESSION['user_id'];

// Check if the user has already booked a slot
$check_booking_sql = "SELECT slots.slot_date, slots.slot_venue, slots.slot_time FROM bookings 
                      JOIN slots ON bookings.slot_id = slots.id 
                      WHERE bookings.user_id = ?";
$check_stmt = $conn->prepare($check_booking_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->bind_result($slot_date, $slot_venue, $slot_time);
    $check_stmt->fetch();
    $booking_exists = true;
} else {
    $booking_exists = false;
}

// Fetch available slots
$sql = "SELECT id, slot_date, slot_venue, slot_time, accommodate FROM slots";
$result = $conn->query($sql);
$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$booking_exists) {
    $slot_id = intval($_POST['slot_id']);

    // Check the current number of bookings for the selected slot
    $count_sql = "SELECT COUNT(*) as booking_count FROM bookings WHERE slot_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $slot_id);
    $count_stmt->execute();
    $count_stmt->bind_result($booking_count);
    $count_stmt->fetch();
    $count_stmt->close();

    // Fetch the maximum number of students for the selected slot
    $max_students_sql = "SELECT accommodate FROM slots WHERE id = ?";
    $max_students_stmt = $conn->prepare($max_students_sql);
    $max_students_stmt->bind_param("i", $slot_id);
    $max_students_stmt->execute();
    $max_students_stmt->bind_result($max_students);
    $max_students_stmt->fetch();
    $max_students_stmt->close();

    if ($booking_count < $max_students) {
        $sql = "INSERT INTO bookings (user_id, slot_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $slot_id);

        if ($stmt->execute()) {
            // Fetch the booked slot details
            $booking_sql = "SELECT slot_date, slot_venue, slot_time FROM slots WHERE id = ?";
            $booking_stmt = $conn->prepare($booking_sql);
            $booking_stmt->bind_param("i", $slot_id);
            $booking_stmt->execute();
            $booking_stmt->bind_result($slot_date, $slot_venue, $slot_time);
            $booking_stmt->fetch();
            $booking_exists = true;
            $booking_stmt->close();
        } else {
            echo "Error booking slot: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "The selected slot is fully booked.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Booking Form</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/slot_booking_form.css">
</head>
<body>
    <div class="popup-container">
        <div class="header">
            <h1>Slot Booking Form</h1>
            <a href="ira_register.php" class="back-icon"><span class="material-icons">arrow_circle_right</span></a>
        </div>
        <p class="note">*Book Slots For Your Registered IRA*</p>
        <?php if ($booking_exists): ?>
            <p>You have already booked a slot:</p>
            <p>Date: <?php echo htmlspecialchars($slot_date); ?></p>
            <p>Venue: <?php echo htmlspecialchars($slot_venue); ?></p>
            <p>Time: <?php echo htmlspecialchars($slot_time); ?></p>
        <?php else: ?>
            <form method="POST" action="slot_booking_form.php">
                <select name="slot_id" required>
                    <option value="" disabled selected>Select Slot</option>
                    <?php foreach ($slots as $slot): ?>
                        <option value="<?php echo $slot['id']; ?>">
                            <?php echo htmlspecialchars($slot['slot_date'] . ' - ' . $slot['slot_venue'] . ' - ' . $slot['slot_time']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="buttons">
                    <button type="button" class="cancel-button" onclick="window.location.href='dashboard.php'">Cancel</button>
                    <button type="submit" class="book-button">Book</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>