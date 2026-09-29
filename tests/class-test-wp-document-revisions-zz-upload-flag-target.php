<?php
/**
 * Tests that the document upload flag only applies to a document the user can edit.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document upload flag target tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Upload_Flag_Target extends WP_UnitTestCase {

	/**
	 * Contributor user id.
	 *
	 * @var int
	 */
	private static $contributor;

	/**
	 * Editor user id.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create users.
	 *
	 * @param WP_UnitTest_Factory $factory factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$contributor = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$editor      = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Reset the per-request flag and allow an extra type for document uploads only.
	 */
	public function set_up() {
		parent::set_up();
		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
		WP_Document_Revisions::$doc_image = true;

		add_filter( 'document_allowed_mimes', array( $this, 'allow_dwg' ) );
		wp_set_current_user( self::$contributor );
	}

	/**
	 * Clean up request state.
	 */
	public function tear_down() {
		global $wpdr;
		unset( $_POST['upload_source'], $_POST['post_id'], $_REQUEST['post_id'] );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		remove_filter( 'document_allowed_mimes', array( $this, 'allow_dwg' ) );
		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
		WP_Document_Revisions::$doc_image = true;
		parent::tear_down();
	}

	/**
	 * Adds DWG to the allowed types for document uploads.
	 *
	 * @param array<string, string> $mimes allowed types.
	 * @return array<string, string>
	 */
	public function allow_dwg( $mimes ) {
		$mimes['dwg'] = 'image/vnd.dwg';
		return $mimes;
	}

	/**
	 * A draft document.
	 *
	 * @param int $author author id.
	 * @return int
	 */
	private function document( $author ) {
		return self::factory()->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'draft',
				'post_author' => $author,
			)
		);
	}

	/**
	 * Runs filename_rewrite() with the document upload flag and an optional post_id.
	 *
	 * @param int|null $post_id the post_id sent with the upload, or null for none.
	 * @return array<string, mixed> the (possibly renamed) file.
	 */
	private function flagged_upload( $post_id ) {
		global $wpdr;

		// phpcs:disable WordPress.Security.NonceVerification
		$_POST['upload_source'] = 'wp-document-revisions';
		if ( null !== $post_id ) {
			$_POST['post_id']    = $post_id;
			$_REQUEST['post_id'] = $post_id;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return $wpdr->filename_rewrite( array( 'name' => 'plan.dwg' ) );
	}

	/**
	 * Asserts the upload was handled as a document upload.
	 *
	 * @param array<string, mixed> $file the file returned by filename_rewrite().
	 */
	private function assert_document_upload( $file ) {
		global $wpdr;
		self::assertTrue( $wpdr->is_document_upload(), 'is_document_upload()' );
		self::assertNotSame( 'plan.dwg', $file['name'], 'The file name is hashed.' );
		self::assertFalse( WP_Document_Revisions::$doc_image, 'Stored in the document directory.' );
		self::assertArrayHasKey( 'dwg', get_allowed_mime_types(), 'document_allowed_mimes applies.' );
	}

	/**
	 * Asserts the upload was handled as an ordinary media upload.
	 *
	 * @param array<string, mixed> $file the file returned by filename_rewrite().
	 */
	private function assert_media_upload( $file ) {
		global $wpdr;
		self::assertFalse( $wpdr->is_document_upload(), 'is_document_upload()' );
		self::assertSame( 'plan.dwg', $file['name'], 'The file name is kept.' );
		self::assertTrue( WP_Document_Revisions::$doc_image, 'Stored in the media directory.' );
		self::assertArrayNotHasKey( 'dwg', get_allowed_mime_types(), 'document_allowed_mimes does not apply.' );
	}

	/**
	 * Flag + the contributor's own document: a document upload.
	 */
	public function test_own_document_is_document_upload() {
		$started = did_action( 'document_upload_start' );
		$file    = $this->flagged_upload( $this->document( self::$contributor ) );

		$this->assert_document_upload( $file );
		self::assertSame( $started + 1, did_action( 'document_upload_start' ) );
	}

	/**
	 * Flag + someone else's document the contributor can't edit: a media upload.
	 */
	public function test_others_document_is_media_upload() {
		$started = did_action( 'document_upload_start' );
		$file    = $this->flagged_upload( $this->document( self::$editor ) );

		$this->assert_media_upload( $file );
		self::assertSame( $started, did_action( 'document_upload_start' ) );
	}

	/**
	 * Flag + a regular post the contributor owns: a media upload.
	 */
	public function test_regular_post_is_media_upload() {
		$post = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => self::$contributor,
			)
		);

		$this->assert_media_upload( $this->flagged_upload( $post ) );
	}

	/**
	 * Flag without a post_id: a media upload.
	 */
	public function test_no_post_id_is_media_upload() {
		$this->assert_media_upload( $this->flagged_upload( null ) );
	}

	/**
	 * Flag + post_id 0: a media upload.
	 */
	public function test_zero_post_id_is_media_upload() {
		$this->assert_media_upload( $this->flagged_upload( 0 ) );
	}

	/**
	 * An editor can upload to the same document the contributor can't.
	 */
	public function test_editor_can_upload_to_others_document() {
		wp_set_current_user( self::$editor );

		$this->assert_document_upload( $this->flagged_upload( $this->document( self::$contributor ) ) );
	}
}
