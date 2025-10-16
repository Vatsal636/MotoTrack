#!/bin/bash
# MotoTrack - Production Readiness Verification Script
# Run this script to verify all security features are working

echo "======================================"
echo "MotoTrack - Security Verification"
echo "======================================"
echo ""

ERRORS=0
WARNINGS=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
echo "1. Checking configuration files..."
if [ -f .env ]; then
    echo -e "${GREEN}✓${NC} .env file exists"
else
    echo -e "${RED}✗${NC} .env file missing!"
    ERRORS=$((ERRORS + 1))
fi

# Check if .htaccess exists
if [ -f .htaccess ]; then
    echo -e "${GREEN}✓${NC} .htaccess file exists"
else
    echo -e "${RED}✗${NC} .htaccess file missing!"
    ERRORS=$((ERRORS + 1))
fi

# Check logs directory
echo ""
echo "2. Checking logs directory..."
if [ -d logs ]; then
    echo -e "${GREEN}✓${NC} logs/ directory exists"
    if [ -w logs ]; then
        echo -e "${GREEN}✓${NC} logs/ directory is writable"
    else
        echo -e "${YELLOW}⚠${NC} logs/ directory not writable"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}✗${NC} logs/ directory missing!"
    ERRORS=$((ERRORS + 1))
fi

# Check .env configuration
echo ""
echo "3. Checking .env configuration..."
if grep -q "APP_DEBUG=false" .env 2>/dev/null; then
    echo -e "${GREEN}✓${NC} APP_DEBUG is set to false"
elif grep -q "APP_DEBUG=true" .env 2>/dev/null; then
    echo -e "${YELLOW}⚠${NC} APP_DEBUG is set to true (should be false in production)"
    WARNINGS=$((WARNINGS + 1))
else
    echo -e "${RED}✗${NC} APP_DEBUG not configured"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "APP_ENV=production" .env 2>/dev/null; then
    echo -e "${GREEN}✓${NC} APP_ENV is set to production"
elif grep -q "APP_ENV=development" .env 2>/dev/null; then
    echo -e "${YELLOW}⚠${NC} APP_ENV is set to development (should be production)"
    WARNINGS=$((WARNINGS + 1))
else
    echo -e "${RED}✗${NC} APP_ENV not configured"
    ERRORS=$((ERRORS + 1))
fi

# Check database configuration
if grep -q "DB_PASS=your_database_password" .env 2>/dev/null; then
    echo -e "${YELLOW}⚠${NC} Database password still has default value"
    WARNINGS=$((WARNINGS + 1))
elif grep -q "DB_PASS=" .env 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Database password configured"
else
    echo -e "${RED}✗${NC} Database password not configured"
    ERRORS=$((ERRORS + 1))
fi

# Check file permissions
echo ""
echo "4. Checking file permissions..."
if [ -f .env ]; then
    PERMS=$(stat -c %a .env 2>/dev/null || stat -f %A .env 2>/dev/null)
    if [ "$PERMS" = "600" ] || [ "$PERMS" = "400" ]; then
        echo -e "${GREEN}✓${NC} .env has secure permissions ($PERMS)"
    else
        echo -e "${YELLOW}⚠${NC} .env permissions are $PERMS (should be 600)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

# Check documentation
echo ""
echo "5. Checking documentation..."
DOCS=("README.md" "SECURITY_CHECKLIST.md" "QUICK_REFERENCE.md" "STATUS_REPORT.md" "DOCUMENTATION_INDEX.md")
for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo -e "${GREEN}✓${NC} $doc exists"
    else
        echo -e "${RED}✗${NC} $doc missing!"
        ERRORS=$((ERRORS + 1))
    fi
done

# Check security files
echo ""
echo "6. Checking security implementation..."
SECURITY_FILES=("config/config.php" "config/database.php" "config/env.php")
for file in "${SECURITY_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file exists"
    else
        echo -e "${RED}✗${NC} $file missing!"
        ERRORS=$((ERRORS + 1))
    fi
done

# Check if CSRF functions exist
if grep -q "generateCSRFToken" config/config.php 2>/dev/null; then
    echo -e "${GREEN}✓${NC} CSRF protection functions found"
else
    echo -e "${RED}✗${NC} CSRF protection functions missing!"
    ERRORS=$((ERRORS + 1))
fi

# Check if rate limiting exists
if grep -q "checkLoginAttempts" config/config.php 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Rate limiting functions found"
else
    echo -e "${RED}✗${NC} Rate limiting functions missing!"
    ERRORS=$((ERRORS + 1))
fi

# Check if activity logging exists
if grep -q "logActivity" config/config.php 2>/dev/null; then
    echo -e "${GREEN}✓${NC} Activity logging function found"
else
    echo -e "${RED}✗${NC} Activity logging function missing!"
    ERRORS=$((ERRORS + 1))
fi

# Summary
echo ""
echo "======================================"
echo "Verification Summary"
echo "======================================"
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo "Your application is production-ready!"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ $WARNINGS warning(s) found${NC}"
    echo "Review warnings before deploying to production"
else
    echo -e "${RED}✗ $ERRORS error(s) and $WARNINGS warning(s) found${NC}"
    echo "Please fix errors before deploying to production"
fi

echo ""
echo "Next steps:"
echo "1. Review .env configuration"
echo "2. Import database/schema.sql"
echo "3. Change default admin password"
echo "4. Follow SECURITY_CHECKLIST.md"
echo "5. Test all features"
echo ""

exit $ERRORS
