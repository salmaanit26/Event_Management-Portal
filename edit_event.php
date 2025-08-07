<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include('connection_sqlite.php');

// Fetch event details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure the ID is an integer
    $sql = "SELECT * FROM event_details WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
}

// Update event details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']); // Ensure the ID is an integer
    $status = $_POST['status'];
    $ira = $_POST['ira'];
    $remarks = $_POST['remarks'];

    $sql = "UPDATE event_details SET status = ?, ira = ?, remarks = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $status, $ira, $remarks, $id);
    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
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
    <title>Edit Event</title>
    <link rel="stylesheet" href="css/edit_event.css">
</head>
<body>
    <div class="container">
        <h1>Edit Event</h1>
        <form method="POST" action="edit_event.php">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id']); ?>">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="In Progress" <?php if ($event['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                <option value="Approved" <?php if ($event['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                <option value="Rejected" <?php if ($event['status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
            </select>
            <label for="ira">IRA</label>
            <select name="ira" id="ira">
                <option value="NO" <?php if ($event['ira'] == 'NO') echo 'selected'; ?>>NO</option>
                <option value="YES" <?php if ($event['ira'] == 'YES') echo 'selected'; ?>>YES</option>
            </select>
            <label for="remarks">Remarks</label>
            <textarea name="remarks" id="remarks"><?php echo htmlspecialchars($event['remarks']); ?></textarea>
            <input type="submit" value="Update">
        </form>
    </div>
</body>
</html>