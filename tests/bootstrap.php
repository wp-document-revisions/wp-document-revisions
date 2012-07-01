<?php
/**
 * Bootstrap the workflow testing environment
 * Uses wordpress tests (http://github.com/nb/wordpress-tests/) which uses PHPUnit
 * @package wp-document-revisions
 */

//activate our plugin and boot up WP that's really it.
$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array( 'wp-document-revisions/wp-document-revisions.php' ),
);

require dirname( __FILE__ ). '/framework/init.php';
require dirname( __FILE__ ). '/functions.php';
