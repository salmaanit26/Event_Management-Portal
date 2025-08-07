# 🎓 Event Management Portal

Hello! Welcome to our comprehensive Event Management Portal for educational institutions.

## 🚀 Features

### For Students/Faculty
- **Event Request Submission**: Submit requests for new events with detailed information
- **Event Status Tracking**: Monitor the approval status of submitted events
- **IRA Registration**: Register for Internal Review Assessment for qualifying events
- **Slot Booking**: Book time slots for IRA assessments
- **Dashboard**: View personal event history and statistics

### For Administrators
- **Event Management**: Approve, reject, or modify event requests
- **IRA System**: Manage reviewers, slots, and assessment schedules
- **User Management**: Handle user accounts and permissions
- **Reporting**: Generate reports and export data
- **Reviewer Management**: Add and manage faculty reviewers

### General Features
- **Professional UI**: Modern, responsive design with intuitive navigation
- **Security**: CSRF protection, input sanitization, and secure authentication
- **Mobile Responsive**: Works seamlessly on all devices
- **Real-time Updates**: Live status updates and notifications
- **Data Export**: Export reports and data in various formats

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3 (Custom Framework), JavaScript (ES6+)
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Inter (Google Fonts)

## 📋 Prerequisites

Before setting up the project, ensure you have:

- **XAMPP** (or similar local server environment)
  - Apache Web Server
  - MySQL Database
  - PHP 7.4 or higher
- **Web Browser** (Chrome, Firefox, Safari, Edge)

## 🔧 Installation & Setup

### Step 1: Download and Setup XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP and start Apache and MySQL services
3. Ensure both services are running (green indicators in XAMPP Control Panel)

### Step 2: Database Setup

1. **Open phpMyAdmin**:
   - Navigate to `http://localhost/phpmyadmin` in your browser
   - Or click "Admin" next to MySQL in XAMPP Control Panel

2. **Create Database**:
   - Click "New" in the left sidebar
   - Create a new database named `event_management`
   - Select `utf8mb4_general_ci` as collation

3. **Import Database Structure**:
   - Select the `event_management` database
   - Click on the "SQL" tab
   - Copy and paste the contents of `database_setup.sql` file
   - Click "Go" to execute the SQL commands

### Step 3: Project Setup

1. **Copy Project Files**:
   ```
   Copy the entire project folder to: C:\xampp\htdocs\Event_management_p\
   ```

2. **Verify File Structure**:
   ```
   C:\xampp\htdocs\Event_management_p\
   ├── connection.php
   ├── login.php
   ├── dashboard.php
   ├── add_event.php
   ├── database_setup.sql
   ├── css/
   │   ├── framework.css
   │   └── (other CSS files)
   └── (other PHP files)
   ```

3. **Configure Database Connection**:
   - Open `connection.php`
   - Verify the database credentials:
     ```php
     $servername = "127.0.0.1";
     $username = "root";
     $password = "";
     $dbname = "event_management";
     ```

### Step 4: Access the Application

1. **Open Your Browser**
2. **Navigate to**: `http://localhost/Event_management_p/login.php`
3. **Login with Demo Credentials**:

   **Administrator Account**:
   - Email: `admin@college.edu`
   - Password: `admin123`
   - Role: Admin

   **Faculty Account**:
   - Email: `faculty@college.edu`
   - Password: `faculty123`
   - Role: Faculty

   **Student Account**:
   - Email: `student@college.edu`
   - Password: `student123`
   - Role: Student

## 🗃️ Database Schema

The system uses the following main tables:

- **users**: User authentication and profile information
- **event_details**: Event requests and information
- **reviewers**: Faculty reviewers for IRA assessments
- **ira_registered_students**: Students registered for IRA
- **slots**: Available time slots for assessments
- **bookings**: Slot bookings and attendance
- **ira_reviews**: Assessment results and feedback
- **notifications**: System notifications
- **event_categories**: Predefined event categories

## 🔐 Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Prevention**: Prepared statements used throughout
- **Session Management**: Secure session handling
- **Role-based Access**: Different permissions for different user types

## 📱 Mobile Responsive Design

The portal is fully responsive and works on:
- **Desktop** (1200px+)
- **Tablet** (768px - 1199px)
- **Mobile** (320px - 767px)

## 🎨 UI/UX Features

- **Modern Design**: Professional interface with clean aesthetics
- **Intuitive Navigation**: Easy-to-use sidebar and navigation
- **Visual Feedback**: Progress bars, status badges, and notifications
- **Accessibility**: Proper contrast ratios and keyboard navigation
- **Loading States**: Visual indicators for form submissions

## 🚨 Troubleshooting

### Common Issues:

1. **"Connection failed" Error**:
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `connection.php`
   - Verify database exists and is properly imported

2. **PHP Errors**:
   - Check PHP error logs in XAMPP
   - Ensure PHP version is 7.4 or higher
   - Verify all required PHP extensions are enabled

3. **CSS/JS Not Loading**:
   - Check file paths are correct
   - Ensure Apache is running
   - Clear browser cache

4. **Permission Denied**:
   - Check file permissions
   - Ensure XAMPP has write access to the directory

### Reset Database:

If you need to reset the database:

1. Open phpMyAdmin
2. Select `event_management` database
3. Click "Drop" to delete the database
4. Re-create the database and import `database_setup.sql`

## 📊 Default Data

The system comes with predefined:

- **3 User Accounts** (Admin, Faculty, Student)
- **8 Event Categories** (Technical, Cultural, Sports, etc.)
- **3 Sample Reviewers**
- **Event Types and Domains**

## 🔄 Future Enhancements

Planned features for future versions:

- **Email Notifications**: Automated email alerts
- **Calendar Integration**: Event calendar view
- **File Upload**: Document and image uploads
- **Advanced Reporting**: Detailed analytics and reports
- **API Integration**: REST API for mobile apps
- **Real-time Chat**: Communication between stakeholders

## 🆘 Support

For technical support or questions:

1. Check the troubleshooting section above
2. Review the database setup instructions
3. Ensure all prerequisites are met
4. Verify file permissions and server configuration

## 📄 License

This project is developed for educational purposes as a college event management system.

---

**Last Updated**: January 2025
**Version**: 2.0.0
**Compatibility**: PHP 7.4+, MySQL 5.7+, Modern Web Browsers
