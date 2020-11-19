<?php
/**
 * Tests front-end functionality.
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Front end tests
 */
class Test_WP_Document_Revisions_Front_End extends WP_UnitTestCase {


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
	 * Verify joe public can't access a list of revisions.
	 */
	public function test_revisions_shortcode_unauthed() {

		$this->consoleLog( 'Test_Front_End - revisions_shortcode_unauthed' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();

		$output = do_shortcode( '[document_revisions id="' . $doc_id . '"]' );
		$this->assertEquals( 0, (int) substr_count( $output, '<li' ), 'unauthed revision shortcode' );

	}


	/**
	 * Verify auth'd user can view revision shortcode and can truncate proper count.
	 */
	public function test_revisions_shortcode() {

		$this->consoleLog( 'Test_Front_End - revisions_shortcode' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();

		// admin should be able to access.
		$id = _make_user( 'administrator' );
		wp_set_current_user( $id );

		$output = do_shortcode( '[document_revisions id="' . $doc_id . '"]' );
		$this->assertEquals( 2, substr_count( $output, '<li' ), 'admin revision shortcode' );
		_destroy_user( $id );

	}

	/**
	 * Verify joe public can't access a block list of revisions.
	 */
	public function test_revisions_block_unauthed() {

		$this->consoleLog( 'Test_Front_End - revisions_block_unauthed' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();

		global $wpdr_fe;

		$atts   = array(
			'id' => $doc_id,
		);
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( $atts );

		$this->assertEquals( 0, (int) substr_count( $output, '<li' ), 'unauthed revision block' );

	}


	/**
	 * Verify auth'd user can view revision block and can truncate proper count.
	 */
	public function test_revisions_block() {

		$this->consoleLog( 'Test_Front_End - revisions_block' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();

		// admin should be able to access.
		$id = _make_user( 'administrator' );
		wp_set_current_user( $id );

		global $wpdr_fe;

		$atts   = array(
			'id' => $doc_id,
		);
		$output = $wpdr_fe->wpdr_revisions_shortcode_display( $atts );

		$this->assertEquals( 2, substr_count( $output, '<li' ), 'admin revision block' );
		_destroy_user( $id );

	}


	/**
	 * Tests the document_revisions shortcode with a number=1 limit.
	 */
	public function test_revision_shortcode_limit() {

		$this->consoleLog( 'Test_Front_End - revision_shortcode_limit' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document();

		// admin should be able to access.
		$id = _make_user( 'administrator' );
		wp_set_current_user( $id );

		$output = do_shortcode( '[document_revisions number="1" id="' . $doc_id . '"]' );
		$this->assertEquals( 1, substr_count( $output, '<li' ), 'revision shortcode count' );
		_destroy_user( $id );

	}


	/**
	 * Tests the documents shortcode.
	 */
	public function test_document_shortcode() {

		$this->consoleLog( 'Test_Front_End - document_shortcode' );

		$tdr = new Test_WP_Document_Revisions();

		$doc_id = $tdr->test_revise_document(); // add a doc w/ revisions.
		wp_publish_post( $doc_id );

		$output = do_shortcode( '[documents]' );
		$this->assertEquals( 1, substr_count( $output, '<li' ), 'document shortcode count' );

	}


	/**
	 * Tests the documents shortcode with a workflow state filter.
	 */
	public function test_document_shortcode_wfs_filter() {

		$this->consoleLog( 'Test_Front_End - document_shortcode_wfs_filter' );

		$tdr = new Test_WP_Document_Revisions();

		$doc_id = $tdr->test_revise_document(); // add a doc w/ revisions.
		wp_publish_post( $doc_id );

		$doc_id = $tdr->test_add_document(); // add another doc.
		wp_publish_post( $doc_id );

		// move a doc to another workflow state (default is index 0).
		$terms = get_terms(
			'workflow_state',
			array(
				'hide_empty' => false,
			)
		);
		wp_set_post_terms( $doc_id, array( $terms[1]->slug ), 'workflow_state' );
		wp_cache_flush();

		$output = do_shortcode( '[documents workflow_state="' . $terms[1]->slug . '"]' );
		$this->assertEquals( 1, substr_count( $output, '<li' ), 'document shortcode filter count' );

	}


	/**
	 * Test document shortcode with a post_meta filter.
	 */
	public function test_document_shortcode_post_meta_filter() {

		$this->consoleLog( 'Test_Front_End - document_shortcode_post_meta_filter' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_add_document(); // add a doc.
		wp_publish_post( $doc_id );

		$doc_id = $tdr->test_revise_document(); // add a doc w/ revisions.
		wp_publish_post( $doc_id );

		// give postmeta to a doc.
		update_post_meta( $doc_id, 'test_meta_key', 'test_value' );
		wp_cache_flush();

		$output = do_shortcode( '[documents meta_key="test_meta_key" meta_value="test_value"]' );
		$this->assertEquals( 1, substr_count( $output, '<li' ), 'document shortcode filter count' );

	}

	/**
	 * Tests the documents block with a workflow state filter. with and without read_document caps.
	 */
	public function test_document_block_wfs_filter() {

		$this->consoleLog( 'Test_Front_End - document_block_wfs_filter' );

		$tdr = new Test_WP_Document_Revisions();

		$doc_id = $tdr->test_revise_document(); // add a doc w/ revisions.
		wp_publish_post( $doc_id );

		$doc_id = $tdr->test_add_document(); // add another doc.
		wp_publish_post( $doc_id );

		// move a doc to another workflow state (default is index 0).
		$terms = get_terms(
			'workflow_state',
			array(
				'hide_empty' => false,
			)
		);
		wp_set_post_terms( $doc_id, array( $terms[1]->slug ), 'workflow_state' );
		wp_cache_flush();

		global $wpdr_fe;

		$atts   = array(
			'taxonomy_0' => 'workflow_state',
			'term_0'     => $terms[1]->term_id,
		);
		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		$this->assertEquals( 1, substr_count( $output, '<li' ), 'document block filter auth' );

		// using document_read capability means no access for an unauthorized use..
		add_filter( 'document_read_uses_read', '__return_false' );
		$output = $wpdr_fe->wpdr_documents_shortcode_display( $atts );

		$this->assertEquals( 1, substr_count( $output, 'not authorized' ), 'document block filter noauth' );
		remove_filter( 'document_read_uses_read', '__return_false' );

	}

	/**
	 * Tests the public get_documents function.
	 */
	public function test_get_documents() {

		$this->consoleLog( 'Test_Front_End - get_documents' );

		$tdr = new Test_WP_Document_Revisions();

		$doc_id = $tdr->test_revise_document(); // add a doc.
		wp_publish_post( $doc_id );

		$doc_id = $tdr->test_add_document(); // add another doc.
		wp_publish_post( $doc_id );

		$this->assertCount( 2, get_documents(), 'get_document() count' );

	}


	/**
	 * Tests that get_documents returns attachments when asked.
	 */
	public function test_get_documents_returns_attachments() {

		$this->consoleLog( 'Test_Front_End - get_documents_returns_attachments' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_add_document(); // add a doc.
		wp_publish_post( $doc_id );

		$docs = get_documents( null, true );
		$doc  = array_pop( $docs );

		$this->assertEquals( $doc->post_type, 'attachment', 'get_documents not returning attachments' );

	}


	/**
	 * Tests that get_documents properly filters when asked.
	 */
	public function test_get_documents_filter() {

		$this->consoleLog( 'Test_Front_End - get_documents_filter' );

		$tdr = new Test_WP_Document_Revisions();

		$tdr->test_add_document(); // add a doc.
		$doc_id = $tdr->test_add_document(); // add another doc.
		wp_publish_post( $doc_id );

		// give postmeta to a doc.
		update_post_meta( $doc_id, 'test_meta_key', 'test_value' );
		wp_cache_flush();

		$docs = get_documents(
			array(
				'test_meta_key' => 'test_value',
			)
		);
		$this->assertCount( 1, $docs, 'get_documents filter count' );

	}


	/**
	 * Tests the get_revisions function.
	 */
	public function test_get_document_revisions() {

		$this->consoleLog( 'Test_Front_End - get_document_revisions' );

		$tdr    = new Test_WP_Document_Revisions();
		$doc_id = $tdr->test_revise_document(); // add a doc.
		$this->assertCount( 2, get_document_revisions( $doc_id ) );
	}

}
