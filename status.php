<?php
session_start();
require_once 'connection_sqlite.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get user's events with comprehensive details
if ($user_role == 'admin') {
    // Admin can see all events
    $events_query = $conn->query("
        SELECT e.*, u.full_name as applicant_name, u.email as applicant_email 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        ORDER BY e.created_at DESC
    ");
} else {
    // Regular users see only their events
    $events_query = $conn->prepare("
        SELECT e.*, u.full_name as applicant_name, u.email as applicant_email 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        WHERE e.applied_by = ? 
        ORDER BY e.created_at DESC
    ");
    $events_query->execute([$user_id]);
}

$events = $events_query->fetchAll();

// Handle status updates (admin only)
if ($user_role == 'admin' && $_POST && isset($_POST['update_status'])) {
    try {
        $event_id = intval($_POST['event_id']);
        $new_status = sanitize_input($_POST['new_status']);
        $admin_notes = sanitize_input($_POST['admin_notes']);
        
        // Update event status
        $update_stmt = $conn->prepare("UPDATE event_details SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_stmt->execute([$new_status, $admin_notes, $event_id]);
        
        // Get event details for notification
        $event_stmt = $conn->prepare("SELECT * FROM event_details WHERE id = ?");
        $event_stmt->execute([$event_id]);
        $event_data = $event_stmt->fetch();
        
        // Create notification for the applicant
        $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $notification_stmt->execute([
            $event_data['applied_by'],
            "Event Status Updated",
            "Your event '{$event_data['event_name']}' status has been updated to: {$new_status}",
            $new_status == 'Approved' ? 'success' : ($new_status == 'Rejected' ? 'error' : 'info')
        ]);
        
        // Send email notification
        require_once 'email_service.php';
        $email_service = new EmailService();
        $email_service->sendStatusUpdateEmail($event_data, $event_data['email'], $new_status, $admin_notes);
        
        header("Location: status.php?updated=1");
        exit();
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Apply filters
if ($status_filter != 'all' || !empty($search)) {
    $where_conditions = [];
    $params = [];
    
    if ($user_role != 'admin') {
        $where_conditions[] = "e.applied_by = ?";
        $params[] = $user_id;
    }
    
    if ($status_filter != 'all') {
        $where_conditions[] = "e.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(e.event_name LIKE ? OR e.event_organizer LIKE ? OR e.applicant_name LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $filtered_query = $conn->prepare("
        SELECT e.*, u.full_name as applicant_name, u.email as applicant_email 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        {$where_clause}
        ORDER BY e.created_at DESC
    ");
    $filtered_query->execute($params);
    $events = $filtered_query->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user_role == 'admin' ? 'All Events Management' : 'My Events Status'; ?> - Event Management Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nav-container h1 {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: #4a5568;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            min-width: 200px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.3);
        }
        
        .events-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .event-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .event-organizer {
            color: #718096;
            font-weight: 600;
        }
        
        .status-badges {
            display: flex;
            gap: 0.5rem;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: #fed7d7; color: #c53030; }
        .status-approved { background: #c6f6d5; color: #22543d; }
        .status-rejected { background: #fed7d7; color: #c53030; }
        .status-in-progress { background: #bee3f8; color: #2b6cb0; }
        .status-completed { background: #d4edda; color: #155724; }
        
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .priority-low { background: #e2e8f0; color: #4a5568; }
        .priority-medium { background: #bee3f8; color: #2b6cb0; }
        .priority-high { background: #fbb6ce; color: #b83280; }
        .priority-critical { background: #fed7d7; color: #c53030; }
        
        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #4299e1;
        }
        
        .detail-section h4 {
            color: #2d3748;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .detail-value {
            color: #2d3748;
            text-align: right;
        }
        
        .admin-actions {
            background: #f1f5f9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            border: 2px dashed #cbd5e0;
        }
        
        .admin-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            min-width: 150px;
        }
        
        .empty-state {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 4rem 2rem;
            color: #718096;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .success-message {
            background: #c6f6d5;
            color: #22543d;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #9ae6b4;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .event-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .status-badges {
                align-items: flex-start;
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .event-details {
                grid-template-columns: 1fr;
            }
            
            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group select,
            .filter-group input {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1>🎯 Event Management Portal</h1>
            <div class="nav-links">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="add_event.php">➕ Add Event</a>
                <a href="status.php">📋 <?php echo $user_role == 'admin' ? 'All Events' : 'My Events'; ?></a>
                <?php if ($user_role == 'admin'): ?>
                    <a href="database_admin.php">⚙️ Admin Panel</a>
                    <a href="edit_reviewer.php">👥 Reviewers</a>
                <?php endif; ?>
                <a href="ira_register.php">🎯 IRA Registration</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2><?php echo $user_role == 'admin' ? '📋 All Events Management' : '📋 My Event Submissions'; ?></h2>
            <p><?php echo $user_role == 'admin' ? 'Manage and review all event submissions' : 'Track the status of your submitted events'; ?></p>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="success-message">
                ✅ Event status updated successfully! Email notification sent to applicant.
            </div>
        <?php endif; ?>

        <div class="filters-section">
            <form method="GET" class="filters-row">
                <div class="filter-group">
                    <label for="status">Filter by Status:</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Search Events:</label>
                    <input type="text" name="search" id="search" placeholder="Event name, organizer, applicant..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">
                    🔍 Apply Filters
                </button>
                
                <a href="status.php" class="btn" style="background: #e2e8f0; color: #4a5568; margin-top: 1.5rem;">
                    🔄 Clear Filters
                </a>
            </form>
        </div>

        <div class="events-grid">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="event-header">
                            <div>
                                <h3 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p class="event-organizer">Organized by: <?php echo htmlspecialchars($event['event_organizer']); ?></p>
                            </div>
                            <div class="status-badges">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $event['status'])); ?>">
                                    <?php echo $event['status']; ?>
                                </span>
                                <span class="priority-badge priority-<?php echo strtolower($event['priority']); ?>">
                                    <?php echo $event['priority']; ?> Priority
                                </span>
                                <?php if ($event['ira'] == 'YES'): ?>
                                    <span class="priority-badge priority-medium">IRA Required</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="event-details">
                            <div class="detail-section">
                                <h4>📅 Event Information</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Event Date:</span>
                                    <span class="detail-value"><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Registration Deadline:</span>
                                    <span class="detail-value"><?php echo date('F j, Y', strtotime($event['reg_deadline'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Event Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Category:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['event_category']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Domain:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['domain']); ?></span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>📍 Location & Logistics</h4>
                                <div class="detail-item">
                                    <span class="detail-label">City:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['city']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">State:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['state']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Country:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['country']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Estimated Participants:</span>
                                    <span class="detail-value"><?php echo number_format($event['estimated_participants']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Budget Required:</span>
                                    <span class="detail-value">₹<?php echo number_format($event['budget_required'], 2); ?></span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>👤 Applicant Details</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['applicant_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">ID:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['applicant_id']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Department:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['department']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Year/Role:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['year_role']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['phone']); ?></span>
                                </div>
                            </div>

                            <div class="detail-section">
                                <h4>⏱️ Timeline</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Submitted:</span>
                                    <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($event['created_at'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Last Updated:</span>
                                    <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($event['updated_at'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Reference ID:</span>
                                    <span class="detail-value">EVT-<?php echo $event['id']; ?></span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($event['venue_details'])): ?>
                            <div class="detail-section" style="margin-top: 1rem;">
                                <h4>🏢 Venue Details</h4>
                                <p><?php echo htmlspecialchars($event['venue_details']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($event['remarks'])): ?>
                            <div class="detail-section" style="margin-top: 1rem;">
                                <h4>📝 Remarks</h4>
                                <p><?php echo htmlspecialchars($event['remarks']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($event['admin_notes'])): ?>
                            <div class="detail-section" style="margin-top: 1rem; background: #fff3cd; border-left-color: #ffc107;">
                                <h4>📋 Admin Notes</h4>
                                <p><?php echo htmlspecialchars($event['admin_notes']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($event['special_lab_name']): ?>
                            <div class="detail-section" style="margin-top: 1rem;">
                                <h4>🔬 Special Lab Requirements</h4>
                                <div class="detail-item">
                                    <span class="detail-label">Lab Name:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($event['special_lab_name']); ?></span>
                                </div>
                                <?php if ($event['special_lab_id']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Lab ID:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($event['special_lab_id']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($event['special_lab_incharge']): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Lab In-charge:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($event['special_lab_incharge']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($user_role == 'admin'): ?>
                            <div class="admin-actions">
                                <h4 style="margin-bottom: 1rem; color: #2d3748;">🛠️ Admin Actions</h4>
                                <form method="POST" class="admin-form">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="new_status_<?php echo $event['id']; ?>">Update Status:</label>
                                        <select name="new_status" id="new_status_<?php echo $event['id']; ?>" required>
                                            <option value="">Select Status</option>
                                            <option value="Pending" <?php echo $event['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="In Progress" <?php echo $event['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Approved" <?php echo $event['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="Rejected" <?php echo $event['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="Completed" <?php echo $event['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="admin_notes_<?php echo $event['id']; ?>">Admin Notes:</label>
                                        <textarea name="admin_notes" id="admin_notes_<?php echo $event['id']; ?>" 
                                                rows="2" placeholder="Add notes for the applicant..."><?php echo htmlspecialchars($event['admin_notes']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        ✅ Update Status
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">📋</div>
                    <h3><?php echo !empty($search) || $status_filter != 'all' ? 'No events match your filters' : 'No events found'; ?></h3>
                    <p><?php echo $user_role == 'admin' ? 'No events have been submitted yet.' : 'You haven\'t submitted any events yet.'; ?></p>
                    <?php if ($user_role != 'admin'): ?>
                        <a href="add_event.php" class="btn btn-primary" style="margin-top: 1rem;">
                            ➕ Submit Your First Event
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
