# Library Management System - Setup & Installation Guide

## Project Overview

A complete Library Management System built with **Core PHP** (no frameworks), **MySQL**, and **Bootstrap 5**. Features include:

- ✅ User authentication (Login/Register) with role-based access
- ✅ Admin dashboard with CRUD operations for books
- ✅ User dashboard for browsing and borrowing books
- ✅ Session management with security best practices
- ✅ Responsive UI with Bootstrap 5 and modern styling
- ✅ Database with proper relationships and indexes
- ✅ Prepared statements to prevent SQL injection
- ✅ Password hashing with bcrypt
- ✅ Audit logging system

---

## System Requirements

- **PHP 7.4+** (7.4 or 8.0+)
- **MySQL 5.7+** (or MariaDB 10.3+)
- **Apache 2.4** with mod_rewrite enabled
- **Modern Browser** (Chrome, Firefox, Safari, Edge)

---

## Installation Steps

### 1. Extract Project Files

Place the LMS folder in your web root:
```
htdocs/lms/
```

### 2. Import Database Schema

1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Create new database (optional - script will create it)
3. Go to **Import** tab
4. Select `database_schema.sql` file
5. Click **Import**

**Alternatively**, run via MySQL command line:
```bash
mysql -u root -p < database_schema.sql
```

### 3. Configure Database Connection

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_USER', 'root');           // Your database user
define('DB_PASS', '');               // Your database password
define('DB_NAME', 'lms_db');         // Database name
define('DB_PORT', 3306);             // MySQL port
```

### 4. Set Permissions

Ensure write permissions on the project folder:
```bash
chmod -R 755 /path/to/lms
chmod -R 777 /path/to/lms/logs  # For error logs
```

### 5. Access the Application

Open browser and navigate to:
```
http://localhost/lms/
```

---

## Default Test Accounts

### Admin Account
- **Email:** admin@lms.com
- **Password:** admin123
- **Role:** Administrator

### User Account
- **Email:** user@lms.com
- **Password:** user123
- **Role:** Regular User

> ⚠️ Change passwords in production!

---

## Project Structure

```
lms/
├── config/
│   └── database.php                 # Database connection & helpers
├── includes/
│   ├── auth.php                     # Authentication utilities
│   └── logout.php                   # Logout handler
├── admin/
│   ├── dashboard.php                # Admin dashboard
│   ├── books.php                    # Book management (CRUD)
│   ├── categories.php               # Category management
│   ├── users.php                    # User management
│   ├── borrowings.php               # Borrowing records
│   └── reports.php                  # Reports (optional)
├── user/
│   ├── dashboard.php                # Browse & borrow books
│   ├── my_borrowings.php            # User's borrowed books
│   ├── wishlist.php                 # User's wishlist
│   └── profile.php                  # User profile
├── api/
│   ├── borrow_book.php              # Borrow book API
│   ├── return_book.php              # Return book API
│   └── wishlist.php                 # Wishlist API
├── assets/
│   ├── css/
│   │   ├── style.css                # Global styles
│   │   ├── dashboard.css            # Dashboard styles
│   │   └── forms.css                # Form styles
│   └── js/
│       └── auth.js                  # Form validation & interactivity
├── index.php                        # Login/Register page
└── database_schema.sql              # Database schema (SQL)
```

---

## Database Schema

### Tables

1. **users** - User accounts with roles (admin/user)
2. **categories** - Book categories
3. **books** - Book inventory with availability tracking
4. **borrowings** - Lending records with due dates
5. **reviews** - User reviews and ratings
6. **wishlist** - Books saved by users
7. **audit_logs** - Admin action tracking

### Key Relationships

- Users → Borrowings → Books (One-to-Many)
- Books → Categories (Many-to-One)
- Users → Reviews → Books (One-to-Many)
- Users → Wishlist → Books (One-to-Many)

---

## Security Features

### ✅ Implemented

1. **Password Security**
   - Bcrypt hashing (cost: 10)
   - Minimum 6 characters with uppercase, lowercase, number
   - `password_hash()` and `password_verify()`

2. **SQL Injection Prevention**
   - Prepared statements with parameterized queries
   - `mysqli_stmt::bind_param()`

3. **Session Security**
   - HTTP-only cookies
   - Session ID regeneration after login
   - CSRF token validation
   - Session timeout (30 minutes)

4. **Input Validation**
   - Client-side (JavaScript)
   - Server-side (PHP)
   - Input sanitization

5. **Audit Logging**
   - All admin actions logged
   - IP address tracking
   - User agent logging
   - Timestamp recording

### 🔒 Recommendations for Production

1. Use HTTPS (SSL/TLS)
2. Set `session.secure = 1` in php.ini
3. Implement rate limiting on login
4. Add 2FA (two-factor authentication)
5. Regular security audits
6. Keep PHP and MySQL updated

---

## Core Features

### 1. Authentication System

**Login Page** (`index.php`)
- Email and password validation
- Role-based redirect (admin/user)
- "Remember me" functionality
- Error handling and messages

**Registration Page** (`index.php`)
- Full name, username, email, password
- Password strength indicator
- Duplicate detection
- Email validation

### 2. Admin Dashboard

**Main Dashboard** (`admin/dashboard.php`)
- Key statistics (books, users, borrowings)
- Recent activity log
- Quick action buttons

**Book Management** (`admin/books.php`)
- ✅ Create (Add new books)
- ✅ Read (List all books)
- ✅ Update (Edit book details)
- ✅ Delete (Remove books)
- ISBN uniqueness check
- Category filtering

**Category Management** (`admin/categories.php`)
- Add/Edit/Delete categories
- Active/Inactive toggle

**User Management** (`admin/users.php`)
- View all users
- Activate/Deactivate accounts
- View user details

**Borrowing Records** (`admin/borrowings.php`)
- Pending returns
- Overdue tracking
- Return confirmation

### 3. User Dashboard

**Browse Books** (`user/dashboard.php`)
- Search by title/author
- Filter by category
- Sorting options
- Pagination

**Borrowing System**
- 14-day borrow period
- Check availability
- Auto-return functionality

**Wishlist** (`user/wishlist.php`)
- Add to wishlist
- Track desired books

---

## API Endpoints

### Borrowing APIs

```php
POST /api/borrow_book.php
Parameters: book_id, csrf_token
Response: { success: true, due_date: "date" }

