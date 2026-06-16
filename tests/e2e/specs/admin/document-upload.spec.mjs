/**
 * E2E tests for the document upload callback in the classic editor.
 *
 * Verifies that the documentUpload() handler records the new attachment and
 * enables the submit buttons. The upload now runs through wp.media, so the
 * callback is invoked directly rather than via a CustomEvent.
 *
 * @see js/wp-document-revisions.dev.js
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

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
		const uploadButton = page.locator( '#add-document-file' );
		await expect( uploadButton ).toBeVisible( { timeout: 10000 } );
		await expect( uploadButton ).toContainText( /[Uu]pload/ );

		// The hidden post_content input should exist.
		const postContent = page.locator( '#post_content' );
		await expect( postContent ).toBeAttached();
	} );

	test( 'documentUpload() records the upload', async ( {
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

		// Invoke the upload callback directly (wp.media calls this on FileUploaded).
		const result = await page.evaluate( () => {
			const instance = window.WPDocumentRevisions;
			const hadUploadBefore = instance.hasUpload;

			instance.documentUpload( '999', '.txt' );

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

		// post_content should not contain the WPDR comment with the attachment ID as now done server side.
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

		// Invoke the upload callback.
		await page.evaluate( () => {
			window.WPDocumentRevisions.documentUpload( '888', '.pdf' );
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
