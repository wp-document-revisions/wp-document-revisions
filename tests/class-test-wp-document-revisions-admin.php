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
class Test_WP_Document_Revisions_Admin extends WP_UnitTestCase {

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
	 * Author Public Post ID
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
	 * Path to test file
	 *
	 * @var $test_file
	 */
	private static $test_file = __DIR__ . '/../tests/documents/test-file.txt';

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
	private static function add_document_attachment( $post_id, $filename ) {
		$terms = wp_set_post_terms( $post_id, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );

		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $filename ), null );

		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir();

		// create and store attachment ID as post content without creating a revision.
		$attach_id = wp_insert_attachment(
			array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$filename,
			$post_id
		);

		self::assertGreaterThan( 0, $attach_id, 'Cannot create attachment' );

		global $wpdb;

		// now link the attachment, it'll create a revision.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $attach_id,
			)
		);

		wp_cache_flush();

		global $wpdr;

		self::assertEquals( $attach_id, $wpdr->get_latest_revision( $post_id )->post_content, 'Latest reviosion not updated to revision' );
		self::verify_attachment_matches_file( $post_id, $filename, 'Initial Upload' );
	}

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// phpcs:enable
		console_log( 'Test_Admin' );

		global $wpdr;

		// set up admin.
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
		$wpdr->admin_init();

		// create users.
		// Note that editor can do everything admin can do. Contributors cannot actually upload files by default.
		self::$editor_user_id = $factory->user->create(
			array(
				'user_nicename' => 'Editor',
				'role'          => 'editor',
			)
		);

		// init user roles.
		global $wpdr;
		$wpdr->add_caps();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->initialize_workflow_states();
		$ws_terms   = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		$ws_term_id = $ws_terms[0]->term_id;

		// create posts for scenarios.
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
		self::add_document_attachment( self::$editor_public_post, self::$test_file );

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

		// add terms.
		self::add_document_attachment( self::$editor_private_post, self::$test_file );

	}

	/**
	 * Output message to log.
	 *
	 * @param string $text text to output.
	 */
	public static function consoleLog( $text ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fwrite( STDERR, "\n" . $text . ' : ' );
	}

	/**
	 * Verify dashboard display.
	 */
	public function test_dashboard_display_1() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' dashboard_display 1' );

		// see that one post only is seen.
		ob_start();
		$wpdr->admin->dashboard_display();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'display count public 1' );
		self::assertEquals( 1, (int) substr_count( $output, 'Publish' ), 'display publish public 1' );
	}

	/**
	 * Verify dashboard display. Publish the private one, so now two seen.
	 */
	public function test_dashboard_display_2() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' dashboard_display 2' );

		// see that two posts are seen.
		wp_publish_post( self::$editor_private_post );
		ob_start();
		$wpdr->admin->dashboard_display();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'display count all' );
		self::assertEquals( 2, (int) substr_count( $output, 'Publish' ), 'display publish all' );

	}

	/**
	 * Verify revision log metabox. dashboard_display_2 will have created a revision.
	 */
	public function test_revision_metabox_unauth() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' revision_metabox_unauth' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		ob_start();
		$wpdr->admin->revision_metabox( get_post( self::$editor_private_post ) );
		$output = ob_get_contents();
		ob_end_clean();

		// There will be 1 for RSS feed.
		self::assertEquals( 1, (int) substr_count( $output, '<a href' ), 'revision count' );
		self::assertEquals( 0, (int) substr_count( $output, '-revision-1.' ), 'revision count revision 1' );
	}

	/**
	 * Verify revision log metabox. dashboard_display_2 will have created a revision.
	 */
	public function test_revision_metabox_auth() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' revision_metabox_auth' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		$wpdr->admin->revision_metabox( get_post( self::$editor_private_post ) );
		$output = ob_get_contents();
		ob_end_clean();

		// There will be 1 for RSS feed.
		self::assertEquals( 3, (int) substr_count( $output, '<a href' ), 'revision count' );
		self::assertEquals( 1, (int) substr_count( $output, '-revision-1.' ), 'revision count revision 1' );
		self::assertEquals( 0, (int) substr_count( $output, '-revision-2.' ), 'revision count revision 2' );
	}

	/**
	 * Verify document log metabox.
	 */
	public function test_document_metabox() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' document_metabox' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();
		
		$post_obj = get_post( self::$editor_private_post );

		ob_start();
		$wpdr->admin->document_metabox( $post_obj );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'post_id=' . $post_obj->post_content . '&' ), 'document metabox post_id' );
		self::assertEquals( 1, (int) substr_count( $output, get_permalink( self::$editor_private_post ) ), 'document metabox permalin' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'document metabox author' );
	}

}
