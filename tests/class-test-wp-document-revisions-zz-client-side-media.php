<?php
/**
 * Tests that document screens opt out of client-side media processing.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Client-side media processing tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Client_Side_Media extends WP_UnitTestCase {

	/**
	 * Make sure the admin class is loaded.
	 */
	public function set_up() {
		parent::set_up();
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}
	}

	/**
	 * Reset the current screen.
	 */
	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * The filter is registered.
	 */
	public function test_filter_registered() {
		global $wpdr;
		$this->assertSame( 10, has_filter( 'wp_client_side_media_processing_enabled', array( $wpdr->admin, 'no_client_side_media_for_documents' ) ) );
	}

	/**
	 * Document edit screens turn the pipeline off.
	 */
	public function test_disabled_on_document_screen() {
		global $wpdr;
		set_current_screen( 'document' );
		$this->assertFalse( $wpdr->admin->no_client_side_media_for_documents( true ) );
	}

	/**
	 * Other screens are left alone.
	 */
	public function test_untouched_on_other_screens() {
		global $wpdr;
		set_current_screen( 'post' );
		$this->assertTrue( $wpdr->admin->no_client_side_media_for_documents( true ) );
		$this->assertFalse( $wpdr->admin->no_client_side_media_for_documents( false ) );
	}
}
