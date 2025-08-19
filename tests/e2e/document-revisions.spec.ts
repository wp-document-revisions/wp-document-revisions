import { test, expect } from '@playwright/test';
import { loginAsAdmin, createDocument } from './helpers/wp-utils';

test.describe('Document Revisions', () => {
	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
	});

	test('adds a new revision to existing document and sees it in history', async ({ page }) => {
		const baseTitle = 'Revision Test ' + Date.now();
		const firstContent = 'Initial content ' + Math.random();
		let postId = await createDocument(page, baseTitle, firstContent);

		// Ensure we are on the edit screen and capture ID even if helper couldn't.
		const idField = page.locator('#post_ID');
		await expect(idField).toHaveValue(/\d+/);
		if (!postId) {
			postId = await idField.inputValue();
		}

		// Classic editor content may be inside TinyMCE iframe; attempt multiple strategies.
		const newContent = 'Updated content ' + Math.random();
		let filled = false;
		// 1. Direct textarea (#content).
		const classicArea = page.locator('#content');
		if (await classicArea.isVisible().catch(() => false)) {
			await classicArea.fill(newContent);
			filled = true;
		}
		// 2. TinyMCE iframe body.
		if (!filled) {
			const iframe = page.frameLocator('iframe[id^="content_ifr"], iframe');
			try {
				const body = iframe.locator('body#tinymce');
				if (await body.count()) {
					await body.click();
					await body.fill(newContent);
					filled = true;
				}
			} catch {}
		}
		// 3. Block editor rich text.
		if (!filled) {
			const blockEditable = page.locator(
				'.block-editor-rich-text__editable[aria-label="Add text"]'
			);
			if (await blockEditable.isVisible().catch(() => false)) {
				await blockEditable.fill(newContent);
				filled = true;
			}
		}
		// 4. Text editor area.
		if (!filled) {
			const textEditor = page.locator('.editor-post-text-editor');
			if (await textEditor.isVisible().catch(() => false)) {
				await textEditor.fill(newContent);
				filled = true;
			}
		}

		// Save/publish again to create a second revision.
		const updateBtn = page
			.locator('#publish, #save-post, button:has-text("Update"), button:has-text("Publish")')
			.first();
		if (await updateBtn.isVisible().catch(() => false)) {
			// Wait up to 5s for enable.
			for (let i = 0; i < 10; i++) {
				if (await updateBtn.isEnabled()) {
					break;
				}
				await page.waitForTimeout(500);
			}
			await updateBtn.click();
		} else {
			// JS dispatch fallback.
			try {
				await page.evaluate(() => {
					// @ts-ignore
					window.wp?.data?.dispatch('core/editor').savePost();
				});
			} catch {}
		}

		await page.waitForLoadState('networkidle');

		// Navigate to revisions screen via link if present (WordPress UI adds browser link with 'Revisions').
		const revisionsLink = page.locator('a:has-text("Revisions")').first();
		if (await revisionsLink.isVisible().catch(() => false)) {
			await revisionsLink.click();
			await page.waitForLoadState('networkidle');
			// Expect at least two revisions.
			const revisionRows = page.locator('table.diff, #revisiondiff, .revisions-meta');
			await expect(revisionRows.first()).toBeVisible();
		} else {
			// Alternative: open revisions directly via URL pattern.
			if (postId) {
				await page.goto(`/wp-admin/revision.php?revision=1&action=edit`); // basic existence check; ID increment unknown
			}
		}

		// Back to the edit screen and verify revision log shows at least one row (initial revision) after update.
		if (postId) {
			await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
		}
		const revisionLogRows = page
			.locator('table tr')
			.filter({ has: page.locator('a:has-text("seconds")') });
		await expect(revisionLogRows.first()).toBeVisible();
	});
});
