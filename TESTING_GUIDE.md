# Testing Guide

## Library Management System - Comprehensive Testing Guide

This guide provides step-by-step instructions for testing all features of the LMS.

---

## Setup for Testing

### Prerequisites
- XAMPP or similar local server running
- Database imported (database_schema.sql)
- All files uploaded to htdocs/lms

### Test Accounts
```
Admin Account:
- Email: admin@lms.com
- Password: admin123

Regular User Account:
- Email: user@lms.com
- Password: user123
```

### Test Browsers
- Chrome (latest)
- Firefox (latest)
- Safari (if on Mac)
- Edge (latest)
- Mobile Safari (iOS)
- Chrome Mobile (Android)

---

## Test Categories

### 1. Authentication & Authorization

#### Test 1.1: Admin Login
- [ ] Navigate to http://localhost/lms/
- [ ] Click "Login" tab
- [ ] Enter email: admin@lms.com
- [ ] Enter password: admin123
- [ ] Click Login
- [ ] **Expected:** Redirect to admin/dashboard.php

#### Test 1.2: User Login
- [ ] Navigate to http://localhost/lms/
- [ ] Click "Login" tab
- [ ] Enter email: user@lms.com
- [ ] Enter password: user123
- [ ] Click Login
- [ ] **Expected:** Redirect to user/dashboard.php

#### Test 1.3: Invalid Login
- [ ] Navigate to http://localhost/lms/
- [ ] Enter invalid email
- [ ] Enter any password
- [ ] Click Login
- [ ] **Expected:** Error message "Invalid email or password"

#### Test 1.4: Registration
- [ ] Navigate to http://localhost/lms/
- [ ] Click "Register" tab
- [ ] Fill in all required fields
- [ ] Create new account
- [ ] **Expected:** Success message, redirect to login
- [ ] **Verify:** New user can login with created credentials

#### Test 1.5: Password Strength Indicator
- [ ] On registration page, focus on password field
- [ ] Type weak password (e.g., "123")
- [ ] **Expected:** Red indicator "Weak"
- [ ] Type stronger password (e.g., "Test123")
- [ ] **Expected:** Green indicator "Strong"

#### Test 1.6: Session Timeout
- [ ] Login to system
- [ ] Wait 30 minutes (or modify session timeout for testing)
- [ ] Try to access any admin/user page
- [ ] **Expected:** Redirect to login page

#### Test 1.7: Logout
- [ ] Login to system
- [ ] Click Logout
- [ ] **Expected:** Session destroyed, redirect to login page
- [ ] Try to access previous page
- [ ] **Expected:** Redirect to login

---

### 2. User Profile Management

#### Test 2.1: View Profile
- [ ] Login as user
- [ ] Click "My Profile"
- [ ] **Expected:** Profile page shows all user information
- [ ] **Verify:** Name, email, phone, address displayed correctly

#### Test 2.2: Update Profile
- [ ] On profile page, edit "Full Name"
- [ ] Change phone number
- [ ] Update address
- [ ] Click "Save Changes"
- [ ] **Expected:** Success message
- [ ] **Verify:** Data saved and reflected on page reload

#### Test 2.3: Change Password
- [ ] On profile page, scroll to "Change Password"
- [ ] Enter current password
- [ ] Enter new password (meeting strength requirements)
- [ ] Confirm new password
- [ ] Click "Update Password"
- [ ] **Expected:** Success message
- [ ] **Verify:** Can login with new password

#### Test 2.4: Invalid Password Change
- [ ] Try to change password with wrong current password
- [ ] **Expected:** Error message "Current password is incorrect"
- [ ] Try to use weak new password
- [ ] **Expected:** Error about password requirements
- [ ] Try mismatched password confirmation
- [ ] **Expected:** Error "Passwords do not match"

#### Test 2.5: Profile Statistics
- [ ] Login as user with borrowing history
- [ ] Go to profile
- [ ] **Expected:** Activity section shows:
  - Active loans count
  - Returned books count
  - Overdue books count
  - Wishlist items count

