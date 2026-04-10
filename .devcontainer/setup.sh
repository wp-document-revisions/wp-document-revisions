#!/bin/bash
# Development environment setup script for WP Document Revisions
# This script runs once when the devcontainer is first created.
# Dependencies are installed separately via updateContentCommand.
#
# Note: This script uses simple passwords for local development only.
# These credentials are already defined in docker-compose.yml and are
# not intended for production use.

echo "=========================================="
echo "Setting up WP Document Revisions"
echo "=========================================="

cd /workspaces/wp-document-revisions

# ── Wait for WordPress files ────────────────────────────────────────
WORDPRESS_PLUGINS_DIR="/var/www/html/wp-content/plugins"
echo ""
echo "Waiting for WordPress..."
for i in $(seq 1 30); do
    [ -d "$WORDPRESS_PLUGINS_DIR" ] && break
    echo "  Waiting for WordPress files... ($i/30)"
    sleep 2
done

# ── Link plugin into WordPress ──────────────────────────────────────
PLUGIN_LINK="$WORDPRESS_PLUGINS_DIR/wp-document-revisions"
if [ -d "$WORDPRESS_PLUGINS_DIR" ]; then
    [ -L "$PLUGIN_LINK" ] || [ -d "$PLUGIN_LINK" ] && rm -rf "$PLUGIN_LINK"
    ln -s /workspaces/wp-document-revisions "$PLUGIN_LINK"
    echo "✓ Plugin linked to WordPress"
else
    echo "⚠ WordPress plugins directory not found"
fi

# ── Wait for database ───────────────────────────────────────────────
echo ""
echo "Waiting for database..."
for i in $(seq 1 30); do
    MYSQL_PWD=wordpress mysql -h db -u wordpress -e "SELECT 1" &>/dev/null && break
    echo "  Waiting for database... ($i/30)"
    sleep 2
done

# ── Create test database ────────────────────────────────────────────
MYSQL_PWD=mariadb mysql -h db -u root -e "CREATE DATABASE IF NOT EXISTS wordpress_test; GRANT ALL ON wordpress_test.* TO 'wordpress'@'%';" 2>/dev/null || true
echo "✓ Test database ready"

# ── Install WordPress via WP-CLI ────────────────────────────────────
echo ""
echo "Installing WordPress..."
if ! wp --path=/var/www/html core is-installed --allow-root 2>/dev/null; then
    wp --path=/var/www/html core install \
        --url="http://localhost" \
        --title="WP Document Revisions Dev" \
        --admin_user=admin \
        --admin_password=password \
        --admin_email=admin@example.com \
        --skip-email \
        --allow-root 2>/dev/null && echo "✓ WordPress installed" || echo "⚠ WordPress install failed (may need manual setup)"
else
    echo "✓ WordPress already installed"
fi

# ── Activate plugin ─────────────────────────────────────────────────
wp --path=/var/www/html plugin activate wp-document-revisions --allow-root 2>/dev/null && echo "✓ Plugin activated" || echo "⚠ Plugin activation failed"

# ── Summary ─────────────────────────────────────────────────────────
echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "  WordPress:  http://localhost"
echo "  Admin:      http://localhost/wp-admin"
echo "  Login:      admin / password"
echo ""
echo "  Commands:   bin/phpcs   bin/phpcbf"
echo "              bin/phpunit bin/phpstan"
echo "              npm test    npm run build:blocks"
echo ""
echo "  To set up PHPUnit test environment:"
echo "    bash script/install-wp-tests wordpress_test wordpress wordpress db latest true"
echo "=========================================="
