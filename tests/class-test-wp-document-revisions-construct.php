<?php
/**
 * Invokes the classes of WP Document Revisions
 *
 * @author Neil James <neil@familyjames.com>
 * @package WP Document Revisions
 */

/**
 * Classes of WP Document Revisions tests
 */
class Test_WP_Document_Revisions_Construct extends Test_Common_WPDR {

	/**
	 * Invoke the classes.
	 *
	 * @return void.
	 */
	public function test_construct() {
		// switch rest on.
		add_filter( 'document_show_in_rest', '__return_true' );

		// init classes.
		global $wpdr;
		$val1 = $wpdr;
		$wpdr = new WP_Document_Revisions();

		$wpdr->register_cpt();
		$wpdr->add_caps();
		$wpdr->register_ct();
		$wpdr->initialize_workflow_states();

		// make sure that we have the admin set up.
		require_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-admin.php';
		$wpdr->admin = new WP_Document_Revisions_Admin( $wpdr::$instance );

		self::assertNotNull( $wpdr->admin, 'Class Admin not defined' );

		// make sure that we have the rest set up.
		require_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-manage-rest.php';
		$wpdr_mr = new WP_Document_Revisions_Manage_Rest( $wpdr::$instance );

		self::assertNotNull( $wpdr_mr, 'Class Manage_Rest not defined' );

		// put back globals.
		$wpdr = $val1;
	}

}
