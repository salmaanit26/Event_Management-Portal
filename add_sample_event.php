<?php
require_once 'connection_sqlite.php';

// Add a sample event suggestion
$conn->prepare('INSERT INTO event_details (event_name, event_date, event_time, reg_deadline, event_organizer, domain, event_type, event_category, state, city, venue_details, applicant_name, applicant_id, department, year_role, email, phone, applied_by, status, ira, priority, estimated_participants, budget_required, venue, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"))')
->execute(['Tech Innovation Challenge', '2025-08-15', '10:00:00', '2025-08-10', 'KnowaFest Tech Committee', 'Technology', 'Competition', 'Technical', 'Karnataka', 'Bangalore', 'Main Auditorium', 'John Doe', 'CS2023001', 'Computer Science', '3rd Year', 'student@college.edu', '+1-555-0102', 2, 'Pending', 'NO', 'High', 100, 5000.00, 'Main Auditorium', 'A challenging tech competition for innovative solutions']);

echo 'Sample event suggestion added successfully\n';
?>
