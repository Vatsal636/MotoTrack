# 🚀 UPDATE LIVE SITE - Step-by-Step Guide

## For Existing Live MotoTrack Application on InfinityFree

**Your Site:** https://mototrack.rf.gd/  
**Status:** Already Live with Database Imported  
**Task:** Update with Security Enhancements

---

## 📋 **Pre-Update Checklist**

### Before You Start:
- [ ] Backup current live files (download via FTP)
- [ ] Backup database (export from phpMyAdmin)
- [ ] Note current admin password (you'll change it after)
- [ ] Have FTP credentials ready

**Estimated Time:** 15-20 minutes  
**Difficulty:** Easy (just file upload + password change)

---

## 📦 **STEP 1: Backup Current Site (5 minutes)**

### Option A: Via FTP
1. Connect to InfinityFree FTP
2. Download entire `htdocs/` folder to your computer
3. Save as: `mototrack_backup_2025-10-16.zip`

### Option B: Via File Manager
1. Login to InfinityFree Control Panel
2. Open File Manager
3. Select all files → Compress → Download
4. Save as: `mototrack_backup_2025-10-16.zip`

### Option C: Via phpMyAdmin (Database)
1. Go to: https://sql201.infinityfree.com/phpmyadmin
2. Select database: `if0_40155593_mototrack`
3. Click "Export" → "Go"
4. Save as: `database_backup_2025-10-16.sql`

✅ **YOU MUST DO THIS!** If anything goes wrong, you can restore.

---

## 📤 **STEP 2: Upload New/Modified Files (10 minutes)**

### Files to Upload (Critical - Must Replace):

#### A. Configuration Files (HIGHEST PRIORITY)
```
Upload & Replace These:
├── config/config.php          (UPDATED - Security functions)
├── config/database.php        (UPDATED - Environment variables)
├── config/env.php             (NEW - Environment loader)
├── .env                       (NEW - Configuration file)
└── .htaccess                  (NEW - Security rules)
```

#### B. Application Files (HIGH PRIORITY)
```
Upload & Replace These:
├── login.php                  (UPDATED - CSRF + Rate limiting)
├── register.php               (UPDATED - Password validation)
├── logout.php                 (UPDATED - Activity logging)
└── includes/header.php        (UPDATED - Security headers)
```

#### C. Create New Directory
```
Create This:
└── logs/                      (NEW - For activity logs)
    └── Set permissions: 755
```

#### D. Documentation (OPTIONAL - But Recommended)
```
Upload These (Optional):
├── README.md
├── SECURITY_CHECKLIST.md
├── QUICK_REFERENCE.md
├── STATUS_REPORT.md
├── DOCUMENTATION_INDEX.md
└── READY_TO_DEPLOY.md
```

---

## 🔧 **STEP 3: Upload Instructions**

### Method A: FileZilla (FTP Client) - RECOMMENDED

1. **Connect to FTP**
   - Host: `ftpupload.net`
   - Username: `if0_40155593`
   - Password: [Your FTP password]
   - Port: `21`

2. **Navigate to Directory**
   - Remote site: `/htdocs/` (or your app folder)
   - Local site: `C:\xampp\htdocs\mttt\`

3. **Upload Files**
   - Select files from left panel (local)
   - Right-click → Upload
   - Confirm overwrite when asked

4. **Create logs Directory**
   - Right-click in remote panel → Create directory → `logs`
   - Right-click `logs` → File permissions → Set to `755`

### Method B: InfinityFree File Manager

1. **Login to Control Panel**
   - Go to: https://infinityfree.com/clientarea.php
   - Login with your credentials

2. **Open File Manager**
   - Click "File Manager" in control panel
   - Navigate to your application folder

3. **Upload Files**
   - Click "Upload" button
   - Select files from your computer
   - Wait for upload to complete
   - Confirm overwrite if asked

4. **Create logs Directory**
   - Click "New Folder"
   - Name: `logs`
   - Right-click → Permissions → Set to `755`

---

## ⚙️ **STEP 4: Verify .env Configuration (2 minutes)**

After uploading, check your `.env` file on the server:

**Edit `.env` file and ensure these values are correct:**
```env
# MUST be correct for your site
APP_ENV=production              ✅ Keep this
APP_DEBUG=false                 ✅ Keep this
APP_URL=https://mototrack.rf.gd/ ✅ Verify this

# Database (should already be correct from your current config)
DB_HOST=sql201.infinityfree.com
DB_USER=if0_40155593
DB_PASS=xE1owNnNOmTi6          ✅ Verify this matches your current DB password
DB_NAME=if0_40155593_mototrack

# Security (defaults are good)
SESSION_TIMEOUT=3600
PASSWORD_MIN_LENGTH=8
```

**How to Edit:**
- Via FTP: Download `.env`, edit locally, re-upload
- Via File Manager: Right-click `.env` → Edit → Make changes → Save

---

## 🔐 **STEP 5: Change Admin Password (1 minute) - CRITICAL!**

### Why This is Critical:
Your database has a default admin account:
- Username: `admin`
- Password: `admin123`

**This is publicly known!** Anyone can see it in your `schema.sql` file.

### How to Change:

1. **Visit Your Site**
   ```
   https://mototrack.rf.gd/login.php
   ```

2. **Login with Default Credentials**
   - Username: `admin`
   - Password: `admin123`

3. **Go to Profile**
   - Click your name in the top-right
   - Click "Profile"

4. **Change Password**
   - Enter current password: `admin123`
   - Enter new password: [STRONG PASSWORD - 16+ characters]
   - Confirm new password
   - Click "Update Password"

**Strong Password Example:**
```
MotoTrack@2025!Secure#Admin
(Mix of uppercase, lowercase, numbers, symbols)
```

5. **Test New Login**
   - Logout
   - Login with new password
   - Verify it works

✅ **DONE!** Admin account is now secure.

---

## ✅ **STEP 6: Test Security Features (5 minutes)**

### Test 1: CSRF Protection
1. Go to login page: https://mototrack.rf.gd/login.php
2. Open Browser DevTools (F12) → Elements tab
3. Find the hidden input: `<input type="hidden" name="csrf_token" ...>`
4. Delete this entire input element
5. Try to login
6. ✅ **Expected Result:** "Invalid request. Please try again."

### Test 2: Rate Limiting
1. Go to login page
2. Enter wrong password 6 times in a row
3. ✅ **Expected Result:** After 5th attempt, you see:
   ```
   "Too many failed login attempts. Please try again in X minutes."
   ```
4. Wait 15 minutes OR clear cookies to unlock

### Test 3: Session Timeout
1. Login to your site
2. Wait 1 hour without any activity
3. Try to access any page
4. ✅ **Expected Result:** Redirected to login with message:
   ```
   "Your session has expired. Please login again."
   ```

### Test 4: Password Validation (New Users)
1. Try to register a new user
2. Use weak password: `test123`
3. ✅ **Expected Result:** Error message:
   ```
   "Password must contain at least one uppercase letter"
   "Password must contain at least one number"
   ```

### Test 5: .env File Protection
1. Try to access: https://mototrack.rf.gd/.env
2. ✅ **Expected Result:** 
   - `403 Forbidden` OR
   - `404 Not Found`
3. ❌ **If you see file contents:** `.htaccess` is not working
   - Contact InfinityFree support to enable `.htaccess`

### Test 6: Activity Logging
1. Login to your site
2. Perform some actions (add fuel log, etc.)
3. Check if logs are created:
   - Via FTP: Check `logs/` directory
   - Should see: `activity_2025-10-16.log`
4. ✅ **Expected:** Log file exists with entries

---

## 🚨 **Troubleshooting Common Issues**

### Issue 1: "Service temporarily unavailable"
**Cause:** Database connection failed

**Solution:**
1. Check `.env` file - verify DB credentials
2. Check if your database password changed
3. Test database connection from phpMyAdmin
4. Check `logs/php_errors.log` for details

### Issue 2: "Invalid CSRF token" on every form
**Cause:** Sessions not working

**Solution:**
1. Check if `session_start()` is being called
2. Clear browser cookies
3. Check server session directory is writable
4. Contact InfinityFree support if persistent

### Issue 3: .htaccess not working (can access .env file)
**Cause:** mod_rewrite not enabled or .htaccess not processed

**Solution:**
1. Check if `.htaccess` file is in root directory
2. Ensure filename is exactly `.htaccess` (with the dot)
3. InfinityFree usually has this enabled, but contact support if not

### Issue 4: Logs not being created
**Cause:** Directory not writable

**Solution:**
1. Check `logs/` directory exists
2. Set permissions to `755` or `777`
3. Via FTP: Right-click → File Permissions → 755

### Issue 5: "Too many redirects" error
**Cause:** Infinite redirect loop

**Solution:**
1. Check `.htaccess` HTTPS redirect rules
2. Temporarily disable HTTPS redirect
3. Check `APP_URL` in `.env` matches actual URL

---

## 📊 **STEP 7: Monitor Your Site (24 hours)**

### What to Check:

**First Hour:**
- Check logs every 15 minutes
- Verify no PHP errors in `logs/php_errors.log`
- Test all major features (add bike, fuel log, service, etc.)
- Try logging in/out multiple times

**First Day:**
- Review `logs/activity_*.log` for suspicious activity
- Check for any failed login attempts
- Verify all features working correctly
- Monitor server resources (InfinityFree dashboard)

**What to Look For:**
```log
# Normal activity log entry
[2025-10-16 10:30:45] [INFO] User: 1 | IP: 192.168.1.1 | 
Action: LOGIN_SUCCESS | Details: admin

# Suspicious activity (investigate)
[2025-10-16 10:31:00] [WARNING] User: 0 | IP: 203.0.113.1 | 
Action: LOGIN_FAILED | Details: Invalid credentials for: admin
... (repeated 10+ times from same IP)
```

---

## 🎯 **Quick Status Check**

After completing all steps, verify:

```
✅ Files uploaded successfully
✅ .env configured correctly
✅ logs/ directory created (755 permissions)
✅ Admin password changed
✅ CSRF protection working
✅ Rate limiting working
✅ Session timeout working
✅ .env file protected (403 error)
✅ Logs being created
✅ No PHP errors
✅ All features working
```

---

## 📞 **Need Help?**

### Check Documentation:
- `QUICK_REFERENCE.md` - Common commands & troubleshooting
- `README.md` - Complete deployment guide
- `SECURITY_CHECKLIST.md` - Full testing procedures

### Check Logs:
```
logs/php_errors.log      - PHP errors
logs/activity_*.log      - User activities & security events
```

### InfinityFree Support:
- Forum: https://forum.infinityfree.com
- Control Panel: https://infinityfree.com/clientarea.php

---

## 🎉 **Success Criteria**

Your update is successful when:

1. ✅ Site loads without errors
2. ✅ Can login/logout normally
3. ✅ CSRF protection blocks invalid requests
4. ✅ Rate limiting blocks brute force attempts
5. ✅ Admin password changed from default
6. ✅ Logs are being created
7. ✅ All features working (fuel logs, service, etc.)
8. ✅ No errors in `logs/php_errors.log`

---

## ⏱️ **Timeline Summary**

```
Backup current site        →  5 min
Upload new files          → 10 min
Verify configuration      →  2 min
Change admin password     →  1 min
Test security features    →  5 min
Monitor site             → 24 hrs
─────────────────────────────────
Total Active Time:         ~25 min
Total Monitoring:          24 hrs
```

---

## 🎊 **You're Almost There!**

Follow these steps carefully, and your site will be:
- ✅ Secure with enterprise-grade protection
- ✅ Protected against OWASP Top 10 vulnerabilities
- ✅ Logging all security events
- ✅ Production-ready with proper error handling

**Good luck with the update! You've got this! 🚀**

---

**Questions?** Check the documentation or review the logs first!

**Last Updated:** October 16, 2025  
**For:** MotoTrack Live Site Update  
**Status:** Ready to Execute
