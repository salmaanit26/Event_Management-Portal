<?php
// Production configuration for live hosting
// Update these settings when deploying to live server

// Database Configuration for SQLite (portable)
$database_path = __DIR__ . '/event_management.db';

// For MySQL hosting (uncomment and configure):
/*
$servername = "your-host-server";
$username = "your-username"; 
$password = "your-password";
$dbname = "your-database-name";
*/

// Current SQLite setup (works on most PHP hosts)
try {
    $conn = new PDO("sqlite:" . $database_path);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
