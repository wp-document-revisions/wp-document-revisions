<?php
/**
 * Tests admin functionality.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Admin tests
 */
class Test_WP_Document_Revisions_Admin extends Test_Common_WPDR {

	/**
	 * Editor user id
	 *
	 * @var integer $editor_user_id
	 */
	private static $editor_user_id;

	/**
	 * Workflow_state term id
	 *
	 * @var integer $ws_term_id
	 */
	private static $ws_term_id;

	/**
	 * Author Public Post ID
	 *
	 * @var integer $editor_public_post
	 */
	private static $editor_public_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer $editor_private_post
	 */
	private static $editor_private_post;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// phpcs:enable
		console_log( 'Test_Admin' );

		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}

		// set up admin.
		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}
		$wpdr->admin_init();

		// create users.
		// Note that editor can do everything admin can do. Contributors cannot actually upload files by default.
		self::$editor_user_id = $factory->user->create(
			array(
				'user_nicename' => 'Editor',
				'role'          => 'editor',
			)
		);

		// init user roles.
		$wpdr->add_caps();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->initialize_workflow_states();
		$ws_terms         = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::$ws_term_id = $ws_terms[0]->term_id;

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_public_post, self::$ws_term_id, 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document Editor Private' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( $editor_public_post, 1, 1 );
		self::verify_structure( $editor_private_post, 1, 1 );
	}

	/**
	 * Verify dashboard display.
	 */
	public function test_dashboard_display_1() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' dashboard_display 1' );

		// see that one post only is seen.
		ob_start();
		$wpdr->admin->dashboard_display();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'display count public 1' );
		self::assertEquals( 1, (int) substr_count( $output, 'Publish' ), 'display publish public 1' );
	}

	/**
	 * Verify dashboard display. Publish the private one, so now two seen.
	 */
	public function test_dashboard_display_2() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' dashboard_display 2' );

		// see that two posts are seen.
		wp_publish_post( self::$editor_private_post );
		ob_start();
		$wpdr->admin->dashboard_display();
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'display count all' );
		self::assertEquals( 2, (int) substr_count( $output, 'Publish' ), 'display publish all' );
	}

	/**
	 * Verify revision log metabox. dashboard_display_2 will have created a revision.
	 */
	public function test_revision_metabox_unauth() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' revision_metabox_unauth' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		ob_start();
		$wpdr->admin->revision_metabox( get_post( self::$editor_private_post ) );
		$output = ob_get_contents();
		ob_end_clean();

		// There will be 1 for RSS feed.
		self::assertEquals( 1, (int) substr_count( $output, '<a href' ), 'revision count' );
		self::assertEquals( 0, (int) substr_count( $output, '-revision-1.' ), 'revision count revision 1' );
	}

	/**
	 * Verify revision log metabox. dashboard_display_2 will have created a revision.
	 */
	public function test_revision_metabox_auth() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' revision_metabox_auth' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		ob_start();
		$wpdr->admin->revision_metabox( get_post( self::$editor_private_post ) );
		$output = ob_get_contents();
		ob_end_clean();

		// There will be 1 for RSS feed.
		self::assertEquals( 3, (int) substr_count( $output, '<a href' ), 'revision count' );
		self::assertEquals( 1, (int) substr_count( $output, '-revision-1.' ), 'revision count revision 1' );
		self::assertEquals( 0, (int) substr_count( $output, '-revision-2.' ), 'revision count revision 2' );
	}

	/**
	 * Verify document log metabox.
	 */
	public function test_document_metabox() {
		global $wpdr;
		$GLOBALS['is_wp_die'] = false;

		console_log( ' document_metabox' );

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		$post_obj = get_post( self::$editor_private_post );

		ob_start();
		$wpdr->admin->document_metabox( $post_obj );
		$output = ob_get_contents();
		ob_end_clean();

		self::assertEquals( 1, (int) substr_count( $output, 'post_id=' . $post_obj->ID . '&' ), 'document metabox post_id' );
		self::assertEquals( 1, (int) substr_count( $output, get_permalink( $post_obj->ID ) ), 'document metabox permalin' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'document metabox author' );
	}

}
