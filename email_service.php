<?php
// Email Follow-up System for Event Management Portal
// This file contains functions for sending email notifications

class EmailService {
    private $from_email;
    private $from_name;
    private $smtp_enabled;
    
    public function __construct() {
        $this->from_email = "noreply@college.edu";
        $this->from_name = "Event Management Portal";
        $this->smtp_enabled = false; // Set to true if SMTP is configured
    }
    
    /**
     * Send event submission confirmation email
     */
    public function sendEventSubmissionEmail($event_data, $applicant_email) {
        $subject = "Event Submission Confirmation - " . $event_data['event_name'];
        
        $message = $this->generateSubmissionEmailTemplate($event_data);
        
        return $this->sendEmail($applicant_email, $subject, $message);
    }
    
    /**
     * Send event status update email
     */
    public function sendStatusUpdateEmail($event_data, $applicant_email, $new_status, $admin_notes = '') {
        $subject = "Event Status Update - " . $event_data['event_name'];
        
        $message = $this->generateStatusUpdateTemplate($event_data, $new_status, $admin_notes);
        
        return $this->sendEmail($applicant_email, $subject, $message);
    }
    
    /**
     * Send admin notification email
     */
    public function sendAdminNotificationEmail($event_data) {
        $admin_email = "admin@college.edu"; // Configure admin email
        $subject = "New Event Submission Requires Review - " . $event_data['event_name'];
        
        $message = $this->generateAdminNotificationTemplate($event_data);
        
        return $this->sendEmail($admin_email, $subject, $message);
    }
    
    /**
     * Send IRA reminder email
     */
    public function sendIRAReminder($event_data, $student_email) {
        $subject = "IRA Registration Reminder - " . $event_data['event_name'];
        
        $message = $this->generateIRAReminderTemplate($event_data);
        
        return $this->sendEmail($student_email, $subject, $message);
    }
    
