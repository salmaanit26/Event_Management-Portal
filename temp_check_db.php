<?php
try {
    $pdo = new PDO('sqlite:event_management.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "IRA Registered Students table structure:\n";
    $stmt = $pdo->query('PRAGMA table_info(ira_registered_students)');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['name'] . " (" . $col['type'] . ")\n";
    }
    
    echo "\nSample data from ira_registered_students:\n";
    $stmt = $pdo->query('SELECT * FROM ira_registered_students LIMIT 1');
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        foreach ($sample as $key => $value) {
            echo $key . ": " . $value . "\n";
        }
    } else {
        echo "No sample data found\n";
    }
    
    echo "\nSlots table structure:\n";
    $stmt = $pdo->query('PRAGMA table_info(slots)');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['name'] . " (" . $col['type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
