<?php
/**
 * Admin - Manage Borrowings
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'active';

// Handle return confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['action'] ?? '';
        $borrowing_id = intval($_POST['borrowing_id'] ?? 0);
        
        if ($action === 'confirm_return') {
            $borrowing = getRow($conn, "SELECT id, book_id FROM borrowings WHERE id = ?", "i", [$borrowing_id]);
            
            if ($borrowing) {
                $affected = executeUpdate($conn, 
                    "UPDATE borrowings SET status = 'returned', return_date = NOW() WHERE id = ?",
                    "i", [$borrowing_id]);
                
                if ($affected > 0) {
                    // Update book availability
                    executeUpdate($conn, 
                        "UPDATE books SET available_copies = available_copies + 1 WHERE id = ?",
                        "i", [$borrowing['book_id']]);
                    
                    logAction($conn, 'BORROWING_CONFIRMED_RETURN', 'borrowings', $borrowing_id);
                    $message = 'Return confirmed!';
                }
            } else {
                $error = 'Borrowing record not found.';
            }
        }
    }
}

// Get borrowings based on tab
if ($tab === 'overdue') {
    $borrowings = getRows($conn, 
        "SELECT b.id, b.title, u.full_name, u.email, br.due_date, br.borrow_date, br.status,
                DATEDIFF(CURDATE(), br.due_date) as days_overdue
         FROM borrowings br
         JOIN books b ON br.book_id = b.id
         JOIN users u ON br.user_id = u.id
         WHERE br.status = 'overdue' OR (br.status = 'borrowed' AND br.due_date < CURDATE())
         ORDER BY br.due_date ASC", "", []);
} elseif ($tab === 'returned') {
    $borrowings = getRows($conn, 
        "SELECT b.id, b.title, u.full_name, br.due_date, br.return_date
         FROM borrowings br
         JOIN books b ON br.book_id = b.id
         JOIN users u ON br.user_id = u.id
         WHERE br.status = 'returned'
         ORDER BY br.return_date DESC
         LIMIT 50", "", []);
} else { // active
    $borrowings = getRows($conn, 
        "SELECT b.id, b.title, u.full_name, u.email, br.id as borrowing_id, br.due_date, br.borrow_date, br.status,
                DATEDIFF(br.due_date, CURDATE()) as days_left
         FROM borrowings br
         JOIN books b ON br.book_id = b.id
         JOIN users u ON br.user_id = u.id
         WHERE br.status = 'borrowed'
         ORDER BY br.due_date ASC", "", []);
}

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrowings - Admin Dashboard</title>
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
            <h5>LMS Admin</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="books.php" class="nav-link">
                <i class="bi bi-book"></i> Books
            </a>
            <a href="borrowings.php" class="nav-link active">
                <i class="bi bi-arrow-left-right"></i> Borrowings
            </a>
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4"><i class="bi bi-arrow-left-right"></i> Manage Borrowings</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'active' ? 'active' : ''; ?>" href="borrowings.php?tab=active">
                    <i class="bi bi-bookmark-check"></i> Active Borrowings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'overdue' ? 'active' : ''; ?>" href="borrowings.php?tab=overdue">
                    <i class="bi bi-exclamation-circle"></i> Overdue
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'returned' ? 'active' : ''; ?>" href="borrowings.php?tab=returned">
                    <i class="bi bi-check-circle"></i> Returned
                </a>
            </li>
        </ul>
        
        <!-- Borrowings Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Book</th>
                            <th>User</th>
                            <th>Borrow Date</th>
                            <th><?php echo $tab === 'returned' ? 'Return Date' : 'Due Date'; ?></th>
                            <?php if ($tab !== 'returned'): ?>
                                <th>Days <?php echo $tab === 'overdue' ? 'Overdue' : 'Left'; ?></th>
                            <?php endif; ?>
                            <?php if ($tab === 'active'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($borrowings)): ?>
                            <?php foreach ($borrowings as $borrow): ?>
                                <tr class="<?php echo ($tab === 'overdue' || ($tab === 'active' && isset($borrow['days_left']) && $borrow['days_left'] < 0)) ? 'table-danger' : ''; ?>">
                                    <td><strong><?php echo htmlspecialchars($borrow['title']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($borrow['full_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($borrow['email'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($borrow['borrow_date'] ?? $borrow['due_date'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($borrow['due_date'] ?? $borrow['return_date'])); ?></small>
                                    </td>
                                    <?php if ($tab !== 'returned'): ?>
                                        <td>
                                            <?php 
                                            if (isset($borrow['days_overdue'])) {
                                                echo '<span class="badge bg-danger">' . $borrow['days_overdue'] . ' days</span>';
                                            } elseif (isset($borrow['days_left'])) {
                                                echo '<span class="badge ' . ($borrow['days_left'] <= 3 ? 'bg-warning' : 'bg-success') . '">' . $borrow['days_left'] . ' days</span>';
                                            }
                                            ?>
                                        </td>
                                    <?php endif; ?>
                                    <?php if ($tab === 'active'): ?>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                <input type="hidden" name="action" value="confirm_return">
                                                <input type="hidden" name="borrowing_id" value="<?php echo $borrow['borrowing_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Confirm Return
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $tab === 'returned' ? 4 : ($tab === 'active' ? 6 : 5); ?>" class="text-center text-muted py-4">
                                    No records found.
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
