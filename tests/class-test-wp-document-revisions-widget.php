<?php
/**
 * Tests widgett functionality.
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Front end tests
 */
class Test_WP_Document_Revisions_Widget extends WP_UnitTestCase {


	/**
	 * SetUp initial settings.
	 */
	public function setUp() {

		parent::setUp();

		// init user roles.
		global $wpdr;
		$wpdr->add_caps();
		_flush_roles();
		$this->user_ids = array();
		wp_set_current_user( 0 );

		// flush cache for good measure.
		wp_cache_flush();

	}

	/**
	 * Break down for next test.
	 */
	public function tearDown() {

		_destroy_uploads();
		parent::tearDown();

	}

	/**
	 * Output message to log.
	 *
	 * @param string $text text to output.
	 */
	public function consoleLog( $text ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			fwrite( STDERR, "\n" . $text . ' : ' );
	}

	/**
	 * Verify published post on widget (no author info).
	 */
	public function test_widget_noauthor() {

		$this->consoleLog( 'Test_Widget - widget_noauthor' );

		// create post with a user.
		$user_id = _make_user( 'administrator', 'test_user' );
		wp_set_current_user( $user_id );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();
		wp_publish_post( $doc_id );

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

		$this->assertEquals( 1, (int) substr_count( $output, '<li' ), 'published_noauthor' );
		$this->assertEquals( 0, (int) substr_count( $output, 'test_user' ), 'noauthor' );

	}

	/**
	 * Verify published post on widget (with author info).
	 */
	public function test_widget_author() {

		$this->consoleLog( 'Test_Widget - widget_author' );

		// create post with a user.
		$user_id = _make_user( 'administrator', 'test_user' );
		wp_set_current_user( $user_id );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();
		wp_publish_post( $doc_id );

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
		$this->consoleLog( $output );

		$this->assertEquals( 1, (int) substr_count( $output, '<li' ), 'published_withauthor' );
		$this->assertEquals( 1, (int) substr_count( $output, 'test_user' ), 'withauthor' );

	}

	/**
	 * Verify published and private post on block widget (with author info).
	 */
	public function test_block_widget() {

		$this->consoleLog( 'Test_Widget - block_widget' );

		// create post with a user.
		$user_id = _make_user( 'administrator', 'test_user' );
		wp_set_current_user( $user_id );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();
		wp_publish_post( $doc_id );

		// create a private post.
		$doc_id = $tdr->test_revise_document();

		$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();

		$atts   = array();
		$output = $wpdr_widget->wpdr_documents_widget_display( $atts );
		$this->consoleLog( $output );

		$this->assertEquals( 1, (int) substr_count( $output, '<li' ), 'block_publish' );
		$this->assertEquals( 1, (int) substr_count( $output, 'test_user' ), 'block_publish_auth' );

		$atts['post_stat_private'] = true;
		$output                    = $wpdr_widget->wpdr_documents_widget_display( $atts );
		$this->consoleLog( $output );

		$this->assertEquals( 2, (int) substr_count( $output, '<li' ), 'block_publish' );
		$this->assertEquals( 2, (int) substr_count( $output, 'test_user' ), 'block_publish_auth' );

	}

}
