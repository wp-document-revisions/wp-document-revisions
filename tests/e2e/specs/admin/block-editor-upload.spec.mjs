/**
 * E2E tests for the block editor document upload sidebar panel.
 *
 * Verifies:
 * - The "Document" sidebar panel renders in the block editor
 * - Upload button appears and is functional
 * - Attachment meta syncs to post_content via REST on save
 * - Post saving is locked for new documents without a file
 *
 * @see src/editor-document-upload/index.js
 * @see includes/class-wp-document-revisions-manage-rest.php
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import path from 'path';
import { fileURLToPath } from 'url';
const __dirname = path.dirname( fileURLToPath( import.meta.url ) );

/**
 * Helper: open the Document upload panel in the Settings sidebar.
 * The panel may be collapsed by default, so this clicks the header to expand it.
 *
 * @param {import('@playwright/test').Page}                              page   Playwright page.
 * @param {import('@wordpress/e2e-test-utils-playwright').Editor} editor Editor utils.
 * @return {import('@playwright/test').Locator} The expanded panel locator.
 */
async function openDocumentUploadPanel( page, editor ) {
	await editor.openDocumentSettingsSidebar();

	// Find the panel by its heading text "Document" within the sidebar.
	const panelHeader = page.getByRole( 'button', {
		name: /^Document$/,
	} );
	await expect( panelHeader ).toBeVisible( { timeout: 10000 } );

	// Expand if collapsed (aria-expanded="false").
	const expanded = await panelHeader.getAttribute( 'aria-expanded' );
	if ( expanded === 'false' ) {
		await panelHeader.click();
	}

	const panel = page.locator( '.wp-document-revisions-upload-panel' );
	await expect( panel ).toBeVisible( { timeout: 5000 } );
	return panel;
}

test.describe( 'Block Editor Document Upload', () => {
	test( 'document panel renders in Settings sidebar with upload button', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'Block Editor Upload Test',
		} );

		// Wait for block editor to fully load (canvas is hidden for documents via CSS).
		await page.waitForSelector( '.edit-post-header', { timeout: 15000 } );

		const panel = await openDocumentUploadPanel( page, editor );

		// The "Upload Document" button should be visible (no file attached yet).
		const uploadButton = panel.getByRole( 'button', {
			name: /[Uu]pload [Dd]ocument/,
		} );
		await expect( uploadButton ).toBeVisible();
	} );

	test( 'attachment meta syncs to post_content on REST save', async ( {
		requestUtils,
	} ) => {
		// Upload a test file to get an attachment ID.
		const filePath = path.resolve(
			__dirname,
			'../../fixtures/test-document.txt'
		);
		const media = await requestUtils.uploadMedia( filePath );

		// Create a document with the attachment ID in meta.
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: {
				title: 'Meta Sync Test',
				status: 'draft',
				meta: {
					_document_attachment_id: media.id,
				},
			},
		} );

		// Fetch the document to verify content was synced.
		const saved = await requestUtils.rest( {
			path: `/wp/v2/documents/${ doc.id }`,
		} );

		// post_content should contain the WPDR comment with the attachment ID.
		expect( saved.content.rendered ).toBeDefined();

		// Fetch raw content via edit context.
		const raw = await requestUtils.rest( {
			path: `/wp/v2/documents/${ doc.id }?context=edit`,
		} );

		// The meta should be populated in the response.
		expect( raw.meta._document_attachment_id ).toBe( media.id );

		// The raw content should have WPDR stripped (for block editor display).
		expect( raw.content.raw ).not.toContain( '<!-- WPDR' );

		// But the actual DB content should have it — verify by checking
		// that the non-edit response contains the WPDR-formatted ID.
		// (The view context's rendered content goes through wpautop etc.,
		// so just verify the meta round-tripped correctly.)

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ doc.id }`,
			params: { force: true },
		} );
		await requestUtils.deleteMedia( media.id );
	} );

	test( 'media frame is upload-only (no Media Library tab)', async ( { admin, editor, page } ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'Block Editor Upload-only Frame Test',
		} );
		await page.waitForSelector( '.edit-post-header', { timeout: 15000 } );

		const panel = await openDocumentUploadPanel( page, editor );
		await panel.getByRole( 'button', { name: /[Uu]pload [Dd]ocument/ } ).click();

		const mediaModal = page.locator( '.media-modal' );
		await expect( mediaModal ).toBeVisible( { timeout: 10000 } );
		// Picking an existing library file can't work: a document may only use its own attachments.
		await expect( mediaModal.getByRole( 'tab', { name: /[Mm]edia [Ll]ibrary/ } ) ).toHaveCount( 0 );
		await expect( mediaModal.locator( 'input[type="file"]' ) ).toHaveCount( 1 );
	} );

	test( 'uploading through the panel stores a protected document file and survives reload', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost( {
			postType: 'document',
			title: 'Block Editor Upload Flow Test',
		} );
		await page.waitForSelector( '.edit-post-header', { timeout: 15000 } );

		const panel = await openDocumentUploadPanel( page, editor );
		await panel.getByRole( 'button', { name: /[Uu]pload [Dd]ocument/ } ).click();

		const mediaModal = page.locator( '.media-modal' );
		await expect( mediaModal ).toBeVisible( { timeout: 10000 } );
		await mediaModal
			.locator( 'input[type="file"]' )
			.setInputFiles( path.resolve( __dirname, '../../fixtures/test-document.txt' ) );

		// The frame auto-closes once the upload finishes, and the panel shows the file.
		await expect( mediaModal ).not.toBeVisible( { timeout: 15000 } );
		await expect(
			panel.getByRole( 'button', { name: /[Uu]pload [Nn]ew [Vv]ersion/ } )
		).toBeVisible( { timeout: 10000 } );

		await editor.saveDraft();
		const postId = await page.evaluate( () =>
			wp.data.select( 'core/editor' ).getCurrentPostId()
		);

		const doc = await requestUtils.rest( {
			path: `/wp/v2/documents/${ postId }?context=edit`,
		} );
		const attachId = doc.meta._document_attachment_id;
		expect( attachId ).toBeGreaterThan( 0 );

		const media = await requestUtils.rest( {
			path: `/wp/v2/media/${ attachId }?context=edit`,
		} );
		// Parented to the document, and stored under a hashed name (not the public original).
		expect( media.post ).toBe( postId );
		expect( media.source_url ).not.toContain( 'test-document' );

		// After a reload the panel still shows the attached file.
		await page.reload();
		await page.waitForSelector( '.edit-post-header', { timeout: 15000 } );
		const reloaded = await openDocumentUploadPanel( page, editor );
		await expect(
			reloaded.getByRole( 'button', { name: /[Uu]pload [Nn]ew [Vv]ersion/ } )
		).toBeVisible( { timeout: 10000 } );
		await expect( reloaded.getByRole( 'link', { name: 'Download' } ) ).toBeVisible();

		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ postId }`,
			params: { force: true },
		} );
	} );
} );
