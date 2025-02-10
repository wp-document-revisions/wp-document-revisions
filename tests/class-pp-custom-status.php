<?php
/**
 * Test class for Edit Flow Support.
 *
 * @since 3.6.1
 * @package WP_Document_Revisions
 */

/**
 * Main PublishPress Support class definition.
 *
 * Contains only the data to support the test execution.
 */
class PP_Custom_Status {
	/**
	 * Create a sub-object.
	 *
	 * @var $modules object
	 */
	public $modules = null;

	// phpcs:ignore
	const taxonomy_key = 'post_status';

	/**
	 * Initiates an instance of the class and adds hooks.
	 *
	 * @since 3.6.1
	 */
	public function __construct() {
		$options                              = new StdClass();
		$options->post_types                  = array(
			'document' => 'on',
		);
		$this->modules                        = new StdClass();
		$this->modules->custom_class          = new StdClass();
		$this->modules->custom_class->options = $options;
	}

	/**
	 * Identifies if the function is enabled.
	 *
	 * @param string $funct function to be called.
	 * @return bool
	 * @since 3.6.1
	 */
	public function module_enabled( $funct ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		// funct is enabled.
		return true;
	}
}
