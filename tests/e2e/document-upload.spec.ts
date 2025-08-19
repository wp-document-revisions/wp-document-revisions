import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { loginAsAdmin, createDocument } from './helpers/wp-utils';

// E2E: Uploading a new version (attachment) for an existing Document.
// This exercises the classic media-upload thickbox flow the plugin customizes.

test.describe('Document Upload', () => {
	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
	});

	test('opens upload dialog (thickbox) for new document', async ({ page }) => {
		const title = 'Upload Dialog Open ' + Date.now();
		await createDocument(page, title, 'Description only.');
		// Ensure edit screen (classic editor may render #post_ID; on some browsers delay can occur).
		const postIdField = page.locator('#post_ID');
		for (let i = 0; i < 10; i++) {
			if (await postIdField.isVisible().catch(() => false)) break;
			await page.waitForTimeout(500);
		}
		// If still not visible, fall back to URL assertion.
		if (!(await postIdField.isVisible().catch(() => false))) {
			await expect(page).toHaveURL(/post-new\.php|post\.php/);
		} else {
			await expect(postIdField).toHaveValue(/\d+/);
		}
		const trigger = page.locator('#content-add_media');
		await expect(trigger).toBeVisible();
		await trigger.click();
		// Wait for iframe to appear.
		let found = false;
		for (let i = 0; i < 20; i++) {
			const frame = page.frames().find((f) => /media-upload\.php/.test(f.url()));
			if (frame) {
				found = true;
				break;
			}
			await page.waitForTimeout(250);
		}
		expect(found).toBeTruthy();
		// Basic plupload UI elements.
		const frameHandle = page.frameLocator('iframe#TB_iframeContent');
		await frameHandle
			.locator('#plupload-upload-ui, form#image-form, form#file-form')
			.first()
			.waitFor({ state: 'visible', timeout: 10000 });
	});
});
