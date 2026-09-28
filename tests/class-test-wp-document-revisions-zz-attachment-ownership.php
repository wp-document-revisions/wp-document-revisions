<?php
/**
 * Tests that a document can only reference its own attachments.
 *
 * @package WP_Document_Revisions
 */

/**
 * Attachment ownership tests.
 */
class Test_WP_Document_Revisions_Zz_Attachment_Ownership extends Test_Common_WPDR {

	/**
	 * Private document owned by an editor.
	 *
	 * @var integer
	 */
	private static $victim_doc;

	/**
	 * Attachment of the private document.
	 *
	 * @var integer
	 */
	private static $victim_attach;

	/**
	 * Published document owned by an author.
	 *
	 * @var integer
	 */
	private static $author_doc;

	/**
	 * Attachment of the author's document.
	 *
	 * @var integer
	 */
	private static $author_attach;

	/**
	 * Author user id.
	 *
	 * @var integer
	 */
	private static $author;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// phpcs:enable
		add_filter( 'document_show_in_rest', '__return_true' );

		global $wpdr;
		$wpdr->register_cpt();
		$wpdr->add_caps();

		if ( ! class_exists( 'WP_Document_Revisions_Manage_Rest' ) ) {
			$wpdr->manage_rest();
		}

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$editor       = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author = $factory->user->create( array( 'role' => 'author' ) );
		$wpdr->add_caps();

		self::$victim_doc = $factory->post->create(
			array(
				'post_title'   => 'Victim private',
				'post_status'  => 'private',
				'post_author'  => $editor,
				'post_type'    => 'document',
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$victim_doc, self::$test_file );
		self::$victim_attach = $wpdr->get_document( self::$victim_doc )->ID;

		self::$author_doc = $factory->post->create(
			array(
				'post_title'   => 'Author public',
				'post_status'  => 'publish',
				'post_author'  => self::$author,
				'post_type'    => 'document',
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$author_doc, self::$test_file2 );
		self::$author_attach = $wpdr->get_document( self::$author_doc )->ID;
	}

	/**
	 * Delete the posts.
	 */
	public static function wpTearDownAfterClass() {
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );
		wp_delete_post( self::$victim_doc, true );
		wp_delete_post( self::$author_doc, true );
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10 );

