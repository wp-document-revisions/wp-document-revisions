#!/bin/bash
# Development environment setup script for WP Document Revisions
# This script runs automatically when the devcontainer is created
#
# Note: This script uses simple passwords for local development only.
# These credentials are already defined in docker-compose.yml and are
# not intended for production use.

set -e

echo "=========================================="
echo "Setting up WP Document Revisions Development Environment"
echo "=========================================="

# Navigate to workspace
cd /workspaces/wp-document-revisions

# Install PHP dependencies via Composer
echo ""
echo "Installing PHP dependencies..."
if [ -f "composer.json" ]; then
    composer install --optimize-autoloader --prefer-dist --no-interaction
    echo "✓ Composer dependencies installed"
else
    echo "⚠ No composer.json found"
fi

# Install Node.js dependencies
echo ""
echo "Installing Node.js dependencies..."
if [ -f "package.json" ]; then
    npm install
    echo "✓ Node.js dependencies installed"
else
    echo "⚠ No package.json found"
fi

# Wait for database to be ready
echo ""
echo "Waiting for database to be ready..."
max_attempts=30
attempt=0
until MYSQL_PWD=wordpress mysql -h db -u wordpress -e "SELECT 1" &> /dev/null || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "Waiting for database... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "⚠ Database not ready after $max_attempts attempts. You may need to start it manually."
else
    echo "✓ Database is ready"
    
    # Create test database for PHPUnit
    echo ""
    echo "Setting up test database..."
    MYSQL_PWD=wordpress mysql -h db -u wordpress -e "CREATE DATABASE IF NOT EXISTS wordpress_test;" 2>/dev/null || true
    echo "✓ Test database created"
fi

# Install WordPress test environment
echo ""
echo "Installing WordPress test environment..."
if [ -f "script/install-wp-tests" ]; then
    bash script/install-wp-tests wordpress_test wordpress wordpress db latest || {
        echo "⚠ WordPress test installation failed. You may need to run it manually."
    }
    if [ -d "/tmp/wordpress-tests-lib" ]; then
        echo "✓ WordPress test environment installed"
    fi
else
    echo "⚠ WordPress test installation script not found"
fi

# Link plugin to WordPress installation
echo ""
echo "Setting up WordPress plugin link..."
WORDPRESS_PLUGINS_DIR="/var/www/html/wp-content/plugins"
PLUGIN_LINK="$WORDPRESS_PLUGINS_DIR/wp-document-revisions"

# Wait for WordPress container to create the plugins directory
max_attempts=30
attempt=0
until [ -d "$WORDPRESS_PLUGINS_DIR" ] || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "Waiting for WordPress plugins directory... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ -d "$WORDPRESS_PLUGINS_DIR" ]; then
    # Remove existing link/directory if it exists
    if [ -L "$PLUGIN_LINK" ] || [ -d "$PLUGIN_LINK" ]; then
        rm -rf "$PLUGIN_LINK"
    fi
    
    # Create symlink
    ln -s /workspaces/wp-document-revisions "$PLUGIN_LINK"
    echo "✓ Plugin linked to WordPress installation"
else
    echo "⚠ WordPress plugins directory not found. Link plugin manually if needed."
fi

# Display summary
echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Development Tools:"
echo "  • PHP $(php -v | head -n 1 | cut -d ' ' -f 2)"
echo "  • Composer $(composer --version 2>/dev/null | cut -d ' ' -f 3 || echo 'not found')"
echo "  • Node.js $(node --version 2>/dev/null || echo 'not found')"
echo "  • WP-CLI $(wp --version 2>/dev/null | cut -d ' ' -f 2 || echo 'not found')"
echo ""
echo "Available Commands:"
echo "  • composer install    - Install PHP dependencies"
echo "  • npm install         - Install Node.js dependencies"
echo "  • npm test            - Run JavaScript tests"
echo "  • bin/phpcs           - Run PHP CodeSniffer"
echo "  • bin/phpcbf          - Fix PHP code style"
echo "  • bin/phpunit         - Run PHP unit tests"
echo "  • wp                  - WP-CLI commands"
echo ""
echo "Access Points:"
echo "  • WordPress: http://localhost (or forwarded port)"
echo "  • Database: localhost:3306"
echo "    - Database: wordpress"
echo "    - User: wordpress"
echo "    - Password: wordpress"
echo "    - Test DB: wordpress_test"
echo ""
echo "Next Steps:"
echo "  1. Access WordPress at http://localhost"
echo "  2. Complete WordPress installation"
echo "  3. Activate WP Document Revisions plugin"
echo "  4. Start developing!"
echo ""
echo "=========================================="
