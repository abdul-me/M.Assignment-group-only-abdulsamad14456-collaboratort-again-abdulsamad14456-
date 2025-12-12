<?php
/**
 * Notification System - Check overdue books and generate alerts
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

$user_id = getCurrentUserId();

// Get user's overdue borrowings
$overdue_books = getRows($conn,
    "SELECT br.*, b.title, b.author, 
     DATEDIFF(CURDATE(), br.due_date) as days_overdue 
     FROM borrowings br 
     JOIN books b ON br.book_id = b.id 
     WHERE br.user_id = ? AND br.status = 'borrowed' AND br.due_date < CURDATE()
     ORDER BY br.due_date ASC",
    "i", [$user_id]);

// Get user's due soon borrowings (within 3 days)
$due_soon_books = getRows($conn,
    "SELECT br.*, b.title, b.author,
     DATEDIFF(br.due_date, CURDATE()) as days_remaining
     FROM borrowings br
     JOIN books b ON br.book_id = b.id
     WHERE br.user_id = ? AND br.status = 'borrowed' AND br.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
     ORDER BY br.due_date ASC",
    "i", [$user_id]);

// Get system notifications
$system_alerts = getRows($conn,
    "SELECT DISTINCT 'maintenance' as type, 'System Maintenance' as title, 'The library system will be under maintenance on December 10th from 2-4 PM' as message
     UNION
     SELECT 'update', 'New Features Available', 'Check out our new advanced search feature!'");

initSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .notification-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .alert-overdue {
            border-left: 4px solid #dc3545;
            background-color: #f8d7da;
        }
        .alert-due-soon {
            border-left: 4px solid #ffc107;
            background-color: #fff3cd;
        }
        .notification-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-fill" style="font-size: 28px; margin-bottom: 10px; display: block;"></i>
            <h5>LMS</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-house"></i> Browse
            </a>
            <a href="my_borrowings.php" class="nav-link">
                <i class="bi bi-bookmark-check"></i> Borrowings
            </a>
            <a href="wishlist.php" class="nav-link">
                <i class="bi bi-heart"></i> Wishlist
            </a>
            <a href="profile.php" class="nav-link">
                <i class="bi bi-person-circle"></i> Profile
            </a>
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <div class="main-content">
        <h2 class="mb-4">
            <i class="bi bi-bell"></i> Notifications
            <?php if (!empty($overdue_books) || !empty($due_soon_books)): ?>
                <span class="badge bg-danger notification-badge">
                    <?php echo count($overdue_books) + count($due_soon_books); ?>
                </span>
            <?php endif; ?>
        </h2>
        
        <!-- Overdue Books Alert -->
        <?php if (!empty($overdue_books)): ?>
            <div class="alert alert-overdue alert-dismissible fade show">
                <h5><i class="bi bi-exclamation-triangle"></i> Overdue Books</h5>
                <p class="mb-3">You have <?php echo count($overdue_books); ?> book(s) that are overdue. Please return them as soon as possible.</p>
                
                <?php foreach ($overdue_books as $book): ?>
                    <div class="card mb-2 border-danger">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                    <p class="text-muted small mb-0">By <?php echo htmlspecialchars($book['author']); ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-danger">
                                        <?php echo $book['days_overdue']; ?> day(s) overdue
                                    </span>
                                    <br>
                                    <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($book['due_date'])); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <a href="my_borrowings.php" class="btn btn-sm btn-danger">
                    <i class="bi bi-arrow-right"></i> Go to Borrowings
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Due Soon Alert -->
        <?php if (!empty($due_soon_books)): ?>
            <div class="alert alert-due-soon alert-dismissible fade show">
                <h5><i class="bi bi-info-circle"></i> Due Soon</h5>
                <p class="mb-3">The following book(s) are due within the next 3 days:</p>
                
                <?php foreach ($due_soon_books as $book): ?>
                    <div class="card mb-2 border-warning">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                    <p class="text-muted small mb-0">By <?php echo htmlspecialchars($book['author']); ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-warning text-dark">
                                        <?php echo $book['days_remaining']; ?> day(s) remaining
                                    </span>
                                    <br>
                                    <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($book['due_date'])); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- System Notifications -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-megaphone"></i> System Notifications</h5>
            </div>
            <div class="card-body">
                <div class="notification-item" style="border-left-color: #0d6efd;">
                    <h6 class="mb-2"><i class="bi bi-star-fill" style="color: #0d6efd;"></i> Advanced Search Feature</h6>
                    <p class="mb-2">Discover our new advanced search page with powerful filtering options!</p>
                    <a href="advanced_search.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-search"></i> Try Now
                    </a>
                </div>
                
                <div class="notification-item" style="border-left-color: #28a745;">
                    <h6 class="mb-2"><i class="bi bi-check-circle" style="color: #28a745;"></i> Profile Management</h6>
                    <p class="mb-2">Update your profile information and manage your account settings.</p>
                    <a href="profile.php" class="btn btn-sm btn-success">
                        <i class="bi bi-person-circle"></i> Manage Profile
                    </a>
                </div>
                
                <div class="notification-item" style="border-left-color: #17a2b8;">
                    <h6 class="mb-2"><i class="bi bi-clock-history" style="color: #17a2b8;"></i> Borrowing Policy</h6>
                    <p class="mb-0">All books have a standard 14-day borrowing period. Late fees may apply for overdue items.</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-danger"><?php echo count($overdue_books); ?></h3>
                        <small class="text-muted">Overdue Books</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?php echo count($due_soon_books); ?></h3>
                        <small class="text-muted">Due Soon</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?php echo getRow($conn, "SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'", "i", [getCurrentUserId()])['count']; ?></h3>
                        <small class="text-muted">Active Loans</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo getRow($conn, "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?", "i", [getCurrentUserId()])['count']; ?></h3>
                        <small class="text-muted">Wishlist Items</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
