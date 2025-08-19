import { test, expect } from '@playwright/test';
import { loginAsAdmin, createDocument } from './helpers/wp-utils';

async function ensureBlockEditor(page) {
	if (
		await page
			.locator('#content')
			.isVisible()
			.catch(() => false)
	) {
		await page.goto('/wp-admin/post-new.php');
	}
}

async function addBlockBySearch(page, searchTerm: string, blockLabel: string) {
	const toggle = page
		.locator('button[aria-label="Add block"], button:has-text("Add block")')
		.first();
	if (await toggle.isVisible().catch(() => false)) {
		await toggle.click();
	} else {
		await page.keyboard.press('/');
	}
	const searchBox = page.locator('input[placeholder*="Search"i]');
	if (await searchBox.isVisible().catch(() => false)) {
		await searchBox.fill(searchTerm);
	}
	await page.locator(`button:has-text("Document Revisions")`).first().click();
}

test.describe('Revisions Shortcode Block (E2E)', () => {
	let docId: string | undefined;

	test.beforeAll(async ({ browser }) => {
		// Create a source document with a couple revisions to display
		const context = await browser.newContext();
		const page = await context.newPage();
		await loginAsAdmin(page);
		const baseTitle = 'Revisions Source ' + Date.now();
		docId = await createDocument(page, baseTitle, 'Initial revision');
		// Add one more revision for richer output
		if (docId) {
			await page.goto(`/wp-admin/post.php?post=${docId}&action=edit`);
			const content = page.locator('#content');
			if (await content.isVisible().catch(() => false)) {
				await content.fill('Second revision content');
			}
			const updateBtn = page
				.locator(
					'#publish, #save-post, button:has-text("Update"), button:has-text("Publish")'
				)
				.first();
			if (await updateBtn.isVisible().catch(() => false)) {
				await updateBtn.click();
			}
			await page.waitForLoadState('networkidle');
		}
		await context.close();
	});

	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
		await page.goto('/wp-admin/post-new.php?post_type=document');
		await ensureBlockEditor(page);
	});

	test('inserts revisions block and displays revision list placeholder', async ({ page }) => {
		await addBlockBySearch(page, 'Revisions', 'Document Revisions');
		const block = page.locator(
			'.block-editor-block-list__block:has-text("Document Revisions")'
		);
		await expect(block.first()).toBeVisible();

		// If Document ID control present, set it
		const idField = page.locator('input[type="number"]');
		if (docId && (await idField.isVisible().catch(() => false))) {
			await idField.fill(docId);
		}

		const publish = page.locator('button:has-text("Publish")').first();
		if (await publish.isVisible().catch(() => false)) {
			await publish.click();
			const confirm = page.locator('button:has-text("Publish")').nth(1);
			if (await confirm.isVisible().catch(() => false)) {
				await confirm.click();
			}
		}
		await page.waitForLoadState('networkidle');

		const viewLink = page.locator('a:has-text("View"), a:has-text("View Post")').first();
		if (await viewLink.isVisible().catch(() => false)) {
			const href = await viewLink.getAttribute('href');
			if (href) await page.goto(href);
		}

		// Heuristic: look for revision related text or list
		const revisionText = page.locator('text=Revisions, text=Document Revisions').first();
		await expect(revisionText).toBeVisible({ timeout: 10000 });
	});
});
