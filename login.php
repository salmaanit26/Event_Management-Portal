<?php
session_start();
include('connection_sqlite.php');

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
    if (empty($email) || empty($password) || empty($role)) {
        $error_message = "Please fill in all fields.";
    } else {
        // Use prepared statement to prevent SQL injection
        $sql = "SELECT id, email, password, role, full_name FROM users WHERE email = ? AND role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        
        if ($user) {
            
            // Use plain text password comparison (for easier testing)
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['csrf_token'] = generate_csrf_token();
                
                // Redirect based on role
                if ($user['role'] == 'faculty') {
                    header("Location: faculty_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "Invalid email or role selected.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management Portal - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/framework.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-4);
        }
        
        .login-container {
            width: 100%;
            max-width: 1000px;
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: var(--spacing-8);
            color: var(--white);
            text-align: center;
        }
        
        .login-left h1 {
            font-size: var(--text-4xl);
            font-weight: 700;
            margin-bottom: var(--spacing-4);
            color: var(--white);
        }
        
        .login-left p {
            font-size: var(--text-lg);
            opacity: 0.9;
            color: var(--white);
        }
        
        .login-right {
            padding: var(--spacing-8);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-8);
        }
        
        .login-header h2 {
            color: var(--gray-900);
            margin-bottom: var(--spacing-2);
        }
        
        .login-header p {
            color: var(--gray-600);
            margin-bottom: 0;
        }
        
        .form-container {
            width: 100%;
        }
        
        .form-group {
            position: relative;
        }
        
        .form-input {
            padding-left: var(--spacing-10);
        }
        
        .form-icon {
            position: absolute;
            left: var(--spacing-3);
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: var(--spacing-6);
        }
        
        .forgot-password a {
            font-size: var(--text-sm);
            color: var(--primary-color);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: var(--spacing-6) 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        
        .divider span {
            padding: 0 var(--spacing-4);
            color: var(--gray-500);
            font-size: var(--text-sm);
        }
        
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-3);
            width: 100%;
            padding: var(--spacing-3);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            background: var(--white);
            color: var(--gray-700);
            font-weight: 500;
            transition: all var(--transition-fast);
            text-decoration: none;
        }
        
        .google-btn:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
            text-decoration: none;
            color: var(--gray-800);
        }
        
        .alert {
            margin-bottom: var(--spacing-4);
        }
        
        .role-selector {
            margin-bottom: var(--spacing-4);
        }
        
        .role-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-2);
            margin-top: var(--spacing-2);
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .role-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: var(--spacing-3);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-align: center;
        }
        
        .role-option input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
            color: var(--primary-color);
        }
        
        .role-option i {
            font-size: var(--text-lg);
            margin-bottom: var(--spacing-1);
        }
        
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }
            
            .login-left {
                padding: var(--spacing-6);
            }
            
            .login-right {
                padding: var(--spacing-6);
            }
            
            .role-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div>
                <i class="fas fa-graduation-cap" style="font-size: 4rem; margin-bottom: var(--spacing-4);"></i>
                <h1>Welcome Back</h1>
                <p>Sign in to access your Event Management Portal and manage college events efficiently.</p>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>Sign In</h2>
                <p>Enter your credentials to access your account</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="form-container" method="POST" novalidate>
                <div class="role-selector">
                    <label class="form-label">Select Your Role</label>
                    <div class="role-options">
                        <div class="role-option">
                            <input type="radio" id="admin" name="role" value="admin" required>
                            <label for="admin">
                                <i class="fas fa-user-shield"></i>
                                <span>Admin</span>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="faculty" name="role" value="faculty" required>
                            <label for="faculty">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Faculty</span>
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="student" name="role" value="student" required>
                            <label for="student">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div style="position: relative;">
                        <i class="fas fa-envelope form-icon"></i>
                        <input type="email" id="email" name="email" class="form-input" 
                               placeholder="Enter your email address" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock form-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="#" onclick="alert('Please contact system administrator for password reset.')">
                        Forgot your password?
                    </a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input[required]');
            
            // Add real-time validation
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateField(this);
                    }
                });
            });
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            function validateField(field) {
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';
                
                if (field.hasAttribute('required') && !value) {
                    isValid = false;
                    errorMessage = 'This field is required';
                } else if (field.type === 'email' && value && !isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                
                // Remove existing error styling and message
                field.classList.remove('error');
                const existingError = field.parentNode.querySelector('.form-error');
                if (existingError) {
                    existingError.remove();
                }
                
                if (!isValid) {
                    field.classList.add('error');
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'form-error';
                    errorDiv.textContent = errorMessage;
                    field.parentNode.appendChild(errorDiv);
                }
                
                return isValid;
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
        });
    </script>
</body>
</html>