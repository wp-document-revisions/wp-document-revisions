<?php
/**
 * Tests the filters that control the plugin's abilities (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Abilities filter tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Abilities_Filters extends WP_UnitTestCase {

	/**
	 * Skip without the Abilities API (WordPress < 6.9).
	 */
	public function set_up() {
		parent::set_up();
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available on this WordPress version.' );
		}
	}

	/**
	 * With document_register_abilities off, nothing is registered; with it on, all four are.
	 */
	public function test_register_abilities_filter() {
		global $wpdr, $wp_current_filter;

		$names = array(
			'wp-document-revisions/check-document-access',
			'wp-document-revisions/get-document-info',
			'wp-document-revisions/get-document-revisions',
			'wp-document-revisions/override-document-lock',
		);

		$registered = array();
		$spy        = function ( $args, $name ) use ( &$registered ) {
			$registered[] = $name;
			return $args;
		};
		add_filter( 'wp_register_ability_args', $spy, 10, 2 );

		// wp_register_ability() only works during wp_abilities_api_init.
		$wp_current_filter[] = 'wp_abilities_api_init'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		add_filter( 'document_register_abilities', '__return_false' );
		$wpdr->register_abilities();
		remove_filter( 'document_register_abilities', '__return_false' );
		$when_off = $registered;

		// Control: re-register the (already registered) abilities with the filter on.
		foreach ( $names as $name ) {
			wp_unregister_ability( $name );
		}
		$registered = array();
		$wpdr->register_abilities();

		array_pop( $wp_current_filter );
		remove_filter( 'wp_register_ability_args', $spy, 10 );

		self::assertSame( array(), $when_off, 'Nothing registered when the filter returns false.' );
		self::assertSame( $names, $registered, 'All abilities registered by default.' );
	}

	/**
	 * The get-document-info capability can be raised.
	 */
	public function test_get_info_capability_filter() {
		global $wpdr;
		$wpdr->add_caps();

		$ability = wp_get_ability( 'wp-document-revisions/get-document-info' );
		self::assertNotNull( $ability );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		self::assertTrue( $ability->check_permissions( array( 'document_id' => 1 ) ), 'Editors can by default.' );

		$filter = function () {
			return 'manage_options';
		};
		add_filter( 'document_get_info_ability_capability', $filter );
		$allowed = $ability->check_permissions( array( 'document_id' => 1 ) );
		remove_filter( 'document_get_info_ability_capability', $filter );

		self::assertFalse( $allowed );
	}
}
