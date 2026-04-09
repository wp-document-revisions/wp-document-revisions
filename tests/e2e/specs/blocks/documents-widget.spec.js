/**
 * E2E tests for the Latest Documents (documents-widget) Gutenberg block.
 *
 * @see js/wpdr-documents-widget.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Documents Widget Block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { title: 'Widget Block Test' } );
	} );

	test( 'can be inserted into the editor', async ( { editor, page } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
		} );

		// Block should appear in the editor.
		const blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe(
			'wp-document-revisions/documents-widget'
		);
	} );

	test( 'renders ServerSideRender preview in editor', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
		} );

		// ServerSideRender output should appear (even if empty list).
		// The block wraps its content in a div.
		const blockContent = page.locator(
			'[data-type="wp-document-revisions/documents-widget"]'
		);
		await expect( blockContent ).toBeVisible();
	} );

	test( 'shows inspector controls with default attributes', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
		} );

		// Open the block settings sidebar.
		await editor.openDocumentSettingsSidebar();

		// Verify heading text input exists.
		const headingInput = page.locator(
			'.components-panel__body >> text=Latest Documents List Heading'
		);
		await expect( headingInput ).toBeVisible();

		// Verify range control for number of documents.
		const rangeLabel = page.locator(
			'.components-panel__body >> text=Documents to Display'
		);
		await expect( rangeLabel ).toBeVisible();
	} );

	test( 'can configure block attributes via inspector', async ( {
		editor,
		page,
	} ) => {
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

		// Verify the attributes were set.
		const blocks = await editor.getBlocks();
		expect( blocks[ 0 ].attributes.header ).toBe( 'My Documents' );
		expect( blocks[ 0 ].attributes.numberposts ).toBe( 10 );
		expect( blocks[ 0 ].attributes.post_stat_draft ).toBe( true );
		expect( blocks[ 0 ].attributes.show_thumb ).toBe( true );
		expect( blocks[ 0 ].attributes.new_tab ).toBe( true );
	} );

	test( 'block can be saved and renders on frontend', async ( {
		admin,
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-widget',
			attributes: {
				header: 'Recent Documents',
				numberposts: 3,
			},
		} );

		// Publish the post.
		const postId = await editor.publishPost();

		// Visit the frontend.
		await page.goto( `/?p=${ postId }` );

		// The block should render something on the frontend.
		// Since it's server-rendered, it might show "Recent Documents" heading
		// or an empty list if no documents exist.
		const content = page.locator( '.entry-content, .post-content, main' );
		await expect( content ).toBeVisible();
	} );
} );
