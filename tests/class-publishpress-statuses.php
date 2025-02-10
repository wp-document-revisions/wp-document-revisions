<?php
/**
 * Test class for PublishPress_Statuses Support.
 *
 * @since 3.6.1
 * @package WP_Document_Revisions
 */

/**
 * Main PublishPress_Statuses Support class definition.
 *
 * Contains only the data to support the test execution.
 */
class PublishPress_Statuses {
	/**
	 * Create a sub-object.
	 *
	 * @var $instance object (class)
	 */
	public static $instance = null;

	/**
	 * Create a sub-object.
	 *
	 * @var $instance object (class)
	 */
	public $options = null;

	// phpcs:ignore
	const taxonomy_key = 'post_status';

	/**
	 * Initiates an instance of the class and adds hooks.
	 *
	 * @since 3.6.1
	 */
	public function __construct() {
		self::$instance = &$this;
		$options             = new StdClass();
		$options->enabled    = 'on';
		$options->post_types = array(
			'document' => 'on',
		);
		$this->options       = $options;
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
