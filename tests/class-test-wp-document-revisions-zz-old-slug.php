<?php
/**
 * Tests that changing a document's slug keeps its old URL working (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Old document slug tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Old_Slug extends WP_UnitTestCase {

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create an editor.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Route AJAX wp_die() to the test handler.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$editor );
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', '_wpdr_die_handler_filter' );
	}

	/**
	 * Clean up request globals.
	 */
	public function tear_down() {
		unset( $_POST['post_id'], $_POST['new_title'], $_POST['new_slug'], $_POST['samplepermalinknonce'], $_REQUEST['samplepermalinknonce'] );
		parent::tear_down();
	}

	/**
	 * Create a document with a given slug and status.
	 *
	 * @param string $slug   slug.
	 * @param string $status post status.
	 * @return int document ID.
	 */
	private function create_document( $slug, $status = 'publish' ) {
		return self::factory()->post->create(
			array(
				'post_title'  => 'Old Slug Doc',
				'post_name'   => $slug,
				'post_type'   => 'document',
				'post_status' => $status,
				'post_author' => self::$editor,
			)
		);
	}

	/**
	 * Change a document's slug through the edit screen's AJAX handler.
	 *
	 * @param int    $doc_id document ID.
	 * @param string $slug   new slug.
	 */
	private function change_slug( $doc_id, $slug ) {
		global $wpdr;

		$nonce = wp_create_nonce( 'samplepermalink' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_POST['post_id']                 = $doc_id;
		$_POST['new_title']               = 'Old Slug Doc';
		$_POST['new_slug']                = $slug;
		$_POST['samplepermalinknonce']    = $nonce;
		$_REQUEST['samplepermalinknonce'] = $nonce;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		ob_start();
		try {
			$wpdr->update_post_slug_field();
		} catch ( WPDieException $e ) {
			unset( $e ); // The handler always ends with wp_die() carrying the permalink HTML.
		}
		ob_end_clean();
	}

	/**
	 * The old slug is kept and the action fires.
	 */
	public function test_old_slug_recorded_and_action_fires() {
		$doc   = $this->create_document( 'original-slug' );
		$fired = array();
		$spy   = function ( ...$args ) use ( &$fired ) {
			$fired = $args;
		};
		add_action( 'document_permalink_updated', $spy, 10, 3 );

		$this->change_slug( $doc, 'renamed-slug' );
		remove_action( 'document_permalink_updated', $spy, 10 );

		self::assertSame( 'renamed-slug', get_post( $doc )->post_name );
		self::assertContains( 'original-slug', get_post_meta( $doc, '_wp_old_slug', false ) );
		self::assertSame( array( $doc, 'renamed-slug', 'original-slug' ), $fired );
	}

	/**
	 * Private documents have pretty permalinks too, so their old slug is kept.
	 */
	public function test_private_document_keeps_old_slug() {
		$doc = $this->create_document( 'private-original', 'private' );

		$this->change_slug( $doc, 'private-renamed' );

		self::assertContains( 'private-original', get_post_meta( $doc, '_wp_old_slug', false ) );
	}

	/**
	 * Changing back to an old slug removes it from the old slugs.
	 */
	public function test_reverting_slug_removes_it_from_old_slugs() {
		$doc = $this->create_document( 'first-slug' );

		$this->change_slug( $doc, 'second-slug' );
		$this->change_slug( $doc, 'first-slug' );

		$old = get_post_meta( $doc, '_wp_old_slug', false );
		self::assertNotContains( 'first-slug', $old );
		self::assertContains( 'second-slug', $old );
	}

	/**
	 * Drafts use ?p= links, so nothing is recorded.
	 */
	public function test_draft_records_nothing() {
		$doc = $this->create_document( 'draft-original', 'draft' );

		$this->change_slug( $doc, 'draft-renamed' );

		self::assertSame( array(), get_post_meta( $doc, '_wp_old_slug', false ) );
	}

	/**
	 * Core's old-slug redirect sends the old document URL to the new one.
	 */
	public function test_old_url_redirects() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );

		$doc     = $this->create_document( 'redirect-original' );
		$old_url = get_permalink( $doc );
		$this->change_slug( $doc, 'redirect-renamed' );

		$this->go_to( $old_url );
		self::assertTrue( is_404(), 'The old URL no longer resolves directly.' );

		$redirect = null;
		$capture  = function ( $link ) use ( &$redirect ) {
			$redirect = $link;
			return false; // Stop wp_old_slug_redirect() from redirecting and exiting.
		};
		add_filter( 'old_slug_redirect_url', $capture );
		wp_old_slug_redirect();
		remove_filter( 'old_slug_redirect_url', $capture );
		$expected = get_permalink( $doc );
		$wp_rewrite->set_permalink_structure( '' );

		self::assertSame( $expected, $redirect );
	}
}
