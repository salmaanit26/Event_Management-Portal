<?php
session_start();
require_once 'connection_sqlite.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    echo "<h2>🧪 Faculty Login Test Results</h2>";
    echo "<p><strong>Testing with:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> {$email}</li>";
    echo "<li><strong>Password:</strong> {$password}</li>";
    echo "<li><strong>Role:</strong> {$role}</li>";
    echo "</ul>";
    
    try {
        // Use exact same query as login.php
        $sql = "SELECT id, email, password, role, full_name FROM users WHERE email = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p>✅ <strong>User found in database</strong></p>";
            echo "<p>Database role: <strong>{$user['role']}</strong></p>";
            
            if (password_verify($password, $user['password'])) {
                echo "<p>✅ <strong>Password verification: SUCCESS</strong></p>";
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 LOGIN SUCCESSFUL!</p>";
                echo "<p><strong>Session set for:</strong> {$user['full_name']} ({$user['role']})</p>";
                
                echo "<p><a href='faculty_dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👨‍🏫 Go to Faculty Dashboard</a></p>";
                
            } else {
                echo "<p>❌ <strong>Password verification: FAILED</strong></p>";
                echo "<p>This means the stored password hash doesn't match 'faculty123'</p>";
            }
        } else {
            echo "<p>❌ <strong>User not found with email '{$email}' and role '{$role}'</strong></p>";
            
            // Check if user exists with different role
            $check_stmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            $existing_roles = $check_stmt->fetchAll();
            
            if ($existing_roles) {
                echo "<p><strong>User exists with these roles:</strong></p>";
                foreach ($existing_roles as $role_row) {
                    echo "<p>• {$role_row['role']}</p>";
                }
            } else {
                echo "<p>User doesn't exist at all with this email.</p>";
            }
        }
        
    } catch(Exception $e) {
        echo "<p>❌ <strong>Database Error:</strong> " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>No POST data received</p>";
}

echo "<hr>";
echo "<p><a href='debug_faculty_complete.php'>🔍 Back to Debug Page</a></p>";
echo "<p><a href='login.php'>🔑 Back to Login Page</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h2 { color: #333; }
    ul { background: #f9f9f9; padding: 15px; border-radius: 5px; }
</style>
