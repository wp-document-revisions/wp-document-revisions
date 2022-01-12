<?php
/**
 * Tests admin functionality.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Admin tests
 */
class Test_WP_Document_Revisions_Admin_Other extends Test_Common_WPDR {

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
		add_action( 'save_post_document', array( $wpdr->admin, 'save_document' ) );

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

		remove_action( 'save_post_document', array( $wpdr->admin, 'save_document' ) );
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
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$editor_public_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
		self::verify_structure( self::$editor_public_post_2, 2, 2 );
	}

	/**
	 * Tests the admin messages.
	 */
	public function test_admin_messages() {
		global $wpdr;

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		$messages = array();
		// add messages.
		$messages = $wpdr->admin->update_messages( $messages );

		self::assertTrue( is_array( $messages ), 'still array' );
		self::assertNotEmpty( $messages, 'has valuse' );
		self::assertArrayHasKey( 'document', $messages, 'loaded' );
		self::assertArrayHasKey( 10, $messages['document'], 'tenth' );
	}

	/**
	 * Tests the admin help text.
	 */
	public function test_admin_add_help_text() {
		global $wpdr;

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		// set hook_suffix in global scope (bending rule).
		global $hook_suffix, $typenow;
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$hook_suffix = 'post.php';
		$typenow     = 'document';
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		set_current_screen();
		$screen = get_current_screen();

		// add help text for other screen (none).
		$screen->id = 'other';
		$help_text  = $wpdr->admin->get_help_text( $screen );

		self::assertEmpty( $help_text, 'other not empty' );

		// add help text for document screen (Basic).
		$screen->id = 'document';
		$help_text  = $wpdr->admin->get_help_text( $screen );

		self::assertArrayHasKey( 'Basic Usage', $help_text, 'document basic' );
		self::assertArrayHasKey( 'Document Description', $help_text, 'document description' );
		self::assertEquals( 5, (int) count( $help_text ), 'document count' );

		// add help text for document screen (Basic).
		$screen->id = 'edit-document';
		$help_text  = $wpdr->admin->get_help_text( $screen );

		self::assertArrayHasKey( 'Documents', $help_text, 'edit-document not correct' );
		self::assertEquals( 1, (int) count( $help_text ), 'document-edit count' );

		// add help text for current screen (none).
		$wpdr->admin->add_help_tab();
	}

	/**
	 * Tests the admin meta_cb.
	 */
	public function test_admin_meta_cb() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		ob_start();
		$wpdr->admin->meta_cb();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test document metabox unauth.
	 */
	public function test_document_metabox_unauth() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		$curr_post = get_post( self::$editor_public_post );

		ob_start();
		$wpdr->admin->document_metabox( $curr_post );
		$output = ob_get_contents();
		ob_end_clean();

		// There will be various bits found.
		self::assertEquals( 2, (int) substr_count( $output, '<input' ), 'input count' );
		self::assertEquals( 1, (int) substr_count( $output, '?post_id=' . self::$editor_public_post . '&' ), 'post_id' );
		self::assertEquals( 1, (int) substr_count( $output, get_permalink( self::$editor_public_post ) ), 'permalink' );
	}

	/**
	 * Test document metabox auth.
	 */
	public function test_document_metabox_auth() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$curr_post = get_post( self::$editor_public_post );

		ob_start();
		$wpdr->admin->document_metabox( $curr_post );
		$output = ob_get_contents();
		ob_end_clean();

		// There will be various bits found.
		self::assertEquals( 2, (int) substr_count( $output, '<input' ), 'input count' );
		self::assertEquals( 1, (int) substr_count( $output, '?post_id=' . self::$editor_public_post . '&' ), 'post_id' );
		self::assertEquals( 1, (int) substr_count( $output, get_permalink( self::$editor_public_post ) ), 'permalink' );
	}

	/**
	 * Test document delete.
	 *
	 * This code is called in Teardown but codecov does not appear to include it.
	 */
	public function test_document_delete() {
		// make sure that we have the admin set up.
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// get attachment_id and file.
		$attach = $wpdr->get_document( self::$editor_public_post_2 );
		$file   = get_attached_file( $attach->ID );

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$editor_public_post_2, true );

		// delete done, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		// test deletion.
		self::assertNull( get_post( $attach->ID ), 'attachment not deleted' );
		self::assertFalse( file_exists( $file ), 'file not deleted' );
	}

	/**
	 * Tests the posts limit..
	 */
	public function test_admin_check_limits() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		ob_start();
		$wpdr->admin->check_document_revisions_limit();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertTrue( true, 'check_limits' );
	}

	/**
	 * Tests the posts enqueue.
	 */
	public function test_admin_enqueue() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		ob_start();
		$wpdr->admin->enqueue();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertTrue( true, 'admin_enqueue' );
	}

	/**
	 * Tests the posts prepare_editor.
	 */
	public function test_admin_prepare_editor() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$npost              = new stdClass();
		$npost->ID          = 0;
		$npost->post_author = '';
		$npost->post_type   = 'post';
		$npost->post_status = 'draft';
		$npost->post_parent = 0;
		global $post;
		// nothing in global scope.
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = new WP_Post( $npost );

		ob_start();
		$wpdr->admin->prepare_editor( $post );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEmpty( $output, 'not doc not empty' );

		// get a post in global scope (bending rule).
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		ob_start();
		$wpdr->admin->prepare_editor( $post );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'Document Description' ), 'Description not found' );
		self::assertTrue( true, 'prepare_editor' );
	}

	/**
	 * Tests the document_editor_setting.
	 */
	public function test_admin_document_editor_setting() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$settings = array();

		$npost              = new stdClass();
		$npost->ID          = 0;
		$npost->post_author = '';
		$npost->post_type   = 'post';
		$npost->post_status = 'draft';
		$npost->post_parent = 0;
		global $post;
		// nothing in global scope.
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = new WP_Post( $npost );

		$output = $wpdr->admin->document_editor_setting( $settings, 'not_content' );

		self::assertTrue( is_array( $output ), 'still array' );
		self::assertEmpty( $output, 'empty' );

		$output = $wpdr->admin->document_editor_setting( $settings, 'content' );

		self::assertTrue( is_array( $output ), 'still array' );
		self::assertEmpty( $output, 'empty' );

		// get a post in global scope (bending rule).
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		$output = $wpdr->admin->document_editor_setting( $settings, 'not_content' );

		self::assertTrue( is_array( $output ), 'still array' );
		self::assertEmpty( $output, 'empty' );

		$output = $wpdr->admin->document_editor_setting( $settings, 'content' );

		self::assertTrue( is_array( $output ), 'still array' );
		self::assertNotEmpty( $output, 'has values' );
		self::assertArrayHasKey( 'wpautop', $output, 'setting wpautop' );
		self::assertFalse( $output['wpautop'], 'wpautop not false' );
		self::assertArrayHasKey( 'textarea_rows', $output, 'setting wpautop' );
		self::assertEquals( 8, $output['textarea_rows'], 'textarea_rows not 8' );

		self::assertTrue( true, 'document_editor_setting' );
	}
	/**
	 * Tests the posts modify_content_class.
	 */
	public function test_admin_modify_content_class() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$settings = array();

		$settings = $wpdr->admin->modify_content_class( $settings );

		self::assertTrue( is_array( $settings ), 'not array' );
		self::assertEmpty( $settings, 'not empty' );

		$settings = array(
			'body_class'  => 'content post-type-document',
			'content_css' => 'some.css',
		);

		$settings = $wpdr->admin->modify_content_class( $settings );

		self::assertNotEquals( 'some.css', $settings['content_css'], 'content_css not changed' );
		self::assertTrue( true, 'modify_content_class' );
	}

	/**
	 * Tests the posts hide_postcustom_metabox.
	 */
	public function test_admin_hide_postcustom_metabox() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$hidden     = array();
		$screen     = new StdClass();
		$screen->id = 'post';

		$hidden = $wpdr->admin->hide_postcustom_metabox( $hidden, $screen );

		self::assertTrue( is_array( $hidden ), 'not doc not array' );
		self::assertEmpty( $hidden, 'not doc not empty' );

		$screen->id = 'document';

		$hidden = $wpdr->admin->hide_postcustom_metabox( $hidden, $screen );

		self::assertTrue( is_array( $hidden ), 'doc not array' );
		self::assertNotEmpty( $hidden, 'doc empty' );
		self::assertArrayHasKey( 0, $hidden, 'doc not 0 row' );
		self::assertEquals( $hidden[0], 'postcustom', 'doc wrong value' );

		self::assertTrue( true, 'hide_postcustom_metabox' );
	}

	/**
	 * Tests the admin_body_class_filter.
	 */
	public function test_admin_body_class_filter() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$npost              = new stdClass();
		$npost->ID          = 0;
		$npost->post_author = '';
		$npost->post_type   = 'post';
		$npost->post_status = 'draft';
		$npost->post_parent = 0;
		global $post;
		// nothing in global scope.
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = new WP_Post( $npost );

		$body_class = '';

		$body_class = $wpdr->admin->admin_body_class_filter( $body_class );

		self::assertEmpty( $body_class, 'not doc not empty' );

		// get a post in global scope (bending rule).
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		$body_class = $wpdr->admin->admin_body_class_filter( $body_class );

		self::assertNotEmpty( $body_class, 'doc not empty' );
		self::assertEquals( $body_class, ' document', 'doc not correct' );
		self::assertTrue( true, 'body_class_filter' );
	}
}
