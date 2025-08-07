<?php
require_once 'connection_sqlite.php';

echo "<h2>Faculty User Management</h2>";

// Create test faculty users
$faculty_users = [
    [
        'email' => 'faculty@college.edu',
        'password' => 'faculty123',
        'name' => 'Dr. John Smith',
        'department' => 'Computer Science'
    ],
    [
        'email' => 'dr.jane@college.edu', 
        'password' => 'jane123',
        'name' => 'Dr. Jane Doe',
        'department' => 'Electronics Engineering'
    ],
    [
        'email' => 'prof.wilson@college.edu',
        'password' => 'wilson123', 
        'name' => 'Prof. Robert Wilson',
        'department' => 'Mechanical Engineering'
    ]
];

echo "<h3>Creating Faculty Users:</h3>";

foreach ($faculty_users as $faculty) {
    try {
        // Check if faculty user already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$faculty['email']]);
        
        if ($check->fetch()) {
            echo "<p>✅ Faculty user already exists: <strong>{$faculty['email']}</strong></p>";
        } else {
            // Insert faculty user
            $hashed_password = password_hash($faculty['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, password, department, role) 
                VALUES (?, ?, ?, ?, 'reviewer')
            ");
            $stmt->execute([$faculty['name'], $faculty['email'], $hashed_password, $faculty['department']]);
            
            echo "<p>✨ <strong>Faculty user created successfully!</strong></p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> {$faculty['email']}</li>";
            echo "<li><strong>Password:</strong> {$faculty['password']}</li>";
            echo "<li><strong>Name:</strong> {$faculty['name']}</li>";
            echo "<li><strong>Department:</strong> {$faculty['department']}</li>";
            echo "<li><strong>Role:</strong> reviewer</li>";
            echo "</ul><br>";
        }
    } catch(Exception $e) {
        echo "<p>❌ Error creating {$faculty['email']}: " . $e->getMessage() . "</p>";
    }
}

// Show all faculty users
echo "<h3>All Faculty Users in System:</h3>";
try {
    $faculty_list = $conn->query("SELECT * FROM users WHERE role = 'reviewer' ORDER BY full_name");
    $faculty_members = $faculty_list->fetchAll();
    
    if (empty($faculty_members)) {
        echo "<p>❌ No faculty users found in the system.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Name</th>";
        echo "<th style='padding: 10px;'>Email</th>";
        echo "<th style='padding: 10px;'>Department</th>";
        echo "<th style='padding: 10px;'>Role</th>";
        echo "</tr>";
        
        foreach ($faculty_members as $faculty) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>{$faculty['id']}</td>";
            echo "<td style='padding: 10px;'>{$faculty['full_name']}</td>";
            echo "<td style='padding: 10px;'>{$faculty['email']}</td>";
            echo "<td style='padding: 10px;'>{$faculty['department']}</td>";
            echo "<td style='padding: 10px;'>{$faculty['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "<p>❌ Error fetching faculty: " . $e->getMessage() . "</p>";
}

// Show testing instructions
echo "<hr>";
echo "<h3>🧪 How to Test Faculty System:</h3>";
echo "<ol>";
echo "<li><strong>Login as Faculty:</strong>";
echo "<ul>";
echo "<li>Go to <a href='login.php' target='_blank'>login.php</a></li>";
echo "<li>Use any faculty email above with corresponding password</li>";
echo "<li>You'll be redirected to faculty_dashboard.php</li>";
echo "</ul></li>";

echo "<li><strong>Admin Tasks:</strong>";
echo "<ul>";
echo "<li>Login as admin and go to <a href='edit_reviewer.php' target='_blank'>edit_reviewer.php</a></li>";
echo "<li>Assign faculty to IRA registrations</li>";
echo "<li>Faculty will then see assigned students in their dashboard</li>";
echo "</ul></li>";

echo "<li><strong>Student Registration:</strong>";
echo "<ul>";
echo "<li>Students must register for IRA slots first</li>";
echo "<li>Admin assigns faculty to those registrations</li>";
echo "<li>Then faculty can evaluate the students</li>";
echo "</ul></li>";
echo "</ol>";

echo "<h3>🔗 Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='login.php' target='_blank'>🔑 Login Page</a></li>";
echo "<li><a href='faculty_dashboard.php' target='_blank'>👨‍🏫 Faculty Dashboard</a> (requires faculty login)</li>";
echo "<li><a href='edit_reviewer.php' target='_blank'>👥 Admin - Assign Reviewers</a> (requires admin login)</li>";
echo "<li><a href='ira_register.php' target='_blank'>🎯 IRA Registration</a> (requires student login)</li>";
echo "</ul>";
?>
