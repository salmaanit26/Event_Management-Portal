<?php
session_start();
require_once 'connection_sqlite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get user details
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Get comprehensive statistics
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_events,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_events,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_events,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_events,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_events,
        SUM(CASE WHEN ira = 'YES' THEN 1 ELSE 0 END) as ira_events,
        ROUND(AVG(estimated_participants), 0) as avg_participants,
        SUM(budget_required) as total_budget,
        COUNT(DISTINCT applied_by) as total_applicants
    FROM event_details
");
$stats = $stats_query->fetch();

// Get user's events
$user_events_query = $conn->prepare("
    SELECT * FROM event_details 
    WHERE applied_by = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$user_events_query->execute([$user_id]);
$user_events = $user_events_query->fetchAll();

// Get recent events for admin/all users
if ($user_role == 'admin') {
    $recent_events_query = $conn->query("
        SELECT e.*, u.full_name as applicant_name, u.department as applicant_dept
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        ORDER BY e.created_at DESC 
        LIMIT 10
    ");
} else {
    $recent_events_query = $conn->query("
        SELECT e.*, u.full_name as applicant_name, u.department as applicant_dept
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        WHERE e.status = 'Approved'
        ORDER BY e.event_date ASC 
        LIMIT 10
    ");
}
$recent_events = $recent_events_query->fetchAll();

// Get notifications
$notifications_query = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND read_status = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
$notifications_query->execute([$user_id]);
$notifications = $notifications_query->fetchAll();

// Get priority events for admin
$priority_events = [];
if ($user_role == 'admin') {
    $priority_query = $conn->query("
        SELECT e.*, u.full_name as applicant_name 
        FROM event_details e 
        LEFT JOIN users u ON e.applied_by = u.id 
        WHERE e.priority IN ('High', 'Critical') AND e.status = 'Pending'
        ORDER BY 
            CASE e.priority 
                WHEN 'Critical' THEN 1 
                WHEN 'High' THEN 2 
                ELSE 3 
            END,
            e.created_at ASC
        LIMIT 5
    ");
    $priority_events = $priority_query->fetchAll();
}

// Get upcoming events
$upcoming_events_query = $conn->query("
    SELECT e.*, u.full_name as applicant_name 
    FROM event_details e 
    LEFT JOIN users u ON e.applied_by = u.id 
    WHERE e.event_date >= date('now') AND e.status = 'Approved'
    ORDER BY e.event_date ASC 
    LIMIT 5
");
$upcoming_events = $upcoming_events_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Event Management Portal</title>
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
        
        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-section h2 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #4a5568;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .description {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            color: #2d3748;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .event-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .event-item {
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .event-item:hover {
            border-color: #4299e1;
            background: #f7fafc;
        }
        
        .event-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .event-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .event-meta span {
            font-size: 0.85rem;
            color: #718096;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
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
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .priority-low { background: #e2e8f0; color: #4a5568; }
        .priority-medium { background: #bee3f8; color: #2b6cb0; }
        .priority-high { background: #fbb6ce; color: #b83280; }
        .priority-critical { background: #fed7d7; color: #c53030; }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
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
        
        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .notification-item {
            padding: 1rem;
            border-left: 4px solid #4299e1;
            background: #f7fafc;
            border-radius: 0 8px 8px 0;
            margin-bottom: 0.5rem;
        }
        
        .notification-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .notification-time {
            color: #a0aec0;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        .empty-state {
            text-align: center;
            color: #718096;
            padding: 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .dashboard-container {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="add_event_comprehensive.php">➕ Add Event</a>
                <a href="status.php">📋 My Events</a>
                <?php if ($user_role == 'admin'): ?>
                    <a href="database_admin.php">⚙️ Admin Panel</a>
                    <a href="edit_reviewer.php">👥 Reviewers</a>
                <?php endif; ?>
                <a href="ira_register.php">🎯 IRA Registration</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h2>
            <p>Here's what's happening in your event management portal today.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Events</h3>
                <div class="number" style="color: #4299e1;"><?php echo $stats['total_events'] ?? 0; ?></div>
                <div class="description">All events in system</div>
            </div>
            
            <div class="stat-card">
                <h3>Pending Approval</h3>
                <div class="number" style="color: #ed8936;"><?php echo $stats['pending_events'] ?? 0; ?></div>
                <div class="description">Awaiting review</div>
            </div>
            
            <div class="stat-card">
                <h3>Approved Events</h3>
                <div class="number" style="color: #48bb78;"><?php echo $stats['approved_events'] ?? 0; ?></div>
                <div class="description">Ready to proceed</div>
            </div>
            
            <div class="stat-card">
                <h3>IRA Events</h3>
                <div class="number" style="color: #9f7aea;"><?php echo $stats['ira_events'] ?? 0; ?></div>
                <div class="description">Research assessments</div>
            </div>
            
            <div class="stat-card">
                <h3>Avg. Participants</h3>
                <div class="number" style="color: #38b2ac;"><?php echo $stats['avg_participants'] ?? 0; ?></div>
                <div class="description">Per event</div>
            </div>
            
            <div class="stat-card">
                <h3>Total Budget</h3>
                <div class="number" style="color: #f56565;">₹<?php echo number_format($stats['total_budget'] ?? 0, 2); ?></div>
                <div class="description">Requested funds</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="main-content">
                <?php if ($user_role == 'admin' && !empty($priority_events)): ?>
                <div class="section-card">
                    <h3 class="section-title">🚨 Priority Events Requiring Attention</h3>
                    <div class="event-list">
                        <?php foreach ($priority_events as $event): ?>
                        <div class="event-item">
                            <div class="event-header">
                                <div>
                                    <div class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></div>
                                    <div class="event-meta">
                                        <span>📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                        <span>👤 <?php echo htmlspecialchars($event['applicant_name']); ?></span>
                                        <span>💰 ₹<?php echo number_format($event['budget_required'], 2); ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <span class="priority-badge priority-<?php echo strtolower($event['priority']); ?>">
                                        <?php echo $event['priority']; ?>
                                    </span>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $event['status'])); ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <p style="color: #718096; font-size: 0.9rem; margin-top: 0.5rem;">
                                <?php echo htmlspecialchars(substr($event['remarks'] ?? 'No remarks provided.', 0, 150)); ?>
                                <?php if (strlen($event['remarks'] ?? '') > 150) echo '...'; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="section-card">
                    <h3 class="section-title">📅 Recent Events</h3>
                    <?php if (!empty($recent_events)): ?>
                    <div class="event-list">
                        <?php foreach ($recent_events as $event): ?>
                        <div class="event-item">
                            <div class="event-header">
                                <div>
                                    <div class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></div>
                                    <div class="event-meta">
                                        <span>📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                        <span>📍 <?php echo htmlspecialchars($event['city'] . ', ' . $event['state']); ?></span>
                                        <span>👤 <?php echo htmlspecialchars($event['applicant_name'] ?? 'Unknown'); ?></span>
                                        <span>🏢 <?php echo htmlspecialchars($event['department'] ?? ''); ?></span>
                                        <span>👥 <?php echo $event['estimated_participants']; ?> participants</span>
                                        <?php if ($event['budget_required'] > 0): ?>
                                            <span>💰 ₹<?php echo number_format($event['budget_required'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $event['status'])); ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                    <?php if ($event['ira'] == 'YES'): ?>
                                        <span class="priority-badge priority-medium">IRA Required</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organizer']); ?> | 
                                <strong>Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?> | 
                                <strong>Category:</strong> <?php echo htmlspecialchars($event['event_category']); ?>
                            </div>
                            <?php if ($event['venue_details']): ?>
                                <p style="color: #718096; font-size: 0.9rem; margin-top: 0.5rem;">
                                    <strong>Venue:</strong> <?php echo htmlspecialchars($event['venue_details']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i>📋</i>
                        <p>No events found. <a href="add_event_comprehensive.php">Create your first event</a>!</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="quick-actions">
                    <a href="add_event_comprehensive.php" class="btn btn-primary">
                        ➕ Submit New Event
                    </a>
                    <a href="status.php" class="btn btn-secondary">
                        📋 View All My Events
                    </a>
                    <?php if ($user_role == 'admin'): ?>
                        <a href="database_admin.php" class="btn btn-secondary">
                            ⚙️ Admin Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-content">
                <?php if (!empty($upcoming_events)): ?>
                <div class="section-card">
                    <h3 class="section-title">⏰ Upcoming Events</h3>
                    <div class="event-list">
                        <?php foreach ($upcoming_events as $event): ?>
                        <div class="event-item">
                            <div class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></div>
                            <div class="event-meta">
                                <span>📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                <span>📍 <?php echo htmlspecialchars($event['city']); ?></span>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <span class="status-badge status-approved">Approved</span>
                                <?php if ($event['ira'] == 'YES'): ?>
                                    <span class="priority-badge priority-medium" style="margin-left: 0.5rem;">IRA</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($notifications)): ?>
                <div class="section-card">
                    <h3 class="section-title">🔔 Recent Notifications</h3>
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($user_events)): ?>
                <div class="section-card">
                    <h3 class="section-title">📝 My Recent Submissions</h3>
                    <div class="event-list">
                        <?php foreach ($user_events as $event): ?>
                        <div class="event-item">
                            <div class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></div>
                            <div class="event-meta">
                                <span>📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                <span>💰 ₹<?php echo number_format($event['budget_required'], 2); ?></span>
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $event['status'])); ?>">
                                    <?php echo $event['status']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
