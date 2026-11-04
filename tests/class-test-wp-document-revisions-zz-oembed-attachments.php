<?php
/**
 * Tests that oEmbed and titles don't reveal a document attachment's stored file name.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document attachment oEmbed tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Oembed_Attachments extends Test_Common_WPDR {

	/**
	 * Published, password-protected document.
	 *
	 * @var int
	 */
	private static $protected_doc;

	/**
	 * Published document with two file versions.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Attachment of the password-protected document.
	 *
	 * @var int
	 */
	private static $protected_attach;

	/**
	 * Attachment of the published document's first (older) version.
	 *
	 * @var int
	 */
	private static $old_attach;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create the documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );

		self::$protected_doc = $factory->post->create(
			array(
				'post_type'     => 'document',
				'post_status'   => 'publish',
				'post_author'   => self::$editor,
				'post_title'    => 'Protected Document',
				'post_password' => 'secret',
				'post_content'  => '',
			)
		);
		self::add_document_attachment( $factory, self::$protected_doc, self::$test_file );
		self::$protected_attach = self::attachments( self::$protected_doc )[0];

		self::$doc = $factory->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_author'  => self::$editor,
				'post_title'   => 'Public Document',
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$doc, self::$test_file );
		self::add_document_attachment( $factory, self::$doc, self::$test_file2 );
		self::$old_attach = self::attachments( self::$doc )[0];
	}

	/**
	 * A document's attachment IDs, oldest first.
	 *
	 * @param int $doc document ID.
	 * @return int[]
	 */
	private static function attachments( int $doc ): array {
		return get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'post_parent' => $doc,
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'fields'      => 'ids',
				'numberposts' => -1,
			)
		);
	}

	/**
	 * Request the oEmbed data for a post as the given user.
	 *
	 * @param int $post_id post to embed.
	 * @param int $user    user to request as.
	 * @return WP_REST_Response
	 */
	private function oembed( int $post_id, int $user = 0 ): WP_REST_Response {
		wp_set_current_user( $user );
		$request = new WP_REST_Request( 'GET', '/oembed/1.0/embed' );
		$request->set_param( 'url', home_url( '/?p=' . $post_id ) );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * A document's attachments, including a password-protected document's and an older
	 * revision's, aren't embeddable, so their stored file names aren't returned.
	 */
	public function test_document_attachments_are_not_embeddable() {
		foreach ( array( self::$protected_attach, self::$old_attach ) as $attach ) {
			$response = $this->oembed( $attach );
			self::assertSame( 404, $response->get_status(), 'attachment ' . $attach );
			self::assertStringNotContainsString( get_post_field( 'post_title', $attach ), wp_json_encode( $response->get_data() ) );
		}
	}

	/**
	 * The document itself still embeds.
	 */
	public function test_document_is_still_embeddable() {
		$response = $this->oembed( self::$doc );
		self::assertSame( 200, $response->get_status() );
		self::assertSame( 'Public Document', $response->get_data()['title'] );
	}

	/**
	 * Someone who can't edit the document sees the document's title, not the stored file name.
	 */
	public function test_attachment_title_is_masked_for_readers() {
		wp_set_current_user( 0 );
		self::assertSame( 'Protected Document', get_the_title( self::$protected_attach ) );
		self::assertSame( 'Public Document', get_the_title( self::$old_attach ) );
	}

	/**
	 * Someone who can edit the document still sees the attachment's own title.
	 */
	public function test_attachment_title_is_shown_to_editors() {
		wp_set_current_user( self::$editor );
		self::assertSame( get_post_field( 'post_title', self::$old_attach ), get_the_title( self::$old_attach ) );
	}
}
