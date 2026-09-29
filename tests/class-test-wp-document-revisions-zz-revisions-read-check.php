<?php
/**
 * Tests that revision lists check the viewer can read the document.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Revisions shortcode/block read-check tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Revisions_Read_Check extends WP_UnitTestCase {

	/**
	 * Document IDs keyed by kind.
	 *
	 * @var array<string, int>
	 */
	private static $docs = array();

	/**
	 * Contributor who can't read the private documents.
	 *
	 * @var int
	 */
	private static $contributor;

	/**
	 * Create the users and documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		$editor            = $factory->user->create( array( 'role' => 'editor' ) );
		self::$contributor = $factory->user->create( array( 'role' => 'contributor' ) );

		$kinds = array(
			'public'   => array( 'post_status' => 'publish' ),
			'private'  => array( 'post_status' => 'private' ),
			'draft'    => array( 'post_status' => 'draft' ),
			'password' => array(
				'post_status'   => 'publish',
				'post_password' => 'secret',
			),
		);
		foreach ( $kinds as $key => $args ) {
			self::$docs[ $key ] = $factory->post->create(
				array_merge(
					$args,
					array(
						'post_type'   => 'document',
						'post_title'  => 'Secret title ' . $key,
						'post_author' => $editor,
					)
				)
			);
		}
	}

	/**
	 * Reset caches between tests.
	 */
	public function set_up() {
		parent::set_up();
		wp_cache_flush();
		wp_set_current_user( self::$contributor );
	}

	/**
	 * Data provider: documents the contributor can't read.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function unreadable() {
		return array(
			'private'  => array( 'private' ),
			'draft'    => array( 'draft' ),
			'password' => array( 'password' ),
		);
	}

	/**
	 * The shortcode refuses documents the user can't read.
	 *
	 * @dataProvider unreadable
	 * @param string $key document kind.
	 */
	public function test_shortcode_denied( $key ) {
		$output = do_shortcode( '[document_revisions id="' . self::$docs[ $key ] . '"]' );
		self::assertStringContainsString( 'not authorized', $output );
		self::assertStringNotContainsString( 'class="revision', $output );
	}

	/**
	 * The block refuses documents the user can't read, including the title.
	 *
	 * @dataProvider unreadable
	 * @param string $key document kind.
	 */
	public function test_block_denied( $key ) {
		global $wpdr_fe;
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( array( 'id' => self::$docs[ $key ] ) );
		self::assertStringNotContainsString( 'Secret title', $output );
		self::assertStringNotContainsString( 'class="revision', $output );
	}

	/**
	 * Readable documents still list their revisions.
	 */
	public function test_readable_document() {
		global $wpdr_fe;
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( array( 'id' => self::$docs['public'] ) );
		self::assertStringContainsString( 'Secret title public', $output );
		self::assertStringContainsString( 'class="revision', $output );
	}
}
