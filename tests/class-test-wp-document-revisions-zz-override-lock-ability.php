<?php
/**
 * Tests the override-document-lock ability callback (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Override lock ability tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Override_Lock_Ability extends WP_UnitTestCase {

	/**
	 * Editor who owns the document and holds the lock.
	 *
	 * @var int
	 */
	private static $owner;

	/**
	 * Second editor who overrides the lock.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Author who can't edit the document.
	 *
	 * @var int
	 */
	private static $author;

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Create the users and document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$owner  = $factory->user->create( array( 'role' => 'editor' ) );
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$author = $factory->user->create( array( 'role' => 'author' ) );
		self::$doc    = $factory->post->create(
			array(
				'post_title'  => 'Override Lock Doc',
				'post_type'   => 'document',
				'post_status' => 'private',
				'post_author' => self::$owner,
			)
		);
	}

	/**
	 * Don't send override e-mails from these tests.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'send_document_override_notice', '__return_false' );
	}

	/**
	 * Lock the document as the owner.
	 */
	private function lock_as_owner() {
		wp_set_current_user( self::$owner );
		wp_set_post_lock( self::$doc );
	}

	/**
	 * A user who can't edit the document gets 403 even when it isn't locked.
	 */
	public function test_forbidden_before_lock_check() {
		global $wpdr;

		delete_post_meta( self::$doc, '_edit_lock' );
		wp_set_current_user( self::$author );

		$result = $wpdr->ability_override_document_lock( array( 'document_id' => self::$doc ) );

		self::assertWPError( $result );
		self::assertSame( 'document_forbidden', $result->get_error_code() );
	}

	/**
	 * Overriding takes the lock and fires document_lock_override, like the editor button.
	 */
	public function test_override_takes_lock_and_fires_action() {
		global $wpdr;

		$this->lock_as_owner();
		wp_set_current_user( self::$editor );

		$fired  = array();
		$action = function ( ...$args ) use ( &$fired ) {
			$fired = $args;
		};
		add_action( 'document_lock_override', $action, 10, 3 );
		$result = $wpdr->ability_override_document_lock( array( 'document_id' => self::$doc ) );
		remove_action( 'document_lock_override', $action, 10 );

		self::assertIsArray( $result );
		self::assertTrue( $result['success'] );
		self::assertNotEmpty( $result['previous_lock'] );
		self::assertSame( array( self::$doc, self::$editor, self::$owner ), $fired );

		$lock = explode( ':', (string) get_post_meta( self::$doc, '_edit_lock', true ) );
		self::assertSame( (string) self::$editor, $lock[1], 'The overriding user now holds the lock.' );
	}

	/**
	 * An unlocked document reports success with no previous lock.
	 */
	public function test_unlocked_document() {
		global $wpdr;

		delete_post_meta( self::$doc, '_edit_lock' );
		wp_set_current_user( self::$editor );

		$result = $wpdr->ability_override_document_lock( array( 'document_id' => self::$doc ) );

		self::assertSame(
			array(
				'success'       => true,
				'previous_lock' => null,
			),
			$result
		);
	}
}
