---
applies_to:
  - includes/
  - wp-document-revisions.php
---

# Core Plugin Code Instructions

When working with plugin PHP files:

## WordPress Standards

- Follow WordPress Coding Standards (WPCS) strictly
- Use WordPress naming conventions (snake_case, wp_document_revisions_ prefix)
- Implement proper sanitization, validation, and escaping
- Use WordPress hooks and filters for extensibility
- Support WordPress multisite installations

## Required Tools & Validation

1. **Code Standards**:
   ```bash
   bin/phpcs --standard=phpcs.ruleset.xml -p -s --colors *.php */**.php -v
   bin/phpcbf --standard=phpcs.ruleset.xml *.php */**.php
   ```

2. **Testing**:
   ```bash
   bin/phpunit --config=phpunit9.xml
   ```

3. **Security**:
   - Check with gh-advisory-database for vulnerable dependencies
   - Run codeql_checker before finalizing changes

## Key Files

- `wp-document-revisions.php` - Main plugin file
- `includes/class-wp-document-revisions.php` - Core functionality
- `includes/class-wp-document-revisions-admin.php` - Admin interface
- `includes/class-wp-document-revisions-front-end.php` - Frontend
- `includes/class-wp-document-revisions-manage-rest.php` - REST API

## Security Critical

This plugin handles **file uploads and sensitive documents**. Always:

- Validate file types, sizes, and content
- Sanitize all user inputs using WordPress functions
- Use WordPress nonces for all admin actions
- Check user capabilities: `current_user_can()`
- Use `$wpdb->prepare()` for database queries
- Never expose direct file paths
- Implement proper access controls

## Code Patterns

**Error Handling**:
```php
if ( is_wp_error( $result ) ) {
    return $result;
}
```

**Database Queries**:
```php
global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
    'document'
) );
```

**Hooks and Filters**:
```php
do_action( 'document_revisions_document_uploaded', $document_id );
$allowed = apply_filters( 'document_revisions_allowed_file_types', $default_types );
```

## Best Practices

- Use **@wordpress-php-expert** for plugin code changes
- Use **@security-expert** for security reviews
- Make minimal, surgical changes
- Maintain backward compatibility
- Update version numbers when releasing
- Add PHPDoc comments for all functions

## Validation Checklist

- [ ] Zero PHPCS errors and warnings
- [ ] All PHPUnit tests pass
- [ ] Security scan passes (CodeQL)
- [ ] No vulnerable dependencies
- [ ] Backward compatibility maintained
- [ ] Proper error handling implemented

Always prioritize security and follow WordPress best practices.
