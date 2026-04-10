/**
 * Playwright configuration for WP Document Revisions E2E tests.
 *
 * @see https://playwright.dev/docs/test-configuration
 */
const { defineConfig } = require( '@playwright/test' );
const path = require( 'path' );

const STORAGE_STATE_PATH = path.resolve(
	__dirname,
	'tests/e2e/config/.auth/admin.json'
);
process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;

module.exports = defineConfig( {
	globalTimeout: 600_000, // 10 minutes max for the entire test suite
	testDir: './tests/e2e/specs',
	outputDir: './tests/e2e/results',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 2 : 3,
	reporter: process.env.CI ? 'github' : 'list',
	globalSetup: require.resolve( './tests/e2e/config/global-setup.js' ),
	use: {
		baseURL: 'http://localhost:8888',
		storageState: STORAGE_STATE_PATH,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { browserName: 'chromium' },
		},
	],
} );
