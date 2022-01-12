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
					self::assertArrayHasKey( 'permission_callback', $endpoint );
					self::assertArrayHasKey( 0, $endpoint['permission_callback'], get_class( $this ) );
					self::assertArrayHasKey( 1, $endpoint['permission_callback'], get_class( $this ) );
					self::assertTrue( is_callable( array( $endpoint['permission_callback'][0], $endpoint['permission_callback'][1] ) ) );
				}
			}
		}
	}

	/**
	 * Tests the document endpoints.
	 */
	public function test_other_endpoints() {
		global $wp_rest_server;
		// only want the default documents one.
		$the_route = $this->namespaced_route . '/media';
		$routes    = $wp_rest_server->get_routes( $this->namespaced_route );
		self::assertNotEmpty( $routes, 'No media routes' );

		self::assertTrue( array_key_exists( '/wp/v2/media', $routes ), 'media not present' );
		self::assertTrue( array_key_exists( '/wp/v2/media/(?P<id>[\d]+)', $routes ), 'media/id not present' );
	}

	/**
	 * Tests the public query.
	 */
	public function test_get_items_noauth() {
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// make sure rest functions are explicitly defined.
		global $wpdr_mr;
		add_filter( 'rest_request_before_callbacks', array( $wpdr_mr, 'document_validation' ), 10, 3 );

		add_filter( 'rest_prepare_document', array( $wpdr_mr, 'doc_clean_document' ), 10, 3 );
		add_filter( 'rest_prepare_revision', array( $wpdr_mr, 'doc_clean_revision' ), 10, 3 );
		add_filter( 'rest_prepare_attachment', array( $wpdr_mr, 'doc_clean_attachment' ), 10, 3 );

		global $wp_rest_server;
		// Two public posts.
		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $wp_rest_server->dispatch( $request );
		self::assertEquals( 200, $response->get_status() );

		$responses = $response->get_data();
		self::assertEquals( 2, count( $responses ) );

		// separate out which is which.
		if ( self::$editor_public_post === $responses[1]['id'] && self::$editor_public_post_2 === $responses[0]['id'] ) {
			// expected order (descending).
			$p1 = $responses[1];
			$p2 = $responses[0];
		} elseif ( self::$editor_public_post === $responses[0]['id'] && self::$editor_public_post_2 === $responses[1]['id'] ) {
			// alternative order.
			$p1 = $responses[0];
			$p2 = $responses[1];
		} else {
			self::assertFalse( true, 'Expected posts not returned' );
		}

		// validate parts.
		self::assertSame( $p1['status'], 'publish', 'wrong status 1' );
		self::assertSame( $p2['status'], 'publish', 'wrong status 2' );
		self::assertSame( $p1['type'], 'document', 'wrong type 1' );
		self::assertSame( $p2['type'], 'document', 'wrong type 2' );
		self::assertEquals( 1, (int) substr_count( $p1['link'], $p1['slug'] ), 'slug not in link 1' );
		self::assertEquals( 1, (int) substr_count( $p2['link'], $p2['slug'] ), 'slug not in link 2' );

		// public should not see versions or attachments.
		self::assertFalse( array_key_exists( 'version-history', $p2['_links'] ), 'version history' );
		self::assertFalse( array_key_exists( 'predecessor-version', $p2['_links'] ), 'previous version' );
		self::assertFalse( array_key_exists( 'wp:attachment', $p1['_links'] ), 'p1 attachment' );
		self::assertFalse( array_key_exists( 'wp:attachment', $p2['_links'] ), 'p2 attachment' );

		// try the attachment query via parent.
		$request = new WP_REST_Request( 'GET', '/wp/v2/media' );
		$request->set_param( 'parent', self::$editor_public_post );
		$response = $wp_rest_server->dispatch( $request );

		// can read it.
		self::assertEquals( 200, $response->get_status(), 'cannot read attachment' );
		$responses = $response->get_data();
		self::assertEquals( 1, count( $responses ), 'not one response 1' );
		$response = $responses[0];
		self::assertSame( $response['type'], 'attachment', 'wrong type attachment 1' );

		// elements are protected.
		self::assertSame( $response['slug'], '<!-- protected -->', 'wrong status 1' );
		self::assertSame( $response['title']['rendered'], '<!-- protected -->', 'wrong title 1' );

		// try the attachment query directly. Note a single array returned.
		global $wpdr;
		$attach = $wpdr->get_document( self::$editor_public_post );
		self::assertTrue( $attach instanceof WP_Post, 'not a post' );
		self::assertSame( $attach->post_type, 'attachment', 'not an attachment' );

		$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/media/%d', $attach->ID ) );
		$response = $wp_rest_server->dispatch( $request );

		// can read it.
		self::assertEquals( 200, $response->get_status(), 'cannot read attachment' );
		$response = $response->get_data();
		self::assertEquals( 24, count( $response ), 'not single response' );
		self::assertSame( $response['type'], 'attachment', 'wrong type attachment 2' );
		self::assertEquals( $response['id'], $attach->ID, 'wrong attachment 2' );

		// elements are protected.
		self::assertSame( $response['slug'], '<!-- protected -->', 'wrong status 2' );
		self::assertSame( $response['title']['rendered'], '<!-- protected -->', 'wrong title 2' );

		// find a revision. Should not be available.
		$revns = $wpdr->get_revisions( self::$editor_public_post_2 );
		if ( array_key_exists( 1, $revns ) ) {
			// try a revisions query.
			$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/documents/%d/revisions/%d', self::$editor_public_post_2, $revns[1]->ID ) );
			$response = $wp_rest_server->dispatch( $request );

			self::assertEquals( 401, $response->get_status(), 'Authorization error' );
			$revision = $response->get_data();
			self::assertEquals( 'rest_cannot_read', $revision['code'], 'revision wrong code' );
			self::assertEquals( 'Sorry, you are not allowed to view revisions.', $revision['message'], 'revision wrong message' );
		} else {
			self::assertFalse( true, 'no revision found' );
		}
	}

	/**
	 * Tests the public query.
	 */
	public function test_get_items_editor() {
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// make sure rest functions are explicitly defined.
		global $wpdr_mr;
		add_filter( 'rest_request_before_callbacks', array( $wpdr_mr, 'document_validation' ), 10, 3 );

		add_filter( 'rest_prepare_document', array( $wpdr_mr, 'doc_clean_document' ), 10, 3 );
		add_filter( 'rest_prepare_revision', array( $wpdr_mr, 'doc_clean_revision' ), 10, 3 );
		add_filter( 'rest_prepare_attachment', array( $wpdr_mr, 'doc_clean_attachment' ), 10, 3 );

		global $wp_rest_server;
		// Two public posts.
		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $wp_rest_server->dispatch( $request );
		self::assertEquals( 200, $response->get_status() );

		$responses = $response->get_data();
		self::assertEquals( 2, count( $responses ) );

		// separate out which is which.
		if ( self::$editor_public_post === $responses[1]['id'] && self::$editor_public_post_2 === $responses[0]['id'] ) {
			// expected order (descending).
			$p1 = $responses[1];
			$p2 = $responses[0];
		} elseif ( self::$editor_public_post === $responses[0]['id'] && self::$editor_public_post_2 === $responses[1]['id'] ) {
			// alternative order.
			$p1 = $responses[0];
			$p2 = $responses[1];
		} else {
			self::assertFalse( true, 'Expected posts not returned' );
		}

		// validate parts.
		self::assertSame( $p1['status'], 'publish', 'wrong status 1' );
		self::assertSame( $p2['status'], 'publish', 'wrong status 2' );
		self::assertSame( $p1['type'], 'document', 'wrong type 1' );
		self::assertSame( $p2['type'], 'document', 'wrong type 2' );
		self::assertEquals( 1, (int) substr_count( $p1['link'], $p1['slug'] ), 'slug not in link 1' );
		self::assertEquals( 1, (int) substr_count( $p2['link'], $p2['slug'] ), 'slug not in link 2' );

		// editor should see versions or attachments.
		self::assertTrue( array_key_exists( 'version-history', $p2['_links'] ), 'version history' );
		self::assertTrue( array_key_exists( 'predecessor-version', $p2['_links'] ), 'previous version' );
		self::assertTrue( array_key_exists( 'wp:attachment', $p1['_links'] ), 'p1 attachment' );
		self::assertTrue( array_key_exists( 'wp:attachment', $p2['_links'] ), 'p2 attachment' );

		// try the attachment query via parent.
		$request = new WP_REST_Request( 'GET', '/wp/v2/media' );
		$request->set_param( 'parent', self::$editor_public_post );
		$response = $wp_rest_server->dispatch( $request );

		// can read it.
		self::assertEquals( 200, $response->get_status(), 'cannot read attachment' );
		$responses = $response->get_data();
		self::assertEquals( 1, count( $responses ), 'not one response 1' );
		$response = $responses[0];
		self::assertSame( $response['type'], 'attachment', 'wrong type attachment 1' );

		// elements are not protected.
		self::assertNotSame( $response['slug'], '<!-- protected -->', 'wrong status 1' );
		self::assertNotSame( $response['title']['rendered'], '<!-- protected -->', 'wrong title 1' );

		// try the attachment query directly. Note a single array returned.
		global $wpdr;
		$attach = $wpdr->get_document( self::$editor_public_post );
		self::assertTrue( $attach instanceof WP_Post, 'not a post' );
		self::assertSame( $attach->post_type, 'attachment', 'not an attachment' );

		$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/media/%d', $attach->ID ) );
		$response = $wp_rest_server->dispatch( $request );

		// can read it.
		self::assertEquals( 200, $response->get_status(), 'cannot read attachment 2' );
		$response = $response->get_data();
		self::assertEquals( 24, count( $response ), 'not single response 2' );
		self::assertEquals( $response['id'], $attach->ID, 'wrong attachment 2' );
		self::assertSame( $response['type'], 'attachment', 'wrong type attachment 2' );

		// elements are not protected.
		self::assertNotSame( $response['slug'], '<!-- protected -->', 'wrong status 2' );
		self::assertNotSame( $response['title']['rendered'], '<!-- protected -->', 'wrong title 2' );

		// find a revision.
		global $wpdr;
		$revns = $wpdr->get_revisions( self::$editor_public_post_2 );
		if ( array_key_exists( 1, $revns ) ) {
			// try a revisions query.
			$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/documents/%d/revisions/%d', self::$editor_public_post_2, $revns[1]->ID ) );
			$response = $wp_rest_server->dispatch( $request );
			$revision = $response->get_data();
			self::assertSame( $revision['id'], $revns[1]->ID, 'not correct id' );
			self::assertSame( $revision['parent'], self::$editor_public_post_2, 'not correct parent' );
			self::assertSame( $revision['slug'], self::$editor_public_post_2 . '-revision-v1', 'not correct slug' );
		} else {
			self::assertFalse( true, 'no revision found' );
		}
	}
}
