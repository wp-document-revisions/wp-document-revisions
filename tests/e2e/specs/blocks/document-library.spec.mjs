/**
 * E2E tests for the Document Library Gutenberg block.
 *
 * @see src/blocks/document-library/
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Document Library Block', () => {
	test( 'offers a variation per layout and shows its settings', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Library Block Test' } );

		const variations = await page.evaluate( () =>
			window.wp.blocks
				.getBlockVariations( 'wp-document-revisions/document-library', 'inserter' )
				.map( ( variation ) => variation.name )
		);
		expect( variations ).toEqual( [ 'document-list', 'document-table', 'document-grid' ] );

		await editor.insertBlock( {
			name: 'wp-document-revisions/document-library',
			attributes: { variant: 'table' },
		} );

		const blocks = await editor.getBlocks();
		expect( blocks[ 0 ].attributes.variant ).toBe( 'table' );

		await expect(
			editor.canvas.locator( '[data-type="wp-document-revisions/document-library"]' )
		).toBeVisible( { timeout: 10000 } );

		await editor.openDocumentSettingsSidebar();
		for ( const panel of [ 'Layout', 'Fields', 'Order' ] ) {
			await expect(
				page.locator( '.components-panel__body-title', { hasText: panel } )
			).toBeVisible();
		}
	} );

	test( 'renders each layout on the frontend', async ( { admin, editor, page } ) => {
		await admin.createNewPost( { title: 'Library Frontend Test' } );

		for ( const layout of [ 'list', 'table', 'grid' ] ) {
			await editor.insertBlock( {
				name: 'wp-document-revisions/document-library',
				attributes: { variant: layout },
			} );
		}

		await editor.publishPost();
		const postId = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getCurrentPostId()
		);
		await page.goto( `/?p=${ postId }` );

		for ( const layout of [ 'list', 'table', 'grid' ] ) {
			await expect( page.locator( `.wpdr-library--${ layout }` ) ).toHaveCount( 1 );
		}
	} );
} );
