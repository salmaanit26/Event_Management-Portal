<?php
// Simple SQLite database test - no MySQL needed!
echo "<h2>Database Test - No MySQL Required</h2>";

try {
    // Create SQLite database (lightweight, no server needed)
    $pdo = new PDO('sqlite:event_management.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create a simple test table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        name TEXT,
        email TEXT
    )");
    
    // Insert test data
    $pdo->exec("INSERT OR IGNORE INTO users (id, name, email) VALUES 
        (1, 'Admin', 'admin@college.edu'),
        (2, 'Student', 'student@college.edu')");
    
    // Show data
    $stmt = $pdo->query("SELECT * FROM users");
    echo "<h3>✅ Database Working!</h3>";
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    while ($row = $stmt->fetch()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
    
    echo "<p><strong>✅ Your databases are accessible without MySQL!</strong></p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