		remove_filter( 'document_show_in_rest', '__return_true' );
	}

	/**
	 * Mirror a real REST request: REST is not is_admin(), so the admin class guard on
	 * wp_insert_post_data is not loaded there (the test harness always loads it).
	 */
	public function set_up() {
		parent::set_up();

		// Earlier test classes can leave the REST validation filter detached.
		global $wpdr_mr;
		if ( false === has_filter( 'rest_request_before_callbacks', array( $wpdr_mr, 'document_validation' ) ) ) {
			add_filter( 'rest_request_before_callbacks', array( $wpdr_mr, 'document_validation' ), 10, 3 );
		}

		global $wpdr;
		if ( isset( $wpdr->admin ) ) {
			remove_filter( 'wp_insert_post_data', array( $wpdr->admin, 'restore_document_attachment_id' ), 10 );
		}
	}

	/**
	 * Restore the author's document to its own attachment.
	 */
	public function tear_down() {
		global $wpdb, $wpdr;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, array( 'post_content' => $wpdr->format_doc_id( self::$author_attach ) ), array( 'ID' => self::$author_doc ) );
		clean_post_cache( self::$author_doc );
		update_post_meta( self::$author_doc, '_document_attachment_id', self::$author_attach );
		if ( isset( $wpdr->admin ) ) {
			add_filter( 'wp_insert_post_data', array( $wpdr->admin, 'restore_document_attachment_id' ), 10, 2 );
		}
		parent::tear_down();
	}

	/**
	 * As the author, try to point their document at the private document's attachment.
	 *
	 * @return WP_REST_Response
	 */
	private function forge_request() {
		wp_set_current_user( self::$author );
		$this->assertFalse( current_user_can( 'read_document', self::$victim_doc ), 'Author should not read the private document' );

		$request = new WP_REST_Request( 'POST', '/wp/v2/documents/' . self::$author_doc );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$request->set_header( 'x-wp-nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_body_params(
			array(
				'content' => '<!-- WPDR ' . self::$victim_attach . ' -->',
				'meta'    => array( '_document_attachment_id' => self::$victim_attach ),
			)
		);

		$response = rest_do_request( $request );
		clean_post_cache( self::$author_doc );

		return $response;
	}

	/**
	 * Assert the author's document still resolves to its own attachment.
	 */
	private function assert_not_repointed() {
		global $wpdr;
		$attach = $wpdr->get_document( self::$author_doc );
		$this->assertInstanceOf( WP_Post::class, $attach, 'Document lost its attachment' );
		$this->assertSame( self::$author_attach, $attach->ID, 'Document repointed at another attachment' );
		$this->assertNotEquals( self::$victim_attach, (int) get_post_meta( self::$author_doc, '_document_attachment_id', true ), 'Meta repointed at another attachment' );
	}

	/**
	 * With REST on (no block editor), an edit context must not allow writes.
	 */
	public function test_rest_edit_context_does_not_bypass_get_only() {
		// Earlier tests may leave block editor mode on; this test needs it off.
		add_filter( 'document_use_block_editor', '__return_false', PHP_INT_MAX );
		$response = $this->forge_request();
		remove_filter( 'document_use_block_editor', '__return_false', PHP_INT_MAX );
		$this->assertTrue( $response->is_error(), 'Write should be refused' );
		$this->assertSame( 'rest_cannot_modify', $response->as_error()->get_error_code() );
		$this->assert_not_repointed();
	}

	/**
	 * In block editor mode, a forged attachment id in meta and content is not used.
	 */
	public function test_block_editor_forged_attachment_not_used() {
		global $wpdr_mr;
		add_filter( 'document_use_block_editor', '__return_true' );
		add_filter( 'rest_pre_insert_document', array( $wpdr_mr, 'sync_meta_to_content' ), 10, 2 );

		// Drop the stored meta so the forged request meta is the only attachment id source.
		delete_post_meta( self::$author_doc, '_document_attachment_id' );

		$response = $this->forge_request();

		remove_filter( 'rest_pre_insert_document', array( $wpdr_mr, 'sync_meta_to_content' ), 10 );
		remove_filter( 'document_use_block_editor', '__return_true' );

		$this->assert_not_repointed();
	}

	/**
	 * In block editor mode, a forged marker in the content is dropped and the real one kept.
	 */
	public function test_block_editor_forged_marker_replaced() {
		global $wpdr, $wpdr_mr;
		add_filter( 'document_use_block_editor', '__return_true' );
		add_filter( 'rest_pre_insert_document', array( $wpdr_mr, 'sync_meta_to_content' ), 10, 2 );

		wp_set_current_user( self::$author );
		$request = new WP_REST_Request( 'POST', '/wp/v2/documents/' . self::$author_doc );
		$request->set_header( 'x-wp-nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_body_params( array( 'content' => '<!-- WPDR ' . self::$victim_attach . ' -->Description' ) );
		$response = rest_do_request( $request );
		clean_post_cache( self::$author_doc );

		remove_filter( 'rest_pre_insert_document', array( $wpdr_mr, 'sync_meta_to_content' ), 10 );
		remove_filter( 'document_use_block_editor', '__return_true' );

		$this->assertFalse( $response->is_error(), 'Legitimate update should succeed' );
		$content = get_post_field( 'post_content', self::$author_doc, 'raw' );
		$this->assertSame( self::$author_attach, $wpdr->extract_document_id( $content ) );
		$this->assertStringContainsString( 'Description', $content );
		$this->assert_not_repointed();
	}

	/**
	 * Get_document ignores a marker naming another document's attachment.
	 */
	public function test_get_document_requires_ownership() {
		global $wpdb, $wpdr;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, array( 'post_content' => $wpdr->format_doc_id( self::$victim_attach ) ), array( 'ID' => self::$author_doc ) );
		clean_post_cache( self::$author_doc );

		$this->assertFalse( $wpdr->get_document( self::$author_doc ) );
		$this->assertSame( self::$victim_attach, $wpdr->get_document( self::$victim_doc )->ID, 'Owner still resolves' );
	}

	/**
	 * The attachment id meta can't name another document's attachment.
	 */
	public function test_meta_guard() {
		$this->assertFalse( update_post_meta( self::$author_doc, '_document_attachment_id', self::$victim_attach ) );
		$this->assertSame( self::$author_attach, (int) get_post_meta( self::$author_doc, '_document_attachment_id', true ) );

		// Own attachment and zero are still fine.
		$this->assertTrue( update_post_meta( self::$author_doc, '_document_attachment_id', 0 ) );
		$this->assertTrue( update_post_meta( self::$author_doc, '_document_attachment_id', self::$author_attach ) );
	}
}
