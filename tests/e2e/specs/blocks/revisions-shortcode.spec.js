/**
 * E2E tests for the Document Revisions (revisions-shortcode) Gutenberg block.
 *
 * @see js/wpdr-revisions-shortcode.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Revisions Shortcode Block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { title: 'Revisions Block Test' } );
	} );

	test( 'can be inserted into the editor', async ( { editor } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
		} );

		const blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe(
			'wp-document-revisions/revisions-shortcode'
		);
	} );

	test( 'renders with default attributes', async ( { editor } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
		} );

		const blocks = await editor.getBlocks();
		const attrs = blocks[ 0 ].attributes;

		expect( attrs.numberposts ).toBe( 5 );
		expect( attrs.summary ).toBe( false );
	} );

	test( 'can configure document ID and display options', async ( {
		editor,
	} ) => {
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

		const blocks = await editor.getBlocks();
		const attrs = blocks[ 0 ].attributes;
		expect( attrs.id ).toBe( 42 );
		expect( attrs.numberposts ).toBe( 10 );
		expect( attrs.summary ).toBe( true );
		expect( attrs.show_pdf ).toBe( true );
		expect( attrs.new_tab ).toBe( true );
	} );

	test( 'shows inspector controls for configuration', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
		} );

		await editor.openDocumentSettingsSidebar();

		// Verify the settings panel is visible.
		const settingsPanel = page.locator(
			'.components-panel__body >> text=Document Revisions Settings'
		);
		await expect( settingsPanel ).toBeVisible();
	} );

	test( 'renders ServerSideRender preview', async ( { editor, page } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
		} );

		const blockContent = page.locator(
			'[data-type="wp-document-revisions/revisions-shortcode"]'
		);
		await expect( blockContent ).toBeVisible();
	} );

	test( 'block can be saved and appears on frontend', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/revisions-shortcode',
			attributes: {
				id: 1,
				numberposts: 3,
			},
		} );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		const content = page.locator( '.entry-content, .post-content, main' );
		await expect( content ).toBeVisible();
	} );
} );
