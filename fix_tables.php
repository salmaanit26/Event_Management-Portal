<?php
require_once 'connection_sqlite.php';

echo "Recreating slots and ira_registered_students tables with correct structure...\n\n";

// Drop and recreate slots table
try {
    $conn->exec("DROP TABLE IF EXISTS slots");
    $conn->exec("
        CREATE TABLE slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER,
            slot_date DATE,
            slot_time VARCHAR(20),
            hall_name VARCHAR(100),
            assigned_faculty INTEGER,
            max_capacity INTEGER DEFAULT 10,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES event_details(id),
            FOREIGN KEY (assigned_faculty) REFERENCES users(id)
        )
    ");
    echo "✅ Slots table recreated with correct structure\n";
} catch (Exception $e) {
    echo "❌ Error recreating slots table: " . $e->getMessage() . "\n";
}

// Drop and recreate ira_registered_students table
try {
    $conn->exec("DROP TABLE IF EXISTS ira_registered_students");
    $conn->exec("
        CREATE TABLE ira_registered_students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER,
            slot_id INTEGER,
            student_id INTEGER,
            student_name VARCHAR(100),
            student_email VARCHAR(100),
            student_department VARCHAR(100),
            student_year VARCHAR(20),
            registration_status VARCHAR(50) DEFAULT 'Pending Review',
            faculty_remarks TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES event_details(id),
            FOREIGN KEY (slot_id) REFERENCES slots(id),
            FOREIGN KEY (student_id) REFERENCES users(id)
        )
    ");
    echo "✅ IRA registered students table recreated with correct structure\n";
} catch (Exception $e) {
    echo "❌ Error recreating IRA registered students table: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "TABLES RECREATED SUCCESSFULLY\n";
echo str_repeat("=", 50) . "\n";

// Verify new structures
echo "\nNew table structures:\n\n";

echo "slots table:\n";
$columns = $conn->query("PRAGMA table_info(slots)")->fetchAll();
foreach($columns as $col) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\nira_registered_students table:\n";
$columns = $conn->query("PRAGMA table_info(ira_registered_students)")->fetchAll();
foreach($columns as $col) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}
?>
