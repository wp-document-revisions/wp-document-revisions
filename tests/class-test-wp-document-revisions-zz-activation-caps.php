<?php
/**
 * Tests that first activation doesn't warn about a capability it just granted (#887).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Activation capability tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Activation_Caps extends WP_UnitTestCase {

	/**
	 * Restore the document capabilities and clear the warning.
	 */
	public function tear_down() {
		global $wpdr;
		$wpdr->add_caps();
		delete_transient( 'wpdr_activation_issue' );
		parent::tear_down();
	}

	/**
	 * Simulate a first activation: the user's capabilities are loaded before the plugin
	 * adds the document capabilities to their role.
	 */
	public function test_first_activation_does_not_warn() {
		global $wpdr;

		// Strip the document capabilities, as on a site that has never run the plugin.
		$role = get_role( 'editor' );
		foreach ( array_keys( $role->capabilities ) as $cap ) {
			if ( false !== strpos( $cap, 'document' ) ) {
				$role->remove_cap( $cap );
			}
		}

		// Load the user, and their capabilities, before activation runs.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		delete_transient( 'wpdr_activation_issue' );
		self::assertFalse( current_user_can( 'edit_documents' ), 'precondition: no edit_documents yet' );

		$wpdr->activation_hook();

		self::assertTrue( current_user_can( 'edit_documents' ), 'capabilities reloaded after add_caps' );
		self::assertFalse( get_transient( 'wpdr_activation_issue' ), 'no false activation warning' );
	}

	/**
	 * A user whose role is denied edit_documents still gets the warning.
	 */
	public function test_activation_warns_when_role_lacks_cap() {
		global $wpdr;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		delete_transient( 'wpdr_activation_issue' );

		$wpdr->activation_hook();

		self::assertSame( get_current_user_id(), (int) get_transient( 'wpdr_activation_issue' ), 'warning set' );
	}
}
