<?php
/**
 * Tests rewrite and access functionality with trailing '/' character.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Access tests
 */
class Test_WP_Document_Revisions_Rewrites extends WP_UnitTestCase {
	/**
	 * Contributor user id
	 *
	 * @var integer $contributor_user_id
	 */
	private static $contributor_user_id;

	/**
	 * Author user id
	 *
	 * @var integer $author_user_id
	 */
	private static $author_user_id;

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
	 * @var integer $author_public_post
	 */
	private static $author_public_post;

	/**
	 * Author Private Post ID
	 *
	 * @var integer $author_private_post
	 */
	private static $author_private_post;

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
	private static $test_file = 'documents/test-file.txt';

	/**
	 * Path to another test file
	 *
	 * @var $test-file2
	 */
	private static $test_file2 = 'documents/test-file-2.txt';

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
		$post_meta  = get_post_meta( $post_id, '_wp_attached_file', true );

		// self:: assertFileEquals( wp_upload_dir() . '/' .  . '/' . $file, $att achment, "Uploaded files don\'t match original ($msg)" );
		self::assertEquals( $attachment, wp_upload_dir() . $post_meta, "Uploaded files don\'t match original ($msg)" );

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

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			$wpdb->posts,
			array(
				'post_content' => $attach_id,
			),
			array(
				'ID' => $post_id,
			)
		);
		wp_cache_flush();

		global $wpdr;

		self::assertGreaterThan( 0, $result, 'Cannot update document post_content with attachment ID' );
		self::assertEquals( $attach_id, $wpdr->get_latest_revision( $post_id )->post_content );
		self::verify_attachment_matches_file( $post_id, $filename, 'Initial Upload' );
	}

	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::consoleLog( 'Test_Rewrites' );

		// don't use gzip.
		add_filter( 'document_use_gzip', '__return_false' );

		// create users.
		// Note that editor can do everything admin can do. Contributors cannot actually upload files by default.
		self::$contributor_user_id = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$author_user_id      = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$editor_user_id      = $factory->user->create( array( 'role' => 'editor' ) );

		// init permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$wp_rewrite->flush_rules();

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
		// Author Public.
		self::$author_public_post = $factory->post->create(
			array(
				'post_title'   => 'Author Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$author_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_public_post ), 'Failed inserting document' );

		// add term and attachment.
		self::add_document_attachment( self::$author_public_post, self::$test_file );

		// Author Private.
		self::$author_private_post = $factory->post->create(
			array(
				'post_title'   => 'Author Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$author_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_private_post ), 'Failed inserting document' );

		// add terms and attachment.
		self::add_document_attachment( self::$author_private_post, self::$test_file );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Author Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document' );

		// add term and attachment.
		self::add_document_attachment( self::$editor_private_post, self::$test_file );

	}

	/**
	 * Output message to log.
	 *
	 * @param string $text text to output.
	 */
	private static function consoleLog( $text ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fwrite( STDERR, "\n" . $text . ' : ' );
	}

	/**
	 * Tests that a given URL actually returns the right file.
	 *
	 * @param string $url to check.
	 * @param string $file relative path of expected file.
	 * @param string $msg message describing failure.
	 */
	private static function verify_download( $url = null, $file = null, $msg = null ) {

		if ( ! $url ) {
			return;
		}

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
		self::assertStringEqualsFile( dirname( __FILE__ ) . '/' . $file, $content, "Contents don\'t match file ($msg)" );
	}

	/**
	 * Tests that a given url *DOES NOT* return a file.
	 *
	 * @param string $url to check.
	 * @param string $file relative path of expected file.
	 * @param string $msg message describing failure.
	 */
	private static function verify_cant_download( $url = null, $file = null, $msg = null ) {

		if ( ! $url ) {
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
		self::assertStringNotEqualsFile( dirname( __FILE__ ) . '/' . $file, $content, "File being erroneously served ($msg)" );

	}

	/**
	 * Does get_permalink generate the right permalink?
	 */
	public function test_permalink() {
		global $wpdr;

		$doc       = get_post( self::$author_public_post );
		$permalink = get_bloginfo( 'url' ) . '/' . $wpdr->document_slug() . '/' . gmdate( 'Y' ) . '/' . gmdate( 'm' ) . '/' . $doc->post_name . $wpdr->get_file_type( $doc_id ) . '/';

		self::assertEquals( $permalink, get_permalink( $doc_id ), 'Bad permalink' );
	}

	/**
	 * Can the public access a public file - doc_id using read? (yes).
	 */
	public function test_public_document_docid_read() {
		global $wpdr;

		self::consoleLog( 'public_document_docid_read' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_true' );

		self::verify_download( '?p=' . self::$author_public_post . '&post_type=document', self::$test_file, 'Public Ugly Permalink Read' );

		remove_filter( 'document_read_uses_read', '__return_true' );
	}

	/**
	 * Can the public access a public file - doc_id using read_document? (no).
	 */
	public function test_public_document_docid_docread() {
		global $wpdr;

		self::consoleLog( 'public_document_docid_docread' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_false' );

		self::verify_download( '?p=' . self::$author_public_post . '&post_type=document', self::$test_file, 'Public Ugly Permalink DocRead' );

		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Can the public access a public file - permalink using read? (yes).
	 */
	public function test_public_document_pretty_read() {
		global $wpdr;

		self::consoleLog( 'public_document_pretty_read' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_true' );

		self::verify_download( get_permalink( self::$author_public_post ), self::$test_file, 'Public Pretty Permalink Read' );

		remove_filter( 'document_read_uses_read', '__return_true' );
	}

	/**
	 * Can the public access a public file - permalink using read_document? (no).
	 */
	public function test_public_document_pretty_docread() {
		global $wpdr;

		self::consoleLog( 'public_document_pretty_docread' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_false' );

		self::verify_download( get_permalink( self::$author_public_post ), self::$test_file, 'Public Pretty Permalink DocRead' );

		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Can the public access a private file - doc_id using read? (no).
	 */
	public function test_private_document_as_unauth_docid_read() {
		global $wpdr;

		self::consoleLog( 'private_document_as_unauth_docid_read' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_true' );

		// public should be denied.
		self::verify_cant_download( '?p=' . self::$author_private_post . '&post_type=document', self::$test_file, 'Private, Unauthenticated Ugly Permalink Read' );

		remove_filter( 'document_read_uses_read', '__return_true' );
	}

	/**
	 * Can the public access a private file - doc_id using document_read? (no).
	 */
	public function test_private_document_as_unauth_docid_docread() {
		global $wpdr;

		self::consoleLog( 'private_document_as_unauth_docid_docread' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_false' );

		// public should be denied.
		self::verify_cant_download( '?p=' . self::$author_private_post . '&post_type=document', self::$test_file, 'Private, Unauthenticated Ugly Permalink DocRead' );

		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Can the public access a private file - permalink using read? (no).
	 */
	public function test_private_document_as_unauth_pretty_read() {
		global $wpdr;

		self::consoleLog( 'private_document_as_unauth_pretty_read' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_true' );

		// public should be denied.
		self::verify_cant_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private, Unauthenticated Pretty Permalink Read' );

		remove_filter( 'document_read_uses_read', '__return_true' );
	}

	/**
	 * Can the public access a private file - permalink using document_read? (no).
	 */
	public function test_private_document_as_unauth_pretty_docread() {
		global $wpdr;

		self::consoleLog( 'private_document_as_unauth_pretty_docread' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user should not read.
		add_filter( 'document_read_uses_read', '__return_false' );

		// public should be denied.
		self::verify_cant_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private, Unauthenticated Pretty Permalink DocRead' );

		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Can an owner access their own private file? (yes).
	 */
	public function test_private_document_as_owner() {
		global $wpdr;

		self::consoleLog( 'private_document_as_owner' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$author_user_id );
		wp_cache_flush();

		self::verify_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Owner' );
	}

	/**
	 * Can an non-owner access their another's private file? (no).
	 */
	public function test_private_document_as_other() {
		global $wpdr;

		self::consoleLog( 'private_document_as_other' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$contributor_user_id );
		wp_cache_flush();

		self::verify_cant_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Other' );
	}

	/**
	 * Can an editor access another's private file? (yes).
	 */
	public function test_private_document_as_admin() {
		global $wpdr;

		self::consoleLog( 'private_document_as_admin' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		self::verify_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Admin' );
	}

}
