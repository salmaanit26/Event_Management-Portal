<?php
/**
 * Database Setup Script for Event Management Portal
 * Run this file to automatically set up your database
 */

// Database configuration
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "event_management";

echo "<html><head><title>Database Setup - Event Management Portal</title><style>
body { font-family: 'Segoe UI', Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f8f9fa; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
.success { color: #155724; background: #d4edda; padding: 15px; border-left: 5px solid #28a745; margin: 15px 0; border-radius: 5px; }
.error { color: #721c24; background: #f8d7da; padding: 15px; border-left: 5px solid #dc3545; margin: 15px 0; border-radius: 5px; }
.info { color: #0c5460; background: #d1ecf1; padding: 15px; border-left: 5px solid #17a2b8; margin: 15px 0; border-radius: 5px; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-left: 5px solid #ffc107; margin: 15px 0; border-radius: 5px; }
h1 { color: #333; text-align: center; margin-bottom: 30px; }
h2 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
h3 { color: #6c757d; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #dee2e6; }
.btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: 500; }
.btn:hover { background: #0056b3; color: white; text-decoration: none; }
.btn-success { background: #28a745; }
.btn-success:hover { background: #1e7e34; }
.btn-danger { background: #dc3545; }
.btn-danger:hover { background: #c82333; }
.progress { background: #e9ecef; border-radius: 5px; height: 20px; margin: 20px 0; }
.progress-bar { background: #007bff; height: 100%; border-radius: 5px; transition: width 0.3s; }
.step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; }
.step.active { border-color: #007bff; background: #f8f9fa; }
.step.completed { border-color: #28a745; background: #d4edda; }
</style></head><body><div class='container'>";

echo "<h1>🚀 Event Management Portal - Database Setup</h1>";

// Progress tracking
$total_steps = 6;
$current_step = 0;

function updateProgress($step, $total) {
    $percentage = ($step / $total) * 100;
    echo "<div class='progress'><div class='progress-bar' style='width: {$percentage}%'></div></div>";
    echo "<p style='text-align: center; color: #6c757d;'>Step $step of $total</p>";
}

echo "<div class='info'><strong>🎯 Welcome!</strong> This script will automatically set up your Event Management Portal database.</div>";

updateProgress(++$current_step, $total_steps);

try {
    // Connect to MySQL server (without selecting database)
    $conn = new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<div class='success'>✓ Connected to MySQL server successfully!</div>";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>✓ Database '$dbname' created successfully!</div>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($dbname);
    echo "<div class='success'>✓ Database '$dbname' selected!</div>";
    
    // Read and execute SQL commands from database_setup.sql
    $sql_file = 'database_setup.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL commands by semicolon and execute each one
        $sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($sql_commands as $command) {
            if (!empty($command) && !preg_match('/^(--|\#)/', $command)) {
                try {
                    if ($conn->query($command) === TRUE) {
                        $success_count++;
                    } else {
                        $error_count++;
                        echo "<div class='error'>Error executing command: " . $conn->error . "</div>";
                        echo "<pre>" . htmlspecialchars($command) . "</pre>";
                    }
                } catch (Exception $e) {
                    $error_count++;
                    echo "<div class='error'>Exception: " . $e->getMessage() . "</div>";
                    echo "<pre>" . htmlspecialchars($command) . "</pre>";
                }
            }
        }
        
        echo "<div class='info'>📊 Executed $success_count SQL commands successfully!</div>";
        if ($error_count > 0) {
            echo "<div class='error'>⚠️ $error_count commands had errors.</div>";
        }
        
    } else {
        echo "<div class='error'>❌ database_setup.sql file not found!</div>";
        echo "<div class='info'>Please make sure the database_setup.sql file is in the same directory as this script.</div>";
    }
    
    // Verify tables were created
    $result = $conn->query("SHOW TABLES");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✓ Database tables created successfully!</div>";
        echo "<div class='info'><strong>Created tables:</strong><br>";
        while($row = $result->fetch_array()) {
            echo "• " . $row[0] . "<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='error'>❌ No tables found in database!</div>";
    }
    
    // Test sample data
    $test_query = "SELECT COUNT(*) as user_count FROM users";
    $result = $conn->query($test_query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<div class='success'>✓ Sample data verified: " . $row['user_count'] . " users created!</div>";
    }
    
    echo "<div class='success'>";
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p><strong>Your Event Management Portal is ready to use!</strong></p>";
    echo "<p><a href='login.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Default Login Credentials:</h3>";
    echo "<strong>Administrator:</strong><br>";
    echo "Email: admin@college.edu<br>";
    echo "Password: admin123<br><br>";
    
    echo "<strong>Faculty:</strong><br>";
    echo "Email: faculty@college.edu<br>";
    echo "Password: faculty123<br><br>";
    
    echo "<strong>Student:</strong><br>";
    echo "Email: student@college.edu<br>";
    echo "Password: student123<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Setup failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure XAMPP is running</li>";
    echo "<li>Check that MySQL service is started in XAMPP Control Panel</li>";
    echo "<li>Verify database credentials in connection.php</li>";
    echo "<li>Ensure database_setup.sql file exists in the same directory</li>";
    echo "</ol>";
    echo "</div>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo "</body></html>";
?>
