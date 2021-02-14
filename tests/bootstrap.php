<?php
/**
 * Bootstrap the local test environment
 *
 * @package WP_Document_Revisions
 */

// Save error reporting level (for reversion after file delete).
// phpcs:ignore
$err_level = error_reporting();

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Output message to log.
 *
 * @param string $text text to output.
 */
function console_log( $text ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
	fwrite( STDERR, "\n" . $text . ' : ' );
}

/**
 * Require the WP Document Revisions Plugin on load
 */
function _manually_load_plugin() {
	require __DIR__ . '/../wp-document-revisions.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Several tests will try to serve a file twice, this would fail, so suppress headers from being written.
 *
 * Tests also require buffers opened to be closed (and so send headers).
 *
 * @param array  $headers any headers for the file being served.
 * @param string $file    file name of file being served.
 */
function _remove_headers( $headers, $file ) {
	return array();
}

tests_add_filter( 'document_revisions_serve_file_headers', '_remove_headers', 10, 2 );

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
add_filter( 'wp_die_handler', '_wpdr_die_handler_filter', 100 );

require $_tests_dir . '/includes/bootstrap.php';
