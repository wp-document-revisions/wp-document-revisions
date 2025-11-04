---
applies_to:
  - tests/
---

# Testing Instructions

When working with test files:

## Test Framework

- PHPUnit with WordPress test framework
- Configuration: `phpunit9.xml`
- Base class: `WP_UnitTestCase`
- Test files: `tests/class-test-*.php`

## Before Testing

1. Ensure WordPress test environment is set up:
   ```bash
   # Note: Replace 'root'/'root' with your actual MySQL username/password
   mysql -u root -proot -e "CREATE DATABASE wordpress_test;"
   bash script/install-wp-tests wordpress_test root root 127.0.0.1 latest
   ```

2. Install dependencies:
   ```bash
   composer install --optimize-autoloader --prefer-dist
   ```

## Running Tests

- All tests: `bin/phpunit --config=phpunit9.xml`
- Specific file: `bin/phpunit --config=phpunit9.xml tests/class-test-feature.php`
- Specific test: `bin/phpunit --config=phpunit9.xml --filter test_method_name`

## Writing Tests

- Extend `WP_UnitTestCase` for all test classes
- Use descriptive test method names (test_*)
- Test both success and failure cases
- Clean up test data in `tearDown()`
- Mock external dependencies
- Test WordPress hooks and filters

## Best Practices

- Use **@testing-expert** custom agent for test-related tasks
- All existing tests must pass before committing
- Add tests for new features
- Test edge cases and error conditions
- Keep tests focused and independent
- Make tests deterministic (no random failures)

## Required Validation

- Zero test failures
- Tests execute in reasonable time
- No test database pollution
- All new features have test coverage

Always run the full test suite before committing changes to ensure nothing breaks.
