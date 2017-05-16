<?php
/**
 * Verifies basic CRUD operations of WP Document Revisions
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP Document Revisions
 */

class WP_Test_Document_Revisions extends WP_UnitTestCase {

	public $test_file = 'documents/test-file.txt';
	public $test_file2 = 'documents/test-file-2.txt';

	/**
	 * Setup Initial Testing Environment
	 */
	function setUp() {

		global $wpdr;

		parent::setUp();

		//init workflow states
		foreach ( get_terms( 'workflow_state', array( 'hide_empty' => false ) ) as $term )
			wp_delete_term( $term->term_id, 'workflow_state' );

		wp_cache_flush();

		$wpdr->initialize_workflow_states();

		wp_cache_flush();

	}


	/**
	 * If called via rewrites tests
	 */
	function __construct() {
		$this->setUp();
	}

	/**
	 * Make sure plugin is activated
	 */
	function test_activated() {

		$this->assertTrue( class_exists( 'Document_Revisions' ), 'Document_Revisions class not defined' );

	}


	/**
	 * Post type is properly registered
	 */
	function test_post_type_exists() {

		$this->assertTrue( post_type_exists( 'document' ), 'Document post type does not exist' );

	}


	/**
	 * Workflow states exists and are initialized
	 */
	function test_workflow_states_exist() {

		$terms = get_terms( 'workflow_state', array( 'hide_empty' => false ) );
		$this->assertFalse( is_wp_error( $terms ), 'Workflow State taxonomy does not exist' );
		$this->assertCount( 4, $terms, 'Initial Workflow States not properly registered' );

	}


	/**
	 * Pretend to upload a file
	 * @param int $postID the parent post
	 * @param string $file relative URL to file to "upload"
	 * @return int the attachment ID
	 */
	function spoof_upload( $postID, $file ) {

		global $wpdr;
		$file = dirname( __FILE__ ) . '/' . $file;
		$_POST['post_id'] = $postID;
		$upload_dir = wp_upload_dir();

		$wp_filetype = wp_check_filetype( basename( $file ), null );
		$file_array = apply_filters( 'wp_handle_upload_prefilter', array(
				'name' => basename( $file ),
				'tmp_name' => $file,
				'type' => $wp_filetype['type'],
				'size' => 1,
			)
		);

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		// If the directory is not available, the upload does not succeed.
		if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
			return false;
		}

		//copy temp test file into wp-uploads
		copy( $file, $upload_dir['path'] . '/' . $file_array['name'] );

		$attach_id = wp_insert_attachment( $attachment, $upload_dir['path'] . '/' . $file_array['name'], $postID );

		$this->assertGreaterThan( 0, $attach_id, 'Cannot attach file to document' );

