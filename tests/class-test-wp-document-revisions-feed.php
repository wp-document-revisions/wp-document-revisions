<?php
/**
 * Tests feed functionality.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Access tests
 */
class Test_WP_Document_Revisions_Feed extends Test_Common_WPDR {
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

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {

		// don't use gzip.
		add_filter( 'document_use_gzip', '__return_false' );

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// make sure that we have the admin set up - to use feed functions.
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

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
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
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

		// add second attachment.
		self::add_document_attachment( $factory, self::$author_public_post, self::$test_file2 );

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

	}

	/**
	 * Delete the posts. (Taken from WP Test Suite).
	 */
	public static function wpTearDownAfterClass() {
		// remove terms.
		wp_remove_object_terms( self::$author_public_post, self::$ws_term_id, 'workflow_state' );
		wp_remove_object_terms( self::$author_private_post, self::$ws_term_id, 'workflow_state' );

		// make sure that we have the admin set up.
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$author_public_post, true );
		wp_delete_post( self::$author_private_post, true );

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
	 * Simulate accessing a revision log feed.
	 *
	 * @param string $url the URL to try.
	 *
	 * @return string the content returned
	 * @throws Exception If there's an error.
	 */
	public function simulate_feed( $url = null ) {
		if ( ! $url ) {
			return '';
		}

		$this->go_to( $url );
		global $wpdr, $post, $wp_query;

		$wpdr->revision_feed_auth();

//		if ( is_404() ) {
	//		return '';
	//	}

		ob_start();
		require dirname( __DIR__ ) . '/includes/revision-feed.php';
		$content = ob_get_clean();

		return $content;
	}
	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$author_public_post, 2, 2 );
		self::verify_structure( self::$author_private_post, 1, 1 );
	}


	/**
	 * Can the public access a revision log feed?
	 */
	public function test_feed_as_unauthenticated() {

		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		self::assertFalse( $wpdr->validate_feed_key(), 'not properly validating feed key' );

		// try to get an un auth'd feed.
		$exception = null;
		$content = '';
		try {
			$content = self::simulate_feed( get_permalink( self::$author_private_post ) . 'feed/' );
		} catch ( WPDieException $e ) {
			$exception = $e;
		}

		self::assertNotNull( $exception, 'not properly denying access to feeds' );
		self::assertEquals( 0, substr_count( $content, '<item>' ), 'denied feed leaking items' );
	}

	/**
	 * Can a user with the proper feed key access a feed (author)?
	 */
	public function test_feed_as_authorized_auth() {

		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		// try to get an auth'd feed.
		$wpdr->admin->generate_new_feed_key( self::$users['author']->ID );
		$key = $wpdr->admin->get_feed_key( self::$users['author']->ID );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['key'] = $key;

		$exception = null;
		$content = '';
		try {
			$content = self::simulate_feed( add_query_arg( 'key', $key, get_permalink( self::$author_public_post ) . 'feed/' ) );
		} catch ( WPDieException $e ) {
			$exception = $e;
		}

		self::assertTrue( $wpdr->validate_feed_key(), 'not properly validating feed key' );
		self::assertNull( $exception, 'Not properly allowing access to feeds_1' );
		self::assertEquals( 3, (int) substr_count( $content, '<item>' ), 'improper feed item count_1' );

		$exception = null;
		try {
			$content = self::simulate_feed( add_query_arg( 'key', $key, get_permalink( self::$author_private_post ) . 'feed/' ) );
		} catch ( WPDieException $e ) {
			$exception = $e;
		}

		self::assertTrue( $wpdr->validate_feed_key(), 'not properly validating feed key' );
		self::assertNull( $exception, 'Not properly allowing access to feeds_2' );
		self::assertEquals( 2, (int) substr_count( $content, '<item>' ), 'improper feed item count_2' );
	}

}