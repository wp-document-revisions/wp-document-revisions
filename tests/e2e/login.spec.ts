import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { loginAsAdmin } from './helpers/wp-utils';

// Basic admin login test to validate WordPress & plugin activation (menu present)

test.describe('Admin Login', () => {
	test('logs in and sees Dashboard + Documents menu', async ({ page, admin }) => {
		const errors: string[] = [];
		page.on('console', (msg) => {
			if (msg.type() === 'error') {
				errors.push(msg.text());
			}
		});
		await loginAsAdmin(page);
		await expect(page.locator('#wpadminbar')).toBeVisible();
		await admin.visitAdminPage('index.php');
		await expect(page.locator('#menu-posts-document')).toBeVisible();
		// Assert no unexpected validation errors.
		const validationErrors = errors.filter((e) => e.includes('Validation request failed'));
		expect(validationErrors).toHaveLength(0);
	});
});
