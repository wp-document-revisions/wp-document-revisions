# JavaScript Testing for WP Document Revisions

This document describes the JavaScript test suite for the WP Document Revisions plugin.

## Overview

The test suite provides comprehensive coverage of all front-end JavaScript functionality to prevent regressions when modernizing the codebase.

## Test Files

- **tests/js/wp-document-revisions.test.js** - Tests for the main admin functionality
- **tests/js/wp-document-revisions-validate.test.js** - Tests for validation/correction functionality
- **tests/js/wpdr-documents-shortcode.test.js** - Tests for the Documents List Gutenberg block
- **tests/js/wpdr-documents-widget.test.js** - Tests for the Latest Documents Gutenberg block
- **tests/js/wpdr-revisions-shortcode.test.js** - Tests for the Document Revisions Gutenberg block

## Installation

Install the required dependencies:

```bash
npm install
```

## Running Tests

### Run all tests
```bash
npm test
```

### Run tests in watch mode (for development)
```bash
npm run test:watch
```

### Generate coverage report
```bash
npm run test:coverage
```

Coverage reports are generated in the `coverage/` directory.

## Test Coverage

The test suite includes **197 tests** covering:

### Main Admin Functionality (wp-document-revisions.dev.js)
- Constructor and initialization
- Autosave hijacking and button management
- Revision restoration with confirmation
- Document locking and overrides
- File upload handling
- Human-readable time differences
- Cookie management
- Content building from TinyMCE
- Timestamp updates
- Helper functions (roundUp)

### Validation Functionality (wp-document-revisions-validate.dev.js)
- REST API interaction for validation fixes
- DOM manipulation for validation status display
- Toggle visibility for validation types
- Integration between API and UI updates

### Gutenberg Blocks

#### Documents Shortcode Block (wpdr-documents-shortcode.dev.js)
- Block registration and configuration
- Attribute definitions and defaults
- Block supports (alignment, colors, spacing, typography)
- Edit function and inspector controls
- Shortcode-to-block transforms
- Block-to-shortcode transforms
- Taxonomy handling
- Edge cases (empty taxonomies, workflow state conversions)

#### Documents Widget Block (wpdr-documents-widget.dev.js)
- Block registration and configuration
- Document status filtering (publish, private, draft)
- Display options (thumbnail, description, author, PDF indicator)
- Block styling and alignment
- Edge cases (extreme values, all options toggled)

#### Revisions Shortcode Block (wpdr-revisions-shortcode.dev.js)
- Block registration and configuration
- Document ID and revision display options
- Shortcode transforms (bidirectional)
- Summary and PDF indication toggles
- Edge cases (zero/negative IDs, extreme values)

## Test Structure

All tests follow a consistent structure:

```javascript
describe('Component Name', () => {
	beforeEach(() => {
		// Setup code
	});

	describe('Feature Group', () => {
		test('should do something specific', () => {
			// Test code
		});
	});
});
```

## Mocking Strategy

The test suite uses Jest's mocking capabilities to simulate:
- jQuery and DOM manipulation
- WordPress globals (wp.blocks, wp.element, wp.i18n, etc.)
- AJAX requests
- Browser APIs (cookies, notifications, etc.)
- WordPress document revisions data

See `tests/js/setup.js` for the complete mocking configuration.

## Known Issues

Some tests currently fail due to defensive coding issues in the original JavaScript:

1. **wp-document-revisions.test.js** - The class instantiation during module load triggers `checkUpdate()` which accesses `window.document` in complex ways. These tests reveal that the code makes assumptions about DOM structure.

2. **wp-document-revisions-validate.test.js** - The `clear_line()` function doesn't check if elements exist before accessing their properties. This is a potential bug in the original code that these tests expose.

These failing tests are **valuable** because they:
- Highlight areas where the code could be more defensive
- Identify potential runtime errors
- Guide improvements when modernizing the JavaScript

## Continuous Integration

JavaScript tests run automatically in CI alongside PHP tests. See `.github/workflows/ci.yml` for the complete CI configuration.

## Future Improvements

1. **Increase Coverage** - Add more edge case tests
2. **Integration Tests** - Test interactions between multiple components
3. **Performance Tests** - Add benchmarks for critical operations
4. **E2E Tests** - Add browser-based end-to-end tests with Playwright or Cypress
5. **Code Modernization** - Update JavaScript to use modern ES6+ features while maintaining test coverage

## Contributing

When adding new JavaScript functionality:

1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Aim for >80% code coverage
4. Follow existing test patterns
5. Document complex test scenarios

## Resources

- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Testing Library](https://testing-library.com/)
- [WordPress Gutenberg Block Testing](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/)
