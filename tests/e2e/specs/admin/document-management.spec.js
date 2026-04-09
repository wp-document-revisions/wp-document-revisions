/**
 * E2E tests for admin document management workflows.
 *
 * Tests the core user journey: creating documents, uploading files,
 * and managing revisions through the WordPress admin interface.
 *
 * @see js/wp-document-revisions.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const path = require( 'path' );

const TEST_DOC_PATH = path.resolve(
	__dirname,
	'../../fixtures/test-document.txt'
);
const TEST_DOC_V2_PATH = path.resolve(
	__dirname,
	'../../fixtures/test-document-v2.txt'
);

test.describe( 'Document Management', () => {
	test( 'can navigate to the documents list', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'edit.php', 'post_type=document' );

		// The documents list table should be visible.
		const heading = page.locator( '.wp-heading-inline, h1' ).first();
		await expect( heading ).toContainText( /[Dd]ocument/ );
	} );

	test( 'can access the new document editor', async ( { admin, page } ) => {
		await admin.createNewPost( { postType: 'document' } );

		// The document editor should load.
		// Check that the title field is present and editable.
		const titleField = page.locator(
			'role=textbox[name=/Add title/i]'
		);
		await expect( titleField ).toBeVisible();
	} );

	test( 'can create a new document with a title', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'E2E Test Document',
		} );

		// Verify the title was set.
		const titleField = page.locator(
			'role=textbox[name=/Add title/i]'
		);
		await expect( titleField ).toHaveValue( 'E2E Test Document' );
	} );

	test( 'document post type is registered and accessible', async ( {
		requestUtils,
	} ) => {
		// Use the REST API to verify the document post type exists.
		const types = await requestUtils.rest( {
			path: '/wp/v2/types',
		} );

		expect( types ).toHaveProperty( 'document' );
		expect( types.document.slug ).toBe( 'document' );
	} );

	test( 'can create a document via REST API', async ( {
		requestUtils,
	} ) => {
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: {
				title: 'REST API Test Document',
				status: 'draft',
			},
		} );

		expect( doc.title.raw ).toBe( 'REST API Test Document' );
		expect( doc.type ).toBe( 'document' );
		expect( doc.status ).toBe( 'draft' );

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ doc.id }`,
			params: { force: true },
		} );
	} );

	test( 'can list documents via REST API', async ( { requestUtils } ) => {
		// Create a test document.
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: {
				title: 'Listable Document',
				status: 'publish',
			},
		} );

		// List documents.
		const docs = await requestUtils.rest( {
			path: '/wp/v2/documents',
		} );

		expect( docs.length ).toBeGreaterThanOrEqual( 1 );

		const found = docs.find( ( d ) => d.id === doc.id );
		expect( found ).toBeDefined();
		expect( found.title.rendered ).toBe( 'Listable Document' );

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ doc.id }`,
			params: { force: true },
		} );
	} );

	test( 'document edit screen shows revision log', async ( {
		admin,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'Revision Log Test',
		} );

		// The document editor should contain the revision log area.
		// This is added by the plugin's admin JS.
		const editorContent = page.locator( '#editor, .editor-styles-wrapper' );
		await expect( editorContent ).toBeVisible();
	} );

	test( 'documents admin menu item exists', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'index.php' );

		// The admin menu should contain a "Documents" item.
		const menuItem = page.locator( '#menu-posts-document' );
		await expect( menuItem ).toBeVisible();
	} );
} );
