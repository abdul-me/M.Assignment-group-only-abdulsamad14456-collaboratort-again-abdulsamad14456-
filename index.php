<?php
/**
 * Login & Register Handler
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Verify CSRF token
    if (empty($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid. Please try again.';
    }
    
    if ($action === 'login' && empty($error)) {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email format.';
        } else {
            // Check user in database
            $user = getRow($conn, "SELECT id, username, email, password, role, full_name FROM users WHERE email = ? AND is_active = 1", "s", [$email]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Login successful
                setUserSession($user['id'], $user['username'], $user['email'], $user['role'], $user['full_name']);
                
                // Log action
                logAction($conn, 'USER_LOGIN', 'users', $user['id']);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
    
    elseif ($action === 'register' && empty($error)) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        // Allow role selection from registration form (user requested Option A).
        // NOTE: Allowing admin creation via public registration is a security risk.
        // This implementation accepts the posted role but limits accepted values.
        $role = sanitizeInput($_POST['role'] ?? 'user');
        if (!in_array($role, ['user', 'admin'])) {
            $role = 'user';
        }
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
            $error = 'All fields are required.';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email format.';
        } elseif (!isValidPassword($password)) {
            $error = 'Password must be at least 6 characters with uppercase, lowercase, and numbers.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if username or email already exists
            $existing = getRow($conn, "SELECT id FROM users WHERE username = ? OR email = ?", "ss", [$username, $email]);
            
            if ($existing) {
                $error = 'Username or email already exists.';
            } else {
                // Insert new user
                $hashed_password = hashPassword($password);
                $affected = executeUpdate(
                    $conn,
                    "INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)",
                    "sssss",
                    [$username, $email, $hashed_password, $full_name, $role]
                );
                
                if ($affected > 0) {
                    $success = 'Registration successful! Please login with your credentials.';
                    // Log action
                    logAction($conn, 'USER_REGISTER', 'users', $conn->insert_id);
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --accent-color: #f39c12;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .auth-container {
            width: 100%;
            max-width: 900px;
            margin: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .auth-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .library-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        /* Ensure header text is always highly visible */
        .auth-header, .auth-header h1, .auth-header p {
            color: #ffffff !important;
            opacity: 1 !important;
        }

        .auth-header h1 {
            font-size: 30px;
            font-weight: 800;
            text-shadow: 0 4px 14px rgba(0,0,0,0.35);
            letter-spacing: 0.2px;
        }

        .auth-header p {
            font-size: 14px;
            opacity: 0.95 !important;
            text-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        
        .auth-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #bdc3c7;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 60, 114, 0.15);
        }
        
        .form-control.is-invalid {
            border-color: var(--danger-color);
        }
        
        .invalid-feedback {
            display: block;
            color: var(--danger-color);
            font-size: 13px;
            margin-top: 5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 60, 114, 0.4);
        }
        
        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
        }
        
        .btn-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .tab-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            color: #95a5a6;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-btn.active {
            color: var(--primary-color);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .toggle-password {
            cursor: pointer;
            user-select: none;
        }
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #7f8c8d;
            transition: all 0.3s ease;
        }
        
        .password-toggle-btn:hover {
            color: var(--primary-color);
        }
        
        .form-group.password-group {
            position: relative;
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #ecf0f1;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: var(--danger-color);
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: #f39c12;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: var(--success-color);
        }

        /* Role select custom styling */
        .role-input-group .input-group-text {
            border-right: none;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            background: linear-gradient(135deg, rgba(42,82,152,0.06), rgba(30,60,114,0.02));
            border-radius: 8px 0 0 8px;
        }

        .role-select {
            border: 2px solid #bdc3c7;
            border-left: none;
            border-radius: 0 8px 8px 0;
            padding: 10px 12px;
            background: linear-gradient(180deg,#ffffff,#fbfbfd);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.98rem;
            display: flex;
            align-items: center;
        }
        }

        .role-input-group .bi-person-badge {
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        @media (max-width: 600px) {
            .auth-body {
                padding: 25px;
            }
            
            .tab-btn {
                font-size: 14px;
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="bi bi-book-fill library-icon"></i>
                <h1>Library Management</h1>
                <p>Manage your library collection efficiently</p>
            </div>
            
            <div class="auth-body">
                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Success Alert -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Tab Toggle -->
                <div class="tab-toggle">
                    <button class="tab-btn active" data-tab="login">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                    <button class="tab-btn" data-tab="register">
                        <i class="bi bi-person-plus"></i> Register
                    </button>
                </div>
                
                <!-- Login Form -->
                <form id="loginForm" class="tab-content active" action="index.php" method="POST" novalidate>
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="loginEmail" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="loginEmail" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="invalid-feedback" id="loginEmailError"></div>
                    </div>
                    
                    <div class="form-group password-group">
                        <label for="loginPassword" class="form-label">Password</label>
                        <div class="input-group password-input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('loginPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <button type="button" class="btn-link" data-tab="register">Register here</button></p>
                    </div>
                </form>
                
                <!-- Register Form -->
                <form id="registerForm" class="tab-content" action="index.php" method="POST" novalidate>
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="registerFullName" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control" id="registerFullName" name="full_name" placeholder="Enter your full name" required>
                        </div>
                        <div class="invalid-feedback" id="fullNameError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerUsername" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-at"></i>
                            </span>
                            <input type="text" class="form-control" id="registerUsername" name="username" placeholder="Choose a username (min 3 chars)" required>
                        </div>
                        <div class="invalid-feedback" id="usernameError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerEmail" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="registerEmail" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="invalid-feedback" id="emailError"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="registerRole" class="form-label">Role</label>
                        <div class="input-group role-input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <select class="form-control role-select" id="registerRole" name="role" required>
                                <option value="user" selected>👤 User</option>
                                <option value="admin">🛡️ Admin</option>
                            </select>
                        </div>
                        <div class="invalid-feedback" id="roleError"></div>
                    </div>
                    
                    <div class="form-group password-group">
                        <label for="registerPassword" class="form-label">Password</label>
                        <div class="input-group password-input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Min 6 chars, uppercase, lowercase, number" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('registerPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="invalid-feedback" id="passwordError"></div>
                    </div>
                    
                    <div class="form-group password-group">
                        <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-group password-input-group">
                            <span class="input-group-text" style="border: 2px solid #bdc3c7; background: white;">
                                <i class="bi bi-lock-check"></i>
                            </span>
                            <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" placeholder="Re-enter your password" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('registerConfirmPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="confirmPasswordError"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <button type="button" class="btn-link" data-tab="login">Login here</button></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>
