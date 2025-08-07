<?php
session_start();

// SQLite Database Setup and Admin Panel
$database_file = __DIR__ . '/event_management.db';

try {
    $conn = new PDO("sqlite:$database_file");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create all tables
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('admin', 'student', 'reviewer')),
            full_name TEXT NOT NULL,
            phone TEXT,
            department TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        'event_details' => "CREATE TABLE IF NOT EXISTS event_details (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_name TEXT NOT NULL,
            event_date DATE NOT NULL,
            event_time TIME NOT NULL,
            venue TEXT NOT NULL,
            description TEXT,
            organizer_id INTEGER,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected')),
            max_participants INTEGER DEFAULT 100,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organizer_id) REFERENCES users(id)
        )",
        
        'reviewers' => "CREATE TABLE IF NOT EXISTS reviewers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER UNIQUE,
            department TEXT NOT NULL,
            expertise TEXT,
            experience_years INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        'slots' => "CREATE TABLE IF NOT EXISTS slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER,
            slot_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            capacity INTEGER DEFAULT 50,
            booked_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES event_details(id)
        )",
        
        'bookings' => "CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slot_id INTEGER,
            user_id INTEGER,
            booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'confirmed' CHECK(status IN ('confirmed', 'cancelled', 'waiting')),
            notes TEXT,
            FOREIGN KEY (slot_id) REFERENCES slots(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE(slot_id, user_id)
        )",
        
        'ira_registered_students' => "CREATE TABLE IF NOT EXISTS ira_registered_students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_name TEXT NOT NULL,
            student_id TEXT UNIQUE NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            department TEXT NOT NULL,
            year_of_study INTEGER CHECK(year_of_study BETWEEN 1 AND 4),
            project_title TEXT NOT NULL,
            project_description TEXT,
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'submitted' CHECK(status IN ('submitted', 'under_review', 'reviewed'))
        )",
        
        'ira_reviews' => "CREATE TABLE IF NOT EXISTS ira_reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER,
            reviewer_id INTEGER,
            technical_score INTEGER CHECK(technical_score BETWEEN 0 AND 10),
            presentation_score INTEGER CHECK(presentation_score BETWEEN 0 AND 10),
            innovation_score INTEGER CHECK(innovation_score BETWEEN 0 AND 10),
            overall_score INTEGER CHECK(overall_score BETWEEN 0 AND 30),
            comments TEXT,
            review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES ira_registered_students(id),
            FOREIGN KEY (reviewer_id) REFERENCES reviewers(id),
            UNIQUE(student_id, reviewer_id)
        )"
    ];
    
    // Create all tables
    foreach ($tables as $table_name => $create_sql) {
        $conn->exec($create_sql);
    }
    
    // Insert sample data if tables are empty
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    if ($user_count == 0) {
        // Insert users
        $conn->exec("INSERT INTO users (email, password, role, full_name, phone, department) VALUES 
            ('admin@college.edu', 'admin123', 'admin', 'System Administrator', '9876543210', 'IT Department'),
            ('student@college.edu', 'student123', 'student', 'John Doe', '9876543211', 'Computer Science'),
            ('student2@college.edu', 'student123', 'student', 'Jane Smith', '9876543212', 'Electronics'),
            ('reviewer@college.edu', 'reviewer123', 'reviewer', 'Dr. Robert Johnson', '9876543213', 'Computer Science'),
            ('reviewer2@college.edu', 'reviewer123', 'reviewer', 'Dr. Sarah Williams', '9876543214', 'Electronics')");
        
        // Insert reviewers
        $conn->exec("INSERT INTO reviewers (user_id, department, expertise, experience_years) VALUES 
            (4, 'Computer Science', 'AI, Machine Learning, Database Systems', 10),
            (5, 'Electronics', 'IoT, Embedded Systems, Circuit Design', 8)");
        
        // Insert sample events
        $conn->exec("INSERT INTO event_details (event_name, event_date, event_time, venue, description, organizer_id, status, max_participants) VALUES 
            ('Tech Symposium 2025', '2025-08-15', '10:00:00', 'Main Auditorium', 'Annual technology symposium showcasing latest innovations', 1, 'approved', 200),
            ('Project Exhibition', '2025-08-20', '14:00:00', 'Exhibition Hall', 'Student project exhibition and competition', 1, 'approved', 150),
            ('Industry Expert Talk', '2025-08-25', '11:00:00', 'Conference Room A', 'Guest lecture by industry experts', 1, 'pending', 100)");
        
        // Insert sample slots
        $conn->exec("INSERT INTO slots (event_id, slot_date, start_time, end_time, capacity) VALUES 
            (1, '2025-08-15', '10:00:00', '12:00:00', 100),
            (1, '2025-08-15', '14:00:00', '16:00:00', 100),
            (2, '2025-08-20', '14:00:00', '17:00:00', 150),
            (3, '2025-08-25', '11:00:00', '12:30:00', 100)");
        
        // Insert sample IRA students
        $conn->exec("INSERT INTO ira_registered_students (student_name, student_id, email, phone, department, year_of_study, project_title, project_description) VALUES 
            ('Alice Johnson', 'CS2021001', 'alice@college.edu', '9876543215', 'Computer Science', 4, 'AI-Powered Learning Management System', 'Development of an intelligent LMS using machine learning algorithms'),
            ('Bob Wilson', 'EC2021002', 'bob@college.edu', '9876543216', 'Electronics', 4, 'IoT-Based Smart Campus System', 'Implementation of IoT sensors for smart campus management'),
            ('Carol Davis', 'CS2022003', 'carol@college.edu', '9876543217', 'Computer Science', 3, 'Blockchain-Based Voting System', 'Secure and transparent voting system using blockchain technology')");
    }
    
    $message = "✅ Database setup completed successfully!";
    
} catch(PDOException $e) {
    $message = "❌ Error: " . $e->getMessage();
}

// Handle table viewing
$selected_table = $_GET['table'] ?? '';
$table_data = [];
$table_structure = [];

if ($selected_table && in_array($selected_table, array_keys($tables))) {
    try {
        // Get table structure
        $structure_query = $conn->query("PRAGMA table_info($selected_table)");
        $table_structure = $structure_query->fetchAll();
        
        // Get table data
        $data_query = $conn->query("SELECT * FROM $selected_table ORDER BY id DESC LIMIT 50");
        $table_data = $data_query->fetchAll();
    } catch(PDOException $e) {
        $message = "Error viewing table: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQLite Database Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 2rem; }
        .header p { margin: 5px 0 0 0; opacity: 0.9; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .nav { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .nav h2 { margin: 0 0 15px 0; color: #333; }
        .table-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .table-link { display: block; padding: 15px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 8px; text-decoration: none; color: #495057; transition: all 0.3s; }
        .table-link:hover { background: #e9ecef; border-color: #667eea; color: #667eea; }
        .table-link.active { background: #667eea; color: white; border-color: #667eea; }
        .table-info { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .table-structure { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .data-table { width: 100%; border-collapse: collapse; background: white; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .data-table th { background: #f8f9fa; font-weight: 600; color: #495057; }
        .data-table tr:hover { background: #f8f9fa; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #5a6fd8; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #667eea; }
        .stat-label { color: #6c757d; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ SQLite Database Admin Panel</h1>
            <p>Event Management Portal - Database Administration</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Get database statistics
        $stats = [];
        try {
            $stats['users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
            $stats['events'] = $conn->query("SELECT COUNT(*) as count FROM event_details")->fetch()['count'];
            $stats['bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'];
            $stats['ira_students'] = $conn->query("SELECT COUNT(*) as count FROM ira_registered_students")->fetch()['count'];
        } catch(Exception $e) {
            // Ignore errors for stats
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['users'] ?? 0 ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['events'] ?? 0 ?></div>
                <div class="stat-label">Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['bookings'] ?? 0 ?></div>
                <div class="stat-label">Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['ira_students'] ?? 0 ?></div>
                <div class="stat-label">IRA Students</div>
            </div>
        </div>
        
        <div class="nav">
            <h2>📊 Database Tables</h2>
            <div class="table-links">
                <a href="?table=users" class="table-link <?= $selected_table === 'users' ? 'active' : '' ?>">
                    👥 Users<br><small>System users and authentication</small>
                </a>
                <a href="?table=event_details" class="table-link <?= $selected_table === 'event_details' ? 'active' : '' ?>">
                    🎭 Events<br><small>Event information and details</small>
                </a>
                <a href="?table=reviewers" class="table-link <?= $selected_table === 'reviewers' ? 'active' : '' ?>">
                    👨‍🏫 Reviewers<br><small>Event and project reviewers</small>
                </a>
                <a href="?table=slots" class="table-link <?= $selected_table === 'slots' ? 'active' : '' ?>">
                    ⏰ Time Slots<br><small>Event scheduling and timing</small>
                </a>
                <a href="?table=bookings" class="table-link <?= $selected_table === 'bookings' ? 'active' : '' ?>">
                    📅 Bookings<br><small>User event registrations</small>
                </a>
                <a href="?table=ira_registered_students" class="table-link <?= $selected_table === 'ira_registered_students' ? 'active' : '' ?>">
                    🎓 IRA Students<br><small>Independent research students</small>
                </a>
                <a href="?table=ira_reviews" class="table-link <?= $selected_table === 'ira_reviews' ? 'active' : '' ?>">
                    📝 IRA Reviews<br><small>Project review scores</small>
                </a>
            </div>
        </div>
        
        <?php if ($selected_table): ?>
            <div class="table-info">
                <h2>📋 Table: <?= strtoupper($selected_table) ?></h2>
                
                <?php if ($table_structure): ?>
                    <div class="table-structure">
                        <h3>🏗️ Table Structure</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Null</th>
                                    <th>Default</th>
                                    <th>Primary Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($table_structure as $column): ?>
                                    <tr>
                                        <td><strong><?= $column['name'] ?></strong></td>
                                        <td><?= $column['type'] ?></td>
                                        <td><?= $column['notnull'] ? 'No' : 'Yes' ?></td>
                                        <td><?= $column['dflt_value'] ?? 'None' ?></td>
                                        <td><?= $column['pk'] ? 'Yes' : 'No' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if ($table_data): ?>
                    <h3>📊 Table Data (Latest 50 records)</h3>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($table_data[0]) as $column): ?>
                                        <th><?= $column ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($table_data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No data found in this table.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="nav">
            <h2>🚀 Quick Actions</h2>
            <a href="login.php" class="btn">🔐 Go to Login Page</a>
            <a href="dashboard.php" class="btn">📊 Dashboard</a>
            <a href="?" class="btn">🔄 Refresh Database</a>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
            <h3>💡 How to Use This Database Admin Panel:</h3>
            <ul>
                <li><strong>📊 View Tables:</strong> Click on any table name to see its structure and data</li>
                <li><strong>📁 Database File:</strong> Your data is stored in <code>event_management.db</code></li>
                <li><strong>🔒 Access Control:</strong> Use the login credentials to test different user roles</li>
                <li><strong>📝 Add Data:</strong> Use your portal forms to add events, bookings, etc.</li>
                <li><strong>💾 Backup:</strong> Simply copy the <code>event_management.db</code> file to backup</li>
            </ul>
        </div>
    </div>
</body>
</html>
