/**
 * Global setup for WP Document Revisions E2E tests.
 *
 * Logs in as admin and saves the authentication state
 * so individual tests can reuse it without re-authenticating.
 */
const { RequestUtils } = require( '@wordpress/e2e-test-utils-playwright' );
const path = require( 'path' );

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
const STORAGE_STATE_PATH = path.resolve(
	__dirname,
	'.auth',
	'admin.json'
);

// Set env var so the test fixture uses the same path.
process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;

module.exports = async function globalSetup() {
	const requestUtils = await RequestUtils.setup( {
		storageStatePath: STORAGE_STATE_PATH,
		baseURL: BASE_URL,
		user: {
			username: 'admin',
			password: 'password',
		},
	} );

	// Activate the plugin if not already active.
	await requestUtils.activatePlugin( 'wp-document-revisions' );
};

