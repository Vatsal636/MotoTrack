# MotoTrack - Quick Reference Guide

## 🚀 Quick Start Commands

### Windows (Using setup.bat)
```cmd
setup.bat
```

### Edit Configuration
```cmd
notepad .env
```

### View Logs
```cmd
type logs\activity_2025-10-15.log
type logs\php_errors.log
```

---

## 🔐 Common Security Tasks

### Change Default Admin Password
1. Login at: `yoursite.com/login.php`
2. Username: `admin`, Password: `admin123`
3. Go to Profile and change password
4. New password must have:
   - At least 8 characters
   - One uppercase letter
   - One lowercase letter
   - One number

### Test Security Features

**Test CSRF Protection:**
```html
<!-- Try submitting form without csrf_token -->
Should see: "Invalid request. Please try again."
```

**Test Rate Limiting:**
```
1. Enter wrong password 5 times
2. Should see: "Too many failed login attempts..."
3. Wait 15 minutes or clear cookies
```

**Test Session Timeout:**
```
1. Login to application
2. Wait 1 hour without activity
3. Try to access any page
4. Should redirect to login
```

---

## 📝 Configuration Quick Reference

### .env File Settings

```env
# Must Change
APP_URL=https://your-domain.com/
DB_HOST=your_host
DB_USER=your_user
DB_PASS=your_password
DB_NAME=your_database

# Security Settings
APP_ENV=production          # development|production
APP_DEBUG=false            # true|false (false in production)
SESSION_TIMEOUT=3600       # seconds (3600 = 1 hour)
PASSWORD_MIN_LENGTH=8      # minimum password characters

# Optional
TIMEZONE=Asia/Kolkata
SESSION_NAME=MOTOTRACK_SESSION
```

---

## 🔍 Troubleshooting Quick Fixes

### Problem: "Service temporarily unavailable"
**Solution:**
1. Check `.env` database credentials
2. Verify MySQL is running
3. Check `logs/php_errors.log`

### Problem: "Invalid CSRF token"
**Solution:**
1. Clear browser cookies
2. Try again
3. Check session is working

### Problem: "Too many login attempts"
**Solution:**
1. Wait 15 minutes
2. Or clear browser cookies
3. This is security feature working correctly

### Problem: Pages show PHP errors
**Solution:**
1. Set `APP_DEBUG=false` in `.env`
2. Check `logs/php_errors.log` for actual errors

### Problem: Cannot access pages after login
**Solution:**
1. Check `SESSION_TIMEOUT` setting
2. Verify cookies are enabled
3. Check browser console for errors

---

## 📊 Database Quick Commands

### Backup Database
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u username -p database_name < backup_file.sql
```

### Import Initial Schema
```bash
mysql -u username -p database_name < database/schema.sql
```

### Change Admin Password (SQL)
```sql
UPDATE users 
SET password = '$2y$10$your_new_hash_here' 
WHERE username = 'admin';
```
*Generate hash at: https://bcrypt-generator.com/*

---

## 📁 Important File Locations

```
.env                    → Main configuration
logs/activity_*.log     → User activity logs
logs/php_errors.log     → PHP error logs
database/schema.sql     → Database structure
config/config.php       → Application settings
.htaccess              → Apache security rules
```

---

## 🛡️ Security Quick Checks

### Quick Security Audit (5 minutes)
```bash
# 1. Check if .env is protected
curl https://yoursite.com/.env
# Should get: 403 Forbidden

# 2. Check security headers
curl -I https://yoursite.com/
# Should see: X-Frame-Options, X-XSS-Protection, etc.

# 3. Check logs for suspicious activity
tail -n 50 logs/activity_$(date +%Y-%m-%d).log | grep FAILED

# 4. Verify HTTPS
curl -I http://yoursite.com/
# Should redirect to HTTPS
```

---

## 🔧 Common Code Tasks

### Add CSRF Token to New Form
```php
<form method="POST">
    <?php echo csrfField(); ?>
    <!-- your form fields -->
</form>

