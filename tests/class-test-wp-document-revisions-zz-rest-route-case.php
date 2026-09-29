<?php
/**
 * Tests that document REST validation can't be skipped by changing the route's case.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * REST route case tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_REST_Route_Case extends WP_UnitTestCase {

	/**
	 * A document.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Subscriber (no read_document_revisions).
	 *
	 * @var int
	 */
	private static $subscriber;

	/**
	 * Create the user and document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		$wpdr->manage_rest();

		self::$subscriber = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$doc        = $factory->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * The documents REST base and namespace before the test.
	 *
	 * @var array<int, string|bool>
	 */
	private $rest = array();

	/**
	 * Expose documents at /wp/v2/documents, as document_show_in_rest does.
	 */
	public function set_up() {
		parent::set_up();
		$post_type  = get_post_type_object( 'document' );
		$this->rest = array( $post_type->rest_base, $post_type->rest_namespace );

		$post_type->rest_base      = 'documents';
		$post_type->rest_namespace = 'wp/v2';
	}

	/**
	 * Restore the REST base.
	 */
	public function tear_down() {
		$post_type = get_post_type_object( 'document' );
		list( $post_type->rest_base, $post_type->rest_namespace ) = $this->rest;
		parent::tear_down();
	}

	/**
	 * Run the validation filter on a request.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  route.
	 * @return mixed
	 */
	private function validate( $method, $route ) {
		$request = new WP_REST_Request( $method, $route );
		if ( preg_match( '#/(\d+)$#', $route, $m ) ) {
			$request->set_param( 'id', (int) $m[1] );
		}
		return WP_Document_Revisions_Manage_Rest::document_validation( null, array(), $request );
	}

	/**
	 * Data provider: casings of the documents route.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function bases() {
		return array(
			'lower' => array( '/wp/v2/documents' ),
			'title' => array( '/wp/v2/Documents' ),
			'upper' => array( '/WP/V2/DOCUMENTS' ),
		);
	}

	/**
	 * Writes are refused without the block editor, whatever the route's case.
	 *
	 * @dataProvider bases
	 * @param string $base route base.
	 */
	public function test_write_refused( $base ) {
		add_filter( 'document_use_block_editor', '__return_false', PHP_INT_MAX );
		$result = $this->validate( 'POST', $base . '/' . self::$doc );
		remove_filter( 'document_use_block_editor', '__return_false', PHP_INT_MAX );

		self::assertWPError( $result );
		self::assertSame( 'rest_cannot_modify', $result->get_error_code() );
	}

	/**
	 * Revision routes, including the collection, need read_document_revisions.
	 *
	 * @dataProvider bases
	 * @param string $base route base.
	 */
	public function test_revisions_refused( $base ) {
		wp_set_current_user( self::$subscriber );
		$result = $this->validate( 'GET', $base . '/' . self::$doc . '/revisions' );

		self::assertWPError( $result );
		self::assertSame( 'rest_cannot_read', $result->get_error_code() );
	}
}
