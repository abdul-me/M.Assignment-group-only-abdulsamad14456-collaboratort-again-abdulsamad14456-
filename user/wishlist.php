<?php
/**
 * User - Wishlist Page
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireUser();

$user_id = getCurrentUserId();

// Get wishlist items
$wishlist_books = getRows($conn, 
    "SELECT b.id, b.title, b.author, b.available_copies, c.name as category_name, w.added_at
     FROM wishlist w
     JOIN books b ON w.book_id = b.id
     JOIN categories c ON b.category_id = c.id
     WHERE w.user_id = ?
     ORDER BY w.added_at DESC", 
    "i", [$user_id]);

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Library Management System</title>
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
        .wishlist-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .wishlist-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
                        <a class="nav-link" href="my_borrowings.php">
                            <i class="bi bi-bookmark"></i> My Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="wishlist.php">
                            <i class="bi bi-heart"></i> Wishlist
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <h2 class="mb-4">
            <i class="bi bi-heart"></i> My Wishlist
        </h2>

        <?php if (!empty($wishlist_books)): ?>
            <div class="row g-3">
                <?php foreach ($wishlist_books as $book): ?>
                    <div class="col-md-6">
                        <div class="wishlist-card">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="text-muted mb-2">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <div>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                        <?php if ($book['available_copies'] > 0): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <button class="btn btn-sm btn-primary" onclick="borrowBook(<?php echo $book['id']; ?>)">
                                            <i class="bi bi-download"></i> Borrow
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" onclick="removeFromWishlist(<?php echo $book['id']; ?>)">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> Your wishlist is empty. 
                <a href="dashboard.php">Browse books and add to wishlist</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function borrowBook(bookId) {
            fetch('../api/borrow_book.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'book_id=' + bookId + '&csrf_token=<?php echo $csrf_token; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Book borrowed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Error: ' + err));
        }
        
        function removeFromWishlist(bookId) {
            fetch('../api/wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'book_id=' + bookId + '&action=remove&csrf_token=<?php echo $csrf_token; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Removed from wishlist');
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
