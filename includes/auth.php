<?php
/**
 * Session Management & Authentication Utilities
 */

// Session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.http_only', 1);
ini_set('session.secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_samesite', 'Strict');

/**
 * Initialize secure session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    initSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    initSession();
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is regular user
 */
function isUser() {
    initSession();
    return isLoggedIn() && $_SESSION['role'] === 'user';
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    initSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    initSession();
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    initSession();
    return $_SESSION['user_data'] ?? null;
}

/**
 * Set user session after login
 */
function setUserSession($user_id, $username, $email, $role, $full_name) {
    initSession();
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['user_data'] = [
        'id' => $user_id,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'full_name' => $full_name
    ];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Destroy session on logout
 */
function destroySession() {
    initSession();
    session_destroy();
    // Redirect to the application's root index page. Use script path to
    // construct a URL that points to the top-level `index.php` so that
    // scripts in subdirectories (e.g., `includes/`, `admin/`) correctly
    // return to the main login page.
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/';
    $scriptDir = dirname($scriptPath);
    $redirect = $scriptDir . '/../index.php';
    header('Location: ' . $redirect);
    exit;
}

/**
 * Check session timeout (30 minutes)
 */
function checkSessionTimeout($timeout = 1800) {
    initSession();
    
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $timeout) {
            destroySession();
        }
    }
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit;
    }
    checkSessionTimeout();
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit;
    }
}

/**
 * Require user access (non-admin)
 */
function requireUser() {
    requireLogin();
    if (!isUser()) {
        header("Location: ../index.php");
        exit;
    }
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate password strength
 */
function isValidPassword($password) {
    // Minimum 6 characters, at least one uppercase, one lowercase, one number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $password);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    initSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log user action (audit trail)
 */
function logAction($conn, $action, $entity_type = null, $entity_id = null, $old_values = null, $new_values = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $admin_id = getCurrentUserId();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query = "INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $old_json = $old_values ? json_encode($old_values) : null;
    $new_json = $new_values ? json_encode($new_values) : null;
    
    $types = "issiisss";
    $params = [$admin_id, $action, $entity_type, $entity_id, $old_json, $new_json, $ip_address, $user_agent];

    // Ensure database helper functions are available. Many entry scripts include
    // the project's `config/database.php` (which defines `executeUpdate`). If
    // those helpers aren't present, attempt to include the config file from the
    // project config directory. This avoids requiring a non-existent
    // `includes/database.php` file which caused fatal errors.
    if (!function_exists('executeUpdate')) {
        $possible = __DIR__ . '/../config/database.php';
        if (file_exists($possible)) {
            require_once $possible;
        }
    }

    if (!function_exists('executeUpdate')) {
        // Can't log without DB helpers; fail gracefully.
        return false;
    }

    return executeUpdate($conn, $query, $types, $params) > 0;
}
?>
