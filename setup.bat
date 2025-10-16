@echo off
REM MotoTrack - Production Setup Script (Windows)
REM This script helps set up the application for production deployment

echo ==================================
echo MotoTrack Production Setup
echo ==================================
echo.

REM Check if .env exists
if exist .env (
    echo [OK] .env file already exists
) else (
    echo Creating .env file from template...
    copy .env.example .env
    echo [OK] .env file created
    echo [WARNING] IMPORTANT: Edit .env file with your production credentials!
)

REM Create logs directory
if not exist logs mkdir logs
echo [OK] Logs directory ready

echo.
echo ==================================
echo Setup Complete!
echo ==================================
echo.
echo Next steps:
echo 1. Edit .env file with your database credentials
echo 2. Import database/schema.sql into your MySQL database
echo 3. Update APP_URL in .env to your domain
echo 4. Set APP_ENV=production and APP_DEBUG=false
echo 5. Change default admin password in database
echo 6. Test the application thoroughly
echo.
echo Security Checklist:
echo [ ] Updated database credentials in .env
echo [ ] Changed default admin password
echo [ ] Enabled HTTPS/SSL
echo [ ] Verified .htaccess is working
echo [ ] Set APP_DEBUG=false
echo [ ] Tested all security features
echo.
pause
