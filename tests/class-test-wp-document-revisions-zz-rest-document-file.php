<?php
/**
 * Tests the read-only document_file REST field (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * REST document_file field tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Rest_Document_File extends Test_Common_WPDR {

	/**
	 * Document with a file.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Owner (editor).
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Subscriber.
	 *
	 * @var int
	 */
	private static $subscriber;

	/**
	 * Create users and a document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor     = $factory->user->create( array( 'role' => 'editor' ) );
		self::$subscriber = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$doc        = $factory->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_author'  => self::$editor,
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$doc, self::$test_file );
	}

	/**
	 * Put documents in REST.
	 */
	public function set_up() {
		global $wpdr, $wpdr_mr, $wp_rest_server;
		parent::set_up();

		// $wpdr_mr is created once, during an earlier test's rest_api_init, and the test
		// framework removes hooks added during a test afterwards, so re-add ours.
		$wpdr->manage_rest();
		add_action( 'rest_api_init', array( $wpdr_mr, 'register_document_file_field' ), 20 );

		add_filter( 'document_show_in_rest', '__return_true' );
		// A fresh post type object, so its REST controller doesn't reuse a schema cached by
		// earlier test classes before this field was registered. In a request the schema is
		// built after rest_api_init.
		unregister_post_type( 'document' );
		$wpdr->register_cpt();
		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		do_action( 'rest_api_init' );
	}

	/**
	 * Take documents out of REST again.
	 */
	public function tear_down() {
		global $wpdr, $wp_rest_server;
		remove_filter( 'document_show_in_rest', '__return_true' );
		$wpdr->register_cpt();
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		parent::tear_down();
	}

	/**
	 * Fetch the document over REST as a user.
	 *
	 * @param int $user_id user ID.
	 * @return array<string, mixed>
	 */
	private function get_document_as( $user_id ) {
		global $wpdr;
		wp_set_current_user( $user_id );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/' . $wpdr->document_slug() . '/' . self::$doc );
		$response = rest_get_server()->dispatch( $request );
		self::assertSame( 200, $response->get_status() );
		return $response->get_data();
	}

	/**
	 * Editors get the file details, with the permalink rather than the storage URL.
	 */
	public function test_editor_sees_file() {
		global $wpdr;

		$data = $this->get_document_as( self::$editor );

		self::assertArrayHasKey( 'document_file', $data );
		self::assertSame( $wpdr->get_document( self::$doc )->ID, $data['document_file']['attachment_id'] );
		self::assertSame( 'text/plain', $data['document_file']['mime_type'] );
		self::assertSame( 'txt', $data['document_file']['extension'] );
		self::assertIsInt( $data['document_file']['filesize'] );
		self::assertSame( get_permalink( self::$doc ), $data['document_file']['url'] );
	}

	/**
	 * Users who can only read the document get null.
	 */
	public function test_reader_gets_null() {
		$data = $this->get_document_as( self::$subscriber );

		self::assertArrayHasKey( 'document_file', $data );
		self::assertNull( $data['document_file'] );
	}
}
