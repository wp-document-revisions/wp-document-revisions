/**
 * E2E tests for the Document Revisions (revisions-shortcode) Gutenberg block.
 *
 * @see src/blocks/revisions-shortcode/
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Revisions Shortcode Block', () => {
	test( 'can be inserted, configured, and previewed in editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Revisions Block Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
			attributes: {
				id: 42,
				numberposts: 10,
				summary: true,
				show_pdf: true,
				new_tab: true,
			},
		} );

		// Block should appear with correct attributes.
		const blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe(
			'wp-document-revisions/revisions-shortcode'
		);

		const attrs = blocks[ 0 ].attributes;
		expect( attrs.id ).toBe( 42 );
		expect( attrs.numberposts ).toBe( 10 );
		expect( attrs.summary ).toBe( true );
		expect( attrs.show_pdf ).toBe( true );
		expect( attrs.new_tab ).toBe( true );

		// Block wrapper should appear in the editor canvas (may be inside an iframe).
		const blockContent = editor.canvas.locator(
			'[data-type="wp-document-revisions/revisions-shortcode"]'
		);
		await expect( blockContent ).toBeVisible( { timeout: 10000 } );

		// Inspector controls should be accessible.
		await editor.openDocumentSettingsSidebar();
		const settingsPanel = page.locator(
			'.components-panel__body >> text=Selection Criteria'
		);
		await expect( settingsPanel ).toBeVisible();
	} );

	test( 'block can be saved and appears on frontend', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Revisions Frontend Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
			attributes: {
				id: 1,
				numberposts: 3,
			},
		} );

		await editor.publishPost();
		const postId = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getCurrentPostId()
		);
		await page.goto( `/?p=${ postId }` );

		const content = page
			.locator( '.entry-content, .post-content, .wp-block-post-content, main, body' )
			.first();
		await expect( content ).toBeVisible();
	} );
} );
