<?php
/**
 * Tests for the built-in PDF text extractor.
 *
 * Phase 3b of issue #514: exercises WP_Document_Revisions_PDF_Text_Extractor
 * against the existing Document1.pdf fixture, plus the auto-registration
 * hooked from wp-document-revisions.php.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_PDF_Text_Extractor and its auto-registration.
 */
class Test_WP_Document_Revisions_PDF_Text_Extractor extends Test_Common_WPDR {

	/**
	 * Supports returns true for application/pdf and false for everything else.
	 */
	public function test_supports_only_application_pdf() {
		$extractor = new WP_Document_Revisions_PDF_Text_Extractor();

		self::assertTrue( $extractor->supports( 'application/pdf' ) );
		self::assertFalse( $extractor->supports( 'text/plain' ) );
		self::assertFalse( $extractor->supports( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ) );
		self::assertFalse( $extractor->supports( '' ) );
		self::assertFalse( $extractor->supports( 'application/pdf; charset=binary' ) );
	}

	/**
	 * Returns non-empty alphabetic text when run against the bundled PDF.
	 *
	 * Deliberately not asserting the literal content — only that smalot ran
	 * and produced a sensible string. Tightening the assertion couples the
	 * test to the fixture's words.
	 */
	public function test_extract_returns_text_from_real_pdf() {
		$extractor = new WP_Document_Revisions_PDF_Text_Extractor();

		$text = $extractor->extract( self::$pdf_file, 'application/pdf' );

		self::assertIsString( $text );
		self::assertNotSame( '', trim( $text ), 'Expected non-whitespace text from Document1.pdf' );
		self::assertMatchesRegularExpression( '/[A-Za-z]/', $text, 'Expected at least one alphabetic character' );
	}

	/**
	 * Returns an empty string for a missing file rather than throwing —
	 * defensive even though the registry already short-circuits unreadable
	 * paths before calling into the extractor.
	 */
	public function test_extract_returns_empty_for_missing_file() {
		$extractor = new WP_Document_Revisions_PDF_Text_Extractor();

		self::assertSame( '', $extractor->extract( '/no/such/file.pdf', 'application/pdf' ) );
	}

	/**
	 * Returns an empty string when handed a plain text file masquerading as
	 * a PDF — smalot throws, the extractor catches.
	 */
	public function test_extract_returns_empty_for_non_pdf_content() {
		$extractor = new WP_Document_Revisions_PDF_Text_Extractor();

		self::assertSame( '', $extractor->extract( self::$test_file, 'application/pdf' ) );
	}

	/**
	 * The plugin bootstrap auto-registers the PDF extractor via the
	 * wpdr_text_extractors filter.
	 */
	public function test_pdf_extractor_is_registered_by_default() {
		$extractors = WP_Document_Revisions_Text_Extractor_Registry::get_extractors();

		$has_pdf = false;
		foreach ( $extractors as $extractor ) {
			if ( $extractor instanceof WP_Document_Revisions_PDF_Text_Extractor ) {
				$has_pdf = true;
				break;
			}
		}

		self::assertTrue( $has_pdf, 'Expected WP_Document_Revisions_PDF_Text_Extractor in the default registry' );
	}

	/**
	 * The registry resolves application/pdf to the built-in extractor when
	 * no third party has overridden it.
	 */
	public function test_registry_resolves_pdf_to_built_in_extractor() {
		$extractor = WP_Document_Revisions_Text_Extractor_Registry::find_for( 'application/pdf' );

		self::assertInstanceOf( WP_Document_Revisions_PDF_Text_Extractor::class, $extractor );
	}

	/**
	 * End-to-end: the public wpdr_extract_text() template function produces
	 * text when called against a PDF attachment.
	 */
	public function test_wpdr_extract_text_runs_pdf_extractor_against_attachment() {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'PDF Extractor Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$pdf_file );

		global $wpdr;
		$revision  = $wpdr->get_latest_revision( $doc_id );
		$attach_id = (int) $revision->post_content;

		$text = wpdr_extract_text( $attach_id );

		self::assertIsString( $text );
		self::assertNotSame( '', trim( $text ) );
		self::assertMatchesRegularExpression( '/[A-Za-z]/', $text );
	}
}