---

### 3. Book Management (Admin)

#### Test 3.1: Add Book
- [ ] Login as admin
- [ ] Go to Books page
- [ ] Click "Add Book"
- [ ] Fill in all fields:
  - Title: "Test Book"
  - Author: "Test Author"
  - ISBN: "unique-isbn-12345"
  - Category: Select a category
  - Quantity: 5
- [ ] Click "Save"
- [ ] **Expected:** Success message, book appears in list

#### Test 3.2: Add Book with Duplicate ISBN
- [ ] Try to add book with existing ISBN
- [ ] Click "Save"
- [ ] **Expected:** Error message "ISBN already exists"

#### Test 3.3: Edit Book
- [ ] In books list, click Edit on a book
- [ ] Modal appears with book data
- [ ] Change title to "Updated Title"
- [ ] Change quantity
- [ ] Click "Save"
- [ ] **Expected:** Success message, changes reflected in list

#### Test 3.4: Delete Book
- [ ] In books list, click Delete on a book
- [ ] Confirm deletion
- [ ] **Expected:** Success message, book removed from list
- [ ] **Verify:** Book no longer accessible

#### Test 3.5: Search Books (Admin)
- [ ] On books page, use search box
- [ ] Search for part of a book title
- [ ] Click Search
- [ ] **Expected:** Only matching books shown
- [ ] **Verify:** Non-matching books hidden

#### Test 3.6: Filter by Category
- [ ] Select category from dropdown
- [ ] **Expected:** Only books in that category shown
- [ ] **Verify:** Category indicator visible on cards

#### Test 3.7: Book Availability Tracking
- [ ] Add a book with quantity 2
- [ ] **Expected:** Shows "Available: 2"
- [ ] Have user borrow one copy
- [ ] **Expected:** Admin sees "Available: 1"
- [ ] Have user return it
- [ ] **Expected:** Back to "Available: 2"

---

### 4. Category Management (Admin)

#### Test 4.1: Add Category
- [ ] Go to Categories page
- [ ] Click "Add Category"
- [ ] Enter category name: "Test Category"
- [ ] Click Save
- [ ] **Expected:** Success message, category appears in list

#### Test 4.2: Edit Category
- [ ] Click Edit on a category
- [ ] Change name
- [ ] Click Save
- [ ] **Expected:** Success message, changes shown in list

#### Test 4.3: Delete Category (Empty)
- [ ] Create a new category with no books
- [ ] Click Delete
- [ ] Confirm deletion
- [ ] **Expected:** Category removed successfully

#### Test 4.4: Delete Category (With Books)
- [ ] Try to delete a category that has books
- [ ] **Expected:** Error message "Cannot delete category with books"
- [ ] **Action:** Delete all books in that category first, then retry

#### Test 4.5: Category Search
- [ ] Type in category search box
- [ ] **Expected:** Categories filtered in real-time

---

### 5. User Management (Admin)

#### Test 5.1: View Users List
- [ ] Go to Users page
- [ ] **Expected:** List shows:
  - Username/Email
  - Full Name
  - Role (Admin/User badge)
  - Status (Active/Inactive badge)
  - Join date

#### Test 5.2: Activate/Deactivate User
- [ ] Find an active user
- [ ] Click status toggle
- [ ] **Expected:** Status changes to Inactive
- [ ] That user cannot login
- [ ] Toggle back to Active
- [ ] **Expected:** User can login again

#### Test 5.3: Search Users
- [ ] Use search box to find user by email
- [ ] **Expected:** Only matching user shown

#### Test 5.4: User Role Display
- [ ] Verify admin accounts show "Admin" badge in red
- [ ] Verify regular users show "User" badge in blue

---

### 6. Borrowing Management (Admin)

#### Test 6.1: View Active Borrowings
- [ ] Go to Borrowings page
- [ ] Click "Active" tab
- [ ] **Expected:** Shows all borrowed books with:
  - User name
  - Book title
  - Borrow date
  - Due date
  - Days remaining

