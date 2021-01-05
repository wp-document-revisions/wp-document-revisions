<?php
/**
 * Common class code functionality.
 *
 * Ensure assert code will be with the class.
 *
 * @author Neil James <neil@familyjames.com>
 * @package WP_Document_Revisions
 */

/**
 * WP_UnitTestCase extended with a few common routines.
 */
class Test_Common_WPDR extends WP_UnitTestCase {

	/**
	 * Prepare a copy of the input file with encoded name...
	 *
	 * N.B. Delete tests will delete this file.
	 *
	 * @param integer $post_id post id of document.
	 * @param string  $file    file name of file being loaded.
	 * @return string  file name of copied file to link to attachment.
	 */
	private static function create_file_copy( $post_id, $file ) {
		global $wpdr;

		// ensure that rename function will be called.
		$_POST['post_id'] = $post_id;
		$wpdr::$doc_image = false;

		// create file structure.
		$file_name = array( 'name' => basename( $file ) );

		// call coding function.
		$new_name = $wpdr->filename_rewrite( $file_name );

		$new_file = wp_upload_dir()['path'] . '/' . $new_name['name'];

		// check directory exists.
		self::assertTrue( wp_mkdir_p( dirname( $new_file ) ), 'check directory exists' );

		// create the file copy.
		copy( $file, $new_file );

		return $new_file;
	}

	/**
	 * Make sure a file is properly uploaded and attached.
	 *
	 * @param int    $post_id the ID of the parent post.
	 * @param string $file relative url to file.
	 * @param string $msg message to display on failure.
	 */
	private static function verify_attachment_matches_file( $post_id = null, $file = null, $msg = null ) {

		if ( ! $post_id ) {
			return;
		}

		$doc        = get_post( $post_id );
		$attachment = get_attached_file( $doc->post_content );

		self::assertTrue( is_string( $attachment ), 'Attached file not found on ' . $doc->post_content . '/' . $doc->post_title );
		self::assertFileEquals( $file, $attachment, "Uploaded files don\'t match original ($msg)" );
	}

	/**
	 * Add test file attachment to post.
	 *
	 * @param integer $post_id  The Post ID to attach.
	 * @param string  $filename The file name to attach.
	 * @return void.
	 */
	public static function add_document_attachment( $post_id, $filename ) {
		self::assertNotEmpty( $filename, 'Filename for post ' . $post_id . ' must be entered' );

		// check $post_id is a document.
		global $wpdr;
		self::assertTrue( $wpdr->verify_post_type( $post_id ), 'check document attach' );

		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $filename ), null );

		// Create a copy of the input file.
		$new_file = self::create_file_copy( $post_id, $filename );

		// Get upload directory.
		$upload_dir = wp_upload_dir();

		// create and store attachment ID as post content..
		$attach_id = wp_insert_attachment(
			array(
				'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$new_file,
			$post_id
		);

		self::assertGreaterThan( 0, $attach_id, 'Cannot create attachment' );

		// now link the attachment, it'll create a revision.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $attach_id,
			)
		);

		wp_cache_flush();

		global $wpdr;

		self::assertEquals( $attach_id, $wpdr->get_latest_revision( $post_id )->post_content );
		self::verify_attachment_matches_file( $post_id, $filename, 'Initial Upload' );
		self::verify_attachment_matches_file( $post_id, $new_file, 'File Loaded' );
	}

	/**
	 * Verify class is loaded (and to avoid a warning).
	 */
	public function test_class_loaded() {

		console_log( 'Test Common WPDR' );

		self::assertTrue( true, 'common wpdr loaded' );
	}
}
