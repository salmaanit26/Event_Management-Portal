<?php
require_once 'connection_sqlite.php';

echo "<h2>➕ Adding New Student</h2>";

try {
    // New student details
    $new_student = [
        'name' => 'Sarah Wilson',
        'email' => 'sarah.student@college.edu',
        'password' => 'student123',
        'department' => 'Information Technology',
        'role' => 'student'
    ];
    
    // Check if student already exists
    $check_student = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_student->execute([$new_student['email']]);
    
    if ($check_student->fetch()) {
        echo "<p>⚠️ <strong>Student already exists:</strong> {$new_student['email']}</p>";
    } else {
        // Add new student
        $create_student = $conn->prepare("
            INSERT INTO users (full_name, email, password, department, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $create_student->execute([
            $new_student['name'],
            $new_student['email'],
            $new_student['password'],
            $new_student['department'],
            $new_student['role']
        ]);
        
        echo "<p>✅ <strong>New student added successfully!</strong></p>";
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h4>👨‍🎓 New Student Details:</h4>";
        echo "<p><strong>Name:</strong> {$new_student['name']}</p>";
        echo "<p><strong>Email:</strong> {$new_student['email']}</p>";
        echo "<p><strong>Password:</strong> {$new_student['password']}</p>";
        echo "<p><strong>Department:</strong> {$new_student['department']}</p>";
        echo "<p><strong>Role:</strong> {$new_student['role']}</p>";
        echo "</div>";
    }
    
    // Show all current students
    echo "<h3>📋 All Students in System:</h3>";
    $all_students = $conn->query("
        SELECT id, full_name, email, password, department 
        FROM users 
        WHERE role = 'student' 
        ORDER BY full_name
    ")->fetchAll();
    
    if (empty($all_students)) {
        echo "<p>❌ No students found in system.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Name</th>";
        echo "<th style='padding: 10px;'>Email</th>";
        echo "<th style='padding: 10px;'>Password</th>";
        echo "<th style='padding: 10px;'>Department</th>";
        echo "</tr>";
        
        foreach ($all_students as $student) {
            $highlight = ($student['email'] == $new_student['email']) ? 'background: #d4edda;' : '';
            echo "<tr style='{$highlight}'>";
            echo "<td style='padding: 10px;'>{$student['id']}</td>";
            echo "<td style='padding: 10px;'>{$student['full_name']}</td>";
            echo "<td style='padding: 10px;'>{$student['email']}</td>";
            echo "<td style='padding: 10px; font-weight: bold; color: #007bff;'>{$student['password']}</td>";
            echo "<td style='padding: 10px;'>{$student['department']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Total Students:</strong> " . count($all_students) . "</p>";
    }
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Student Addition Complete!</h3>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h4>🔑 Student Login Credentials:</h4>";
echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<p><strong>Email:</strong> sarah.student@college.edu</p>";
echo "<p><strong>Password:</strong> student123</p>";
echo "<p><strong>Role:</strong> Select 'Student' from dropdown</p>";
echo "</div>";
echo "</div>";

echo "<p><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block; margin: 10px 5px;'>🔑 Test New Student Login</a></p>";
echo "<p><a href='dashboard.php' target='_blank' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block; margin: 10px 5px;'>📊 Student Dashboard</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
</style>
