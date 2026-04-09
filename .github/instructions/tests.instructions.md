---
applies_to:
  - tests/
---

# Testing Instructions

When working with test files:

## Test Frameworks

### PHPUnit (PHP tests)
- PHPUnit with WordPress test framework
- Configuration: `phpunit9.xml`
- Base class: `WP_UnitTestCase`
- Test files: `tests/class-test-*.php`

### Jest (JavaScript unit tests)
- Jest with jsdom environment
- Test files: `tests/js/*.test.js`
- Run: `npm test`

### Playwright (E2E tests)
- Playwright with `@wordpress/e2e-test-utils-playwright`
- Configuration: `playwright.config.js`
- Test files: `tests/e2e/specs/**/*.spec.js`
- Run: `npm run test:e2e` (requires Docker and `npx wp-env start`)

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
   npm install
   ```

## Running Tests

### PHPUnit
- All tests: `bin/phpunit --config=phpunit9.xml`
- Specific file: `bin/phpunit --config=phpunit9.xml tests/class-test-feature.php`
- Specific test: `bin/phpunit --config=phpunit9.xml --filter test_method_name`

### Jest
- All tests: `npm test`
- Watch mode: `npm run test:watch`
- With coverage: `npm run test:coverage`

### E2E (Playwright)
- Start environment: `npx wp-env start`
- Run tests: `npm run test:e2e`
- Interactive UI: `npm run test:e2e:ui`
- Stop environment: `npx wp-env stop`

## Writing Tests

### PHPUnit
- Extend `WP_UnitTestCase` for all test classes
- Use descriptive test method names (test_*)
- Test both success and failure cases
- Clean up test data in `tearDown()`
- Mock external dependencies
- Test WordPress hooks and filters

### Jest
- Test files: `tests/js/<source-file>.test.js`
- Use the shared setup in `tests/js/setup.js` for WordPress globals
- Use `jest.resetModules()` to clear module cache between tests

### E2E (Playwright)
- Import `test` and `expect` from `@wordpress/e2e-test-utils-playwright`
- Use `admin`, `editor`, `page`, `requestUtils` fixtures
- Test files: `tests/e2e/specs/blocks/` for Gutenberg blocks, `tests/e2e/specs/admin/` for admin workflows
- Global setup in `tests/e2e/config/global-setup.js` handles login and plugin activation

## Best Practices

- Use **@testing-expert** custom agent for test-related tasks
- All existing tests must pass before committing
- Add tests for new features
- Test edge cases and error conditions
- Keep tests focused and independent
- Make tests deterministic (no random failures)

## Required Validation

- Zero PHPUnit test failures
- Zero Jest test failures
- Zero E2E test failures
- Tests execute in reasonable time
- No test database pollution
- All new features have test coverage

Always run the full test suite before committing changes to ensure nothing breaks.
