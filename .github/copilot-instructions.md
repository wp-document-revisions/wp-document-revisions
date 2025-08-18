# WP Document Revisions

WP Document Revisions is a document management and version control WordPress plugin that allows teams to collaboratively edit files and manage their workflow. Built with PHP, it follows WordPress coding standards and uses PHPUnit for testing.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Bootstrap and Dependencies

- Install dependencies: `composer install --optimize-autoloader --prefer-dist`
    - Downloads and installs all PHP development dependencies
    - Creates vendor directory with PHPUnit, PHPCS, and WordPress Coding Standards
    - Takes 2-4 minutes depending on network speed. NEVER CANCEL - Set timeout to 5+ minutes.

### Code Quality and Standards

- Run PHPCS linting: `bin/phpcs --standard=phpcs.ruleset.xml -p -s --colors *.php */**.php -v`
    - Checks all PHP files against WordPress Coding Standards
    - Takes approximately 10 seconds. NEVER CANCEL - Set timeout to 2+ minutes.
    - Zero errors and warnings expected for successful builds
- Fix code style automatically: `bin/phpcbf --standard=phpcs.ruleset.xml *.php */**.php`
    - Auto-fixes WordPress coding standard violations
    - Takes approximately 10 seconds. NEVER CANCEL - Set timeout to 2+ minutes.

### Testing Requirements

- WordPress test environment setup required: `bash script/install-wp-tests wordpress_test root root 127.0.0.1 latest`
    - Downloads WordPress core and test framework to /tmp/wordpress-tests-lib
    - Requires MySQL database and internet connection to WordPress.org APIs
    - Takes 1-3 minutes. NEVER CANCEL - Set timeout to 5+ minutes.
    - Create test database first: `mysql -u root -proot -e "CREATE DATABASE wordpress_test;"`
- Run PHPUnit tests: `bin/phpunit --config=phpunit9.xml` (after WordPress test setup)
    - Executes comprehensive test suite covering all plugin functionality
    - Takes 2-5 minutes. NEVER CANCEL - Set timeout to 10+ minutes.

### Development Environment Options

- **Option 1 - Docker**: Use `docker-compose up` and access WordPress at http://localhost:8088
    - Includes MySQL, PHPMyAdmin (port 8089), and WordPress with plugin mounted
    - Takes 2-3 minutes to start. NEVER CANCEL - Set timeout to 5+ minutes.
- **Option 2 - Local**: Install WordPress manually and symlink plugin directory

### Build and Release

- Validate JavaScript is current (files pre-built in js/ directory)
- Generate translation files: `script/generate-pot` (uses Composer-installed WP-CLI i18n command)
- No traditional "build" step - this is a WordPress plugin distributed as source

## Validation

### Essential Pre-commit Validation

- ALWAYS run PHPCS before committing: `bin/phpcs --standard=phpcs.ruleset.xml -p -s --colors *.php */**.php -v`
- ALWAYS run code formatting: `bin/phpcbf --standard=phpcs.ruleset.xml *.php */**.php`
- If making WordPress functionality changes, ALWAYS run full PHPUnit test suite
- Validate plugin loads in WordPress: Check wp-document-revisions.php syntax with `php -l wp-document-revisions.php`

### Manual Validation Scenarios

When making changes, ALWAYS test these core workflows:

1. **Plugin Activation**: Activate plugin in WordPress admin and verify no fatal errors
2. **Document Upload**: Create new document, upload file, verify it saves and generates revision
3. **Document Access**: Test public, private, and password-protected document access
4. **Revision Management**: Upload new version of document, verify revision history displays
5. **Permissions**: Test different user roles can/cannot access documents appropriately

### CI Requirements

- All code must pass PHPCS with zero violations
- All existing PHPUnit tests must pass
- PHP compatibility tested across versions 7.2-8.3
- WordPress compatibility tested with latest and 4.9 minimum versions

## Common Tasks

### Repository Structure

```
wp-document-revisions/
├── includes/               # Core PHP classes
│   ├── class-wp-document-revisions.php         # Main plugin class
│   ├── class-wp-document-revisions-admin.php   # Admin interface
│   └── class-wp-document-revisions-front-end.php # Frontend functionality
├── tests/                  # PHPUnit test files
├── js/                     # JavaScript files (pre-built)
├── css/                    # Stylesheets
├── script/                 # Development automation scripts
├── wp-document-revisions.php # Main plugin file
├── composer.json          # PHP dependencies
├── phpcs.ruleset.xml      # Code standards configuration
├── phpunit.xml|phpunit9.xml # Test configurations
└── docker-compose.yml     # Local development environment
```

### Key Files to Monitor

- Always check `class-wp-document-revisions.php` after making core functionality changes
- Always check `class-wp-document-revisions-admin.php` after making admin interface changes
- Review `wp-document-revisions.php` for any plugin-level configuration changes
- Update version numbers in both `wp-document-revisions.php` and `readme.txt` for releases

### Dependencies and Requirements

- **PHP**: 7.2+ (tested up to 8.3)
- **WordPress**: 4.9+ (tested up to latest)
- **Database**: MySQL 5.7+ or MariaDB equivalent
- **Development Tools**: Composer, PHPUnit, PHPCS with WordPress standards

### WordPress Plugin Standards

- Follows WordPress Plugin API and hooks system
- Uses WordPress coding standards enforced by PHPCS
- Implements proper sanitization, escaping, and nonce verification
- Supports multisite installations
- Includes proper internationalization (i18n) support

## Troubleshooting

### Common Issues

- **PHPUnit bootstrap errors**: Ensure WordPress test environment is installed via `script/install-wp-tests`
- **PHPCS not found**: Run `composer install` to install development dependencies
- **Database connection errors**: Start MySQL and create wordpress_test database
- **Permission errors**: Check file permissions on uploads directory in WordPress
- **Network timeouts**: WordPress.org APIs may be temporarily unavailable, retry later

### Performance Notes

- Code analysis (PHPCS): ~10 seconds for full codebase
- Code formatting (PHPCBF): ~10 seconds for full codebase
- Full test suite: 2-5 minutes depending on system performance
- WordPress installation: 1-3 minutes depending on network speed
- Docker environment startup: 2-3 minutes first time, faster on subsequent runs

Remember: This is a WordPress plugin, not a standalone application. All testing and validation must be done within a WordPress environment.