// In POST handler:
if (!validateCSRFToken($_POST['csrf_token'])) {
    $error = 'Invalid request';
}
```

### Escape Output (Prevent XSS)
```php
// Always use e() for user data
echo e($user_input);
echo e($_POST['username']);
echo e($database_value);
```

### Validate Input
```php
// Email
if (!validateInput($email, 'email')) {
    $error = 'Invalid email';
}

// Number
if (!validateInput($age, 'number')) {
    $error = 'Must be a number';
}

// Phone
if (!validateInput($phone, 'phone')) {
    $error = 'Invalid phone number';
}
```

### Log Activity
```php
logActivity(
    getUserId(),           // user ID
    'ACTION_NAME',        // action
    'Details here',       // details
    'INFO'               // level: INFO, WARNING, ERROR
);
```

---

## 📞 Emergency Procedures

### If Site is Compromised

**Immediate Actions:**
1. Change all passwords immediately
2. Review logs: `logs/activity_*.log`
3. Check for modified files
4. Restore from clean backup
5. Contact hosting provider

**Investigation:**
```bash
# Check recent file modifications
find . -type f -mtime -1 -ls

# Check suspicious activity in logs
grep -i "suspicious\|attack\|injection" logs/*.log

# Review all admin users
mysql -u user -p -e "SELECT * FROM users WHERE role='admin'"
```

---

## 💡 Performance Tips

### Clear Old Logs (Manual)
```bash
# Keep last 30 days only
find logs/ -name "activity_*.log" -mtime +30 -delete
```

### Database Optimization
```sql
-- Run monthly
OPTIMIZE TABLE users, bikes, fuel_logs, service_records, reminders;

-- Check indexes
SHOW INDEX FROM users;
```

### Monitor Disk Space
```bash
df -h
du -sh logs/
```

---

## 📚 Helpful Functions Reference

### Security Functions
```php
sanitizeInput($data)              // Sanitize user input
e($string)                        // Escape output (XSS)
validateInput($data, $type)       // Validate input
generateCSRFToken()               // Get CSRF token
validateCSRFToken($token)         // Check CSRF token
csrfField()                       // CSRF input field
validatePasswordStrength($pass)   // Check password
```

### Auth Functions
```php
isLoggedIn()                      // Check if user logged in
requireLogin()                    // Redirect if not logged in
getUserId()                       // Get current user ID
checkSessionTimeout()             // Check session expiry
```

### Helper Functions
```php
formatDate($date)                 // Format date for display
formatCurrency($amount)           // Format money
setFlashMessage($msg, $type)      // Set flash message
getFlashMessage()                 // Get & clear flash message
redirect($url)                    // Redirect to URL
logActivity($user, $action, ...)  // Log user activity
```

---

## 🎯 Regular Maintenance Checklist

### Daily (2 minutes)
- [ ] Check for critical errors: `tail logs/php_errors.log`
- [ ] Review failed logins: `grep FAILED logs/activity_*.log`

### Weekly (10 minutes)
- [ ] Review all activity logs
- [ ] Check disk space usage
- [ ] Verify backups are running
- [ ] Check for software updates

### Monthly (30 minutes)
- [ ] Full security review
- [ ] Database optimization
- [ ] Log cleanup
- [ ] Password rotation reminder
- [ ] Performance audit

---

## 📱 Contact Info Template

```
Application: MotoTrack
Version: 1.0.0
URL: _________________
Admin: _________________
Hosting: _________________
Database: _________________
Support: _________________
```

---

## ⚡ One-Liner Solutions

```bash
# Check if app is up
curl -I https://yoursite.com/

# Count failed logins today
grep -c "LOGIN_FAILED" logs/activity_$(date +%Y-%m-%d).log

# Find big log files
find logs/ -size +10M -exec ls -lh {} \;

# Check PHP version
php -v

# Test database connection
mysql -u username -p -e "SELECT 1"

# View last 20 activities
tail -n 20 logs/activity_$(date +%Y-%m-%d).log
```

---

**Keep this guide handy for quick reference!**

*Last Updated: October 15, 2025*
