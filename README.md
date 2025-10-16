# MotoTrack - Bike Tracker Application

A comprehensive web application for tracking your motorcycle's fuel consumption, service records, reminders, and maintenance history.

## 🚀 Production Deployment Guide

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- HTTPS/SSL certificate (strongly recommended)

### Installation Steps

#### 1. Upload Files
Upload all files to your web server's public directory.

#### 2. Configure Environment
```bash
# Copy the example environment file
cp .env.example .env

# Edit .env with your actual configuration
nano .env
```

**Important**: Update these values in `.env`:
- `APP_URL` - Your application URL
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` - Your database credentials
- Set `APP_ENV=production` and `APP_DEBUG=false` for production

#### 3. Set Up Database
```bash
# Import the database schema
mysql -u your_username -p your_database < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`

#### 4. Set File Permissions
```bash
# Make logs directory writable
chmod 755 logs/
chmod 664 logs/*.log

# Protect sensitive files
chmod 600 .env
chmod 644 .htaccess
```

#### 5. Security Checklist

✅ **Before Going Live:**
- [ ] Change all default passwords in database
- [ ] Update `.env` with production credentials
- [ ] Ensure `APP_DEBUG=false` in production
- [ ] Verify `.htaccess` is working (try accessing `.env` - should be blocked)
- [ ] Enable HTTPS/SSL certificate
- [ ] Remove or secure phpMyAdmin access
- [ ] Set up automatic database backups
- [ ] Test all security features (CSRF, rate limiting, session timeout)
- [ ] Review and update `CSP` headers in `.htaccess` if needed
- [ ] Ensure `.git` directory is not publicly accessible

#### 6. Post-Deployment Testing
1. Test user registration with strong password requirements
2. Test login with rate limiting (try 5+ wrong passwords)
3. Test session timeout (wait 1 hour of inactivity)
4. Test CSRF protection (modify form tokens)
5. Test all CRUD operations (Create, Read, Update, Delete)
6. Check activity logs in `logs/` directory

### 🔒 Security Features

**Implemented:**
- ✅ CSRF Protection on all forms
- ✅ Password hashing with bcrypt
- ✅ SQL Injection prevention (Prepared statements)
- ✅ XSS Protection (Output escaping)
- ✅ Session timeout enforcement
- ✅ Login rate limiting (5 attempts, 15-min lockout)
- ✅ Strong password requirements (8+ chars, uppercase, lowercase, number)
- ✅ Security headers (X-XSS-Protection, X-Frame-Options, etc.)
- ✅ Activity logging and audit trail
- ✅ Environment-based configuration
- ✅ Input validation and sanitization

### 📁 Directory Structure

```
mttt/
├── assets/
│   ├── css/
│   └── js/
├── config/
│   ├── config.php          # Main configuration
│   ├── database.php        # Database connection
│   └── env.php            # Environment loader
├── database/
│   └── schema.sql         # Database schema
├── includes/
│   ├── header.php
│   └── footer.php
├── logs/                  # Activity logs (writable)
├── .env                   # Environment config (DO NOT COMMIT)
├── .env.example          # Environment template
├── .htaccess             # Apache configuration
├── .gitignore            # Git ignore rules
└── *.php                 # Application files
```

### 🔧 Configuration Options

**Environment Variables (.env):**

| Variable | Description | Default |
|----------|-------------|---------|
| APP_NAME | Application name | MotoTrack |
| APP_ENV | Environment (production/development) | production |
| APP_DEBUG | Debug mode (true/false) | false |
| APP_URL | Application URL | - |
| DB_HOST | Database host | localhost |
| DB_USER | Database username | - |
| DB_PASS | Database password | - |
| DB_NAME | Database name | - |
| SESSION_TIMEOUT | Session timeout in seconds | 3600 |
| PASSWORD_MIN_LENGTH | Minimum password length | 8 |

### 📊 Features

- **Dashboard**: Overview of bike statistics and recent activities
- **Bike Management**: Add and manage multiple bikes
- **Fuel Logs**: Track fuel consumption and calculate mileage
- **Service Records**: Maintain service history with cost tracking
- **Reminders**: Set maintenance and service reminders
- **Reports**: Generate expense and mileage reports
- **User Profile**: Manage account settings

### 🛠️ Maintenance

**Regular Tasks:**
1. **Backup Database**: Daily automated backups recommended
2. **Review Logs**: Check `logs/` directory for suspicious activity
3. **Update Software**: Keep PHP and MySQL updated
4. **Monitor Performance**: Check server resources and query performance
5. **Security Audits**: Regular security reviews

**Log Files:**
- `logs/activity_YYYY-MM-DD.log` - User activity logs
- `logs/php_errors.log` - PHP error logs

### 🐛 Troubleshooting

**Common Issues:**

1. **"Service temporarily unavailable"**
   - Check database credentials in `.env`
   - Verify database server is running
   - Check `logs/php_errors.log` for details

2. **"Invalid CSRF token"**
   - Clear browser cookies and try again
   - Ensure sessions are working properly

3. **"Too many login attempts"**
   - Wait 15 minutes or clear session cookies
   - This is a security feature to prevent brute force attacks

4. **404 Errors on pages**
   - Verify `.htaccess` is present and mod_rewrite is enabled
   - Check file permissions

5. **Session timeout too quick**
   - Adjust `SESSION_TIMEOUT` in `.env` file

### 🔐 Default Credentials

**Important**: The database schema includes a default admin account:
- Username: `admin`
- Password: `admin123`

**⚠️ CRITICAL**: Change this password immediately after installation!

### 📝 Changelog

**Version 1.0.0 (Production Ready)**
- Added CSRF protection
- Implemented rate limiting
- Enhanced password requirements
- Added activity logging
- Environment-based configuration
- Security headers implementation
- Input validation improvements
- Output escaping for XSS protection
- Session timeout enforcement

### 📞 Support

For issues or questions:
1. Check logs in `logs/` directory
2. Review this README
3. Check `.htaccess` configuration
4. Verify environment settings

### 📜 License

Proprietary - All rights reserved

### ⚠️ Important Security Notes

1. **Never commit `.env` to version control**
2. **Always use HTTPS in production**
3. **Regularly update passwords**
4. **Keep PHP and MySQL updated**
5. **Monitor activity logs for suspicious behavior**
6. **Set up automated backups**
7. **Use strong database passwords**
8. **Restrict database access to application only**

---

**Deployed with ❤️ by MotoTrack Team**
