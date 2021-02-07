<?php
/**
 * Tests front-end functionality.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Front end tests
 */
class Test_WP_Document_Revisions_Front_End extends Test_Common_WPDR {

	/**
	 * List of users being tested.
	 *
	 * @var WP_User[] $users
	 */
	protected static $users = array(
		'editor' => null,
		'author' => null,
	);

	/**
	 * Workflow_state term id 0
	 *
	 * @var integer $ws_term_id_0
	 */
	private static $ws_term_id_0;

	/**
	 * Workflow_state slug 0
	 *
	 * @var string $ws_slug_0
	 */
	private static $ws_slug_0;

	/**
	 * Workflow_state term id 1
	 *
	 * @var integer $ws_term_id 1
	 */
	private static $ws_term_id_1;

	/**
	 * Workflow_state slug 1
	 *
	 * @var string $ws_slug_1
	 */
	private static $ws_slug_1;

	/**
	 * Author Public Post ID
	 *
	 * @var integer $author_public_post
	 */
	private static $author_public_post;

	/**
	 * Author Private Post ID
	 *
	 * @var integer $author_private_post
	 */
	private static $author_private_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer $editor_private_post
	 */
	private static $editor_private_post;

	/**
	 * Editor Public Post ID (contains revision)
	 *
	 * @var integer $editor_public_post
	 */
	private static $editor_public_post;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
	// phpcs:enable
		console_log( 'Test_Front_End' );

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author' => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
		);

		// init permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$wp_rewrite->flush_rules();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->initialize_workflow_states();
		$ws_terms           = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::$ws_term_id_0 = $ws_terms[0]->term_id;
		self::$ws_slug_0    = $ws_terms[0]->slug;
		self::$ws_term_id_1 = $ws_terms[1]->term_id;
		self::$ws_slug_1    = $ws_terms[1]->slug;

		// create posts for scenarios.
		// Author Public.
		self::$author_public_post = $factory->post->create(
			array(
				'post_title'   => 'Author Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['author']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_public_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$author_public_post, self::$ws_term_id_0, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$author_public_post, self::$test_file );

		// Author Private.
		self::$author_private_post = $factory->post->create(
			array(
				'post_title'   => 'Author Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['author']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_private_post ), 'Failed inserting document' );

		// add terms and attachment.
		$terms = wp_set_post_terms( self::$author_private_post, array( self::$ws_term_id_0 ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$author_private_post, self::$test_file );

		// give postmeta to it.
		update_post_meta( self::$author_private_post, 'test_meta_key', 'test_value' );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, array( self::$ws_term_id_1 ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );

		// Editor Public. N.B. This has a Revision, i.e. two revision posts.
		self::$editor_public_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_public_post ), 'Failed inserting document' );

		// give postmeta to it.
		update_post_meta( self::$editor_public_post, 'test_meta_key', 'test_value' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_public_post, array( self::$ws_term_id_1 ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file );

		// add attachment (again).
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file2 );

		// clear cache.
		wp_cache_flush();
	}

	/**
	 * Get the roles data refreshed. (Taken from WP Test Suite).
	 */
	public function setUp() {
		parent::setUp();
		// Keep track of users we create.
		self::flush_roles();
	}

	/**
	 * Get the roles data refreshed.
	 */
	private function flush_roles() {
		// We want to make sure we're testing against the DB, not just in-memory data.
		// This will flush everything and reload it from the DB.
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $GLOBALS['wp_user_roles'] );
		global $wp_roles;
		$wp_roles = new WP_Roles();
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		// re-init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->add_caps();
	}

	/**
	 * Delete the posts. (Taken from WP Test Suite).
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$author_public_post, true );
		wp_delete_post( self::$author_private_post, true );
		wp_delete_post( self::$editor_private_post, true );
		wp_delete_post( self::$editor_public_post, true );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$author_public_post, 1, 1 );
		self::verify_structure( self::$author_private_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
		self::verify_structure( self::$editor_public_post, 2, 2 );
	}

	/**
	 * Verify shortcodes exist.
	 */
	public function test_shortcodes_defined() {

		console_log( ' shortcodes_defined' );

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		self::assertTrue( shortcode_exists( 'document_revisions' ) );
		self::assertTrue( shortcode_exists( 'documents' ) );
	}

	/**
	 * Verify joe public can't access a list of revisions.
	 */
	public function test_revisions_shortcode_unauthed() {

		console_log( ' revisions_shortcode_unauthed' );

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[document_revisions id="' . self::$author_public_post . '"]' );

		self::assertEquals( 1, substr_count( $output, 'You are not authorized' ), 'shortcode_unauthed' );
	}


	/**
	 * Verify auth'd user can view revision shortcode and can truncate proper count.
	 */
	public function test_revisions_shortcode() {

		console_log( ' revisions_shortcode' );

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		$usr = wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// can user read_revisions?
		self::assertTrue( $usr->has_cap( 'read_document_revisions' ), 'Cannot read document revisions' );

		$output = do_shortcode( '[document_revisions id="' . self::$editor_public_post . '"]' );

		self::assertEquals( 3, substr_count( $output, '<li' ), 'editor revision shortcode' );
	}

	/**
	 * Verify unauth'd user cannot view revision block.
	 */
	public function test_revisions_block_noauth() {

		console_log( ' revisions_block_noauth' );

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$atts   = array(
			'id' => self::$author_public_post,
		);
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( $atts );

		self::assertEquals( 0, (int) substr_count( $output, '<li' ), 'unauthed revision block' );
		self::assertEquals( 1, substr_count( $output, 'You are not authorized' ), 'admin revision block' );
	}

	/**
	 * Verify auth'd user can view revision block and can truncate proper count.
	 */
	public function test_revisions_block() {

		console_log( ' revisions_block' );

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// can user read_revisions?
		self::assertTrue( current_user_can( 'read_document_revisions' ), 'Cannot read document revisions' );

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$atts   = array(
			'id' => self::$editor_public_post,
		);
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( $atts );
		console_log( $output );

		self::assertEquals( 2, substr_count( $output, '<li' ), 'editor revision block' );
	}

	/**
	 * Verify auth'd user can view revision block and can truncate proper count.
	 */
	public function test_revisions_block_nondoc() {

		console_log( ' revisions_block' );

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// can user read_revisions?
		self::assertTrue( current_user_can( 'read_document_revisions' ), 'Cannot read document revisions' );

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$atts   = array(
			'id' => 2,
		);
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( $atts );
		console_log( $output );

		self::assertEquals( 1, substr_count( $output, 'This is not a valid document' ), 'editor revision block nondoc' );
	}

	/**
	 * Tests the document_revisions shortcode with a number=1 limit.
	 */
	public function test_revisions_shortcode_limit() {

		console_log( ' revisions_shortcode_limit' );

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// can user read_revisions?
		self::assertTrue( current_user_can( 'read_document_revisions' ), 'Cannot read document revisions' );

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[document_revisions number="1" id="' . self::$editor_public_post . '"]' );

		self::assertEquals( 1, substr_count( $output, '<li' ), 'revision shortcode limit' );
	}

	/**
	 * Tests the documents shortcode.
	 *
	 * An unauthorised user cannot see post revisions.
	 */
	public function test_document_shortcode() {

		console_log( ' document_shortcode' );

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[documents]' );

		// read the two published ones.
		self::assertEquals( 2, substr_count( $output, '<li' ), 'document shortcode count' );
	}

	/**
	 * Tests the documents shortcode with a workflow state filter.
	 */
	public function test_document_shortcode_wfs_filter() {

		console_log( ' document_shortcode_wfs_filter' );

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();
		console_log( 'APu - QT:' . self::$ws_slug_0 );
		$terms = wp_get_post_terms( self::$author_public_post, 'workflow_state' );
		foreach ( $terms as $term ) {
		 	 console_log( 'AT:' . $term->term_ID . '/' . $term->slug );
		}

		console_log( 'APrEPu - QT:' . self::$ws_slug_1 );
		$terms = wp_get_post_terms( self::$author_private_post, 'workflow_state' );
		foreach ( $terms as $term ) {
		 	 console_log( 'AT:' . $term->term_ID . '/' . $term->slug );
		}
		$terms = wp_get_post_terms( self::$editor_public_post, 'workflow_state' );
		foreach ( $terms as $term ) {
		 	 console_log( 'AT:' . $term->term_ID . '/' . $term->slug );
		}

		$output_0 = do_shortcode( '[documents workflow_state="' . self::$ws_slug_0 . '"]' );

		self::assertEquals( 1, substr_count( $output_0, '<li' ), 'document shortcode filter count_0' );
		self::assertEquals( 1, substr_count( $output_0, 'Author Private' ), 'document shortcode filter title_0' );

		$output_1 = do_shortcode( '[documents workflow_state="' . self::$ws_slug_1 . '"]' );

		self::assertEquals( 2, substr_count( $output_1, '<li' ), 'document shortcode filter count_1' );
		self::assertEquals( 1, substr_count( $output_1, 'Editor Private' ), 'document shortcode filter title_11' );
		self::assertEquals( 1, substr_count( $output_1, 'Editor Public' ), 'document shortcode filter title_12' );
	}

	/**
	 * Test document shortcode with a post_meta filter.
	 */
	public function test_document_shortcode_post_meta_filter() {

		console_log( ' document_shortcode_post_meta_filter' );

		// author should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[documents meta_key="test_meta_key" meta_value="test_value"]' );

		self::assertEquals( 2, substr_count( $output, '<li' ), 'document shortcode filter count' );
		self::assertEquals( 1, substr_count( $output, 'Author Private' ), 'document shortcode filter post_1' );
		self::assertEquals( 1, substr_count( $output, 'Editor Public' ), 'document shortcode filter post_2' );
	}

	/**
	 * Tests the documents block with and without read_document caps.
	 */
	public function test_document_block() {

		console_log( ' document_block' );

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$atts   = array();
		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		self::assertEquals( 2, substr_count( $output, '<li' ), 'document block read' );

		// using document_read capability means no access for an unauthorized use..
		add_filter( 'document_read_uses_read', '__return_false' );

		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		self::assertEquals( 1, substr_count( $output, 'not authorized' ), 'document block docread' );
		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Tests the documents block with a workflow state filter. with and without read_document caps.
	 */
	public function test_document_block_wfs_filter() {

		console_log( ' document_block_wfs_filter' );

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = $wpdr_fe->wpdr_documents_shortcode_display( array() );
		console_log( $output );

		$atts   = array(
			'taxonomy_0' => 'workflow_state',
			'term_0'     => self::$ws_term_id_0,
		);
		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );
		console_log( $output );

		self::assertEquals( 1, substr_count( $output, '<li' ), 'document block filter auth' );

		// using document_read capability means no access for an unauthorized use..
		add_filter( 'document_read_uses_read', '__return_false' );

		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );
		console_log( $output );

		self::assertEquals( 1, substr_count( $output, 'not authorized' ), 'document block docread' );
		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Tests the public get_documents function.
	 */
	public function test_get_documents() {

		console_log( ' get_documents' );

		global $wpdr;

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		self::assertCount( 2, $wpdr->get_documents(), 'get_document() count' );
	}

	/**
	 * Tests that get_documents returns attachments when asked.
	 */
	public function test_get_documents_returns_attachments() {

		console_log( ' get_documents_returns_attachments' );

		global $wpdr;

		$docs = $wpdr->get_documents( null, true );
		$doc  = array_pop( $docs );

		self::assertEquals( $doc->post_type, 'attachment', 'get_documents not returning attachments' );
	}

	/**
	 * Tests that get_documents properly filters when asked.
	 */
	public function test_get_documents_filter() {

		console_log( ' get_documents_filter' );

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$docs = get_documents(
			array(
				'meta_key'   => 'test_meta_key',
				'meta_value' => 'test_value',
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		console_log( 'Retrieved: ' . count( $docs ) );
		foreach ( $docs as $doc ) {
			console_log( 'Post:' . $doc->ID . ' ' . $doc->post_title );
		}
		self::assertCount( 1, $docs, 'get_documents filter count' );

		$docs = get_documents(
			array(
				'test_meta_key' => 'test_value',
			)
		);
		console_log( 'Retrieved: ' . count( $docs ) );
		foreach ( $docs as $doc ) {
			console_log( 'Post:' . $doc->ID . ' ' . $doc->post_title );
		}
		self::assertCount( 1, $docs, 'get_documents filter count' );
	}

	/**
	 * Tests the get_revisions function.
	 */
	public function test_get_document_revisions() {

		console_log( ' get_document_revisions' );

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		self::assertCount( 2, get_document_revisions( self::$editor_private_post ), 'private count' );
		self::assertCount( 3, get_document_revisions( self::$editor_public_post ), 'public count' );
	}

}
