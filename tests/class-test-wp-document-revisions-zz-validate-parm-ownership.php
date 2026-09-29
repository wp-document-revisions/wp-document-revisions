<?php
/**
 * Tests that Validate Structure fixes only touch the document's own attachments.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Validate Structure parm ownership tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Validate_Parm_Ownership extends Test_Common_WPDR {

	/**
	 * Contributor user id.
	 *
	 * @var int
	 */
	private static $contributor;

	/**
	 * Editor user id.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create users.
	 *
	 * @param WP_UnitTest_Factory $factory factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$contributor = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$editor      = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Build a correct request.
	 *
	 * @param int $id   document id.
	 * @param int $code fix code.
	 * @param int $parm parameter.
	 * @return WP_REST_Request
	 */
	private function request( $id, $code, $parm ) {
		$request = new WP_REST_Request( 'PUT', '/wpdr/v1/correct/' . $id . '/type/' . $code . '/attach/' . $parm );
		$request->set_param( 'id', $id );
		$request->set_param( 'code', $code );
		$request->set_param( 'parm', $parm );
		return $request;
	}

	/**
	 * A contributor's own draft document.
	 *
	 * @return int
	 */
	private function own_draft() {
		return self::factory()->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'draft',
				'post_author' => self::$contributor,
			)
		);
	}

	/**
	 * Code 6 must not rename a post that isn't the document's attachment.
	 */
	public function test_code_6_rejects_foreign_post() {
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Home',
				'post_name'   => 'home',
				'post_author' => self::$editor,
			)
		);
		wp_set_current_user( self::$contributor );
		$doc = $this->own_draft();

		$request = $this->request( $doc, 6, $page );
		$result  = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		self::assertWPError( $result );
		self::assertSame( 'Home', get_post_field( 'post_title', $page, 'raw' ) );
		self::assertSame( 'home', get_post_field( 'post_name', $page, 'raw' ) );
	}

	/**
	 * Code 6 must not rename or delete another post's media file.
	 */
	public function test_code_6_rejects_foreign_media() {
		$upload = wp_upload_bits( 'logo-' . wp_rand() . '.txt', null, 'logo' );
		self::assertEmpty( $upload['error'] );
		$media = self::factory()->attachment->create_object(
			$upload['file'],
			0,
			array(
				'post_mime_type' => 'text/plain',
				'post_author'    => self::$editor,
			)
		);
		$rel   = get_post_meta( $media, '_wp_attached_file', true );

		wp_set_current_user( self::$contributor );
		$doc = $this->own_draft();

		$request  = $this->request( $doc, 6, $media );
		$validate = new WP_Document_Revisions_Validate_Structure();
		self::assertTrue( $validate->check_permission( $request ), 'contributor reaches the route' );

		$result = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		self::assertWPError( $result );
		self::assertFileExists( $upload['file'] );
		self::assertSame( $rel, get_post_meta( $media, '_wp_attached_file', true ) );

		wp_delete_file( $upload['file'] );
	}

	/**
	 * Code 7 must not move another document's attachment.
	 */
	public function test_code_7_rejects_foreign_attachment() {
		$other  = self::factory()->post->create(
			array(
				'post_type'   => 'document',
				'post_author' => self::$editor,
			)
		);
		$attach = self::factory()->attachment->create( array( 'post_parent' => $other ) );

		wp_set_current_user( self::$contributor );
		$doc = $this->own_draft();

		$result = WP_Document_Revisions_Validate_Structure::correct_document( $this->request( $doc, 7, $attach ) );

		self::assertWPError( $result );
	}

	/**
	 * The route only works on documents.
	 */
	public function test_permission_requires_document() {
		wp_set_current_user( self::$contributor );
		$post = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => self::$contributor,
			)
		);

		$validate = new WP_Document_Revisions_Validate_Structure();
		self::assertFalse( $validate->check_permission( $this->request( $post, 10, $post ) ) );
		self::assertTrue( $validate->check_permission( $this->request( $this->own_draft(), 10, 1 ) ) );
	}
}
