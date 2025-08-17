# WordPress Document Revisions - Modern Build System

This document explains the modern TypeScript/JavaScript build system that has been implemented for the WP Document Revisions plugin.

## Overview

The plugin has been modernized with:

- **TypeScript** for type safety and better development experience
- **Webpack** for bundling and optimization
- **ESLint** for code quality and consistency
- **Modern JavaScript/TypeScript** replacing legacy CoffeeScript

## Directory Structure

```
src/
├── admin/                          # Admin-side functionality
│   ├── wp-document-revisions.ts    # Main document revisions class (converted from CoffeeScript)
│   └── wp-document-revisions-validate.ts  # Validation functions
├── blocks/                         # Gutenberg blocks
│   ├── wpdr-documents-shortcode.tsx    # Documents list block
│   ├── wpdr-documents-widget.tsx       # Latest documents widget
│   └── wpdr-revisions-shortcode.tsx    # Document revisions block
└── types/                          # TypeScript type definitions
    ├── globals.ts                  # Global WordPress types
    └── blocks.ts                   # Block-specific types
```

## Build Scripts

### Development

```bash
npm run dev      # Build for development
npm run watch    # Build and watch for changes
```

### Production

```bash
npm run build    # Build optimized for production
```

### Code Quality

```bash
npm run lint         # Run ESLint
npm run lint:fix     # Run ESLint with auto-fix
npm run type-check   # Run TypeScript type checking
```

## Installation

1. Install dependencies:

```bash
npm install
```

2. Build the assets:

```bash
npm run build
```

## Migration from Legacy Code

### What Was Converted

1. **CoffeeScript → TypeScript**
    - `js/wp-document-revisions.coffee` → `src/admin/wp-document-revisions.ts`
    - `js/wp-document-revisions.coffee2` → `src/admin/wp-document-revisions.ts`

2. **Legacy JavaScript → Modern TypeScript**
    - `js/wp-document-revisions-validate.dev.js` → `src/admin/wp-document-revisions-validate.ts`

3. **Gutenberg Blocks → Modern TypeScript/React**
    - `js/wpdr-documents-shortcode.dev.js` → `src/blocks/wpdr-documents-shortcode.tsx`
    - `js/wpdr-documents-widget.dev.js` → `src/blocks/wpdr-documents-widget.tsx`
    - `js/wpdr-revisions-shortcode.dev.js` → `src/blocks/wpdr-revisions-shortcode.tsx`

### Key Improvements

1. **Type Safety**: Full TypeScript typing for WordPress APIs and plugin-specific interfaces
2. **Modern ES6+**: Classes, arrow functions, async/await, destructuring
3. **Better Error Handling**: Proper promise-based error handling
4. **Code Organization**: Modular structure with clear separation of concerns
5. **Developer Experience**: ESLint, TypeScript compiler, and webpack dev tools

### Backwards Compatibility

The build system generates JavaScript files in the `dist/` directory that maintain the same public API as the legacy code. WordPress integration points remain unchanged.

## WordPress Integration

The built files should be enqueued in your PHP code like this:

```php
// Instead of the old files
wp_enqueue_script('wp-document-revisions', plugin_dir_url(__FILE__) . 'dist/wp-document-revisions.js', ['jquery'], $version, true);
wp_enqueue_script('wpdr-documents-shortcode', plugin_dir_url(__FILE__) . 'dist/wpdr-documents-shortcode.js', ['wp-blocks', 'wp-element', 'wp-i18n'], $version, true);
```

## Legacy Files

The original CoffeeScript and JavaScript files in the `js/` directory are preserved for reference but should no longer be used in production. They can be safely removed after confirming the new build works correctly.

## Development Workflow

1. Make changes to TypeScript/TSX files in `src/`
2. Run `npm run watch` during development
3. Test in WordPress admin
4. Run `npm run lint` to check code quality
5. Build for production with `npm run build`

## Configuration Files

- `package.json` - Dependencies and scripts
- `tsconfig.json` - TypeScript compiler configuration
- `webpack.config.js` - Webpack bundling configuration
- `.eslintrc.json` - ESLint code quality rules

## Browser Support

The build system targets modern browsers with ES2020 support. For older browser support, adjust the TypeScript target in `tsconfig.json` and add appropriate polyfills.