#### Test 6.2: View Overdue Borrowings
- [ ] Click "Overdue" tab
- [ ] **Expected:** Shows books past due date with:
  - Days overdue count
  - Red color indicator
  - User info

#### Test 6.3: View Returned Books
- [ ] Click "Returned" tab
- [ ] **Expected:** Shows historical returned books

#### Test 6.4: Confirm Return
- [ ] In Active tab, find a borrowing
- [ ] Click "Confirm Return"
- [ ] **Expected:**
  - Borrowing moved to Returned tab
  - Book availability increased
  - User's active loans count decreased

---

### 7. Reports & Analytics (Admin)

#### Test 7.1: View Overview Statistics
- [ ] Go to Reports page
- [ ] **Expected:** Statistics cards show:
  - Total books
  - Total users
  - Active loans
  - Overdue books

#### Test 7.2: Filter by Date Range
- [ ] Select start date: 01/01/2025
- [ ] Select end date: 12/31/2025
- [ ] Click Filter
- [ ] **Expected:** Reports update with filtered data

#### Test 7.3: Top Borrowed Books Report
- [ ] Scroll to "Top 10 Most Borrowed Books"
- [ ] **Expected:** Shows books sorted by borrow count
- [ ] **Verify:** Books with more borrows appear first

#### Test 7.4: Most Active Users Report
- [ ] Scroll to "Most Active Users"
- [ ] **Expected:** Shows users sorted by borrow count
- [ ] **Verify:** Most active users appear first

---

### 8. User Dashboard & Book Browsing

#### Test 8.1: View Dashboard
- [ ] Login as regular user
- [ ] **Expected:** Dashboard shows:
  - Book search box
  - Category filter
  - Sort options
  - Grid of available books

#### Test 8.2: Search Books
- [ ] Enter search term in search box
- [ ] Click Search or press Enter
- [ ] **Expected:** Books filtered by title and author
- [ ] **Verify:** Only matching books shown

#### Test 8.3: Filter by Category
- [ ] Select category from dropdown
- [ ] **Expected:** Only books in that category shown
- [ ] **Verify:** Filter persists on pagination

#### Test 8.4: Sort Options
- [ ] Test each sort option:
  - Recent first
  - Popular (by quantity)
  - Title (A-Z)
- [ ] **Expected:** Books reordered accordingly

#### Test 8.5: Pagination
- [ ] On dashboard with many books
- [ ] Navigate through pages
- [ ] **Expected:** 12 books per page, working pagination

#### Test 8.6: Active Borrowings Sidebar
- [ ] On dashboard, scroll to sidebar
- [ ] **Expected:** Shows currently borrowed books with:
  - Book title
  - Due date
  - Color-coded urgency (green/yellow/red)

---

### 9. Book Borrowing

#### Test 9.1: Borrow Available Book
- [ ] Login as user
- [ ] Find book with available copies
- [ ] Click "Borrow"
- [ ] **Expected:**
  - Confirmation dialog appears
  - Success message after confirmation
  - Page updates with new borrowing
  - Availability count decreases by 1

#### Test 9.2: Borrow Unavailable Book
- [ ] Find book with no available copies
- [ ] **Expected:** Borrow button is disabled
- [ ] Cannot click to borrow

#### Test 9.3: Prevent Duplicate Borrowing
- [ ] Borrow a book
- [ ] Try to borrow same book again
- [ ] **Expected:** Error "You already have this book borrowed"

#### Test 9.4: Due Date Calculation
- [ ] Borrow a book
- [ ] Check "My Borrowings" page
- [ ] **Expected:** Due date is 14 days from borrow date

#### Test 9.5: Borrow from Advanced Search
- [ ] Go to Advanced Search page
- [ ] Find and borrow a book from there
- [ ] **Expected:** Borrow works seamlessly

#### Test 9.6: Borrow from Wishlist
- [ ] Go to Wishlist page
- [ ] If book available, click "Borrow"
- [ ] **Expected:** Book borrowed, removed from wishlist option

