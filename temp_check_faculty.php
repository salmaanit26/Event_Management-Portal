<?php
// Temporary script to check faculty in database
try {
    $pdo = new PDO('sqlite:event_management.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE role = 'faculty'");
    $stmt->execute();
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Faculty Members in Database:\n";
    echo "============================\n";
    
    if (empty($faculty)) {
        echo "No faculty members found in database\n";
        
        // Let's also check what roles exist
        $stmt = $pdo->prepare("SELECT DISTINCT role FROM users");
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Available roles in database: " . implode(', ', $roles) . "\n";
        
    } else {
        foreach ($faculty as $f) {
            echo "ID: " . $f['id'] . "\n";
            echo "Name: " . $f['full_name'] . "\n";
            echo "Email: " . $f['email'] . "\n";
            echo "Password: " . $f['password'] . "\n";
            echo "Role: " . $f['role'] . "\n";
            echo "-------------------\n";
        }
    }
    
    // Show total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal users in database: " . $total['total'] . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
