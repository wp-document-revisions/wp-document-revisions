<?php
/**
 * Tests that document attachments are kept out of the media library grid.
 *
 * @package WP_Document_Revisions
 */

/**
 * Media grid filter tests.
 */
class Test_WP_Document_Revisions_Zz_Media_Grid_Filter extends Test_Common_WPDR {

	/**
	 * Private document owned by an editor.
	 *
	 * @var integer
	 */
	private static $private_doc;

	/**
	 * Attachment of the private document.
	 *
	 * @var integer
	 */
	private static $private_attach;

	/**
	 * Draft document owned by the author.
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
	 * Unattached media uploaded by the author.
	 *
	 * @var integer
	 */
	private static $author_media;

	/**
	 * Media attached to a regular post.
	 *
	 * @var integer
	 */
	private static $post_media;

	/**
	 * Editor user id.
	 *
	 * @var integer
	 */
	private static $editor;

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
		global $wpdr;
		$wpdr->register_cpt();
		$wpdr->add_caps();
		$wpdr->admin_init();

		require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author = $factory->user->create( array( 'role' => 'author' ) );
		$wpdr->add_caps();

		self::$private_doc = $factory->post->create(
			array(
				'post_title'   => 'Editor private',
				'post_status'  => 'private',
				'post_author'  => self::$editor,
				'post_type'    => 'document',
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$private_doc, self::$test_file );
		self::$private_attach = $wpdr->get_document( self::$private_doc )->ID;

		self::$author_doc = $factory->post->create(
			array(
				'post_title'   => 'Author draft',
				'post_status'  => 'draft',
				'post_author'  => self::$author,
				'post_type'    => 'document',
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$author_doc, self::$test_file2 );
		self::$author_attach = $wpdr->get_document( self::$author_doc )->ID;

		self::$author_media = $factory->attachment->create_object(
			array(
				'file'           => 'author-media.jpg',
				'post_author'    => self::$author,
				'post_mime_type' => 'image/jpeg',
			)
		);

		$post             = $factory->post->create( array( 'post_author' => self::$editor ) );
		self::$post_media = $factory->attachment->create_object(
			array(
				'file'           => 'post-media.jpg',
				'post_author'    => self::$editor,
				'post_parent'    => $post,
				'post_mime_type' => 'image/jpeg',
			)
		);
	}

	/**
	 * Delete the posts.
	 */
	public static function wpTearDownAfterClass() {
		global $wpdr;

		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );
		wp_delete_post( self::$private_doc, true );
		wp_delete_post( self::$author_doc, true );
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10 );
	}

	/**
	 * Run the ajax handlers without defining DOING_AJAX for later tests.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
	}

	/**
	 * Remove the filters added here or by the grid filter.
	 */
	public function tear_down() {
		global $wpdr;
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		remove_filter( 'document_use_block_editor', '__return_true', PHP_INT_MAX );
		remove_filter( 'document_use_block_editor', '__return_false', PHP_INT_MAX );
		remove_filter( 'posts_where_paged', array( $wpdr->admin, 'filter_media_where' ), 20 );
		unset( $_REQUEST['query'], $_REQUEST['id'] );
		parent::tear_down();
	}

	/**
	 * Return the die handler.
	 *
	 * @return callable
	 */
	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	/**
	 * Stop wp_die() without exiting.
	 *
	 * @throws WPAjaxDieContinueException Always.
	 */
	public function die_handler() {
		throw new WPAjaxDieContinueException( '' );
	}

	/**
	 * Call an ajax handler and decode its JSON response.
	 *
	 * @param callable $handler the ajax handler.
	 * @return array<string, mixed>
	 */
	private function do_ajax( $handler ) {
		ob_start();
		try {
			// Silence the warning from query-attachments sending headers after test output.
			@call_user_func( $handler ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$response = json_decode( ob_get_clean(), true );
		$this->assertIsArray( $response, 'Ajax handler returned no JSON' );
		$this->assertTrue( $response['success'], 'Ajax handler failed' );

		return $response['data'];
	}

	/**
	 * Query the media grid as the author and return the attachment ids.
	 *
	 * @param bool $block_editor whether the block editor is used for documents.
	 * @return int[]
	 */
	private function query_grid( $block_editor ) {
		add_filter( 'document_use_block_editor', $block_editor ? '__return_true' : '__return_false', PHP_INT_MAX );
		wp_set_current_user( self::$author );
		$_REQUEST['query'] = array( 'posts_per_page' => -1 );

		return array_map( 'intval', wp_list_pluck( $this->do_ajax( 'wp_ajax_query_attachments' ), 'id' ) );
	}

	/**
	 * Get an attachment for the media modal as a user.
	 *
	 * @param int $user    the user id.
	 * @param int $attach  the attachment id.
	 * @return array<string, mixed>
	 */
	private function get_attachment( $user, $attach ) {
		wp_set_current_user( $user );
		$_REQUEST['id'] = $attach;

		return $this->do_ajax( 'wp_ajax_get_attachment' );
	}

	/**
	 * Check the grid shows other media but no document attachments.
	 *
	 * @param int[] $ids the attachment ids returned.
	 */
	private function assert_grid_filtered( $ids ) {
		$this->assertNotContains( self::$private_attach, $ids, 'Private document attachment listed' );
		$this->assertNotContains( self::$author_attach, $ids, 'Document attachment listed' );
		$this->assertContains( self::$author_media, $ids, 'Own media not listed' );
		$this->assertContains( self::$post_media, $ids, 'Post media not listed' );
	}

	/**
	 * The author cannot read the private document but can upload files.
	 */
	public function test_fixture() {
		wp_set_current_user( self::$author );
		$this->assertFalse( current_user_can( 'read_document', self::$private_doc ) );
		$this->assertTrue( current_user_can( 'upload_files' ) );
	}

	/**
	 * Classic editor: the media grid excludes document attachments.
	 */
	public function test_media_grid_classic_editor() {
		$this->assert_grid_filtered( $this->query_grid( false ) );
	}

	/**
	 * Block editor: the media grid excludes document attachments.
	 */
	public function test_media_grid_block_editor() {
		$this->assert_grid_filtered( $this->query_grid( true ) );
	}

	/**
	 * Details of another user's document attachment are hidden.
	 */
	public function test_get_attachment_masked() {
		$file = get_post_meta( self::$private_attach, '_wp_attached_file', true );
		$data = $this->get_attachment( self::$author, self::$private_attach );

		$this->assertSame( self::$private_attach, (int) $data['id'] );
		$this->assertSame( '', $data['url'], 'URL exposed' );
		$this->assertSame( '', $data['link'], 'Link exposed' );
		$this->assertEmpty( $data['sizes'] ?? array(), 'Sizes exposed' );
		$this->assertStringNotContainsString( get_post_field( 'post_excerpt', self::$private_attach ), $data['caption'], 'Caption exposed' );
		foreach ( array( 'filename', 'name', 'title' ) as $key ) {
			$this->assertStringNotContainsString( pathinfo( $file, PATHINFO_FILENAME ), $data[ $key ], "$key exposed" );
		}
		$this->assertStringNotContainsString( pathinfo( $file, PATHINFO_FILENAME ), wp_json_encode( $data ), 'File name exposed' );
	}

	/**
	 * The attachment data doesn't reveal the document it belongs to.
	 */
	public function test_get_attachment_hides_document() {
		$data = $this->get_attachment( self::$author, self::$private_attach );

		$this->assertStringNotContainsString( 'Editor private', wp_json_encode( $data ), 'Document title exposed' );
		foreach ( array( 'uploadedToLink', 'uploadedToTitle', 'editLink', 'alt', 'originalImageURL', 'originalImageName' ) as $key ) {
			$this->assertEmpty( $data[ $key ] ?? '', "$key exposed" );
		}
	}

	/**
	 * Details of a document attachment the user can edit are not hidden.
	 */
	public function test_get_attachment_not_masked_for_editors() {
		$data = $this->get_attachment( self::$author, self::$author_attach );
		$this->assertSame( basename( get_attached_file( self::$author_attach ) ), $data['filename'] );
		$this->assertNotEmpty( $data['url'] );

		$data = $this->get_attachment( self::$editor, self::$private_attach );
		$this->assertSame( basename( get_attached_file( self::$private_attach ) ), $data['filename'] );
		$this->assertNotEmpty( $data['url'] );
	}

	/**
	 * Non-document media is not hidden.
	 */
	public function test_get_attachment_not_masked_for_media() {
		$data = $this->get_attachment( self::$author, self::$post_media );
		$this->assertSame( 'post-media.jpg', $data['filename'] );
		$this->assertNotEmpty( $data['url'] );
	}
}
