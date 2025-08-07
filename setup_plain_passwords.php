<?php
require_once 'connection_sqlite.php';

echo "<h2>🔧 Converting to Plain Text Passwords</h2>";
echo "<p><strong>Note:</strong> Converting all passwords to plain text for easier testing</p>";

try {
    // Define users with plain text passwords
    $users_to_update = [
        ['email' => 'admin@college.edu', 'password' => 'admin123', 'name' => 'Admin User', 'role' => 'admin', 'dept' => 'Administration'],
        ['email' => 'faculty@college.edu', 'password' => 'faculty123', 'name' => 'Dr. John Smith', 'role' => 'faculty', 'dept' => 'Computer Science'],
        ['email' => 'dr.jane@college.edu', 'password' => 'jane123', 'name' => 'Dr. Jane Doe', 'role' => 'faculty', 'dept' => 'Electronics Engineering'],
        ['email' => 'student@college.edu', 'password' => 'student123', 'name' => 'Test Student', 'role' => 'student', 'dept' => 'Computer Science'],
        ['email' => 'john.student@college.edu', 'password' => 'student123', 'name' => 'John Doe', 'role' => 'student', 'dept' => 'Computer Science'],
        ['email' => 'jane.student@college.edu', 'password' => 'student123', 'name' => 'Jane Smith', 'role' => 'student', 'dept' => 'Electronics'],
        ['email' => 'mike.student@college.edu', 'password' => 'student123', 'name' => 'Mike Johnson', 'role' => 'student', 'dept' => 'Mechanical']
    ];
    
    echo "<h3>Creating/Updating Users with Plain Text Passwords:</h3>";
    
    foreach ($users_to_update as $user_data) {
        // Check if user exists
        $check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_user->execute([$user_data['email']]);
        $existing_user = $check_user->fetch();
        
        if ($existing_user) {
            // Update existing user with plain text password
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET password = ?, full_name = ?, role = ?, department = ?
                WHERE email = ?
            ");
            $update_stmt->execute([
                $user_data['password'], 
                $user_data['name'], 
                $user_data['role'], 
                $user_data['dept'], 
                $user_data['email']
            ]);
            echo "<p>✅ <strong>Updated:</strong> {$user_data['email']} | Password: {$user_data['password']} | Role: {$user_data['role']}</p>";
        } else {
            // Create new user with plain text password
            $create_stmt = $conn->prepare("
                INSERT INTO users (full_name, email, password, department, role) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $create_stmt->execute([
                $user_data['name'],
                $user_data['email'],
                $user_data['password'],
                $user_data['dept'],
                $user_data['role']
            ]);
            echo "<p>✨ <strong>Created:</strong> {$user_data['email']} | Password: {$user_data['password']} | Role: {$user_data['role']}</p>";
        }
    }
    
    // Show all users with their plain text passwords
    echo "<h3>All System Users (Plain Text Passwords):</h3>";
    $all_users = $conn->query("SELECT id, full_name, email, password, role, department FROM users ORDER BY role, full_name")->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Role</th>";
    echo "<th style='padding: 10px;'>Name</th>";
    echo "<th style='padding: 10px;'>Email</th>";
    echo "<th style='padding: 10px;'>Password</th>";
    echo "<th style='padding: 10px;'>Department</th>";
    echo "</tr>";
    
    foreach ($all_users as $user) {
        $role_color = [
            'admin' => '#dc3545',
            'faculty' => '#007bff', 
            'student' => '#28a745'
        ][$user['role']] ?? '#6c757d';
        
        echo "<tr>";
        echo "<td style='padding: 10px; color: {$role_color}; font-weight: bold;'>{$user['role']}</td>";
        echo "<td style='padding: 10px;'>{$user['full_name']}</td>";
        echo "<td style='padding: 10px;'>{$user['email']}</td>";
        echo "<td style='padding: 10px; background: #fff3cd; font-weight: bold;'>{$user['password']}</td>";
        echo "<td style='padding: 10px;'>{$user['department']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Plain Text Password Setup Complete!</h3>";

echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h4>📋 Login Credentials (All Plain Text):</h4>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;'>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h5 style='color: #dc3545; margin: 0 0 10px 0;'>👑 ADMIN</h5>";
echo "<p><strong>Email:</strong> admin@college.edu</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "</div>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;'>";
echo "<h5 style='color: #007bff; margin: 0 0 10px 0;'>👨‍🏫 FACULTY</h5>";
echo "<p><strong>Email:</strong> faculty@college.edu</p>";
echo "<p><strong>Password:</strong> faculty123</p>";
echo "<p><strong>Email:</strong> dr.jane@college.edu</p>";
echo "<p><strong>Password:</strong> jane123</p>";
echo "</div>";

echo "<div style='background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h5 style='color: #28a745; margin: 0 0 10px 0;'>👨‍🎓 STUDENTS</h5>";
echo "<p><strong>Email:</strong> student@college.edu</p>";
echo "<p><strong>Password:</strong> student123</p>";
echo "<p><em>All student accounts use: student123</em></p>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "<p><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block; margin: 10px 5px;'>🔑 Test Login Now</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
</style>
