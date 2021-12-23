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
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
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
		$ws_terms           = self::create_term_fixtures( $factory );
		self::$ws_term_id_0 = (int) $ws_terms[0]->term_id;
		self::$ws_slug_0    = $ws_terms[0]->slug;
		self::$ws_term_id_1 = (int) $ws_terms[1]->term_id;
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
		$terms = wp_set_post_terms( self::$author_public_post, array( self::$ws_term_id_0 ), 'workflow_state' );
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
	 * Delete the posts. (Taken from WP Test Suite).
	 */
	public static function wpTearDownAfterClass() {
		// remove terms.
		wp_remove_object_terms( self::$author_public_post, self::$ws_term_id_0, 'workflow_state' );
		wp_remove_object_terms( self::$author_private_post, self::$ws_term_id_0, 'workflow_state' );
		wp_remove_object_terms( self::$editor_private_post, self::$ws_term_id_1, 'workflow_state' );
		wp_remove_object_terms( self::$editor_public_post, self::$ws_term_id_1, 'workflow_state' );

		// make sure that we have the admin set up.
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$author_public_post, true );
		wp_delete_post( self::$author_private_post, true );
		wp_delete_post( self::$editor_private_post, true );
		wp_delete_post( self::$editor_public_post, true );

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
		self::assertTrue( user_can( $usr, 'read_document_revisions' ), 'Cannot read document revisions' );

		$output = do_shortcode( '[document_revisions id="' . self::$editor_public_post . '"]' );

		self::assertEquals( 3, substr_count( $output, '<li' ), 'editor revision shortcode' );
	}

	/**
	 * Verify unauth'd user cannot view revision block.
	 */
	public function test_revisions_block_noauth() {

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

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		$usr = wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// can user read_revisions?
		self::assertTrue( user_can( $usr, 'read_document_revisions' ), 'Cannot read document revisions' );

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$atts   = array(
			'id' => self::$editor_public_post,
		);
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( $atts );

		self::assertEquals( 3, substr_count( $output, '<li' ), 'editor revision block' );
	}

	/**
	 * Tests the documents shortcode.
	 *
	 * An unauthorised user cannot see post revisions.
	 */
	public function test_document_shortcode() {

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
	 * Tests the documents shortcode with options.
	 *
	 * An unauthorised user cannot edit documents.
	 */
	public function test_document_shortcode_opts_unauth() {

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[documents show_thumb show_descr=true show_edit new_tab ]' );

		// read the two published ones.
		self::assertEquals( 2, substr_count( $output, '<li' ), 'document shortcode count' );
		self::assertEquals( 0, substr_count( $output, 'action=edit' ), 'document new_tab count' );
		self::assertEquals( 2, substr_count( $output, 'target="_blank"' ), 'document new_tab count' );
	}

	/**
	 * Tests the documents shortcode with options.
	 *
	 * An unauthorised user cannot edit documents.
	 */
	public function test_document_shortcode_opts_author() {

		// set author user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[documents show_thumb show_descr=true show_edit new_tab ]' );

		// read the two published and the private ones.
		self::assertEquals( 3, substr_count( $output, '<li' ), 'document shortcode count' );
		self::assertEquals( 2, substr_count( $output, 'action=edit' ), 'document new_tab count' );
		self::assertEquals( 3, substr_count( $output, 'target="_blank"' ), 'document new_tab count' );

		$output = do_shortcode( '[documents show_thumb show_descr=true new_tab ]' );

		// read the two published and the private ones, but no edit option.
		self::assertEquals( 3, substr_count( $output, '<li' ), 'document shortcode count' );
		self::assertEquals( 0, substr_count( $output, 'action=edit' ), 'document new_tab count' );
		self::assertEquals( 3, substr_count( $output, 'target="_blank"' ), 'document new_tab count' );
	}

	/**
	 * Tests the documents shortcode with options.
	 *
	 * An editor user can edit all documents.
	 */
	public function test_document_shortcode_opts_editor() {

		// set editor user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		global $wpdr_fe;
		if ( ! $wpdr_fe ) {
			$wpdr_fe = new WP_Document_Revisions_Front_End();
		}

		$output = do_shortcode( '[documents show_thumb show_descr=true show_edit new_tab ]' );

		// read the two published and the two private ones.
		self::assertEquals( 4, substr_count( $output, '<li' ), 'document shortcode count' );
		self::assertEquals( 4, substr_count( $output, 'action=edit' ), 'document new_tab count' );
		self::assertEquals( 4, substr_count( $output, 'target="_blank"' ), 'document new_tab count' );

		$output = do_shortcode( '[documents show_thumb show_descr=true new_tab ]' );

		// read the two published and the two private ones, but no edit option.
		self::assertEquals( 4, substr_count( $output, '<li' ), 'document shortcode count' );
		self::assertEquals( 0, substr_count( $output, 'action=edit' ), 'document new_tab count' );
		self::assertEquals( 4, substr_count( $output, 'target="_blank"' ), 'document new_tab count' );
	}

	/**
	 * Tests the documents shortcode with a workflow state filter - authoe.
	 */
	public function test_document_shortcode_wfs_filter_auth() {

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		$output_0 = do_shortcode( '[documents workflow_state="' . self::$ws_slug_0 . '"]' );

		self::assertEquals( 2, substr_count( $output_0, '<li' ), 'document shortcode filter count_0' );
		self::assertEquals( 1, substr_count( $output_0, 'Author Public' ), 'document shortcode filter title_01' );
		self::assertEquals( 1, substr_count( $output_0, 'Author Private' ), 'document shortcode filter title_02' );

		$output_1 = do_shortcode( '[documents workflow_state="' . self::$ws_slug_1 . '"]' );

		self::assertEquals( 1, substr_count( $output_1, '<li' ), 'document shortcode filter count_1' );
		self::assertEquals( 1, substr_count( $output_1, 'Editor Public' ), 'document shortcode filter title_1' );
	}

	/**
	 * Test document shortcode with a post_meta filter.
	 */
	public function test_document_shortcode_post_meta_filter() {

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
		self::set_up_document_read();

		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		self::assertEquals( 1, substr_count( $output, 'not authorized' ), 'document block docread' );
		self::tear_down_document_read();
	}

	/**
	 * Tests the documents block with a workflow state filter. with and without read_document caps.
	 */
	public function test_document_block_wfs_filter() {

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

		self::assertEquals( 2, substr_count( $output, '<li' ), 'document block nofilter count' );
		self::assertEquals( 1, substr_count( $output, 'Author Public' ), 'document block nofilter post_1' );
		self::assertEquals( 1, substr_count( $output, 'Editor Public' ), 'document block nofilter post_2' );

		$term = get_term( self::$ws_term_id_0, 'workflow_state' );
		self::assertEquals( self::$ws_slug_0, $term->slug, 'slug equal' );

		$terms = wp_get_post_terms( self::$author_public_post, 'workflow_state' );
		self::assertEquals( self::$ws_term_id_0, $terms[0]->term_id, 'term_id equal' );

		$atts   = array(
			'taxonomy_0' => 'workflow_state',
			'term_0'     => self::$ws_term_id_0,
		);
		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		self::assertEquals( 1, substr_count( $output, '<li' ), 'document block filter auth' );
		self::assertEquals( 1, substr_count( $output, 'Author Public' ), 'document block nofilter auth_1' );

		// using document_read capability means no access for an unauthorized use..
		self::set_up_document_read();

		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		self::assertEquals( 1, substr_count( $output, 'not authorized' ), 'document block docread' );
		self::tear_down_document_read();
	}

	/**
	 * Tests the public get_documents function.
	 */
	public function test_get_documents() {

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

		global $wpdr;

		$docs = $wpdr->get_documents( null, true );
		$doc  = array_pop( $docs );

		self::assertEquals( $doc->post_type, 'attachment', 'get_documents not returning attachments' );
	}

	/**
	 * Tests that get_documents properly filters when asked.
	 */
	public function test_get_documents_filter() {

		// set unauthorised user.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// Proper query.
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$docs = get_documents(
			array(
				'meta_key'   => 'test_meta_key',
				'meta_value' => 'test_value',
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		self::assertCount( 1, $docs, 'get_documents filter count_1' );
		self::assertEquals( self::$editor_public_post, $docs[0]->ID, 'get_documents filter title_11' );

		// Incorrect query. Will retrieve all public rows.
		$docs = get_documents(
			array(
				'test_meta_key' => 'test_value',
			)
		);

		self::assertCount( 2, $docs, 'get_documents filter count_2' );
		if ( self::$author_public_post === $docs[0]->ID ) {
			self::assertEquals( self::$author_public_post, $docs[0]->ID, 'get_documents filter title_21' );
			self::assertEquals( self::$editor_public_post, $docs[1]->ID, 'get_documents filter title_22' );
		} else {
			self::assertEquals( self::$author_public_post, $docs[1]->ID, 'get_documents filter title_21' );
			self::assertEquals( self::$editor_public_post, $docs[0]->ID, 'get_documents filter title_22' );
		}
	}

	/**
	 * Tests the get_revisions function.
	 */
	public function test_get_document_revisions() {

		// editor should be able to access.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		self::assertCount( 2, get_document_revisions( self::$editor_private_post ), 'private count' );
		self::assertCount( 3, get_document_revisions( self::$editor_public_post ), 'public count' );
	}
}
