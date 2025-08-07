<?php
require_once 'connection_sqlite.php';

echo "Migrating users table to add missing columns...\n\n";

// List of columns to add to users table
$user_columns = [
    'department' => 'TEXT DEFAULT NULL',
    'student_id' => 'TEXT DEFAULT NULL',
    'phone' => 'TEXT DEFAULT NULL',
    'year_of_study' => 'TEXT DEFAULT NULL',
    'branch' => 'TEXT DEFAULT NULL',
    'profile_picture' => 'TEXT DEFAULT NULL',
    'bio' => 'TEXT DEFAULT NULL',
    'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
];

$success_count = 0;
$error_count = 0;

foreach ($user_columns as $column => $definition) {
    try {
        $sql = "ALTER TABLE users ADD COLUMN $column $definition";
        $conn->exec($sql);
        echo "✅ Added column: $column\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "ℹ️  Column already exists: $column\n";
        } else {
            echo "❌ Error adding column $column: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}

// Update sample users with department information
echo "\nUpdating sample user data...\n";
try {
    $updates = [
        ['id' => 1, 'department' => 'Administration', 'phone' => '+1-555-0101'],
        ['id' => 2, 'department' => 'Computer Science', 'student_id' => 'CS2023001', 'phone' => '+1-555-0102', 'year_of_study' => '3rd Year', 'branch' => 'CSE'],
        ['id' => 3, 'department' => 'Computer Science', 'phone' => '+1-555-0103', 'bio' => 'Senior faculty member and event reviewer']
    ];
    
    foreach ($updates as $update) {
        $sql = "UPDATE users SET ";
        $params = [];
        $setParts = [];
        
        foreach ($update as $key => $value) {
            if ($key !== 'id') {
                $setParts[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        $sql .= implode(', ', $setParts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $params[] = $update['id'];
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        echo "✅ Updated user ID: " . $update['id'] . "\n";
    }
} catch (PDOException $e) {
    echo "❌ Error updating users: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "USERS TABLE MIGRATION COMPLETE\n";
echo "✅ Successful column additions: $success_count\n";
echo "❌ Errors: $error_count\n";
echo str_repeat("=", 50) . "\n";

// Verify the updated structure
echo "\nUpdated users table structure:\n";
$columns = $conn->query('PRAGMA table_info(users)')->fetchAll();
foreach($columns as $col) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\nSample updated user data:\n";
$users = $conn->query('SELECT id, email, full_name, department, student_id, phone FROM users LIMIT 3')->fetchAll();
foreach($users as $user) {
    echo "ID: {$user['id']} | {$user['full_name']} | Dept: {$user['department']} | Student ID: {$user['student_id']}\n";
}
?>
