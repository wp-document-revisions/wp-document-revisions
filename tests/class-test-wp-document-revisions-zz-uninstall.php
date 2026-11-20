<?php
/**
 * Tests the plugin uninstall cleanup.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Uninstall tests.
 *
 * Named with a zz- prefix so it runs after the other tests: uninstall.php removes the
 * document capabilities from the in-memory roles, which tear_down() reloads.
 */
class Test_WP_Document_Revisions_Zz_Uninstall extends WP_UnitTestCase {

	/**
	 * Per-site options the plugin saves, with a value to save for each.
	 *
	 * @var array<string, mixed>
	 */
	private static $site_options = array(
		'document_slug'                   => 'files',
		'document_link_date'              => '1',
		'document_notify_enabled'         => '1',
		'document_notify_recipients'      => 'editors@example.com',
		'document_notify_on_state_change' => '1',
		'document_notify_on_new_revision' => '1',
		'wpdr_db_version'                 => '4',
	);

	/**
	 * Reload the roles, since uninstall.php removed the document capabilities from them.
	 */
	public function tear_down() {
		parent::tear_down();
		wp_cache_flush();
		wp_roles()->for_site();
	}

	/**
	 * Save every per-site option on the current site.
	 */
	private function save_site_options() {
		foreach ( self::$site_options as $option => $value ) {
			update_option( $option, $value );
		}
	}

	/**
	 * Run uninstall.php the way WordPress does.
	 */
	private function uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-document-revisions/wp-document-revisions.php' );
		}
		include dirname( __DIR__ ) . '/uninstall.php';
	}

	/**
	 * Uninstalling removes the plugin's options from the current site.
	 */
	public function test_uninstall_removes_current_site_options() {
		$this->save_site_options();

		$this->uninstall();

		foreach ( array_keys( self::$site_options ) as $option ) {
			self::assertFalse( get_option( $option ), "$option is removed." );
		}
	}

	/**
	 * On multisite, uninstalling removes the plugin's options from every site.
	 */
	public function test_uninstall_removes_options_on_every_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		$site = self::factory()->blog->create();
		switch_to_blog( $site );
		$this->save_site_options();
		restore_current_blog();

		$this->uninstall();

		foreach ( array_keys( self::$site_options ) as $option ) {
			self::assertFalse( get_blog_option( $site, $option ), "$option is removed on site $site." );
		}
	}

	/**
	 * Uninstalling removes the document capabilities from the current site's roles.
	 */
	public function test_uninstall_removes_current_site_caps() {
		get_role( 'editor' )->add_cap( 'edit_documents' );

		$this->uninstall();

		self::assertFalse( get_role( 'editor' )->has_cap( 'edit_documents' ) );
	}

	/**
	 * On multisite, uninstalling removes the document capabilities from every site's roles.
	 */
	public function test_uninstall_removes_caps_on_every_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		$site = self::factory()->blog->create();
		switch_to_blog( $site );
		get_role( 'editor' )->add_cap( 'edit_documents' );
		restore_current_blog();

		$this->uninstall();

		switch_to_blog( $site );
		$has_cap = get_role( 'editor' )->has_cap( 'edit_documents' );
		restore_current_blog();
		self::assertFalse( $has_cap, "edit_documents is removed on site $site." );
	}
}
