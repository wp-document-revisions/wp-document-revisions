<?php
/**
 * Unit tests for permalink and rewrite system
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_revisions
 */

class WP_Test_Document_Rewrites extends WP_UnitTestCase {

	/**
	 * SetUp initial settings
	 */
	function setUp() {

		parent::setUp();

		//init permalink structure
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%day%/%postname%/');
		$wp_rewrite->flush_rules();

		$GLOBALS['is_wp_die'] = false;

		//init user roles
		global $wpdr;
		$wpdr->add_caps();
		_flush_roles();

		//flush cache for good measure
		wp_cache_flush();

	}

	/**
	 * Break down for next test
	 */
	function tearDown() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('');

		_destroy_uploads();
		parent::tearDown();

	}

	/**
	 * Tests that a given URL actually returns the right file
	 * @param string $url to check
	 * @param string $file relative path of expected file
	 * @param string $msg message describing failure
	 */
	function verify_download( $url = null, $file = null, $msg = null ) {

		if ( !$url )
			return;

		global $wpdr;
		flush_rewrite_rules();

		$this->go_to( $url );

		//verify contents are actually served
		ob_start();
		$wpdr->serve_file( '' );
		$content = ob_get_contents();
		ob_end_clean();

		$this->assertFalse( is_404(), "404 ($msg)" );
		$this->assertFalse( _wpdr_is_wp_die(), "wp_died ($msg)" );
		$this->assertTrue( is_single(), "Not single ($msg)" );
		$this->assertStringEqualsFile( dirname( __FILE__ ) . '/' . $file, $content, "Contents don\'t match file ($msg)" );

	}


	/**
	 * Tests that a given url *DOES NOT* return a file
	 * @param string $url to check
	 * @param string $file relative path of expected file
	 * @param string $msg message describing failure
	 */
	function verify_cant_download( $url = null, $file = null, $msg = null ) {

		if ( !$url )
			return;

		global $wpdr;
		flush_rewrite_rules();

		$this->go_to( $url );

		//verify contents are actually served
		ob_start();
		$wpdr->serve_file( '' );
		$content = ob_get_contents();
		ob_end_clean();

		$this->assertTrue( ( is_404() || _wpdr_is_wp_die() ), "Not 404'd or wp_die'd ($msg)" );
		$this->assertFalse( file_get_contents( dirname( __FILE__ ) . '/' . $file ) == $content, "File being erroneously served ($msg)" );

	}


	/**
	 * Can the public access a public file? (yes)
	 */
	function test_public_document() {
		global $wpdr;

		//make new public document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();
		wp_publish_post( $docID );

		wp_set_current_user( 0 );
		wp_cache_flush();

		$this->verify_download( "?p=$docID&post_type=document", $tdr->test_file, 'Public Ugly Permalink' );
		$this->verify_download( get_permalink( $docID ), $tdr->test_file, 'Public Pretty Permalink' );

	}


	/**
	 * Can the public access a private file? (no)
	 */
	function test_private_document_as_unauthenticated() {

		//make new private document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		global $current_user;
		unset( $current_user );
		wp_set_current_user( 0 );
		wp_cache_flush();

		//public should be denied
		$this->verify_cant_download( "?p=$docID&post_type=document", $tdr->test_file, 'Private, Unauthenticated Ugly Permalink' );
		$this->verify_cant_download( get_permalink( $docID ), $tdr->test_file, 'Private, Unauthenticated Pretty Permalink' );

	}


	/**
	 * Can a contributor access a public file? (no)
	 */
	function test_private_document_as_contributor() {

		//make new private document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//contibutor should be denied
		$id = _make_user('contributor');
		wp_set_current_user( $id );

		$this->verify_cant_download( "?p=$docID&post_type=document", $tdr->test_file, 'Private, Contrib. Ugly Permalink' );
		$this->verify_cant_download( get_permalink( $docID ), $tdr->test_file, 'Private, Contrib. Pretty Permalink' );
		_destroy_user( $id );

	}


	/**
	 * Can an admin access a private file? (yes)
	 */
	function test_private_document_as_admin() {

		//make new private document
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//admin should be able to access
		$id = _make_user('administrator');
		$user = wp_set_current_user( $id );

		$this->verify_download( "?p=$docID&post_type=document", $tdr->test_file, 'Private, Admin Ugly Permalink' );
		$this->verify_download( get_permalink( $docID ), $tdr->test_file, 'Private, Admin Pretty Permalink' );
		_destroy_user( $id );

	}


	/**
	 * Can the public access a document revision? (no)
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
		wp_set_current_user( 0 );
		wp_cache_flush();


		//public should be denied access to revisions
		$this->verify_cant_download( get_permalink( $revision->ID ), $tdr->test_file, 'Public revision request (pretty)' );
		$this->verify_cant_download( "?p=$docID&post_type=document&revision=1", $tdr->test_file, 'Public revision request (ugly)' );

	}


	/**
	 * Can an admin access a document revision? (yes)
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
		$id = _make_user('administrator');
		wp_set_current_user( $id );

		$this->markTestSkipped();
		$this->verify_download( get_permalink( $revision->ID ), $tdr->test_file, 'Admin revision clean' );
		$this->verify_download( "?p=$docID&post_type=document&revision=1", $tdr->test_file, 'Admin revision ugly' );
		_destroy_user( $id );

	}


	/**
	 * Do we serve the latest version of a document?
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
	 * Does the document archive work?
	 */
	function test_archive() {
		global $wpdr;
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();
		flush_rewrite_rules();
		$this->go_to( get_home_url(null, $wpdr->document_slug() ) );
		$this->assertTrue( is_post_type_archive( 'document' ), 'Couldn\'t access /documents/' );
	}


	/**
	 * Does get_permalink generate the right permalink?
	 */
	function test_permalink() {

		global $wpdr;
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();
		$post = get_post( $docID );
		$permalink = get_bloginfo( 'url' ) . '/' . $wpdr->document_slug() . '/' . date('Y') . '/' . date('m') . '/' . $post->post_name . $wpdr->get_file_type( $docID );
		$this->assertEquals( $permalink, get_permalink( $docID ), 'Bad permalink' );

	}


	/**
	 * Test get_permalink() on a revision
	 */
	function test_revision_permalink() {

		global $wpdr;
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();
		$revisions = $wpdr->get_revisions( $docID );
		$revision = array_pop( $revisions );
		$permalink = get_bloginfo( 'url' ) . '/' . $wpdr->document_slug() . '/' . date('Y') . '/' . date('m') . '/' . get_post( $docID )->post_name . '-revision-1' . $wpdr->get_file_type( $docID );
		$this->assertEquals( $permalink, get_permalink( $revision->ID ), 'Bad revision permalink' );
	}


	/**
	 * Simulate accessing a revision log feed
	 *
	 * @param string $url the URL to try
	 *
	 * @return string the content returned
	 * @throws Exception
	 */
	function simulate_feed( $url = null ) {
		if ( ! $url ) {
			return '';
		}

		global $wpdr;
		flush_rewrite_rules();

		$this->go_to( $url );
		$wpdr->revision_feed_auth();

		if ( _wpdr_is_wp_die() ) {
			return '';
		}

		ob_start();
		global $post;
		try {
			@require( dirname( __DIR__ ) . '/includes/revision-feed.php' );
			$content = ob_get_clean();
		} catch( Exception $e ) {
			$content = ob_get_clean();
			throw ( $e );
		}

		return $content;
	}

	/**
	 * Can the public access a revision log feed?
	 */
	function test_feed_as_unauthenticated() {

		global $wpdr;

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//try to get an un auth'd feed
		$content = $this->simulate_feed( get_permalink( $docID ) . '/feed/' );
		$this->assertFalse( $wpdr->validate_feed_key(), 'not properly validating feed key' );
		$this->assertTrue( _wpdr_is_wp_die(), 'not properly denying access to feeds' );
		$this->assertEquals( 0, substr_count( $content, '<item>' ), 'denied feed leaking items' );

	}


	/**
	 * Can a user with the proper feed key access a feed?
	 */
	function test_feed_as_authorized() {

		global $wpdr;

		define ( 'WP_ADMIN', true );

		$wpdr->admin_init();
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document();

		//try to get an auth'd feed
		$userID = _make_user('administrator');
		$wpdr->admin->generate_new_feed_key( $userID );
		$key = $wpdr->admin->get_feed_key( $userID );

		wp_set_current_user( $userID );
		$content = $this->simulate_feed( add_query_arg( 'key', $key, get_permalink( $docID ) . '/feed/' ) );
		$this->assertTrue( $wpdr->validate_feed_key(), 'not properly validating feed key' );
		$this->assertFalse( _wpdr_is_wp_die(), 'Not properly allowing access to feeds' );
		$this->assertEquals( count( $wpdr->get_revisions( $docID ) ), (int) substr_count( $content, '<item>' ), 'improper feed item count' );
		wp_set_current_user( 0 );
		_destroy_user( $userID );

	}

	/**
	 * Tests that changing the document slug is reflected in permalinks
	 */
	function test_document_slug() {

		global $wp_rewrite;
		$tdr = new WP_Test_Document_Revisions();

		//set new slug
		update_site_option( 'document_slug', 'docs' );

		//add doc and flush
		$docID = $tdr->test_add_document();
		wp_publish_post( $docID );
		wp_cache_flush();

		$this->verify_download( get_permalink( $docID ), $tdr->test_file, 'revised document slug permalink doesn\'t rewrite' );
		$this->assertContains( '/docs/', get_permalink( $docID ), 'revised document slug not in permalink' );

	}

}
