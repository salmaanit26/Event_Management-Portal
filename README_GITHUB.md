# 🎓 Event Management Portal

A comprehensive web-based Event Management Portal designed for educational institutions to manage student events, IRA (Individual Review Assessment) processes, and faculty evaluations.

## 🌟 Features

### 👨‍🎓 **Student Features**
- **Event Suggestions**: Students can suggest events from KnowaFest that would benefit the college
- **IRA Registration**: Register for Individual Review Assessment slots
- **Event Status Tracking**: Monitor event approval and IRA requirements
- **Professional Dashboard**: Clean, responsive interface

### 👨‍💼 **Admin Features**
- **Event Management**: Review and approve student event suggestions
- **IRA Slot Creation**: Create time slots for Individual Review Assessments
- **Faculty Assignment**: Assign faculty members to evaluate students
- **Comprehensive Dashboard**: Complete system oversight and statistics

### 👩‍🏫 **Faculty Features**
- **Student Evaluation**: Evaluate students during assigned IRA slots
- **Assessment Dashboard**: View assigned students and evaluation schedules
- **Performance Tracking**: Submit evaluation results and feedback

## 🚀 Live Demo

**GitHub Repository**: [Event_Management_Portal](https://github.com/salmaanit26/Event_Management_Portal)

## 🔐 Test Credentials

### Admin Access
- **Email**: `admin@college.edu`
- **Password**: `admin123`

### Faculty Access
- **Email**: `faculty@college.edu`
- **Password**: `faculty123`

### Student Access
- **Email**: `student@college.edu`
- **Password**: `student123`

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: SQLite (portable and lightweight)
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache (XAMPP compatible)
- **Authentication**: Role-based access control
- **File Handling**: Upload support for event documentation

## 📋 System Workflow

1. **Students** suggest events from KnowaFest that would benefit the college
2. **Admin** reviews suggestions and decides if IRA is required
3. **Admin** creates time slots and assigns faculty for evaluation
4. **Students** register for available IRA slots
5. **Faculty** evaluate students during assigned time slots
6. **System** tracks all activities and provides comprehensive reporting

## 🔧 Installation & Setup

### Prerequisites
- XAMPP/WAMP/LAMP server
- PHP 8.0 or higher
- SQLite extension enabled

### Quick Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/salmaanit26/Event_Management_Portal.git
   ```

2. Move to web server directory:
   ```bash
   cp -r Event_Management_Portal /xampp/htdocs/
   ```

3. Start Apache server

4. Access the portal:
   ```
   http://localhost/Event_Management_Portal/login.php
   ```

### Database Setup
The system uses SQLite for easy deployment. The database will be automatically created on first run.

## 📁 Project Structure

```
Event_Management_Portal/
├── 📄 login.php              # Authentication system
├── 📄 dashboard.php          # Role-based dashboard
├── 📄 add_event.php         # Event suggestion form
├── 📄 ira_register.php      # IRA registration system
├── 📄 status.php            # Event status tracking
├── 📁 css/                  # Stylesheets
├── 📁 images/               # Project assets
├── 📁 uploads/              # File upload directory
└── 📄 event_management.db   # SQLite database
```

## 🎨 UI/UX Features

- **Professional Design**: Clean, modern interface
- **Responsive Layout**: Mobile-friendly design
- **Consistent Navigation**: Standardized across all pages
- **Role-based UI**: Interface adapts to user role
- **No Clutter**: Removed instructional content for professional appearance

## 🔒 Security Features

- **Role-based Access Control**: Admin, Faculty, Student roles
- **Input Sanitization**: Protection against SQL injection
- **Session Management**: Secure user sessions
- **File Upload Validation**: Safe file handling

## 📊 Database Schema

### Users Table
- User authentication and role management
- Support for admin, faculty, and student roles

### Event Details
- Complete event information storage
- IRA requirement tracking
- Approval status management

### IRA System
- Slot management for assessments
- Faculty assignment tracking
- Student registration records

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📧 Contact

**Developer**: Arsalan  
**Email**: arsalmaan123@gmail.com  
**GitHub**: [@salmaanit26](https://github.com/salmaanit26)

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- Built for educational institutions
- Designed with user experience in mind
- Focused on professional workflow management

---

**🎯 Ready to manage your events professionally!** 🚀
