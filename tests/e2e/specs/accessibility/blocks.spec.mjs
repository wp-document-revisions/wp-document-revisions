/**
 * Accessibility tests for WP Document Revisions blocks and admin pages.
 *
 * Uses axe-core via @axe-core/playwright to check for WCAG 2.1 AA violations.
 * Excludes known WordPress core violations that are not caused by this plugin.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import AxeBuilder from '@axe-core/playwright';
import path from 'path';
import { fileURLToPath } from 'url';
const __dirname = path.dirname( fileURLToPath( import.meta.url ) );

// Known WordPress core a11y issues to exclude from our tests.
const WP_CORE_RULES_TO_DISABLE = [
	'aria-allowed-role',
	'color-contrast',
	'duplicate-id',
	'duplicate-id-active',
	'region',
];

// WordPress core controls that axe 4.14's label-content-name-mismatch rule
// flags: their visible text isn't part of their aria-label. Excluded by
// element rather than disabling the rule, so plugin UI is still checked.
const WP_CORE_ELEMENTS_TO_EXCLUDE = [
	'#collapse-button', // Admin menu "Collapse Menu".
	'button[aria-label="Template options"]', // Editor sidebar template picker.
];

test.describe( 'Accessibility', () => {
	test( 'documents list page has no critical violations', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( 'edit.php', 'post_type=document' );
		await page.waitForLoadState( 'domcontentloaded' );

		const results = await new AxeBuilder( { page } )
			.include( '#wpbody-content' )
			.disableRules( WP_CORE_RULES_TO_DISABLE )
			.analyze();

		const critical = results.violations.filter(
			( v ) => v.impact === 'critical' || v.impact === 'serious'
		);
		expect( critical ).toEqual( [] );
	} );

	test( 'document editor has no critical violations', async ( {
		admin,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'Accessibility Test Document',
		} );
		await page.waitForLoadState( 'domcontentloaded' );

		let axe = new AxeBuilder( { page } ).exclude( 'iframe' );
		for ( const selector of WP_CORE_ELEMENTS_TO_EXCLUDE ) {
			axe = axe.exclude( selector );
		}
		const results = await axe
			.disableRules( WP_CORE_RULES_TO_DISABLE )
			.analyze();

		const critical = results.violations.filter(
			( v ) => v.impact === 'critical' || v.impact === 'serious'
		);
		expect( critical ).toEqual( [] );
	} );

	test( 'documents-widget block editor has no critical violations', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'A11y Widget Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
			attributes: { header: 'Recent Docs', numberposts: 5 },
		} );
		await editor.openDocumentSettingsSidebar();
		await page.waitForLoadState( 'domcontentloaded' );

		const results = await new AxeBuilder( { page } )
			.include( '.interface-interface-skeleton__sidebar' )
			.disableRules( WP_CORE_RULES_TO_DISABLE )
			.analyze();

		const critical = results.violations.filter(
			( v ) => v.impact === 'critical' || v.impact === 'serious'
		);
		expect( critical ).toEqual( [] );
	} );

	test( 'documents-shortcode block editor has no critical violations', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'A11y Shortcode Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );
		await editor.openDocumentSettingsSidebar();
		await page.waitForLoadState( 'domcontentloaded' );

		const results = await new AxeBuilder( { page } )
			.include( '.interface-interface-skeleton__sidebar' )
			.disableRules( WP_CORE_RULES_TO_DISABLE )
			.analyze();

		const critical = results.violations.filter(
			( v ) => v.impact === 'critical' || v.impact === 'serious'
		);
		expect( critical ).toEqual( [] );
	} );

	test( 'revisions-shortcode block editor has no critical violations', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'A11y Revisions Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
			attributes: { id: 1, numberposts: 5 },
		} );
		await editor.openDocumentSettingsSidebar();
		await page.waitForLoadState( 'domcontentloaded' );

		const results = await new AxeBuilder( { page } )
			.include( '.interface-interface-skeleton__sidebar' )
			.disableRules( WP_CORE_RULES_TO_DISABLE )
			.analyze();

		const critical = results.violations.filter(
			( v ) => v.impact === 'critical' || v.impact === 'serious'
		);
		expect( critical ).toEqual( [] );
	} );

	test( 'document-library table has no critical violations on the frontend', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		// A published document with a file, so the table has a row to check.
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: { title: 'A11y Library Document', status: 'draft' },
		} );
		const media = await requestUtils.uploadMedia(
			path.resolve( __dirname, '../../fixtures/test-document.txt' )
		);
		await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/media/${ media.id }`,
			data: { post: doc.id },
		} );
		await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/documents/${ doc.id }`,
			data: { content: `<!-- WPDR ${ media.id } -->`, status: 'publish' },
		} );

		await admin.createNewPost( { title: 'A11y Library Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/document-library',
			attributes: {
				variant: 'table',
				fields: [ 'title', 'file_type', 'workflow_state', 'modified', 'download' ],
			},
		} );
		await editor.publishPost();
		const postId = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getCurrentPostId()
		);
		await page.goto( `/?p=${ postId }` );

		await expect(
			page.locator( '.wpdr-library__table tbody', { hasText: 'A11y Library Document' } )
		).toBeVisible();

		const results = await new AxeBuilder( { page } )
			.include( '.wpdr-library' )
			.disableRules( WP_CORE_RULES_TO_DISABLE )
			.analyze();

		const critical = results.violations.filter(
			( v ) => v.impact === 'critical' || v.impact === 'serious'
		);
		expect( critical ).toEqual( [] );

		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ doc.id }`,
			params: { force: true },
		} );
		await requestUtils.deleteMedia( media.id );
	} );
} );
