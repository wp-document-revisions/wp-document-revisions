<?php
/**
 * Tests rewrite and access functionality without trailing '/' character.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Access tests
 */
class Test_WP_Document_Revisions_Rewrites_Without extends Test_Common_WPDR {
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
	 * Editor Public Post ID (contains revision)
	 *
	 * @var integer $editor_public_post
	 */
	private static $editor_public_post;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
	// phpcs:enable
		console_log( 'Test_Rewrites-Without' );

		// don't use gzip.
		add_filter( 'document_use_gzip', '__return_false' );

		// Set EMPTY_TRASH_DAYS to 2 (so trash will work).
		if ( ! defined( 'EMPTY_TRASH_DAYS' ) ) {
			define( 'EMPTY_TRASH_DAYS', 2 );
		}

		// create users.
		// Note that editor can do everything admin can do. Contributors cannot actually upload files by default.
		self::$contributor_user_id = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$author_user_id      = $factory->user->create( array( 'role' => 'author' ) );
		self::$editor_user_id      = $factory->user->create( array( 'role' => 'editor' ) );

		// init permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%' );
		$wp_rewrite->flush_rules();

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->add_caps();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->initialize_workflow_states();
		$ws_terms         = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::$ws_term_id = $ws_terms[0]->term_id;

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
		$terms = wp_set_post_terms( self::$author_public_post, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$author_public_post, self::$test_file );

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
		$terms = wp_set_post_terms( self::$author_private_post, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$author_private_post, self::$test_file );

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

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );

		// For debug.
		$posts = $wpdr->get_revisions( self::$editor_private_post );
		console_log( ' Editor Private' );
		foreach ( $posts as $post ) {
			console_log( $post->ID . '/' . $post->post_content . '/' . $post->post_type );
		}

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

		self::assertFalse( is_wp_error( self::$editor_public_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_public_post, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file );

		// add attachment (again).
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file2 );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$author_public_post, 1, 1 );
		self::verify_structure( self::$author_private_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
		self::verify_structure( self::$editor_public_post, 2, 2 );
	}

	/**
	 * Does the document archive work?
	 */
	public function test_archive() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		// For debug.
		$posts = $wpdr->get_revisions( self::$editor_public_post );
		console_log( ' Editor Public' );
		foreach ( $posts as $post ) {
			console_log( $post->ID . '/' . $post->post_content . '/' . $post->post_type );
		}

		console_log( ' test archive' );

		self::go_to( get_home_url( null, $wpdr->document_slug() ) );
		self::assertTrue( is_post_type_archive( 'document' ), 'Couldn\'t access /documents/' );
	}

	/**
	 * Does get_permalink generate the right permalink?
	 */
	public function test_permalink() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		$doc       = get_post( self::$author_public_post );
		$permalink = get_bloginfo( 'url' ) . '/' . $wpdr->document_slug() . '/' . gmdate( 'Y' ) . '/' . gmdate( 'm' ) . '/' . $doc->post_name . $wpdr->get_file_type( $doc->ID );

		self::assertEquals( $permalink, get_permalink( $doc->ID ), 'Bad permalink' );
	}

	/**
	 * Can the public access a public file - doc_id using read? (yes).
	 */
	public function test_public_document_docid_read() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' public_document_docid_read' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' public_document_docid_docread' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user does not has read_document so should not read.
		add_filter( 'document_read_uses_read', '__return_false' );

		self::verify_cant_download( '?p=' . self::$author_public_post . '&post_type=document', self::$test_file, 'Public Ugly Permalink DocRead' );

		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Can the public access a public file - permalink using read? (yes).
	 */
	public function test_public_document_pretty_read() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' public_document_pretty_read' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' public_document_pretty_docread' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		add_filter( 'document_read_uses_read', '__return_false' );

		self::verify_cant_download( get_permalink( self::$author_public_post ), self::$test_file, 'Public Pretty Permalink DocRead' );

		remove_filter( 'document_read_uses_read', '__return_false' );
	}

	/**
	 * Can the public access a private file - doc_id using read? (no).
	 */
	public function test_private_document_as_unauth_docid_read() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_unauth_docid_read' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_unauth_docid_docread' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_unauth_pretty_read' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_unauth_pretty_docread' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_owner' );

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
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_other' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$contributor_user_id );
		wp_cache_flush();

		self::verify_cant_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Other' );
	}

	/**
	 * Can an editor access another's private file? (yes).
	 */
	public function test_private_document_as_editor() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' private_document_as_editor' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		self::verify_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Editor' );
	}

	/**
	 * Can an author delete another's private file? (no).
	 */
	public function test_del_other_private_document_as_author() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' del_other_private_document_as_author' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$author_user_id );
		wp_cache_flush();

		self::check_trash_delete( self::$editor_private_post, false );
	}

	/**
	 * Can an author delete own published file? (yes).
	 */
	public function test_del_own_public_document_as_author() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' del_own_public_document_as_author' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$author_user_id );
		wp_cache_flush();

		self::check_trash_delete( self::$author_public_post, true );
	}

	/**
	 * Can an author delete another's published file? (yes).
	 */
	public function test_del_other_public_document_as_author() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' del_other_public_document_as_author' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$author_user_id );
		wp_cache_flush();

		self::check_trash_delete( self::$editor_public_post, true );
	}

}
