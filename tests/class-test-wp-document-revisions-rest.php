<?php
/**
 * Tests admin functionality.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * REST tests
 */
class Test_WP_Document_Revisions_Rest extends Test_Common_WPDR {

	/**
	 * Documents are part of Standard WP.
	 *
	 * @var string
	 */
	protected $namespaced_route = 'wp/v2';

	/**
	 * Editor user id
	 *
	 * @var integer $editor_user_id
	 */
	private static $editor_user_id;

	/**
	 * Workflow_state term id
	 *
	 * @var integer $ws_term_id
	 */
	private static $ws_term_id;

	/**
	 * Editor Public Post ID
	 *
	 * @var integer $editor_public_post
	 */
	private static $editor_public_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer $editor_private_post
	 */
	private static $editor_private_post;

	/**
	 * Editor Public Post 2 ID
	 *
	 * @var integer $editor_public_post_2
	 */
	private static $editor_public_post_2;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// set permalink structure to Month and name string.
		global $wp_rewrite, $orig;
		$orig = $wp_rewrite->permalink_structure;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		// flush cache for good measure.
		wp_cache_flush();

		// phpcs:enable
		// switch rest on.
		add_filter( 'document_show_in_rest', '__return_true' );

		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// make sure that we have the admin set up.
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// make sure that we have the rest set up.
		if ( ! class_exists( 'WP_Document_Revisions_Manage_Rest' ) ) {
			$wpdr->manage_rest();
		}

		global $wpdr_mr;
		self::assertNotNull( $wpdr_mr, 'Class Manage_Rest not defined' );

		// set up the rest server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		// create users.
		// Note that editor can do everything admin can do. Contributors cannot actually upload files by default.
		self::$editor_user_id = $factory->user->create(
			array(
				'user_nicename' => 'Editor',
				'role'          => 'editor',
			)
		);

		// init user roles.
		$wpdr->add_caps();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->register_ct();

		// Check no values.
		$ws_terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::assertEquals( 0, count( $ws_terms ), 'Taxonomy not empty' );

		$wpdr->initialize_workflow_states();

		// Taxonomy terms recreated as fixtures.
		$ws_terms         = self::create_term_fixtures( $factory );
		self::$ws_term_id = (int) $ws_terms[0]->term_id;

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_public_post, array( self::$ws_term_id ), 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document Editor Private' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, array( self::$ws_term_id ), 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );

		// Editor Public 2.
		self::$editor_public_post_2 = $factory->post->create(
			array(
				'post_title'   => 'Editor Public 2 - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_public_post_2 ), 'Failed inserting document Editor Public 2' );

		// add term and two attachments.
		$terms = wp_set_post_terms( self::$editor_public_post_2, array( self::$ws_term_id ), 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_public_post_2, self::$test_file );
		self::add_document_attachment( $factory, self::$editor_public_post_2, self::$test_file2 );
	}

	/**
	 * Delete the posts. (Taken from WP Test Suite).
	 */
	public static function wpTearDownAfterClass() {
		// remove terms.
		wp_remove_object_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );
		wp_remove_object_terms( self::$editor_public_post, self::$ws_term_id, 'workflow_state' );

		// make sure that we have the admin set up.
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$editor_private_post, true );
		wp_delete_post( self::$editor_public_post, true );
		wp_delete_post( self::$editor_public_post_2, true );

		// delete done, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		// clear down the ws terms.
		$ws_terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);

		// delete them all.
		foreach ( $ws_terms as $ws_term ) {
			wp_delete_term( $ws_term->term_id, 'workflow_state' );
			clean_term_cache( $ws_term->term_id, 'workflow_state' );
		}

		unregister_taxonomy( 'workflow_state' );

		// reset permalink structure.
		global $wp_rewrite, $orig;
		$wp_rewrite->set_permalink_structure( $orig );

		// switch rest off.
		remove_filter( 'document_show_in_rest', '__return_true' );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$editor_public_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
		self::verify_structure( self::$editor_public_post_2, 2, 2 );
	}

	/**
	 * Tests that the document routes are there.
	 */
	public function test_register_route() {
		global $wp_rest_server;
		$routes = $wp_rest_server->get_routes( $this->namespaced_route );
		self::assertNotEmpty( $routes, 'No document routes' );
	}

	/**
	 * Tests the document endpoints.
	 */
	public function test_endpoints() {
		global $wp_rest_server;
		// only want the default documents one.
		$the_route = $this->namespaced_route . '/documents/';
		$routes    = $wp_rest_server->get_routes( $this->namespaced_route );
		self::assertNotEmpty( $routes, 'No document routes' );

		foreach ( $routes as $route => $route_config ) {
			if ( 1 === strpos( $route, $the_route ) ) {
				self::assertTrue( is_array( $route_config ) );
				foreach ( $route_config as $i => $endpoint ) {
					self::assertArrayHasKey( 'callback', $endpoint );
					self::assertArrayHasKey( 0, $endpoint['callback'], get_class( $this ) );
					self::assertArrayHasKey( 1, $endpoint['callback'], get_class( $this ) );
					self::assertTrue( is_callable( array( $endpoint['callback'][0], $endpoint['callback'][1] ) ) );
				}
			}
		}
	}

	/**
	 * Tests the public  query.
	 */
	public function test_get_items_noauth() {
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wp_rest_server;
		// Two public posts.
		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $wp_rest_server->dispatch( $request );
		self::assertEquals( 200, $response->get_status() );
		self::assertEquals( 2, count( $response->get_data() ) );

		global $wp_filter;
		console_log( 'filter set: ' . +isset( $wp_filter['rest_request_before_callbacks'] ) );
	}

	/**
	 * Tests the public query.
	 */
	public function test_get_items_editor() {
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		global $wp_rest_server;
		// Two public posts and one private post (not seen).
		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $wp_rest_server->dispatch( $request );
		self::assertEquals( 200, $response->get_status() );
		self::assertEquals( 2, count( $response->get_data() ) );
	}

	/**
	 * Tests the public query with filter.
	 */
	public function test_get_items_editor_filtered() {
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		global $wp_filter;
		console_log( 'filter set: ' . +isset( $wp_filter['rest_prepare_document'] ) );
		if ( ! isset( $wp_filter['rest_prepare_document'] ) ) {
			global $wpdr_mr;
			add_filter( 'rest_prepare_document', array( $wpdr_mr, 'doc_clean_document' ), 10, 3 );
		}
		console_log( 'filter set: ' . +isset( $wp_filter['rest_prepare_document'] ) );

		global $wp_rest_server;
		// Two public posts and one private post (not seen).
		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $wp_rest_server->dispatch( $request );

		self::assertEquals( 200, $response->get_status() );
		self::assertEquals( 2, count( $response->get_data() ) );

		ob_start();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
		var_dump( $response->get_data() );
		$output = ob_get_clean();
		console_log( $output );
	}
}
