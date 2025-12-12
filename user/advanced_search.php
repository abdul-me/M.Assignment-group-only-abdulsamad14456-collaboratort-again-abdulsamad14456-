<?php
/**
 * Advanced Search & Filtering
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireLogin();

$search_query = sanitizeInput($_GET['q'] ?? '');
$category_id = intval($_GET['category'] ?? 0);
$sort_by = sanitizeInput($_GET['sort'] ?? 'title');
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 10000);
$availability = sanitizeInput($_GET['availability'] ?? 'all');

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$sql = "SELECT * FROM books WHERE is_active = 1";
$types = "";
$params = [];

// Search filter
if (!empty($search_query)) {
    $sql .= " AND (MATCH(title, author) AGAINST(? IN BOOLEAN MODE) OR MATCH(title, author) AGAINST(? IN BOOLEAN MODE))";
    $types .= "ss";
    $params[] = "+{$search_query}*";
    $params[] = $search_query;
}

// Category filter
if ($category_id > 0) {
    $sql .= " AND category_id = ?";
    $types .= "i";
    $params[] = $category_id;
}

// Availability filter
if ($availability === 'available') {
    $sql .= " AND total_copies > 0";
} elseif ($availability === 'unavailable') {
    $sql .= " AND total_copies = 0";
}

// Sorting
$sort_map = [
    'title' => 'title ASC',
    'title_desc' => 'title DESC',
    'newest' => 'created_at DESC',
    'oldest' => 'created_at ASC',
];
$sort_order = $sort_map[$sort_by] ?? 'title ASC';
$sql .= " ORDER BY {$sort_order}";

// Get total count for pagination
$count_result = getRow($conn, 
    preg_replace('/SELECT \*/', 'SELECT COUNT(*) as total', $sql),
    $types, $params);
$total_books = $count_result['total'] ?? 0;
$total_pages = ceil($total_books / $per_page);

// Get results
$sql .= " LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $per_page;
$params[] = $offset;

$books = getRows($conn, $sql, $types, $params);

// Get categories for filter
$categories = getRows($conn, "SELECT * FROM categories ORDER BY name");

initSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .search-sidebar {
            position: sticky;
            top: 20px;
        }
        .filter-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .filter-section h6 {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        .form-check-label {
            cursor: pointer;
            margin-bottom: 8px;
        }
        .book-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
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
        <h2 class="mb-4"><i class="bi bi-search"></i> Advanced Search</h2>
        
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="search-sidebar">
                    <!-- Quick Search -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="mb-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="q" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <form method="GET" id="filterForm">
                        <!-- Category Filter -->
                        <div class="filter-section">
                            <h6><i class="bi bi-list-ul"></i> Category</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" id="cat_all" value="0" <?php echo $category_id === 0 ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                                <label class="form-check-label" for="cat_all">All Categories</label>
                            </div>
                            <?php foreach ($categories as $cat): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" id="cat_<?php echo $cat['id']; ?>" value="<?php echo $cat['id']; ?>" <?php echo $category_id === $cat['id'] ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                                    <label class="form-check-label" for="cat_<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Availability Filter -->
                        <div class="filter-section">
                            <h6><i class="bi bi-box-seam"></i> Availability</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" id="avail_all" value="all" <?php echo $availability === 'all' ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                                <label class="form-check-label" for="avail_all">All Books</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" id="avail_available" value="available" <?php echo $availability === 'available' ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                                <label class="form-check-label" for="avail_available">Available Only</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" id="avail_unavailable" value="unavailable" <?php echo $availability === 'unavailable' ? 'checked' : ''; ?> onchange="document.getElementById('filterForm').submit();">
                                <label class="form-check-label" for="avail_unavailable">Unavailable Only</label>
                            </div>
                        </div>
                        
                        <!-- Sort -->
                        <div class="filter-section">
                            <h6><i class="bi bi-arrow-down-up"></i> Sort By</h6>
                            <select class="form-select form-select-sm" name="sort" onchange="document.getElementById('filterForm').submit();">
                                <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                                <option value="title_desc" <?php echo $sort_by === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Results -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5>
                        <span class="badge bg-primary"><?php echo $total_books; ?></span> 
                        Results Found
                    </h5>
                    <small class="text-muted">
                        Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?>
                    </small>
                </div>
                
                <!-- Books Grid -->
                <?php if (!empty($books)): ?>
                    <div class="row g-4 mb-4">
                        <?php foreach ($books as $book): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card book-card h-100">
                                    <div style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="bi bi-book" style="font-size: 64px;"></i>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title"><?php echo htmlspecialchars(substr($book['title'], 0, 30)); ?></h6>
                                        <p class="card-text small text-muted">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars(substr($book['author'], 0, 25)); ?>
                                        </p>
                                        <p class="card-text small">
                                            <span class="badge bg-info">ISBN: <?php echo htmlspecialchars(substr($book['isbn'], 0, 10)); ?></span>
                                        </p>
                                        
                                        <div class="mt-auto">
                                            <?php if ($book['total_copies'] > 0): ?>
                                                <span class="badge bg-success mb-2" style="display: inline-block;">
                                                    <i class="bi bi-check-circle"></i> Available (<?php echo $book['total_copies']; ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger mb-2" style="display: inline-block;">
                                                    <i class="bi bi-x-circle"></i> Out of Stock
                                                </span>
                                            <?php endif; ?>
                                            <br>
                                            <button class="btn btn-sm btn-primary w-100 mt-2" onclick="borrowBook(<?php echo $book['id']; ?>)" <?php echo $book['total_copies'] === 0 ? 'disabled' : ''; ?>>
                                                <i class="bi bi-bookmark"></i> Borrow
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?q=<?php echo urlencode($search_query); ?>&category=<?php echo $category_id; ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-search" style="font-size: 48px;"></i>
                        <p class="mt-3">No books found matching your search criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function borrowBook(bookId) {
            if (confirm('Borrow this book for 14 days?')) {
                fetch('../api/borrow_book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'book_id=' + bookId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Book borrowed successfully! Due date: ' + data.due_date);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => alert('Error borrowing book'));
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
