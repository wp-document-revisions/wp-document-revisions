/**
 * Accessibility tests for WP Document Revisions blocks and admin pages.
 *
 * Uses axe-core via @axe-core/playwright to check for WCAG 2.1 AA violations.
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

test.describe( 'Accessibility', () => {
	test( 'documents list page has no violations', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( 'edit.php', 'post_type=document' );
		await page.waitForLoadState( 'networkidle' );

		const results = await new AxeBuilder( { page } )
			.include( '#wpbody-content' )
			.analyze();

		expect( results.violations ).toEqual( [] );
	} );

	test( 'document editor has no violations', async ( {
		admin,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'Accessibility Test Document',
		} );
		await page.waitForLoadState( 'networkidle' );

		const results = await new AxeBuilder( { page } )
			.exclude( 'iframe' )
			.analyze();

		expect( results.violations ).toEqual( [] );
	} );

	test( 'documents-widget block editor has no violations', async ( {
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
		await page.waitForLoadState( 'networkidle' );

		const results = await new AxeBuilder( { page } )
			.include( '.interface-interface-skeleton__sidebar' )
			.analyze();

		expect( results.violations ).toEqual( [] );
	} );

	test( 'documents-shortcode block editor has no violations', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'A11y Shortcode Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );
		await editor.openDocumentSettingsSidebar();
		await page.waitForLoadState( 'networkidle' );

		const results = await new AxeBuilder( { page } )
			.include( '.interface-interface-skeleton__sidebar' )
			.analyze();

		expect( results.violations ).toEqual( [] );
	} );

	test( 'revisions-shortcode block editor has no violations', async ( {
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
		await page.waitForLoadState( 'networkidle' );

		const results = await new AxeBuilder( { page } )
			.include( '.interface-interface-skeleton__sidebar' )
			.analyze();

		expect( results.violations ).toEqual( [] );
	} );
} );
