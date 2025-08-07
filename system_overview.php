<?php
require_once 'connection_sqlite.php';

echo "<h2>System Users Overview</h2>";

// Show all users by role
$roles = ['admin', 'reviewer', 'student'];

foreach ($roles as $role) {
    echo "<h3>👤 {$role} Users:</h3>";
    
    try {
        $stmt = $conn->prepare("SELECT id, full_name, email, department FROM users WHERE role = ? ORDER BY full_name");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            echo "<p>❌ No {$role} users found.</p>";
            
            // Create default admin if none exists
            if ($role == 'admin') {
                echo "<p>🔧 Creating default admin user...</p>";
                $admin_stmt = $conn->prepare("
                    INSERT INTO users (full_name, email, password, department, role) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $admin_stmt->execute(['Admin User', 'admin@college.edu', $admin_password, 'Administration', 'admin']);
                echo "<p>✅ <strong>Default admin created:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Email:</strong> admin@college.edu</li>";
                echo "<li><strong>Password:</strong> admin123</li>";
                echo "</ul>";
            }
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th style='padding: 8px;'>ID</th>";
            echo "<th style='padding: 8px;'>Name</th>";
            echo "<th style='padding: 8px;'>Email</th>";
            echo "<th style='padding: 8px;'>Department</th>";
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td style='padding: 8px;'>{$user['id']}</td>";
                echo "<td style='padding: 8px;'>{$user['full_name']}</td>";
                echo "<td style='padding: 8px;'>{$user['email']}</td>";
                echo "<td style='padding: 8px;'>{$user['department']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch(Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Show IRA registrations if any
echo "<hr>";
echo "<h3>📋 Current IRA Registrations:</h3>";
try {
    $ira_stmt = $conn->query("
        SELECT r.*, e.event_name, s.slot_date, s.slot_time, u.full_name as reviewer_name
        FROM ira_registered_students r 
        LEFT JOIN event_details e ON r.event_id = e.id 
        LEFT JOIN slots s ON r.slot_id = s.id 
        LEFT JOIN users u ON r.assigned_reviewer = u.id 
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $registrations = $ira_stmt->fetchAll();
    
    if (empty($registrations)) {
        echo "<p>📝 No IRA registrations found. Students need to register for IRA slots first.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>Student</th>";
        echo "<th style='padding: 8px;'>Event</th>";
        echo "<th style='padding: 8px;'>Slot</th>";
        echo "<th style='padding: 8px;'>Assigned Reviewer</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "</tr>";
        
        foreach ($registrations as $reg) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$reg['student_name']}</td>";
            echo "<td style='padding: 8px;'>{$reg['event_name']}</td>";
            echo "<td style='padding: 8px;'>" . date('M d, Y', strtotime($reg['slot_date'])) . " {$reg['slot_time']}</td>";
            echo "<td style='padding: 8px;'>" . ($reg['reviewer_name'] ?: 'Not assigned') . "</td>";
            echo "<td style='padding: 8px;'>{$reg['registration_status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>🧪 Testing Instructions:</h3>

<h4>1. Test Faculty Login & Evaluation:</h4>
<ol>
    <li><strong>Login as Faculty:</strong>
        <ul>
            <li>Go to <a href="login.php" target="_blank">Login Page</a></li>
            <li>Use: <code>faculty@college.edu</code> / <code>faculty123</code></li>
            <li>Or: <code>dr.jane@college.edu</code> / <code>jane123</code></li>
        </ul>
    </li>
    <li><strong>Access Faculty Dashboard:</strong>
        <ul>
            <li>After login, you'll see <a href="faculty_dashboard.php" target="_blank">Faculty Dashboard</a></li>
            <li>View assigned IRA registrations (if any)</li>
            <li>Evaluate students as Eligible/Not Eligible</li>
        </ul>
    </li>
</ol>

<h4>2. Test Admin Functions:</h4>
<ol>
    <li><strong>Login as Admin:</strong>
        <ul>
            <li>Use: <code>admin@college.edu</code> / <code>admin123</code></li>
        </ul>
    </li>
    <li><strong>Assign Reviewers:</strong>
        <ul>
            <li>Go to <a href="edit_reviewer.php" target="_blank">Reviewer Management</a></li>
            <li>Assign faculty to IRA registrations</li>
        </ul>
    </li>
</ol>

<h4>3. Create Test Data (if needed):</h4>
<ul>
    <li><strong>Events:</strong> <a href="add_event.php" target="_blank">Add Event</a> (login as student)</li>
    <li><strong>IRA Slots:</strong> <a href="add_slot.php" target="_blank">Add Slot</a> (login as admin)</li>
    <li><strong>Student Registration:</strong> <a href="ira_register.php" target="_blank">IRA Registration</a> (login as student)</li>
</ul>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
    ul, ol { margin: 10px 0; }
    h3 { color: #333; margin-top: 20px; }
</style>
