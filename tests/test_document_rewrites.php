<?php
/**
 * Unit tests for permalink and rewrite system
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_revisions
 */

class WP_Test_Document_Rewrites extends WPTestCase {

	/**
	 * SetUp initial settings
	 */
	function setUp() {

		parent::setUp();

		//init permalink structure
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%day%/%postname%/');
		$wp_rewrite->flush_rules();

		//custom wp_die_handler to verify that we died
		add_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );
		global $is_wp_die;
		$is_wp_die = false;

		//init user roles
		global $wpdr;
		$wpdr->add_caps();
		$this->_flush_roles();
		global $current_user;
		unset( $current_user );

		//flush cache for good measure
		wp_cache_flush();

		// Suppress warnings from "Cannot modify header information - headers already sent by"
		$this->_error_level = error_reporting();
		error_reporting( $this->_error_level & ~E_WARNING );

	}


	/**
	 *
	 */
	function tearDown() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('');

		$this->_destroy_users();
		$this->_destroy_uploads();
		$this->_delete_all_posts();
		parent::tearDown();
	}


	/**
	 *
	 * @return unknown
	 */
	function get_die_handler() {
		return array( &$this, 'die_handler' );
	}


	/**
	 *
	 * @param unknown $msg
	 */
	function die_handler( $msg ) {

		global $is_wp_die;
		$is_wp_die = true;

		echo $msg;

	}


	/**
	 *
	 * @return unknown
	 */
	function is_wp_die() {
		global $is_wp_die;
		return $is_wp_die;
	}


	/**
	 *
	 * @param unknown $url (optional)
	 * @param unknown $file (optional)
	 * @param unknown $msg (optional)
	 */
	function verify_download( $url = null, $file = null, $msg = null ) {

		if ( !$url )
			return;

		global $wpdr;

		$this->http( $url );

		//verify contents are actually served
		ob_start();
		$wpdr->serve_file( '' );
		$content = ob_get_contents();
		ob_end_clean();
		
		$this->assertFalse( is_404() || $this->is_wp_die(), "404 ($msg)" );
		$this->assertTrue( is_single(), "Not single ($msg)" );
		$this->assertStringEqualsFile( dirname( __FILE__ ) . '/' . $file, $content, "Contents don\'t match file ($msg)" );

	}


	/**
	 *
	 * @param unknown $url (optional)
	 * @param unknown $file (optional)
	 * @param unknown $msg (optional)
	 */
	function verify_cant_download( $url = null, $file = null, $msg = null ) {

		if ( !$url )
			return;

		global $wpdr;

		$this->http( $url );
		global $current_user;

		//verify contents are actually served
		ob_start();
		$wpdr->serve_file( '' );
		$content = ob_get_contents();
		ob_end_clean();
		
		global $current_user;
		if ( $msg == 'Public revision request (pretty)' )
			var_dump( $current_user );

		$this->assertTrue( ( is_404() || $this->is_wp_die() ), "Not 404'd or wp_die'd ($msg)" );
		$this->assertFalse( file_get_contents( dirname( __FILE__ ) . '/' . $file ) == $content, "File being erroneously served ($msg)" );

	}


	/**
	 *
	 */
	function test_public_document() {
		global $wpdr;

		//make new public document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();
		wp_publish_post( $docID );
		wp_cache_flush();

		$this->verify_download( "?p=$docID&post_type=document", $tdr->test_file, 'Public Ugly Permalink' );
		$this->verify_download( get_permalink( $docID ), $tdr->test_file, 'Public Pretty Permalink' );

	}


	/**
	 *
	 */
	function test_private_document_as_unauthenticated() {

		//make new private document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//public should be denied
		$this->verify_cant_download( "?p=$docID&post_type=document", $tdr->test_file, 'Private, Unauthenticated Ugly Permalink' );
		$this->verify_cant_download( get_permalink( $docID ), $tdr->test_file, 'Private, Unauthenticated Pretty Permalink' );

	}


	/**
	 *
	 */
	function test_private_document_as_contributor() {

		//make new private document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//contibutor should be denied
		$id = $this->_make_user('contributor');
		wp_set_current_user( $id );

		$this->verify_cant_download( "?p=$docID&post_type=document", $tdr->test_file, 'Private, Contrib. Ugly Permalink' );
		$this->verify_cant_download( get_permalink( $docID ), $tdr->test_file, 'Private, Contrib. Pretty Permalink' );
		$this->_destroy_user( $id );

	}


	/**
	 *
	 */
	function test_private_document_as_admin() {

		//make new private document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//admin should be able to access
		$id = $this->_make_user('administrator');
		wp_set_current_user( $id );

		$this->verify_download( "?p=$docID&post_type=document", $tdr->test_file, 'Private, Admin Ugly Permalink' );
		$this->verify_download( get_permalink( $docID ), $tdr->test_file, 'Private, Admin Pretty Permalink' );
		$this->_destroy_user( $id );

	}


	/**
	 *
	 */
	function test_document_revision_as_a_unauthenticated() {
		global $wpdr;

		//make new public, revised document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();
		wp_publish_post( $docID );
		$revisions = $wpdr->get_revisions( $docID );
		$revision = array_pop( $revisions );
		
		global $current_user;
		unset( $current_user );

		//public should be denied access to revisions
		$this->verify_cant_download( get_permalink( $revision->ID ), $tdr->test_file, 'Public revision request (pretty)' );
		$this->verify_cant_download( "?p=$docID&post_type=document&revision=1", $tdr->test_file, 'Public revision request (ugly)' );

	}


	/**
	 *
	 */
	function test_document_revision_as_admin() {

		global $wpdr;

		//make new public, revised document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();
		wp_publish_post( $docID );
		$revisions = $wpdr->get_revisions( $docID );
		$revision = array_pop( $revisions );

		//admin should be able to access
		$id = $this->_make_user('administrator');
		wp_set_current_user( $id );

		$this->verify_download( get_permalink( $revision->ID ), $tdr->test_file, 'Admin revision clean' );
		$this->verify_download( "?p=$docID&post_type=document&revision=1", $tdr->test_file, 'Admin revision ugly' );
		$this->_destroy_user( $id );

	}


	/**
	 *
	 */
	function test_revised_document() {

		global $wpdr;

		//make new public, revised document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();
		wp_publish_post( $docID );

		$this->verify_download( "?p=$docID&post_type=document", $tdr->test_file2, 'Revised, Ugly Permalink' );
		$this->verify_download( get_permalink( $docID ), $tdr->test_file2, 'Revised, Pretty Permalink' );

	}


	/**
	 *
	 */
	function test_archive() {

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();
		$this->http('/documents/' );
		$this->assertTrue( is_post_type_archive( 'document' ), 'Couldn\'t access /documents/' );

	}


	/**
	 *
	 */
	function test_permalink() {

		global $wpdr;
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();
		$post = get_post( $docID );
		$permalink = get_bloginfo( 'url' ) . '/documents/' . date('Y') . '/' . date('m') . '/' . $post->post_name . $wpdr->get_file_type( $docID );
		$this->assertEquals( $permalink, get_permalink( $docID ), 'Bad permalink' );

	}


	/**
	 *
	 */
	function test_revision_permalink() {

		global $wpdr;
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();
		$revisions = $wpdr->get_revisions( $docID );
		$revision = array_pop( $revisions );
		$permalink = get_bloginfo( 'url' ) . '/documents/' . date('Y') . '/' . date('m') . '/' . get_post( $docID )->post_name . '-revision-1' . $wpdr->get_file_type( $docID );
		$this->assertEquals( $permalink, get_permalink( $revision->ID ), 'Bad revision permalink' );
	}


	/**
	 *
	 * @param unknown $url (optional)
	 * @return unknown
	 */
	function simulate_feed( $url = null ) {

		if ( !$url )
			return;

		global $wpdr;

		$this->http( $url );

		ob_start();

		$wpdr->revision_feed_auth();

		if ( !$this->is_wp_die() )
			do_feed();

		$content = ob_get_contents();
		ob_end_clean();

		return $content;

	}


	/**
	 *
	 */
	function test_feed_as_unauthenticated() {

		global $wpdr;

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//try to get an un auth'd feed
		$content = $this->simulate_feed( get_permalink( $docID ) . '/feed/' );
		$this->assertFalse( $wpdr->validate_feed_key(), 'not properly validating feed key' );
		$this->assertTrue( $this->is_wp_die(), 'not properly denying access to feeds' );
		$this->assertEquals( 0, substr_count( $content, '<item>' ), 'denied feed leaking items' );

	}


	/**
	 *
	 */
	function test_feed_as_authorized() {

		global $wpdr;

		$wpdr->admin_init();
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//try to get an auth'd feed
		$userID = $this->_make_user('administrator');
		$key = $wpdr->admin->get_feed_key( $userID );

		$content = $this->simulate_feed( add_query_arg( 'key', $key, get_permalink( $docID ) . '/feed/' ) );
		$this->assertTrue( $wpdr->validate_feed_key(), 'not properly validating feed key' );
		$this->assertFalse( $this->is_wp_die(), 'Not properly allowing access to feeds' );
		$this->assertEquals( count( $wpdr->get_revisions( $docID ) ), (int) substr_count( $content, '<item>' ), 'improper feed item count' );
		$this->_destroy_user( $userID );

	}


	/**
	 * we want to make sure we're testing against the db, not just in-memory data
	 * this will flush everything and reload it from the db
	 */
	function _flush_roles() {
		unset($GLOBALS['wp_user_roles']);
		global $wp_roles;
		$wp_roles->_init();
	}


}