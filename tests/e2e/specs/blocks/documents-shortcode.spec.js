/**
 * E2E tests for the Documents List (documents-shortcode) Gutenberg block.
 *
 * @see js/wpdr-documents-shortcode.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Documents Shortcode Block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { title: 'Shortcode Block Test' } );
	} );

	test( 'can be inserted into the editor', async ( { editor } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );

		const blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe(
			'wp-document-revisions/documents-shortcode'
		);
	} );

	test( 'renders with default attributes', async ( { editor } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );

		const blocks = await editor.getBlocks();
		const attrs = blocks[ 0 ].attributes;

		// Verify default attribute values match the block registration.
		expect( attrs.numberposts ).toBe( 5 );
		expect( attrs.new_tab ).toBe( true );
		expect( attrs.show_descr ).toBe( true );
		expect( attrs.show_thumb ).toBe( false );
		expect( attrs.show_pdf ).toBe( false );
	} );

	test( 'can configure attributes', async ( { editor } ) => {
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
	} );

	test( 'shows inspector controls', async ( { editor, page } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );

		await editor.openDocumentSettingsSidebar();

		// Verify key inspector controls are present.
		const settingsPanel = page.locator(
			'.components-panel__body >> text=Documents List Settings'
		);
		await expect( settingsPanel ).toBeVisible();
	} );

	test( 'renders ServerSideRender preview', async ( { editor, page } ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
		} );

		const blockContent = page.locator(
			'[data-type="wp-document-revisions/documents-shortcode"]'
		);
		await expect( blockContent ).toBeVisible();
	} );

	test( 'block can be saved and appears on frontend', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'wp-document-revisions/documents-shortcode',
			attributes: {
				header: 'All Documents',
				numberposts: 10,
			},
		} );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		const content = page.locator( '.entry-content, .post-content, main' );
		await expect( content ).toBeVisible();
	} );
} );
