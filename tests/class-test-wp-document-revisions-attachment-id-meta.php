<?php
/**
 * Tests server-side management of the document_attachment_id meta (issue #539).
 *
 * The meta is the source of truth for the current document file during editing;
 * post_content carries the WPDR comment only at rest (for revisioning) and is
 * rebuilt server-side on save.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Attachment id meta tests.
 */
class Test_WP_Document_Revisions_Attachment_Id_Meta extends Test_Common_WPDR {

	/**
	 * Editor user id.
	 *
	 * @var integer
	 */
	private static $editor_user_id;

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
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();

		// make sure that we have the admin set up (registers restore_document_attachment_id).
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		$wpdr->add_caps();

		self::$editor_user_id = $factory->user->create(
			array(
				'user_nicename' => 'AttachMetaEditor',
				'role'          => 'administrator',
			)
		);

		wp_cache_flush();
	}

	/**
	 * Delete the user after the tests.
	 */
	public static function wpTearDownAfterClass() {
		if ( self::$editor_user_id ) {
			self::delete_user( self::$editor_user_id );
		}
	}

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$editor_user_id );
	}

	/**
	 * Create a bare document post.
	 *
	 * @param string $content initial post_content.
	 * @return int document id.
	 */
	private function make_document( $content = '' ) {
		$doc_id = wp_insert_post(
			array(
				'post_title'   => 'Attach Meta Doc - ' . wp_rand(),
				'post_status'  => 'private',
				'post_author'  => self::$editor_user_id,
				'post_content' => $content,
				'post_type'    => 'document',
			)
		);
		self::assertGreaterThan( 0, $doc_id, 'Could not create document' );
		return (int) $doc_id;
	}

	/**
	 * Create an attachment parented to a post, optionally as a document-file upload.
	 *
	 * In the real upload flow filename_rewrite() sets WP_Document_Revisions::$doc_image
	 * to false for a genuine document-file upload (and leaves it true for featured
	 * images / thumbnails). The factory does not run that prefilter, so the flag is
	 * set here to simulate the relevant upload type.
	 *
	 * @param int  $parent_id parent post id.
	 * @param bool $is_document_file whether to simulate a document-file upload.
	 * @return int attachment id.
	 */
	private function make_attachment( $parent_id, $is_document_file = true ) {
		$prev                              = WP_Document_Revisions::$doc_image;
		WP_Document_Revisions::$doc_image  = ! $is_document_file;
		$attach_id = self::factory()->attachment->create(
			array(
				'post_mime_type' => 'text/plain',
				'post_title'     => 'attach-' . wp_rand(),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $parent_id,
			)
		);
		WP_Document_Revisions::$doc_image = $prev;
		self::assertGreaterThan( 0, $attach_id, 'Could not create attachment' );
		return (int) $attach_id;
	}

	/**
	 * Uploading (add_attachment) a file parented to a document records its id in meta.
	 */
	public function test_add_attachment_sets_meta() {
		$doc_id    = $this->make_document( '' );
		$attach_id = $this->make_attachment( $doc_id );

		self::assertSame(
			$attach_id,
			(int) get_post_meta( $doc_id, 'document_attachment_id', true ),
			'add_attachment did not record the attachment id in meta'
		);

		wp_delete_post( $doc_id, true );
		wp_delete_post( $attach_id, true );
	}

	/**
	 * Attachments parented to a non-document are ignored.
	 */
	public function test_add_attachment_ignores_non_document_parent() {
		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Regular Post - ' . wp_rand(),
				'post_type'  => 'post',
			)
		);

		$attach_id = $this->make_attachment( $post_id );

		self::assertSame(
			'',
			get_post_meta( $post_id, 'document_attachment_id', true ),
			'non-document parent should not receive document_attachment_id meta'
		);

		wp_delete_post( $post_id, true );
		wp_delete_post( $attach_id, true );
	}

	/**
	 * A featured-image upload (also parented to the document) must not overwrite the
	 * current document-file id in meta.
	 */
	public function test_featured_image_does_not_change_meta() {
		$doc_id    = $this->make_document( '' );
		$file_id   = $this->make_attachment( $doc_id, true );

		self::assertSame( $file_id, (int) get_post_meta( $doc_id, 'document_attachment_id', true ), 'document file id not recorded' );

		// Simulate a featured-image upload parented to the same document.
		$image_id = $this->make_attachment( $doc_id, false );

		self::assertSame(
			$file_id,
			(int) get_post_meta( $doc_id, 'document_attachment_id', true ),
			'featured-image upload clobbered the document file id'
		);

		wp_delete_post( $doc_id, true );
		wp_delete_post( $file_id, true );
		wp_delete_post( $image_id, true );
	}

	/**
	 * Populate seeds meta from post_content when empty and does not clobber an
	 * existing (e.g. uploaded-but-unsaved) value.
	 */
	public function test_populate_meta_from_content_without_clobber() {
		global $wpdr;

		$doc_id = $this->make_document( $wpdr->format_doc_id( 999 ) );

		// Seeding from content when the meta is empty.
		delete_post_meta( $doc_id, 'document_attachment_id' );
		$seeded = $wpdr->populate_document_attachment_meta( $doc_id );
		self::assertSame( 999, $seeded, 'populate did not return the seeded id' );
		self::assertSame( 999, (int) get_post_meta( $doc_id, 'document_attachment_id', true ), 'meta not seeded from content' );

		// An uploaded-but-unsaved version (meta ahead of content) must not be clobbered.
		update_post_meta( $doc_id, 'document_attachment_id', 555 );
		$again = $wpdr->populate_document_attachment_meta( $doc_id );
		self::assertSame( 555, $again, 'populate clobbered an existing meta value' );
		self::assertSame( 555, (int) get_post_meta( $doc_id, 'document_attachment_id', true ), 'existing meta was overwritten from content' );

		wp_delete_post( $doc_id, true );
	}

	/**
	 * The full classic flow: with the id in meta and only a description posted to
	 * post_content, the save reintegrates the WPDR comment server-side.
	 */
	public function test_save_reintegrates_id_from_meta() {
		global $wpdr;

		$doc_id    = $this->make_document( '' );
		$attach_id = $this->make_attachment( $doc_id );

		// Sanity: upload recorded the id in meta.
		self::assertSame( $attach_id, (int) get_post_meta( $doc_id, 'document_attachment_id', true ), 'meta not set by upload' );

		// Simulate a classic save where post_content carries only the description.
		wp_update_post(
			array(
				'ID'           => $doc_id,
				'post_content' => 'My description text',
			)
		);

		$doc = get_post( $doc_id );
		self::assertSame(
			$attach_id,
			(int) $wpdr->extract_document_id( $doc->post_content ),
			'attachment id was not reintegrated into post_content from meta'
		);
		self::assertStringContainsString(
			'My description text',
			$doc->post_content,
			'description was lost during reintegration'
		);

		wp_delete_post( $doc_id, true );
		wp_delete_post( $attach_id, true );
	}

	/**
	 * When post_content already carries a WPDR id (block REST path), restore leaves
	 * it untouched rather than overwriting from meta.
	 */
	public function test_save_does_not_override_explicit_content_id() {
		global $wpdr;

		$doc_id    = $this->make_document( '' );
		$attach_id = $this->make_attachment( $doc_id );

		// meta now holds $attach_id, but the save explicitly supplies a different id.
		wp_update_post(
			array(
				'ID'           => $doc_id,
				'post_content' => $wpdr->format_doc_id( 4242 ) . 'Explicit',
			)
		);

		$doc = get_post( $doc_id );
		self::assertSame(
			4242,
			(int) $wpdr->extract_document_id( $doc->post_content ),
			'explicit post_content id should win over meta'
		);

		wp_delete_post( $doc_id, true );
		wp_delete_post( $attach_id, true );
	}
}
