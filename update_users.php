<?php
require_once 'connection_sqlite.php';

echo "Updating user data...\n";

$conn->exec("UPDATE users SET department = 'Administration', phone = '+1-555-0101' WHERE id = 1");
$conn->exec("UPDATE users SET department = 'Computer Science', student_id = 'CS2023001', phone = '+1-555-0102', year_of_study = '3rd Year', branch = 'CSE' WHERE id = 2");
$conn->exec("UPDATE users SET department = 'Computer Science', phone = '+1-555-0103', bio = 'Senior faculty member and event reviewer' WHERE id = 3");

echo "User data updated successfully\n";

echo "\nVerifying updated user data:\n";
$users = $conn->query('SELECT id, email, full_name, department, student_id, phone FROM users LIMIT 3')->fetchAll();
foreach($users as $user) {
    echo "ID: {$user['id']} | {$user['full_name']} | Dept: {$user['department']} | Student ID: {$user['student_id']}\n";
}
?>
