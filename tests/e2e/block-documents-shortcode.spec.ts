import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/wp-utils';

// Utility to ensure block inserter works in both classic and block contexts
async function ensureBlockEditor(page) {
	// If classic editor for 'document' CPT, navigate to a post type that supports blocks (post)
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
	// Open inserter
	const toggle = page
		.locator('button[aria-label="Add block"], button:has-text("Add block")')
		.first();
	if (await toggle.isVisible().catch(() => false)) {
		await toggle.click();
	} else {
		// Fallback: press slash to invoke inline inserter
		await page.keyboard.press('/');
	}
	// Search
	const searchBox = page.locator('input[placeholder*="Search"i]');
	if (await searchBox.isVisible().catch(() => false)) {
		await searchBox.fill(searchTerm);
	}
	// Click block
	const blockButton = page.locator(`button:has-text("${blockLabel}")`).first();
	await blockButton.click();
}

test.describe('Documents Shortcode Block (E2E)', () => {
	test.beforeEach(async ({ page }) => {
		await loginAsAdmin(page);
		await page.goto('/wp-admin/post-new.php?post_type=document');
		await ensureBlockEditor(page);
	});

	test('inserts and toggles shortcode block options', async ({ page }) => {
		await addBlockBySearch(page, 'Documents', 'Documents List');
		// Block should appear (SSR placeholder or heading text we render server-side). We expect a block wrapper.
		// Use generic selector for block outline.
		const ssrPlaceholder = page.locator(
			'.block-editor-block-list__block:has-text("Documents List"), .block-editor-block-list__block:has-text("Documents")'
		);
		await expect(ssrPlaceholder.first()).toBeVisible();

		// Open sidebar settings (if collapsed)
		const settingsTab = page.locator(
			'button[aria-label="Settings"], button[aria-label="Block"], button[aria-label="Document"]'
		);
		if (await settingsTab.isVisible().catch(() => false)) {
			await settingsTab.first().click();
		}

		// Toggle a known control: Show PDF Link (label may vary depending on i18n, using contains)
		const toggle = page.locator('label:has-text("PDF"), button:has-text("PDF")').first();
		if (await toggle.isVisible().catch(() => false)) {
			await toggle.click();
		}

		// Change header if TextControl is present
		const headerField = page.locator('input[type="text"]').first();
		if (await headerField.isVisible().catch(() => false)) {
			await headerField.fill('My Docs Header');
		}

		// Publish post (Block editor flow tolerant)
		const publish = page.locator('button:has-text("Publish")').first();
		if (await publish.isVisible().catch(() => false)) {
			await publish.click();
			const confirm = page.locator('button:has-text("Publish")').nth(1);
			if (await confirm.isVisible().catch(() => false)) {
				await confirm.click();
			}
		}

		await page.waitForLoadState('networkidle');

		// View post (follow View link if present)
		const viewLink = page
			.locator('a:has-text("View Post"), a:has-text("View document"), a:has-text("View")')
			.first();
		if (await viewLink.isVisible().catch(() => false)) {
			const href = await viewLink.getAttribute('href');
			if (href) {
				await page.goto(href);
			}
		}

		// Assert header text if set
		const headerMatch = page.locator('text=My Docs Header');
		await expect(headerMatch.first()).toBeVisible({ timeout: 10000 });
	});
});
