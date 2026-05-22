<?php
/**
 * Tests for the text extractor interface and registry.
 *
 * Phase 1 of issue #514: covers the dispatcher only, with fake extractors
 * standing in for the real PDF/DOCX implementations that land in later phases.
 *
 * @package WP_Document_Revisions
 */

/**
 * Fake extractor that returns a fixed string for a single MIME type.
 */
class WPDR_Test_Fake_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * MIME type this fake claims to support.
	 *
	 * @var string
	 */
	private $supported_mime;

	/**
	 * String to return from extract().
	 *
	 * @var string
	 */
	private $return_text;

	/**
	 * Constructor.
	 *
	 * @param string $supported_mime MIME type this fake claims.
	 * @param string $return_text    Text to return from extract().
	 */
	public function __construct( string $supported_mime, string $return_text = 'fake text' ) {
		$this->supported_mime = $supported_mime;
		$this->return_text    = $return_text;
	}

	/**
	 * Supports check.
	 *
	 * @param string $mime_type MIME type to check.
	 * @return bool
	 */
	public function supports( string $mime_type ): bool {
		return $mime_type === $this->supported_mime;
	}

	/**
	 * Extract — returns the configured fixed string.
	 *
	 * @param string $file_path file path.
	 * @param string $mime_type MIME type.
	 * @return string
	 */
	public function extract( string $file_path, string $mime_type ): string {
		unset( $file_path, $mime_type );
		return $this->return_text;
	}
}

/**
 * Fake extractor that throws a hard-failure exception.
 */
class WPDR_Test_Throwing_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * Supports any MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool
	 */
	public function supports( string $mime_type ): bool {
		unset( $mime_type );
		return true;
	}

	/**
	 * Always throws.
	 *
	 * @param string $file_path file path.
	 * @param string $mime_type MIME type.
	 * @return string
	 * @throws WP_Document_Revisions_Text_Extraction_Exception always.
	 */
	public function extract( string $file_path, string $mime_type ): string {
		unset( $file_path, $mime_type );
		throw new WP_Document_Revisions_Text_Extraction_Exception( 'boom' );
	}
}

/**
 * Tests for the text extractor registry and wpdr_extract_text().
 */
class Test_WP_Document_Revisions_Text_Extractor extends Test_Common_WPDR {

	/**
	 * Clear any filters registered during a test so they don't leak across tests.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		parent::tear_down();
	}

	/**
	 * With no filter, the registry returns an empty list.
	 */
	public function test_get_extractors_empty_by_default() {
		self::assertSame( array(), WP_Document_Revisions_Text_Extractor_Registry::get_extractors() );
	}

