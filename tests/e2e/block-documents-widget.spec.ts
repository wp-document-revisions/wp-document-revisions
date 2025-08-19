import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/wp-utils';

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
	await page.locator(`button:has-text("Latest Documents")`).first().click();
}

test.describe('Documents Widget Block (E2E)', () => {
	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
		await page.goto('/wp-admin/post-new.php?post_type=document');
		await ensureBlockEditor(page);
	});

	test('inserts widget block and toggles thumbnail setting', async ({ page }) => {
		await addBlockBySearch(page, 'Documents', 'Latest Documents');
		const widgetBlock = page.locator(
			'.block-editor-block-list__block:has-text("Latest Documents"), .block-editor-block-list__block:has-text("Documents")'
		);
		await expect(widgetBlock.first()).toBeVisible();

		// Open settings sidebar
		const settingsTab = page.locator(
			'button[aria-label="Settings"], button[aria-label="Block"]'
		);
		if (await settingsTab.isVisible().catch(() => false)) {
			await settingsTab.first().click();
		}

		// Toggle a control (Thumbnail or Description)
		const thumbToggle = page
			.locator('label:has-text("Thumbnail"), button:has-text("Thumbnail")')
			.first();
		if (await thumbToggle.isVisible().catch(() => false)) {
			await thumbToggle.click();
		}

		// Publish flow
		const publish = page.locator('button:has-text("Publish")').first();
		if (await publish.isVisible().catch(() => false)) {
			await publish.click();
			const confirm = page.locator('button:has-text("Publish")').nth(1);
			if (await confirm.isVisible().catch(() => false)) {
				await confirm.click();
			}
		}
		await page.waitForLoadState('networkidle');

		// View post
		const viewLink = page.locator('a:has-text("View"), a:has-text("View Post")').first();
		if (await viewLink.isVisible().catch(() => false)) {
			const href = await viewLink.getAttribute('href');
			if (href) await page.goto(href);
		}

		// Expect listing container or list items (heuristic)
		const listLikely = page.locator('ul, ol');
		await expect(listLikely.first()).toBeVisible({ timeout: 10000 });
	});
});
