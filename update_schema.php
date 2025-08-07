<?php
require_once 'connection_sqlite.php';

echo "Updating database schema for the new IRA system...\n\n";

// Update slots table
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS slots (
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
    echo "✅ Slots table updated\n";
} catch (Exception $e) {
    echo "❌ Error updating slots table: " . $e->getMessage() . "\n";
}

// Update ira_registered_students table
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ira_registered_students (
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
    echo "✅ IRA registered students table updated\n";
} catch (Exception $e) {
    echo "❌ Error updating IRA registered students table: " . $e->getMessage() . "\n";
}

// Add admin_remarks column to event_details if not exists
try {
    $conn->exec("ALTER TABLE event_details ADD COLUMN admin_remarks TEXT DEFAULT NULL");
    echo "✅ Added admin_remarks column to event_details\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "ℹ️  admin_remarks column already exists\n";
    } else {
        echo "❌ Error adding admin_remarks column: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "DATABASE SCHEMA UPDATE COMPLETE\n";
echo str_repeat("=", 50) . "\n";

// Show current table structures
echo "\nCurrent table structures:\n";

$tables = ['event_details', 'users', 'slots', 'ira_registered_students'];
foreach ($tables as $table) {
    echo "\n$table table:\n";
    try {
        $columns = $conn->query("PRAGMA table_info($table)")->fetchAll();
        foreach($columns as $col) {
            echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "❌ Error reading $table: " . $e->getMessage() . "\n";
    }
}
?>
