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
		$wpdr->activation_hook();

		// make sure that we have the admin set up.
		include_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-admin.php';
		$wpdr->admin = new WP_Document_Revisions_Admin( $wpdr::$instance );

		self::assertNotNull( $wpdr->admin, 'Class Admin not defined' );

		// make sure that we have the rest set up.
		include_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-manage-rest.php';
		$wpdr_mr = new WP_Document_Revisions_Manage_Rest( $wpdr::$instance );

		self::assertNotNull( $wpdr_mr, 'Class Manage_Rest not defined' );

		// Test rules.
		$home_root = wp_parse_url( home_url() );
		if ( isset( $home_root['path'] ) ) {
			$home_root = trailingslashit( $home_root['path'] );
		} else {
			$home_root = '/';
		}

		$rules = '^RewriteRule ^WPDR ' . $home_root . '- [QSA,L]';
		$rules = $wpdr->mod_rewrite_rules( $rules );
		self::assertFalse( strpos( $rules, 'WPDR' ), 'mod_rewrite_rules' );

		// test notice.
		set_transient( 'wpdr_activation_issue', get_current_user_id() );
		ob_start();
		$wpdr->activation_error_notice();
		$output = ob_get_clean();
		self::assertTrue( true, 'activation_error_notice' );

		// test is_doc_image.
		self::assertTrue( $wpdr->is_doc_image(), 'is_doc_image' );

		// test i18n.
		$wpdr->i18n();
		self::assertTrue( true, 'i18n' );

		// test generate_rewrite_rules.
		$wpdr->generate_rewrite_rules();
		self::assertTrue( true, 'generate_rewrite_rules' );

		// test inject_rules.
		$wpdr->inject_rules();
		self::assertTrue( true, 'inject_rules' );

		// test add_post_status_column.
		$defaults = array(
			'col_1'  => 'Col 1',
			'col_2'  => 'Col 2',
			'author' => 'Author',
			'col_3'  => 'Col 3',
			'col_4'  => 'Col 4',
		);
		$updated  = $wpdr->add_post_status_column( $defaults );
		self::assertTrue( array_key_exists( 'status', $updated ), 'status exists' );
		self::assertTrue( true, 'add_post_status_column' );

		// test admin init.
		$wpdr->admin = null;
		$wpdr->admin_init( true );
		self::assertTrue( true, 'admin_init' );

		// test document_dir.
		$doc_dir                  = trailingslashit( $wpdr->document_upload_dir() );
		$wpdr::$wpdr_document_dir = null;
		self::assertEquals( $doc_dir, trailingslashit( $wpdr->document_upload_dir() ), 'Doc Dir' );

		// test document_upload_dir_set.
		$dir = $wpdr::$wp_default_dir;
		$dir = $wpdr->document_upload_dir_set( $dir );
		self::assertTrue( true, 'Doc Dir Set' );

		// test add_qv_workflow_state.
		$vars = array();
		$vars = $wpdr->add_qv_workflow_state( $vars );
		self::assertNotEmpty( $vars, 'QV empty' );

		// test use_workflow_states.
		$wpdr->use_workflow_states();
		self::assertTrue( true, 'use_workflow_states' );

		// test disable_workflow_states.
		$wpdr->disable_workflow_states();
		self::assertTrue( true, 'disable_workflow_states' );

		// test register_term_count_cb.
		$wpdr->register_term_count_cb();
		self::assertTrue( true, 'register_term_count_cb' );

		// test manage_rest.
		$wpdr->manage_rest();
		self::assertTrue( true, 'manage_rest' );

		// test ie_fix.
		global $wp;
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$_SERVER['HTTPS'] = 'on';
		$wp               = $wpdr->ie_cache_fix( $wp );
		self::assertTrue( true, 'ie_cache_fix 1' );
		$_SERVER['HTTP_USER_AGENT'] = 'msie';
		$wp                         = $wpdr->ie_cache_fix( $wp );
		self::assertTrue( true, 'ie_cache_fix 2' );
		$_SERVER['HTTP_USER_AGENT'] = 'other';
		$wp                         = $wpdr->ie_cache_fix( $wp );
		self::assertTrue( true, 'ie_cache_fix 2' );
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		// put back globals.
		$wpdr        = $val1;
		$wpdr_fe     = $val2;
		$wpdr_widget = $val3;
	}
}
