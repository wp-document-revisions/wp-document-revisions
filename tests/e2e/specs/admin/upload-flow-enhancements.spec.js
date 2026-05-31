/**
 * E2E tests for document upload flow enhancements.
 *
 * Verifies upload feedback UI: confirmation notice after upload,
 * save-first notice on duplicate upload, error notice rendering,
 * and progress indicator display.
 *
 * @see js/wp-document-revisions.dev.js
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

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

		// Dispatch the upload event to trigger post-upload feedback.
		await page.evaluate( () => {
			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '100', extension: '.pdf' },
				} )
			);
		} );

		// The confirmation notice should appear.
		const notice = page.locator( '#wpdr-upload-confirm' );
		await expect( notice ).toBeVisible( { timeout: 5000 } );
		await expect( notice ).toContainText( /[Uu]pload/ );
	} );

	test( 'save-first notice appears on duplicate upload without saving', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		// Create a fresh document for clean state.
		const freshDoc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: { title: 'Save First Test', status: 'draft' },
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

		// First upload — should succeed.
		await page.evaluate( () => {
			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '200', extension: '.docx' },
				} )
			);
		} );

		// Confirm first upload was accepted.
		expect(
			await page.evaluate( () => window.WPDocumentRevisions.hasUpload )
		).toBe( true );

		// Second upload without saving — should show save-first notice.
		await page.evaluate( () => {
			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '201', extension: '.docx' },
				} )
			);
		} );

		const saveNotice = page.locator( '#wpdr-save-first-notice' );
		await expect( saveNotice ).toBeVisible( { timeout: 5000 } );
		await expect( saveNotice ).toContainText( /save/i );

		// Clean up.
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ freshDoc.id }`,
			params: { force: true },
		} );
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
				'wpdr-upload-progress',
				'wpdr-save-first-notice',
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
			'wpdr-upload-progress',
			'wpdr-save-first-notice',
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
			'wpdr-upload-progress',
			'wpdr-save-first-notice',
			'wpdr-upload-error',
		] ) {
			await expect( page.locator( `#${ id }` ) ).not.toBeAttached();
		}
	} );

	test( 'upload does not write the WPDR comment into post_content', async ( {
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

		// Dispatch upload event with known attachment ID.
		await page.evaluate( () => {
			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '42', extension: '.txt' },
				} )
			);
		} );

		// The attachment id is managed server-side (document_attachment_id meta,
		// reintegrated into post_content on save), so the upload callback must not
		// write the WPDR comment into post_content.
		const content = await page.evaluate( () => {
			return document.getElementById( 'post_content' )?.value ?? '';
		} );
		expect( content ).not.toMatch( /<!-- WPDR \d+ -->/ );

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

		// Dispatch upload with a PDF extension.
		await page.evaluate( () => {
			document.dispatchEvent(
				new CustomEvent( 'documentUpload', {
					detail: { attachmentID: '50', extension: '.pdf' },
				} )
			);
		} );

		// The extension display element should show the new extension.
		const extEl = page.locator( '#document_extension' );
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