		return $attach_id;

	}


	/**
	 * Verify we can add documents
	 * @return int document id
	 */
	function test_add_document() {

		global $wpdr;
		global $wpdb;

		$post = array(
			'post_title' => 'Test Document - ' . time(),
			'post_status' => 'private',
			'post_content' => '',
			'post_excerpt' => 'Test Upload',
			'post_type' => 'document',
		);

		//insert post
		$postID = wp_insert_post( $post, true );
		$this->assertFalse( is_wp_error( $postID ), 'Failed inserting new document' );

		//assign workflow state
		$terms = get_terms( 'workflow_state', array( 'hide_empty' => false ) );

		if ( empty( $terms ) )
			WP_Test_Document_Revisions::setUp();

		$terms = wp_set_post_terms( $postID, $terms[0]->slug, 'workflow_state' );
		$this->assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );

		$attach_id = $this->spoof_upload( $postID, $this->test_file );

		//store attachment ID as post content without creating a revision
		$result = $wpdb->update( $wpdb->posts, array( 'post_content' => $attach_id ), array( 'ID' => $postID ) );
		wp_cache_flush();

		$this->assertGreaterThan( 0, $result, 'Cannot update document post_content with attachment ID' );

		$this->assertEquals( $attach_id, $wpdr->get_latest_revision( $postID )->post_content );

		$this->verify_attachment_matches_file( $postID, $this->test_file, 'Initial Upload' );

		return $postID;

	}


	/**
	 * Try to revise an existing document (creates that document first)
	 * @return int the revision ID
	 */
	function test_revise_document() {

		$docID = WP_Test_Document_Revisions::test_add_document();

		$attach_id = $this->spoof_upload( $docID, $this->test_file2 );

		$post = array( 'ID' => $docID, 'post_content' => $attach_id, 'post_excerpt' => 'revised' );
		$result = wp_update_post( $post );
		wp_cache_flush();

		$this->assertGreaterThan( 0, $result, 'Cannot update document post_content with revised attachment ID' );
		$this->verify_attachment_matches_file( $result, $this->test_file2, 'revise document' );

		return $result;

	}


	/**
	 * Make sure a file is properly uploaded and attached
	 * @param int $postID the ID of the parent post
	 * @param string $file relative url to file
	 * @param string $msg message to display on failure
	 */
	function verify_attachment_matches_file( $postID = null, $file = null, $msg = null ) {

		if ( !$postID )
			return;

		$post = get_post( $postID );
		$attachment = get_attached_file( $post->post_content );

		$this->assertFileEquals( dirname( __FILE__ ) . '/' . $file, $attachment, "Uploaded files don\'t match original ($msg)");

	}


	/**
	 * Validate teh get_attachments function are few different ways
	 */
	function test_get_attachments() {

		global $wpdr;

		$docID = $this->test_revise_document();

		//grab an attachment
		$attachments = $wpdr->get_attachments( $docID );
		$attachment = end(  $attachments );

		//grab a revision
		$revisions = $wpdr->get_revisions( $docID );
		$revision = end(  $revisions );

		//get as postID
		$this->assertCount( 2, $attachments, 'Bad attachment count via get_attachments as postID' );

		//get as Object
		$this->assertCount( 2, $wpdr->get_attachments( get_post( $docID ) ), 'Bad attachment count via get_attachments as Object' );

		//get as a revision
		$this->assertCount( 2, $wpdr->get_attachments( $revision->ID ), 'Bad attachment count via get_attachments as revisionID' );

		//get as attachment
		$this->assertCount( 2, $wpdr->get_attachments( $attachment->ID ), 'Bad attachment count via get_attachments as attachmentID' );

	}


	/**
	 * Verify the get_file_Type function works
	 */
	function test_file_type() {

		global $wpdr;

		$docID = $this->test_add_document();

		//grab an attachment
		$attachments = $wpdr->get_attachments( $docID );
		$attachment = end(  $attachments );

		$post = get_post( $docID );

		global $wpdr;
		$this->assertEquals( '.txt', $wpdr->get_file_type( $docID ), 'Didn\'t detect filetype via document ID' );
		$this->assertEquals( '.txt', $wpdr->get_file_type( $attachment->ID ), 'Didn\'t detect filetype via attachment ID' );

	}


	/**
	 * Make sure get_revisions() works
	 */
	function test_get_revisions() {
		global $wpdr;

		$docID = $this->test_revise_document();

		$this->assertEquals( 2, count( $wpdr->get_revisions( $docID ) ) );

	}


	/**
	 * Tets get_revision_number()
	 */
	function test_get_revision_number() {

		global $wpdr;

		$docID = $this->test_revise_document();
		$revisions = $wpdr->get_revisions( $docID );
		$last = end(  $revisions );
		$this->assertEquals( 1, $wpdr->get_revision_number( $last->ID ) );
		$this->assertEquals( $last->ID, $wpdr->get_revision_id( 1, $docID ) );

	}


	/**
	 * Tests varify_post_type() with the various ways used throughout
	 */
	function test_verify_post_type() {
		global $wpdr;

		$docID = $this->test_add_document();

		$_GET['post_type'] = 'document';
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via explicit' );
		unset( $_GET['post_type'] );

		$_GET['post'] = $docID;
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via get' );
		unset( $_GET['post'] );

		$_REQUEST['post_id'] = $docID;
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via request (post_id)' );
		unset( $_REQUEST['post_id'] );

		global $post;
		$post = get_post( $docID );
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via global $post' );
		unset( $post );

		$this->assertTrue( $wpdr->verify_post_type( $docID ) );
	}

}
