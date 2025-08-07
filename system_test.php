<?php
// Database Test Script - Comprehensive Event Management System
require_once 'connection_sqlite.php';

echo "<h1>🎯 Event Management Portal - System Test</h1>";
echo "<style>body{font-family: Arial, sans-serif; margin: 20px;} .success{color: green;} .error{color: red;} .info{color: blue;}</style>";

try {
    // Test 1: Database Connection
    echo "<h2>1. Database Connection Test</h2>";
    if ($conn) {
        echo "<p class='success'>✅ Database connected successfully!</p>";
    } else {
        echo "<p class='error'>❌ Database connection failed!</p>";
    }
    
    // Test 2: Table Structure
    echo "<h2>2. Database Schema Test</h2>";
    
    $tables = [
        'users', 'event_details', 'reviewers', 'ira_registered_students', 
        'slots', 'bookings', 'ira_reviews', 'notifications', 'event_categories'
    ];
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch()['count'];
            echo "<p class='success'>✅ Table '$table' exists with $count records</p>";
        } else {
            echo "<p class='error'>❌ Table '$table' not found</p>";
        }
    }
    
    // Test 3: Sample Data
    echo "<h2>3. Sample Data Test</h2>";
    
    // Check users
    $users = $conn->query("SELECT * FROM users LIMIT 5")->fetchAll();
    echo "<p class='info'>👥 Sample Users:</p>";
    foreach ($users as $user) {
        echo "<p>• {$user['full_name']} ({$user['role']}) - {$user['email']}</p>";
    }
    
    // Check event categories
    $categories = $conn->query("SELECT * FROM event_categories")->fetchAll();
    echo "<p class='info'>📋 Event Categories:</p>";
    foreach ($categories as $category) {
        echo "<p>• {$category['category_name']} " . ($category['requires_ira'] ? '(IRA Required)' : '') . "</p>";
    }
    
    // Check reviewers
    $reviewers = $conn->query("SELECT * FROM reviewers")->fetchAll();
    echo "<p class='info'>👨‍🏫 Sample Reviewers:</p>";
    foreach ($reviewers as $reviewer) {
        echo "<p>• {$reviewer['name']} - {$reviewer['department']} ({$reviewer['specialization']})</p>";
    }
    
    // Test 4: Event Details Structure
    echo "<h2>4. Event Details Table Structure</h2>";
    $columns = $conn->query("PRAGMA table_info(event_details)")->fetchAll();
    echo "<p class='info'>📊 Event Details Columns:</p>";
    foreach ($columns as $column) {
        echo "<p>• {$column['name']} ({$column['type']})" . ($column['notnull'] ? ' - Required' : '') . "</p>";
    }
    
    // Test 5: Test Event Insertion
    echo "<h2>5. Test Event Submission</h2>";
    
    // Get a test user
    $test_user = $conn->query("SELECT * FROM users WHERE role = 'student' LIMIT 1")->fetch();
    
    if ($test_user) {
        try {
            $test_event_data = [
                'event_name' => 'Test Event - System Check',
                'event_date' => date('Y-m-d', strtotime('+30 days')),
                'reg_deadline' => date('Y-m-d', strtotime('+15 days')),
                'event_organizer' => 'System Test Organizer',
                'domain' => 'Technical',
                'event_type' => 'Workshop',
                'event_category' => 'Technical Competition',
                'competition_name' => 'Test Competition',
                'country' => 'India',
                'state' => 'Test State',
                'city' => 'Test City',
                'venue_details' => 'Test Venue Details',
                'brochure' => '',
                'applied_by' => $test_user['id'],
                'applicant_name' => $test_user['full_name'],
                'applicant_id' => 'TEST001',
                'department' => $test_user['department'] ?? 'Computer Science',
                'year_role' => '3rd Year',
                'email' => $test_user['email'],
                'phone' => '9999999999',
                'special_lab_name' => 'Test Lab',
                'special_lab_id' => 'LAB001',
                'special_lab_incharge' => 'Test Lab Incharge',
                'ira' => 'YES',
                'priority' => 'High',
                'estimated_participants' => 100,
                'budget_required' => 50000.00,
                'remarks' => 'This is a system test event submission.'
            ];
            
            $stmt = $conn->prepare("INSERT INTO event_details (
                event_name, event_date, reg_deadline, event_organizer, domain, event_type, 
                event_category, competition_name, country, state, city, venue_details, 
                brochure, applied_by, applicant_name, applicant_id, department, year_role, 
                email, phone, special_lab_name, special_lab_id, special_lab_incharge, 
                ira, priority, estimated_participants, budget_required, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute(array_values($test_event_data));
            $test_event_id = $conn->lastInsertId();
            
            echo "<p class='success'>✅ Test event inserted successfully! Event ID: {$test_event_id}</p>";
            
            // Test notification creation
            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $notification_stmt->execute([
                $test_user['id'],
                "System Test Notification",
                "Test event submission successful. Event ID: {$test_event_id}",
                "success"
            ]);
            
            echo "<p class='success'>✅ Test notification created successfully!</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Test event insertion failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ No test user found for event insertion test</p>";
    }
    
    // Test 6: Email Service Test
    echo "<h2>6. Email Service Test</h2>";
    try {
        require_once 'email_service.php';
        $email_service = new EmailService();
        echo "<p class='success'>✅ Email service loaded successfully!</p>";
        echo "<p class='info'>📧 Email logging enabled for development</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Email service test failed: " . $e->getMessage() . "</p>";
    }
    
    // Test 7: Statistics
    echo "<h2>7. System Statistics</h2>";
    
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_events,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_events,
            SUM(CASE WHEN ira = 'YES' THEN 1 ELSE 0 END) as ira_events,
            COUNT(DISTINCT applied_by) as unique_applicants,
            SUM(budget_required) as total_budget,
            AVG(estimated_participants) as avg_participants
        FROM event_details
    ")->fetch();
    
    echo "<p class='info'>📊 Current System Statistics:</p>";
    echo "<p>• Total Events: {$stats['total_events']}</p>";
    echo "<p>• Pending Events: {$stats['pending_events']}</p>";
    echo "<p>• Approved Events: {$stats['approved_events']}</p>";
    echo "<p>• IRA Events: {$stats['ira_events']}</p>";
    echo "<p>• Unique Applicants: {$stats['unique_applicants']}</p>";
    echo "<p>• Total Budget: ₹" . number_format($stats['total_budget'], 2) . "</p>";
    echo "<p>• Average Participants: " . round($stats['avg_participants']) . "</p>";
    
    // Test 8: File Structure
    echo "<h2>8. File Structure Test</h2>";
    
    $required_files = [
        'login.php', 'dashboard.php', 'add_event.php', 'status.php', 'ira_page.php',
        'edit_reviewer.php', 'database_admin.php', 'email_service.php', 'connection_sqlite.php'
    ];
    
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<p class='success'>✅ File '$file' exists</p>";
        } else {
            echo "<p class='error'>❌ File '$file' missing</p>";
        }
    }
    
    // Upload directory test
    if (is_dir('uploads')) {
        echo "<p class='success'>✅ Uploads directory exists</p>";
    } else {
        echo "<p class='error'>❌ Uploads directory missing</p>";
    }
    
    echo "<h2>🎉 System Test Complete!</h2>";
    echo "<p class='info'>Access your portal at: <a href='login.php'>Login Page</a></p>";
    echo "<p class='info'>Default Login Credentials:</p>";
    echo "<p>• Admin: admin@college.edu / admin123</p>";
    echo "<p>• Student: student@college.edu / student123</p>";
    echo "<p>• Faculty: faculty@college.edu / faculty123</p>";
    echo "<p>• Reviewer: reviewer@college.edu / reviewer123</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ System test failed: " . $e->getMessage() . "</p>";
}
?>