POST /api/return_book.php
Parameters: borrowing_id, csrf_token
Response: { success: true, message: "..." }

POST /api/wishlist.php
Parameters: book_id, action (add/remove), csrf_token
Response: { success: true, message: "..." }
```

---

## Key PHP Functions

### Authentication (`includes/auth.php`)

```php
initSession()                    # Start secure session
isLoggedIn()                     # Check if user logged in
isAdmin()                        # Check if user is admin
getCurrentUserId()               # Get user ID from session
setUserSession($id, ...)         # Create user session
destroySession()                 # Logout user
hashPassword($password)          # Hash password with bcrypt
verifyPassword($pwd, $hash)      # Verify password
verifyCSRFToken($token)          # Validate CSRF token
logAction(...)                   # Log user action
```

### Database (`config/database.php`)

```php
executeQuery($conn, $query, $types, $params)    # Execute prepared query
getRow($conn, $query, $types, $params)          # Get single row
getRows($conn, $query, $types, $params)         # Get multiple rows
executeUpdate($conn, $query, $types, $params)   # Insert/Update/Delete
getLastInsertId($conn)                          # Get last inserted ID
```

---

## Common Tasks

### Add New Book (Admin)

1. Navigate to **Books** → **Add New Book**
2. Fill in details:
   - Title, Author, ISBN
   - Category, Description
   - Publisher, Publication Date, Pages
   - Total Copies
3. Click **Add Book**

### Borrow Book (User)

1. Navigate to **Browse Books**
2. Find book
3. Click **Borrow** button
4. Confirm borrowing
5. Book due in 14 days

### Return Book (User)

1. Go to **My Books**
2. Find borrowed book
3. Click **Return**
4. Confirm return

### Manage Categories (Admin)

1. Go to **Categories**
2. View/Add/Edit/Delete categories

---

## Customization Guide

### Change Borrow Period

In `api/borrow_book.php`, change line:
```php
$due_date = date('Y-m-d', strtotime('+14 days'));  // Change '14 days'
```

### Modify Password Requirements

In `includes/auth.php`, update regex:
```php
// Current: Min 6 chars, uppercase, lowercase, number
preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $password)
```

### Add New Admin Features

1. Create PHP file in `admin/` folder
2. Start with `requireAdmin()` check
3. Include database and auth files
4. Add navigation link in sidebar

### Styling Customization

Edit CSS variables in `assets/css/style.css`:
```css
:root {
    --primary-color: #1e3c72;
    --secondary-color: #2a5298;
    --accent-color: #f39c12;
}
```

---

## Troubleshooting

### Database Connection Error

- Check database credentials in `config/database.php`
- Verify MySQL is running
- Check database and user exist

### Session Issues

- Ensure PHP sessions are enabled
- Check `php.ini` settings
- Verify temporary directory permissions

### Form Not Submitting

- Check CSRF token in forms
- Verify form method is POST
- Check JavaScript console for errors
- Validate HTML markup

### Books Not Displaying

- Check if books exist in database
- Verify category associations
- Check `is_active` flag

---

## Performance Optimization

### Database

1. **Indexes** - Already added on:
   - Foreign keys
   - Frequently searched columns (username, email, ISBN)
   - Status and date fields

2. **Query Optimization**
   - Use prepared statements (already implemented)
   - Implement pagination (already done)
   - Add caching for categories

### Frontend

1. **CSS/JS Optimization**
   - Minify CSS and JavaScript
   - Use CDN for Bootstrap and Bootstrap Icons
   - Lazy load images

2. **Caching**
   - Browser caching headers
   - Server-side caching

---

## Testing Checklist

### Authentication
- [ ] Login with admin account
- [ ] Login with user account
- [ ] Invalid credentials
- [ ] Register new user
- [ ] Session timeout
- [ ] Logout

### Admin Features
- [ ] Add book
- [ ] Edit book
- [ ] Delete book
- [ ] View borrowings
- [ ] View audit logs
- [ ] Manage users

### User Features
- [ ] Browse books
- [ ] Search books
- [ ] Filter by category
- [ ] Borrow book
- [ ] Return book
- [ ] Add to wishlist

### Security
- [ ] SQL injection attempts
- [ ] XSS attempts
- [ ] CSRF token validation
- [ ] Session security

---

## Deployment

### Production Checklist

1. **Security**
   - [ ] Set `error_reporting` to not display errors
   - [ ] Enable HTTPS/SSL
   - [ ] Update default passwords
   - [ ] Set secure session cookies

2. **Database**
   - [ ] Create backup
   - [ ] Set strong database password
   - [ ] Regular backups scheduled

3. **Configuration**
   - [ ] Update database credentials
   - [ ] Set proper file permissions (644 for files, 755 for dirs)
   - [ ] Remove database_schema.sql file

4. **Monitoring**
   - [ ] Setup error logging
   - [ ] Monitor performance
   - [ ] Regular security updates

---

## Support & Resources

### Documentation
- [PHP Official Docs](https://www.php.net/docs.php)
- [MySQL Docs](https://dev.mysql.com/doc/)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0/)

### Related Topics
- OWASP Security Guidelines
- Database Design Best Practices
- RESTful API Design

---

## License & Credits

Built as a comprehensive Library Management System starter template.

**Version:** 1.0.0  
**Last Updated:** December 2024  
**PHP Version:** 7.4+  
**MySQL Version:** 5.7+

---

## Contact & Issues

For issues, questions, or improvements:
1. Check documentation
2. Review database structure
3. Check browser console for errors
4. Review PHP error logs

---

**Enjoy building your Library Management System!** 📚
#   - M a j o r - A s s i g n m e n t - G r o u p - o n l y - A b d u l S a m a d -  
 #   - M a j o r - A s s i g n m e n t - G r o u p - o n l y - A b d u l S a m a d -  
 