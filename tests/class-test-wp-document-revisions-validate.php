<?php
/**
 * Tests validate structure functionality.
 *
 * @author Neil James <neil@familyjames.com>
 * @package WP_Document_Revisions
 */

/**
 * Admin tests
 */
class Test_WP_Document_Revisions_Validate extends Test_Common_WPDR {

	/**
	 * Package namespace.
	 *
	 * @var string
	 */
	protected $namespaced_route = 'wpdr/v1';

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
	}

	/**
	 * Tests that the test Document structures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$editor_public_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
		self::verify_structure( self::$editor_public_post_2, 2, 2 );
	}

	/**
	 * Tests that the test Document structures are valid.
	 */
	public function test_structure_OK() {
		// test with no user, nothing found.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as no user...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - structure_ok' );

		// now test with editor - should be nothing found.
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as all valid...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'edit - structure_ok' );
	}

	/**
	 * Tests that the missing file is detected.
	 */
	public function test_struct_missing_file() {
		// test with no user, nothing found.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// Get file from $editor_public_post_2.
		global $wpdr;
		$attach = $wpdr->get_document( self::$editor_public_post_2 );
		self::assertTrue( $attach instanceof WP_Post, 'struct_missing_file_attach' );
		$file = get_attached_file( $attach->ID );
		// Move $file.
		rename( $file, $file . '.txt' );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as no user...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - no edit' );

		// now test with editor - should be invalid found.
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should have two rows - the header row.
		self::assertEquals( 2, (int) substr_count( $output, '<tr' ), 'test_struct_missing_file_cnt' );
		self::assertEquals( 1, (int) substr_count( $output, 'Document attachment exists but related file not found' ), 'test_struct_missing_file_msg' );

		// Move $file back.
		rename( $file . '.txt', $file );
	}

	/**
	 * Tests that the missing content is detected.
	 */
	public function test_struct_missing_content() {
		// test with no user, nothing found.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// get the post_content from $editor_public_post_2.
		$content = get_post_field( 'post_content', self::$editor_public_post_2, 'db' );

		global $wpdr;
		$attach_id = $wpdr->extract_document_id( $content );

		// expected fix text parameters.
		$fix_parms = '(' . self::$editor_public_post_2 . ',4,' . $attach_id . ')';

		// clean post cache.
		clean_post_cache( self::$editor_public_post_2 );

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}posts 
				 SET post_content = ''
				 WHERE ID = %d
				",
				self::$editor_public_post_2
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		self::assertEquals( 1, $rows, 'test_struct_missing_rows_1' );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as no user...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - no edit' );

		// now test with editor - should be invalid found.
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should have two rows - the header row.
		self::assertEquals( 2, (int) substr_count( $output, '<tr' ), 'test_struct_missing_cnt' );
		self::assertEquals( 1, (int) substr_count( $output, 'Attachment found for document, but not currently linked' ), 'message not found' );
		self::assertEquals( 1, (int) substr_count( $output, $fix_parms ), 'fix parms not found' );

		// will be a row like wpdr_valid_fix(106,4,109). - Can use it to mend document.
		$request = new WP_REST_Request(
			'PUT',
			'/wpdr/v1/correct/' . self::$editor_public_post_2 . '/type/4/attach/' . $attach_id
		);
		$request->set_param( 'id', self::$editor_public_post_2 );
		$request->set_param( 'code', 4 );
		$request->set_param( 'parm', $attach_id );
		$request->set_body( '{"userid":"' . self::$editor_user_id . '"}' );

		$response = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		self::assertInstanceOf( 'WP_Rest_Response', $response, 'not a valid response' );
		self::assertEquals( 200, $response->get_status(), 'success not returned' );
		self::assertEquals( 'Success.', $response->get_data(), 'not expected response' );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as fixed...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - now fixed' );

		// put content back.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}posts 
				 SET post_content = %s
				 WHERE ID = %d
				",
				$content,
				self::$editor_public_post_2
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		// should already been fixed but is a null update.
		self::assertSame( 0, $rows, 'null update did not happen.' );
	}

	/**
	 * Tests that wrong content is detected.
	 */
	public function test_struct_wrong_content() {
		// test with no user, nothing found.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// get the post_content from $editor_public_post_2.
		$content = get_post_field( 'post_content', self::$editor_public_post_2, 'db' );

		global $wpdr;
		$attach_id = $wpdr->extract_document_id( $content );

		// expected fix text parameters.
		$fix_parms = '(' . self::$editor_public_post_2 . ',5,' . $attach_id . ')';

		// clean post cache.
		clean_post_cache( self::$editor_public_post_2 );

		// create wrong content.
		$wrong = '<!-- WPDR ' . self::$editor_public_post_2 . ' -->';

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}posts 
				 SET post_content = %s
				 WHERE ID = %d
				",
				$wrong,
				self::$editor_public_post_2
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		self::assertEquals( 1, $rows, 'test_struct_missing_rows_1' );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as no user...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - no edit' );

		// now test with editor - should be invalid found.
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should have two rows - the header row.
		self::assertEquals( 2, (int) substr_count( $output, '<tr' ), 'test_struct_missing_cnt' );
		self::assertEquals( 1, (int) substr_count( $output, 'Document links to invalid attachment. An attachment exists and can replace link' ), 'message not found' );
		self::assertEquals( 1, (int) substr_count( $output, $fix_parms ), 'fix parms not found' );

		// will be a row like wpdr_valid_fix(106,5,109). - Can use it to mend document.
		$request = new WP_REST_Request(
			'PUT',
			'/wpdr/v1/correct/' . self::$editor_public_post_2 . '/type/5/attach/' . $attach_id
		);
		$request->set_param( 'id', self::$editor_public_post_2 );
		$request->set_param( 'code', 5 );
		$request->set_param( 'parm', $attach_id );
		$request->set_body( '{"userid":"' . self::$editor_user_id . '"}' );

		$response = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		self::assertInstanceOf( 'WP_Rest_Response', $response, 'not a valid response' );
		self::assertEquals( 200, $response->get_status(), 'success not returned' );
		self::assertEquals( 'Success.', $response->get_data(), 'not expected response' );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as fixed...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - now fixed' );

		// put content back.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}posts 
				 SET post_content = %s
				 WHERE ID = %d
				",
				$content,
				self::$editor_public_post_2
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		// should already been fixed but is a null update.
		self::assertSame( 1, $rows, 'null update did not happen.' );
	}

	/**
	 * Tests that name not in md5 format is detected.
	 */
	public function test_struct_not_md5() {
		// test with no user, nothing found.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// get the post_content from $editor_public_post_2.
		$content = get_post_field( 'post_content', self::$editor_public_post_2, 'db' );

		global $wpdr;
		$attach_id = $wpdr->extract_document_id( $content );

		// expected fix text parameters.
		$fix_parms = '(' . self::$editor_public_post_2 . ',6,' . $attach_id . ')';

		// clean post cache.
		clean_post_cache( self::$editor_public_post_2 );

		// change file name.
		$file = get_attached_file( $attach_id );

		// change the meta data.
		$fname = get_post_meta( $attach_id, '_wp_attached_file', true );
		$nname = preg_replace( '|^([0-9]{4}/[0-9]{2}/)([a-f0-9]{32})(.{4})$|', '$1X$2X$3', $fname );
		update_post_meta( $attach_id, '_wp_attached_file', $nname, $fname );

		$nfile = str_replace( $fname, $nname, $file );

		// Move $file.
		rename( $file, $nfile );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as no user...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - no edit' );

		// now test with editor - should be invalid found.
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should have two rows - the header row.
		self::assertEquals( 2, (int) substr_count( $output, '<tr' ), 'test_struct_missing_cnt' );
		self::assertEquals( 1, (int) substr_count( $output, 'Document attachment does not appear to be md5 encoded' ), 'message not found' );
		self::assertEquals( 1, (int) substr_count( $output, $fix_parms ), 'fix parms not found' );

		// will be a row like wpdr_valid_fix(106,6,109). - Can use it to mend document.
		$request = new WP_REST_Request(
			'PUT',
			'/wpdr/v1/correct/' . self::$editor_public_post_2 . '/type/6/attach/' . $attach_id
		);
		$request->set_param( 'id', self::$editor_public_post_2 );
		$request->set_param( 'code', 6 );
		$request->set_param( 'parm', $attach_id );
		$request->set_body( '{"userid":"' . self::$editor_user_id . '"}' );

		$response = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		self::assertInstanceOf( 'WP_Rest_Response', $response, 'not a valid response' );
		self::assertEquals( 200, $response->get_status(), 'success not returned' );
		self::assertEquals( 'Success.', $response->get_data(), 'not expected response' );

		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();

		// should be nothing found - as fixed...
		self::assertEquals( 1, (int) substr_count( $output, 'No invalid documents found' ), 'none - now fixed' );
	}

	/**
	 * Tests that for wrong location - test message only.
	 */
	public function test_struct_wrong_locn() {
		// test with no user, nothing found.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// get the post_content from $editor_public_post_2.
		$content = get_post_field( 'post_content', self::$editor_public_post_2, 'db' );

		global $wpdr;
		$attach_id = $wpdr->extract_document_id( $content );

		// fix text parameters to test.
		$fix_parms = '(' . self::$editor_public_post_2 . ',7,' . $attach_id . ')';

		// Will try a row like wpdr_valid_fix(106,7,109). - Can use it to mend document.
		$request = new WP_REST_Request(
			'PUT',
			'/wpdr/v1/correct/' . self::$editor_public_post_2 . '/type/7/attach/' . $attach_id
		);
		$request->set_param( 'id', self::$editor_public_post_2 );
		$request->set_param( 'code', 7 );
		$request->set_param( 'parm', $attach_id );
		$request->set_body( '{"userid":"' . self::$editor_user_id . '"}' );

		$response = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		self::assertInstanceOf( 'WP_Error', $response, 'not a valid response' );
		self::assertEquals( 'inconsistent_parms', $response->get_error_code(), 'error not returned' );
		self::assertEquals( 'Inconsistent data sent to Interface', $response->get_error_message(), 'not expected response' );
	}

	/**
	 * Tests that the package routes are there.
	 */
	public function test_register_route() {
		global $wp_rest_server;
		$routes = $wp_rest_server->get_routes( $this->namespaced_route );
		self::assertNotEmpty( $routes, 'No document routes' );
	}

	/**
	 * Tests that the package endpoints.
	 */
	public function test_endpoints() {
		global $wp_rest_server;
		// only want the default documents one and not the namespace one.
		$the_route = $this->namespaced_route . '/';
		$routes    = $wp_rest_server->get_routes( $this->namespaced_route );
		self::assertNotEmpty( $routes, 'No document routes' );

		foreach ( $routes as $route => $route_config ) {
			// routes have a leading slash.
			if ( 1 === strpos( $route, $the_route ) ) {
				// expect only the correct route.
				self::assertEquals( 1, (int) substr_count( $route, '/correct/' ), 'correct route not found' );

				self::assertTrue( is_array( $route_config ) );
				self::assertArrayHasKey( 0, $route_config, 'zero element' );
				self::assertEquals( 1, count( $route_config ), 'route_config too many rows' );
				$route_parms = $route_config[0];

				// check PUT method.
				self::assertArrayHasKey( 'methods', $route_parms, 'methods element' );
				self::assertTrue( is_array( $route_parms['methods'] ), 'no methods' );
				self::assertEquals( 1, count( $route_parms['methods'] ), 'methods element too many rows' );
				self::assertArrayHasKey( 'PUT', $route_parms['methods'], 'methods PUT element' );

				// check args properly defined.
				self::assertArrayHasKey( 'args', $route_parms, 'arge element' );
				self::assertTrue( is_array( $route_parms['args'] ), 'no args' );
				self::assertEquals( 3, count( $route_parms['args'] ), 'args element too many rows' );
				// id.
				self::assertArrayHasKey( 'id', $route_parms['args'], 'args id element' );
				self::assertTrue( is_array( $route_parms['args']['id'] ), 'no args id' );
				self::assertEquals( 2, count( $route_parms['args']['id'] ), 'args id element too many rows' );
				self::assertArrayHasKey( 'required', $route_parms['args']['id'], 'args id required element' );
				self::assertArrayHasKey( 'sanitize_callback', $route_parms['args']['id'], 'args id sanitize element' );
				self::assertEquals( 'absint', $route_parms['args']['id']['sanitize_callback'], 'args id sanitize value' );
				// code.
				self::assertArrayHasKey( 'code', $route_parms['args'], 'args code element' );
				self::assertTrue( is_array( $route_parms['args']['code'] ), 'no args code' );
				self::assertEquals( 2, count( $route_parms['args']['code'] ), 'args code element too many rows' );
				self::assertArrayHasKey( 'required', $route_parms['args']['code'], 'args code required element' );
				self::assertArrayHasKey( 'sanitize_callback', $route_parms['args']['code'], 'args code sanitize element' );
				self::assertEquals( 'absint', $route_parms['args']['code']['sanitize_callback'], 'args code sanitize value' );
				// parm.
				self::assertArrayHasKey( 'parm', $route_parms['args'], 'args parm element' );
				self::assertTrue( is_array( $route_parms['args']['parm'] ), 'no args parm' );
				self::assertEquals( 2, count( $route_parms['args']['parm'] ), 'args parm element too many rows' );
				self::assertArrayHasKey( 'required', $route_parms['args']['parm'], 'args parm required element' );
				self::assertArrayHasKey( 'sanitize_callback', $route_parms['args']['parm'], 'args parm sanitize element' );
				self::assertEquals( 'absint', $route_parms['args']['parm']['sanitize_callback'], 'args parm sanitize value' );

				// check callback properly defined.
				self::assertArrayHasKey( 'callback', $route_parms, 'callback element' );
				self::assertTrue( is_array( $route_parms['callback'] ), 'no callback' );
				self::assertEquals( 2, count( $route_parms['callback'] ), 'callback element too many rows' );
				self::assertArrayHasKey( 1, $route_parms['callback'], 'callback 1 element' );
				self::assertEquals( 'correct_document', $route_parms['callback'][1], 'callback element wrong parameter' );

				// check permission_callback properly defined.
				self::assertArrayHasKey( 'permission_callback', $route_parms, 'permission_callback element' );
				self::assertTrue( is_array( $route_parms['permission_callback'] ), 'no permission_callback' );
				self::assertEquals( 2, count( $route_parms['permission_callback'] ), 'permission_callback element too many rows' );
				self::assertArrayHasKey( 1, $route_parms['permission_callback'], 'permission_callback 1 element' );
				self::assertEquals( 'check_permission', $route_parms['permission_callback'][1], 'permission_callback element wrong parameter' );
			}
		}
	}

	/**
	 * Tests the help text.
	 */
	public function test_add_help_text() {
		global $wpdr;

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		// set hook_suffix in global scope (bending rule).
		global $hook_suffix, $typenow;
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$hook_suffix = 'document_page_wpdr_validate';
		$typenow     = 'post';
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		set_current_screen();

		// add help text for validate.
		$wpdr_v = new WP_Document_Revisions_Validate_Structure();
		$wpdr_v->add_help_tab();

		self::assertTrue( true, 'help text called' );
	}

	/**
	 * Tests the enqueue_scripts routine.
	 */
	public function test_enqueue_scripts() {
		// add help text for validate.
		WP_Document_Revisions_Validate_Structure::enqueue_scripts();

		self::assertTrue( true, 'enqueue_scripts called' );
	}

	/**
	 * Tests the add_menu routine.
	 */
	public function test_add_menu() {
		// add menu for validate.
		WP_Document_Revisions_Validate_Structure::add_menu();

		self::assertTrue( true, 'add_menu called' );
	}
}
