<?php
require_once 'connection_sqlite.php';

echo "<h2>🧪 Creating Test Data for Faculty Dashboard</h2>";

try {
    // Step 1: Create test students if they don't exist
    echo "<h3>Step 1: Creating Test Students</h3>";
    
    $test_students = [
        ['name' => 'John Doe', 'email' => 'john.student@college.edu', 'dept' => 'Computer Science'],
        ['name' => 'Jane Smith', 'email' => 'jane.student@college.edu', 'dept' => 'Electronics'],
        ['name' => 'Mike Johnson', 'email' => 'mike.student@college.edu', 'dept' => 'Mechanical']
    ];
    
    foreach ($test_students as $student) {
        $check_student = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_student->execute([$student['email']]);
        
        if (!$check_student->fetch()) {
            $create_student = $conn->prepare("
                INSERT INTO users (full_name, email, password, department, role) 
                VALUES (?, ?, ?, ?, 'student')
            ");
            $password = password_hash('student123', PASSWORD_DEFAULT);
            $create_student->execute([$student['name'], $student['email'], $password, $student['dept']]);
            echo "<p>✅ Created student: {$student['name']}</p>";
        } else {
            echo "<p>✓ Student exists: {$student['name']}</p>";
        }
    }
    
    // Step 2: Create test event if doesn't exist
    echo "<h3>Step 2: Creating Test IRA Event</h3>";
    
    $check_event = $conn->query("SELECT id FROM event_details WHERE ira = 'YES' LIMIT 1");
    $event = $check_event->fetch();
    
    if (!$event) {
        $create_event = $conn->prepare("
            INSERT INTO event_details (
                event_name, event_date, event_time, venue, event_organizer, 
                domain, event_type, status, ira, applied_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Approved', 'YES', 1)
        ");
        $create_event->execute([
            'AI Workshop - IRA Event',
            date('Y-m-d', strtotime('+7 days')),
            '10:00 AM',
            'Main Auditorium',
            'Tech Club',
            'Technology',
            'Workshop'
        ]);
        $event_id = $conn->lastInsertId();
        echo "<p>✅ Created test IRA event with ID: {$event_id}</p>";
    } else {
        $event_id = $event['id'];
        echo "<p>✓ Using existing IRA event with ID: {$event_id}</p>";
    }
    
    // Step 3: Create test slot if doesn't exist
    echo "<h3>Step 3: Creating Test IRA Slot</h3>";
    
    $check_slot = $conn->prepare("SELECT id FROM slots WHERE event_id = ? LIMIT 1");
    $check_slot->execute([$event_id]);
    $slot = $check_slot->fetch();
    
    if (!$slot) {
        // Get faculty ID
        $faculty_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $faculty_check->execute(['faculty@college.edu']);
        $faculty = $faculty_check->fetch();
        
        if ($faculty) {
            $create_slot = $conn->prepare("
                INSERT INTO slots (
                    event_id, slot_date, slot_time, hall_name, max_capacity, assigned_faculty
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $create_slot->execute([
                $event_id,
                date('Y-m-d', strtotime('+3 days')),
                '2:00 PM - 4:00 PM',
                'Room 101',
                20,
                $faculty['id']
            ]);
            $slot_id = $conn->lastInsertId();
            echo "<p>✅ Created test slot with ID: {$slot_id}, assigned to faculty</p>";
        }
    } else {
        $slot_id = $slot['id'];
        echo "<p>✓ Using existing slot with ID: {$slot_id}</p>";
    }
    
    // Step 4: Create test IRA registrations
    echo "<h3>Step 4: Creating Test IRA Registrations</h3>";
    
    // Get student IDs
    $students_data = $conn->query("SELECT id, full_name, email, department FROM users WHERE role = 'student' LIMIT 3")->fetchAll();
    $faculty_data = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $faculty_data->execute(['faculty@college.edu']);
    $faculty = $faculty_data->fetch();
    
    foreach ($students_data as $student) {
        // Check if already registered
        $check_reg = $conn->prepare("
            SELECT id FROM ira_registered_students 
            WHERE student_id = ? AND event_id = ?
        ");
        $check_reg->execute([$student['id'], $event_id]);
        
        if (!$check_reg->fetch()) {
            $create_reg = $conn->prepare("
                INSERT INTO ira_registered_students (
                    student_id, event_id, slot_id, student_name, student_email, 
                    student_department, student_phone, student_year, 
                    registration_status, assigned_reviewer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Approved', ?)
            ");
            $create_reg->execute([
                $student['id'],
                $event_id,
                $slot_id,
                $student['full_name'],
                $student['email'],
                $student['department'],
                '9876543210',
                'Final Year',
                $faculty['id']
            ]);
            echo "<p>✅ Created IRA registration for: {$student['full_name']}</p>";
        } else {
            echo "<p>✓ IRA registration exists for: {$student['full_name']}</p>";
        }
    }
    
    // Step 5: Show summary
    echo "<h3>Step 5: Summary</h3>";
    
    $summary_query = $conn->prepare("
        SELECT r.student_name, r.student_email, e.event_name, s.slot_date, s.slot_time
        FROM ira_registered_students r
        LEFT JOIN event_details e ON r.event_id = e.id
        LEFT JOIN slots s ON r.slot_id = s.id
        WHERE r.assigned_reviewer = ?
    ");
    $summary_query->execute([$faculty['id']]);
    $assignments = $summary_query->fetchAll();
    
    echo "<p><strong>Faculty (faculty@college.edu) has {count($assignments)} student assignments:</strong></p>";
    
    if (!empty($assignments)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>Student Name</th>";
        echo "<th style='padding: 10px;'>Email</th>";
        echo "<th style='padding: 10px;'>Event</th>";
        echo "<th style='padding: 10px;'>Slot</th>";
        echo "</tr>";
        
        foreach ($assignments as $assignment) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>{$assignment['student_name']}</td>";
            echo "<td style='padding: 10px;'>{$assignment['student_email']}</td>";
            echo "<td style='padding: 10px;'>{$assignment['event_name']}</td>";
            echo "<td style='padding: 10px;'>" . date('M d, Y', strtotime($assignment['slot_date'])) . " {$assignment['slot_time']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch(Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🎉 Test Data Created Successfully!</h3>";
echo "<p><strong>Now login as faculty and check the dashboard:</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> faculty@college.edu</li>";
echo "<li><strong>Password:</strong> faculty123</li>";
echo "<li><strong>Role:</strong> Faculty</li>";
echo "</ul>";

echo "<p><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; display: inline-block;'>🔑 Login as Faculty</a></p>";
echo "<p><a href='faculty_dashboard.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; display: inline-block;'>👨‍🏫 Faculty Dashboard Direct</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { border-collapse: collapse; margin: 10px 0; width: 100%; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f2f2f2; }
    h3 { color: #333; margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 5px; }
    ul { background: #f9f9f9; padding: 15px; border-radius: 5px; }
</style>
