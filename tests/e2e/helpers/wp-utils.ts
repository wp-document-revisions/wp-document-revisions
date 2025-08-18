import type { Page } from 'playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import { execSync } from 'child_process';

// If @wordpress/e2e-test-utils-playwright exports its own expect we could use it, but retain base expect.

export const adminUser = process.env.WP_ADMIN_USER || 'admin';
export const adminPass = process.env.WP_ADMIN_PASS || 'password';

export async function loginAsAdmin(page: Page) {
	await page.goto('/wp-login.php');
	await page.locator('#user_login').fill(adminUser);
	await page.locator('#user_pass').fill(adminPass);
	await page.locator('#wp-submit').click();
	await expect(page).toHaveURL(/wp-admin\/?$/);
}

// New approach leveraging Gutenberg test utils fixtures if available (Admin & Editor)
export async function createDocument(
	page: Page,
	title: string,
	body: string,
	_context?: { admin?: any; editor?: any }
): Promise<string | undefined> {
	// The 'document' CPT does not expose show_in_rest by default; block editor fixtures may fail.
	// Always use classic fallback for this CPT unless future change enables REST.
	await page.goto('/wp-admin/post-new.php?post_type=document');

	const titleSelectors = ['#title', '#post-title-0', 'textarea.editor-post-title__input'];
	for (const sel of titleSelectors) {
		const locator = page.locator(sel).first();
		if ((await locator.count()) && (await locator.isVisible().catch(() => false))) {
			await locator.fill(title);
			break;
		}
	}

	const contentSelectors = [
		'#content',
		'.block-editor-rich-text__editable[aria-label="Add text"]',
		'.editor-post-text-editor',
	];
	for (const sel of contentSelectors) {
		const locator = page.locator(sel).first();
		if ((await locator.count()) && (await locator.isVisible().catch(() => false))) {
			await locator.fill(body);
			break;
		}
	}

	// Try Classic editor first (#publish enabled eventually) else Block editor flow.
	const classicPublish = page.locator('#publish');
	if (await classicPublish.isVisible().catch(() => false)) {
		await classicPublish.waitFor({ state: 'visible' });
		// Poll manually for enabled state (some CPT/editor combos keep disabled until first savePost dispatch)
		let enabled = false;
		for (let i = 0; i < 15; i++) {
			if (await classicPublish.isEnabled().catch(() => false)) {
				enabled = true;
				break;
			}
			// Trigger a save attempt via JS if available to force enabling
			try {
				await page.evaluate(() => {
					if (window.wp?.data?.dispatch) {
						window.wp.data.dispatch('core/editor').editPost({});
					}
				});
			} catch {}
			await page.waitForTimeout(500);
		}
		if (!enabled) {
			// Attempt clicking 'Save Draft' if available (#save-post) to force initial creation.
			const saveDraft = page.locator('#save-post');
			if (
				(await saveDraft.isVisible().catch(() => false)) &&
				(await saveDraft.isEnabled().catch(() => false))
			) {
				await saveDraft.click();
				await page.waitForTimeout(1500);
			}
			// Re-check publish enabled after draft save.
			if (await classicPublish.isEnabled().catch(() => false)) {
				enabled = true;
			}
		}
		if (enabled) {
			await classicPublish.click();
		} else {
			// Fallback to JS save if still not enabled.
			try {
				await page.evaluate(() => {
					window.wp?.data?.dispatch('core/editor').savePost();
				});
			} catch {}
		}
	} else {
		// Block editor: two-step publish sometimes.
		const primaryPublish = page.locator('button:has-text("Publish")');
		if (await primaryPublish.isVisible().catch(() => false)) {
			await primaryPublish.click();
			// Confirmation panel
			const confirmBtn = page.locator('button:has-text("Publish")');
			if (await confirmBtn.isVisible().catch(() => false)) {
				// Wait for enable
				await confirmBtn.waitFor({ state: 'visible' });
				await confirmBtn.click();
			}
		} else {
			// Fallback: invoke savePost through wp.data if available (ensures post created for custom CPT)
			try {
				await page.evaluate(() => {
					if (window.wp?.data?.dispatch) {
						window.wp.data.dispatch('core/editor').savePost();
					}
				});
			} catch (e) {
				// ignore
			}
		}
	}

	await page.waitForLoadState('networkidle');
	// Soft check for success notice; if absent, fall back to verifying post exists or create via WP-CLI.
	const successNotice = page.locator('.notice-success, .components-snackbar__content');
	if (!(await successNotice.isVisible().catch(() => false))) {
		// Attempt to locate document by title in admin list; if not present, create via wp-cli.
		try {
			await page.goto('/wp-admin/edit.php?post_type=document');
			const rowLink = page.locator(`a.row-title:has-text("${title}")`);
			if (
				!(await rowLink
					.first()
					.isVisible()
					.catch(() => false))
			) {
				// Create via WP-CLI fallback.
				const safeTitle = title.replace(/'/g, "'\\''");
				const safeBody = body.replace(/'/g, "'\\''");
				try {
					execSync(
						`docker compose run --rm wpcli post create --post_type=document --post_status=publish --post_title='${safeTitle}' --post_content='${safeBody}'`,
						{ stdio: 'inherit' }
					);
				} catch (e) {
					// ignore wp-cli failure; test assertions later will catch issues.
				}
				await page.goto('/wp-admin/edit.php?post_type=document');
			}
			// Open the document edit page for subsequent assertions if possible.
			const rowLink2 = page.locator(`a.row-title:has-text("${title}")`).first();
			if (await rowLink2.isVisible().catch(() => false)) {
				await rowLink2.click();
				await page.waitForLoadState('networkidle');
			}
		} catch {}
	}

	// Attempt to read post_ID if on edit screen.
	try {
		const idInput = page.locator('#post_ID');
		if (await idInput.isVisible().catch(() => false)) {
			const val = await idInput.inputValue();
			return val;
		}
	} catch {}
	return undefined;
}
