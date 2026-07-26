/**
 * ESLint flat config for WP Document Revisions.
 *
 * Extends the default @wordpress/scripts config (used by `wp-scripts lint-js`)
 * and layers on project-specific adjustments for the sources under `src/`
 * (block editor in `src/blocks`/`src/editor-*`, classic-admin scripts in
 * `src/admin`).
 */

const globals = require( 'globals' );

const wpScriptsConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wpScriptsConfig,

	// Only the modern block-editor sources are linted (and gated in CI).
	{
		files: [ 'src/**/*.js' ],
		languageOptions: {
			globals: {
				// Injected from PHP via wp_localize_script().
				wpdr_data: 'readonly',
			},
		},
		rules: {
			// @wordpress/* packages are supplied as webpack externals at
			// runtime, not installed as npm dependencies, so the import
			// resolver cannot (and should not) resolve them.
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
			'import/no-extraneous-dependencies': 'off',
			// WordPress REST API responses and post meta use snake_case keys
			// (e.g. source_url, mime_type, document_attachment_id); allow them
			// when read as object properties.
			camelcase: [ 'error', { properties: 'never' } ],
		},
	},

	// The shortcode/widget block registrations port the classic-editor query
	// logic, whose local variables deliberately mirror the server-side query
	// variable names (staxonomy_0, sterm_0, snumberposts, …). Renaming them
	// would desync the two implementations for no functional gain, so the
	// stylistic camelcase check is disabled for these files only.
	{
		files: [ 'src/blocks/**/*.js' ],
		rules: {
			camelcase: 'off',
		},
	},

	// Classic-admin scripts (formerly js/*.dev.js). These run outside a module
	// bundler context and rely on globals injected by their PHP enqueue via
	// wp_add_inline_script()/wp_localize_script(), plus standard WordPress admin
	// globals. Their identifiers deliberately mirror those PHP-side names, and
	// the confirm()/alert() prompts are long-standing intentional UX, so the
	// stylistic camelcase/no-alert checks are relaxed for these files only.
	{
		files: [ 'src/admin/**/*.js' ],
		languageOptions: {
			globals: {
				// Standard browser globals (alert, confirm, location, …); these
				// scripts run directly in the admin, not through a bundler.
				...globals.browser,
				// Injected via wp_add_inline_script()/wp_localize_script().
				wp_document_revisions: 'readonly',
				lock_override_notice: 'readonly',
				user: 'readonly',
				processed: 'readonly',
				// Standard WordPress admin globals.
				ajaxurl: 'readonly',
				autosave: 'readonly',
				wp: 'readonly',
			},
		},
		rules: {
			camelcase: 'off',
			'no-alert': 'off',
			// Permit the `== null` / `!= null` idiom (matches null and
			// undefined) while still requiring strict equality elsewhere.
			eqeqeq: [ 'error', 'smart' ],
		},
	},
];
