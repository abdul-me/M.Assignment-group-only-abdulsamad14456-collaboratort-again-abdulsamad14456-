# Library Management System - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Extract Files
Place the `lms` folder in your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\lms\
```

### Step 2: Import Database
1. Open **phpMyAdmin** → http://localhost/phpmyadmin
2. Click **Import** tab
3. Select `database_schema.sql` file
4. Click **Import** button

**Or via command line:**
```bash
mysql -u root -p < database_schema.sql
```

### Step 3: Access Application
Open your browser:
```
http://localhost/lms/
```

---

## 🔐 Test Credentials

### Admin Account
```
Email: admin@lms.com
Password: admin123
```

### Regular User
```
Email: user@lms.com
Password: user123
```

---

## 📋 Quick Reference

### Admin Tasks
| Task | Path | Action |
|------|------|--------|
| Add Book | `admin/books.php` | Click "Add New Book" |
| Manage Books | `admin/books.php` | Edit/Delete books |
| Manage Users | `admin/users.php` | Activate/Deactivate users |
| View Borrowings | `admin/borrowings.php` | Manage loans |
| Add Category | `admin/categories.php` | Create new categories |

### User Tasks
| Task | Path | Action |
|------|------|--------|
| Browse Books | `user/dashboard.php` | Search/Filter/Sort |
| Borrow Book | `user/dashboard.php` | Click "Borrow" button |
| My Books | `user/my_borrowings.php` | View & return books |
| Wishlist | `user/wishlist.php` | Save desired books |

---

## 🎯 Sample Workflow

### As Admin:
1. Login with `admin@lms.com / admin123`
2. Go to **Dashboard** → View statistics
3. Go to **Books** → Add "The Great Gatsby"
4. Go to **Categories** → Add "Fiction" (if needed)
5. Go to **Users** → View registered users
6. Go to **Borrowings** → Confirm returns

### As User:
1. Login with `user@lms.com / user123`
2. Browse books or search by title
3. Filter by category (Fiction, Science, etc.)
4. Click **Borrow** on a book
5. Go to **My Books** → See your borrowings
6. Click **Return** when done
7. Add books to **Wishlist** for later

---

## 🛠️ Troubleshooting

### Database Not Connecting?
- Check `config/database.php` credentials
- Ensure MySQL is running
- Try importing schema again

### Login Not Working?
- Clear browser cookies
- Check password (case-sensitive)
- Verify user is active in database

### Session Timeout Too Quick?
- Edit `includes/auth.php` line: `$timeout = 1800`
- Change 1800 (seconds) to desired duration

### Can't Borrow Books?
- Check available_copies > 0
- Verify book is_active = 1
- Check category is assigned

---

## 📱 Mobile Access

The system is fully responsive:
- Works on phones, tablets, desktops
- Touch-friendly buttons
- Responsive navigation
- Optimized for small screens

**Test on phone:**
```
http://<your-ip>:80/lms/
```

---

## 🔧 Configuration

### Database Settings
**File:** `config/database.php`
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lms_db');
```

### Session Timeout
**File:** `includes/auth.php`
```php
checkSessionTimeout($timeout = 1800); // 30 minutes
```

### Borrow Duration
**File:** `api/borrow_book.php`
```php
$due_date = date('Y-m-d', strtotime('+14 days')); // Change days
```

### Colors & Theme
**File:** `assets/css/style.css`
```css
:root {
    --primary-color: #1e3c72;
    --secondary-color: #2a5298;
    --accent-color: #f39c12;
}
```

---

## 📂 File Map

```
Entry Point: index.php (Login/Register)
   ↓
Admin Area: admin/dashboard.php
├── admin/books.php ........... CRUD books
├── admin/categories.php ....... CRUD categories
├── admin/users.php ........... Manage users
└── admin/borrowings.php ....... Manage loans

User Area: user/dashboard.php
├── user/my_borrowings.php ..... View borrowed books
└── user/wishlist.php .......... Saved books

Core Files:
├── config/database.php ........ Database connection
├── includes/auth.php .......... Auth functions
└── includes/logout.php ........ Logout handler

API Endpoints:
├── api/borrow_book.php ........ Borrow API
├── api/return_book.php ........ Return API
└── api/wishlist.php ........... Wishlist API

Assets:
├── assets/css/style.css ....... Global styles
├── assets/css/dashboard.css ... Dashboard styles
└── assets/js/auth.js .......... Form validation
```

---

## ✨ Key Features at a Glance

✅ **Secure Authentication**
- Login/Register with validation
- Password hashing
- Session management
- CSRF protection

✅ **Book Management**
- Add/Edit/Delete books
- Track availability
- Category organization
- Search & filter

✅ **Lending System**
- 14-day borrow period
- Overdue tracking
- Return confirmation
- Wishlist support

✅ **User Management**
- View all users
- Activate/Deactivate
- Track statistics
- Audit logging

✅ **Security**
- Prepared statements
- Input validation
- Password encryption
- Audit trails

---

## 🚀 Next Steps

1. **Explore:** Browse the application
2. **Test:** Try all features
3. **Customize:** Modify colors, text, duration
4. **Extend:** Add new features as needed
5. **Deploy:** Follow deployment guide

---

## 📚 Full Documentation

- **README.md** - Setup & installation
- **TECHNICAL_DOC.md** - Technical details
- **database_schema.sql** - Database structure

---

## 💡 Pro Tips

1. **Search Books:** Use title, author, ISBN
2. **Filter Smart:** Use categories to organize
3. **Track Loans:** Check "My Books" regularly
4. **Wishlist Use:** Save books for later
5. **Admin View:** Check audit logs for history

---

## ❓ Common Questions

**Q: How long can I borrow a book?**  
A: 14 days by default (configurable)

**Q: Can I borrow the same book twice?**  
A: No, only one active loan per book per user

**Q: What happens if I'm overdue?**  
A: Book marks as "overdue" and shows in admin dashboard

**Q: Can I add books as a user?**  
A: No, only admins can add books

**Q: How do I become an admin?**  
A: Database modification only (security feature)

**Q: Where are error logs?**  
A: Check `logs/error.log` if directory exists

---

## 🎓 Learning Resources

- PHP Sessions: https://www.php.net/manual/en/book.session.php
- MySQLi: https://www.php.net/manual/en/book.mysqli.php
- Bootstrap 5: https://getbootstrap.com/docs/5.0/
- Security: https://owasp.org/

---

## 📞 Quick Support

### Database Issues
```php
// Check connection in config/database.php
error_log("DB Error: " . $conn->error);
```

### Session Issues
```php
// Clear sessions
session_destroy();
// Check php.ini settings
```

### Form Issues
```javascript
// Check browser console for JavaScript errors
console.log("Debug info");
```

---

**Enjoy using the Library Management System!** 📚

For complete documentation, see README.md and TECHNICAL_DOC.md
