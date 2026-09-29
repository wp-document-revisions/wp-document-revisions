<?php
/**
 * Tests handing document downloads to the web server (X-Sendfile and friends) (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Sendfile tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Serve_Sendfile extends Test_Common_WPDR {

	/**
	 * Published document with a file.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create the document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$doc    = $factory->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_author'  => self::$editor,
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$doc, self::$test_file );
	}

	/**
	 * Remove test filters.
	 */
	public function tear_down() {
		remove_all_filters( 'document_serve_sendfile_header' );
		remove_all_filters( 'document_serve_sendfile_path' );
		parent::tear_down();
	}

	/**
	 * Request the document, returning the headers the plugin sent and the body.
	 *
	 * @return array{0: array<string, string>, 1: string}
	 */
	private function request_document() {
		global $wpdr;

		wp_set_current_user( self::$editor );
		$this->go_to( get_permalink( self::$doc ) );

		$sent    = array();
		$capture = function ( $headers ) use ( &$sent ) {
			$sent = $headers;
			return $headers;
		};
		// Before the test bootstrap's filter that drops headers under PHPUnit.
		add_filter( 'document_revisions_serve_file_headers', $capture, 5 );

		ob_start();
		$wpdr->serve_file( '' );
		$body = (string) ob_get_clean();

		remove_filter( 'document_revisions_serve_file_headers', $capture, 5 );

		return array( $sent, $body );
	}

	/**
	 * X-Sendfile gets the file path and no body is streamed.
	 */
	public function test_x_sendfile() {
		add_filter(
			'document_serve_sendfile_header',
			function () {
				return 'X-Sendfile';
			}
		);

		list( $headers, $body ) = $this->request_document();

		self::assertArrayHasKey( 'X-Sendfile', $headers );
		self::assertFileExists( $headers['X-Sendfile'] );
		self::assertArrayNotHasKey( 'Content-Length', $headers );
		self::assertArrayHasKey( 'Content-Disposition', $headers );
		self::assertSame( '', $body );
	}

	/**
	 * X-Accel-Redirect needs a mapped internal URI; without one the file is streamed as usual.
	 */
	public function test_x_accel_redirect_needs_a_uri() {
		add_filter(
			'document_serve_sendfile_header',
			function () {
				return 'X-Accel-Redirect';
			}
		);

		list( $headers, $body ) = $this->request_document();
		self::assertArrayNotHasKey( 'X-Accel-Redirect', $headers );
		self::assertNotSame( '', $body, 'Streamed through PHP.' );

		add_filter(
			'document_serve_sendfile_path',
			function ( $value, $file ) {
				return '/protected-documents/' . basename( $file ) . "\r\nX-Evil: 1";
			},
			10,
			2
		);
		list( $headers, $body ) = $this->request_document();
		self::assertStringStartsWith( '/protected-documents/', $headers['X-Accel-Redirect'] );
		self::assertStringNotContainsString( "\n", $headers['X-Accel-Redirect'] );
		self::assertSame( '', $body );
	}

	/**
	 * Unknown header names and the default serve through PHP.
	 */
	public function test_default_and_unknown_header() {
		list( $headers, $body ) = $this->request_document();
		self::assertArrayNotHasKey( 'X-Sendfile', $headers );
		self::assertNotSame( '', $body );

		add_filter(
			'document_serve_sendfile_header',
			function () {
				return 'X-Anything';
			}
		);
		list( $headers ) = $this->request_document();
		self::assertArrayNotHasKey( 'X-Anything', $headers );
	}
}
