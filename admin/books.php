<?php
/**
 * Admin - Manage Books
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';
$book = null;
$categories = getRows($conn, "SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name", "", []);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['action'] ?? 'list';
        
        if ($action === 'add_book' || $action === 'edit_book') {
            $title = sanitizeInput($_POST['title'] ?? '');
            $author = sanitizeInput($_POST['author'] ?? '');
            $isbn = sanitizeInput($_POST['isbn'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $description = sanitizeInput($_POST['description'] ?? '');
            $publisher = sanitizeInput($_POST['publisher'] ?? '');
            $publication_date = $_POST['publication_date'] ?? '';
            $pages = intval($_POST['pages'] ?? 0);
            $total_copies = intval($_POST['total_copies'] ?? 1);
            
            if (empty($title) || empty($author) || empty($isbn) || $category_id <= 0) {
                $error = 'Title, author, ISBN, and category are required.';
            } else {
                if ($action === 'add_book') {
                    // Check if ISBN already exists
                    $existing = getRow($conn, "SELECT id FROM books WHERE isbn = ?", "s", [$isbn]);
                    if ($existing) {
                        $error = 'ISBN already exists.';
                    } else {
                        $affected = executeUpdate(
                            $conn,
                            "INSERT INTO books (title, author, isbn, category_id, description, publisher, publication_date, pages, total_copies, available_copies) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            "sssisssiii",
                            [$title, $author, $isbn, $category_id, $description, $publisher, $publication_date, $pages, $total_copies, $total_copies]
                        );
                        
                        if ($affected > 0) {
                            $book_id = getLastInsertId($conn);
                            logAction($conn, 'BOOK_ADDED', 'books', $book_id);
                            $message = 'Book added successfully!';
                            $action = 'list';
                        } else {
                            $error = 'Failed to add book.';
                        }
                    }
                } else {
                    $book_id = intval($_POST['book_id'] ?? 0);
                    $old_book = getRow($conn, "SELECT * FROM books WHERE id = ?", "i", [$book_id]);
                    
                    // Check ISBN uniqueness (excluding current book)
                    $existing = getRow($conn, "SELECT id FROM books WHERE isbn = ? AND id != ?", "si", [$isbn, $book_id]);
                    if ($existing) {
                        $error = 'ISBN already exists.';
                    } else {
                        $affected = executeUpdate(
                            $conn,
                            "UPDATE books SET title = ?, author = ?, isbn = ?, category_id = ?, description = ?, 
                             publisher = ?, publication_date = ?, pages = ?, total_copies = ? WHERE id = ?",
                            "sssisssiii",
                            [$title, $author, $isbn, $category_id, $description, $publisher, $publication_date, $pages, $total_copies, $book_id]
                        );
                        
                        if ($affected >= 0) {
                            logAction($conn, 'BOOK_UPDATED', 'books', $book_id, $old_book);
                            $message = 'Book updated successfully!';
                            $action = 'list';
                        } else {
                            $error = 'Failed to update book.';
                        }
                    }
                }
            }
        } elseif ($action === 'delete_book') {
            $book_id = intval($_POST['book_id'] ?? 0);
            $affected = executeUpdate($conn, "DELETE FROM books WHERE id = ?", "i", [$book_id]);
            
            if ($affected > 0) {
                logAction($conn, 'BOOK_DELETED', 'books', $book_id);
                $message = 'Book deleted successfully!';
                $action = 'list';
            } else {
                $error = 'Failed to delete book.';
            }
        }
    }
}

// Get book for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $book_id = intval($_GET['id']);
    $book = getRow($conn, "SELECT * FROM books WHERE id = ?", "i", [$book_id]);
    if (!$book) {
        $error = 'Book not found.';
        $action = 'list';
    }
}

// Get all books
$books = [];
if ($action === 'list') {
    $books = getRows($conn, 
        "SELECT b.*, c.name as category_name FROM books b 
         JOIN categories c ON b.category_id = c.id 
         ORDER BY b.created_at DESC", "", []);
}

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
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
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        .nav-link.active,
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #f39c12;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar (same as dashboard) -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-book-fill" style="font-size: 28px; margin-bottom: 10px; display: block;"></i>
            <h5>LMS Admin</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="books.php" class="nav-link active">
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
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-book"></i> Manage Books</h2>
            <a href="books.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Book
            </a>
        </div>
        
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
        
        <?php if ($action === 'list'): ?>
            <!-- Books List -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ISBN</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($books)): ?>
                                <?php foreach ($books as $b): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($b['isbn']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($b['author']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($b['category_name']); ?></span>
                                        </td>
                                        <td><?php echo $b['total_copies']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $b['available_copies'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $b['available_copies']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $b['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?>
                                        </td>
                                        <td>
                                            <a href="books.php?action=edit&id=<?php echo $b['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $b['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No books found. <a href="books.php?action=add">Add one now</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-<?php echo $action === 'add' ? 'plus-circle' : 'pencil'; ?>"></i>
                        <?php echo $action === 'add' ? 'Add New Book' : 'Edit Book'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'add_book' : 'edit_book'; ?>">
                        <?php if ($action === 'edit' && $book): ?>
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="author" class="form-label">Author *</label>
                                    <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN *</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($book['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" class="form-control" id="publisher" name="publisher" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="publication_date" class="form-label">Publication Date</label>
                                    <input type="date" class="form-control" id="publication_date" name="publication_date" value="<?php echo htmlspecialchars($book['publication_date'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pages" class="form-label">Pages</label>
                                    <input type="number" class="form-control" id="pages" name="pages" value="<?php echo htmlspecialchars($book['pages'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_copies" class="form-label">Total Copies *</label>
                            <input type="number" class="form-control" id="total_copies" name="total_copies" value="<?php echo htmlspecialchars($book['total_copies'] ?? 1); ?>" min="1" required>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> <?php echo $action === 'add' ? 'Add Book' : 'Update Book'; ?>
                            </button>
                            <a href="books.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Delete Book</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this book? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="delete_book">
                        <input type="hidden" id="deleteId" name="book_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set delete ID when modal is shown
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
            document.getElementById('deleteId').value = e.relatedTarget.dataset.id;
        });
    </script>
</body>
</html>
