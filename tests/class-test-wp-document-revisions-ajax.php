<?php
/**
 * Tests for AJAX handlers added in modern JS migration.
 *
 * Focus: validate_structure admin-ajax fallback.
 *
 * @package WP_Document_Revisions
 */

/**
 * Ajax tests.
 */
class Test_WP_Document_Revisions_Ajax extends Test_Common_WPDR {

	/**
	 * Set up environment – ensure CPT registered and caps present.
	 */
	public function setUp(): void { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		parent::setUp();
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// Ensure validate structure component loaded (constructor adds ajax action).
		if ( ! class_exists( 'WP_Document_Revisions_Validate_Structure', false ) ) {
			require_once dirname( __DIR__, 1 ) . '/includes/class-wp-document-revisions-validate-structure.php';
		}
		new WP_Document_Revisions_Validate_Structure( $wpdr );
	}

	/**
	 * Test ajax validate_structure returns success for privileged user.
	 */
	public function test_ajax_validate_structure_success() {
		// Create an editor (has edit_documents via plugin caps) and set as current.
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		// Simulate POST request.
		$_POST['action'] = 'validate_structure'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		ob_start();
		do_action( 'wp_ajax_validate_structure' );
		$raw = ob_get_clean();

		$this->assertNotEmpty( $raw, 'AJAX response empty' );
		$data = json_decode( $raw, true );
		$this->assertIsArray( $data, 'AJAX response not JSON' );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'], 'AJAX success flag false' );
		$this->assertEquals( 'Validation complete', $data['data']['message'] );
	}

	/**
	 * Test ajax validate_structure blocked for user without capability.
	 */
	public function test_ajax_validate_structure_permission_denied() {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_POST['action'] = 'validate_structure'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		ob_start();
		do_action( 'wp_ajax_validate_structure' );
		$raw  = ob_get_clean();
		$data = json_decode( $raw, true );

		// WordPress sends success false + data for errors.
		$this->assertIsArray( $data );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
	}
}
