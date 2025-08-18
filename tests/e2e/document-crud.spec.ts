import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from 'playwright';
import { loginAsAdmin, createDocument } from './helpers/wp-utils';

test.describe('Document CRUD', () => {
	test.beforeEach(async ({ page }: { page: Page }) => {
		await loginAsAdmin(page);
	});

	test('creates a new document and lands on edit screen', async ({ page }: { page: Page }) => {
		const title = 'E2E Test Document ' + Date.now();
		const postId = await createDocument(page, title, 'E2E body content for revision 1.');

		// Confirm title value present (classic vs block title field)
		const possibleTitle = page
			.locator('#title, #post-title-0, textarea.editor-post-title__input')
			.first();
		await expect(possibleTitle).toHaveValue(title);

		// Basic sanity: hidden post_ID field exists & matches digits if available
		const idField = page.locator('#post_ID');
		if (await idField.isVisible().catch(() => false)) {
			await expect(idField).toHaveValue(/\d+/);
		}
		if (postId) {
			await expect(postId).toMatch(/\d+/);
		}
	});
});
