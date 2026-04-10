/**
 * Accessibility tests for WP Document Revisions blocks and admin pages.
 *
 * Uses axe-core via @axe-core/playwright to check for WCAG 2.1 AA violations.
 * Excludes known WordPress core violations that are not caused by this plugin.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

// Known WordPress core a11y issues to exclude from our tests.
const WP_CORE_RULES_TO_DISABLE = [
	'aria-allowed-role',
	'color-contrast',
	'duplicate-id',
	'duplicate-id-active',
	'region',
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

		const results = await new AxeBuilder( { page } )
			.exclude( 'iframe' )
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
} );
