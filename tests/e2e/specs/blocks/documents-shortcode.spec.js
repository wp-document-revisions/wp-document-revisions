/**
 * E2E tests for the Documents List (documents-shortcode) Gutenberg block.
 *
 * @see js/wpdr-documents-shortcode.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Documents Shortcode Block', () => {
	test( 'can be inserted with defaults, configured, and previewed', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Shortcode Block Test' } );

		// Insert with defaults and verify.
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );

		let blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe(
			'wp-document-revisions/documents-shortcode'
		);

		// Check defaults.
		let attrs = blocks[ 0 ].attributes;
		expect( attrs.numberposts ).toBe( 5 );
		expect( attrs.new_tab ).toBe( true );
		expect( attrs.show_descr ).toBe( true );
		expect( attrs.show_thumb ).toBe( false );
		expect( attrs.show_pdf ).toBe( false );

		// ServerSideRender preview should appear.
		const blockContent = page.locator(
			'[data-type="wp-document-revisions/documents-shortcode"]'
		);
		await expect( blockContent ).toBeVisible();

		// Inspector controls should be present.
		await editor.openDocumentSettingsSidebar();
		const settingsPanel = page.locator(
			'.components-panel__body >> text=Documents List Settings'
		);
		await expect( settingsPanel ).toBeVisible();
	} );

	test( 'can configure attributes and render on frontend', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Shortcode Config Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
			attributes: {
				header: 'Team Documents',
				numberposts: 15,
				orderby: 'post_title',
				order: 'DESC',
				show_thumb: true,
				show_pdf: true,
				new_tab: false,
			},
		} );

		const blocks = await editor.getBlocks();
		const attrs = blocks[ 0 ].attributes;
		expect( attrs.header ).toBe( 'Team Documents' );
		expect( attrs.numberposts ).toBe( 15 );
		expect( attrs.orderby ).toBe( 'post_title' );
		expect( attrs.order ).toBe( 'DESC' );
		expect( attrs.show_thumb ).toBe( true );
		expect( attrs.show_pdf ).toBe( true );
		expect( attrs.new_tab ).toBe( false );

		// Publish and verify frontend.
		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		const content = page.locator( '.entry-content, .post-content, main' );
		await expect( content ).toBeVisible();
	} );
} );
