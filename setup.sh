#!/bin/bash
# MotoTrack - Production Setup Script
# This script helps set up the application for production deployment

echo "=================================="
echo "MotoTrack Production Setup"
echo "=================================="
echo ""

# Check if .env exists
if [ -f .env ]; then
    echo "✓ .env file already exists"
else
    echo "Creating .env file from template..."
    cp .env.example .env
    echo "✓ .env file created"
    echo "⚠️  IMPORTANT: Edit .env file with your production credentials!"
fi

# Create logs directory
if [ ! -d logs ]; then
    mkdir -p logs
    echo "✓ Created logs directory"
fi

# Set permissions
echo ""
echo "Setting file permissions..."
chmod 755 logs/
chmod 600 .env 2>/dev/null || echo "⚠️  Could not set .env permissions (might need sudo)"
chmod 644 .htaccess 2>/dev/null || echo "⚠️  Could not set .htaccess permissions"

echo ""
echo "=================================="
echo "Setup Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Edit .env file with your database credentials"
echo "2. Import database/schema.sql into your MySQL database"
echo "3. Update APP_URL in .env to your domain"
echo "4. Set APP_ENV=production and APP_DEBUG=false"
echo "5. Change default admin password in database"
echo "6. Test the application thoroughly"
echo ""
echo "Security Checklist:"
echo "- [ ] Updated database credentials in .env"
echo "- [ ] Changed default admin password"
echo "- [ ] Enabled HTTPS/SSL"
echo "- [ ] Verified .htaccess is working"
echo "- [ ] Set APP_DEBUG=false"
echo "- [ ] Tested all security features"
echo ""