---

### 10. Returning Books

#### Test 10.1: Return Book
- [ ] Go to "My Borrowings" page
- [ ] Find active borrowing
- [ ] Click "Return Book"
- [ ] Confirm action
- [ ] **Expected:**
  - Success message
  - Borrowing moves to returned section
  - Book appears in available books again

#### Test 10.2: Return from Dashboard
- [ ] On dashboard, in active borrowings sidebar
- [ ] Return a book (if return button available)
- [ ] **Expected:** Book returned, sidebar updates

#### Test 10.3: Cannot Return Twice
- [ ] Return a book
- [ ] Try to return same borrowing again
- [ ] **Expected:** Error or button disabled

---

### 11. Wishlist Management

#### Test 11.1: Add to Wishlist
- [ ] On dashboard or advanced search
- [ ] Click "Add to Wishlist" on a book
- [ ] **Expected:**
  - Success message
  - Icon changes state
  - Wishlist count increases

#### Test 11.2: View Wishlist
- [ ] Go to Wishlist page
- [ ] **Expected:** Shows all wishlist items with:
  - Book details
  - Availability status
  - Borrow button
  - Remove button

#### Test 11.3: Remove from Wishlist
- [ ] On Wishlist page
- [ ] Click "Remove"
- [ ] **Expected:**
  - Book removed from list
  - Wishlist count decreases
  - Success message

#### Test 11.4: Borrow from Wishlist
- [ ] On Wishlist page
- [ ] If book available, click "Borrow"
- [ ] **Expected:** Book borrowed successfully
- [ ] **Option:** Wishlist item removed or kept

#### Test 11.5: Prevent Duplicate Wishlist
- [ ] Try to add same book to wishlist twice
- [ ] **Expected:** Error "Already in wishlist"

#### Test 11.6: Empty Wishlist
- [ ] Remove all items from wishlist
- [ ] Return to wishlist page
- [ ] **Expected:** "Your wishlist is empty" message with link to browse

---

### 12. Notifications & Alerts

#### Test 12.1: Overdue Books Alert
- [ ] Go to Notifications page
- [ ] If you have overdue books
- [ ] **Expected:**
  - Red alert shows overdue books
  - Days overdue calculation correct
  - Quick action button to borrowings

#### Test 12.2: Due Soon Alert
- [ ] Books due within 3 days
- [ ] **Expected:**
  - Yellow alert shows due soon books
  - Days remaining count
  - Link to borrowings

#### Test 12.3: System Notifications
- [ ] On Notifications page
- [ ] **Expected:** Shows system announcements
- [ ] Links to new features work

#### Test 12.4: Activity Statistics
- [ ] On Notifications page, bottom section
- [ ] **Expected:** Shows:
  - Overdue count
  - Due soon count
  - Active loans
  - Wishlist items

#### Test 12.5: Notification Persistence
- [ ] Dismiss an alert
- [ ] Navigate away and return
- [ ] **Expected:** Persistent alerts return
- [ ] Closed notifications don't reappear

---

### 13. Advanced Search

#### Test 13.1: Full-Text Search
- [ ] Go to Advanced Search page
- [ ] Enter book title or author
- [ ] **Expected:** Books matching search shown
- [ ] Results count displays

#### Test 13.2: Category Filter
- [ ] Select category
- [ ] **Expected:** Results filtered by category

#### Test 13.3: Availability Filter
- [ ] Test "Available Only"
- [ ] **Expected:** Only books with copies shown
- [ ] Test "Unavailable Only"
- [ ] **Expected:** Only out-of-stock books shown

#### Test 13.4: Sorting Options
- [ ] Test each sort option
- [ ] **Expected:** Results sorted correctly

#### Test 13.5: Pagination
- [ ] If many results, test pagination
- [ ] **Expected:** Page links work, correct books show

#### Test 13.6: Search + Filter Combination
- [ ] Search for a title
- [ ] Add category filter
- [ ] **Expected:** Results filtered by both criteria

