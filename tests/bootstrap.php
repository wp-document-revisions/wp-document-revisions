<?php
/**
 * Bootstrap the local test environment
 *
 * @package WP_Document_Revisions
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Require the WP Document Revisions Plugin on load
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wp-document-revisions.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Whether we wp_die'd this test.
 *
 * @return bool True if wp_die() has been used. False if not.
 */
function _wpdr_is_wp_die() {
	if ( isset( $GLOBALS['is_wp_die'] ) ) {
		return $GLOBALS['is_wp_die'];
	}

	return false;
}

/**
 * Acts as a custom wp_die() handler.
 *
 * This allows tests to continue, but sets a global state that
 * we can check and manipulate.
 */
function _wpdr_die_handler() {
	$GLOBALS['is_wp_die'] = true;
}

/**
 * Registers the handler to use for a wp_die() call.
 *
 * @return string
 */
function _wpdr_die_handler_filter() {
	return '_wpdr_die_handler';
}
tests_add_filter( 'wp_die_handler', '_wpdr_die_handler_filter', 100 );

require $_tests_dir . '/includes/bootstrap.php';

/**
 * Utility functions used for testing document revisions
 * Most adapted from the core testing framework: http://svn.automattic.com/wordpress-tests/
 *
 * @param String $role the user's role.
 * @param String $user_login the user's login.
 * @param String $pass the user's password.
 * @param string $email the user's email.
 */
function _make_user( $role = 'administrator', $user_login = '', $pass = '', $email = '' ) {

	$user = array(
		'role'       => $role,
		'user_login' => ( $user_login ) ? $user_login : rand_str(),
		'user_pass'  => ( $pass ) ? $pass : rand_str(),
		'user_email' => ( $email ) ? $email : rand_str() . '@example.com',
	);

	$user_id = wp_insert_user( $user );

	return $user_id;

}

/**
 * Remove a user from the DB.
 *
 * @param Int $user_id the user to remove.
 */
function _destroy_user( $user_id ) {

	// non-admin.
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	if ( is_multisite() ) {
		wpmu_delete_user( $user_id );
	} else {
		wp_delete_user( $user_id );
	}

}

/**
 * Remove all users from DB.
 */
function _destroy_users() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$users = $wpdb->get_col( "SELECT ID from $wpdb->users" );
		array_map( '_destroy_user', $users );
}

/**
 * Recursively delete a directory.
 *
 * @param String $dir the directory to delete.
 */
function _rrmdir( $dir ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( '.' !== $object && '..' !== $object ) {
				if ( 'dir' === filetype( $dir . '/' . $object ) ) {
					_rrmdir( $dir . '/' . $object );
				} else {
					unlink( $dir . '/' . $object );
				}
			}
		}
		reset( $objects );
		rmdir( $dir );
	}
}

/**
 * Remove any uploaded files.
 */
function _destroy_uploads() {
	$uploads = wp_upload_dir();
	$files   = array_diff( scandir( $uploads['basedir'] ), array( '..', '.' ) );
	foreach ( $files as $file ) {
		_rrmdir( $uploads['basedir'] . '/' . $file );
	}
}

/**
 * We want to make sure we're testing against the db, not just in-memory data
 * this will flush everything and reload it from the db.
 */
function _flush_roles() {
	unset( $GLOBALS['wp_user_roles'] );
	global $wp_roles;
	$wp_roles->for_site();
}
