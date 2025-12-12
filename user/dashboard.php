<?php
/**
 * User Dashboard - Browse and Borrow Books
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireUser();

$search = sanitizeInput($_GET['search'] ?? '');
$category_filter = intval($_GET['category'] ?? 0);
$sort = $_GET['sort'] ?? 'recent';
$page = intval($_GET['page'] ?? 1);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get categories for filter
$categories = getRows($conn, "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name", "", []);

// Build query for books
$query = "SELECT b.*, c.name as category_name FROM books b 
          JOIN categories c ON b.category_id = c.id 
          WHERE b.is_active = 1 AND b.available_copies > 0";

$types = "";
$params = [];

if (!empty($search)) {
    $query .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term];
    $types = "ss";
}

if ($category_filter > 0) {
    $query .= " AND b.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

// Add sorting
if ($sort === 'popular') {
    $query .= " ORDER BY b.total_copies DESC";
} elseif ($sort === 'title') {
    $query .= " ORDER BY b.title ASC";
} else {
    $query .= " ORDER BY b.created_at DESC";
}

// Get total count
$count_query = str_replace("SELECT b.*, c.name as category_name", "SELECT COUNT(*) as total", $query);
$count_result = getRow($conn, $count_query, $types, $params);
$total_books = $count_result['total'] ?? 0;
$total_pages = ceil($total_books / $per_page);

// Get books for current page
$query .= " LIMIT $offset, $per_page";
$books = getRows($conn, $query, $types, $params);

// Get user's borrowings
$user_id = getCurrentUserId();
$borrowings = getRows($conn, 
    "SELECT b.id, b.title, br.due_date, br.status FROM borrowings br 
     JOIN books b ON br.book_id = b.id 
     WHERE br.user_id = ? AND br.status IN ('borrowed', 'overdue') 
     ORDER BY br.due_date ASC", 
    "i", [$user_id]);

// Get wishlist count
$wishlist_count = getRow($conn, 
    "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?", 
    "i", [$user_id]);

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --accent-color: #f39c12;
        }

        body {
            background-color: #f5f7fa;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
        }

        .navbar-brand i {
            margin-right: 8px;
        }

        .badge-notification {
            position: absolute;
            top: -5px;
            right: -10px;
            background: var(--accent-color);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: white;
        }

        .sidebar-filters {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .filter-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 16px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .filter-option input[type="radio"],
        .filter-option input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .book-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .book-cover {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
        }

        .book-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .book-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 14px;
            line-height: 1.3;
        }

        .book-author {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .book-category {
            font-size: 11px;
            margin-bottom: 10px;
        }

        .book-available {
            font-size: 12px;
            color: #27ae60;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .book-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .book-actions button {
            flex: 1;
            padding: 8px 10px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-borrow {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .btn-borrow:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
        }

        .btn-wishlist {
            background: #ecf0f1;
            color: var(--accent-color);
        }

        .btn-wishlist:hover {
            background: var(--accent-color);
            color: white;
        }

        .borrowing-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
        }

        .borrowing-card.overdue {
            border-left-color: #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .borrowing-card.active {
            border-left-color: #27ae60;
        }

        .pagination-custom {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }

        .pagination-custom a,
        .pagination-custom span {
            padding: 8px 12px;
            border-radius: 6px;
            background: white;
            border: 1px solid #bdc3c7;
            text-decoration: none;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .pagination-custom a:hover {
            background: var(--primary-color);
            color: white;
        }

        .pagination-custom .active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .sidebar-filters {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-book-fill"></i> Library
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="advanced_search.php">
                            <i class="bi bi-search"></i> Advanced Search
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_borrowings.php">
                            <i class="bi bi-bookmark"></i> My Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="wishlist.php">
                            <i class="bi bi-heart"></i> Wishlist
                            <?php if (($wishlist_count['count'] ?? 0) > 0): ?>
                                <span class="badge-notification"><?php echo $wishlist_count['count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notifications.php">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars(getCurrentUser()['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../includes/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid my-4">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="sidebar-filters">
                    <div class="filter-title">Filters</div>
                    
                    <!-- Search -->
                    <form method="GET" class="search-box">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search books..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <div class="filter-title" style="font-size: 14px;">Category</div>
                        <div class="filter-option">
                            <input type="radio" id="cat_all" name="category" value="0" <?php echo $category_filter == 0 ? 'checked' : ''; ?> onchange="applyFilter()">
                            <label for="cat_all">All Categories</label>
                        </div>
                        <?php foreach ($categories as $cat): ?>
                            <div class="filter-option">
                                <input type="radio" id="cat_<?php echo $cat['id']; ?>" name="category" value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'checked' : ''; ?> onchange="applyFilter()">
                                <label for="cat_<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Sort -->
                    <div class="filter-group">
                        <div class="filter-title" style="font-size: 14px;">Sort By</div>
                        <select class="form-select form-select-sm" name="sort" onchange="applyFilter()">
                            <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Recently Added</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        </select>
                    </div>
                </div>

                <!-- Current Borrowings -->
                <?php if (!empty($borrowings)): ?>
                    <div class="sidebar-filters">
                        <div class="filter-title">Your Books</div>
                        <?php foreach ($borrowings as $borrow): ?>
                            <div class="borrowing-card <?php echo $borrow['status'] === 'overdue' ? 'overdue' : 'active'; ?>">
                                <strong><?php echo htmlspecialchars($borrow['title']); ?></strong>
                                <div style="font-size: 12px; color: #7f8c8d; margin-top: 8px;">
                                    <i class="bi bi-calendar"></i> 
                                    Due: <?php echo date('M d, Y', strtotime($borrow['due_date'])); ?>
                                    <?php if ($borrow['status'] === 'overdue'): ?>
                                        <span class="badge bg-danger ms-2">Overdue</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Books Grid -->
            <div class="col-lg-9">
                <!-- Page Title -->
                <div class="mb-4">
                    <h2 class="mb-1">
                        <i class="bi bi-book"></i> Browse Books
                    </h2>
                    <p class="text-muted mb-0">
                        <?php if (!empty($search)): ?>
                            Search results for "<?php echo htmlspecialchars($search); ?>"
                            (<?php echo $total_books; ?> books found)
                        <?php elseif ($category_filter > 0): ?>
                            Category: <?php echo htmlspecialchars($categories[array_search($category_filter, array_column($categories, 'id'))]['name'] ?? ''); ?>
                            (<?php echo $total_books; ?> books)
                        <?php else: ?>
                            Showing <?php echo $total_books; ?> available books
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (!empty($books)): ?>
                    <!-- Books Grid -->
                    <div class="row g-4 mb-4">
                        <?php foreach ($books as $book): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="book-card">
                                    <div class="book-cover">
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <div class="book-info">
                                        <div class="book-title" title="<?php echo htmlspecialchars($book['title']); ?>">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </div>
                                        <div class="book-author">
                                            by <?php echo htmlspecialchars($book['author']); ?>
                                        </div>
                                        <div class="book-category">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                        </div>
                                        <div class="book-available">
                                            <i class="bi bi-check-circle"></i> <?php echo $book['available_copies']; ?> available
                                        </div>
                                        <div class="book-actions">
                                            <button class="btn-borrow" onclick="borrowBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                                <i class="bi bi-download"></i> Borrow
                                            </button>
                                            <button class="btn-wishlist" onclick="addToWishlist(<?php echo $book['id']; ?>)">
                                                <i class="bi bi-heart"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-custom">
                            <?php if ($page > 1): ?>
                                <a href="dashboard.php?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort) ? '&sort=' . $sort : ''; ?>">First</a>
                                <a href="dashboard.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort) ? '&sort=' . $sort : ''; ?>">Previous</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="dashboard.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort) ? '&sort=' . $sort : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="dashboard.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort) ? '&sort=' . $sort : ''; ?>">Next</a>
                                <a href="dashboard.php?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($sort) ? '&sort=' . $sort : ''; ?>">Last</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i> No books found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilter() {
            const category = document.querySelector('input[name="category"]:checked').value;
            const sort = document.querySelector('select[name="sort"]').value;
            const search = new URLSearchParams(window.location.search).get('search') || '';
            
            let url = 'dashboard.php?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (category > 0) url += 'category=' + category + '&';
            url += 'sort=' + sort;
            
            window.location.href = url;
        }

        function borrowBook(bookId, title) {
            fetch('../api/borrow_book.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'book_id=' + bookId + '&csrf_token=<?php echo $csrf_token; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Book borrowed successfully! Due date: ' + data.due_date);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Error: ' + err));
        }

        function addToWishlist(bookId) {
            fetch('../api/wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'book_id=' + bookId + '&action=add&csrf_token=<?php echo $csrf_token; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
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
