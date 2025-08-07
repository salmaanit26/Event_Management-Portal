<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
</head>
<body>
    <h1>Simple Login Test</h1>
    
    <?php
    session_start();
    
    echo "<p>Session started successfully!</p>";
    echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
    
    // Test database connection
    $servername = "127.0.0.1";
    $username = "root";
    $password = "";
    $dbname = "event_management";
    
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            echo "<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p style='color: green;'>Database connected successfully!</p>";
            
            // Test if users table exists
            $result = $conn->query("SHOW TABLES LIKE 'users'");
            if ($result->num_rows > 0) {
                echo "<p style='color: green;'>Users table exists!</p>";
                
                // Count users
                $count_result = $conn->query("SELECT COUNT(*) as count FROM users");
                $count = $count_result->fetch_assoc();
                echo "<p>Number of users in database: " . $count['count'] . "</p>";
                
            } else {
                echo "<p style='color: orange;'>Users table does not exist.</p>";
            }
        }
        $conn->close();
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        echo "<h3>Form submitted!</h3>";
        echo "<p>Email: " . htmlspecialchars($_POST['email']) . "</p>";
        echo "<p>Role: " . htmlspecialchars($_POST['role']) . "</p>";
    }
    ?>
    
    <form method="POST">
        <h3>Test Login Form</h3>
        <p>
            <label>Email:</label><br>
            <input type="email" name="email" value="admin@college.edu" required>
        </p>
        <p>
            <label>Password:</label><br>
            <input type="password" name="password" value="admin123" required>
        </p>
        <p>
            <label>Role:</label><br>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="faculty">Faculty</option>
                <option value="student">Student</option>
            </select>
        </p>
        <p>
            <button type="submit">Test Login</button>
        </p>
    </form>
    
    <p><a href="test.php">Go to PHP Test Page</a></p>
    <p><a href="setup_database.php">Go to Database Setup</a></p>
    <p><a href="login.php">Go to Main Login Page</a></p>
</body>
</html>
