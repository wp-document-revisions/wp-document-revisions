<?php
/**
 * Tests that callbacks on core hooks tolerate non-canonical arguments (#732, #733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Hook callback argument tests.
 *
 * Themes and plugins sometimes apply core filters themselves with fewer
 * arguments, or an earlier callback returns false or null. None of that should
 * fatal. Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Hook_Callback_Args extends WP_UnitTestCase {

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private static $document_id;

	/**
	 * Create a published document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$document_id = $factory->post->create(
			array(
				'post_title'   => 'Hook Args Document',
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_excerpt' => 'Revision note',
			)
		);
	}

	/**
	 * Point the global post and main query at the document, as on its single page.
	 */
	private function go_to_document() {
		$this->go_to( get_permalink( self::$document_id ) );
		$GLOBALS['post'] = get_post( self::$document_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * The adjacent-post filters accept a bare WHERE clause and fall back to the current post (#732).
	 */
	public function test_suppress_adjacent_doc_with_one_arg() {
		$this->go_to_document();

		$where = apply_filters( 'get_next_post_where', 'WHERE 1=1' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		self::assertSame( 'WHERE 1=1 AND 1 = 0 ', $where, 'The current document should still be suppressed.' );
	}

	/**
	 * A null $in_same_term and a non-string WHERE clause pass through without a TypeError.
	 */
	public function test_suppress_adjacent_doc_with_null_args() {
		global $wpdr;

		$post = get_post( self::$document_id );
		self::assertIsString( $wpdr->suppress_adjacent_doc( 'WHERE 1=1', null, '', 'category', $post ) );
		self::assertNull( $wpdr->suppress_adjacent_doc( null, null, '', 'category', $post ) );
	}

	/**
	 * `transient_rewrite_rules` passes false when the rules aren't cached.
	 */
	public function test_revision_rewrite_passes_through_false() {
		global $wpdr;

		self::assertFalse( $wpdr->revision_rewrite( false ) );
	}

	/**
	 * Callbacks with optional trailing arguments work when called with fewer.
	 */
	public function test_callbacks_with_missing_trailing_args() {
		global $wpdr;

		$this->go_to_document();

		self::assertIsString( $wpdr->empty_excerpt_return( 'Revision note' ) );
		self::assertSame( array(), $wpdr->hide_doc_attach_slug( array() ) );
		self::assertSame( 'http://example.org/file.txt', $wpdr->attachment_url_filter( 'http://example.org/file.txt' ) );
		self::assertSame( 'http://example.org/?attachment_id=1', $wpdr->attachment_link_filter( 'http://example.org/?attachment_id=1' ) );
		self::assertIsString( $wpdr->redirect_canonical_filter( 'http://example.org/x/' ) );
		self::assertSame( get_permalink( self::$document_id ), $wpdr->permalink( 'http://example.org/?p=' . self::$document_id, self::$document_id ), 'A post ID works in place of a WP_Post.' );
	}

	/**
	 * Non-string or non-array values from an earlier callback pass through unchanged.
	 */
	public function test_non_canonical_values_pass_through() {
		global $wpdr;

		$this->go_to_document();

		self::assertSame( '', $wpdr->content_filter( null ), 'Document content is still suppressed.' );
		self::assertNull( $wpdr->add_revision_num_to_title( null, self::$document_id ) );
		self::assertNull( $wpdr->empty_excerpt_return( null, self::$document_id ) );
		self::assertFalse( $wpdr->hide_doc_attach_slug( false, 1, 'create' ) );
		self::assertFalse( $wpdr->attachment_url_filter( false, 1 ) );
		self::assertFalse( $wpdr->attachment_link_filter( false, 'not-an-id' ) );
		self::assertFalse( $wpdr->redirect_canonical_filter( false, true ) );
		self::assertFalse( $wpdr->permalink( false, self::$document_id, false ) );
		self::assertSame( 'http://example.org/', $wpdr->permalink( 'http://example.org/', 0 ) );
		self::assertFalse( $wpdr->admin->network_settings_redirect( false ) );
	}
}
