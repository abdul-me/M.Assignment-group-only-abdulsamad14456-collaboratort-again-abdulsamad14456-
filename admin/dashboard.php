<?php
/**
 * Admin Dashboard
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$active_page = 'dashboard';
$total_books = 0;
$available_books = 0;
$total_users = 0;
$active_users = 0;
$recent_activity = [];

// Get statistics
$stats = getRow($conn, "SELECT COUNT(*) as total_books, SUM(available_copies) as available FROM books");
if ($stats) {
    $total_books = $stats['total_books'] ?? 0;
    $available_books = $stats['available'] ?? 0;
}

$user_stats = getRow($conn, "SELECT COUNT(*) as total_users, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users FROM users WHERE role = 'user'");
if ($user_stats) {
    $total_users = $user_stats['total_users'] ?? 0;
    $active_users = $user_stats['active_users'] ?? 0;
}

// Get recent activity
$recent_activity = getRows($conn, 
    "SELECT a.*, u.full_name FROM audit_logs a 
     JOIN users u ON a.admin_id = u.id 
     ORDER BY a.created_at DESC LIMIT 10", "", []);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --accent-color: #f39c12;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
            --dark-bg: #2c3e50;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h5 {
            margin: 0;
            font-weight: 700;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            padding: 0;
            margin: 5px 0;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            text-decoration: none;
            display: block;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--accent-color);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .topbar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .user-menu {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card.books {
            border-left-color: var(--primary-color);
        }

        .stat-card.users {
            border-left-color: var(--success-color);
        }

        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .stat-card-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }

        .stat-card-icon {
            font-size: 40px;
            opacity: 0.1;
            position: absolute;
            right: 20px;
            top: 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 60, 114, 0.4);
            color: white;
        }

        .table-responsive {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #f5f7fa;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .badge-info {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .page-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .topbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-fill" style="font-size: 28px; margin-bottom: 10px; display: block;"></i>
            <h5>LMS Admin</h5>
            <small><?php echo htmlspecialchars(getCurrentUser()['full_name']); ?></small>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="books.php" class="nav-link">
                <i class="bi bi-book"></i> Books
            </a>
            <a href="categories.php" class="nav-link">
                <i class="bi bi-tag"></i> Categories
            </a>
            <a href="users.php" class="nav-link">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="borrowings.php" class="nav-link">
                <i class="bi bi-arrow-left-right"></i> Borrowings
            </a>
            <a href="reports.php" class="nav-link">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h2 class="topbar-title">
                <i class="bi bi-speedometer2"></i> Dashboard
            </h2>
            <div class="user-menu">
                <span class="text-muted">
                    <i class="bi bi-calendar3"></i> <?php echo date('l, M d, Y'); ?>
                </span>
                <a href="../includes/logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card books">
                <i class="bi bi-book-fill stat-card-icon"></i>
                <div class="stat-card-label">Total Books</div>
                <div class="stat-card-value"><?php echo $total_books; ?></div>
                <small class="text-muted"><?php echo $available_books; ?> available</small>
            </div>
            
            <div class="stat-card users">
                <i class="bi bi-people-fill stat-card-icon"></i>
                <div class="stat-card-label">Total Users</div>
                <div class="stat-card-value"><?php echo $total_users; ?></div>
                <small class="text-muted"><?php echo $active_users; ?> active</small>
            </div>
            
            <div class="stat-card" style="border-left-color: #3498db;">
                <i class="bi bi-arrow-left-right stat-card-icon"></i>
                <div class="stat-card-label">Current Borrowings</div>
                <div class="stat-card-value">
                    <?php 
                    $borrowing_stats = getRow($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'");
                    echo $borrowing_stats['count'] ?? 0;
                    ?>
                </div>
                <small class="text-muted">Active loans</small>
            </div>
            
            <div class="stat-card" style="border-left-color: var(--danger-color);">
                <i class="bi bi-exclamation-circle stat-card-icon"></i>
                <div class="stat-card-label">Overdue Books</div>
                <div class="stat-card-value">
                    <?php 
                    $overdue_stats = getRow($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
                    echo $overdue_stats['count'] ?? 0;
                    ?>
                </div>
                <small class="text-muted">Action needed</small>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="page-section">
            <h3 class="section-title">
                <i class="bi bi-lightning"></i> Quick Actions
            </h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="books.php?action=add" class="action-button">
                    <i class="bi bi-plus-circle"></i> Add New Book
                </a>
                <a href="categories.php?action=add" class="action-button">
                    <i class="bi bi-plus-circle"></i> Add Category
                </a>
                <a href="borrowings.php" class="action-button">
                    <i class="bi bi-arrow-left-right"></i> Manage Borrowings
                </a>
                <a href="reports.php" class="action-button">
                    <i class="bi bi-download"></i> Generate Reports
                </a>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="page-section">
            <h3 class="section-title">
                <i class="bi bi-clock-history"></i> Recent Activity
            </h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_activity)): ?>
                            <?php foreach ($recent_activity as $log): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php 
                                            $entity = $log['entity_type'] ? htmlspecialchars($log['entity_type']) . ' #' . $log['entity_id'] : 'N/A';
                                            echo $entity;
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['ip_address']); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No activity yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
