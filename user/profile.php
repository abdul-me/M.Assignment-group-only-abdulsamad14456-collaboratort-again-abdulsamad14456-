<?php
/**
 * User - Profile Management
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

$user_id = getCurrentUserId();
$user = getRow($conn, "SELECT * FROM users WHERE id = ?", "i", [$user_id]);

$message = '';
$message_type = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');
    
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Validate inputs
    $errors = [];
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email) || !isValidEmail($email)) $errors[] = "Valid email is required";
    
    // Check if email is already taken by another user
    $email_check = getRow($conn, "SELECT id FROM users WHERE email = ? AND id != ?", "ii", [$email, $user_id]);
    if ($email_check) $errors[] = "Email already in use";
    
    if (empty($errors)) {
        $result = executeUpdate($conn, 
            "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() 
             WHERE id = ?",
            "ssssi", [$full_name, $email, $phone, $address, $user_id]);
        
        if ($result) {
            $message = "Profile updated successfully!";
            $message_type = "success";
            // Refresh user data
            $user = getRow($conn, "SELECT * FROM users WHERE id = ?", "i", [$user_id]);
        } else {
            $message = "Error updating profile. Please try again.";
            $message_type = "danger";
        }
    } else {
        $message = implode(" ", $errors);
        $message_type = "danger";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verifyCSRFToken($_POST['csrf_token'] ?? '');
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    $errors = [];
    if (!verifyPassword($current_password, $user['password_hash'])) {
        $errors[] = "Current password is incorrect";
    }
    if (!isValidPassword($new_password)) {
        $errors[] = "Password must be at least 6 characters with uppercase, lowercase, and numbers";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $hashed_password = hashPassword($new_password);
        $result = executeUpdate($conn,
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            "si", [$hashed_password, $user_id]);
        
        if ($result) {
            $message = "Password changed successfully!";
            $message_type = "success";
        } else {
            $message = "Error changing password. Please try again.";
            $message_type = "danger";
        }
    } else {
        $message = implode(" ", $errors);
        $message_type = "danger";
    }
}

// Get borrowing statistics
$borrowing_stats = getRow($conn,
    "SELECT 
        (SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'borrowed') as active_count,
        (SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'returned') as returned_count,
        (SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status = 'overdue') as overdue_count,
        (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) as wishlist_count",
    "iiii", [$user_id, $user_id, $user_id, $user_id]);

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-fill" style="font-size: 28px; margin-bottom: 10px; display: block;"></i>
            <h5>LMS User</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-house"></i> Browse Books
            </a>
            <a href="my_borrowings.php" class="nav-link">
                <i class="bi bi-bookmark-check"></i> My Borrowings
            </a>
            <a href="wishlist.php" class="nav-link">
                <i class="bi bi-heart"></i> Wishlist
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4"><i class="bi bi-person-circle"></i> My Profile</h2>
        
        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4">
                <div class="card text-center mb-4">
                    <div class="card-body">
                        <div style="font-size: 64px; margin-bottom: 10px;">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="mb-3">
                            <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                            <span class="badge bg-success">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </p>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Active Loans:</span>
                            <strong><?php echo $borrowing_stats['active_count']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Returned Books:</span>
                            <strong><?php echo $borrowing_stats['returned_count']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Overdue Books:</span>
                            <strong class="text-danger"><?php echo $borrowing_stats['overdue_count']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Wishlist Items:</span>
                            <strong><?php echo $borrowing_stats['wishlist_count']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Forms -->
            <div class="col-md-8">
                <!-- Update Profile -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Update Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-lock"></i> Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted d-block mt-2">
                                    Must be at least 6 characters with uppercase, lowercase, and numbers
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
