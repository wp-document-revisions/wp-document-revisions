<?php
/**
 * Tests front-end functionality
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

class WP_Test_Document_Front_End extends WP_UnitTestCase {


	/**
	 * SetUp initial settings
	 */
	function setUp() {

		parent::setUp();

		//init user roles
		global $wpdr;
		$wpdr->add_caps();
		_flush_roles();
		$this->user_ids = array();
		wp_set_current_user( 0 );

		//flush cache for good measure
		wp_cache_flush();

	}


	/**
	 * Break down for next test
	 */
	function tearDown() {

		_destroy_uploads();
		parent::tearDown();

	}

	/**
	 * Verify joe public can't access a list of revisions
	 */
	function test_revisions_shortcode_unauthed() {

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();

		$output = do_shortcode( '[document_revisions id="' . $docID . '"]' );
		$this->assertEquals( 0, (int) substr_count( $output, '<li'), 'unauthed revision shortcode' );

	}


	/**
	 * Verify auth'd user can view revision shortcode and can truncate proper count
	 */
	function test_revisions_shortcode() {

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();

		//admin should be able to access
		$id = _make_user('administrator');
		wp_set_current_user( $id );

		$output = do_shortcode( '[document_revisions id="' . $docID . '"]' );
		$this->assertEquals( 2, substr_count( $output, '<li'), 'admin revision shortcode' );

	}


	/**
	 * Tests the document_revisions shortcode with a number=1 limit
	 */
	function test_revision_shortcode_limit() {

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document();

		//admin should be able to access
		$id = _make_user('administrator');
		wp_set_current_user( $id );

		$output = do_shortcode( '[document_revisions number="1" id="' . $docID . '"]' );
		$this->assertEquals( 1, substr_count( $output, '<li'), 'revision shortcode count' );

	}


	/**
	 * tests the documents shortcode
	 */
	function test_document_shortcode() {

		$tdr = new WP_Test_Document_Revisions();

		$docID = $tdr->test_revise_document(); //add a doc w/ revisions
		wp_publish_post( $docID );

		$output = do_shortcode( '[documents]' );
		$this->assertEquals( 1, substr_count( $output, '<li'), 'document shortcode count' );

	}


	/**
	 * Tests the documents shortcode with a workflow state filter
	 */
	function test_document_shortcode_wfs_filter() {

		$tdr = new WP_Test_Document_Revisions();

		$docID = $tdr->test_revise_document(); //add a doc w/ revisions
		wp_publish_post( $docID );

		$docID = $tdr->test_add_document(); //add another doc
		wp_publish_post( $docID );

		//move a doc to another workflow state (default is index 0)
		$terms = get_terms( 'workflow_state', array( 'hide_empty' => false ) );
		wp_set_post_terms( $docID, array( $terms[1]->slug ), 'workflow_state' );
		wp_cache_flush();

		$output = do_shortcode( '[documents workflow_state="' . $terms[1]->slug . '"]' );
		$this->assertEquals( 1, substr_count( $output, '<li'), 'document shortcode filter count' );

	}


	/**
	 * Test documetn shortcode with a post_meta filter
	 */
	function test_document_shortcode_post_meta_filter() {

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document(); //add a doc
		wp_publish_post( $docID );

		$docID = $tdr->test_revise_document(); //add a doc w/ revisions
		wp_publish_post( $docID );

		//give postmeta to a doc
		update_post_meta( $docID, 'test_meta_key', 'test_value' );
		wp_cache_flush();

		$output = do_shortcode( '[documents meta_key="test_meta_key" meta_value="test_value"]' );
		$this->assertEquals( 1, substr_count( $output, '<li'), 'document shortcode filter count' );

	}

	/**
	 * Tests the public get_documents function
	 */
	function test_get_documents() {

		$tdr = new WP_Test_Document_Revisions();

		$docID = $tdr->test_revise_document(); //add a doc
		wp_publish_post( $docID );

		$docID = $tdr->test_add_document(); //add another doc
		wp_publish_post( $docID );

		$this->assertCount( 2, get_documents(), 'get_document() count' );

	}


	/**
	 * Tests that get_documents returns attachments when asked
	 */
	function test_get_documents_returns_attachments() {

		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_add_document(); //add a doc
		wp_publish_post( $docID );

		$docs = get_documents( null, true );
		$doc = array_pop( $docs );

		$this->assertEquals( $doc->post_type, 'attachment', 'get_documents not returning attachments' );

	}


	/**
	 * Tests that get_documents properly filters when asked
	 */
	function test_get_documents_filter() {

		$tdr = new WP_Test_Document_Revisions();

		$tdr->test_add_document(); //add a doc
		$docID = $tdr->test_add_document(); //add another doc
		wp_publish_post( $docID );

		//give postmeta to a doc
		update_post_meta( $docID, 'test_meta_key', 'test_value' );
		wp_cache_flush();

		$docs = get_documents( array( 'test_meta_key' => 'test_value' ) );
		$this->assertCount( 1, $docs, 'get_documents filter count' );

	}


	/**
	 * Tests the get_revisions function
	 */
	function test_get_document_revisions() {
		$tdr = new WP_Test_Document_Revisions();
		$docID = $tdr->test_revise_document(); //add a doc
		$this->assertCount( 2, get_document_revisions( $docID ) );
	}


}
