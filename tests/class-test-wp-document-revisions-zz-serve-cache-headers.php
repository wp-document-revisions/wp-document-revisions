<?php
/**
 * Tests the caching headers sent with served documents.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Served document cache header tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Serve_Cache_Headers extends Test_Common_WPDR {

	/**
	 * Cache-Control sent for documents that aren't public.
	 */
	const PRIVATE_CACHE = 'private, no-cache, no-store, max-age=0';

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Published document.
	 *
	 * @var int
	 */
	private static $published;

	/**
	 * Draft document.
	 *
	 * @var int
	 */
	private static $draft;

	/**
	 * Private document.
	 *
	 * @var int
	 */
	private static $private;

	/**
	 * Password-protected published document.
	 *
	 * @var int
	 */
	private static $password;

	/**
	 * Create the documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );

		$docs = array(
			'published' => array( 'post_status' => 'publish' ),
			'draft'     => array( 'post_status' => 'draft' ),
			'private'   => array( 'post_status' => 'private' ),
			'password'  => array(
				'post_status'   => 'publish',
				'post_password' => 'secret',
			),
		);
		foreach ( $docs as $prop => $args ) {
			self::$$prop = $factory->post->create(
				array_merge(
					array(
						'post_type'    => 'document',
						'post_author'  => self::$editor,
						'post_content' => '',
					),
					$args
				)
			);
			self::add_document_attachment( $factory, self::$$prop, self::$test_file );
		}
	}

	/**
	 * Remove test filters and request state.
	 */
	public function tear_down() {
		remove_all_filters( 'post_password_required' );
		remove_filter( 'document_read_uses_read', '__return_false' );
		unset( $_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		parent::tear_down();
	}

	/**
	 * Request a document, returning the headers the plugin sent.
	 *
	 * @param int      $doc_id   document to request.
	 * @param int      $user_id  user making the request.
	 * @param int|null $revision revision number to request, if any.
	 * @return array<string, string>
	 */
	private function request_headers( int $doc_id, int $user_id, ?int $revision = null ): array {
		global $wpdr;

		wp_set_current_user( $user_id );
		// Plain URL so the request doesn't depend on the permalink structure.
		$url = home_url( '?p=' . $doc_id . '&post_type=document' );
		if ( null !== $revision ) {
			$url .= '&revision=' . $revision;
		}
		$this->go_to( $url );

		$sent    = array();
		$capture = function ( $headers ) use ( &$sent ) {
			$sent = $headers;
			return $headers;
		};
		// Before the test bootstrap's filter that drops headers under PHPUnit.
		add_filter( 'document_revisions_serve_file_headers', $capture, 5 );

		ob_start();
		$wpdr->serve_file( '' );
		ob_end_clean();

		remove_filter( 'document_revisions_serve_file_headers', $capture, 5 );

		self::assertNotEmpty( $sent, 'Document was not served.' );

		return $sent;
	}

	/**
	 * Assert headers mark the response as not storable by shared caches.
	 *
	 * @param array<string, string> $headers headers sent.
	 */
	private static function assert_private( array $headers ) {
		self::assertSame( self::PRIVATE_CACHE, $headers['Cache-Control'] ?? null );
		self::assertSame( 'no-cache', $headers['Pragma'] ?? null );
		self::assertArrayHasKey( 'Expires', $headers );
		self::assertLessThan( time(), strtotime( $headers['Expires'] ) );
		self::assertSame( 'nosniff', $headers['X-Content-Type-Options'] ?? null );
	}

	/**
	 * A published document served to anyone keeps the existing caching headers.
	 */
	public function test_published_document_unchanged() {
		$headers = $this->request_headers( self::$published, 0 );
		self::assertSame( 'no-cache', $headers['Cache-Control'] ?? null );
		self::assertArrayNotHasKey( 'Pragma', $headers );
		self::assertArrayNotHasKey( 'Expires', $headers );
		self::assertSame( 'nosniff', $headers['X-Content-Type-Options'] ?? null );

		$headers = $this->request_headers( self::$published, self::$editor );
		self::assertSame( 'no-cache', $headers['Cache-Control'] ?? null );
		self::assertSame( 'nosniff', $headers['X-Content-Type-Options'] ?? null );
	}

	/**
	 * A revision of a published document isn't public.
	 */
	public function test_revision_is_private() {
		global $wpdr;
		$revision = count( $wpdr->get_revision_indices( self::$published ) );
		self::assertGreaterThan( 0, $revision );
		self::assert_private( $this->request_headers( self::$published, self::$editor, $revision ) );
	}

	/**
	 * A draft document isn't public.
	 */
	public function test_draft_is_private() {
		self::assert_private( $this->request_headers( self::$draft, self::$editor ) );
	}

	/**
	 * A private document isn't public.
	 */
	public function test_private_status_is_private() {
		self::assert_private( $this->request_headers( self::$private, self::$editor ) );
	}

	/**
	 * A password-protected document served after the password was given isn't public.
	 */
	public function test_password_protected_is_private() {
		add_filter( 'post_password_required', '__return_false' );
		self::assert_private( $this->request_headers( self::$password, 0 ) );
	}

	/**
	 * A published document isn't public when anonymous users can't read documents.
	 */
	public function test_published_is_private_when_read_needs_capability() {
		add_filter( 'document_read_uses_read', '__return_false' );
		self::assert_private( $this->request_headers( self::$published, self::$editor ) );
	}

	/**
	 * A 304 Not Modified response carries the same headers.
	 */
	public function test_not_modified_keeps_headers() {
		$headers = $this->request_headers( self::$draft, self::$editor );

		$_SERVER['HTTP_IF_NONE_MATCH'] = $headers['ETag'];
		$headers                       = $this->request_headers( self::$draft, self::$editor );
		self::assertArrayNotHasKey( 'Content-Length', $headers, 'Not a 304 response.' );
		self::assert_private( $headers );
	}
}