    /**
     * Generate event submission email template
     */
    private function generateSubmissionEmailTemplate($event_data) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .footer { background: #e9ecef; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; }
                .status { padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 5px; margin: 15px 0; }
                .highlight { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎯 Event Submission Confirmed</h1>
                    <p>Your event proposal has been successfully submitted</p>
                </div>
                
                <div class='content'>
                    <h2>Dear {$event_data['applicant_name']},</h2>
                    
                    <p>Thank you for submitting your event proposal. We have received your application and it is currently under review.</p>
                    
                    <div class='status'>
                        <strong>✅ Submission Status:</strong> Successfully Received<br>
                        <strong>📋 Reference ID:</strong> EVT-{$event_data['id']}<br>
                        <strong>📅 Submitted On:</strong> " . date('F j, Y g:i A', strtotime($event_data['created_at'])) . "
                    </div>
                    
                    <div class='details'>
                        <h3>Event Details Submitted:</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Event Name:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['event_name']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Event Date:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . date('F j, Y', strtotime($event_data['event_date'])) . "</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Registration Deadline:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . date('F j, Y', strtotime($event_data['reg_deadline'])) . "</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Event Type:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['event_type']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Category:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['event_category']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Location:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['city']}, {$event_data['state']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Estimated Participants:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['estimated_participants']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Budget Required:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>₹" . number_format($event_data['budget_required'], 2) . "</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Priority:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['priority']}</td></tr>
                        </table>
                    </div>
                    
                    " . ($event_data['ira'] == 'YES' ? "
                    <div class='highlight'>
                        <strong>📊 IRA Assessment Required:</strong><br>
                        Your event requires Individual Research Assessment (IRA). Please ensure participants register for IRA evaluation through the portal.
                    </div>
                    " : "") . "
                    
                    <h3>What happens next?</h3>
                    <ol>
                        <li><strong>Review Process:</strong> Our admin team will review your submission within 2-3 business days</li>
                        <li><strong>Status Updates:</strong> You'll receive email notifications for any status changes</li>
                        <li><strong>Approval/Feedback:</strong> Once reviewed, you'll receive approval or feedback for improvements</li>
                        <li><strong>Event Execution:</strong> After approval, you can proceed with event planning and execution</li>
                    </ol>
                    
                    <p><strong>Track Your Application:</strong> You can check the status of your application anytime by logging into the Event Management Portal.</p>
                    
                    <p>If you have any questions or need to make changes to your submission, please contact our support team immediately.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>Event Management Portal</strong><br>
                    📧 Email: support@college.edu | 📞 Phone: +91-XXXXXXXXXX<br>
                    🌐 Portal: <a href='http://localhost/Event_management_p/dashboard.php'>Login to Dashboard</a></p>
                    
                    <p style='font-size: 12px; color: #6c757d; margin-top: 15px;'>
                        This is an automated email. Please do not reply to this email address.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generate status update email template
     */
    private function generateStatusUpdateTemplate($event_data, $new_status, $admin_notes) {
        $status_colors = [
            'Approved' => '#28a745',
            'Rejected' => '#dc3545',
            'In Progress' => '#007bff',
            'Completed' => '#6f42c1'
        ];
        
        $status_color = $status_colors[$new_status] ?? '#6c757d';
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .status-update { background: white; padding: 20px; border-radius: 8px; border-left: 5px solid {$status_color}; margin: 20px 0; }
                .footer { background: #e9ecef; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Event Status Update</h1>
                    <p>Your event status has been updated</p>
                </div>
                
                <div class='content'>
                    <h2>Dear {$event_data['applicant_name']},</h2>
                    
                    <p>We have an important update regarding your event submission.</p>
                    
                    <div class='status-update'>
                        <h3 style='color: {$status_color}; margin-top: 0;'>Status: {$new_status}</h3>
                        <p><strong>Event:</strong> {$event_data['event_name']}</p>
                        <p><strong>Reference ID:</strong> EVT-{$event_data['id']}</p>
                        <p><strong>Updated On:</strong> " . date('F j, Y g:i A') . "</p>
                        
                        " . ($admin_notes ? "<p><strong>Admin Notes:</strong><br>{$admin_notes}</p>" : "") . "
                    </div>
                    
                    " . $this->getStatusSpecificMessage($new_status) . "
                    
                    <p>For any queries or clarifications, please contact our support team.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>Event Management Portal</strong><br>
                    📧 Email: support@college.edu | 📞 Phone: +91-XXXXXXXXXX<br>
                    🌐 Portal: <a href='http://localhost/Event_management_p/dashboard.php'>Login to Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generate admin notification template
     */
    private function generateAdminNotificationTemplate($event_data) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 700px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .priority { padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 New Event Submission</h1>
                    <p>Requires Admin Review</p>
                </div>
                
                <div class='content'>
                    <h2>New Event Submission Alert</h2>
                    
                    <p>A new event has been submitted and requires your review and approval.</p>
                    
                    " . ($event_data['priority'] == 'High' || $event_data['priority'] == 'Critical' ? "
                    <div class='priority'>
                        <strong>⚠️ {$event_data['priority']} Priority Event</strong> - Please review urgently
                    </div>
                    " : "") . "
                    
                    <div class='details'>
                        <h3>Event Details:</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Event Name:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['event_name']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Applicant:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['applicant_name']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Department:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['department']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['email']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Event Date:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . date('F j, Y', strtotime($event_data['event_date'])) . "</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Registration Deadline:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . date('F j, Y', strtotime($event_data['reg_deadline'])) . "</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Event Type:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['event_type']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Category:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['event_category']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Location:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['city']}, {$event_data['state']}, {$event_data['country']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Estimated Participants:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['estimated_participants']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Budget Required:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>₹" . number_format($event_data['budget_required'], 2) . "</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Priority:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['priority']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>IRA Required:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$event_data['ira']}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #ddd;'><strong>Submitted:</strong></td><td style='padding: 8px; border-bottom: 1px solid #ddd;'>" . date('F j, Y g:i A', strtotime($event_data['created_at'])) . "</td></tr>
                        </table>
                        
                        " . ($event_data['remarks'] ? "<p><strong>Additional Remarks:</strong><br>{$event_data['remarks']}</p>" : "") . "
                    </div>
                    
                    <p><strong>Action Required:</strong> Please review this event submission in the admin panel and provide approval or feedback.</p>
                    
                    <p style='text-align: center;'>
                        <a href='http://localhost/Event_management_p/database_admin.php' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Review in Admin Panel
                        </a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get status-specific message content
     */
    private function getStatusSpecificMessage($status) {
        switch ($status) {
            case 'Approved':
                return "
                <div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 15px 0;'>
                    <h4 style='color: #155724; margin-top: 0;'>🎉 Congratulations! Your event has been approved.</h4>
                    <p>You can now proceed with:</p>
                    <ul>
                        <li>Event planning and logistics</li>
                        <li>Participant registration</li>
                        <li>Marketing and promotion</li>
                        <li>Venue booking and arrangements</li>
                    </ul>
                </div>
                ";
                
            case 'Rejected':
                return "
                <div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 15px 0;'>
                    <h4 style='color: #721c24; margin-top: 0;'>❌ Your event submission has been rejected.</h4>
                    <p>Please review the admin notes above and consider:</p>
                    <ul>
                        <li>Addressing the mentioned concerns</li>
                        <li>Modifying your event proposal</li>
                        <li>Resubmitting with improvements</li>
                        <li>Contacting admin for clarification</li>
                    </ul>
                </div>
                ";
                
            case 'In Progress':
                return "
                <div style='background: #cce5ff; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 15px 0;'>
                    <h4 style='color: #004085; margin-top: 0;'>⏳ Your event is currently in progress.</h4>
                    <p>Current activities may include:</p>
                    <ul>
                        <li>Further review and verification</li>
                        <li>Budget allocation process</li>
                        <li>Venue availability check</li>
                        <li>Additional documentation required</li>
                    </ul>
                </div>
                ";
                
            case 'Completed':
                return "
                <div style='background: #e2e3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #6f42c1; margin: 15px 0;'>
                    <h4 style='color: #3d1a78; margin-top: 0;'>✅ Your event has been marked as completed.</h4>
                    <p>Thank you for successfully organizing this event. Please consider:</p>
                    <ul>
                        <li>Submitting post-event feedback</li>
                        <li>Sharing event outcomes and reports</li>
                        <li>Planning follow-up activities</li>
                    </ul>
                </div>
                ";
                
            default:
                return "<p>Please check your dashboard for more details.</p>";
        }
    }
    
    /**
     * Send email using PHP's mail function or SMTP
     */
    private function sendEmail($to, $subject, $html_message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
        $headers .= "Reply-To: {$this->from_email}" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // For development/testing - log emails instead of sending
        if (!$this->smtp_enabled) {
            $log_entry = "
            ==================== EMAIL LOG ====================
            TO: {$to}
            SUBJECT: {$subject}
            TIME: " . date('Y-m-d H:i:s') . "
            MESSAGE: {$html_message}
            ===================================================
            
            ";
            
            file_put_contents('email_log.txt', $log_entry, FILE_APPEND);
            return true; // Return true for testing
        }
        
        // Uncomment the line below to enable actual email sending
        // return mail($to, $subject, $html_message, $headers);
        
        return true;
    }
}

// Usage example:
/*
$email_service = new EmailService();

// Send submission confirmation
$email_service->sendEventSubmissionEmail($event_data, $applicant_email);

// Send status update
$email_service->sendStatusUpdateEmail($event_data, $applicant_email, 'Approved', 'Your event meets all requirements.');

// Send admin notification
$email_service->sendAdminNotificationEmail($event_data);
*/
?>
