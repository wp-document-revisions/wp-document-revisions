<?php
/**
 * Tests that the sitewide extraction and pre-fill switches turn off their UI and routes (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Sitewide text extraction / AI pre-fill opt-out tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Extraction_Opt_Outs extends WP_UnitTestCase {

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
	 * Remove the switches and any registered meta box.
	 */
	public function tear_down() {
		global $wp_meta_boxes, $wp_rest_server;
		remove_filter( 'document_text_extraction_disabled', '__return_true' );
		remove_filter( 'document_ai_prefill_disabled', '__return_true' );
		unset( $wp_meta_boxes['document'] );
		$wp_rest_server = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $_POST[ WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD ] );
		parent::tear_down();
	}

	/**
	 * Whether the Text Extraction & AI meta box is registered.
	 *
	 * @return bool
	 */
	private function meta_box_registered() {
		global $wp_meta_boxes;
		unset( $wp_meta_boxes['document'] );
		set_current_screen( 'document' );
		WP_Document_Revisions_Text_Extraction_Opt_Out::register_meta_box();
		$registered = isset( $wp_meta_boxes['document']['side']['low'][ WP_Document_Revisions_Text_Extraction_Opt_Out::META_BOX_ID ] );
		set_current_screen( 'front' );
		return $registered;
	}

	/**
	 * The meta box isn't added when extraction is off sitewide.
	 */
	public function test_meta_box_hidden_when_extraction_off() {
		self::assertTrue( $this->meta_box_registered(), 'Registered by default.' );

		add_filter( 'document_text_extraction_disabled', '__return_true' );
		self::assertFalse( $this->meta_box_registered() );
	}

	/**
	 * The pre-fill checkbox isn't rendered when the pre-fill is off sitewide.
	 */
	public function test_prefill_checkbox_hidden_when_prefill_off() {
		$doc = self::factory()->post->create_and_get( array( 'post_type' => 'document' ) );

		add_filter( 'document_ai_prefill_disabled', '__return_true' );
		ob_start();
		WP_Document_Revisions_Text_Extraction_Opt_Out::render_meta_box( $doc );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD, $output );
		self::assertStringNotContainsString( WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD_PREFILL, $output );
	}

	/**
	 * Saving without the (hidden) pre-fill checkbox keeps the stored per-document value.
	 */
	public function test_save_keeps_prefill_value_when_prefill_off() {
		wp_set_current_user( self::$editor );
		$doc = self::factory()->post->create_and_get(
			array(
				'post_type'   => 'document',
				'post_author' => self::$editor,
			)
		);
		update_post_meta( $doc->ID, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY_PREFILL, '1' );

		add_filter( 'document_ai_prefill_disabled', '__return_true' );
		$_POST[ WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD ] = wp_create_nonce( WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_ACTION );
		WP_Document_Revisions_Text_Extraction_Opt_Out::save( $doc->ID, $doc );

		self::assertSame( '1', get_post_meta( $doc->ID, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY_PREFILL, true ) );
	}

	/**
	 * The summary and diff routes aren't registered when extraction is off sitewide.
	 */
	public function test_summary_routes_not_registered_when_extraction_off() {
		self::assertNotEmpty( $this->summary_routes(), 'Registered by default.' );

		add_filter( 'document_text_extraction_disabled', '__return_true' );
		self::assertSame( array(), $this->summary_routes() );
	}

	/**
	 * The wpdr/v1 document summary and diff routes on a fresh REST server.
	 *
	 * @return string[]
	 */
	private function summary_routes() {
		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		do_action( 'rest_api_init', $wp_rest_server );
		$routes = array_keys( $wp_rest_server->get_routes() );

		return array_values( preg_grep( '#^/' . preg_quote( WP_Document_Revisions_AI_Summary_REST::ROUTE_NAMESPACE, '#' ) . '/documents/#', $routes ) );
	}
}
