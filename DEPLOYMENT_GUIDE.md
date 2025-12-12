# Deployment Guide

## Pre-Deployment Checklist

### Development Environment
- [ ] All features tested locally
- [ ] No debug code left in PHP files
- [ ] Database credentials configured
- [ ] .htaccess configured for rewrite rules
- [ ] Logs directory writable by web server
- [ ] Backup of database created

### Code Review
- [ ] No hardcoded credentials in files
- [ ] No commented-out debugging code
- [ ] All error handling in place
- [ ] CSRF tokens enabled
- [ ] SQL injection prevention verified
- [ ] XSS protection enabled

### Performance
- [ ] Database indexes optimized
- [ ] CSS and JS minified (optional)
- [ ] Images optimized
- [ ] Database cleanup done (old sessions, logs)

---

## Deployment Steps

### 1. Prepare Server

**Requirements:**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite
- 50 MB minimum disk space
- HTTPS certificate (recommended)

**Install Dependencies:**
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install php php-mysqli php-json apache2 mysql-server

# Enable Apache modules
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 2. Database Setup

**Create Database:**
```bash
# Using MySQL command line
mysql -u root -p

CREATE DATABASE lms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON lms_db.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Import Schema:**
```bash
mysql -u lms_user -p lms_db < database_schema.sql
```

### 3. Upload Files

**Via FTP/SFTP:**
```
Connect to server
Navigate to /var/www/html/ (or your web root)
Upload all files maintaining folder structure
```

**Via SSH:**
```bash
scp -r lms/ user@server.com:/var/www/html/
# Or using git
cd /var/www/html
git clone https://github.com/yourrepo/lms.git
```

### 4. Configure Application

**Update Database Credentials:**

Edit `config/database.php`:
```php
<?php
$db_host = 'localhost';
$db_user = 'lms_user';
$db_pass = 'strong_password_here';
$db_name = 'lms_db';
```

**Set File Permissions:**
```bash
# Make directories writable
chmod 755 /var/www/html/lms
chmod 777 /var/www/html/lms/logs
chmod 777 /var/www/html/lms/tmp

# Make PHP files readable
chmod 644 /var/www/html/lms/*.php
chmod 644 /var/www/html/lms/**/*.php
```

### 5. Configure Web Server

**Create Virtual Host (Apache):**

Create `/etc/apache2/sites-available/lms.conf`:
```apache
<VirtualHost *:80>
    ServerName lms.yourdomain.com
    ServerAlias www.lms.yourdomain.com
    DocumentRoot /var/www/html/lms
    
    <Directory /var/www/html/lms>
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/lms-error.log
    CustomLog ${APACHE_LOG_DIR}/lms-access.log combined
</VirtualHost>
```

**Enable Site:**
```bash
sudo a2ensite lms.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

### 6. Enable HTTPS (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Generate certificate
sudo certbot --apache -d lms.yourdomain.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### 7. Verify Installation

**Test Database Connection:**
```bash
curl http://lms.yourdomain.com/config/test.php
# Should show: "Database connection successful"
```

**Test Login:**
1. Navigate to http://lms.yourdomain.com
2. Login with admin@lms.com / admin123
3. Verify admin dashboard loads

### 8. Post-Deployment

**Check Logs:**
```bash
tail -f /var/log/apache2/lms-error.log
tail -f /var/log/apache2/lms-access.log
```

**Monitor Performance:**
```bash
# Check disk usage
df -h /var/www/html/lms

# Check database size
mysql -u lms_user -p -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'lms_db';"

# Monitor slow queries
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"
```

---

## Configuration Optimization

### PHP Configuration

Edit `/etc/php/7.4/apache2/php.ini`:
```ini
; Performance
max_execution_time = 30
max_input_time = 60
memory_limit = 128M
post_max_size = 50M
upload_max_filesize = 50M

; Security
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
register_globals = Off
magic_quotes_gpc = Off

; Session
session.use_only_cookies = 1
session.cookie_httponly = 1
session.gc_maxlifetime = 1800
```

### MySQL Configuration

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
# Performance
max_connections = 100
thread_cache_size = 64
query_cache_limit = 2M
query_cache_size = 32M

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Security
skip-name-resolve = 1
```

### Enable Caching

Edit `.htaccess`:
```apache
# Browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
</IfModule>

# GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

---

## Backup Strategy

### Automated Daily Backups

Create `/usr/local/bin/backup-lms.sh`:
```bash
#!/bin/bash

BACKUP_DIR="/backups/lms"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="lms_db"
DB_USER="lms_user"
DB_PASS="password"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/lms

# Delete old backups (keep 30 days)
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Schedule with Cron:**
```bash
# Add to crontab
sudo crontab -e

