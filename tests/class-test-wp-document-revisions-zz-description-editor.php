<?php
/**
 * Tests the filter that hides the document description editor (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Description editor filter tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Description_Editor extends WP_UnitTestCase {

	/**
	 * Re-register the post type with the default settings.
	 */
	public function tear_down() {
		global $wpdr;
		remove_filter( 'document_show_description_editor', '__return_false' );
		remove_filter( 'document_use_block_editor', '__return_true' );
		$wpdr->register_cpt();
		parent::tear_down();
	}

	/**
	 * Render the heading hook for a document.
	 *
	 * @return string
	 */
	private function heading() {
		global $wpdr;
		$doc = self::factory()->post->create_and_get( array( 'post_type' => 'document' ) );
		ob_start();
		$wpdr->admin->prepare_editor( $doc );
		return (string) ob_get_clean();
	}

	/**
	 * By default the editor and its heading are shown.
	 */
	public function test_default_shows_editor() {
		global $wpdr;
		$wpdr->register_cpt();

		self::assertTrue( post_type_supports( 'document', 'editor' ) );
		self::assertStringContainsString( 'Document Description', $this->heading() );
	}

	/**
	 * The filter removes editor support and the heading.
	 */
	public function test_filter_hides_editor() {
		global $wpdr;
		add_filter( 'document_show_description_editor', '__return_false' );
		// Supports from an earlier registration persist; in a request the type is registered once.
		unregister_post_type( 'document' );
		$wpdr->register_cpt();

		self::assertFalse( post_type_supports( 'document', 'editor' ) );
		self::assertSame( '', $this->heading() );
	}

	/**
	 * Block editor mode keeps editor support, which the block editor needs.
	 */
	public function test_block_editor_mode_keeps_editor() {
		global $wpdr;
		add_filter( 'document_show_description_editor', '__return_false' );
		add_filter( 'document_use_block_editor', '__return_true' );
		$wpdr->register_cpt();

		self::assertTrue( post_type_supports( 'document', 'editor' ) );
	}
}
