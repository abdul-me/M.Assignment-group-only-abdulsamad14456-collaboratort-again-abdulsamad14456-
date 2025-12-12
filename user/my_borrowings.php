<?php
/**
 * User - My Borrowings Page
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireUser();

$user_id = getCurrentUserId();

// Get active borrowings
$active_borrowings = getRows($conn, 
    "SELECT b.id, b.title, b.author, br.id as borrowing_id, br.due_date, br.status,
            DATEDIFF(br.due_date, CURDATE()) as days_left
     FROM borrowings br 
     JOIN books b ON br.book_id = b.id 
     WHERE br.user_id = ? AND br.status IN ('borrowed', 'overdue')
     ORDER BY br.due_date ASC", 
    "i", [$user_id]);

// Get returned books
$returned_books = getRows($conn, 
    "SELECT b.id, b.title, b.author, br.return_date
     FROM borrowings br 
     JOIN books b ON br.book_id = b.id 
     WHERE br.user_id = ? AND br.status = 'returned'
     ORDER BY br.return_date DESC
     LIMIT 10", 
    "i", [$user_id]);

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowings - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
        }
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        .borrowing-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
        }
        .borrowing-item.active {
            border-left-color: #27ae60;
        }
        .borrowing-item.overdue {
            border-left-color: #e74c3c;
            background: rgba(231, 76, 60, 0.02);
        }
        .due-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .due-badge.safe {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }
        .due-badge.warning {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }
        .due-badge.danger {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-book-fill"></i> Library
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-book"></i> Browse Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_borrowings.php">
                            <i class="bi bi-bookmark"></i> My Books
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> Menu
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../includes/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <h2 class="mb-4">
            <i class="bi bi-bookmark-check"></i> My Borrowed Books
        </h2>

        <?php if (!empty($active_borrowings)): ?>
            <div class="mb-5">
                <h4 class="mb-3">Active Borrowings</h4>
                <?php foreach ($active_borrowings as $borrow): ?>
                    <div class="borrowing-item <?php echo $borrow['status']; ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-1"><?php echo htmlspecialchars($borrow['title']); ?></h5>
                                <p class="text-muted mb-2">by <?php echo htmlspecialchars($borrow['author']); ?></p>
                                <div class="due-badge <?php 
                                    if ($borrow['status'] === 'overdue') {
                                        echo 'danger';
                                    } elseif ($borrow['days_left'] <= 3) {
                                        echo 'warning';
                                    } else {
                                        echo 'safe';
                                    }
                                ?>">
                                    <i class="bi bi-calendar"></i> 
                                    <?php 
                                    if ($borrow['status'] === 'overdue') {
                                        echo 'OVERDUE - Please return immediately!';
                                    } else {
                                        echo $borrow['days_left'] . ' day(s) left';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button class="btn btn-sm btn-success" onclick="returnBook(<?php echo $borrow['borrowing_id']; ?>)">
                                    <i class="bi bi-check-circle"></i> Return Book
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($returned_books)): ?>
            <div>
                <h4 class="mb-3">Previously Returned</h4>
                <div class="list-group">
                    <?php foreach ($returned_books as $book): ?>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h6>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($book['return_date'])); ?></small>
                            </div>
                            <p class="mb-1 text-muted">by <?php echo htmlspecialchars($book['author']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($active_borrowings) && empty($returned_books)): ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> You haven't borrowed any books yet. 
                <a href="dashboard.php">Browse our collection</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function returnBook(borrowingId) {
            if (!confirm('Are you sure you want to return this book?')) return;
            
            fetch('../api/return_book.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'borrowing_id=' + borrowingId + '&csrf_token=<?php echo $csrf_token; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Book returned successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Error: ' + err));
        }
    </script>
</body>
</html>
