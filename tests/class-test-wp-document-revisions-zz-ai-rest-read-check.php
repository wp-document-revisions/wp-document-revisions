<?php
/**
 * Tests that the AI summary and diff routes check access like a download.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * AI summary/diff read-check tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_AI_REST_Read_Check extends WP_UnitTestCase {

	/**
	 * Published document: [ doc, old attachment, current attachment ].
	 *
	 * @var int[]
	 */
	private static $open = array();

	/**
	 * Password-protected document: [ doc, old attachment, current attachment ].
	 *
	 * @var int[]
	 */
	private static $protected = array();

	/**
	 * Subscriber (no read_document_revisions).
	 *
	 * @var int
	 */
	private static $subscriber;

	/**
	 * Contributor (has read_document_revisions).
	 *
	 * @var int
	 */
	private static $contributor;

	/**
	 * Create a published document with two attachments.
	 *
	 * @param WP_UnitTest_Factory $factory  Test factory.
	 * @param string              $password post password.
	 * @return int[]
	 */
	private static function document( WP_UnitTest_Factory $factory, $password ) {
		global $wpdr, $wpdb;
		$doc     = $factory->post->create(
			array(
				'post_type'     => 'document',
				'post_status'   => 'publish',
				'post_password' => $password,
			)
		);
		$old     = $factory->attachment->create( array( 'post_parent' => $doc ) );
		$current = $factory->attachment->create( array( 'post_parent' => $doc ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, array( 'post_content' => $wpdr->format_doc_id( $current ) ), array( 'ID' => $doc ) );
		update_post_meta( $doc, '_document_attachment_id', $current );
		clean_post_cache( $doc );
		return array( $doc, $old, $current );
	}

	/**
	 * Create the users and documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$subscriber  = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$contributor = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$open        = self::document( $factory, '' );
		self::$protected   = self::document( $factory, 'secret' );
	}

	/**
	 * Build a request with the route's path params.
	 *
	 * @param int $doc_id document ID.
	 * @param int $rev_id attachment ID.
	 * @return WP_REST_Request
	 */
	private function request( $doc_id, $rev_id ) {
		$request = new WP_REST_Request( 'GET', '/wpdr/v1/documents/' . $doc_id . '/revisions/' . $rev_id . '/summary' );
		$request->set_param( 'doc_id', $doc_id );
		$request->set_param( 'rev_id', $rev_id );
		return $request;
	}

	/**
	 * A summary of the current file is readable; older ones need read_document_revisions.
	 */
	public function test_summary_old_revision_needs_revisions_cap() {
		wp_set_current_user( self::$subscriber );
		self::assertTrue( WP_Document_Revisions_AI_Summary_REST::can_read_summary( $this->request( self::$open[0], self::$open[2] ) ) );
		self::assertWPError( WP_Document_Revisions_AI_Summary_REST::can_read_summary( $this->request( self::$open[0], self::$open[1] ) ) );

		wp_set_current_user( self::$contributor );
		self::assertTrue( WP_Document_Revisions_AI_Summary_REST::can_read_summary( $this->request( self::$open[0], self::$open[1] ) ) );
	}

	/**
	 * Password-protected documents' summaries and diffs aren't served.
	 */
	public function test_password_protected() {
		wp_set_current_user( self::$contributor );
		self::assertWPError( WP_Document_Revisions_AI_Summary_REST::can_read_summary( $this->request( self::$protected[0], self::$protected[2] ) ) );
		self::assertWPError( WP_Document_Revisions_AI_Summary_REST::can_read_diff( $this->request( self::$protected[0], self::$protected[2] ) ) );
		self::assertTrue( WP_Document_Revisions_AI_Summary_REST::can_read_diff( $this->request( self::$open[0], self::$open[2] ) ) );
	}

	/**
	 * The revisions ability refuses password-protected documents.
	 */
	public function test_ability_password_protected() {
		global $wpdr;
		wp_set_current_user( self::$contributor );
		self::assertWPError( $wpdr->ability_get_document_revisions( array( 'document_id' => self::$protected[0] ) ) );
		self::assertIsArray( $wpdr->ability_get_document_revisions( array( 'document_id' => self::$open[0] ) ) );
	}
}
