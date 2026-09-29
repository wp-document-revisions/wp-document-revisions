<?php
/**
 * Tests that post_status queries including documents check permissions (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Tests for retrieve_documents().
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Retrieve_Documents extends WP_UnitTestCase {

	/**
	 * Another editor's private document.
	 *
	 * @var int
	 */
	private static $private_doc;

	/**
	 * Author who can't read it.
	 *
	 * @var int
	 */
	private static $author;

	/**
	 * Create the users and document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		$editor            = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author      = $factory->user->create( array( 'role' => 'author' ) );
		self::$private_doc = $factory->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'private',
				'post_author' => $editor,
			)
		);
	}

	/**
	 * Run a query as a user and return the IDs.
	 *
	 * @param int                  $user_id user.
	 * @param array<string, mixed> $args    WP_Query args.
	 * @return int[]
	 */
	private function query_as( $user_id, array $args ) {
		wp_set_current_user( $user_id );
		$query = new WP_Query( array_merge( $args, array( 'fields' => 'ids' ) ) );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * Data provider: post_type values that include documents.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function post_types() {
		return array(
			'document only' => array( 'document' ),
			'array'         => array( array( 'post', 'document' ) ),
			'any post type' => array( 'any' ),
		);
	}

	/**
	 * Another user's private document isn't returned to someone who can't read it.
	 *
	 * @dataProvider post_types
	 * @param mixed $post_type post_type query var.
	 */
	public function test_private_document_hidden( $post_type ) {
		$ids = $this->query_as(
			self::$author,
			array(
				'post_type'   => $post_type,
				'post_status' => array( 'publish', 'private' ),
			)
		);

		self::assertNotContains( self::$private_doc, $ids );
	}

	/**
	 * Users who can read private documents still see it.
	 */
	public function test_private_document_visible_to_admin() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		$ids = $this->query_as(
			$admin,
			array(
				'post_type'   => array( 'post', 'document' ),
				'post_status' => array( 'publish', 'private' ),
			)
		);

		self::assertContains( self::$private_doc, $ids );
	}

	/**
	 * Authors still see their own private documents in a mixed query.
	 */
	public function test_own_private_document_visible() {
		$own = self::factory()->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'private',
				'post_author' => self::$author,
			)
		);

		$ids = $this->query_as(
			self::$author,
			array(
				'post_type'   => array( 'post', 'document' ),
				'post_status' => array( 'publish', 'private' ),
			)
		);

		self::assertContains( $own, $ids );
	}
}
