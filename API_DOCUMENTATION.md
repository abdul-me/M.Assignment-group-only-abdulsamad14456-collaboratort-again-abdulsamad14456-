# API Documentation

## Overview
The Library Management System provides RESTful API endpoints for client-side operations. All endpoints use POST method and require CSRF token verification for security.

## Base URL
```
http://localhost/lms/api/
```

## Authentication
All API endpoints require the user to be authenticated (session must be active). CSRF token must be included in all requests.

---

## Endpoints

### 1. Borrow Book

**Endpoint:** `POST /api/borrow_book.php`

**Description:** Allows a user to borrow an available book for 14 days.

**Parameters:**
```
book_id      (required) - Integer, ID of the book to borrow
csrf_token   (required) - String, CSRF token from the session
```

**Request Example:**
```javascript
fetch('/lms/api/borrow_book.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'book_id=5&csrf_token=abc123'
})
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Book borrowed successfully",
    "due_date": "2025-12-20",
    "borrow_id": 42
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "Book not available",
    "error_code": "OUT_OF_STOCK"
}
```

**Error Codes:**
- `NOT_FOUND` - Book does not exist
- `OUT_OF_STOCK` - No copies available
- `ALREADY_BORROWED` - User already has an active borrowing of this book
- `INVALID_TOKEN` - CSRF token is invalid

**HTTP Status:**
- 200: Success or user error
- 403: CSRF token verification failed
- 500: Server error

---

### 2. Return Book

**Endpoint:** `POST /api/return_book.php`

**Description:** Marks a borrowed book as returned and updates availability.

**Parameters:**
```
borrowing_id (required) - Integer, ID of the borrowing record
csrf_token   (required) - String, CSRF token from the session
```

**Request Example:**
```javascript
fetch('/lms/api/return_book.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'borrowing_id=42&csrf_token=abc123'
})
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Book returned successfully",
    "book_title": "The Great Gatsby",
    "return_date": "2025-12-06"
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "Borrowing record not found",
    "error_code": "NOT_FOUND"
}
```

**Error Codes:**
- `NOT_FOUND` - Borrowing record doesn't exist
- `UNAUTHORIZED` - User doesn't own this borrowing
- `INVALID_STATUS` - Book already returned
- `INVALID_TOKEN` - CSRF token is invalid

---

### 3. Wishlist Management

**Endpoint:** `POST /api/wishlist.php`

**Description:** Add or remove books from user's wishlist.

**Parameters:**
```
action       (required) - String, either "add" or "remove"
book_id      (required) - Integer, ID of the book
csrf_token   (required) - String, CSRF token from the session
```

**Request Example - Add to Wishlist:**
```javascript
fetch('/lms/api/wishlist.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'action=add&book_id=7&csrf_token=abc123'
})
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Book added to wishlist",
    "action": "add",
    "book_id": 7
}
```

**Request Example - Remove from Wishlist:**
```javascript
fetch('/lms/api/wishlist.php', {
    method: 'POST',
    body: 'action=remove&book_id=7&csrf_token=abc123'
})
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Book removed from wishlist",
    "action": "remove",
    "book_id": 7
}
```

**Error Codes:**
- `INVALID_ACTION` - Action must be "add" or "remove"
- `NOT_FOUND` - Book doesn't exist
- `DUPLICATE` - Book already in wishlist (for add)
- `NOT_IN_WISHLIST` - Book not in wishlist (for remove)
- `INVALID_TOKEN` - CSRF token is invalid

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200  | Success or validation error |
| 400  | Bad request (missing parameters) |
| 403  | Forbidden (CSRF token invalid, not authenticated) |
| 404  | Resource not found |
| 500  | Server error |

---

## Common Patterns

### Error Handling in JavaScript
```javascript
async function makeAPICall(endpoint, data) {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data).toString()
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Operation successful:', result.message);
            return result;
        } else {
            console.error('Operation failed:', result.message);
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Network error:', error);
        alert('Network error occurred');
    }
}
```

### Using FormData
```javascript
const formData = new FormData();
formData.append('book_id', 5);
formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

fetch('/lms/api/borrow_book.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## CSRF Token

All POST requests must include a valid CSRF token. Tokens are generated on every page load and stored in a hidden input field:

```html
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
```

To retrieve the token in JavaScript:
```javascript
const csrfToken = document.querySelector('input[name="csrf_token"]').value;
```

---

## Rate Limiting

Currently, no rate limiting is implemented. For production use, consider:
- Limiting requests per user per minute
- Adding throttling for heavy operations
- Implementing request queuing

---

## Future Endpoints (Planned)

- `POST /api/review_book.php` - Submit book reviews
- `GET /api/user_stats.php` - Get user borrowing statistics
- `POST /api/renew_book.php` - Extend borrowing period
- `GET /api/recommendations.php` - Get personalized recommendations

---

## Best Practices

1. **Always check response.success** before using response data
2. **Handle errors gracefully** with user-friendly messages
3. **Validate data on server-side** even if validated on client
4. **Use HTTPS in production** to protect CSRF tokens
5. **Log API errors** for debugging and monitoring
6. **Test error scenarios** (network failures, invalid tokens, etc.)

---

## Example: Complete Book Borrowing Flow

```javascript
// Step 1: Prepare data
const bookId = document.getElementById('book_id').value;
const csrfToken = document.querySelector('input[name="csrf_token"]').value;

// Step 2: Confirm with user
if (confirm('Borrow this book for 14 days?')) {
    
    // Step 3: Make API call
    fetch('/lms/api/borrow_book.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'book_id=' + bookId + '&csrf_token=' + csrfToken
    })
    .then(response => response.json())
    .then(data => {
        // Step 4: Handle response
        if (data.success) {
            // Show success message with due date
            const dueDateElement = document.createElement('div');
            dueDateElement.className = 'alert alert-success';
            dueDateElement.textContent = 'Book borrowed! Due: ' + data.due_date;
            document.body.insertBefore(dueDateElement, document.body.firstChild);
            
            // Reload data
            setTimeout(() => location.reload(), 2000);
        } else {
            // Show error
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Request failed:', error);
        alert('Network error. Please try again.');
    });
}
```

---

## Troubleshooting

### "CSRF token verification failed"
- Ensure token is included in request
- Token must be from the current session
- Token expires after session timeout

### "Book not available"
- Check if total_copies > 0 in database
- Verify book is marked as active (is_active = 1)

### "User already has this book"
- User cannot borrow same book twice simultaneously
- Must return first copy before borrowing again

### API returning 500 error
- Check server error logs
- Verify database connection
- Ensure all required fields are provided

