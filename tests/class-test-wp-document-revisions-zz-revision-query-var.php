<?php
/**
 * Tests that a malformed revision query var is rejected rather than fatal.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Revision query var tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Revision_Query_Var extends Test_Common_WPDR {

	/**
	 * Published document with two file versions.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create the document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$doc    = $factory->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_author'  => self::$editor,
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$doc, self::$test_file );
		self::add_document_attachment( $factory, self::$doc, self::$test_file2 );
	}

	/**
	 * Request the document with the given revision query var.
	 *
	 * @param mixed $revision revision query var value.
	 * @param int   $user     user to request as.
	 * @return int|string the wp_die() code, or the streamed output.
	 */
	private function request_revision( $revision, $user = 0 ) {
		global $wpdr;

		wp_set_current_user( $user );
		// Plain URL, so the test doesn't depend on rewrite rules flushed by earlier tests.
		$this->go_to( home_url( '/?post_type=document&p=' . self::$doc ) );
		set_query_var( 'revision', $revision );

		ob_start();
		try {
			$wpdr->serve_file( '' );
		} catch ( WPDieException $e ) {
			ob_end_clean();
			return $e->getCode();
		}
		return (string) ob_get_clean();
	}

	/**
	 * Values that aren't a revision number get the no-file response, not a TypeError.
	 *
	 * @dataProvider malformed_revisions
	 *
	 * @param mixed $revision revision query var value.
	 */
	public function test_malformed_revision_is_not_found( $revision ) {
		self::assertSame( 404, $this->request_revision( $revision ) );
		self::assertSame( 404, $this->request_revision( $revision, self::$editor ) );
	}

	/**
	 * Malformed revision query var values.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function malformed_revisions() {
		return array(
			'string'   => array( 'abc' ),
			'array'    => array( array( '1' ) ),
			'negative' => array( '-1' ),
			'decimal'  => array( '1.5' ),
		);
	}

	/**
	 * Number of the newest revision, which is known to have a file.
	 *
	 * @return string
	 */
	private function latest_revision_number() {
		global $wpdr;
		return (string) max( array_keys( $wpdr->get_revision_indices( self::$doc ) ) );
	}

	/**
	 * A real revision number is still served to a user who can read revisions.
	 */
	public function test_revision_number_still_served() {
		$output = $this->request_revision( $this->latest_revision_number(), self::$editor );

		self::assertIsString( $output );
		self::assertNotSame( '', $output );
	}

	/**
	 * A revision number is still refused to a visitor who can't read revisions.
	 */
	public function test_revision_number_still_gated() {
		self::assertSame( 403, $this->request_revision( $this->latest_revision_number() ) );
	}
}
