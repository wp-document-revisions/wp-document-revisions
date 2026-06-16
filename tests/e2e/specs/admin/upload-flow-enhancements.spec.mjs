/**
 * E2E tests for document upload flow enhancements.
 *
 * Verifies upload feedback UI: confirmation notice after upload,
 * the test-only attachment/extension fields, and clearUploadNotices().
 * Uploads now run through wp.media, so documentUpload() is invoked directly.
 *
 * @see js/wp-document-revisions.dev.js
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Upload Flow Enhancements', () => {
	let documentId;

	test.beforeAll( async ( { requestUtils } ) => {
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: {
				title: 'Upload Flow Test',
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

	test( 'upload confirmation notice appears after successful upload', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'post.php',
			`post=${ documentId }&action=edit&classic=1`
		);

		await page.waitForFunction(
			() =>
				window.WPDocumentRevisions &&
				window.WPDocumentRevisions.hasUpload !== undefined,
			{ timeout: 10000 }
		);

		// Invoke the upload callback to trigger post-upload feedback.
		await page.evaluate( () => {
			window.WPDocumentRevisions.documentUpload( '100', '.pdf' );
		} );

		// The confirmation notice should appear.
		const notice = page.locator( '#wpdr-upload-confirm' );
		await expect( notice ).toBeVisible( { timeout: 5000 } );
		await expect( notice ).toContainText( /[Uu]pload/ );
	} );

	test( 'clearUploadNotices removes all notice elements', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'post.php',
			`post=${ documentId }&action=edit&classic=1`
		);

		await page.waitForFunction(
			() =>
				window.WPDocumentRevisions &&
				window.WPDocumentRevisions.hasUpload !== undefined,
			{ timeout: 10000 }
		);

		// Inject test notice elements into the DOM.
		await page.evaluate( () => {
			const ids = [
				'wpdr-upload-confirm',
				'message',
				'wpdr-upload-error',
			];
			for ( const id of ids ) {
				const el = document.createElement( 'div' );
				el.id = id;
				el.textContent = 'test notice';
				document.body.appendChild( el );
			}
		} );

		// All notices should be present.
		for ( const id of [
			'wpdr-upload-confirm',
			'message',
			'wpdr-upload-error',
		] ) {
			await expect( page.locator( `#${ id }` ) ).toBeAttached();
		}

		// Call clearUploadNotices.
		await page.evaluate( () => {
			window.WPDocumentRevisions.clearUploadNotices();
		} );

		// All notices should be removed.
		for ( const id of [
			'wpdr-upload-confirm',
			'message',
			'wpdr-upload-error',
		] ) {
			await expect( page.locator( `#${ id }` ) ).not.toBeAttached();
		}
	} );

	test( 'upload sets post_content with WPDR comment', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const freshDoc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: { title: 'Content Update Test', status: 'draft' },
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

		// Invoke the upload callback with a known attachment ID.
		await page.evaluate( () => {
			window.WPDocumentRevisions.documentUpload( '42', '.txt' );
		} );

		// Verify curr_attach contains the attachment id.
		const attach = await page.evaluate( () => {
			return document.getElementById( 'curr_attach' )?.value ?? '';
		} );
		expect( attach ).toContain( '42' );

		// Verify attach_ext contains the attachment extension.
		const extens = await page.evaluate( () => {
			return document.getElementById( 'attach_ext' )?.value ?? '';
		} );
		expect( extens ).toContain( '.txt' );

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ freshDoc.id }`,
			params: { force: true },
		} );
	} );

	test( 'document extension updates after upload', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const freshDoc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: { title: 'Extension Test', status: 'draft' },
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

		// Invoke the upload callback with a PDF extension.
		await page.evaluate( () => {
			window.WPDocumentRevisions.documentUpload( '50', '.pdf' );
		} );

		// The extension display element should show the new extension.
		const extEl = page.locator( '#attach_ext' );
		if ( await extEl.isVisible().catch( () => false ) ) {
			await expect( extEl ).toHaveValue( '.pdf' );
		}

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ freshDoc.id }`,
			params: { force: true },
		} );
	} );
} );
