<?php
/**
 * Tests the error shown when PHP can't read a document's file.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Unreadable-file serve tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Serve_Unreadable extends Test_Common_WPDR {

	/**
	 * Published document with a file.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Editor user ID (can use Validate Structure).
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Subscriber user ID (can read but not edit documents).
	 *
	 * @var int
	 */
	private static $subscriber;

	/**
	 * Path of the document's file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Create the document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor     = $factory->user->create( array( 'role' => 'editor' ) );
		self::$subscriber = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$doc        = $factory->post->create(
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
	 * Make the document's file unreadable, or skip where permissions can't do that (running as root).
	 */
	public function set_up() {
		global $wpdr;
		parent::set_up();

		$attach     = $wpdr->get_document( self::$doc );
		$this->file = (string) get_attached_file( $attach->ID );
		self::assertFileExists( $this->file );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		chmod( $this->file, 0000 );
		clearstatcache();
		if ( is_readable( $this->file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			chmod( $this->file, 0644 );
			$this->markTestSkipped( 'File permissions are not enforced for this user (probably root).' );
		}
	}

	/**
	 * Restore the file's permissions so later tests can read it.
	 */
	public function tear_down() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		chmod( $this->file, 0644 );
		clearstatcache();
		parent::tear_down();
	}

	/**
	 * Request the document as a user and return the wp_die() exception.
	 *
	 * @param int $user_id User to request as.
	 * @return WPDieException
	 */
	private function request_as( $user_id ) {
		global $wpdr;

		wp_set_current_user( $user_id );
		// Plain URL so the request doesn't depend on the permalink structure.
		$this->go_to( home_url( '?p=' . self::$doc . '&post_type=document' ) );

		// Keep the expected error_log() line out of the test output.
		$log = ini_set( 'error_log', '/dev/null' ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		try {
			ob_start();
			$wpdr->serve_file( '' );
			ob_end_clean();
		} catch ( WPDieException $e ) {
			ob_end_clean();
			return $e;
		} finally {
			ini_set( 'error_log', (string) $log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		}

		self::fail( 'An unreadable file should end in wp_die(), not a blank response.' );
	}

	/**
	 * Someone who can fix it gets a hint pointing at file permissions and Validate Structure.
	 */
	public function test_editor_gets_permission_hint() {
		$e = $this->request_as( self::$editor );

		self::assertStringContainsString( 'file-permission problem', $e->getMessage() );
		self::assertStringContainsString( 'Validate Structure', $e->getMessage() );
		self::assertStringNotContainsString( $this->file, $e->getMessage(), 'The server path is never shown.' );
	}

	/**
	 * Everyone else gets a generic message without server details.
	 */
	public function test_reader_gets_generic_message() {
		$e = $this->request_as( self::$subscriber );

		self::assertStringContainsString( 'temporarily unavailable', $e->getMessage() );
		self::assertStringNotContainsString( 'permission', $e->getMessage() );
		self::assertStringNotContainsString( $this->file, $e->getMessage() );
	}
}
