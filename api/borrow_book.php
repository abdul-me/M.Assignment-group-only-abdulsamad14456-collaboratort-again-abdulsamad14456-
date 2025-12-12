<?php
/**
 * API - Borrow Book
 * POST /api/borrow_book.php
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

$book_id = intval($_POST['book_id'] ?? 0);
$user_id = getCurrentUserId();

if ($book_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check if book exists and has available copies
$book = getRow($conn, 
    "SELECT id, title, available_copies FROM books WHERE id = ? AND available_copies > 0", 
    "i", [$book_id]);

if (!$book) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Book not available']);
    exit;
}

// Check if user already has this book borrowed
$existing = getRow($conn, 
    "SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue')", 
    "ii", [$user_id, $book_id]);

if ($existing) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You already have this book borrowed']);
    exit;
}

// Create borrowing record (14 days due date)
$due_date = date('Y-m-d', strtotime('+14 days'));

$affected = executeUpdate($conn, 
    "INSERT INTO borrowings (user_id, book_id, due_date, status) VALUES (?, ?, ?, 'borrowed')", 
    "iis", [$user_id, $book_id, $due_date]);

if ($affected > 0) {
    // Update available copies
    executeUpdate($conn, 
        "UPDATE books SET available_copies = available_copies - 1 WHERE id = ?", 
        "i", [$book_id]);
    
    logAction($conn, 'BOOK_BORROWED', 'borrowings', getLastInsertId($conn));
    
    echo json_encode([
        'success' => true, 
        'message' => 'Book borrowed successfully',
        'due_date' => date('M d, Y', strtotime($due_date))
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to borrow book']);
}
?>
