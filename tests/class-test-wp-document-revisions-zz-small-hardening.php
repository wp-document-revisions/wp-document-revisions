<?php
/**
 * Tests the profile feed key, old slug redirect, and REST meta write fixes.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Small hardening tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Small_Hardening extends WP_UnitTestCase {

	/**
	 * Administrator.
	 *
	 * @var int
	 */
	private static $admin;

	/**
	 * Editor.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Author.
	 *
	 * @var int
	 */
	private static $author;

	/**
	 * Another author.
	 *
	 * @var int
	 */
	private static $other_author;

	/**
	 * Create users.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		self::$admin        = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$editor       = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author       = $factory->user->create( array( 'role' => 'author' ) );
		self::$other_author = $factory->user->create( array( 'role' => 'author' ) );
		if ( is_multisite() ) {
			grant_super_admin( self::$admin );
		}
	}

	/**
	 * Clean up request globals.
	 */
	public function tear_down() {
		unset( $_POST['generate-new-feed-key'], $_POST['_document_revisions_nonce'] );
		parent::tear_down();
	}

	/**
	 * Submit the "Generate New Key" button for a user's profile as the current user.
	 *
	 * @param int $user_id the user whose profile is being saved.
	 */
	private function submit_new_feed_key( $user_id ) {
		global $wpdr;
		$_POST['generate-new-feed-key']     = 'Generate New Key';
		$_POST['_document_revisions_nonce'] = wp_create_nonce( 'generate-new-feed-key' );
		$wpdr->admin->profile_update_cb( $user_id );
	}

	/**
	 * An admin regenerating another user's key changes that user's key, not the admin's.
	 */
	public function test_profile_update_regenerates_edited_users_key() {
		global $wpdr;
		$author_key = $wpdr->admin->generate_new_feed_key( self::$author );
		$admin_key  = $wpdr->admin->generate_new_feed_key( self::$admin );

		wp_set_current_user( self::$admin );
		$this->submit_new_feed_key( self::$author );

		self::assertNotSame( $author_key, $wpdr->admin->get_feed_key( self::$author ), 'The edited user gets a new key.' );
		self::assertSame( $admin_key, $wpdr->admin->get_feed_key( self::$admin ), "The admin's own key is unchanged." );
	}

	/**
	 * A user regenerating their own key still works.
	 */
	public function test_profile_update_regenerates_own_key() {
		global $wpdr;
		$key = $wpdr->admin->generate_new_feed_key( self::$author );

		wp_set_current_user( self::$author );
		$this->submit_new_feed_key( self::$author );

		self::assertNotSame( $key, $wpdr->admin->get_feed_key( self::$author ) );
	}

	/**
	 * A user who can't edit the target user changes nobody's key.
	 */
	public function test_profile_update_needs_edit_user() {
		global $wpdr;
		$own_key   = $wpdr->admin->generate_new_feed_key( self::$author );
		$other_key = $wpdr->admin->generate_new_feed_key( self::$other_author );

		wp_set_current_user( self::$author );
		$this->submit_new_feed_key( self::$other_author );

		self::assertSame( $other_key, $wpdr->admin->get_feed_key( self::$other_author ) );
		self::assertSame( $own_key, $wpdr->admin->get_feed_key( self::$author ) );
	}

	/**
	 * Without a valid nonce nothing changes.
	 */
	public function test_profile_update_needs_nonce() {
		global $wpdr;
		$key = $wpdr->admin->generate_new_feed_key( self::$author );

		wp_set_current_user( self::$admin );
		$_POST['generate-new-feed-key']     = 'Generate New Key';
		$_POST['_document_revisions_nonce'] = 'bogus';
		$wpdr->admin->profile_update_cb( self::$author );

		self::assertSame( $key, $wpdr->admin->get_feed_key( self::$author ) );
	}

	/**
	 * Create a document with an old slug, returning its ID and its URL under the old slug.
	 *
	 * @param string $status post status.
	 * @return array{0: int, 1: string}
	 */
	private function document_with_old_slug( $status ) {
		$doc = self::factory()->post->create(
			array(
				'post_title'  => 'Renamed Doc',
				'post_name'   => $status . '-old-slug',
				'post_type'   => 'document',
				'post_status' => $status,
				'post_author' => self::$editor,
			)
		);
		wp_set_current_user( self::$editor );
		$old_url = get_permalink( $doc );
		wp_update_post(
			array(
				'ID'        => $doc,
				'post_name' => $status . '-new-slug',
			)
		);
		add_post_meta( $doc, '_wp_old_slug', $status . '-old-slug' );
		return array( $doc, $old_url );
	}

	/**
	 * Run the old slug redirect for a URL as a user, returning the redirect target (or null).
	 *
	 * @param string $url     the requested URL.
	 * @param int    $user_id the visitor.
	 * @return string|null
	 */
	private function old_slug_redirect( $url, $user_id ) {
		wp_set_current_user( $user_id );
		$this->go_to( $url );
		self::assertTrue( is_404(), 'The old URL no longer resolves directly.' );

		$redirect = null;
		$capture  = function ( $link ) use ( &$redirect ) {
			$redirect = $link;
			return false; // Stop wp_old_slug_redirect() from redirecting and exiting.
		};
		add_filter( 'old_slug_redirect_url', $capture );
		wp_old_slug_redirect();
		remove_filter( 'old_slug_redirect_url', $capture );
		return $redirect;
	}

	/**
	 * Anonymous visitors aren't sent to a private document's new slug.
	 */
	public function test_old_slug_redirect_hides_private_document() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		list( , $old_url ) = $this->document_with_old_slug( 'private' );
		$redirect          = $this->old_slug_redirect( $old_url, 0 );
		$wp_rewrite->set_permalink_structure( '' );

		self::assertNull( $redirect );
	}

	/**
	 * Users who can read the private document are still redirected.
	 */
	public function test_old_slug_redirect_private_document_for_reader() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		list( $doc, $old_url ) = $this->document_with_old_slug( 'private' );
		$redirect              = $this->old_slug_redirect( $old_url, self::$editor );
		$expected              = get_permalink( $doc );
		$wp_rewrite->set_permalink_structure( '' );

		self::assertSame( $expected, $redirect );
	}

	/**
	 * Anonymous visitors are still redirected to a published document's new slug.
	 */
	public function test_old_slug_redirect_published_document_for_anonymous() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		list( $doc, $old_url ) = $this->document_with_old_slug( 'publish' );
		$redirect              = $this->old_slug_redirect( $old_url, 0 );
		$expected              = get_permalink( $doc );
		$wp_rewrite->set_permalink_structure( '' );

		self::assertSame( $expected, $redirect );
	}

	/**
	 * Put documents in REST and dispatch a GET for one as a user.
	 *
	 * @param int $doc     document ID.
	 * @param int $user_id user ID.
	 * @return WP_REST_Response
	 */
	private function rest_get_document( $doc, $user_id ) {
		global $wpdr, $wpdr_mr, $wp_rest_server;

		// $wpdr_mr is created once, possibly during an earlier test, and the test framework
		// removes hooks added during a test afterwards. Hook a fresh instance if needed.
		$wpdr->manage_rest();
		if ( false === has_filter( 'rest_request_before_callbacks', array( $wpdr_mr, 'document_validation' ) ) ) {
			new WP_Document_Revisions_Manage_Rest( $wpdr );
		}
		add_filter( 'document_show_in_rest', '__return_true' );
		unregister_post_type( 'document' );
		$wpdr->register_cpt();
		$post_type                 = get_post_type_object( 'document' );
		$post_type->rest_base      = 'documents';
		$post_type->rest_namespace = 'wp/v2';
		$wp_rest_server            = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		do_action( 'rest_api_init' );

		wp_set_current_user( $user_id );
		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/documents/' . $doc ) );

		remove_filter( 'document_show_in_rest', '__return_true' );
		$wpdr->register_cpt();
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $response;
	}

	/**
	 * Create a document with an attachment, whose attachment meta hasn't been populated yet.
	 *
	 * @param string $status post status.
	 * @return array{0: int, 1: int} document and attachment IDs.
	 */
	private function document_without_attachment_meta( $status ) {
		$doc    = self::factory()->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => $status,
				'post_author' => self::$editor,
			)
		);
		$attach = self::factory()->attachment->create(
			array(
				'post_parent'    => $doc,
				'post_mime_type' => 'text/plain',
				'post_author'    => self::$editor,
			)
		);
		wp_update_post(
			array(
				'ID'           => $doc,
				'post_content' => '<!-- WPDR ' . $attach . ' -->',
			)
		);
		delete_post_meta( $doc, '_document_attachment_id' );
		return array( $doc, $attach );
	}

	/**
	 * An anonymous GET on a private document doesn't write its meta.
	 */
	public function test_rest_get_unauthorized_does_not_write_meta() {
		list( $doc ) = $this->document_without_attachment_meta( 'private' );

		$response = $this->rest_get_document( $doc, 0 );

		self::assertGreaterThanOrEqual( 400, $response->get_status() );
		self::assertFalse( metadata_exists( 'post', $doc, '_document_attachment_id' ) );
	}

	/**
	 * An authorized GET still populates the meta.
	 */
	public function test_rest_get_authorized_populates_meta() {
		list( $doc, $attach ) = $this->document_without_attachment_meta( 'private' );

		$response = $this->rest_get_document( $doc, self::$editor );

		self::assertSame( 200, $response->get_status() );
		self::assertSame( $attach, absint( get_post_meta( $doc, '_document_attachment_id', true ) ) );
	}
}
