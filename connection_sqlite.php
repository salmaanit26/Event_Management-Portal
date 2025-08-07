<?php
// SQLite Database Connection - Complete Event Management System
$database_file = __DIR__ . '/event_management.db';

try {
    $conn = new PDO("sqlite:$database_file");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create comprehensive users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('admin', 'student', 'faculty', 'reviewer')) DEFAULT 'student',
        full_name TEXT NOT NULL,
        department TEXT,
        year_of_study TEXT,
        phone TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create comprehensive event_details table with ALL original fields
    $conn->exec("CREATE TABLE IF NOT EXISTS event_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_name TEXT NOT NULL,
        event_date DATE NOT NULL,
        reg_deadline DATE NOT NULL,
        event_organizer TEXT NOT NULL,
        domain TEXT NOT NULL,
        event_type TEXT NOT NULL,
        event_category TEXT NOT NULL,
        competition_name TEXT,
        country TEXT NOT NULL DEFAULT 'India',
        state TEXT NOT NULL,
        city TEXT NOT NULL,
        venue_details TEXT,
        brochure TEXT,
        applied_by INTEGER NOT NULL,
        applicant_name TEXT NOT NULL,
        applicant_id TEXT NOT NULL,
        department TEXT NOT NULL,
        year_role TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT NOT NULL,
        special_lab_name TEXT,
        special_lab_id TEXT,
        special_lab_incharge TEXT,
        status TEXT DEFAULT 'Pending' CHECK(status IN ('Pending', 'In Progress', 'Approved', 'Rejected', 'Completed')),
        ira TEXT DEFAULT 'NO' CHECK(ira IN ('YES', 'NO')),
        remarks TEXT,
        admin_notes TEXT,
        priority TEXT DEFAULT 'Medium' CHECK(priority IN ('Low', 'Medium', 'High', 'Critical')),
        estimated_participants INTEGER DEFAULT 0,
        budget_required REAL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (applied_by) REFERENCES users(id)
    )");
    
    // Create reviewers table
    $conn->exec("CREATE TABLE IF NOT EXISTS reviewers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        department TEXT NOT NULL,
        specialization TEXT,
        experience_years INTEGER DEFAULT 0,
        phone TEXT,
        status TEXT DEFAULT 'Active' CHECK(status IN ('Active', 'Inactive')),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create IRA registered students table
    $conn->exec("CREATE TABLE IF NOT EXISTS ira_registered_students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        student_name TEXT NOT NULL,
        student_id TEXT NOT NULL,
        student_mail_id TEXT NOT NULL,
        department TEXT NOT NULL,
        year_of_study TEXT NOT NULL,
        cgpa REAL,
        phone TEXT,
        reviewer_id INTEGER,
        slot_id INTEGER,
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        assessment_status TEXT DEFAULT 'Registered' CHECK(assessment_status IN ('Registered', 'Scheduled', 'Assessed', 'Qualified', 'Not Qualified')),
        assessment_score REAL,
        feedback TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES event_details(id),
        FOREIGN KEY (reviewer_id) REFERENCES reviewers(id)
    )");
    
    // Create slots table
    $conn->exec("CREATE TABLE IF NOT EXISTS slots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date DATE NOT NULL,
        time_from TIME NOT NULL,
        time_to TIME NOT NULL,
        reviewer_id INTEGER NOT NULL,
        event_id INTEGER NOT NULL,
        max_students INTEGER DEFAULT 1,
        booked_students INTEGER DEFAULT 0,
        status TEXT DEFAULT 'Available' CHECK(status IN ('Available', 'Booked', 'Completed', 'Cancelled')),
        room_number TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reviewer_id) REFERENCES reviewers(id),
        FOREIGN KEY (event_id) REFERENCES event_details(id)
    )");
    
    // Create bookings table
    $conn->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slot_id INTEGER NOT NULL,
        student_id INTEGER NOT NULL,
        ira_registration_id INTEGER NOT NULL,
        booking_status TEXT DEFAULT 'Confirmed' CHECK(booking_status IN ('Confirmed', 'Cancelled', 'Completed', 'No Show')),
        booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        attendance_marked INTEGER DEFAULT 0,
        assessment_completed INTEGER DEFAULT 0,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (slot_id) REFERENCES slots(id),
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (ira_registration_id) REFERENCES ira_registered_students(id)
    )");
    
    // Create IRA reviews table
    $conn->exec("CREATE TABLE IF NOT EXISTS ira_reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ira_registration_id INTEGER NOT NULL,
        reviewer_id INTEGER NOT NULL,
        technical_score REAL DEFAULT 0.00,
        communication_score REAL DEFAULT 0.00,
        problem_solving_score REAL DEFAULT 0.00,
        overall_score REAL DEFAULT 0.00,
        feedback TEXT,
        recommendation TEXT DEFAULT 'Recommended' CHECK(recommendation IN ('Highly Recommended', 'Recommended', 'Conditionally Recommended', 'Not Recommended')),
        assessment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ira_registration_id) REFERENCES ira_registered_students(id),
        FOREIGN KEY (reviewer_id) REFERENCES reviewers(id)
    )");
    
    // Create notifications table
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        type TEXT DEFAULT 'info' CHECK(type IN ('info', 'success', 'warning', 'error')),
        read_status INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // Create event categories table
    $conn->exec("CREATE TABLE IF NOT EXISTS event_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name TEXT NOT NULL UNIQUE,
        description TEXT,
        requires_ira INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert sample users if table is empty
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    if ($user_count == 0) {
        $conn->exec("INSERT INTO users (email, password, role, full_name, department, phone) VALUES 
            ('admin@college.edu', 'admin123', 'admin', 'System Administrator', 'IT Department', '1234567890'),
            ('student@college.edu', 'student123', 'student', 'Demo Student', 'Computer Science', '9876543210'),
            ('faculty@college.edu', 'faculty123', 'faculty', 'Demo Faculty', 'Electronics', '5555555555'),
            ('reviewer@college.edu', 'reviewer123', 'reviewer', 'Dr. Smith', 'Computer Science', '4444444444')");
        
        // Insert event categories
        $conn->exec("INSERT INTO event_categories (category_name, description, requires_ira) VALUES 
            ('Technical Competition', 'Programming, AI/ML, Robotics competitions', 1),
            ('Cultural Event', 'Music, Dance, Drama, Art competitions', 0),
            ('Sports Tournament', 'Indoor and outdoor sports events', 0),
            ('Workshop/Seminar', 'Educational workshops and seminars', 0),
            ('Hackathon', 'Coding competitions and innovation challenges', 1),
            ('Research Conference', 'Academic conferences and research presentations', 1),
            ('Industry Visit', 'Educational visits to companies and industries', 0),
            ('Internship Fair', 'Career fairs and internship opportunities', 0)");
        
        // Insert sample reviewers
        $conn->exec("INSERT INTO reviewers (name, email, department, specialization, experience_years, phone) VALUES 
            ('Dr. John Smith', 'john.smith@college.edu', 'Computer Science', 'Artificial Intelligence', 10, '1111111111'),
            ('Prof. Jane Doe', 'jane.doe@college.edu', 'Electronics', 'Embedded Systems', 8, '2222222222'),
            ('Dr. Mike Johnson', 'mike.johnson@college.edu', 'Information Technology', 'Cybersecurity', 12, '3333333333')");
    }
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions for SQLite compatibility
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
