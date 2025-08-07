<?php
session_start();
require_once 'connection_sqlite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Get user details for auto-fill
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get event categories
$categories = $conn->query("SELECT * FROM event_categories ORDER BY category_name")->fetchAll();

// Handle form submission
if ($_POST && verify_csrf_token($_POST['csrf_token'])) {
    try {
        // Sanitize all inputs
        // Check for duplicate event names
        $duplicate_check = $conn->prepare("SELECT COUNT(*) FROM event_details WHERE event_name = ? AND status != 'Rejected'");
        $duplicate_check->execute([$_POST['event_name']]);
        if ($duplicate_check->fetchColumn() > 0) {
            throw new Exception("An event with this name already exists in the system. Please choose a different name.");
        }

        $event_name = sanitize_input($_POST['event_name']);
        $event_date = sanitize_input($_POST['event_date']);
        $event_time = sanitize_input($_POST['event_time']);
        $reg_deadline = sanitize_input($_POST['reg_deadline']);
        $event_organizer = sanitize_input($_POST['event_organizer']);
        $domain = sanitize_input($_POST['domain']);
        $event_type = sanitize_input($_POST['event_type']);
        $event_category = sanitize_input($_POST['event_category']);
        $competition_name = sanitize_input($_POST['competition_name'] ?? '');
        $country = sanitize_input($_POST['country'] ?? 'India');
        $state = sanitize_input($_POST['state']);
        $city = sanitize_input($_POST['city']);
        $venue = sanitize_input($_POST['venue']);
        $venue_details = sanitize_input($_POST['venue_details']);
        $description = sanitize_input($_POST['description'] ?? '');
        $applicant_name = sanitize_input($_POST['applicant_name'] ?? $user['full_name']);
        $applicant_id = sanitize_input($_POST['applicant_id']);
        $department = sanitize_input($_POST['department'] ?? $user['department']);
        $year_role = sanitize_input($_POST['year_role']);
        $email = sanitize_input($_POST['email'] ?? $user['email']);
        $phone = sanitize_input($_POST['phone'] ?? $user['phone']);
        $special_lab_name = sanitize_input($_POST['special_lab_name'] ?? '');
        $special_lab_id = sanitize_input($_POST['special_lab_id'] ?? '');
        $special_lab_incharge = sanitize_input($_POST['special_lab_incharge'] ?? '');
        $ira = 'NO'; // Admin will decide IRA necessity
        $remarks = sanitize_input($_POST['remarks'] ?? '');
        
        // Handle file upload for brochure
        $brochure = '';
        if (!empty($_FILES['brochure']['name'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['brochure']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $brochure = $upload_dir . time() . '_' . $_FILES['brochure']['name'];
                if (move_uploaded_file($_FILES['brochure']['tmp_name'], $brochure)) {
                    // File uploaded successfully
                } else {
                    throw new Exception("Failed to upload brochure file.");
                }
            } else {
                throw new Exception("Invalid file type. Only PDF, DOC, DOCX, JPG, JPEG, PNG files are allowed.");
            }
        }
        
        // Insert event into database
        $stmt = $conn->prepare("INSERT INTO event_details (
            event_name, event_date, event_time, reg_deadline, event_organizer, domain, event_type, 
            event_category, competition_name, country, state, city, venue, venue_details, description,
            brochure, applied_by, applicant_name, applicant_id, department, year_role, 
            email, phone, special_lab_name, special_lab_id, special_lab_incharge, 
            ira, remarks, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
        
        $stmt->execute([
            $event_name, $event_date, $event_time, $reg_deadline, $event_organizer, $domain, $event_type,
            $event_category, $competition_name, $country, $state, $city, $venue, $venue_details, $description,
            $brochure, $user_id, $applicant_name, $applicant_id, $department, $year_role,
            $email, $phone, $special_lab_name, $special_lab_id, $special_lab_incharge,
            $ira, $remarks, 'Pending'
        ]);
        
        $event_id = $conn->lastInsertId();
        
        // Create notification
        $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $notification_stmt->execute([
            $user_id,
            "Event Submitted Successfully",
            "Your event '{$event_name}' has been submitted for review. Event ID: {$event_id}",
            "success"
        ]);
        
        // Send email notification (if configured)
        $admin_email = "admin@college.edu";
        $subject = "New Event Submission: " . $event_name;
        $message = "
        A new event has been submitted for approval:
        
        Event Name: {$event_name}
        Event Date: {$event_date}
        Registration Deadline: {$reg_deadline}
        Organizer: {$event_organizer}
        Domain: {$domain}
        Type: {$event_type}
        Applicant: {$applicant_name}
        Department: {$department}
        Email: {$email}
        Phone: {$phone}
        
        Please review and approve/reject this event in the admin dashboard.
        ";
        
        $headers = "From: noreply@college.edu\r\n";
        $headers .= "Reply-To: {$email}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Uncomment the line below to enable email notifications
        // mail($admin_email, $subject, $message, $headers);
        
        $success_message = "Event submitted successfully! Your submission ID is: {$event_id}. You will receive email updates about the approval status.";
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Event - Event Management Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .back-button {
            position: fixed;
            top: 2rem;
            left: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #2d3748;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }
        
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            color: #2d3748;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .form-section {
            margin-bottom: 2.5rem;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #3b82f6;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .required {
            color: #ef4444;
        }
        
        .help-text {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
            margin: 2rem auto 0;
            display: block;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        @media (max-width: 768px) {
            .back-button {
                position: static;
                margin-bottom: 1rem;
                display: inline-block;
            }
            
            .form-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">← Back to Dashboard</a>

    <div class="form-container">
        <div class="page-header">
            <h2>➕ Add New Event</h2>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <!-- Event Details Section -->
            <div class="form-section">
                <h3 class="section-title">📅 Event Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_name">Event Name <span class="required">*</span></label>
                        <input type="text" id="event_name" name="event_name" placeholder="e.g., Tech Innovation Challenge 2025" required>
                    </div>
                    <div class="form-group">
                        <label for="event_organizer">Event Organizer <span class="required">*</span></label>
                        <input type="text" id="event_organizer" name="event_organizer" placeholder="e.g., KnowaFest Tech Committee" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Event Date <span class="required">*</span></label>
                        <input type="date" id="event_date" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label for="event_time">Event Time <span class="required">*</span></label>
                        <input type="time" id="event_time" name="event_time" placeholder="e.g., 10:00" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_deadline">Registration Deadline <span class="required">*</span></label>
                        <input type="date" id="reg_deadline" name="reg_deadline" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="domain">Domain <span class="required">*</span></label>
                        <select id="domain" name="domain" required>
                            <option value="">Select Domain</option>
                            <option value="Technical">Technical</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Sports">Sports</option>
                            <option value="Academic">Academic</option>
                            <option value="Research">Research</option>
                            <option value="Social">Social</option>
                            <option value="Professional">Professional</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event_type">Event Type <span class="required">*</span></label>
                        <select id="event_type" name="event_type" required>
                            <option value="">Select Type</option>
                            <option value="Competition">Competition</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Conference">Conference</option>
                            <option value="Festival">Festival</option>
                            <option value="Tournament">Tournament</option>
                            <option value="Exhibition">Exhibition</option>
                            <option value="Hackathon">Hackathon</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_category">Event Format <span class="required">*</span></label>
                        <select id="event_category" name="event_category" required>
                            <option value="">Select Format</option>
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
                            <option value="Hybrid">Hybrid (Online + Offline)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="competition_name">Competition Name (if applicable)</label>
                        <input type="text" id="competition_name" name="competition_name" placeholder="e.g., Tech Challenge 2025">
                        <div class="help-text">Leave blank if not a competition</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="description">Event Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Brief description of the event"></textarea>
                        <div class="help-text">Provide a brief overview of the event</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="brochure">Event Brochure/Flyer</label>
                    <input type="file" id="brochure" name="brochure" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="help-text">Upload PDF, DOC, DOCX, JPG, JPEG, or PNG files only</div>
                </div>
            </div>
            
            <!-- Location Details Section -->
            <div class="form-section">
                <h3 class="section-title">📍 Location Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="venue">Venue Name <span class="required">*</span></label>
                        <input type="text" id="venue" name="venue" placeholder="e.g., Main Auditorium, Conference Hall A" required>
                    </div>
                    <div class="form-group">
                        <label for="country">Country <span class="required">*</span></label>
                        <input type="text" id="country" name="country" value="India" placeholder="India" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="state">State <span class="required">*</span></label>
                        <input type="text" id="state" name="state" placeholder="e.g., Karnataka" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" placeholder="e.g., Bangalore" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="venue_details">Venue Details</label>
                    <textarea id="venue_details" name="venue_details" rows="3" placeholder="Enter complete venue address, hall numbers, parking details, etc."></textarea>
                </div>
            </div>
            
            <!-- Applicant Information Section -->
            <div class="form-section">
                <h3 class="section-title">👤 Applicant Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="applicant_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="applicant_name" name="applicant_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="applicant_id">Student/Employee ID <span class="required">*</span></label>
                        <input type="text" id="applicant_id" name="applicant_id" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" placeholder="e.g., CS2023001" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department <span class="required">*</span></label>
                        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" placeholder="e.g., Computer Science" required>
                    </div>
                    <div class="form-group">
                        <label for="year_role">Year/Role <span class="required">*</span></label>
                        <select id="year_role" name="year_role" required>
                            <option value="">Select Year/Role</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Staff">Staff</option>
                            <option value="Research Scholar">Research Scholar</option>
                            <option value="Alumni">Alumni</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="your.email@college.edu" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+91-9876543210" required>
                    </div>
                </div>
            </div>
            
            <!-- Special Lab Requirements Section -->
            <div class="form-section">
                <h3 class="section-title">🔬 Special Lab Requirements (if applicable)</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="special_lab_name">Lab Name</label>
                        <input type="text" id="special_lab_name" name="special_lab_name" placeholder="e.g., Computer Lab, Electronics Lab">
                    </div>
                    <div class="form-group">
                        <label for="special_lab_id">Lab ID/Code</label>
                        <input type="text" id="special_lab_id" name="special_lab_id" placeholder="e.g., CSE-LAB-01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="special_lab_incharge">Lab In-charge</label>
                    <input type="text" id="special_lab_incharge" name="special_lab_incharge" placeholder="Name of lab in-charge or faculty coordinator">
                </div>
            </div>
            
            <!-- Additional Information Section -->
            <div class="form-section">
                <h3 class="section-title">📝 Additional Information</h3>
                
                <div class="form-group">
                    <label for="remarks">Remarks/Special Instructions</label>
                    <textarea id="remarks" name="remarks" rows="4" placeholder="Any additional information, special requirements, or instructions for the event..."></textarea>
                </div>
            </div>
            
            <div style="text-align: center;">
                <button type="submit" class="submit-btn">
                    🚀 Submit Event Proposal
                </button>
            </div>
        </form>
    </div>

    <script>
        // Auto-set minimum dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('event_date').setAttribute('min', today);
            document.getElementById('reg_deadline').setAttribute('min', today);
            
            // Ensure registration deadline is before event date
            document.getElementById('event_date').addEventListener('change', function() {
                document.getElementById('reg_deadline').setAttribute('max', this.value);
            });
            
            document.getElementById('reg_deadline').addEventListener('change', function() {
                document.getElementById('event_date').setAttribute('min', this.value);
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const eventDate = new Date(document.getElementById('event_date').value);
            const regDeadline = new Date(document.getElementById('reg_deadline').value);
            
            if (regDeadline >= eventDate) {
                e.preventDefault();
                alert('Registration deadline must be before the event date!');
                return false;
            }
            
            const budget = parseFloat(document.getElementById('budget_required').value) || 0;
            if (budget < 0) {
                e.preventDefault();
                alert('Budget cannot be negative!');
                return false;
            }
            
            const participants = parseInt(document.getElementById('estimated_participants').value) || 0;
            if (participants <= 0) {
                e.preventDefault();
                alert('Estimated participants must be greater than 0!');
                return false;
            }
        });
    </script>
</body>
</html>
