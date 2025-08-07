<!DOCTYPE html>
<html>
<head>
    <title>XAMPP Status & Quick Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 XAMPP Status & Database Setup</h1>
    
    <?php
    echo "<div class='info'><strong>🔍 Checking XAMPP Status...</strong></div>";
    
    // Check Apache
    $apache_status = false;
    $mysql_status = false;
    
    // Test basic connection
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode == 200) {
        echo "<div class='success'>✅ Apache is running and responding!</div>";
        $apache_status = true;
    } else {
        echo "<div class='error'>❌ Apache is not responding properly (HTTP: $httpcode)</div>";
    }
    
    // Test MySQL
    try {
        $conn = new mysqli("127.0.0.1", "root", "");
        if ($conn->connect_error) {
            echo "<div class='error'>❌ MySQL Connection Failed: " . $conn->connect_error . "</div>";
        } else {
            echo "<div class='success'>✅ MySQL is running and accessible!</div>";
            $mysql_status = true;
            $conn->close();
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ MySQL Error: " . $e->getMessage() . "</div>";
    }
    
    // Show PHP info
    echo "<div class='info'>";
    echo "<strong>📋 System Information:</strong><br>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "MySQL Extension: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded') . "<br>";
    echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";
    echo "</div>";
    
    if ($apache_status && $mysql_status) {
        echo "<div class='success'>";
        echo "<h2>🎉 XAMPP is Working!</h2>";
        echo "<p>Your XAMPP installation is running correctly. The phpMyAdmin timeout issue won't affect our Event Management Portal.</p>";
        echo "</div>";
        
        echo "<h3>🛠️ Next Steps:</h3>";
        echo "<div class='info'>";
        echo "<ol>";
        echo "<li><strong>Setup Database:</strong> <a href='setup_database.php' class='btn btn-success'>Run Database Setup</a></li>";
        echo "<li><strong>Access Portal:</strong> <a href='login.php' class='btn'>Go to Login Page</a></li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<h3>🔑 Default Login Credentials:</h3>";
        echo "<div class='info'>";
        echo "<strong>Admin:</strong> admin@college.edu / admin123<br>";
        echo "<strong>Faculty:</strong> faculty@college.edu / faculty123<br>";
        echo "<strong>Student:</strong> student@college.edu / student123<br>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<h2>⚠️ XAMPP Issues Detected</h2>";
        echo "<p>Please fix the above issues before proceeding.</p>";
        echo "</div>";
        
        echo "<h3>🔧 Troubleshooting Steps:</h3>";
        echo "<div class='info'>";
        echo "<ol>";
        echo "<li>Open XAMPP Control Panel</li>";
        echo "<li>Stop all services</li>";
        echo "<li>Start Apache first, wait for green status</li>";
        echo "<li>Start MySQL, wait for green status</li>";
        echo "<li>Refresh this page</li>";
        echo "</ol>";
        echo "</div>";
    }
    ?>
    
    <h3>📝 About phpMyAdmin Issue:</h3>
    <div class="info">
        <p><strong>Don't worry!</strong> The phpMyAdmin timeout issue is common and won't affect our Event Management Portal. We have our own database setup script that works perfectly.</p>
        
        <p><strong>Why phpMyAdmin times out:</strong></p>
        <ul>
            <li>Large MySQL configuration files</li>
            <li>Slow loading on some systems</li>
            <li>PHP execution time limits</li>
        </ul>
        
        <p><strong>Our Solution:</strong> We've created a custom database setup script that bypasses phpMyAdmin entirely!</p>
    </div>
    
    <h3>🎯 Quick Actions:</h3>
    <div style="text-align: center; margin: 30px 0;">
        <a href="setup_database.php" class="btn btn-success">🗄️ Setup Database</a>
        <a href="login.php" class="btn">🚪 Go to Portal</a>
        <a href="test.php" class="btn">🧪 Test PHP & MySQL</a>
    </div>
    
    <div class="info">
        <p><small>💡 <strong>Pro Tip:</strong> You don't need phpMyAdmin for this project. Our custom interface handles everything!</small></p>
    </div>
</body>
</html>
