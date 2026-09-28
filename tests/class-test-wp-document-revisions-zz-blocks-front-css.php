<?php
/**
 * Tests the block registration filter and on-demand front-end CSS (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Blocks filter and front-end CSS tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Blocks_Front_Css extends Test_Common_WPDR {

	/**
	 * The plugin's block names.
	 *
	 * @var string[]
	 */
	private static $blocks = array(
		'wp-document-revisions/documents-shortcode',
		'wp-document-revisions/revisions-shortcode',
		'wp-document-revisions/document-preview',
		'wp-document-revisions/documents-widget',
	);

	/**
	 * Skip the block taxonomy lookup. Earlier test classes simulate EditFlow/PublishPress
	 * and leave taxonomy_key() pointing at a taxonomy that is no longer registered.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'document_block_taxonomies', '__return_empty_array' );
	}

	/**
	 * Reset the stylesheet queue.
	 */
	public function tear_down() {
		remove_filter( 'document_block_taxonomies', '__return_empty_array' );
		wp_dequeue_style( 'wp-document-revisions-front' );
		wp_deregister_style( 'wp-document-revisions-front' );
		remove_filter( 'document_register_blocks', '__return_false' );
		parent::tear_down();
	}

	/**
	 * Unregister the plugin's blocks, then run both registration callbacks.
	 */
	private function reregister_blocks() {
		global $wpdr_fe, $wpdr_widget;

		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( self::$blocks as $name ) {
			if ( $registry->is_registered( $name ) ) {
				unregister_block_type( $name );
			}
		}
		$wpdr_fe->documents_shortcode_blocks();
		$wpdr_widget->documents_widget_block();
	}

	/**
	 * The filter stops all four blocks registering; by default they all register.
	 */
	public function test_register_blocks_filter() {
		$registry = WP_Block_Type_Registry::get_instance();

		add_filter( 'document_register_blocks', '__return_false' );
		$this->reregister_blocks();
		foreach ( self::$blocks as $name ) {
			self::assertFalse( $registry->is_registered( $name ), $name . ' not registered' );
		}

		remove_filter( 'document_register_blocks', '__return_false' );
		$this->reregister_blocks();
		foreach ( self::$blocks as $name ) {
			self::assertTrue( $registry->is_registered( $name ), $name . ' registered' );
		}
	}

	/**
	 * The stylesheet is registered on every page but only enqueued when the Edit link renders.
	 */
	public function test_front_css_only_when_used() {
		global $wpdr, $wpdr_fe;
		$wpdr->add_caps();

		do_action( 'wp_enqueue_scripts' );
		self::assertTrue( wp_style_is( 'wp-document-revisions-front', 'registered' ) );
		self::assertFalse( wp_style_is( 'wp-document-revisions-front', 'enqueued' ), 'Not on every page.' );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$doc = self::factory()->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_author'  => $editor,
				'post_content' => '',
			)
		);
		// get_documents() skips documents without a file.
		self::add_document_attachment( self::factory(), $doc, self::$test_file );

		$wpdr_fe->documents_shortcode( array( 'show_edit' => 'true' ) );
		self::assertTrue( wp_style_is( 'wp-document-revisions-front', 'enqueued' ), 'Enqueued by the Edit link.' );
	}
}