#### Test 13.7: Results Count
- [ ] **Expected:** Badge shows total results found
- [ ] Page indicator shows current page

#### Test 13.8: Advanced Search Borrow
- [ ] From search results, borrow a book
- [ ] **Expected:** Works same as dashboard borrow

---

### 14. UI/UX Testing

#### Test 14.1: Responsive Design - Mobile
- [ ] Test on mobile device (or device emulation)
- [ ] **Expected:**
  - Navigation collapses to hamburger menu
  - Content stacks vertically
  - Buttons still clickable
  - Forms readable and usable

#### Test 14.2: Responsive Design - Tablet
- [ ] Test on tablet (or emulation)
- [ ] **Expected:**
  - Good layout for medium screen
  - Navigation accessible
  - Grid adapts to screen width

#### Test 14.3: Responsive Design - Desktop
- [ ] Test on desktop (1920px wide)
- [ ] **Expected:**
  - Full layout shown
  - Navigation horizontal
  - Proper spacing

#### Test 14.4: Color Contrast
- [ ] **Expected:** All text readable
- [ ] Buttons have sufficient contrast
- [ ] Warnings/errors clearly visible

#### Test 14.5: Form Usability
- [ ] Test all forms
- [ ] **Expected:**
  - Clear labels
  - Proper input types
  - Helpful placeholders
  - Clear error messages

#### Test 14.6: Loading States
- [ ] Perform AJAX operations
- [ ] **Expected:** Loading indication shown
- [ ] Operations complete successfully

#### Test 14.7: Modal Dialogs
- [ ] Test all modals
- [ ] **Expected:**
  - Backdrop darkens page
  - Close button works
  - Escape key closes modal
  - Form submission works

---

### 15. Security Testing

#### Test 15.1: CSRF Protection
- [ ] Try to submit form without CSRF token
- [ ] **Expected:** Request rejected

#### Test 15.2: SQL Injection
- [ ] Try to search with SQL injection attempt
- [ ] **Example:** `' OR '1'='1`
- [ ] **Expected:** Treated as literal text, not executed

#### Test 15.3: XSS Protection
- [ ] Try to input HTML/JavaScript in forms
- [ ] **Example:** `<script>alert('XSS')</script>`
- [ ] **Expected:** Rendered as text, not executed

#### Test 15.4: Authorization Check
- [ ] Login as user
- [ ] Try to access admin URL directly
- [ ] **Expected:** Redirect to login or access denied

#### Test 15.5: Session Hijacking Prevention
- [ ] During session, check session ID
- [ ] After login, check if ID changed
- [ ] **Expected:** ID regenerated after login

#### Test 15.6: Password Not Visible in HTML
- [ ] Right-click on password field, Inspect
- [ ] **Expected:** No plaintext password visible
- [ ] Type password in field
- [ ] **Expected:** Input type="password" still secure

#### Test 15.7: Redirect After Login
- [ ] Login as admin
- [ ] **Expected:** Redirects to admin dashboard
- [ ] Login as user
- [ ] **Expected:** Redirects to user dashboard

---

### 16. Performance Testing

#### Test 16.1: Page Load Speed
- [ ] Load main dashboard
- [ ] **Expected:** Loads in < 2 seconds

#### Test 16.2: Search Performance
- [ ] Search with complex query
- [ ] **Expected:** Results return in < 1 second

#### Test 16.3: Large Data Sets
- [ ] Add 100+ books to system
- [ ] **Expected:** Dashboard still responsive

#### Test 16.4: Image Optimization
- [ ] Check images in browser dev tools
- [ ] **Expected:** Reasonable file sizes
- [ ] No oversized images

#### Test 16.5: CSS/JS Loading
- [ ] Check for render-blocking resources
- [ ] **Expected:** Pages render quickly

---

### 17. Database Testing

#### Test 17.1: Data Persistence
- [ ] Create a book
- [ ] Refresh page
- [ ] **Expected:** Book still visible

