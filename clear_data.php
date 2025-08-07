<?php
require_once 'connection_sqlite.php';

echo "Clearing test data...\n";
$conn->exec('DELETE FROM event_details WHERE id > 0');
$conn->exec('DELETE FROM ira_registered_students WHERE id > 0');
$conn->exec('DELETE FROM slots WHERE id > 0');
$conn->exec('DELETE FROM notifications WHERE id > 0');
echo "Test data cleared successfully\n";

// Reset auto-increment counters
$conn->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "event_details"');
$conn->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "ira_registered_students"');
$conn->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "slots"');
$conn->exec('UPDATE sqlite_sequence SET seq = 0 WHERE name = "notifications"');
echo "Auto-increment counters reset\n";
?>
