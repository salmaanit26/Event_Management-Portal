<?php
require_once 'connection_sqlite.php';

echo "<h2>🔧 Fixing Missing assigned_reviewer Column</h2>";

try {
    // Step 1: Check current table structure
    echo "<h3>Step 1: Current ira_registered_students Table Structure</h3>";
    $result = $conn->query('PRAGMA table_info(ira_registered_students)');
    $columns = $result->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column Name</th><th>Type</th><th>Not Null</th><th>Default</th></tr>";
    
    $has_assigned_reviewer = false;
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['name']}</td>";
        echo "<td>{$col['type']}</td>";
        echo "<td>" . ($col['notnull'] ? 'YES' : 'NO') . "</td>";
        echo "<td>{$col['dflt_value']}</td>";
        echo "</tr>";
        
        if($col['name'] == 'assigned_reviewer') {
            $has_assigned_reviewer = true;
        }
    }
    echo "</table>";
    
    // Step 2: Add missing columns if needed
    echo "<h3>Step 2: Adding Missing Columns</h3>";
    
    if (!$has_assigned_reviewer) {
        echo "<p>❌ <strong>assigned_reviewer column is missing. Adding it...</strong></p>";
        
        $conn->exec("ALTER TABLE ira_registered_students ADD COLUMN assigned_reviewer INTEGER");
        echo "<p>✅ Added assigned_reviewer column</p>";
        
        // Also add other evaluation columns if missing
        $evaluation_columns = [
            'evaluation_status' => 'TEXT',
            'evaluation_remarks' => 'TEXT', 
            'evaluated_by' => 'INTEGER',
            'evaluated_at' => 'TEXT'
        ];
        
        foreach ($evaluation_columns as $col_name => $col_type) {
            try {
                $conn->exec("ALTER TABLE ira_registered_students ADD COLUMN {$col_name} {$col_type}");
                echo "<p>✅ Added {$col_name} column</p>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                    echo "<p>✓ {$col_name} column already exists</p>";
                } else {
                    echo "<p>❌ Error adding {$col_name}: " . $e->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p>✅ <strong>assigned_reviewer column already exists</strong></p>";
    }
    
    // Step 3: Show updated table structure
    echo "<h3>Step 3: Updated Table Structure</h3>";
    $result2 = $conn->query('PRAGMA table_info(ira_registered_students)');
    $columns2 = $result2->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column Name</th><th>Type</th></tr>";
    
    foreach($columns2 as $col) {
        $highlight = in_array($col['name'], ['assigned_reviewer', 'evaluation_status', 'evaluation_remarks', 'evaluated_by', 'evaluated_at']) ? 'background: #d4edda;' : '';
        echo "<tr style='{$highlight}'>";
        echo "<td>{$col['name']}</td>";
        echo "<td>{$col['type']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Step 4: Check if there are any IRA registrations to assign faculty to
    echo "<h3>Step 4: Current IRA Registrations</h3>";
    $registrations = $conn->query("SELECT id, student_name, student_email, assigned_reviewer FROM ira_registered_students")->fetchAll();
    
    if (empty($registrations)) {
        echo "<p>❌ <strong>No IRA registrations found. Creating test data...</strong></p>";
        
        // Create test registrations
        $test_reg = $conn->prepare("
            INSERT INTO ira_registered_students 
            (student_name, student_email, student_department, student_phone, student_year, registration_status)
            VALUES (?, ?, ?, ?, ?, 'Approved')
        ");
        
        $test_students = [
            ['John Doe', 'john@college.edu', 'Computer Science', '9876543210', 'Final Year'],
            ['Jane Smith', 'jane@college.edu', 'Electronics', '9876543211', 'Third Year'],
            ['Mike Johnson', 'mike@college.edu', 'Mechanical', '9876543212', 'Final Year']
        ];
        
        foreach ($test_students as $student) {
            $test_reg->execute($student);
            echo "<p>✅ Created test registration for: {$student[0]}</p>";
        }
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Student Name</th><th>Email</th><th>Assigned Reviewer</th></tr>";
        
        foreach ($registrations as $reg) {
            echo "<tr>";
            echo "<td>{$reg['id']}</td>";
            echo "<td>{$reg['student_name']}</td>";
            echo "<td>{$reg['student_email']}</td>";
            echo "<td>" . ($reg['assigned_reviewer'] ?: 'Not assigned') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 5: Assign faculty to registrations for testing
    echo "<h3>Step 5: Assigning Faculty for Testing</h3>";
    
    $faculty_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'faculty'");
    $faculty_check->execute(['faculty@college.edu']);
    $faculty = $faculty_check->fetch();
    
    if ($faculty) {
        $update_assignments = $conn->prepare("
            UPDATE ira_registered_students 
            SET assigned_reviewer = ? 
            WHERE assigned_reviewer IS NULL OR assigned_reviewer = 0
        ");
        $update_assignments->execute([$faculty['id']]);
        
        $updated_count = $conn->prepare("SELECT COUNT(*) as count FROM ira_registered_students WHERE assigned_reviewer = ?");
        $updated_count->execute([$faculty['id']]);
        $count = $updated_count->fetch();
        
        echo "<p>✅ <strong>Assigned {$count['count']} students to faculty@college.edu</strong></p>";
    } else {
        echo "<p>❌ <strong>Faculty user not found. Please ensure faculty@college.edu exists with role 'faculty'</strong></p>";
    }
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Database Fix Complete!</h3>";
echo "<p><strong>The faculty_dashboard.php should now work properly.</strong></p>";

echo "<p><a href='faculty_dashboard.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; display: inline-block;'>👨‍🏫 Test Faculty Dashboard</a></p>";
echo "<p><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; display: inline-block;'>🔑 Login as Faculty</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
</style>
