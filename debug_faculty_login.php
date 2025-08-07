<?php
require_once 'connection_sqlite.php';

echo "<h2>Checking Faculty User: faculty@college.edu</h2>";

try {
    // Check if the user exists
    $check_user = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check_user->execute(['faculty@college.edu']);
    $user = $check_user->fetch();
    
    if ($user) {
        echo "<p>✅ <strong>User Found!</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><td><strong>ID:</strong></td><td>{$user['id']}</td></tr>";
        echo "<tr><td><strong>Name:</strong></td><td>{$user['full_name']}</td></tr>";
        echo "<tr><td><strong>Email:</strong></td><td>{$user['email']}</td></tr>";
        echo "<tr><td><strong>Role:</strong></td><td>{$user['role']}</td></tr>";
        echo "<tr><td><strong>Department:</strong></td><td>{$user['department']}</td></tr>";
        echo "</table>";
        
        // Test password verification
        echo "<h3>Testing Password:</h3>";
        if (password_verify('faculty123', $user['password'])) {
            echo "<p>✅ <strong>Password is correct!</strong></p>";
        } else {
            echo "<p>❌ <strong>Password verification failed. Updating password...</strong></p>";
            
            // Update password
            $new_password = password_hash('faculty123', PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->execute([$new_password, 'faculty@college.edu']);
            
            echo "<p>✅ <strong>Password updated successfully!</strong></p>";
        }
        
    } else {
        echo "<p>❌ <strong>User not found. Creating faculty user...</strong></p>";
        
        // Create the faculty user
        $faculty_password = password_hash('faculty123', PASSWORD_DEFAULT);
        $create_stmt = $conn->prepare("
            INSERT INTO users (full_name, email, password, department, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $create_stmt->execute(['Dr. John Smith', 'faculty@college.edu', $faculty_password, 'Computer Science', 'reviewer']);
        
        echo "<p>✅ <strong>Faculty user created successfully!</strong></p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> faculty@college.edu</li>";
        echo "<li><strong>Password:</strong> faculty123</li>";
        echo "<li><strong>Name:</strong> Dr. John Smith</li>";
        echo "<li><strong>Department:</strong> Computer Science</li>";
        echo "<li><strong>Role:</strong> reviewer</li>";
        echo "</ul>";
    }
    
    // Check login.php validation logic
    echo "<hr>";
    echo "<h3>Checking Login Logic:</h3>";
    
    // Simulate login validation
    $email = 'faculty@college.edu';
    $password = 'faculty123';
    
    $login_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $login_stmt->execute([$email]);
    $login_user = $login_stmt->fetch();
    
    if ($login_user && password_verify($password, $login_user['password'])) {
        echo "<p>✅ <strong>Login validation would succeed</strong></p>";
        echo "<p>User would be redirected based on role: <strong>{$login_user['role']}</strong></p>";
        
        if ($login_user['role'] == 'reviewer') {
            echo "<p>✅ <strong>Redirect to:</strong> faculty_dashboard.php</p>";
        }
    } else {
        echo "<p>❌ <strong>Login validation would fail</strong></p>";
        if (!$login_user) {
            echo "<p>Reason: User not found</p>";
        } else {
            echo "<p>Reason: Password verification failed</p>";
        }
    }
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔧 Quick Actions:</h3>";
echo "<p><a href='login.php' target='_blank'>🔑 Try Login Again</a></p>";
echo "<p><a href='faculty_dashboard.php' target='_blank'>👨‍🏫 Direct Faculty Dashboard Access</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 20px; }
</style>
