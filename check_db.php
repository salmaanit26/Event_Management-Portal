<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting database check...\n";

try {
    $conn = new PDO('sqlite:event_management.db');
    echo "Database connection successful.\n";
    
    echo "Checking ira_registered_students table structure:\n";
    $result = $conn->query('PRAGMA table_info(ira_registered_students)');
    
    if(!$result) {
        echo "Failed to get table info.\n";
        exit(1);
    }
    
    $columns = $result->fetchAll();
    echo "Found " . count($columns) . " columns:\n";
    
    foreach($columns as $col) {
        echo "- " . $col['name'] . ' (' . $col['type'] . ")\n";
    }
    
    // Check if evaluation columns exist
    $has_evaluation_status = false;
    foreach($columns as $col) {
        if($col['name'] == 'evaluation_status') {
            $has_evaluation_status = true;
            break;
        }
    }
    
    if(!$has_evaluation_status) {
        echo "\nAdding evaluation columns...\n";
        $conn->exec("ALTER TABLE ira_registered_students ADD evaluation_status TEXT");
        echo "Added evaluation_status column.\n";
        $conn->exec("ALTER TABLE ira_registered_students ADD evaluation_remarks TEXT");
        echo "Added evaluation_remarks column.\n";
        $conn->exec("ALTER TABLE ira_registered_students ADD evaluated_by INTEGER");
        echo "Added evaluated_by column.\n";
        $conn->exec("ALTER TABLE ira_registered_students ADD evaluated_at TEXT");
        echo "Added evaluated_at column.\n";
        echo "All evaluation columns added successfully!\n";
    } else {
        echo "\nEvaluation columns already exist.\n";
    }
    
    echo "\nDatabase check completed successfully.\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