#### Test 17.2: Relationships
- [ ] Delete a category
- [ ] Check if books remain (error or cascade)
- [ ] **Expected:** Proper relationship handling

#### Test 17.3: Indexes
- [ ] Search for books by ISBN
- [ ] **Expected:** Returns instantly (indexed field)

#### Test 17.4: Constraints
- [ ] Try to create duplicate ISBN
- [ ] **Expected:** Database rejects with error

#### Test 17.5: Audit Log
- [ ] Perform admin action
- [ ] Check audit_logs table
- [ ] **Expected:** Action recorded with:
  - Admin ID
  - Action type
  - Timestamp
  - IP address

---

### 18. Browser Compatibility

#### Test 18.1: Chrome
- [ ] Test all features
- [ ] **Expected:** 100% functional

#### Test 18.2: Firefox
- [ ] Test all features
- [ ] **Expected:** 100% functional
- [ ] Check for JavaScript differences

#### Test 18.3: Safari
- [ ] Test on Mac/iOS
- [ ] **Expected:** 100% functional
- [ ] Check for CSS differences

#### Test 18.4: Edge
- [ ] Test all features
- [ ] **Expected:** 100% functional

#### Test 18.5: Mobile Browsers
- [ ] Test on Chrome Mobile
- [ ] **Expected:** Touch interactions work
- [ ] Responsive layout correct

---

### 19. Error Handling

#### Test 19.1: Database Connection Error
- [ ] Disconnect database temporarily
- [ ] Try to access system
- [ ] **Expected:** Graceful error message

#### Test 19.2: Invalid Form Input
- [ ] Submit form with invalid data
- [ ] **Expected:** Clear error messages
- [ ] Form doesn't refresh/clear

#### Test 19.3: 404 Errors
- [ ] Try to access non-existent book
- [ ] **Expected:** Proper 404 handling

#### Test 19.4: 403 Forbidden
- [ ] User tries to access admin page
- [ ] **Expected:** 403 or redirect to login

#### Test 19.5: Missing Required Fields
- [ ] Submit form without required field
- [ ] **Expected:** Validation error shown

---

### 20. Data Validation

#### Test 20.1: Email Validation
- [ ] Try invalid email format
- [ ] **Expected:** Error message

#### Test 20.2: ISBN Validation
- [ ] Try duplicate ISBN
- [ ] **Expected:** Error message

#### Test 20.3: Required Fields
- [ ] Leave required fields empty
- [ ] **Expected:** Error on submit

#### Test 20.4: Data Type Validation
- [ ] Try to enter text in number field
- [ ] **Expected:** Validation error or coercion

#### Test 20.5: String Length
- [ ] Try to enter very long title
- [ ] **Expected:** Either truncated or limited

---

## Test Reporting

### Passing Test
- [ ] Feature works as documented
- [ ] No errors in console
- [ ] No database errors
- [ ] Expected results achieved

### Failing Test
- [ ] Document exact steps to reproduce
- [ ] Screenshot of error
- [ ] Browser and version
- [ ] Expected vs actual result
- [ ] Error message details

### Bug Report Template
```
Title: [Feature] - [Issue Description]
Severity: Critical/High/Medium/Low
Steps to Reproduce:
1. ...
2. ...
3. ...

Expected Result:
[What should happen]

Actual Result:
[What actually happened]

Environment:
- Browser: [Name and version]
- OS: [Windows/Mac/Linux and version]
- Screen Resolution: [1920x1080]

Screenshots/Logs:
[Attach if applicable]
```

---

## Sign-Off Checklist

- [ ] All 20 test categories passed
- [ ] No critical bugs remain
- [ ] All features documented and working
- [ ] Performance acceptable
- [ ] Security validated
- [ ] Browser compatibility confirmed
- [ ] Mobile responsiveness verified
- [ ] Database integrity confirmed
- [ ] Error handling adequate
- [ ] User documentation complete

**Tested By:** ____________________
**Date:** ____________________
**Status:** ☐ Pass ☐ Fail ☐ Conditional Pass

