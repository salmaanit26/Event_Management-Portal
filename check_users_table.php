<?php
require_once 'connection_sqlite.php';

echo "Users table structure:\n";
$columns = $conn->query('PRAGMA table_info(users)')->fetchAll();
foreach($columns as $col) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\nSample user data:\n";
$users = $conn->query('SELECT * FROM users LIMIT 3')->fetchAll();
foreach($users as $user) {
    print_r($user);
}
?>
