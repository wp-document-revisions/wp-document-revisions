<?php
ob_start();
$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wp-document-revisions.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

/**
* Utility functions used for testing document revisions
* Most adapted from the core testing framework: http://svn.automattic.com/wordpress-tests/
*/

function _make_user( $role = 'administrator', $user_login = '', $pass='', $email='' ) {

		$user = array(
			'role' => $role,
			'user_login' => ( $user_login ) ? $user_login : rand_str(),
			'user_pass' => ( $pass ) ? $pass: rand_str(),
			'user_email' => ( $email ) ? $email : rand_str() . '@example.com',
		);

	$userID = wp_insert_user( $user );

		return $userID;

}

function _destroy_user( $user_id ) {

	//non-admin
	if ( !function_exists( 'wp_delete_user' ) )
		require_once ABSPATH . 'wp-admin/includes/user.php';

		if ( is_multisite() )
			wpmu_delete_user( $user_id );
		else
			wp_delete_user( $user_id );

}

function _destroy_users() {
	global $wpdr;
	$users = $wpdb->get_col( "SELECT ID from $wpdb->users" );
		array_map( array( $this, '_destroy_user' ), $users );
}

function _rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") _rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }

function _destroy_uploads() {
		$uploads = wp_upload_dir();
		$files = array_diff(scandir($uploads['basedir']), array('..', '.'));
		foreach ( $files as $file )
			_rrmdir( $uploads['basedir'] . '/' . $file );
}

/**
* We want to make sure we're testing against the db, not just in-memory data
* this will flush everything and reload it from the db
*/
function _flush_roles() {
		unset($GLOBALS['wp_user_roles']);
		global $wp_roles;
		$wp_roles->_init();
}
