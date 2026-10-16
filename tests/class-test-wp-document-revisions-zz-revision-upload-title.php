<?php
/**
 * Tests that a new revision is linked to its document whatever the uploaded file was called.
 *
 * Core titles an upload from the original file name ("My Test File.pdf" becomes "My Test File")
 * and derives the slug from that title ("my-test-file"), so the two differ whenever the name has
 * capitals, spaces or accents. check_doc_attach() used to require them to match, so such uploads
 * never updated _document_attachment_id and saving the document kept the old file.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Revision upload title tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Revision_Upload_Title extends WP_UnitTestCase {

	/**
	 * Earlier test classes start document uploads that never generate metadata, so reset the
	 * per-request flag.
	 */
	public function set_up() {
		parent::set_up();
		$this->set_upload_flag( false );
	}

	/**
	 * Clean up request state.
	 */
	public function tear_down() {
		global $wpdr;
		unset( $_POST['upload_source'], $_POST['post_id'], $_POST['post_content'] );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		WP_Document_Revisions::$doc_image = true;
		$this->set_upload_flag( false );
		parent::tear_down();
	}

	/**
	 * Sets the per-request document upload flag.
	 *
	 * @param bool $value the flag value.
	 * @return void
	 */
	private function set_upload_flag( $value ) {
		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, $value );
	}

	/**
	 * Creates a document whose current file is an existing attachment.
	 *
	 * @return array{0:int,1:int} the document id and its current attachment id.
	 */
	private function document_with_file() {
		global $wpdr;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$doc = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$old = self::factory()->attachment->create(
			array(
				'post_parent' => $doc,
				'post_title'  => 'first-version',
				'post_status' => 'inherit',
			)
		);
		update_post_meta( $doc, '_document_attachment_id', $old );
		wp_update_post(
			array(
				'ID'           => $doc,
				'post_content' => $wpdr->format_doc_id( $old ),
			)
		);
		return array( $doc, $old );
	}

	/**
	 * Uploads a new version through the document upload path, as async-upload.php would.
	 *
	 * @param int    $doc   the document id.
	 * @param string $title the attachment title core derives from the original file name.
	 * @return int the new attachment id.
	 */
	private function upload_new_version( $doc, $title ) {
		global $wpdr;
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$_POST['upload_source'] = 'wp-document-revisions';
		$_POST['post_id']       = $doc;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$wpdr->filename_rewrite( array( 'name' => $title . '.txt' ) );

		// Core sets the title from the original name and the slug from sanitize_title().
		$new = self::factory()->attachment->create(
			array(
				'post_parent' => $doc,
				'post_title'  => $title,
				'post_status' => 'inherit',
			)
		);
		apply_filters( 'wp_generate_attachment_metadata', array(), $new, 'create' );
		return $new;
	}

	/**
	 * Data provider of original file names (without extension) whose title and slug differ.
	 *
	 * @return array<string, array{0:string}>
	 */
	public function titles_unlike_slugs() {
		return array(
			'capitals' => array( 'Annual Report' ),
			'spaces'   => array( 'annual report' ),
			'accents'  => array( 'Résumé' ),
		);
	}

	/**
	 * Uploading a new version updates the document's attachment id.
	 *
	 * @dataProvider titles_unlike_slugs
	 * @param string $title the attachment title.
	 */
	public function test_upload_updates_attachment_meta( $title ) {
		list( $doc, $old ) = $this->document_with_file();

		$new = $this->upload_new_version( $doc, $title );

		self::assertNotSame( $title, get_post_field( 'post_name', $new ), 'Fixture title should differ from its slug' );
		self::assertSame( $new, (int) get_post_meta( $doc, '_document_attachment_id', true ), 'Document still points at the old file' );
	}

	/**
	 * Saving the document after the upload keeps the new file, not the old one.
	 *
	 * @dataProvider titles_unlike_slugs
	 * @param string $title the attachment title.
	 */
	public function test_save_after_upload_keeps_new_file( $title ) {
		global $wpdr;
		if ( ! $wpdr->admin ) {
			$wpdr->admin_init();
		}
		list( $doc, $old ) = $this->document_with_file();

		$new = $this->upload_new_version( $doc, $title );

		// The classic editor posts the marker the upload callback wrote into post_content.
		$_POST['post_content'] = $wpdr->format_doc_id( $new );
		$result                = $wpdr->admin->restore_document_attachment_id(
			array(
				'post_type'    => 'document',
				'post_content' => $wpdr->format_doc_id( $new ),
			),
			array(
				'ID'           => $doc,
				'post_content' => $wpdr->format_doc_id( $new ),
			)
		);

		self::assertSame( $new, (int) $wpdr->extract_document_id( $result['post_content'] ), 'Save reverted to the old file' );
	}

	/**
	 * Attachments whose slug was derived from a title with capitals, spaces or accents count as
	 * document files, including core's -N suffix for duplicate slugs.
	 *
	 * @dataProvider titles_unlike_slugs
	 * @param string $title the attachment title.
	 */
	public function test_check_doc_attach_accepts_derived_slugs( $title ) {
		$doc   = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$first = self::factory()->attachment->create_and_get(
			array(
				'post_parent' => $doc,
				'post_title'  => $title,
				'post_status' => 'inherit',
			)
		);
		$dupe  = self::factory()->attachment->create_and_get(
			array(
				'post_parent' => $doc,
				'post_title'  => $title,
				'post_status' => 'inherit',
			)
		);

		self::assertTrue( WP_Document_Revisions::check_doc_attach( $first ) );
		self::assertNotSame( $first->post_name, $dupe->post_name, 'Fixture should have a suffixed slug' );
		self::assertTrue( WP_Document_Revisions::check_doc_attach( $dupe ) );
	}

	/**
	 * Attachments that aren't parented to a document, or whose slug was edited away from the
	 * title, still don't count.
	 */
	public function test_check_doc_attach_still_rejects_others() {
		$page   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$doc    = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$on_pg  = self::factory()->attachment->create_and_get(
			array(
				'post_parent' => $page,
				'post_title'  => 'Annual Report',
				'post_status' => 'inherit',
			)
		);
		$edited = self::factory()->attachment->create_and_get(
			array(
				'post_parent' => $doc,
				'post_title'  => 'Annual Report',
				'post_name'   => 'something-else',
				'post_status' => 'inherit',
			)
		);

		self::assertFalse( WP_Document_Revisions::check_doc_attach( $on_pg ) );
		self::assertFalse( WP_Document_Revisions::check_doc_attach( $edited ) );
	}
}
