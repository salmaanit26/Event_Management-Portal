<?php
require_once 'connection_sqlite.php';

echo "Event_details table columns:\n";
$result = $conn->query('PRAGMA table_info(event_details)');
$columns = $result->fetchAll();

foreach($columns as $col) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}
?>
