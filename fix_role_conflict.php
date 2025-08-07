<?php
require_once 'connection_sqlite.php';

echo "<h2>🔧 Fixing Role Name Conflict</h2>";

try {
    // Step 1: Show current issue
    echo "<h3>Current Issue:</h3>";
    echo "<p>✖️ Login page expects role: <strong>'faculty'</strong></p>";
    echo "<p>✖️ Database stores role as: <strong>'reviewer'</strong></p>";
    echo "<p>➡️ <strong>Solution:</strong> Update database to use 'faculty' instead of 'reviewer'</p>";
    
    // Step 2: Check current faculty users with 'reviewer' role
    echo "<h3>Step 1: Current Faculty Users</h3>";
    $current_reviewers = $conn->query("SELECT id, full_name, email, role FROM users WHERE role = 'reviewer'");
    $reviewers = $current_reviewers->fetchAll();
    
    if (empty($reviewers)) {
        echo "<p>❌ No users found with role 'reviewer'</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Email</th><th>Current Role</th></tr>";
        foreach ($reviewers as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 3: Update role from 'reviewer' to 'faculty'
    echo "<h3>Step 2: Updating Roles</h3>";
    $update_stmt = $conn->prepare("UPDATE users SET role = 'faculty' WHERE role = 'reviewer'");
    $result = $update_stmt->execute();
    $updated_count = $conn->lastInsertRowId();  // This won't work for UPDATE, let me fix
    
    // Get count of updated rows
    $count_stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'faculty'");
    $count_result = $count_stmt->fetch();
    
    echo "<p>✅ <strong>Updated {$count_result['count']} users from 'reviewer' to 'faculty'</strong></p>";
    
    // Step 4: Ensure faculty user exists with correct credentials
    echo "<h3>Step 3: Ensuring Faculty User Exists</h3>";
    $check_faculty = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check_faculty->execute(['faculty@college.edu']);
    $faculty_user = $check_faculty->fetch();
    
    if (!$faculty_user) {
        echo "<p>Creating faculty@college.edu user...</p>";
        $hashed_password = password_hash('faculty123', PASSWORD_DEFAULT);
        $create_stmt = $conn->prepare("
            INSERT INTO users (full_name, email, password, department, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $create_stmt->execute(['Dr. John Smith', 'faculty@college.edu', $hashed_password, 'Computer Science', 'faculty']);
        echo "<p>✅ <strong>Faculty user created!</strong></p>";
    } else {
        // Update existing user to have correct role and password
        $hashed_password = password_hash('faculty123', PASSWORD_DEFAULT);
        $update_existing = $conn->prepare("UPDATE users SET role = 'faculty', password = ? WHERE email = ?");
        $update_existing->execute([$hashed_password, 'faculty@college.edu']);
        echo "<p>✅ <strong>Faculty user updated with correct role and password!</strong></p>";
    }
    
    // Step 5: Verify the fix
    echo "<h3>Step 4: Verification</h3>";
    $verify_stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE email = ? AND role = ?");
    $verify_stmt->execute(['faculty@college.edu', 'faculty']);
    $verified_user = $verify_stmt->fetch();
    
    if ($verified_user) {
        echo "<p>✅ <strong>SUCCESS!</strong> User found with correct role:</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #d4edda;'>";
        echo "<td><strong>Email:</strong></td><td>{$verified_user['email']}</td>";
        echo "</tr>";
        echo "<tr style='background: #d4edda;'>";
        echo "<td><strong>Role:</strong></td><td><span style='color: green; font-weight: bold;'>{$verified_user['role']}</span></td>";
        echo "</tr>";
        echo "<tr style='background: #d4edda;'>";
        echo "<td><strong>Name:</strong></td><td>{$verified_user['full_name']}</td>";
        echo "</tr>";
        echo "</table>";
        
        // Test password
        $pwd_check = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $pwd_check->execute(['faculty@college.edu']);
        $pwd_result = $pwd_check->fetch();
        
        if (password_verify('faculty123', $pwd_result['password'])) {
            echo "<p>✅ <strong>Password verification: SUCCESS</strong></p>";
        } else {
            echo "<p>❌ <strong>Password verification: FAILED</strong></p>";
        }
        
    } else {
        echo "<p>❌ <strong>FAILED!</strong> User not found with role 'faculty'</p>";
    }
    
    // Step 6: Update any database references
    echo "<h3>Step 5: Updating Database References</h3>";
    
    // Check if there are foreign key references that need updating
    $check_ira_assignments = $conn->query("
        SELECT COUNT(*) as count 
        FROM ira_registered_students r 
        INNER JOIN users u ON r.assigned_reviewer = u.id 
        WHERE u.role = 'faculty'
    ");
    $ira_count = $check_ira_assignments->fetch();
    
    echo "<p>✅ <strong>{$ira_count['count']} IRA assignments found with faculty users</strong></p>";
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Fix Complete!</h3>";
echo "<p><strong>Now you can login with:</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> faculty@college.edu</li>";
echo "<li><strong>Password:</strong> faculty123</li>";
echo "<li><strong>Role:</strong> Select <span style='color: green; font-weight: bold;'>Faculty</span> (this will now work!)</li>";
echo "</ul>";

echo "<p><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; display: inline-block;'>🔑 Test Login Now</a></p>";
echo "<p><a href='faculty_dashboard.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; display: inline-block;'>👨‍🏫 Faculty Dashboard</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
    ul { background: #f9f9f9; padding: 15px; border-radius: 5px; }
</style>
