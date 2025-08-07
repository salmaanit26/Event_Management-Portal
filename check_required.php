<?php
require_once 'connection_sqlite.php';
$columns = $conn->query('PRAGMA table_info(event_details)')->fetchAll();
echo "Required columns in event_details:\n";
foreach($columns as $col) {
    if($col['notnull'] == 1) {
        echo "- " . $col['name'] . " (required)\n";
    }
}
?>
