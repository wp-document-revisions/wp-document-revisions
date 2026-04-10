/**
 * Global setup for WP Document Revisions E2E tests.
 *
 * Logs in as admin and saves the authentication state (cookies, nonce,
 * and REST root URL) so individual tests can reuse it without
 * re-authenticating.
 *
 * This bypasses the library's automatic REST API discovery via the Link
 * header, which is unreliable in containerised wp-env environments.
 * See https://github.com/WordPress/gutenberg/issues/61627
 */
const { request } = require( '@playwright/test' );
const fs = require( 'fs/promises' );
const path = require( 'path' );

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
const STORAGE_STATE_PATH = path.resolve(
	__dirname,
	'.auth',
	'admin.json'
);

// Set env vars so the test fixtures use the same values.
process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;
process.env.WP_BASE_URL = BASE_URL;

module.exports = async function globalSetup() {
	await fs.mkdir( path.dirname( STORAGE_STATE_PATH ), {
		recursive: true,
	} );

	const context = await request.newContext( { baseURL: BASE_URL } );

	// Authenticate.
	await context.post( 'wp-login.php', {
		failOnStatusCode: true,
		form: { log: 'admin', pwd: 'password' },
	} );

	// Obtain a REST nonce.
	const nonceResp = await context.get(
		'wp-admin/admin-ajax.php?action=rest-nonce',
		{ failOnStatusCode: true }
	);
	const nonce = await nonceResp.text();

	// Persist cookies + REST metadata for the test fixtures.
	const { cookies } = await context.storageState();
	const storageState = {
		cookies,
		nonce,
		rootURL: BASE_URL + '/wp-json/',
	};
	await fs.writeFile(
		STORAGE_STATE_PATH,
		JSON.stringify( storageState ),
		'utf-8'
	);

	// Activate the plugin via direct REST call.
	const pluginsResp = await context.get( BASE_URL + '/wp-json/wp/v2/plugins', {
		headers: { 'X-WP-Nonce': nonce },
	} );
	const plugins = await pluginsResp.json();
	const wpdr = plugins.find( ( p ) =>
		p.plugin.startsWith( 'wp-document-revisions/' )
	);

	if ( wpdr && wpdr.status !== 'active' ) {
		await context.put(
			BASE_URL + `/wp-json/wp/v2/plugins/${ wpdr.plugin }`,
			{
				headers: { 'X-WP-Nonce': nonce },
				data: { status: 'active' },
			}
		);
	}

	await context.dispose();
};

