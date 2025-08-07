<?php
require_once 'connection_sqlite.php';

echo "<h2>🔍 Faculty Login Debug - Complete Analysis</h2>";

$test_email = 'faculty@college.edu';
$test_password = 'faculty123';

try {
    // Step 1: Check if user exists
    echo "<h3>Step 1: Check User Existence</h3>";
    $check_user = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check_user->execute([$test_email]);
    $user = $check_user->fetch();
    
    if (!$user) {
        echo "<p>❌ <strong>User does not exist. Creating now...</strong></p>";
        
        // Create faculty user with proper hashed password
        $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
        $create_stmt = $conn->prepare("
            INSERT INTO users (full_name, email, password, department, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $create_stmt->execute(['Dr. John Smith', $test_email, $hashed_password, 'Computer Science', 'reviewer']);
        
        // Fetch the newly created user
        $check_user->execute([$test_email]);
        $user = $check_user->fetch();
        
        echo "<p>✅ <strong>Faculty user created!</strong></p>";
    }
    
    // Display user details
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><td><strong>ID:</strong></td><td>{$user['id']}</td></tr>";
    echo "<tr><td><strong>Full Name:</strong></td><td>{$user['full_name']}</td></tr>";
    echo "<tr><td><strong>Email:</strong></td><td>{$user['email']}</td></tr>";
    echo "<tr><td><strong>Role:</strong></td><td><span style='color: blue; font-weight: bold;'>{$user['role']}</span></td></tr>";
    echo "<tr><td><strong>Department:</strong></td><td>{$user['department']}</td></tr>";
    echo "<tr><td><strong>Password Hash:</strong></td><td>" . substr($user['password'], 0, 20) . "...</td></tr>";
    echo "</table>";
    
    // Step 2: Test password verification
    echo "<h3>Step 2: Password Verification Test</h3>";
    if (password_verify($test_password, $user['password'])) {
        echo "<p>✅ <strong>Password verification: SUCCESS</strong></p>";
    } else {
        echo "<p>❌ <strong>Password verification: FAILED</strong></p>";
        echo "<p>🔧 <strong>Fixing password...</strong></p>";
        
        // Update with correct hashed password
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->execute([$new_hash, $test_email]);
        
        echo "<p>✅ <strong>Password updated and fixed!</strong></p>";
        
        // Re-fetch user with updated password
        $check_user->execute([$test_email]);
        $user = $check_user->fetch();
    }
    
    // Step 3: Test login query (same as login.php)
    echo "<h3>Step 3: Login Query Simulation</h3>";
    echo "<p><strong>Testing with role 'reviewer':</strong></p>";
    
    $login_stmt = $conn->prepare("SELECT id, email, password, role, full_name FROM users WHERE email = ? AND role = ?");
    $login_stmt->execute([$test_email, 'reviewer']);
    $login_user = $login_stmt->fetch();
    
    if ($login_user) {
        echo "<p>✅ <strong>User found with role 'reviewer'</strong></p>";
        
        if (password_verify($test_password, $login_user['password'])) {
            echo "<p>✅ <strong>Complete login simulation: SUCCESS</strong></p>";
            echo "<p style='color: green; font-weight: bold;'>🎉 Login should work now!</p>";
        } else {
            echo "<p>❌ <strong>Password verification failed in login simulation</strong></p>";
        }
    } else {
        echo "<p>❌ <strong>User not found with role 'reviewer'</strong></p>";
        
        // Check what roles exist for this email
        $role_check = $conn->prepare("SELECT role FROM users WHERE email = ?");
        $role_check->execute([$test_email]);
        $all_roles = $role_check->fetchAll();
        
        echo "<p><strong>Available roles for this email:</strong></p>";
        foreach ($all_roles as $role_row) {
            echo "<p>- " . $role_row['role'] . "</p>";
        }
    }
    
    // Step 4: Check all available roles in dropdown
    echo "<h3>Step 4: Available Roles in System</h3>";
    $roles_stmt = $conn->query("SELECT DISTINCT role FROM users ORDER BY role");
    $all_system_roles = $roles_stmt->fetchAll();
    
    echo "<p><strong>All roles in system:</strong></p>";
    foreach ($all_system_roles as $role_row) {
        echo "<p>• <strong>{$role_row['role']}</strong></p>";
    }
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Step 5: Create a direct login test
echo "<hr>";
echo "<h3>🧪 Direct Login Test</h3>";
echo "<form method='post' action='test_faculty_login.php' style='border: 2px solid #ddd; padding: 15px; margin: 10px 0;'>";
echo "<p><strong>Test Faculty Login:</strong></p>";
echo "<input type='hidden' name='email' value='faculty@college.edu'>";
echo "<input type='hidden' name='password' value='faculty123'>";
echo "<input type='hidden' name='role' value='reviewer'>";
echo "<p>Email: <strong>faculty@college.edu</strong></p>";
echo "<p>Password: <strong>faculty123</strong></p>";
echo "<p>Role: <strong>reviewer</strong></p>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>🚀 Test Login</button>";
echo "</form>";

echo "<p><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔑 Go to Login Page</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
</style>
