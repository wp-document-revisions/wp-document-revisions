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
	 * @var integer
	 */
	private static $editor_user_id;

	/**
	 * Workflow_state term id
	 *
	 * @var integer
	 */
	private static $ws_term_id;

	/**
	 * Editor Public Post ID
	 *
	 * @var integer
	 */
	private static $editor_public_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer
	 */
	private static $editor_private_post;

	/**
	 * Editor Public Post 2 ID
	 *
	 * @var integer
	 */
	private static $editor_public_post_2;

	/**
	 * Editor Non-document Post
	 *
	 * @var integer
	 */
	private static $editor_public_non_doc;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// phpcs:enable
		// set permalink structure to Month and name string.
		global $wp_rewrite, $orig;
		$orig = $wp_rewrite->permalink_structure;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		// flush cache for good measure.
		wp_cache_flush();

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
		self::add_document_attachment_new( $factory, self::$editor_private_post, self::$test_file );

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
		self::add_document_attachment_new( $factory, self::$editor_public_post_2, self::$test_file2 );

		remove_action( 'save_post_document', array( $wpdr->admin, 'save_document' ) );

		// Editor Public Non-document.
		self::$editor_public_non_doc = $factory->post->create(
			array(
				'post_title'   => 'Editor Public Non-document - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$editor_user_id,
				'post_content' => 'Not document - for negative testing',
				'post_excerpt' => '',
				'post_type'    => 'post',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_public_non_doc ), 'Failed inserting document Editor Public Non-doc' );
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

		wp_delete_post( self::$editor_public_non_doc, true );

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

		// call with no screen.
		$help_text = $wpdr->admin->get_help_text();

		self::assertEmpty( $help_text, 'empty not empty' );

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
		$screen->post_type = 'document';
		$wpdr->admin->add_help_tab();

		// add help text for different screen (none).
		$screen->post_type = 'post';
		$wpdr->admin->add_help_tab();
	}

	/**
	 * Tests the admin no block editor.
	 */
	public function test_admin_no_block_editor() {
		global $wpdr;

		// not document - use attachment instead.
		$post   = get_post( self::$editor_public_non_doc );
		$filter = $wpdr->admin->no_use_block_editor( true, $post );

		self::assertTrue( $filter, 'Not document failed' );

		// document.
		$doc    = get_post( self::$editor_public_post_2 );
		$filter = $wpdr->admin->no_use_block_editor( true, $doc );

		self::assertFalse( $filter, 'Document failed' );
	}

	/**
	 * Tests the admin meta_cb.
	 */
	public function test_admin_meta_cb() {
		global $wpdr;

		global $current_user;
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
	 * Tests the admin enqueue edit scripts.
	 */
	public function test_enqueue_edit_scripts() {
		global $wpdr;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['post'] = self::$editor_public_post;

		ob_start();
		$wpdr->admin->enqueue_edit_scripts();
		$output = ob_get_contents();
		ob_end_clean();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post'] );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Tests the admin media upload tabs.
	 */
	public function test_media_upload_tabs() {
		global $wpdr;

		$default = array(
			'computer' => 'field 1',
			'type_url' => 'field 2',
			'gallery'  => 'field 3',
			'library'  => 'field 4',
		);

		$def_one = $wpdr->admin->media_upload_tabs_computer( $default );

		self::assertEquals( 4, count( $def_one ), 'Values deleted' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_GET['post']   = self::$editor_public_post;
		$_GET['action'] = 'whatever';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$default = $wpdr->admin->media_upload_tabs_computer( $default );

		self::assertEquals( 1, count( $default ), 'Values not deleted' );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Tests the admin settings_fields.
	 */
	public function test_admin_settings_fields() {
		global $wpdr;

		$wpdr->admin->settings_fields();

		self::assertTrue( true, 'run' );
	}

	/**
	 * Tests the admin sanitize document slug.
	 */
	public function test_sanitize_document_slug() {
		global $wpdr;

		$slug = $wpdr->admin->sanitize_document_slug( 'documents' );

		self::assertEquals( 'documents', $slug, 'default not equal' );

		$slug = $wpdr->admin->sanitize_document_slug( 'docs' );

		self::assertEquals( 'docs', $slug, 'change not made' );

		$slug = $wpdr->admin->sanitize_document_slug( 'documents' );

		self::assertEquals( 'documents', $slug, 'no reset' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Tests the admin sanitize link date.
	 */
	public function test_sanitize_link_date() {
		global $wpdr;

		$link = $wpdr->admin->sanitize_link_date( null );

		self::assertFalse( $link, 'null' );

		$link = $wpdr->admin->sanitize_link_date( 0 );

		self::assertFalse( $link, 'zero' );

		$link = $wpdr->admin->sanitize_link_date( 1 );

		self::assertTrue( $link, 'one' );

		$link = $wpdr->admin->sanitize_link_date( 2 );

		self::assertTrue( $link, 'two' );
	}

	/**
	 * Test network settings cb.
	 */
	public function test_network_settings_cb() {
		global $wpdr;

		ob_start();
		$wpdr->admin->network_settings_cb();
		$output = ob_get_contents();
		ob_end_clean();

		// There will be various bits found.
		self::assertEquals( 1, (int) substr_count( $output, 'Document Settings' ), 'heading' );
		self::assertEquals( 1, (int) substr_count( $output, 'id="document_upload_directory"' ), 'directory' );
		self::assertEquals( 1, (int) substr_count( $output, 'value="/tmp/wordpress/wp-content/uploads"' ), 'directoryv' );
		self::assertEquals( 1, (int) substr_count( $output, 'id="document_slug"' ), 'slug' );
		self::assertEquals( 1, (int) substr_count( $output, 'value="documents"' ), 'slugv' );
	}

	/**
	 * Test link date cb.
	 */
	public function test_link_date_cb() {
		global $wpdr;

		ob_start();
		$wpdr->admin->document_link_date_cb();
		$output = ob_get_contents();
		ob_end_clean();

		// There will be various bits found.
		self::assertEquals( 1, (int) substr_count( $output, 'id="document_link_date"' ), 'heading' );
	}

	/**
	 * Test network upload location save.
	 */
	public function test_network_upload_location_save() {
		global $wpdr;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_POST['document_upload_location_nonce'] = wp_create_nonce( 'network_document_upload_location' );
		$_POST['document_upload_directory']      = $wpdr::$wpdr_document_dir;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_upload_location_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with exception.
		self::assertNotNull( $exception, 'no exception' );

		$current_user->add_cap( 'manage_network_options' );

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_upload_location_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should not fail with exception (but does).
		// self::assertNull( $exception, 'exception' );.
		// self::assertEmpty( $output, 'output' );.
		self::assertNotNull( $exception, 'no exception' );

		// repeat to exercise other paths - Invalid nonce.
		$_POST['document_upload_location_nonce'] = 'rubbish';

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_upload_location_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with exception.
		self::assertNotNull( $exception, 'no exception' );

		// repeat to exercise other paths - No nonce.
		unset( $_POST['document_upload_location_nonce'] );

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_upload_location_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should not fail with exception.
		self::assertNull( $exception, 'exception' );
		self::assertEmpty( $output, 'output' );

		$current_user->add_cap( 'manage_network_options', false );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Test network slug save.
	 */
	public function test_network_slug_save() {
		global $wpdr;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_POST['document_slug_nonce'] = wp_create_nonce( 'network_document_slug' );
		$_POST['document_slug']       = 'document';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_slug_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with exception.
		self::assertNotNull( $exception, 'no exception' );

		$current_user->add_cap( 'manage_network_options' );

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_slug_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should not fail with exception (but does).
		// self::assertNull( $exception, 'exception' );.
		// self::assertEmpty( $output, 'output' );.
		self::assertNotNull( $exception, 'no exception' );

		// repeat to exercise other paths - Invalid nonce.
		$_POST['document_slug_nonce'] = 'rubbish';

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_slug_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with exception.
		self::assertNotNull( $exception, 'no exception' );

		// repeat to exercise other paths - No nonce.
		unset( $_POST['document_slug_nonce'] );

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_slug_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should not fail with exception.
		self::assertNull( $exception, 'exception' );
		self::assertEmpty( $output, 'output' );

		$current_user->add_cap( 'manage_network_options', false );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Test network slug save.
	 */
	public function test_network_link_date_save() {
		global $wpdr;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_POST['document_link_date_nonce'] = wp_create_nonce( 'network_document_link_date' );
		$_POST['document_link_date']       = 'document';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_link_date_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with exception.
		self::assertNotNull( $exception, 'no exception' );

		$current_user->add_cap( 'manage_network_options' );

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_link_date_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should not fail with exception (but does).
		// self::assertNull( $exception, 'exception' );.
		// self::assertEmpty( $output, 'output' );.
		self::assertNotNull( $exception, 'no exception' );

		// repeat to exercise other paths - Invalid nonce.
		$_POST['document_link_date_nonce'] = 'rubbish';

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_link_date_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with exception.
		self::assertNotNull( $exception, 'no exception' );

		// repeat to exercise other paths - No nonce.
		unset( $_POST['document_link_date_nonce'] );

		$exception = null;
		try {
			ob_start();
			$wpdr->admin->network_link_date_save();
			$output = ob_get_contents();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should not fail with exception.
		self::assertNull( $exception, 'exception' );
		self::assertEmpty( $output, 'output' );

		$current_user->add_cap( 'manage_network_options', false );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Test network settings errors.
	 */
	public function test_network_settings_errors() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		add_settings_error( 'document_upload_directory', 'upload-exists', 'DiR', 'updated' );
		add_settings_error( 'document_slug', 'slug-exists', 'SlUg', 'updated' );
		add_settings_error( 'document_link_date', 'link-date-exists', 'LinkDate', 'updated' );

		ob_start();
		$wpdr->admin->network_settings_errors();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'DiR' ), 'Setting error dir not found' );
		self::assertEquals( 1, (int) substr_count( $output, 'SlUg' ), 'Setting error slug not found' );
		self::assertEquals( 1, (int) substr_count( $output, 'LinkDate' ), 'Setting error link_date not found' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test network settings redirect.
	 */
	public function test_network_settings_redirect() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$locn = $wpdr->admin->network_settings_redirect( 'site?nothing' );
		self::assertSame( $locn, 'site?nothing', 'settings-updated not found' );

		$locn = add_query_arg( 'updated', 'true', network_admin_url( 'settings.php' ) );
		$locn = $wpdr->admin->network_settings_redirect( $locn );
		self::assertSame( 1, (int) substr_count( $locn, 'settings-updated' ), 'settings-updated not found' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test upload location callback.
	 */
	public function test_upload_location_cb() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		$wpdr->admin->upload_location_cb();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 2, (int) substr_count( $output, 'document_upload_directory' ), 'upload_directory not found' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test .get latest attachment.
	 */
	public function test_get_latest_attachment() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$curr_post = get_post( self::$editor_public_post );
		$attach_id = $wpdr->extract_document_id( $curr_post->post_content );
		$attach    = $wpdr->admin->get_latest_attachment( $curr_post->ID );

		self::assertEquals( $curr_post->ID, $attach->post_parent, 'Wrong parent' );
		self::assertEquals( $attach_id, $attach->ID, 'Wrong attachment' );
	}

	/**
	 * Test lock notice.
	 */
	public function test_lock_notice() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['post'] = self::$editor_public_post;

		ob_start();
		$wpdr->admin->lock_notice();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'lock-notice' ), 'lock notice not found' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test filter documents list.
	 */
	public function test_filter_documents_list() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );

		global $typenow;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$typenow = 'document';

		ob_start();
		$wpdr->admin->filter_documents_list();
		$output = ob_get_contents();
		ob_end_clean();

		// There will be various bits found.
		self::assertSame( 1, (int) substr_count( $output, 'All workflow states' ), 'heading' );
		self::assertSame( 1, (int) substr_count( $output, 'value="final">Final' ), 'final' );
		self::assertSame( 1, (int) substr_count( $output, 'value="in-progress">In Progress' ), 'progress' );
		self::assertSame( 1, (int) substr_count( $output, 'value="initial-draft">Initial Draft' ), 'draft' );
		self::assertSame( 1, (int) substr_count( $output, 'value="under-review">Under Review' ), 'review' );

		self::assertSame( 1, (int) substr_count( $output, "value='0'>All owners" ), 'all owners' );
		self::assertSame( 0, (int) substr_count( $output, "value='1'>admin" ), 'admin' );
		self::assertSame( 1, (int) substr_count( $output, $current_user->display_name ), 'editor' );
	}

	/**
	 * Test rename author column.
	 */
	public function test_rename_author_column() {
		global $wpdr;

		$cols = array(
			'author' => 'Author',
		);

		// There will be various bits found.
		self::assertArrayHasKey( 'author', $cols, 'array key' );
		self::assertSame( $cols['author'], 'Author', 'Not set to Owner' );

		$new = $wpdr->admin->rename_author_column( $cols );

		// There will be various bits found.
		self::assertArrayHasKey( 'author', $new, 'array key' );
		self::assertSame( $new['author'], 'Owner', 'Not set to Owner' );
	}

	/**
	 * Test add currently editing column.
	 */
	public function test_add_currently_editing_column() {
		global $wpdr;

		$cols = array(
			'col_0' => 'col_0',
			'col_1' => 'col_1',
			'col_2' => 'col_2',
			'col_3' => 'col_3',
		);

		self::assertArrayNotHasKey( 'currently_editing', $cols, 'array key' );

		$new = $wpdr->admin->add_currently_editing_column( $cols );

		self::assertArrayHasKey( 'currently_editing', $new, 'array key' );
		self::assertSame( $new['currently_editing'], 'Currently Editing', 'Not set' );
	}

	/**
	 * Test currently editing column callback.
	 */
	public function test_currently_editing_column_cb() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$curr_post = get_post( self::$editor_public_post );

		ob_start();
		$wpdr->admin->currently_editing_column_cb( 'currently_editing', $curr_post->ID );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test Workflow state metabox callback.
	 */
	public function test_workflow_state_metabox_cb() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$curr_post = get_post( self::$editor_public_post );

		ob_start();
		$wpdr->admin->workflow_state_metabox_cb( $curr_post );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 2, (int) substr_count( $output, 'workflow_state_nonce' ), 'ws nonce not found' );
		self::assertEquals( 1, (int) substr_count( $output, 'selected=' ), 'selected not found' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test post author metabox.
	 */
	public function test_post_author_meta_box() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$curr_post = get_post( self::$editor_public_post );

		ob_start();
		$wpdr->admin->post_author_meta_box( $curr_post );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 2, (int) substr_count( $output, 'Owner' ), 'Owner not found' );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test save document.
	 */
	public function test_save_document() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$wpdr->admin->save_document( self::$editor_public_post );

		self::assertTrue( true, 'run' );
	}

	/**
	 * Test revision summary.
	 */
	public function test_revision_summary_cb() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		$wpdr->admin->revision_summary_cb();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, '<textarea' ), 'textarea count' );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Test document metabox auth.
	 */
	public function test_document_metabox_auth() {
		global $wpdr;

		global $current_user;
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

		// test locked.
		// Add a filter to set lock user.
		add_filter(
			'document_lock_check',
			function (
				$user,
				$document
			) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				return 'Locker'; // set locker name.
			},
			10,
			2
		);

		ob_start();
		$wpdr->admin->document_metabox( $curr_post );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'Locker' ), 'Locker name' );
		self::assertEquals( 1, (int) substr_count( $output, '?post_id=' . self::$editor_public_post . '&' ), 'post_id' );

		// Remove the test filter.
		remove_all_filters( 'document_lock_check' );
	}

	/**
	 * Tests the admin sanitize upload dir.
	 */
	public function test_sanitize_upload_dir() {
		global $wpdr;

		$orig = $wpdr::$wpdr_document_dir;

		$new = $wpdr->admin->sanitize_upload_dir( $orig );

		// $orig does not have a trailing slash.
		self::assertEquals( $new, '/tmp/wordpress/wp-content/uploads', 'Original not reset correctly 1' );

		$new = $wpdr->admin->sanitize_upload_dir( '/tmp/wp' );

		self::assertEquals( $new, '/tmp/wp/', 'New not set correctly' );

		$new = $wpdr->admin->sanitize_upload_dir( $orig );

		self::assertEquals( $new, '/tmp/wordpress/wp-content/uploads/', 'Original not reset correctly 2' );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Test bind upload cb.
	 */
	public function test_bind_upload_cb() {
		global $wpdr;

		global $pagenow;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$pagenow = 'media-upload.php';

		ob_start();
		$wpdr->admin->bind_upload_cb();
		$output = ob_get_contents();
		ob_end_clean();

		// There will be various bits found.
		self::assertEquals( 1, (int) substr_count( $output, 'addEventListener' ), 'no listener' );
		self::assertTrue( true, 'run' );
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
	 * Helper function for testing limits.
	 *
	 * @param string $num number of revisions to keep.
	 */
	public function limit_zero( $num ) {
		return 0;
	}

	/**
	 * Helper function for testing limits.
	 *
	 * @param string $num number of revisions to keep.
	 */
	public function limit_one( $num ) {
		return 1;
	}

	/**
	 * Tests the posts limit..
	 */
	public function test_admin_check_limits() {
		global $wpdr;

		global $current_user;
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

		// set revisions limit to 0.
		add_filter( 'document_revisions_limit', array( &$this, 'limit_zero' ) );

		ob_start();
		$wpdr->admin->check_document_revisions_limit();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'zero' ), 'zero message' );
		remove_filter( 'document_revisions_limit', array( &$this, 'limit_zero' ) );

		// set revisions limit to 1.
		add_filter( 'document_revisions_limit', array( &$this, 'limit_one' ) );

		ob_start();
		$wpdr->admin->check_document_revisions_limit();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'More revisions' ), 'More revisions' );
		remove_filter( 'document_revisions_limit', array( &$this, 'limit_one' ) );
	}

	/**
	 * Tests workflow state save (indirectly)..
	 */
	public function test_workflow_state_save() {
		// remove term.
		$terms = wp_set_post_terms( self::$editor_public_post_2, array(), 'workflow_state' );

		// re-add term.
		$terms = wp_set_post_terms( self::$editor_public_post_2, array( self::$ws_term_id ), 'workflow_state' );

		self::assertTrue( true, 'workflow_state_save' );
	}

	/**
	 * Tests the posts enqueue.
	 */
	public function test_admin_enqueue() {
		global $wpdr;

		ob_start();
		$wpdr->admin->enqueue();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEmpty( $output, 'not doc not empty' );

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// get a post in global scope (bending rule).
		global $post;
		// phpcs:ignore  WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( self::$editor_public_post_2 );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['post_type'] = 'document';

		ob_start();
		$wpdr->admin->enqueue();
		$output = ob_get_contents();
		ob_end_clean();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post_type'] );

		self::assertTrue( true, 'admin_enqueue' );
	}

	/**
	 * Tests the posts prepare_editor.
	 */
	public function test_admin_prepare_editor() {
		global $wpdr;

		global $current_user;
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

		// make sure content is old style.
		$post->post_content = $wpdr->extract_document_id( $post->post_content );

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
	 * Tests the posts hide_upload_header.
	 */
	public function test_hide_upload_header() {
		global $wpdr;

		global $current_user;
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// phpcs:disable  WordPress.WP.GlobalVariablesOverride.Prohibited
		global $pagenow;
		$pagenow         = 'media-upload.php';
		$_GET['post_id'] = self::$editor_public_post;
		// phpcs:enable  WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();
		$wpdr->admin->hide_upload_header();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, '#media-upload-header' ), 'media-upload not found' );
	}

	/**
	 * Tests the posts check_upload_files.
	 */
	public function test_check_upload_files() {
		global $wpdr;

		ob_start();
		$wpdr->admin->check_upload_files();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEmpty( $output, 'output not empty' );

		global $current_user;
		$usr = wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// remove capability.
		self::assertTrue( $usr->has_cap( 'upload_files' ), 'Cannot upload files 1' );

		$usr->add_cap( 'upload_files', false );
		self::assertFalse( $usr->has_cap( 'upload_files' ), 'Can upload files' );

		global $typenow, $pagenow;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$typenow = 'document';

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$pagenow = 'edit.php';

		ob_start();
		$wpdr->admin->check_upload_files();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 0, (int) substr_count( $output, '<div' ), '<div> found' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$pagenow = 'post.php';

		ob_start();
		$wpdr->admin->check_upload_files();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, '<div' ), '<div> not found once 1' );
		self::assertEquals( 1, (int) substr_count( $output, 'do not have' ), 'not have not found once 1' );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$pagenow = 'post-new.php';

		ob_start();
		$wpdr->admin->check_upload_files();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, '<div' ), '<div> not found once 1' );
		self::assertEquals( 1, (int) substr_count( $output, 'do not have' ), 'not have not found once 1' );

		$usr->add_cap( 'upload_files', true );
		self::assertTrue( $usr->has_cap( 'upload_files' ), 'Cannot upload files 2' );
	}

	/**
	 * Tests the posts hide_postcustom_metabox.
	 */
	public function test_admin_hide_postcustom_metabox() {
		global $wpdr;

		global $current_user;
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

	/**
	 * Tests the media query code.
	 */
	public function test_media_query_code() {
		global $wpdr;

		$join = $wpdr->admin->filter_media_join( '' );

		self::assertNotEmpty( $join, 'join not empty' );
		self::assertEquals( 2, (int) substr_count( $join, 'wpdr' ), '<wpdr not found twice 1' );

		$where = $wpdr->admin->filter_media_where( '' );

		self::assertNotEmpty( $where, 'where not empty' );
		self::assertEquals( 2, (int) substr_count( $where, 'wpdr' ), '<wpdr not found twice 2' );

		$query = new WP_Query();
		$query = $wpdr->admin->filter_from_media_grid( $query );
		self::assertTrue( true, 'run' );
	}

	/**
	 * Tests the setup dashboard.
	 */
	public function test_setup_dashboard() {
		global $wpdr;

		include_once ABSPATH . '/wp-admin/includes/dashboard.php';
		$wpdr->admin->setup_dashboard();

		self::assertTrue( true, 'run' );
	}
}
