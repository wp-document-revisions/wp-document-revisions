<?php
/**
 * Loops through all plugin tests
 * Adapted from wordpress-tests/all.php
 * @package wordpress-plugin-tests
 *
 * Usage: Place any tests you'd like to run in the same folder as this file 
 *        (usually /tests/ within your plugin repo), and name each file in the
 *        form of `test_{name_of_test}.php`. Within each file, include a single
 *        class in the form of `WP_Test_{name_of_test}. The class should extend 
 *        the base class `WP_UnitTestCase`. All tests will be automatically run.
 *
 */
require_once 'PHPUnit/Autoload.php';

$tests_dir = dirname( __FILE__ );
$old_cwd = getcwd();
chdir( $tests_dir );

for( $depth = 0; $depth <= 3; $depth++ ) {
	foreach( glob( str_repeat( 'tests[_-]*/', $depth ) . 'test_*.php' ) as $test_file ) {
		include_once $test_file;
	}	
}

class all {
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite();
		foreach( get_declared_classes() as $class ) {
			if ( preg_match( '/^WP_Test_/', $class ) ) {
				$suite->addTestSuite( $class );
			}
		}
        return $suite;
    }
}

chdir( $old_cwd );