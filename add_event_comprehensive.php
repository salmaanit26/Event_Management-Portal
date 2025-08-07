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
        $event_name = sanitize_input($_POST['event_name']);
        $event_date = sanitize_input($_POST['event_date']);
        $reg_deadline = sanitize_input($_POST['reg_deadline']);
        $event_organizer = sanitize_input($_POST['event_organizer']);
        $domain = sanitize_input($_POST['domain']);
        $event_type = sanitize_input($_POST['event_type']);
        $event_category = sanitize_input($_POST['event_category']);
        $competition_name = sanitize_input($_POST['competition_name'] ?? '');
        $country = sanitize_input($_POST['country'] ?? 'India');
        $state = sanitize_input($_POST['state']);
        $city = sanitize_input($_POST['city']);
        $venue_details = sanitize_input($_POST['venue_details']);
        $applicant_name = sanitize_input($_POST['applicant_name'] ?? $user['full_name']);
        $applicant_id = sanitize_input($_POST['applicant_id']);
        $department = sanitize_input($_POST['department'] ?? $user['department']);
        $year_role = sanitize_input($_POST['year_role']);
        $email = sanitize_input($_POST['email'] ?? $user['email']);
        $phone = sanitize_input($_POST['phone'] ?? $user['phone']);
        $special_lab_name = sanitize_input($_POST['special_lab_name'] ?? '');
        $special_lab_id = sanitize_input($_POST['special_lab_id'] ?? '');
        $special_lab_incharge = sanitize_input($_POST['special_lab_incharge'] ?? '');
        $ira = sanitize_input($_POST['ira'] ?? 'NO');
        $priority = sanitize_input($_POST['priority'] ?? 'Medium');
        $estimated_participants = intval($_POST['estimated_participants'] ?? 0);
        $budget_required = floatval($_POST['budget_required'] ?? 0.00);
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
            event_name, event_date, reg_deadline, event_organizer, domain, event_type, 
            event_category, competition_name, country, state, city, venue_details, 
            brochure, applied_by, applicant_name, applicant_id, department, year_role, 
            email, phone, special_lab_name, special_lab_id, special_lab_incharge, 
            ira, priority, estimated_participants, budget_required, remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $event_name, $event_date, $reg_deadline, $event_organizer, $domain, $event_type,
            $event_category, $competition_name, $country, $state, $city, $venue_details,
            $brochure, $user_id, $applicant_name, $applicant_id, $department, $year_role,
            $email, $phone, $special_lab_name, $special_lab_id, $special_lab_incharge,
            $ira, $priority, $estimated_participants, $budget_required, $remarks
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
        Applicant: {$applicant_name}
        Department: {$department}
        Email: {$email}
        Phone: {$phone}
        Estimated Participants: {$estimated_participants}
        Budget Required: ₹{$budget_required}
        
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
    <title>Submit New Event - Event Management Portal</title>
    <link rel="stylesheet" href="css/add_event.css">
    <style>
        .form-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .form-section {
            margin-bottom: 40px;
            padding: 25px;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            background: #fafafa;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1976d2;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1976d2;
            outline: none;
            box-shadow: 0 0 10px rgba(25, 118, 210, 0.1);
        }
        
        .required {
            color: #e53e3e;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #1976d2, #42a5f5);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>🎯 Event Management Portal</h1>
            <div class="nav-links">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="status.php">📋 My Events</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h2>Submit New Event Proposal</h2>
        
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
                        <input type="text" id="event_name" name="event_name" required>
                    </div>
                    <div class="form-group">
                        <label for="event_organizer">Event Organizer <span class="required">*</span></label>
                        <input type="text" id="event_organizer" name="event_organizer" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Event Date <span class="required">*</span></label>
                        <input type="date" id="event_date" name="event_date" required>
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
                        <label for="event_category">Event Category <span class="required">*</span></label>
                        <select id="event_category" name="event_category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_name']; ?>">
                                    <?php echo $category['category_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="competition_name">Competition Name (if applicable)</label>
                        <input type="text" id="competition_name" name="competition_name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estimated_participants">Estimated Participants <span class="required">*</span></label>
                        <input type="number" id="estimated_participants" name="estimated_participants" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="budget_required">Budget Required (₹)</label>
                        <input type="number" id="budget_required" name="budget_required" step="0.01" min="0">
                        <div class="help-text">Enter 0 if no budget required</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Priority Level</label>
                        <select id="priority" name="priority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ira">Requires IRA Assessment?</label>
                        <select id="ira" name="ira">
                            <option value="NO">No</option>
                            <option value="YES">Yes</option>
                        </select>
                        <div class="help-text">Individual Research Assessment for technical events</div>
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
                        <label for="country">Country <span class="required">*</span></label>
                        <input type="text" id="country" name="country" value="India" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State <span class="required">*</span></label>
                        <input type="text" id="state" name="state" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required>
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
                        <input type="text" id="applicant_name" name="applicant_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="applicant_id">Student/Employee ID <span class="required">*</span></label>
                        <input type="text" id="applicant_id" name="applicant_id" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department <span class="required">*</span></label>
                        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" required>
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
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
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
