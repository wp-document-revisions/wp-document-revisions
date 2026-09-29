<?php
/**
 * Tests the document_serve_redirect_url filter and get_raw_attachment_url() (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Serve redirect tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Serve_Redirect extends Test_Common_WPDR {

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
	 * Request the document as the editor, capturing any redirect instead of sending it.
	 *
	 * @param callable|null $redirect_filter document_serve_redirect_url callback.
	 * @return array{0: string|null, 1: int|null, 2: string} redirect location, status and output.
	 */
	private function request_document( $redirect_filter = null ) {
		global $wpdr;

		wp_set_current_user( self::$editor );
		$this->go_to( get_permalink( self::$doc ) );

		$location = null;
		$status   = null;
		$capture  = function ( $loc, $code ) use ( &$location, &$status ) {
			$location = $loc;
			$status   = $code;
			return false; // Don't send the header.
		};
		add_filter( 'wp_redirect', $capture, 10, 2 );
		if ( $redirect_filter ) {
			add_filter( 'document_serve_redirect_url', $redirect_filter, 10, 4 );
		}

		ob_start();
		$wpdr->serve_file( '' );
		$output = (string) ob_get_clean();

		remove_filter( 'wp_redirect', $capture, 10 );
		if ( $redirect_filter ) {
			remove_filter( 'document_serve_redirect_url', $redirect_filter, 10 );
		}

		return array( $location, $status, $output );
	}

	/**
	 * A redirect URL from the filter is used instead of streaming the file.
	 */
	public function test_redirect_url_filter() {
		$args   = array();
		$filter = function ( $url, $post, $attach, $file ) use ( &$args ) {
			$args = array( $url, $post->ID, $attach->post_parent, is_string( $file ) );
			return 'https://cdn.example.com/signed/file.txt?sig=abc';
		};

		list( $location, $status, $output ) = $this->request_document( $filter );

		self::assertSame( 'https://cdn.example.com/signed/file.txt?sig=abc', $location );
		self::assertSame( 302, $status );
		self::assertSame( '', $output, 'The file is not streamed.' );
		self::assertSame( array( '', self::$doc, self::$doc, true ), $args );
	}

	/**
	 * Without the filter the file is streamed as before.
	 */
	public function test_no_redirect_by_default() {
		list( $location, , $output ) = $this->request_document();

		self::assertNull( $location );
		self::assertNotSame( '', $output );
	}

	/**
	 * The raw attachment URL bypasses the permalink filter, which stays in place.
	 */
	public function test_get_raw_attachment_url() {
		global $wpdr;

		$attach = $wpdr->get_document( self::$doc );
		$raw    = $wpdr->get_raw_attachment_url( $attach->ID );

		self::assertStringContainsString( '/wp-content/uploads/', (string) $raw );
		self::assertNotSame( $raw, wp_get_attachment_url( $attach->ID ), 'The public URL is still the permalink.' );
		self::assertSame( 10, has_filter( 'wp_get_attachment_url', array( $wpdr, 'attachment_url_filter' ) ) );
	}
}
