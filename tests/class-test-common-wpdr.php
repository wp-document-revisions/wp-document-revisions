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
	 * Path to test file
	 *
	 * @var $test_file
	 */
	public static $test_file = __DIR__ . '/documents/test-file.txt';

	/**
	 * Path to another test file
	 *
	 * @var $test-file2
	 */
	public static $test_file2 = __DIR__ . '/documents/test-file-2.txt';

	/**
	 * Prepare a copy of the input file with encoded name...
	 *
	 * N.B. Delete tests will delete this file.
	 *
	 * @param integer $post_id post id of document.
	 * @param string  $file    file name of file being loaded.
	 * @return string  file name of copied file to link to attachment.
	 */
	public static function create_file_copy( $post_id, $file ) {
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
	public static function verify_attachment_matches_file( $post_id = null, $file = null, $msg = null ) {

		if ( ! $post_id ) {
			return;
		}

		$doc        = get_post( $post_id );
		$attachment = get_attached_file( $doc->post_content );

		self::assertTrue( is_string( $attachment ), 'Attached file not found on ' . $doc->post_content . '/' . $doc->post_title );
		self::assertFileEquals( $file, $attachment, "Uploaded files don\'t match original ($msg)" );
	}

	// phpcs:disable
	/**
	 * Add test file attachment to post.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @param integer $post_id  The Post ID to attach.
	 * @param string  $filename The file name to attach.
	 * @return void.
	 */
	public static function add_document_attachment( WP_UnitTest_Factory $factory, $post_id, $filename ) {
		// phpcs:enable
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
		$attach_id = $factory->attachment->create(
			array(
				'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $post_id,
				'file'           => $new_file,
			)
		);

		self::assertGreaterThan( 0, $attach_id, 'Cannot create attachment' );

		// now link the attachment, it'll create a revision.
		$updt = $factory->post->update_object(
			$post_id,
			array(
				'post_content' => $attach_id,
			)
		);
		console_log( 'updt: ' . $updt . ' on ' . $post_id );

		self::assertGreaterThan( 0, $updt, 'Cannot update post' );

		wp_cache_flush();

		global $wpdr;

		self::assertEquals( $attach_id, $wpdr->get_latest_revision( $post_id )->post_content );
		self::verify_attachment_matches_file( $post_id, $filename, 'Initial Upload' );
		self::verify_attachment_matches_file( $post_id, $new_file, 'File Loaded' );
	}

	/**
	 * Tests that a given URL actually returns the right file.
	 *
	 * @param string $url to check.
	 * @param string $file relative path of expected file.
	 * @param string $msg message describing failure.
	 */
	public function verify_download( $url = null, $file = null, $msg = null ) {

		// check parameters.
		self::assertNotNull( $url, 'Parameter url not entered' );
		self::assertNotNull( $file, 'Parameter file not entered' );

		global $wpdr;
		flush_rewrite_rules();

		self::go_to( $url );

		// verify contents are actually served.
		ob_start();
		$wpdr->serve_file( '' );
		$content = ob_get_contents();
		ob_end_clean();

		self::assertFalse( is_404(), "404 ($msg)" );
		self::assertFalse( _wpdr_is_wp_die(), "wp_died ($msg)" );
		self::assertTrue( is_single(), "Not single ($msg)" );
		self::assertStringEqualsFile( $file, $content, "Contents don\'t match file ($msg)" );
	}

	/**
	 * Tests that the Document stucture is correct.
	 *
	 * @param int $post_id the ID of the parent post.
	 * @param int $revns   number of revisions expected.
	 * @param int $attach  number of attachments expected.
	 */
	public function verify_structure( $post_id = null, $revns = null, $attach = null ) {

		// check parameters.
		self::assertNotNull( $post_id, 'Parameter post_id not entered' );
		self::assertNotNull( $revns, 'Parameter #revisions not entered' );
		self::assertNotNull( $attach, 'Parameter #attachments not entered' );

		// confirm post is document.
		$doc = get_post( $post_id );
		self::assertInstanceOf( WP_Post, $doc, "Post $post_id does not exist" );
		self::assertInternalType( PHPUnit_IsType::TYPE_INT, $doc->post_content );

		// check post type.
		self::assertEquals( get_post_type( $doc ), 'document', "Post $post_id not a document" );

		// get revisions.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$revs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM wp_posts WHERE post_parent = %d AND post_type = 'revision' ORDER BY ID ASC",
				$post_id
			)
		);

		self::assertEquals( $revns, $wpdb->num_rows, "Expected revisions of $post_id not found" );

		// check attachments.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$attchs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM wp_posts WHERE post_parent = %d AND post_type = 'attachment' ORDER BY ID ASC",
				$post_id
			)
		);

		self::assertEquals( $attach, $wpdb->num_rows, "Expected attachments of $post_id not found" );
		self::assertEquals( $doc->post_content, end( $attchs )->ID, 'Document held is not the last one' );
	}

	/**
	 * Tests that a given url *DOES NOT* return a file.
	 *
	 * @param string $url to check.
	 * @param string $file relative path of expected file.
	 * @param string $msg message describing failure.
	 */
	public function verify_cant_download( $url = null, $file = null, $msg = null ) {

		if ( is_null( $url ) || is_null( $file ) ) {
			self::assertTrue( false, 'Parameter URL or file not entered' );
			return;
		}

		global $wpdr;

		flush_rewrite_rules();

		self::go_to( $url );

		// verify whether contents are actually served.
		ob_start();
		$wpdr->serve_file( '' );
		$content = ob_get_contents();
		ob_end_clean();

		global $wp_query;

		self::assertEmpty( $wp_query->posts, "No posts returned ($msg)" );
		self::assertTrue( ( empty( $content ) || is_404() || _wpdr_is_wp_die() ), "No content, not 404'd or wp_die'd ($msg)" );
		self::assertStringNotEqualsFile( $file, $content, "File being erroneously served ($msg)" );
	}

	/**
	 * Tests that all elements of a post are trashed or deleted.
	 *
	 * @param integer $post_id Post ID.
	 * @param boolean $trash   Expect to work (Permissions check..
	 */
	public function check_trash_delete( $post_id = null, $trash = null ) {

		if ( is_null( $post_id ) || is_null( $trash ) ) {
			self::assertTrue( false, 'Parameters not entered' );
			return;
		}

		self::assertGreaterThan( 0, EMPTY_TRASH_DAYS, 'Empty Trash Days not set' );

		global $wpdr;

		// create a list of all elements (document, revisions and attachments).
		$all_posts = array();
		// retrieve document and revisions.
		$posts = $wpdr->get_revisions( $post_id );
		foreach ( $posts as $post ) {
			$all_posts[ $post->ID ] = null;
			// add attachment records.
			$all_posts[ $post->post_content ] = get_attached_file( $post->post_content );
			self::assertFileExists( $all_posts[ $post->post_content ], 'Attachment file does not exist' );
		}

		console_log( 'Delete? : ' . + current_user_can( 'delete_document', $post_id ) . ':' );

		// first trash the document.
		$result = wp_trash_post( $post_id );

		if ( $result instanceof WP_Post ) {
			console_log( $result->post_status );
		} else {
			console_log( 'Result: ' . $result );
		}

		// Is this expected to work?
		if ( ! $trash ) {
			self::assertFalse( $result instanceof WP_Post, 'Trash document should not work' );
			return;
		}

		// flush cache to assure result.
		wp_cache_flush();

		self::assertTrue( $result instanceof WP_Post, 'Trash document did not work' );

		// check trash status.
		self::assertEquals( get_post_status( $post_id ), 'trash', "Post $post_id not set to trash" );

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin::$instance, 'delete_attachments_with_document' ), 10, 1 );

		// delete the post.
		wp_delete_post( $post_id );

		// add the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin::$instance, 'delete_attachments_with_document' ), 10, 1 );

		// check nothing remains.
		foreach ( $all_posts as $id => $i ) {
			$test_post = get_post( $id );
			self::assertFalse( $test_post instanceof WP_Post, "Post $id not deleted" );
			if ( ! is_null( $i ) ) {
				self::assertFileNotExists( $i, 'Attachment file still exists' );
			}
		}
	}

	/**
	 * Verify class is loaded (and to avoid a warning).
	 */
	public function test_class_loaded() {

		console_log( 'Test Common WPDR' );

		self::assertTrue( true, 'common wpdr loaded' );
	}
}
