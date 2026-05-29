/**
 * E2E tests for the document upload callback in the classic editor.
 *
 * Verifies that the legacyPostDocumentUpload event handler fires correctly
 * when a documentUpload CustomEvent is dispatched, testing the arrow-function
 * this-binding fix and CustomEvent.detail argument extraction.
 *
 * @see js/wp-document-revisions.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Document Upload Callback', () => {
	let documentId;

	test.beforeAll( async ( { requestUtils } ) => {
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: {
				title: 'Upload Callback Test',
				status: 'draft',
			},
		} );
		documentId = doc.id;
	} );

	test.afterAll( async ( { requestUtils } ) => {
		if ( documentId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/documents/${ documentId }`,
				params: { force: true },
			} );
		}
	} );

	test( 'classic editor loads document meta box with upload button', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'post.php',
			`post=${ documentId }&action=edit&classic=1`
		);

		// The document metabox should render with the upload button.
		const uploadButton = page.locator( '#content-add_media' );
		await expect( uploadButton ).toBeVisible( { timeout: 10000 } );
		await expect( uploadButton ).toContainText( /[Uu]pload/ );

		// The hidden post_content input should exist.
		const postContent = page.locator( '#post_content' );
		await expect( postContent ).toBeAttached();
	} );

	test( 'documentUpload event fires postDocumentUpload callback', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'post.php',
			`post=${ documentId }&action=edit&classic=1`
		);

		// Wait for the admin JS to initialize.
		await page.waitForFunction(
			() => window.WPDocumentRevisions && window.WPDocumentRevisions.hasUpload !== undefined,
			{ timeout: 10000 }
		);

		// Dispatch a documentUpload CustomEvent (the path fixed by the arrow-function conversion).
		const result = await page.evaluate( () => {
			const instance = window.WPDocumentRevisions;
			const hadUploadBefore = instance.hasUpload;

			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '999', extension: '.txt' },
				} )
			);

			return {
				hadUploadBefore,
				hasUploadAfter: instance.hasUpload,
				postContentValue:
					document.getElementById( 'post_content' )?.value ?? null,
			};
		} );

		// Before the event, hasUpload should have been false.
		expect( result.hadUploadBefore ).toBe( false );

		// After the event, hasUpload should be true (callback fired).
		expect( result.hasUploadAfter ).toBe( true );

		// The attachment id is now recorded server-side (document_attachment_id meta)
		// via the add_attachment hook, so the callback no longer writes the WPDR
		// comment into post_content.
		expect( result.postContentValue ).not.toContain( '<!-- WPDR' );
	} );

	test( 'submit buttons are enabled after upload callback', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		// Create a fresh document so hasUpload starts false.
		const freshDoc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: { title: 'Submit Button Test', status: 'draft' },
		} );

		await admin.visitAdminPage(
			'post.php',
			`post=${ freshDoc.id }&action=edit&classic=1`
		);

		await page.waitForFunction(
			() =>
				window.WPDocumentRevisions &&
				window.WPDocumentRevisions.hasUpload !== undefined,
			{ timeout: 10000 }
		);

		// Submit buttons should start disabled.
		const publishButton = page.locator( '#publish' );
		await expect( publishButton ).toBeDisabled();

		// Dispatch the upload event.
		await page.evaluate( () => {
			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '888', extension: '.pdf' },
				} )
			);
		} );

		// Submit button should now be enabled.
		await expect( publishButton ).toBeEnabled( { timeout: 5000 } );

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ freshDoc.id }`,
			params: { force: true },
		} );
	} );
} );
