<?php
/**
 * Tests widget functionality.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Front end tests
 */
class Test_WP_Document_Revisions_Widget extends Test_Common_WPDR {

	/**
	 * List of users being tested.
	 *
	 * @var WP_User[] $users
	 */
	protected static $users = array(
		'editor' => null,
		'author' => null,
	);

	/**
	 * Workflow_state term id
	 *
	 * @var integer $ws_term_id
	 */
	private static $ws_term_id;

	/**
	 * Author Public Post ID
	 *
	 * @var integer $author_public_post
	 */
	private static $author_public_post;

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
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author' => $factory->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
		);

		// init permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$wp_rewrite->flush_rules();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->register_ct();
		$wpdr->initialize_workflow_states();

		// Taxonomy terms recreated as fixtures.
		$ws_terms         = self::create_term_fixtures( $factory );
		self::$ws_term_id = (int) $ws_terms[0]->term_id;

		// create posts for scenarios.
		// Author Public.
		self::$author_public_post = $factory->post->create(
			array(
				'post_title'   => 'Author Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['author']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_public_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$author_public_post, array( self::$ws_term_id ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow state to document' );

		self::add_document_attachment( $factory, self::$author_public_post, self::$test_file );

		// sleep to ensure timestamps different.
		sleep( 1 );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, array( self::$ws_term_id ), 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow state to document' );

		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );
	}

	/**
	 * Delete the posts. (Taken from WP Test Suite).
	 */
	public static function wpTearDownAfterClass() {
		// remove terms.
		wp_remove_object_terms( self::$author_public_post, self::$ws_term_id, 'workflow_state' );
		wp_remove_object_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );

		// make sure that we have the admin set up.
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$author_public_post, true );
		wp_delete_post( self::$editor_private_post, true );

		// delete done, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		// clear down the ws terms.
		$ws_terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);

		// delete them all.
		foreach ( $ws_terms as $ws_term ) {
			wp_delete_term( $ws_term->term_id, 'workflow_state' );
			clean_term_cache( $ws_term->term_id, 'workflow_state' );
		}

		unregister_taxonomy( 'workflow_state' );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$author_public_post, 1, 1 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
	}

	/**
	 * Verify published post on widget (no author info).
	 *
	 * Should see Author (Public) and not Editor (Private)
	 */
	public function test_widget_publ_noauthor_nopriv() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = false;

		// published status only.
		$instance['post_status']['publish'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'publish_noauthor' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'publish_noauthor_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'publish_noauthor_2' );
	}

	/**
	 * Verify published post on widget (no author info) using doc_read.
	 *
	 * Public should see nothing.
	 */
	public function test_widget_publ_docread_noauthor_nopriv() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = false;

		// published status only.
		$instance['post_status']['publish'] = true;

		// non-logged on user does not use read so should not read anything.
		self::set_up_document_read();

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::tear_down_document_read();

		self::assertEquals( 0, (int) substr_count( $output, '<li' ), 'publish_docread_noauthor' );
	}

	/**
	 * Verify published and private post on widget (no author info).
	 *
	 * Should see Author (Public) and not Editor (Private)
	 */
	public function test_widget_publ_noauthor_priv() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = false;

		// published status only.
		$instance['post_status']['publish'] = true;
		$instance['post_status']['private'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'pubpriv_noauthor' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'pubpriv_noauthor_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'pubpriv_noauthor_2' );
	}

	/**
	 * Verify published and private post on widget (no author info).
	 *
	 * Should see Author (Public) and not Editor (Private)
	 */
	public function test_widget_auth_noauthor_priv() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = false;

		// published status only.
		$instance['post_status']['publish'] = true;
		$instance['post_status']['private'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'pubpriv_noauthor' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'pubpriv_noauthor_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'pubpriv_noauthor_2' );
	}

	/**
	 * Verify published and private post on widget (no author info).
	 *
	 * Should see Author (Public) and not Editor (Private)
	 */
	public function test_widget_edit_noauthor_priv() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = false;

		// published status only.
		$instance['post_status']['publish'] = true;
		$instance['post_status']['private'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'pubpriv_noauthor' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'pubpriv_noauthor_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'pubpriv_noauthor_2' );
	}

	/**
	 * Verify published post on widget (author info).
	 */
	public function test_widget_author_nopriv() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = true;

		// published status only.
		$instance['post_status']['publish'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'publish_author' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'publish_author_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'publish_author_2' );
	}

	/**
	 * Verify published and private post on widget (author info).
	 */
	public function test_widget_author_priv() {

		// Create the two parameter sets.
		$args = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);

		$instance['title']       = 'title';
		$instance['numberposts'] = 5;
		$instance['show_author'] = true;

		// published and private status.
		$instance['post_status']['publish'] = true;
		$instance['post_status']['private'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		// run first as author. Cannot see other private posts.
		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['author']->ID );
		wp_cache_flush();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'pubpriv_author' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'pubpriv_author_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'pubpriv_author_2' );

		// then run as editor. Cant see own and others private posts.
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'pubpriv_author' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'pubpriv_author_1' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'pubpriv_author_2' );
	}

	/**
	 * Verify published and private post on block widget (with author info).
	 */
	public function test_block_widget() {

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$users['editor']->ID );
		wp_cache_flush();

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		// default widget - one public post.
		$atts   = array();
		$output = $wpdr_widget->wpdr_documents_widget_display( $atts );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'block_publish_1' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'block_publish_auth_1' );

		// now include the private post, so should be 2.
		$atts['post_stat_private'] = true;
		$output                    = $wpdr_widget->wpdr_documents_widget_display( $atts );

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'block_publish_2' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['author']->ID ) ), 'block_publish_auth_2' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'block_publish_auth_3' );

		// request that only one is shown, so should be 1. (latest is by Editor).
		$atts['numberposts'] = 1;
		$output              = $wpdr_widget->wpdr_documents_widget_display( $atts );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'block_publish_3' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$users['editor']->ID ) ), 'block_publish_auth_4' );
	}

}
