@echo off
REM MotoTrack - Production Readiness Verification Script (Windows)
REM Run this script to verify all security features are working

echo ======================================
echo MotoTrack - Security Verification
echo ======================================
echo.

set ERRORS=0
set WARNINGS=0

REM Check if .env exists
echo 1. Checking configuration files...
if exist .env (
    echo [OK] .env file exists
) else (
    echo [ERROR] .env file missing!
    set /a ERRORS+=1
)

REM Check if .htaccess exists
if exist .htaccess (
    echo [OK] .htaccess file exists
) else (
    echo [ERROR] .htaccess file missing!
    set /a ERRORS+=1
)

REM Check logs directory
echo.
echo 2. Checking logs directory...
if exist logs\ (
    echo [OK] logs\ directory exists
) else (
    echo [ERROR] logs\ directory missing!
    set /a ERRORS+=1
)

REM Check .env configuration
echo.
echo 3. Checking .env configuration...
findstr /C:"APP_DEBUG=false" .env >nul 2>&1
if %errorlevel%==0 (
    echo [OK] APP_DEBUG is set to false
) else (
    findstr /C:"APP_DEBUG=true" .env >nul 2>&1
    if %errorlevel%==0 (
        echo [WARNING] APP_DEBUG is set to true (should be false in production^)
        set /a WARNINGS+=1
    ) else (
        echo [ERROR] APP_DEBUG not configured
        set /a ERRORS+=1
    )
)

findstr /C:"APP_ENV=production" .env >nul 2>&1
if %errorlevel%==0 (
    echo [OK] APP_ENV is set to production
) else (
    findstr /C:"APP_ENV=development" .env >nul 2>&1
    if %errorlevel%==0 (
        echo [WARNING] APP_ENV is set to development (should be production^)
        set /a WARNINGS+=1
    ) else (
        echo [ERROR] APP_ENV not configured
        set /a ERRORS+=1
    )
)

findstr /C:"DB_PASS=your_database_password" .env >nul 2>&1
if %errorlevel%==0 (
    echo [WARNING] Database password still has default value
    set /a WARNINGS+=1
    goto :checkdocs
)

findstr /C:"DB_PASS=your_" .env >nul 2>&1
if %errorlevel%==0 (
    echo [WARNING] Database password appears to be a placeholder
    set /a WARNINGS+=1
    goto :checkdocs
)

findstr /C:"DB_PASS=" .env >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Database password configured
) else (
    echo [ERROR] Database password not configured
    set /a ERRORS+=1
)

:checkdocs

REM Check documentation
echo.
echo 4. Checking documentation...
if exist README.md (echo [OK] README.md exists) else (echo [ERROR] README.md missing! & set /a ERRORS+=1)
if exist SECURITY_CHECKLIST.md (echo [OK] SECURITY_CHECKLIST.md exists) else (echo [ERROR] SECURITY_CHECKLIST.md missing! & set /a ERRORS+=1)
if exist QUICK_REFERENCE.md (echo [OK] QUICK_REFERENCE.md exists) else (echo [ERROR] QUICK_REFERENCE.md missing! & set /a ERRORS+=1)
if exist STATUS_REPORT.md (echo [OK] STATUS_REPORT.md exists) else (echo [ERROR] STATUS_REPORT.md missing! & set /a ERRORS+=1)
if exist DOCUMENTATION_INDEX.md (echo [OK] DOCUMENTATION_INDEX.md exists) else (echo [ERROR] DOCUMENTATION_INDEX.md missing! & set /a ERRORS+=1)

REM Check security files
echo.
echo 5. Checking security implementation...
if exist config\config.php (echo [OK] config\config.php exists) else (echo [ERROR] config\config.php missing! & set /a ERRORS+=1)
if exist config\database.php (echo [OK] config\database.php exists) else (echo [ERROR] config\database.php missing! & set /a ERRORS+=1)
if exist config\env.php (echo [OK] config\env.php exists) else (echo [ERROR] config\env.php missing! & set /a ERRORS+=1)

findstr /C:"generateCSRFToken" config\config.php >nul 2>&1
if %errorlevel%==0 (
    echo [OK] CSRF protection functions found
) else (
    echo [ERROR] CSRF protection functions missing!
    set /a ERRORS+=1
)

findstr /C:"checkLoginAttempts" config\config.php >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Rate limiting functions found
) else (
    echo [ERROR] Rate limiting functions missing!
    set /a ERRORS+=1
)

findstr /C:"logActivity" config\config.php >nul 2>&1
if %errorlevel%==0 (
    echo [OK] Activity logging function found
) else (
    echo [ERROR] Activity logging function missing!
    set /a ERRORS+=1
)

REM Summary
echo.
echo ======================================
echo Verification Summary
echo ======================================
if %ERRORS%==0 if %WARNINGS%==0 (
    echo [SUCCESS] All checks passed!
    echo Your application is production-ready!
) else if %ERRORS%==0 (
    echo [WARNING] %WARNINGS% warning(s^) found
    echo Review warnings before deploying to production
) else (
    echo [FAILED] %ERRORS% error(s^) and %WARNINGS% warning(s^) found
    echo Please fix errors before deploying to production
)

echo.
echo Next steps:
echo 1. Review .env configuration
echo 2. Import database/schema.sql
echo 3. Change default admin password
echo 4. Follow SECURITY_CHECKLIST.md
echo 5. Test all features
echo.

pause
exit /b %ERRORS%
