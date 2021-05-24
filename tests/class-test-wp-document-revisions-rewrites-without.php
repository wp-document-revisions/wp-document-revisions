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
	 * List of users being tested.
	 *
	 * @var WP_User[] $users
	 */
	protected static $users = array(
		'editor'      => null,
		'author'      => null,
		'contributor' => null,
	);

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
		// don't use gzip.
		add_filter( 'document_serve_use_gzip', '__return_false' );

		// Set EMPTY_TRASH_DAYS to 2 (so trash will work).
		if ( ! defined( 'EMPTY_TRASH_DAYS' ) ) {
			define( 'EMPTY_TRASH_DAYS', 2 );
		}

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor'      => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author'      => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
			'contributor' => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Contributor',
					'role'          => 'contributor',
				)
			),
		);

		// init permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%' );
		$wp_rewrite->flush_rules();

		// flush cache for good measure.
		wp_cache_flush();

		// create terms and use one.
		$wpdr->register_ct();

		// Check no values.
		$ws_terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::assertEquals( 0, count( $ws_terms ), 'Taxonomy not empty' );

		$wpdr->initialize_workflow_states();

		// Taxonomy terms recreated as fixtures.
		$ws_terms         = self::create_term_fixtures( $factory );
		self::$ws_term_id = (int) $ws_terms[0]->term_id;

		// manage term counting.
		global $wp_version;
		$vers = strpos( $wp_version, '-' );
		$vers = $vers ? substr( $wp_version, 0, $vers ) : $wp_version;
		if ( version_compare( $vers, '5.7' ) >= 0 ) {
			// core method introduced with version 5.7.
			add_filter( 'update_post_term_count_statuses', array( $wpdr, 'review_count_statuses' ), 30, 2 );
		} else {
			$wpdr->register_term_count_cb();
		}

		// create posts for scenarios.
		// Author Public.
		self::$author_public_post = $factory->post->create(
			array(
				'post_title'   => 'Author Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['author']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_public_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$author_public_post, array( self::$ws_term_id ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow state to document' );
		self::add_document_attachment( $factory, self::$author_public_post, self::$test_file );

		// Author Private.
		self::$author_private_post = $factory->post->create(
			array(
				'post_title'   => 'Author Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['author']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_private_post ), 'Failed inserting document' );

		// add terms and attachment.
		$terms = wp_set_post_terms( self::$author_private_post, array( self::$ws_term_id ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow state to document' );
		self::add_document_attachment( $factory, self::$author_private_post, self::$test_file );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, array( self::$ws_term_id ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow state to document' );
		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );

		// Editor Public.
		self::$editor_public_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_public_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_public_post, array( self::$ws_term_id ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow state to document' );
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file );

		// add attachment (again).
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file2 );
	}

	/**
	 * Delete the posts. (Taken from WP Test Suite).
	 */
	public static function wpTearDownAfterClass() {
		// remove terms.
		wp_remove_object_terms( self::$author_public_post, self::$ws_term_id, 'workflow_state' );
		wp_remove_object_terms( self::$author_private_post, self::$ws_term_id, 'workflow_state' );
		wp_remove_object_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );
		wp_remove_object_terms( self::$editor_public_post, self::$ws_term_id, 'workflow_state' );

		// make sure that we have the admin set up.
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$author_public_post, true );
		wp_delete_post( self::$author_private_post, true );
		wp_delete_post( self::$editor_private_post, true );
		wp_delete_post( self::$editor_public_post, true );

		// delete done, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		// clear down the ws terms.
		$ws_terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);

		// delete them all.
		foreach ( $ws_terms as $ws_term ) {
			wp_delete_term( $ws_term->term_id, 'workflow_state' );
			clean_term_cache( $ws_term->term_id, 'workflow_state' );
		}

		unregister_taxonomy( 'workflow_state' );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$author_public_post, 1, 1 );
		self::verify_structure( self::$author_private_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
		self::verify_structure( self::$editor_public_post, 2, 2 );

		// All posts have been assigned to term 1.
		$term = get_term( self::$ws_term_id, 'workflow_state' );
		self::assertEquals( 4, $term->count, 'Term count not correct' );
	}

	/**
	 * Does the document archive work?
	 */
	public function test_archive() {
		global $wpdr;

		self::go_to( get_home_url( null, $wpdr->document_slug() ) );
		self::assertTrue( is_post_type_archive( 'document' ), 'Couldn\'t access /documents/' );
	}

	/**
	 * Does get_permalink generate the right permalink?
	 */
	public function test_permalink() {
		global $wpdr;

		$doc       = get_post( self::$author_public_post );
		$permalink = get_bloginfo( 'url' ) . '/' . $wpdr->document_slug() . '/' . gmdate( 'Y' ) . '/' . gmdate( 'm' ) . '/' . $doc->post_name . $wpdr->get_file_type( $doc->ID );

		self::assertEquals( $permalink, get_permalink( $doc->ID ), 'Bad permalink' );
	}

	/**
	 * Can the public access a public file - doc_id using read? (yes).
	 */
	public function test_public_document_docid_read() {
		global $wpdr;

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

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user does not has read_document so should not read.
		self::set_up_document_read();

		self::verify_cant_download( '?p=' . self::$author_public_post . '&post_type=document', self::$test_file, 'Public Ugly Permalink DocRead' );

		self::tear_down_document_read();
	}

	/**
	 * Can the public access a public file - permalink using read? (yes).
	 */
	public function test_public_document_pretty_read() {
		global $wpdr;

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

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should read.
		self::set_up_document_read();

		self::verify_cant_download( get_permalink( self::$author_public_post ), self::$test_file, 'Public Pretty Permalink DocRead' );

		self::tear_down_document_read();
	}

	/**
	 * Can the public access a private file - doc_id using read? (no).
	 */
	public function test_private_document_as_unauth_docid_read() {
		global $wpdr;

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

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should not read.
		self::set_up_document_read();

		// public should be denied.
		self::verify_cant_download( '?p=' . self::$author_private_post . '&post_type=document', self::$test_file, 'Private, Unauthenticated Ugly Permalink DocRead' );

		self::tear_down_document_read();
	}

	/**
	 * Can the public access a private file - permalink using read? (no).
	 */
	public function test_private_document_as_unauth_pretty_read() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user has read so should not read.
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

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// non-logged on user should not read.
		self::set_up_document_read();

		// public should be denied.
		self::verify_cant_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private, Unauthenticated Pretty Permalink DocRead' );

		self::tear_down_document_read();
	}

	/**
	 * Can an owner access their own private file? (yes).
	 */
	public function test_private_document_as_owner() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		self::verify_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Owner' );
	}

	/**
	 * Can an non-owner access their another's private file? (no).
	 */
	public function test_private_document_as_other() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['contributor']->ID );
		wp_cache_flush();

		self::verify_cant_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Other' );
	}

	/**
	 * Can an editor access another's private file? (yes).
	 */
	public function test_private_document_as_editor() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// Note that Author cannot upload files so no access possible.
		self::verify_download( get_permalink( self::$author_private_post ), self::$test_file, 'Private Editor', true );
	}

	/**
	 * Can an author delete another's private file? (no).
	 */
	public function test_del_other_private_document_as_author() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		self::check_trash_delete( self::$editor_private_post, false );
	}

	/**
	 * Can an author delete own published file? (yes).
	 */
	public function test_del_own_public_document_as_author() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		self::check_trash_delete( self::$author_public_post, true );
	}

	/**
	 * Can an author delete another's published file? (yes).
	 */
	public function test_del_other_public_document_as_author() {
		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		self::check_trash_delete( self::$editor_public_post, true );
	}

}
