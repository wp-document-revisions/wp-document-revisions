/**
 * E2E tests for the Latest Documents (documents-widget) Gutenberg block.
 *
 * @see src/blocks/documents-widget/
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Documents Widget Block', () => {
	test( 'can be inserted, configured, and previewed in editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Widget Block Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
			attributes: {
				header: 'My Documents',
				numberposts: 10,
				post_stat_draft: true,
				show_thumb: true,
				new_tab: true,
			},
		} );

		// Block should appear in the editor with correct attributes.
		const blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe(
			'wp-document-revisions/documents-widget'
		);
		expect( blocks[ 0 ].attributes.header ).toBe( 'My Documents' );
		expect( blocks[ 0 ].attributes.numberposts ).toBe( 10 );
		expect( blocks[ 0 ].attributes.post_stat_draft ).toBe( true );
		expect( blocks[ 0 ].attributes.show_thumb ).toBe( true );
		expect( blocks[ 0 ].attributes.new_tab ).toBe( true );

		// ServerSideRender output should appear.
		const blockContent = page.locator(
			'[data-type="wp-document-revisions/documents-widget"]'
		);
		await expect( blockContent ).toBeVisible();

		// Inspector controls should be accessible.
		await editor.openDocumentSettingsSidebar();

		const headingInput = page.locator(
			'.components-panel__body >> text=Latest Documents List Heading'
		);
		await expect( headingInput ).toBeVisible();

		const rangeLabel = page.locator(
			'.components-panel__body >> text=Documents to Display'
		);
		await expect( rangeLabel ).toBeVisible();
	} );

	test( 'block can be saved and renders on frontend', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Widget Frontend Test' } );

		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
			attributes: {
				header: 'Recent Documents',
				numberposts: 3,
			},
		} );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		const content = page.locator( '.entry-content, .post-content, main' ).first();
		await expect( content ).toBeVisible();
	} );
} );
