# Quick Setup Guide - Event Management Portal

## 🚀 Getting Started (5 Minutes Setup)

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Wait for both to show green "Running" status

### Step 2: Setup Database (Automated)
1. Open your web browser
2. Go to: `http://localhost/Event_management_p/setup_database.php`
3. Wait for the setup to complete automatically
4. You should see "Setup Complete!" message

### Step 3: Access the Portal
1. Go to: `http://localhost/Event_management_p/login.php`
2. Use these credentials to login:

**Administrator Login:**
- Email: `admin@college.edu`
- Password: `admin123`
- Role: Admin

**Faculty Login:**
- Email: `faculty@college.edu`
- Password: `faculty123`
- Role: Faculty

**Student Login:**
- Email: `student@college.edu`
- Password: `student123`
- Role: Student

## 🔧 What's New & Improved

### ✅ Modern Professional UI
- Clean, responsive design that works on all devices
- Professional color scheme and typography
- Intuitive navigation with sidebar layout
- Progress indicators and visual feedback

### ✅ Enhanced Security
- CSRF protection on all forms
- Input sanitization and validation
- Prepared SQL statements
- Session security improvements

### ✅ Better Database Structure
- Normalized database design
- Foreign key relationships
- Indexes for better performance
- Comprehensive data validation

### ✅ Improved Functionality
- **Dashboard**: Statistics cards, recent events overview
- **Event Requests**: Step-by-step form with validation
- **Status Tracking**: Real-time status updates with timeline view
- **Admin Panel**: Easy event approval/rejection with notes
- **IRA System**: Streamlined assessment workflow

### ✅ Professional Features
- Auto-save forms (prevents data loss)
- Real-time form validation
- Progress tracking
- Mobile-responsive design
- Loading states and animations

## 📱 Key Pages Overview

### 1. Login Page (`/login.php`)
- Role-based login (Admin/Faculty/Student)
- Modern card-based design
- Form validation
- Demo credentials displayed

### 2. Dashboard (`/dashboard.php`)
- Statistics overview
- Recent events table
- Role-based navigation
- Quick actions

### 3. Add Event (`/add_event.php`)
- Multi-section form
- Progress tracking
- Real-time validation
- Auto-save functionality

### 4. Status Tracking (`/status.php`)
- Event status timeline
- Filter and search
- Admin approval workflow
- Visual status indicators

### 5. IRA System (`/ira_page.php`)
- Student registration for assessments
- Reviewer management
- Slot booking system

## 🎯 How the System Works

### For Students/Faculty:
1. **Login** with your credentials
2. **Submit Event Request** via Add Event form
3. **Track Status** on Status page
4. **Register for IRA** if event requires assessment
5. **Book Assessment Slot** when available

### For Administrators:
1. **Review Event Requests** on Dashboard
2. **Approve/Reject Events** with comments
3. **Manage IRA Requirements** for events
4. **Monitor System Statistics**
5. **Manage Reviewers and Slots**

## 🛡️ Security Features

- **CSRF Token Protection**: All forms protected against cross-site attacks
- **Input Sanitization**: All user inputs cleaned and validated
- **SQL Injection Prevention**: Prepared statements used throughout
- **Session Security**: Secure session management
- **Role-based Access**: Different permissions for different users

## 📊 Database Tables

The system uses these main tables:
- `users` - User accounts and authentication
- `event_details` - Event requests and information
- `reviewers` - Faculty reviewers for IRA
- `ira_registered_students` - IRA registrations
- `slots` - Available assessment time slots
- `bookings` - Slot bookings
- `ira_reviews` - Assessment results
- `notifications` - System notifications

## 🔍 Troubleshooting

### Database Issues:
- If setup fails, check MySQL is running in XAMPP
- Run setup_database.php again if needed
- Check error logs in XAMPP for details

### Access Issues:
- Ensure Apache is running
- Check the URL is correct: `http://localhost/Event_management_p/`
- Clear browser cache if CSS/JS not loading

### Login Issues:
- Use exact credentials provided
- Select correct role from radio buttons
- Check Caps Lock is off

## 🎨 Customization

The system uses a custom CSS framework (`css/framework.css`) that you can easily customize:

- **Colors**: Modify CSS variables in `:root`
- **Typography**: Change font families and sizes
- **Layout**: Adjust spacing and component sizes
- **Themes**: Easy to create dark mode or custom themes

## 📈 Performance Features

- **Optimized Database**: Proper indexing and relationships
- **Responsive Design**: Fast loading on all devices
- **Efficient Queries**: Minimized database calls
- **Modern CSS**: Hardware-accelerated animations

## 🔮 Future Enhancements Ready

The codebase is structured to easily add:
- Email notifications
- File upload for documents
- Calendar integration
- Mobile app API
- Advanced reporting
- Real-time chat

---

**Your professional Event Management Portal is now ready to use!**

Start by logging in as an admin to explore the full functionality, then test the student/faculty workflows.

Need help? Check the README.md file for detailed documentation.
