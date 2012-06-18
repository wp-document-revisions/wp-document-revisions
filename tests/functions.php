<?php
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

function _destroy_uploads() {
    $uploads = wp_upload_dir(); 
    foreach ( scandir( $uploads['basedir'] ) as $file )
    	_rmdir( $uploads['basedir'] . '/' . $file ); 
}

function _rmdir( $path ) {
    if ( in_array(basename( $path ), array( '.', '..' ) ) ) {
    	return;
    } elseif ( is_file( $path ) ) {
    	unlink( $path );
    } elseif ( is_dir( $path ) ) {
    	foreach ( scandir( $path ) as $file )
    		_rmdir( $path . '/' . $file );
    	rmdir( $path );
    }
}

function rand_str($len=32) {
    return substr(md5(uniqid(rand())), 0, $len);
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