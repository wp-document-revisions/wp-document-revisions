import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { loginAsAdmin } from './helpers/wp-utils';

// Basic admin login test to validate WordPress & plugin activation (menu present)

test.describe('Admin Login', () => {
	test('logs in and sees Dashboard + Documents menu', async ({ page, admin }) => {
		// Use our helper (direct form post) if Admin fixture not yet logged in.
		await loginAsAdmin(page);
		await expect(page.locator('#wpadminbar')).toBeVisible();
		// Use Admin utility to navigate to Dashboard explicitly to prove fixture works.
		await admin.visitAdminPage('index.php');
		await expect(page.locator('#menu-posts-document')).toBeVisible();
	});
});