	/**
	 * The registry returns only objects implementing the interface, filtering
	 * out anything else a third party might accidentally append.
	 */
	public function test_get_extractors_filters_invalid_entries() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array(
					'not an object',
					new stdClass(),
					new WPDR_Test_Fake_Text_Extractor( 'application/pdf' ),
					42,
				);
			}
		);

		$extractors = WP_Document_Revisions_Text_Extractor_Registry::get_extractors();
		self::assertCount( 1, $extractors );
		self::assertInstanceOf( WPDR_Test_Fake_Text_Extractor::class, $extractors[0] );
	}

	/**
	 * If the filter returns a non-array, treat it as empty rather than crashing.
	 */
	public function test_get_extractors_handles_non_array_filter() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return null;
			}
		);

		self::assertSame( array(), WP_Document_Revisions_Text_Extractor_Registry::get_extractors() );
	}

	/**
	 * find_for picks the first extractor whose supports() returns true.
	 */
	public function test_find_for_picks_first_supporting_extractor() {
		$pdf  = new WPDR_Test_Fake_Text_Extractor( 'application/pdf', 'pdf text' );
		$docx = new WPDR_Test_Fake_Text_Extractor( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx text' );

		add_filter(
			'wpdr_text_extractors',
			static function () use ( $pdf, $docx ) {
				return array( $pdf, $docx );
			}
		);

		self::assertSame( $pdf, WP_Document_Revisions_Text_Extractor_Registry::find_for( 'application/pdf' ) );
		self::assertSame( $docx, WP_Document_Revisions_Text_Extractor_Registry::find_for( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ) );
	}

	/**
	 * find_for returns null when no registered extractor claims the MIME type.
	 */
	public function test_find_for_returns_null_when_no_match() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Fake_Text_Extractor( 'application/pdf' ) );
			}
		);

		self::assertNull( WP_Document_Revisions_Text_Extractor_Registry::find_for( 'image/png' ) );
	}

	/**
	 * find_for returns null for an empty MIME type, without scanning the registry.
	 */
	public function test_find_for_returns_null_for_empty_mime() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Fake_Text_Extractor( '' ) );
			}
		);

		self::assertNull( WP_Document_Revisions_Text_Extractor_Registry::find_for( '' ) );
	}

	/**
	 * Prepending an extractor at a higher filter priority overrides a
	 * built-in (earlier-registered) extractor for the same MIME type — this
	 * is the documented override pattern.
	 */
	public function test_prepended_extractor_overrides_earlier_match() {
		$builtin  = new WPDR_Test_Fake_Text_Extractor( 'application/pdf', 'builtin' );
		$override = new WPDR_Test_Fake_Text_Extractor( 'application/pdf', 'override' );

		add_filter(
			'wpdr_text_extractors',
			static function ( $extractors ) use ( $builtin ) {
				$extractors[] = $builtin;
				return $extractors;
			},
			10
		);

		add_filter(
			'wpdr_text_extractors',
			static function ( $extractors ) use ( $override ) {
				array_unshift( $extractors, $override );
				return $extractors;
			},
			20
		);

		$chosen = WP_Document_Revisions_Text_Extractor_Registry::find_for( 'application/pdf' );
		self::assertSame( $override, $chosen );
	}

	/**
	 * A naive append leaves the earlier-registered extractor in place — this
	 * documents the gotcha and prevents accidental regressions if someone
	 * changes the dispatcher to "last match wins."
	 */
	public function test_appended_extractor_does_not_override_earlier_match() {
		$builtin = new WPDR_Test_Fake_Text_Extractor( 'application/pdf', 'builtin' );
		$append  = new WPDR_Test_Fake_Text_Extractor( 'application/pdf', 'append' );

		add_filter(
			'wpdr_text_extractors',
			static function ( $extractors ) use ( $builtin ) {
				$extractors[] = $builtin;
				return $extractors;
			},
			10
		);

		add_filter(
			'wpdr_text_extractors',
			static function ( $extractors ) use ( $append ) {
				$extractors[] = $append;
				return $extractors;
			},
			20
		);

		$chosen = WP_Document_Revisions_Text_Extractor_Registry::find_for( 'application/pdf' );
		self::assertSame( $builtin, $chosen );
	}

	/**
	 * extract() dispatches to the matching extractor and returns its output.
	 */
	public function test_extract_dispatches_to_matching_extractor() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Fake_Text_Extractor( 'text/plain', 'hello world' ) );
			}
		);

		$result = WP_Document_Revisions_Text_Extractor_Registry::extract( self::$test_file, 'text/plain' );
		self::assertSame( 'hello world', $result );
	}

	/**
	 * extract() returns empty when no extractor supports the type.
	 */
	public function test_extract_returns_empty_when_no_match() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Fake_Text_Extractor( 'application/pdf' ) );
			}
		);

		self::assertSame( '', WP_Document_Revisions_Text_Extractor_Registry::extract( self::$test_file, 'text/plain' ) );
	}

	/**
	 * extract() returns empty when the file path is missing or unreadable.
	 */
	public function test_extract_returns_empty_for_missing_file() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Fake_Text_Extractor( 'text/plain', 'should not be called' ) );
			}
		);

		self::assertSame( '', WP_Document_Revisions_Text_Extractor_Registry::extract( '', 'text/plain' ) );
		self::assertSame( '', WP_Document_Revisions_Text_Extractor_Registry::extract( '/no/such/file.txt', 'text/plain' ) );
	}

	/**
	 * Hard-failure exceptions from the extractor are swallowed; caller sees empty.
	 */
	public function test_extract_swallows_extraction_exception() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Throwing_Text_Extractor() );
			}
		);

		$result = WP_Document_Revisions_Text_Extractor_Registry::extract( self::$test_file, 'text/plain' );
		self::assertSame( '', $result );
	}

	/**
	 * The exception class is a RuntimeException so generic catch blocks still
	 * see it as a thrown error.
	 */
	public function test_exception_extends_runtime_exception() {
		$e = new WP_Document_Revisions_Text_Extraction_Exception( 'msg' );
		self::assertInstanceOf( RuntimeException::class, $e );
		self::assertSame( 'msg', $e->getMessage() );
	}

	/**
	 * wpdr_extract_text() returns empty for invalid IDs.
	 */
	public function test_wpdr_extract_text_invalid_id() {
		self::assertSame( '', wpdr_extract_text( 0 ) );
		self::assertSame( '', wpdr_extract_text( -1 ) );
	}

	/**
	 * wpdr_extract_text() looks up the attachment, resolves mime + file, and
	 * dispatches to a registered extractor.
	 */
	public function test_wpdr_extract_text_dispatches_for_attachment() {
		add_filter(
			'wpdr_text_extractors',
			static function () {
				return array( new WPDR_Test_Fake_Text_Extractor( 'text/plain', 'extracted plain text' ) );
			}
		);

		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Text Extractor Doc',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision = $wpdr->get_latest_revision( $doc_id );
		$attach_id = (int) $revision->post_content;

		$result = wpdr_extract_text( $attach_id );
		self::assertSame( 'extracted plain text', $result );
	}

	/**
	 * wpdr_extract_text() returns empty when no extractor is registered for
	 * the attachment's MIME type — never throws, never warns.
	 */
	public function test_wpdr_extract_text_empty_when_no_extractor_registered() {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Unhandled Doc',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision  = $wpdr->get_latest_revision( $doc_id );
		$attach_id = (int) $revision->post_content;

		self::assertSame( '', wpdr_extract_text( $attach_id ) );
	}
}
