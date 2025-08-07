-- Event Management System Database Setup
-- Drop database if exists and create new one
DROP DATABASE IF EXISTS event_management;
CREATE DATABASE event_management;
USE event_management;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'faculty') DEFAULT 'student',
    full_name VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    year_of_study VARCHAR(20),
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Event details table
CREATE TABLE event_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    reg_deadline DATE NOT NULL,
    event_organizer VARCHAR(255) NOT NULL,
    domain VARCHAR(100) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_category VARCHAR(100) NOT NULL,
    competition_name VARCHAR(255),
    country VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    venue_details TEXT,
    brochure VARCHAR(500),
    applied_by INT NOT NULL,
    applicant_name VARCHAR(255) NOT NULL,
    applicant_id VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    year_role VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    special_lab_name VARCHAR(255),
    special_lab_id VARCHAR(100),
    special_lab_incharge VARCHAR(255),
    status ENUM('Pending', 'In Progress', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
    ira ENUM('YES', 'NO') DEFAULT 'NO',
    remarks TEXT,
    admin_notes TEXT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    estimated_participants INT DEFAULT 0,
    budget_required DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Reviewers table for IRA assessment
CREATE TABLE reviewers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    specialization VARCHAR(255),
    experience_years INT DEFAULT 0,
    phone VARCHAR(15),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- IRA registered students table
CREATE TABLE ira_registered_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    student_id VARCHAR(100) NOT NULL,
    student_mail_id VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    year_of_study VARCHAR(20) NOT NULL,
    cgpa DECIMAL(3,2),
    phone VARCHAR(15),
    reviewer_id INT,
    slot_id INT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assessment_status ENUM('Registered', 'Scheduled', 'Assessed', 'Qualified', 'Not Qualified') DEFAULT 'Registered',
    assessment_score DECIMAL(5,2),
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES event_details(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES reviewers(id) ON DELETE SET NULL
);

-- Slots table for IRA scheduling
CREATE TABLE slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time_from TIME NOT NULL,
    time_to TIME NOT NULL,
    reviewer_id INT NOT NULL,
    event_id INT NOT NULL,
    max_students INT DEFAULT 1,
    booked_students INT DEFAULT 0,
    status ENUM('Available', 'Booked', 'Completed', 'Cancelled') DEFAULT 'Available',
    room_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES reviewers(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES event_details(id) ON DELETE CASCADE
);

-- Bookings table for slot management
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_id INT NOT NULL,
    student_id INT NOT NULL,
    ira_registration_id INT NOT NULL,
    booking_status ENUM('Confirmed', 'Cancelled', 'Completed', 'No Show') DEFAULT 'Confirmed',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_marked BOOLEAN DEFAULT FALSE,
    assessment_completed BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ira_registration_id) REFERENCES ira_registered_students(id) ON DELETE CASCADE
);

-- IRA reviews table for assessment results
CREATE TABLE ira_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ira_registration_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    technical_score DECIMAL(5,2) DEFAULT 0.00,
    communication_score DECIMAL(5,2) DEFAULT 0.00,
    problem_solving_score DECIMAL(5,2) DEFAULT 0.00,
    overall_score DECIMAL(5,2) DEFAULT 0.00,
    feedback TEXT,
    recommendation ENUM('Highly Recommended', 'Recommended', 'Conditionally Recommended', 'Not Recommended') DEFAULT 'Recommended',
    assessment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ira_registration_id) REFERENCES ira_registered_students(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES reviewers(id) ON DELETE CASCADE
);

-- Notifications table for system notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Event categories table for standardized categories
CREATE TABLE event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    requires_ira BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default data
INSERT INTO users (email, password, role, full_name, department, phone) VALUES
('admin@college.edu', 'admin123', 'admin', 'System Administrator', 'IT Department', '1234567890'),
('student@college.edu', 'student123', 'student', 'Demo Student', 'Computer Science', '9876543210'),
('faculty@college.edu', 'faculty123', 'faculty', 'Demo Faculty', 'Electronics', '5555555555');

INSERT INTO event_categories (category_name, description, requires_ira) VALUES
('Technical Competition', 'Programming, AI/ML, Robotics competitions', TRUE),
('Cultural Event', 'Music, Dance, Drama, Art competitions', FALSE),
('Sports Tournament', 'Indoor and outdoor sports events', FALSE),
('Workshop/Seminar', 'Educational workshops and seminars', FALSE),
('Hackathon', 'Coding competitions and innovation challenges', TRUE),
('Research Conference', 'Academic conferences and research presentations', TRUE),
('Industry Visit', 'Educational visits to companies and industries', FALSE),
('Internship Fair', 'Career fairs and internship opportunities', FALSE);

INSERT INTO reviewers (name, email, department, specialization, experience_years, phone) VALUES
('Dr. John Smith', 'john.smith@college.edu', 'Computer Science', 'Artificial Intelligence', 10, '1111111111'),
('Prof. Jane Doe', 'jane.doe@college.edu', 'Electronics', 'Embedded Systems', 8, '2222222222'),
('Dr. Mike Johnson', 'mike.johnson@college.edu', 'Information Technology', 'Cybersecurity', 12, '3333333333');

-- Create indexes for better performance
CREATE INDEX idx_event_status ON event_details(status);
CREATE INDEX idx_event_date ON event_details(event_date);
CREATE INDEX idx_ira_status ON ira_registered_students(assessment_status);
CREATE INDEX idx_slot_date ON slots(date);
CREATE INDEX idx_booking_status ON bookings(booking_status);
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_notifications_user ON notifications(user_id, read_status);

-- Update foreign key reference in ira_registered_students
ALTER TABLE ira_registered_students ADD FOREIGN KEY (slot_id) REFERENCES slots(id) ON DELETE SET NULL;
