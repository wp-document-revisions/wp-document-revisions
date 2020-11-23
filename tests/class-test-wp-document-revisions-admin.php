<?php
/**
 * Tests admin functionality.
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Disolay tests
 */
class Test_WP_Document_Revisions_Admin extends WP_UnitTestCase {


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
	 * Verify dashboard display.
	 */
	public function test_dashboard_display() {

		global $wpdr;

		$this->consoleLog( 'Test_Admin - dashboard_display' );

		// set up admin.
		if ( ! defined('WP_ADMIN') ) {
			define( 'WP_ADMIN', true );
		}
		$wpdr->admin_init();

		// create post with a user.
		$user_id = _make_user( 'administrator', 'test_user_1' );
		wp_set_current_user( $user_id );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_add_document();
		wp_publish_post( $doc_id );

		// create a private post.
		$doc_id = $tdr->test_add_document();

		global $wpdr;

		ob_start();
		$wpdr->admin->dashboard_display();
		$output = ob_get_contents();
		ob_end_flush();
		$this->consoleLog( $output );

		$this->assertEquals( 2, (int) substr_count( $output, '<li' ), 'display count' );
		$this->assertEquals( 1, (int) substr_count( $output, 'Public' ), 'display public' );
		$this->assertEquals( 1, (int) substr_count( $output, 'Private' ), 'display private' );
		_destroy_user( $user_id );

	}

	/**
	 * Verify revision log metabox.
	 */
	public function test_revision_metabox() {

		global $wpdr;

		$this->consoleLog( 'Test_Admin - revision_metabox' );

		// create post with a user.
		$user_id = _make_user( 'administrator', 'test_user_2' );
		wp_set_current_user( $user_id );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();
		wp_publish_post( $doc_id );

		global $wpdr;

		ob_start();
		$wpdr->admin->revision_metabox( $doc_id );
		$output = ob_get_contents();
		ob_end_flush();
		$this->consoleLog( $output );

		$this->assertEquals( 2, (int) substr_count( $output, '<li' ), 'revision count' );
		_destroy_user( $user_id );

	}

	/**
	 * Verify document log metabox.
	 */
	public function test_document_metabox() {

		global $wpdr;

		$this->consoleLog( 'Test_Admin - document_metabox' );

		// create post with a user.
		$user_id = _make_user( 'administrator', 'test_user_3' );
		wp_set_current_user( $user_id );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_add_document();
		wp_publish_post( $doc_id );

		global $wpdr;

		ob_start();
		$wpdr->admin->document_metabox( $doc_id );
		$output = ob_get_contents();
		ob_end_flush();
		$this->consoleLog( $output );

		$this->assertEquals( 0, (int) substr_count( $output, '<li' ), 'revision count' );
		_destroy_user( $user_id );

	}

}
