<?php
/**
 * Admin - Reports & Analytics
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$report_type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Overview Statistics
$overview = [
    'total_books' => getRow($conn, "SELECT COUNT(*) as count FROM books")['count'] ?? 0,
    'total_users' => getRow($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'] ?? 0,
    'active_borrowings' => getRow($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'")['count'] ?? 0,
    'overdue_borrowings' => getRow($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue' OR (status = 'borrowed' AND due_date < CURDATE())")['count'] ?? 0,
];

// Borrowing statistics for period
$borrowing_stats = getRows($conn, 
    "SELECT DATE(borrow_date) as date, COUNT(*) as count 
     FROM borrowings 
     WHERE borrow_date BETWEEN ? AND ? 
     GROUP BY DATE(borrow_date) 
     ORDER BY date", 
    "ss", [$start_date, $end_date]);

// Top borrowed books
$top_books = getRows($conn, 
    "SELECT b.title, b.author, COUNT(*) as borrow_count 
     FROM borrowings br 
     JOIN books b ON br.book_id = b.id 
     WHERE br.borrow_date BETWEEN ? AND ? 
     GROUP BY br.book_id 
     ORDER BY borrow_count DESC 
     LIMIT 10", 
    "ss", [$start_date, $end_date]);

// Most active users
$active_users = getRows($conn, 
    "SELECT u.full_name, u.email, COUNT(*) as borrow_count 
     FROM borrowings br 
     JOIN users u ON br.user_id = u.id 
     WHERE br.borrow_date BETWEEN ? AND ? 
     GROUP BY br.user_id 
     ORDER BY borrow_count DESC 
     LIMIT 10", 
    "ss", [$start_date, $end_date]);

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-fill" style="font-size: 28px; margin-bottom: 10px; display: block;"></i>
            <h5>LMS Admin</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="books.php" class="nav-link">
                <i class="bi bi-book"></i> Books
            </a>
            <a href="reports.php" class="nav-link active">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4"><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
        
        <!-- Date Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-6" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-book"></i></h5>
                        <h3><?php echo $overview['total_books']; ?></h3>
                        <small class="text-muted">Total Books</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people"></i></h5>
                        <h3><?php echo $overview['total_users']; ?></h3>
                        <small class="text-muted">Total Users</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-bookmark-check"></i></h5>
                        <h3><?php echo $overview['active_borrowings']; ?></h3>
                        <small class="text-muted">Active Loans</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-danger bg-opacity-10">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-exclamation-circle"></i></h5>
                        <h3><?php echo $overview['overdue_borrowings']; ?></h3>
                        <small class="text-muted">Overdue Books</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Books -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-fire"></i> Top 10 Most Borrowed Books</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Book Title</th>
                                    <th class="text-center">Borrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_books)): ?>
                                    <?php foreach ($top_books as $book): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars(substr($book['title'], 0, 25)); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($book['author']); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?php echo $book['borrow_count']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">No data for selected period</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Active Users -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Most Active Users</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th class="text-center">Borrows</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($active_users)): ?>
                                    <?php foreach ($active_users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo $user['borrow_count']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">No data for selected period</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
