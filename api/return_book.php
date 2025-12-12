<?php
/**
 * API - Return Book
 * POST /api/return_book.php
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

$borrowing_id = intval($_POST['borrowing_id'] ?? 0);
$user_id = getCurrentUserId();

if ($borrowing_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid borrowing ID']);
    exit;
}

// Check borrowing record belongs to user
$borrowing = getRow($conn, 
    "SELECT id, book_id, status FROM borrowings WHERE id = ? AND user_id = ?", 
    "ii", [$borrowing_id, $user_id]);

if (!$borrowing) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Borrowing record not found']);
    exit;
}

if ($borrowing['status'] === 'returned') {
    echo json_encode(['success' => false, 'message' => 'Book already returned']);
    exit;
}

// Update borrowing record
$affected = executeUpdate($conn, 
    "UPDATE borrowings SET status = 'returned', return_date = NOW() WHERE id = ?", 
    "i", [$borrowing_id]);

if ($affected > 0) {
    // Increase available copies
    executeUpdate($conn, 
        "UPDATE books SET available_copies = available_copies + 1 WHERE id = ?", 
        "i", [$borrowing['book_id']]);
    
    logAction($conn, 'BOOK_RETURNED', 'borrowings', $borrowing_id);
    
    echo json_encode(['success' => true, 'message' => 'Book returned successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to return book']);
}
?>
