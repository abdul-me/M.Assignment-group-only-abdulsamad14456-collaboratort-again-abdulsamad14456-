<?php
/**
 * Admin - Manage Categories
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAdmin();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_category') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error = 'Category name is required.';
            } else {
                $existing = getRow($conn, "SELECT id FROM categories WHERE name = ?", "s", [$name]);
                if ($existing) {
                    $error = 'Category already exists.';
                } else {
                    $affected = executeUpdate($conn, 
                        "INSERT INTO categories (name, description) VALUES (?, ?)",
                        "ss", [$name, $description]);
                    
                    if ($affected > 0) {
                        logAction($conn, 'CATEGORY_ADDED', 'categories', getLastInsertId($conn));
                        $message = 'Category added successfully!';
                    } else {
                        $error = 'Failed to add category.';
                    }
                }
            }
        } elseif ($action === 'edit_category') {
            $category_id = intval($_POST['category_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error = 'Category name is required.';
            } else {
                $existing = getRow($conn, "SELECT id FROM categories WHERE name = ? AND id != ?", "si", [$name, $category_id]);
                if ($existing) {
                    $error = 'Category name already exists.';
                } else {
                    $affected = executeUpdate($conn, 
                        "UPDATE categories SET name = ?, description = ? WHERE id = ?",
                        "ssi", [$name, $description, $category_id]);
                    
                    if ($affected >= 0) {
                        logAction($conn, 'CATEGORY_UPDATED', 'categories', $category_id);
                        $message = 'Category updated successfully!';
                    } else {
                        $error = 'Failed to update category.';
                    }
                }
            }
        } elseif ($action === 'delete_category') {
            $category_id = intval($_POST['category_id'] ?? 0);
            
            // Check if category has books
            $book_count = getRow($conn, "SELECT COUNT(*) as count FROM books WHERE category_id = ?", "i", [$category_id]);
            if ($book_count['count'] > 0) {
                $error = 'Cannot delete category that contains books.';
            } else {
                $affected = executeUpdate($conn, "DELETE FROM categories WHERE id = ?", "i", [$category_id]);
                if ($affected > 0) {
                    logAction($conn, 'CATEGORY_DELETED', 'categories', $category_id);
                    $message = 'Category deleted successfully!';
                } else {
                    $error = 'Failed to delete category.';
                }
            }
        }
    }
}

// Get all categories
$categories = getRows($conn, "SELECT * FROM categories ORDER BY name", "", []);

initSession();
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Dashboard</title>
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
            <a href="categories.php" class="nav-link active">
                <i class="bi bi-tag"></i> Categories
            </a>
            <a href="users.php" class="nav-link">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="../includes/logout.php" class="nav-link" style="color: rgba(255, 255, 255, 0.6); margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-tag"></i> Manage Categories</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Add Category
            </button>
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
        
        <!-- Categories Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Books</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php 
                                $book_count = getRow($conn, "SELECT COUNT(*) as count FROM books WHERE category_id = ?", "i", [$cat['id']]);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $book_count['count']; ?></span>
                                    </td>
                                    <td>
                                        <?php echo $cat['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $cat['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No categories found. <button class="btn-link" data-bs-toggle="modal" data-bs-target="#addModal">Add one now</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="add_category">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Modals -->
    <?php foreach ($categories as $cat): ?>
        <div class="modal fade" id="editModal<?php echo $cat['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="edit_category">
                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="name_<?php echo $cat['id']; ?>" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="name_<?php echo $cat['id']; ?>" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="desc_<?php echo $cat['id']; ?>" class="form-label">Description</label>
                                <textarea class="form-control" id="desc_<?php echo $cat['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
