<?php
/**
 * Tests for the built-in DOCX/ODT text extractor.
 *
 * Phase 4 of issue #514. Fixtures are generated programmatically with
 * PHPWord per the decision in the PR thread — this means the tests cover
 * the PHPWord writer + the extractor as a pair, not the extractor against
 * real-world Word/LibreOffice output. A follow-up should drop checked-in
 * Word/LibreOffice fixtures into tests/documents/ and exercise the
 * extractor against those.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_DOCX_Text_Extractor and its auto-registration.
 */
class Test_WP_Document_Revisions_DOCX_Text_Extractor extends Test_Common_WPDR {

	/**
	 * DOCX MIME type constant for readability.
	 *
	 * @var string
	 */
	const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

	/**
	 * ODT MIME type constant.
	 *
	 * @var string
	 */
	const ODT_MIME = 'application/vnd.oasis.opendocument.text';

	/**
	 * Temp files created by individual tests; cleaned in tear_down.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Delete any temp DOCX/ODT files written during the test.
	 */
	public function tear_down() {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		parent::tear_down();
	}

	/**
	 * Build a PhpWord document with caller-supplied content and persist it
	 * to a temp file via the requested writer.
	 *
	 * @param callable $builder receives a PhpWord instance, populates it.
	 * @param string   $writer  PHPWord writer name ('Word2007' or 'ODText').
	 * @param string   $ext     file extension to use for the temp file.
	 * @return string path to the generated file.
	 */
	private function build_document( callable $builder, string $writer = 'Word2007', string $ext = '.docx' ): string {
		$phpword = new \PhpOffice\PhpWord\PhpWord();
		$builder( $phpword );

		// wp_tempnam() gives us a unique path inside the WP upload tree;
		// pass the desired suffix so PHPWord's writer sees a sensible
		// extension when it saves and the file name reads naturally in
		// any debugging output.
		$path = wp_tempnam( 'wpdr_test_' . wp_generate_password( 6, false ) . $ext );

		$writer_obj = \PhpOffice\PhpWord\IOFactory::createWriter( $phpword, $writer );
		$writer_obj->save( $path );

		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Supports returns true for DOCX and ODT, false for everything else
	 * (including legacy application/msword which belongs to phase 5).
	 */
	public function test_supports_only_docx_and_odt() {
		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();

		self::assertTrue( $extractor->supports( self::DOCX_MIME ) );
		self::assertTrue( $extractor->supports( self::ODT_MIME ) );

		self::assertFalse( $extractor->supports( 'application/msword' ) );
		self::assertFalse( $extractor->supports( 'application/pdf' ) );
		self::assertFalse( $extractor->supports( 'text/plain' ) );
		self::assertFalse( $extractor->supports( '' ) );
	}

	/**
	 * Returns plain paragraph text from a basic DOCX.
	 */
	public function test_extract_returns_paragraph_text() {
		$path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$section->addText( 'Hello DOCX world.' );
				$section->addText( 'Second paragraph.' );
			}
		);

		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();
		$text      = $extractor->extract( $path, self::DOCX_MIME );

