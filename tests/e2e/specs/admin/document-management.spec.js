/**
 * E2E tests for admin document management workflows.
 *
 * Tests the core user journey: creating documents, uploading files,
 * and managing revisions through the WordPress admin interface.
 *
 * @see js/wp-document-revisions.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Document Management', () => {
	test( 'document post type CRUD via REST API', async ( {
		requestUtils,
	} ) => {
		// Verify post type is registered.
		const types = await requestUtils.rest( {
			path: '/wp/v2/types',
		} );
		expect( types ).toHaveProperty( 'document' );
		expect( types.document.slug ).toBe( 'document' );

		// Create a document.
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: {
				title: 'REST API Test Document',
				status: 'publish',
			},
		} );
		expect( doc.title.raw ).toBe( 'REST API Test Document' );
		expect( doc.type ).toBe( 'document' );

		// List documents and find the created one.
		const docs = await requestUtils.rest( {
			path: '/wp/v2/documents',
		} );
		expect( docs.length ).toBeGreaterThanOrEqual( 1 );

		const found = docs.find( ( d ) => d.id === doc.id );
		expect( found ).toBeDefined();
		expect( found.title.rendered ).toBe( 'REST API Test Document' );

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ doc.id }`,
			params: { force: true },
		} );
	} );

	test( 'admin documents list and menu item', async ( { admin, page } ) => {
		// Dashboard should have Documents menu item.
		await admin.visitAdminPage( 'index.php' );
		const menuItem = page.locator( '#menu-posts-document' );
		await expect( menuItem ).toBeVisible();

		// Documents list should load.
		await admin.visitAdminPage( 'edit.php', 'post_type=document' );
		const heading = page.locator( '.wp-heading-inline, h1' ).first();
		await expect( heading ).toContainText( /[Dd]ocument/ );
	} );

	test( 'can create a new document in the editor', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'E2E Test Document',
		} );

		// Verify the block editor loaded (enabled via mu-plugin for E2E tests).
		const editorContent = editor.canvas.locator(
			'.editor-styles-wrapper, [data-is-root-container]'
		).first();
		await expect( editorContent ).toBeVisible( { timeout: 10000 } );
	} );
} );
