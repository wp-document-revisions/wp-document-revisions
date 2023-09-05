<?php
/**
 * Invokes the classes of WP Document Revisions
 *
 * @author Neil James <neil@familyjames.com>
 * @package WP Document Revisions
 */

/**
 * Classes of WP Document Revisions tests
 *
 * This needs to be run at the end - hence name.
 */
class Test_WP_Document_Revisions_Z_Last extends Test_Common_WPDR {

	/**
	 * Invoke the classes.
	 *
	 * @return void.
	 */
	public function test_construct() {
		// switch rest on.
		add_filter( 'document_show_in_rest', '__return_true' );

		// init classes.
		global $wpdr, $wpdr_fe, $wpdr_widget;
		$val1        = $wpdr;
		$val2        = $wpdr_fe;
		$val3        = $wpdr_widget;
		$wpdr_fe     = null;
		$wpdr_widget = null;
		$wpdr        = new WP_Document_Revisions();

		self::assertNotNull( $wpdr_fe, 'Class Front End not defined' );
		self::assertNotNull( $wpdr_widget, 'Class Widget not defined' );

		$wpdr->register_cpt();
		$wpdr->add_caps();
		$wpdr->register_ct();
		$wpdr->initialize_workflow_states();

		// make sure that we have the admin set up.
		include_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-admin.php';
		$wpdr->admin = new WP_Document_Revisions_Admin( $wpdr::$instance );

		self::assertNotNull( $wpdr->admin, 'Class Admin not defined' );

		// make sure that we have the rest set up.
		include_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-manage-rest.php';
		$wpdr_mr = new WP_Document_Revisions_Manage_Rest( $wpdr::$instance );

		self::assertNotNull( $wpdr_mr, 'Class Manage_Rest not defined' );

		// put back globals.
		$wpdr        = $val1;
		$wpdr_fe     = $val2;
		$wpdr_widget = $val3;
	}
}
