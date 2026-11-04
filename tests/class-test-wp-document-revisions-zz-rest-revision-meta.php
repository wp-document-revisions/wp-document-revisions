<?php
/**
 * Tests that REST requests for a revision don't rewrite another document's attachment meta.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * REST revision meta tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Rest_Revision_Meta extends Test_Common_WPDR {

	/**
	 * Another user's document with two file versions.
	 *
	 * @var int
	 */
	private static $victim;

	/**
	 * The contributor's own draft document.
	 *
	 * @var int
	 */
	private static $own;

	/**
	 * Saved REST base and namespace of the document post type.
	 *
	 * @var array{0: mixed, 1: mixed}
	 */
	private $rest;

	/**
	 * Create the documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		$wpdr->manage_rest();
		$editor      = $factory->user->create( array( 'role' => 'editor' ) );
		$contributor = $factory->user->create( array( 'role' => 'contributor' ) );

		self::$victim = $factory->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_author'  => $editor,
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$victim, self::$test_file );
		self::add_document_attachment( $factory, self::$victim, self::$test_file2 );

		self::$own = $factory->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'draft',
				'post_author' => $contributor,
			)
		);
	}

	/**
	 * Expose documents at /wp/v2/documents, as document_show_in_rest does.
	 */
	public function set_up() {
		parent::set_up();
		$post_type  = get_post_type_object( 'document' );
		$this->rest = array( $post_type->rest_base, $post_type->rest_namespace );

		$post_type->rest_base      = 'documents';
		$post_type->rest_namespace = 'wp/v2';
	}

	/**
	 * Restore the REST base.
	 */
	public function tear_down() {
		$post_type = get_post_type_object( 'document' );
		list( $post_type->rest_base, $post_type->rest_namespace ) = $this->rest;
		parent::tear_down();
	}

	/**
	 * Run the meta filter on a request.
	 *
	 * @param string $route route.
	 * @param int    $id    the route's id parameter.
	 */
	private function dispatch( string $route, int $id ): void {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_param( 'id', $id );
		WP_Document_Revisions_Manage_Rest::populate_document_meta( null, $request );
	}

	/**
	 * A revision of another document, requested under one's own document, leaves the other
	 * document's attachment meta alone.
	 */
	public function test_revision_route_does_not_rewrite_parent_meta() {
		$current = (int) get_post_meta( self::$victim, '_document_attachment_id', true );
		self::assertGreaterThan( 0, $current );

		$older = null;
		foreach ( wp_get_post_revisions( self::$victim ) as $revision ) {
			// The test helper stores the attachment ID in the old numeric format.
			if ( preg_match( '/^(?:<!-- WPDR )?(\d+)/', $revision->post_content, $m ) && (int) $m[1] !== $current ) {
				$older = $revision->ID;
				break;
			}
		}
		self::assertNotNull( $older, 'needs a revision pointing at an older attachment' );

		$this->dispatch( '/wp/v2/documents/' . self::$own . '/revisions/' . $older, $older );
		$this->dispatch( '/wp/v2/documents/' . self::$own . '/autosaves/' . $older, $older );

		self::assertSame( $current, (int) get_post_meta( self::$victim, '_document_attachment_id', true ) );
	}

	/**
	 * A document's own route still fills in missing attachment meta.
	 */
	public function test_document_route_still_populates_meta() {
		$current = (int) get_post_meta( self::$victim, '_document_attachment_id', true );
		delete_post_meta( self::$victim, '_document_attachment_id' );

		$this->dispatch( '/wp/v2/documents/' . self::$victim, self::$victim );

		self::assertSame( $current, (int) get_post_meta( self::$victim, '_document_attachment_id', true ) );
	}
}
