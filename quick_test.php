<?php
// Simple working test page
echo "<!DOCTYPE html>";
echo "<html><head><title>Quick Test</title></head><body>";
echo "<h1 style='color: green;'>✅ SUCCESS!</h1>";
echo "<p>✅ Apache is working</p>";
echo "<p>✅ PHP is working (Version: " . phpversion() . ")</p>";

// Test MySQL
try {
    $conn = new mysqli("127.0.0.1", "root", "");
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ MySQL: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ MySQL is working</p>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ MySQL Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🚀 Ready to proceed!</h2>";
echo "<p><a href='setup_database.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Setup Database</a></p>";
echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
echo "</body></html>";
?>
