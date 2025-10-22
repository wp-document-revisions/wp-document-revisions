# WP Document Revisions - Copilot Instructions

WP Document Revisions is a document management and version control WordPress plugin that allows teams to collaboratively edit files and manage their workflow. Built with PHP, it follows WordPress coding standards and uses PHPUnit for testing.

**Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

**Note**: This file contains practical development workflow instructions. For comprehensive architectural documentation and detailed feature information, see `.copilot-instructions.md` in the repository root.

## Quick Start

For automated environment setup, the repository includes a GitHub Actions workflow at `.github/workflows/copilot-setup-steps.yml` that handles all dependencies and configuration. Manual setup instructions are provided below.

## Working Effectively

### Bootstrap and Dependencies

- Install PHP dependencies: `composer install --optimize-autoloader --prefer-dist`
    - Downloads and installs all PHP development dependencies
    - Creates vendor directory with PHPUnit, PHPCS, and WordPress Coding Standards
    - Takes 2-4 minutes depending on network speed. NEVER CANCEL - Set timeout to 5+ minutes.
- Install JavaScript dependencies: `npm install`
    - Installs terser for JavaScript minification
    - Takes a few seconds

### JavaScript Development

- JavaScript files are written in modern ES6+ JavaScript
- Source files are in `js/*.dev.js` (human-readable)
- Minified files are `js/*.js` (for production)
- Build JavaScript: `npm run build` or `script/build-js`
    - Minifies all JavaScript files using terser
    - Takes a few seconds
- Validate JavaScript syntax: `node -c js/filename.dev.js`

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

- Build JavaScript: `npm run build` or `script/build-js`
    - Minifies JavaScript files from .dev.js to .js using terser
    - Required before release if JavaScript files are modified
- Generate translation files: `script/generate-pot` (requires wp-pot-cli globally)
- No traditional "build" step - this is a WordPress plugin distributed as source
- JavaScript files are pre-built and committed to the repository

## Validation

### Essential Pre-commit Validation

- ALWAYS run PHPCS before committing: `bin/phpcs --standard=phpcs.ruleset.xml -p -s --colors *.php */**.php -v`
- ALWAYS run code formatting: `bin/phpcbf --standard=phpcs.ruleset.xml *.php */**.php`
- If making JavaScript changes, ALWAYS run: `npm run build` to regenerate minified files
- If making WordPress functionality changes, ALWAYS run full PHPUnit test suite
- Validate plugin loads in WordPress: Check wp-document-revisions.php syntax with `php -l wp-document-revisions.php`
- Validate JavaScript syntax: `node -c js/wp-document-revisions.dev.js`

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

## Security Guidelines

This plugin handles file uploads and sensitive documents. When making changes:

- **File Uploads**: Always validate file types, sizes, and content before processing
- **Input Validation**: Sanitize all user inputs using WordPress functions (`sanitize_text_field()`, `esc_html()`, etc.)
- **Nonce Verification**: Use WordPress nonces for all admin actions and form submissions
- **Capability Checks**: Verify user capabilities before allowing document operations (`current_user_can()`)
- **SQL Queries**: Use `$wpdb->prepare()` for all database queries to prevent SQL injection
- **File Access**: Never expose direct file paths; use WordPress authentication for file access
- **No Secrets**: Never commit API keys, passwords, or sensitive data to the repository

## Common Tasks

### Repository Structure

```
wp-document-revisions/
├── includes/               # Core PHP classes
│   ├── class-wp-document-revisions.php         # Main plugin class
│   ├── class-wp-document-revisions-admin.php   # Admin interface
│   └── class-wp-document-revisions-front-end.php # Frontend functionality
├── tests/                  # PHPUnit test files
├── js/                     # JavaScript files (modern ES6+)
│   ├── *.dev.js           # Source files (human-readable)
│   └── *.js               # Minified files (production)
├── css/                    # Stylesheets
├── script/                 # Development automation scripts
├── wp-document-revisions.php # Main plugin file
├── package.json           # JavaScript dependencies (terser)
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
- **Development Tools**: 
  - Composer (PHP dependency management)
  - PHPUnit (PHP testing)
  - PHPCS with WordPress standards (code quality)
  - Node.js/npm (JavaScript tooling)
  - terser (JavaScript minification)

### WordPress Plugin Standards

- Follows WordPress Plugin API and hooks system
- Uses WordPress coding standards enforced by PHPCS
- Implements proper sanitization, escaping, and nonce verification
- Supports multisite installations
- Includes proper internationalization (i18n) support

### Code Patterns and Conventions

When writing code for this plugin:

**Function Names**: Use WordPress naming conventions with plugin prefix
```php
// Correct
function wp_document_revisions_get_document( $id ) { }

// Incorrect
function getDocument( $id ) { }
```

**Variable Names**: Use snake_case for variables
```php
$document_id = 123;
$revision_count = get_revision_count();
```

**Error Handling**: Use WordPress error handling
```php
if ( is_wp_error( $result ) ) {
    return $result;
}
```

**Database Queries**: Always use prepared statements
```php
global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
    'document'
) );
```

**Hooks and Filters**: Provide extensibility points
```php
do_action( 'document_revisions_document_uploaded', $document_id );
$allowed = apply_filters( 'document_revisions_allowed_file_types', $default_types );
```

**Localization**: Use translation functions
```php
__( 'Document uploaded successfully', 'wp-document-revisions' );
_e( 'Error uploading document', 'wp-document-revisions' );
```

**JavaScript**: Use modern ES6+ JavaScript
```javascript
// Use ES6 classes
class MyClass {
    constructor() {
        this.property = value;
    }
    
    myMethod() {
        // Use arrow functions for callbacks
        this.$('.element').click((e) => this.handleClick(e));
    }
}

// Use const/let instead of var
const myVariable = 'value';
let counter = 0;

// Use template literals
const message = `Document ${documentId} uploaded`;

// Use arrow functions
const callback = (data) => {
    console.log(data);
};
```

## Troubleshooting

### Common Issues

- **PHPUnit bootstrap errors**: Ensure WordPress test environment is installed via `script/install-wp-tests`
- **PHPCS not found**: Run `composer install` to install development dependencies
- **Database connection errors**: Start MySQL and create wordpress_test database
- **Permission errors**: Check file permissions on uploads directory in WordPress
- **Network timeouts**: WordPress.org APIs may be temporarily unavailable, retry later
- **JavaScript build errors**: Run `npm install` to install terser and other dependencies
- **Minified JS missing**: Run `npm run build` or `script/build-js` to generate minified files

### Performance Notes

- Code analysis (PHPCS): ~10 seconds for full codebase
- Code formatting (PHPCBF): ~10 seconds for full codebase
- JavaScript minification: ~1-2 seconds for all files
- Full test suite: 2-5 minutes depending on system performance
- WordPress installation: 1-3 minutes depending on network speed
- Docker environment startup: 2-3 minutes first time, faster on subsequent runs

Remember: This is a WordPress plugin, not a standalone application. All testing and validation must be done within a WordPress environment.
