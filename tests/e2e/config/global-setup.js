/**
 * Global setup for WP Document Revisions E2E tests.
 *
 * Logs in as admin and saves the authentication state
 * so individual tests can reuse it without re-authenticating.
 */
const { RequestUtils } = require( '@wordpress/e2e-test-utils-playwright' );
const path = require( 'path' );

const STORAGE_STATE_PATH = path.resolve(
	__dirname,
	'.auth',
	'admin.json'
);

// Set env var so the test fixture uses the same path.
process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;

module.exports = async function globalSetup() {
	const SETUP_TIMEOUT = 60_000; // 60 seconds
	let timerId;

	const timeout = new Promise( ( _, reject ) => {
		timerId = setTimeout( () => {
			reject(
				new Error(
					`Global setup timed out after ${ SETUP_TIMEOUT / 1000 }s. ` +
						'WordPress REST API may not be available at http://localhost:8888.'
				)
			);
		}, SETUP_TIMEOUT );
	} );

	const setup = async () => {
		const requestUtils = await RequestUtils.setup( {
			storageStatePath: STORAGE_STATE_PATH,
			baseURL: 'http://localhost:8888',
			user: {
				username: 'admin',
				password: 'password',
			},
		} );

		// Activate the plugin if not already active.
		await requestUtils.activatePlugin( 'wp-document-revisions' );
	};

	try {
		await Promise.race( [ setup(), timeout ] );
	} finally {
		clearTimeout( timerId );
	}
};

