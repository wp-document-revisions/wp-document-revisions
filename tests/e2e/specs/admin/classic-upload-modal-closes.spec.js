/**
 * E2E regression test for the classic-editor upload modal (Problem B).
 *
 * Drives a REAL file through plupload's html5 file input in the "Upload New
 * Version" thickbox and asserts the modal closes afterwards. Before the fix, the
 * plugin's FileUploaded interceptor was bound only once on window.load; when
 * plupload's `uploader` global initialized later (WordPress 6.9+/7.0), the bind
 * bailed and never retried, so WordPress's native handler rendered the
 * attachment-details panel ("Insert into post" / "Save all changes") and the
 * modal stayed open. bindPostDocumentUploadCB() now retries until `uploader`
 * exists. The synthetic CustomEvent tests in document-upload.spec.js do not
 * exercise this real plupload path, which is how the regression slipped through.
 *
 * @see js/wp-document-revisions.dev.js bindPostDocumentUploadCB
 */
const path = require( 'path' );
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Classic upload modal closes after real upload', () => {
	test( 'modal closes after real file upload (Problem B)', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		test.setTimeout( 60000 );
		const doc = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/documents',
			data: { title: 'Real Upload Test', status: 'draft' },
		} );

		await admin.visitAdminPage(
			'post.php',
			`post=${ doc.id }&action=edit&classic=1`
		);
		await page.waitForFunction(
			() =>
				window.WPDocumentRevisions &&
				window.WPDocumentRevisions.hasUpload !== undefined,
			{ timeout: 15000 }
		);

		await page.locator( '#content-add_media' ).click();
		const frameEl = await page.locator( '#TB_iframeContent' ).elementHandle();
		const frame = await frameEl.contentFrame();
		await frame
			.locator( 'input[type="file"][id^="html5_"]' )
			.first()
			.waitFor( { timeout: 15000 } );

		// Let the bind retry loop attach the interceptor.
		await page.waitForTimeout( 2000 );

		// Drive a REAL upload through plupload's html5 file input.
		const filePath = path.resolve(
			__dirname,
			'../../fixtures/test-document-v2.txt'
		);
		await frame
			.locator( 'input[type="file"][id^="html5_"]' )
			.first()
			.setInputFiles( filePath );

		// The plugin's FileUploaded interceptor should call tb_remove() and close
		// the modal. Assert from the PARENT page so a closing iframe can't crash us.
		await expect( page.locator( '#TB_window' ) ).toBeHidden( {
			timeout: 20000,
		} );

		// And the parent should reflect a successful upload.
		await expect
			.poll(
				() =>
					page.evaluate(
						() =>
							!! (
								window.WPDocumentRevisions &&
								window.WPDocumentRevisions.hasUpload
							)
					),
				{ timeout: 5000 }
			)
			.toBe( true );

		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/documents/${ doc.id }`,
			params: { force: true },
		} );
	} );
} );
