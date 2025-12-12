<?php
/**
 * API - Wishlist Management
 * POST /api/wishlist.php
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

requireUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (empty($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token invalid']);
    exit;
}

$action = $_POST['action'] ?? 'add';
$book_id = intval($_POST['book_id'] ?? 0);
$user_id = getCurrentUserId();

if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check if book exists
$book = getRow($conn, "SELECT id FROM books WHERE id = ?", "i", [$book_id]);
if (!$book) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

if ($action === 'add') {
    // Check if already in wishlist
    $existing = getRow($conn, 
        "SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?", 
        "ii", [$user_id, $book_id]);
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Book already in wishlist']);
        exit;
    }
    
    $affected = executeUpdate($conn, 
        "INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)", 
        "ii", [$user_id, $book_id]);
    
    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
    }
    
} elseif ($action === 'remove') {
    $affected = executeUpdate($conn, 
        "DELETE FROM wishlist WHERE user_id = ? AND book_id = ?", 
        "ii", [$user_id, $book_id]);
    
    if ($affected > 0) {
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
