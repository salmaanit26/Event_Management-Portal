<?php
echo "<h1>PHP Test</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test database connection
$servername = "127.0.0.1";
$username = "root";
$password = "";

try {
    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        echo "<p style='color: red;'>Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>Database connection successful!</p>";
        
        // Check if our database exists
        $result = $conn->query("SHOW DATABASES LIKE 'event_management'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>Database 'event_management' exists!</p>";
        } else {
            echo "<p style='color: orange;'>Database 'event_management' does not exist yet.</p>";
        }
    }
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

phpinfo();
?>
