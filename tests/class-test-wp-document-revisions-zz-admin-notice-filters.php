<?php
/**
 * Tests the filters that turn off the empty-state and review notices (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Admin notice filter tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Admin_Notice_Filters extends WP_UnitTestCase {

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create an editor.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Show the documents list screen as the editor.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$editor );
		set_current_screen( 'edit-document' );
		get_current_screen()->id = 'edit-document';
	}

	/**
	 * Reset the screen.
	 */
	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Render an admin notice callback.
	 *
	 * @param string $method admin method name.
	 * @return string
	 */
	private function capture( $method ) {
		global $wpdr;
		ob_start();
		$wpdr->admin->$method();
		return (string) ob_get_clean();
	}

	/**
	 * Set the number of documents.
	 *
	 * @param int $count number of documents.
	 */
	private function set_document_count( $count ) {
		$docs = get_posts(
			array(
				'post_type'   => 'document',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $docs as $doc_id ) {
			wp_delete_post( $doc_id, true );
		}
		for ( $i = 0; $i < $count; $i++ ) {
			self::factory()->post->create(
				array(
					'post_type'   => 'document',
					'post_status' => 'publish',
					'post_author' => self::$editor,
				)
			);
		}
		wp_cache_flush();
	}

	/**
	 * The empty-state notice can be turned off.
	 */
	public function test_empty_state_filter() {
		$this->set_document_count( 0 );
		self::assertNotSame( '', $this->capture( 'empty_state_notice' ), 'Shown by default.' );

		add_filter( 'document_show_empty_state', '__return_false' );
		$output = $this->capture( 'empty_state_notice' );
		remove_filter( 'document_show_empty_state', '__return_false' );

		self::assertSame( '', $output );
	}

	/**
	 * The review prompt can be turned off, and the filter receives the user ID.
	 */
	public function test_review_prompt_filter() {
		$this->set_document_count( WP_Document_Revisions_Admin::REVIEW_MIN_DOCS );
		self::assertNotSame( '', $this->capture( 'review_prompt' ), 'Shown by default.' );

		$seen   = null;
		$filter = function ( $show, $user_id ) use ( &$seen ) {
			$seen = $user_id;
			return false;
		};
		add_filter( 'document_show_review_prompt', $filter, 10, 2 );
		$output = $this->capture( 'review_prompt' );
		remove_filter( 'document_show_review_prompt', $filter, 10 );

		self::assertSame( '', $output );
		self::assertSame( self::$editor, $seen );
	}
}
