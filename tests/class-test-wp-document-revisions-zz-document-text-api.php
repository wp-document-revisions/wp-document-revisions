<?php
/**
 * Tests wpdr_get_document_text() and the extracted/cleared text actions (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document text API tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Document_Text_Api extends Test_Common_WPDR {

	/**
	 * Load the fake extractor.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
	}

	/**
	 * Register a fake text/plain extractor.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wpdr_text_extractors', array( $this, 'add_fake' ) );
	}

	/**
	 * Remove the fake extractor.
	 */
	public function tear_down() {
		remove_filter( 'wpdr_text_extractors', array( $this, 'add_fake' ) );
		parent::tear_down();
	}

	/**
	 * Prepend a fake extractor that returns fixed text.
	 *
	 * @param array $extractors registered extractors.
	 * @return array
	 */
	public function add_fake( $extractors ) {
		array_unshift( $extractors, new WPDR_Test_Counting_Text_Extractor( 'text/plain', 'Fixed extracted text' ) );
		return $extractors;
	}

	/**
	 * Create a document with a text file.
	 *
	 * @return int[] document and attachment IDs.
	 */
	private function create_document() {
		global $wpdr;

		$doc = self::factory()->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		self::add_document_attachment( self::factory(), $doc, self::$test_file );

		return array( $doc, $wpdr->get_document( $doc )->ID );
	}

	/**
	 * The helper returns the current file's text, firing wpdr_text_extracted with the document ID.
	 */
	public function test_get_document_text_and_extracted_action() {
		list( $doc, $attach ) = $this->create_document();

		$fired = array();
		$spy   = function ( ...$args ) use ( &$fired ) {
			$fired = $args;
		};
		add_action( 'wpdr_text_extracted', $spy, 10, 2 );
		$text = wpdr_get_document_text( $doc );
		remove_action( 'wpdr_text_extracted', $spy, 10 );

		self::assertSame( 'Fixed extracted text', $text );
		self::assertSame( array( $attach, $doc ), $fired );
	}

	/**
	 * Non-documents and documents without a file return ''.
	 */
	public function test_get_document_text_empty_cases() {
		self::assertSame( '', wpdr_get_document_text( 0 ) );
		self::assertSame( '', wpdr_get_document_text( self::factory()->post->create() ) );
		self::assertSame( '', wpdr_get_document_text( self::factory()->post->create( array( 'post_type' => 'document' ) ) ) );
	}

	/**
	 * Clearing cached text fires wpdr_text_cleared, but only when there was text.
	 */
	public function test_cleared_action() {
		list( $doc, $attach ) = $this->create_document();
		wpdr_get_document_text( $doc );

		$fired = array();
		$spy   = function ( ...$args ) use ( &$fired ) {
			$fired[] = $args;
		};
		add_action( 'wpdr_text_cleared', $spy, 10, 2 );
		WP_Document_Revisions_Text_Extractor_Cache::clear( $attach );
		WP_Document_Revisions_Text_Extractor_Cache::clear( $attach );
		remove_action( 'wpdr_text_cleared', $spy, 10 );

		self::assertSame( array( array( $attach, $doc ) ), $fired );
	}
}
