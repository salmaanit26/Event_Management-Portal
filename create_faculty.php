<?php
require_once 'connection_sqlite.php';

// Create a test faculty user
$faculty_email = 'faculty@college.edu';
$faculty_password = password_hash('faculty123', PASSWORD_DEFAULT);
$faculty_name = 'Dr. John Smith';
$department = 'Computer Science';

try {
    // Check if faculty user already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$faculty_email]);
    
    if ($check->fetch()) {
        echo "Faculty user already exists with email: $faculty_email\n";
    } else {
        // Insert faculty user
        $stmt = $conn->prepare("
            INSERT INTO users (full_name, email, password, department, role) 
            VALUES (?, ?, ?, ?, 'reviewer')
        ");
        $stmt->execute([$faculty_name, $faculty_email, $faculty_password, $department]);
        
        echo "Faculty user created successfully!\n";
        echo "Email: $faculty_email\n";
        echo "Password: faculty123\n";
        echo "Name: $faculty_name\n";
        echo "Department: $department\n";
        echo "Role: reviewer\n";
    }
    
    // Create another faculty user
    $faculty_email2 = 'dr.jane@college.edu';
    $faculty_password2 = password_hash('jane123', PASSWORD_DEFAULT);
    $faculty_name2 = 'Dr. Jane Doe';
    $department2 = 'Electronics Engineering';
    
    $check2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check2->execute([$faculty_email2]);
    
    if ($check2->fetch()) {
        echo "\nSecond faculty user already exists with email: $faculty_email2\n";
    } else {
        $stmt2 = $conn->prepare("
            INSERT INTO users (full_name, email, password, department, role) 
            VALUES (?, ?, ?, ?, 'reviewer')
        ");
        $stmt2->execute([$faculty_name2, $faculty_email2, $faculty_password2, $department2]);
        
        echo "\nSecond faculty user created successfully!\n";
        echo "Email: $faculty_email2\n";
        echo "Password: jane123\n";
        echo "Name: $faculty_name2\n";
        echo "Department: $department2\n";
        echo "Role: reviewer\n";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
