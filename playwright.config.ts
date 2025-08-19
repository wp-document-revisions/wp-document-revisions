import { defineConfig, devices } from '@playwright/test';

const isCI = !!process.env.CI;
const baseURL = process.env.WP_BASE_URL || 'http://localhost:8088';

export default defineConfig({
	testDir: 'tests/e2e',
	timeout: 60_000,
	expect: { timeout: 10_000 },
	fullyParallel: true,
	retries: isCI ? 2 : 0,
	reporter: [['list'], ['html', { open: 'never' }]],
	use: {
		baseURL,
		trace: isCI ? 'on-first-retry' : 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	projects: [
		{ name: 'chromium', use: { ...devices['Desktop Chrome'] } },
		{ name: 'webkit', use: { ...devices['Desktop Safari'] } },
	],
});
