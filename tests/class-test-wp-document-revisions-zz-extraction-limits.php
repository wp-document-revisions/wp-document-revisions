<?php
/**
 * Tests for the file size and archive size limits on text extraction.
 *
 * @package WP_Document_Revisions
 */

/**
 * Text extraction resource limit tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Extraction_Limits extends Test_Common_WPDR {

	/**
	 * DOCX MIME type.
	 *
	 * @var string
	 */
	const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

	/**
	 * Temp files created by individual tests.
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * Load the counting fake extractor.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
	}

	/**
	 * Remove filters and temp files.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		remove_all_filters( 'wpdr_text_extraction_max_file_size' );
		remove_all_filters( 'wpdr_text_extraction_max_uncompressed_size' );
		remove_all_filters( 'wpdr_text_extraction_timeout' );
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		parent::tear_down();
	}

	/**
	 * Register a counting fake extractor ahead of the built-ins.
	 *
	 * @param string $mime MIME type the fake claims.
	 * @return WPDR_Test_Counting_Text_Extractor
	 */
	private function register_counting_fake( string $mime = 'text/plain' ): WPDR_Test_Counting_Text_Extractor {
		$fake = new WPDR_Test_Counting_Text_Extractor( $mime, 'extracted' );
		add_filter(
			'wpdr_text_extractors',
			static function ( array $extractors ) use ( $fake ): array {
				array_unshift( $extractors, $fake );
				return $extractors;
			}
		);
		return $fake;
	}

	/**
	 * Create a document with an attached file and return the attachment ID.
	 *
	 * @param string $file fixture path.
	 * @return int attachment ID.
	 */
	private function create_attachment( string $file ): int {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Extraction Limits Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, $file );

		global $wpdr;
		$revision = $wpdr->get_latest_revision( $doc_id );
		return (int) $revision->post_content;
	}

	/**
	 * Write a small DOCX to a temp file with PHPWord.
	 *
	 * @return string path to the DOCX.
	 */
	private function build_docx(): string {
		$phpword = new \PhpOffice\PhpWord\PhpWord();
		$phpword->addSection()->addText( 'Hello from a limited DOCX' );

		$dir  = trailingslashit( get_temp_dir() );
		$path = $dir . wp_unique_filename( $dir, 'wpdr_limits_' . wp_generate_password( 12, false ) . '.docx' );

		\PhpOffice\PhpWord\IOFactory::createWriter( $phpword, 'Word2007' )->save( $path );
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * A file over the size cap is not handed to the extractor by wpdr_extract_text().
	 */
	public function test_oversized_file_is_not_extracted_by_wpdr_extract_text() {
		$fake        = $this->register_counting_fake();
		$attach_id   = $this->create_attachment( self::$test_file );
		$fake->calls = 0;

		add_filter(
			'wpdr_text_extraction_max_file_size',
			static function () {
				return 1;
			}
		);

		self::assertSame( '', wpdr_extract_text( $attach_id ) );
		self::assertSame( 0, $fake->calls, 'Extractor must not run on an oversized file' );
		self::assertNull(
			WP_Document_Revisions_Text_Extractor_Cache::get( $attach_id, (string) get_attached_file( $attach_id ) ),
			'An oversized file must not be cached as a successful extraction'
		);
	}

	/**
	 * A file over the size cap is not handed to the extractor by Registry::extract().
	 */
	public function test_oversized_file_is_not_extracted_by_registry() {
		$fake = $this->register_counting_fake();

		add_filter(
			'wpdr_text_extraction_max_file_size',
			static function () {
				return 1;
			}
		);

		self::assertSame( '', WP_Document_Revisions_Text_Extractor_Registry::extract( self::$test_file, 'text/plain' ) );
		self::assertSame( 0, $fake->calls );
	}

	/**
	 * The async scheduler skips oversized files and records the failure so it doesn't retry.
	 */
	public function test_oversized_file_is_marked_failed_by_scheduler() {
		$fake        = $this->register_counting_fake();
		$attach_id   = $this->create_attachment( self::$test_file );
		$fake->calls = 0;

		add_filter(
			'wpdr_text_extraction_max_file_size',
			static function () {
				return 1;
			}
		);
		// Keep set_time_limit() out of the test process.
		add_filter( 'wpdr_text_extraction_timeout', '__return_zero' );

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );

		self::assertSame( 0, $fake->calls );
		self::assertTrue( WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, (string) get_attached_file( $attach_id ) ) );
	}

	/**
	 * A zero size cap disables the check.
	 */
	public function test_zero_file_size_cap_disables_check() {
		$fake = $this->register_counting_fake();

		add_filter( 'wpdr_text_extraction_max_file_size', '__return_zero' );

		self::assertSame( 'extracted', WP_Document_Revisions_Text_Extractor_Registry::extract( self::$test_file, 'text/plain' ) );
		self::assertSame( 1, $fake->calls );
	}

	/**
	 * A DOCX whose uncompressed contents exceed the cap is rejected before PHPWord sees it.
	 */
	public function test_docx_over_uncompressed_cap_is_rejected() {
		$path = $this->build_docx();

		add_filter(
			'wpdr_text_extraction_max_uncompressed_size',
			static function () {
				return 10;
			}
		);

		self::assertSame( '', WP_Document_Revisions_Text_Extractor_Registry::extract( $path, self::DOCX_MIME ) );

		$this->expectException( WP_Document_Revisions_Text_Extraction_Exception::class );
		WP_Document_Revisions_Text_Extractor_Registry::check_limits( $path, self::DOCX_MIME );
	}

	/**
	 * A normal DOCX still extracts under the default limits.
	 */
	public function test_normal_docx_still_extracts() {
		$path = $this->build_docx();

		$text = WP_Document_Revisions_Text_Extractor_Registry::extract( $path, self::DOCX_MIME );

		self::assertStringContainsString( 'Hello from a limited DOCX', $text );
	}

	/**
	 * The PDF fixture still extracts under the default limits.
	 */
	public function test_normal_pdf_still_extracts() {
		$attach_id = $this->create_attachment( self::$pdf_file );

		self::assertMatchesRegularExpression( '/[A-Za-z]/', wpdr_extract_text( $attach_id ) );
	}

	/**
	 * The REST diff route applies the extraction timeout before extracting synchronously.
	 */
	public function test_rest_diff_applies_extraction_timeout() {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Extraction Limits Diff',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file2 );

		global $wpdr;
		$rev_id = (int) $wpdr->get_latest_revision( $doc_id )->post_content;

		$seen = array();
		add_filter(
			'wpdr_text_extraction_timeout',
			static function ( $timeout, $attachment_id ) use ( &$seen ) {
				$seen[] = array( $timeout, $attachment_id );
				// Keep set_time_limit() out of the test process.
				return 0;
			},
			10,
			2
		);

		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'doc_id', $doc_id );
		$request->set_param( 'rev_id', $rev_id );
		$response = WP_Document_Revisions_AI_Summary_REST::get_diff( $request );

		self::assertSame( 200, $response->get_status() );
		self::assertNotSame( 0, $response->get_data()['prior_revision'] );
		self::assertSame(
			array( array( WP_Document_Revisions_Text_Extractor_Scheduler::DEFAULT_TIMEOUT, $rev_id ) ),
			$seen
		);
	}
}