		self::assertStringContainsString( 'Hello DOCX world.', $text );
		self::assertStringContainsString( 'Second paragraph.', $text );
	}

	/**
	 * Walks tables and pulls cell content from every row.
	 */
	public function test_extract_returns_table_cell_text() {
		$path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$table   = $section->addTable();

				$row1 = $table->addRow();
				$row1->addCell()->addText( 'Header A' );
				$row1->addCell()->addText( 'Header B' );

				$row2 = $table->addRow();
				$row2->addCell()->addText( 'Cell 1A' );
				$row2->addCell()->addText( 'Cell 1B' );
			}
		);

		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();
		$text      = $extractor->extract( $path, self::DOCX_MIME );

		self::assertStringContainsString( 'Header A', $text );
		self::assertStringContainsString( 'Header B', $text );
		self::assertStringContainsString( 'Cell 1A', $text );
		self::assertStringContainsString( 'Cell 1B', $text );
	}

	/**
	 * Pulls text from headers and footers in addition to the section body.
	 */
	public function test_extract_returns_header_and_footer_text() {
		$path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$section->addHeader()->addText( 'Document header line' );
				$section->addText( 'Body paragraph' );
				$section->addFooter()->addText( 'Document footer line' );
			}
		);

		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();
		$text      = $extractor->extract( $path, self::DOCX_MIME );

		self::assertStringContainsString( 'Document header line', $text );
		self::assertStringContainsString( 'Body paragraph', $text );
		self::assertStringContainsString( 'Document footer line', $text );
	}

	/**
	 * Pulls text from a TextRun's inner Text elements (formatted runs).
	 */
	public function test_extract_returns_text_run_content() {
		$path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$run     = $section->addTextRun();
				$run->addText( 'Bold-ish ', array( 'bold' => true ) );
				$run->addText( 'plain.' );
			}
		);

		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();
		$text      = $extractor->extract( $path, self::DOCX_MIME );

		self::assertStringContainsString( 'Bold-ish', $text );
		self::assertStringContainsString( 'plain.', $text );
	}

	/**
	 * Pulls text from list items.
	 */
	public function test_extract_returns_list_item_text() {
		$path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$section->addListItem( 'First bullet' );
				$section->addListItem( 'Second bullet' );
			}
		);

		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();
		$text      = $extractor->extract( $path, self::DOCX_MIME );

		self::assertStringContainsString( 'First bullet', $text );
		self::assertStringContainsString( 'Second bullet', $text );
	}

	/**
	 * Reads ODT via the ODText reader.
	 */
	public function test_extract_reads_odt_format() {
		$path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$section->addText( 'OpenDocument text content.' );
			},
			'ODText',
			'.odt'
		);

		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();
		$text      = $extractor->extract( $path, self::ODT_MIME );

		self::assertStringContainsString( 'OpenDocument text content.', $text );
	}

	/**
	 * Returns an empty string when handed a missing file.
	 */
	public function test_extract_returns_empty_for_missing_file() {
		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();

		self::assertSame( '', $extractor->extract( '/no/such/file.docx', self::DOCX_MIME ) );
	}

	/**
	 * Returns an empty string when handed plain text masquerading as a DOCX
	 * (PHPWord throws, the extractor catches).
	 */
	public function test_extract_returns_empty_for_non_docx_content() {
		$extractor = new WP_Document_Revisions_DOCX_Text_Extractor();

		self::assertSame( '', $extractor->extract( self::$test_file, self::DOCX_MIME ) );
	}

	/**
	 * The plugin bootstrap auto-registers the DOCX extractor.
	 */
	public function test_docx_extractor_is_registered_by_default() {
		$extractors = WP_Document_Revisions_Text_Extractor_Registry::get_extractors();

		$has_docx = false;
		foreach ( $extractors as $extractor ) {
			if ( $extractor instanceof WP_Document_Revisions_DOCX_Text_Extractor ) {
				$has_docx = true;
				break;
			}
		}

		self::assertTrue( $has_docx, 'Expected WP_Document_Revisions_DOCX_Text_Extractor in the default registry' );
	}

	/**
	 * The registry resolves the DOCX MIME type to the built-in extractor.
	 */
	public function test_registry_resolves_docx_to_built_in_extractor() {
		$extractor = WP_Document_Revisions_Text_Extractor_Registry::find_for( self::DOCX_MIME );

		self::assertInstanceOf( WP_Document_Revisions_DOCX_Text_Extractor::class, $extractor );
	}

	/**
	 * The registry resolves the ODT MIME type to the same built-in extractor.
	 */
	public function test_registry_resolves_odt_to_built_in_extractor() {
		$extractor = WP_Document_Revisions_Text_Extractor_Registry::find_for( self::ODT_MIME );

		self::assertInstanceOf( WP_Document_Revisions_DOCX_Text_Extractor::class, $extractor );
	}

	/**
	 * End-to-end: wpdr_extract_text() routes a DOCX attachment to the
	 * built-in extractor and returns the document's text.
	 */
	public function test_wpdr_extract_text_runs_docx_extractor_against_attachment() {
		$docx_path = $this->build_document(
			static function ( \PhpOffice\PhpWord\PhpWord $phpword ): void {
				$section = $phpword->addSection();
				$section->addText( 'Integration test content.' );
			}
		);

		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'DOCX Extractor Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, $docx_path );

		global $wpdr;
		$revision  = $wpdr->get_latest_revision( $doc_id );
		$attach_id = (int) $revision->post_content;

		$text = wpdr_extract_text( $attach_id );

		self::assertStringContainsString( 'Integration test content.', $text );
	}
}
