<?php
/**
 * Tests the response code when a document has no file to serve (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * No-document response code tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_No_Document_Response extends WP_UnitTestCase {

	/**
	 * Request a published document that has no file and return the wp_die() code.
	 *
	 * @return int
	 */
	private function serve_document_without_file() {
		global $wpdr;

		$doc = self::factory()->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		$this->go_to( get_permalink( $doc ) );

		try {
			$wpdr->serve_file( '' );
		} catch ( WPDieException $e ) {
			return $e->getCode();
		}

		$this->fail( 'Expected wp_die() for a document without a file.' );
		return 0;
	}

	/**
	 * Default 404.
	 */
	public function test_default_is_404() {
		self::assertSame( 404, $this->serve_document_without_file() );
	}

	/**
	 * The filter receives the document and can change the code.
	 */
	public function test_filter_receives_document() {
		$seen   = array();
		$filter = function ( $code, $post, $rev_id ) use ( &$seen ) {
			$seen = array( $code, $post instanceof WP_Post ? $post->post_type : null, $rev_id );
			return 410;
		};
		add_filter( 'document_no_document_response_code', $filter, 10, 3 );
		$code = $this->serve_document_without_file();
		remove_filter( 'document_no_document_response_code', $filter, 10 );

		self::assertSame( 410, $code );
		self::assertSame( 404, $seen[0] );
		self::assertSame( 'document', $seen[1] );
		self::assertIsInt( $seen[2] );
	}
}
