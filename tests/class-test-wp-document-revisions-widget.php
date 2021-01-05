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
	 * Author user id
	 *
	 * @var integer $author_user_id
	 */
	private static $author_user_id;

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
	 * @var integer $author_public_post
	 */
	private static $author_public_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer $editor_private_post
	 */
	private static $editor_private_post;

	/**
	 * Path to test file
	 *
	 * @var $test_file
	 */
	private static $test_file;

	/**
	 * Path to another test file
	 *
	 * @var $test-file2
	 */
	private static $test_file2;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
	// phpcs:enable
		console_log( 'Test_Widget' );

		// set source file names.
		self::$test_file  = dirname( __DIR__ ) . '/tests/documents/test-file.txt';
		self::$test_file2 = dirname( __DIR__ ) . '/documents/test-file-2.txt';

		// create users.
		self::$author_user_id = $factory->user->create(
			array(
				'user_nicename' => 'Author',
				'role'          => 'author',
			)
		);
		self::$editor_user_id = $factory->user->create(
			array(
				'user_nicename' => 'Editor',
				'role'          => 'editor',
			)
		);

		// init permalink structure.
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$wp_rewrite->flush_rules();

		// init user roles.
		global $wpdr;
		$wpdr->add_caps();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->initialize_workflow_states();
		$ws_terms   = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		$ws_term_id = $ws_terms[0]->term_id;

		// create posts for scenarios.
		// Author Public.
		self::$author_public_post = $factory->post->create(
			array(
				'post_title'   => 'Author Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$author_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$author_public_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$author_public_post, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );

		self::add_document_attachment( self::$author_public_post, self::$test_file );

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

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );
		self::assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );

		self::add_document_attachment( self::$editor_private_post, self::$test_file );
	}

	/**
	 * Verify published post on widget (no author info).
	 */
	public function test_widget_noauthor_nopriv() {

		console_log( ' widget_noauthor_nopriv' );

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
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$author_user_id ) ), 'publish_noauthor_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'publish_noauthor_2' );
	}

	/**
	 * Verify published and private post on widget (no author info).
	 */
	public function test_widget_noauthor_priv() {

		console_log( ' widget_noauthor_priv' );

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
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$author_user_id ) ), 'pubpriv_noauthor_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'pubpriv_noauthor_2' );
	}

	/**
	 * Verify published post on widget (author info).
	 */
	public function test_widget_author_nopriv() {

		console_log( ' widget_author_nopriv' );

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
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$author_user_id ) ), 'publish_author_1' );
		self::assertEquals( 0, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'publish_author_2' );
	}

	/**
	 * Verify published and private post on widget (author info).
	 */
	public function test_widget_author_priv() {

		console_log( ' widget_author_priv' );

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
		$instance['post_status']['private'] = true;

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$output = $wpdr_widget->widget_gen( $args, $instance );

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'pubpriv_author' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$author_user_id ) ), 'pubpriv_author_1' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'pubpriv_author_2' );
	}

	/**
	 * Verify published and private post on block widget (with author info).
	 */
	public function test_block_widget() {

		console_log( ' block_widget' );

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		// default widget - one public post.
		$atts   = array();
		$output = $wpdr_widget->wpdr_documents_widget_display( $atts );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'block_publish_1' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$author_user_id ) ), 'block_publish_auth_1' );

		// now include the private post, so should be 2.
		$atts['post_stat_private'] = true;
		$output                    = $wpdr_widget->wpdr_documents_widget_display( $atts );

		self::assertEquals( 2, (int) substr_count( $output, '<li' ), 'block_publish_2' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$author_user_id ) ), 'block_publish_auth_2' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'block_publish_auth_3' );

		// request that only one is shown, so should be 1. (latest is by Editor).
		$atts['numberposts'] = 1;
		$output              = $wpdr_widget->wpdr_documents_widget_display( $atts );

		self::assertEquals( 1, (int) substr_count( $output, '<li' ), 'block_publish_3' );
		self::assertEquals( 1, (int) substr_count( $output, get_the_author_meta( 'display_name', self::$editor_user_id ) ), 'block_publish_auth_4' );
	}

}