# Add line:
0 2 * * * /usr/local/bin/backup-lms.sh >> /var/log/lms-backup.log 2>&1
```

### Manual Backup

```bash
# Database
mysqldump -u lms_user -p lms_db > lms_db_backup.sql

# Files
tar -czf lms_files_backup.tar.gz /var/www/html/lms

# Store securely
# Upload to cloud storage (S3, Google Drive, etc.)
```

---

## Monitoring & Maintenance

### Daily Tasks
- [ ] Check error logs for issues
- [ ] Verify database backups completed
- [ ] Monitor server disk space
- [ ] Check application functionality

### Weekly Tasks
- [ ] Review access logs for suspicious activity
- [ ] Update PHP/MySQL security patches
- [ ] Verify HTTPS certificate validity
- [ ] Performance analysis

### Monthly Tasks
- [ ] Security audit
- [ ] Database optimization and cleanup
- [ ] User feedback review
- [ ] Capacity planning

### Server Health Check

```bash
#!/bin/bash
# Check disk usage
echo "Disk Usage:"
df -h | grep -v ^Filesystem

# Check Apache status
echo -e "\nApache Status:"
systemctl status apache2 --no-pager

# Check MySQL status
echo -e "\nMySQL Status:"
systemctl status mysql --no-pager

# Check PHP errors
echo -e "\nRecent PHP Errors:"
tail -5 /var/log/php-errors.log

# Check failed logins
echo -e "\nFailed Logins (last 24h):"
grep "Authentication failed" /var/log/apache2/lms-error.log | tail -5
```

---

## Troubleshooting

### 500 Internal Server Error
```bash
# Check Apache error log
tail -20 /var/log/apache2/lms-error.log

# Common causes:
# 1. .htaccess syntax error
# 2. PHP configuration issue
# 3. Database connection error
# 4. Permission denied
```

### Database Connection Issues
```bash
# Test MySQL connection
mysql -h localhost -u lms_user -p -e "SELECT 1"

# Check credentials in config/database.php
# Verify user privileges
mysql -u root -p -e "SHOW GRANTS FOR 'lms_user'@'localhost';"
```

### File Upload Issues
```bash
# Check permissions
ls -la /var/www/html/lms/uploads/

# Fix permissions
chmod 755 /var/www/html/lms/uploads/
chmod 644 /var/www/html/lms/uploads/*

# Check upload limits in php.ini
# upload_max_filesize = 50M
# post_max_size = 50M
```

### Slow Performance
```bash
# Check database indexes
mysql> SHOW INDEX FROM users;
mysql> SHOW INDEX FROM books;

# Check slow queries
mysql -u root -p -e "SELECT * FROM mysql.slow_log LIMIT 10 \G"

# Check PHP-FPM status (if using FPM)
systemctl status php7.4-fpm
```

---

## Rollback Procedure

If issues occur after deployment:

```bash
# 1. Stop web server
sudo systemctl stop apache2

# 2. Restore files from backup
tar -xzf /backups/lms/files_DATE.tar.gz -C /

# 3. Restore database
mysql -u lms_user -p lms_db < /backups/lms/db_DATE.sql

# 4. Restart web server
sudo systemctl start apache2

# 5. Verify functionality
curl http://lms.yourdomain.com
```

---

## Security Hardening

### Firewall Rules
```bash
# Allow HTTP/HTTPS only
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Disable Root Login
```bash
sudo sed -i 's/^#PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo systemctl restart sshd
```

### Set up Fail2Ban
```bash
sudo apt-get install fail2ban
sudo systemctl start fail2ban
```

### Regular Updates
```bash
sudo apt-get update && sudo apt-get upgrade -y
```

---

## Performance Benchmarking

### Load Testing
```bash
# Using Apache Bench
ab -n 1000 -c 10 http://lms.yourdomain.com/

# Using wrk
wrk -t4 -c100 -d30s http://lms.yourdomain.com/
```

### Database Performance
```sql
-- Check slow queries
SELECT * FROM mysql.slow_log;

-- Analyze tables
ANALYZE TABLE users, books, borrowings;

-- Check table size
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) 
AS 'Size MB' FROM information_schema.TABLES 
WHERE table_schema = 'lms_db';
```

---

## Support & Documentation

- **Admin Panel:** http://lms.yourdomain.com (admin@lms.com)
- **Documentation:** See README.md and TECHNICAL_DOC.md
- **API Reference:** See API_DOCUMENTATION.md
- **Logs Location:** /var/log/apache2/ and /var/log/mysql/

For issues, check logs and consult troubleshooting section above.

