<?php
/**
 * Verifies basic CRUD operations of WP Document Revisions
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP Document Revisions
 */

/**
 * Main WP Document Revisions tests
 */
class Test_WP_Document_Revisions extends WP_UnitTestCase {

	/**
	 * Path to test file
	 *
	 * @var $test_file
	 */
	public $test_file = 'documents/test-file.txt';

	/**
	 * Path to another test file
	 *
	 * @var $test-file2
	 */
	public $test_file2 = 'documents/test-file-2.txt';

	/**
	 * Setup Initial Testing Environment
	 */
	public function setUp() {

		global $wpdr;

		parent::setUp();

		// init workflow states.
		foreach ( get_terms(
			'workflow_state',
			array(
				'hide_empty' => false,
			)
		) as $term ) {
			wp_delete_term( $term->term_id, 'workflow_state' );
		}

		wp_cache_flush();

		$wpdr->initialize_workflow_states();

		wp_cache_flush();

	}


	/**
	 * If called via rewrites tests.
	 */
	public function __construct() {
		$this->setUp();
	}

	/**
	 * Make sure plugin is activated.
	 */
	public function test_activated() {

		$this->assertTrue( class_exists( 'WP_Document_Revisions' ), 'Document_Revisions class not defined' );

	}


	/**
	 * Post type is properly registered.
	 */
	public function test_post_type_exists() {

		$this->assertTrue( post_type_exists( 'document' ), 'Document post type does not exist' );

	}


	/**
	 * Workflow states exists and are initialized.
	 */
	public function test_workflow_states_exist() {

		$terms = get_terms(
			'workflow_state',
			array(
				'hide_empty' => false,
			)
		);
		$this->assertFalse( is_wp_error( $terms ), 'Workflow State taxonomy does not exist' );
		$this->assertCount( 4, $terms, 'Initial Workflow States not properly registered' );

	}


	/**
	 * Pretend to upload a file.
	 *
	 * @param int    $post_id the parent post.
	 * @param string $file relative URL to file to "upload".
	 * @return int the attachment ID
	 */
	public function spoof_upload( $post_id, $file ) {

		global $wpdr;
		$file             = dirname( __FILE__ ) . '/' . $file;
		$_POST['post_id'] = $post_id;
		$upload_dir       = wp_upload_dir();

		$wp_filetype = wp_check_filetype( basename( $file ), null );
		$file_array  = apply_filters(
			'wp_handle_upload_prefilter',
			array(
				'name'     => basename( $file ),
				'tmp_name' => $file,
				'type'     => $wp_filetype['type'],
				'size'     => 1,
			)
		);

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// If the directory is not available, the upload does not succeed.
		if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
			return false;
		}

		// copy temp test file into wp-uploads.
		copy( $file, $upload_dir['path'] . '/' . $file_array['name'] );

		$attach_id = wp_insert_attachment( $attachment, $upload_dir['path'] . '/' . $file_array['name'], $post_id );

		$this->assertGreaterThan( 0, $attach_id, 'Cannot attach file to document' );

		return $attach_id;

	}


	/**
	 * Verify we can add documents.
	 *
	 * @return int document id
	 */
	public function test_add_document() {
		global $wpdr;
		global $wpdb;

		$doc = array(
			'post_title'   => 'Test Document - ' . time(),
			'post_status'  => 'private',
			'post_content' => '',
			'post_excerpt' => 'Test Upload',
			'post_type'    => 'document',
		);

		// insert post.
		$post_id = wp_insert_post( $doc, true );
		$this->assertFalse( is_wp_error( $post_id ), 'Failed inserting new document' );

		// assign workflow state.
		$terms = get_terms(
			'workflow_state',
			array(
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) ) {
			self::setUp();
		}

		$terms = wp_set_post_terms( $post_id, $terms[0]->slug, 'workflow_state' );
		$this->assertTrue( is_array( $terms ), 'Cannot assign workflow states to document' );

		$attach_id = $this->spoof_upload( $post_id, $this->test_file );

		// store attachment ID as post content without creating a revision.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			$wpdb->posts,
			array(
				'post_content' => $attach_id,
			),
			array(
				'ID' => $post_id,
			)
		);
		wp_cache_flush();

		$this->assertGreaterThan( 0, $result, 'Cannot update document post_content with attachment ID' );

		$this->assertEquals( $attach_id, $wpdr->get_latest_revision( $post_id )->post_content );

		$this->verify_attachment_matches_file( $post_id, $this->test_file, 'Initial Upload' );

		return $post_id;

	}


	/**
	 * Try to revise an existing document (creates that document first).
	 *
	 * @return int the revision ID
	 */
	public function test_revise_document() {

		$doc_id = self::test_add_document();

		$attach_id = $this->spoof_upload( $doc_id, $this->test_file2 );

		$doc    = array(
			'ID'           => $doc_id,
			'post_content' => $attach_id,
			'post_excerpt' => 'revised',
		);
		$result = wp_update_post( $doc );
		wp_cache_flush();

		$this->assertGreaterThan( 0, $result, 'Cannot update document post_content with revised attachment ID' );
		$this->verify_attachment_matches_file( $result, $this->test_file2, 'revise document' );

		return $result;

	}


	/**
	 * Make sure a file is properly uploaded and attached.
	 *
	 * @param int    $post_id the ID of the parent post.
	 * @param string $file relative url to file.
	 * @param string $msg message to display on failure.
	 */
	public function verify_attachment_matches_file( $post_id = null, $file = null, $msg = null ) {

		if ( ! $post_id ) {
			return;
		}

		$doc        = get_post( $post_id );
		$attachment = get_attached_file( $doc->post_content );

		$this->assertFileEquals( dirname( __FILE__ ) . '/' . $file, $attachment, "Uploaded files don\'t match original ($msg)" );

	}


	/**
	 * Validate the get_attachments function are few different ways.
	 */
	public function test_get_attachments() {

		global $wpdr;

		$doc_id = $this->test_revise_document();

		// grab an attachment.
		$attachments = $wpdr->get_attachments( $doc_id );
		$attachment  = end( $attachments );

		// grab a revision.
		$revisions = $wpdr->get_revisions( $doc_id );
		$revision  = end( $revisions );

		// get as postID.
		$this->assertCount( 2, $attachments, 'Bad attachment count via get_attachments as postID' );

		// get as Object.
		$this->assertCount( 2, $wpdr->get_attachments( get_post( $doc_id ) ), 'Bad attachment count via get_attachments as Object' );

		// get as a revision.
		$this->assertCount( 2, $wpdr->get_attachments( $revision->ID ), 'Bad attachment count via get_attachments as revisionID' );

		// get as attachment.
		$this->assertCount( 2, $wpdr->get_attachments( $attachment->ID ), 'Bad attachment count via get_attachments as attachmentID' );

	}


	/**
	 * Verify the get_file_Type function works.
	 */
	public function test_file_type() {

		global $wpdr;

		$doc_id = $this->test_add_document();

		// grab an attachment.
		$attachments = $wpdr->get_attachments( $doc_id );
		$attachment  = end( $attachments );

		global $wpdr;
		$this->assertEquals( '.txt', $wpdr->get_file_type( $doc_id ), 'Didn\'t detect filetype via document ID' );
		$this->assertEquals( '.txt', $wpdr->get_file_type( $attachment->ID ), 'Didn\'t detect filetype via attachment ID' );

	}


	/**
	 * Make sure get_revisions() works.
	 */
	public function test_get_revisions() {
		global $wpdr;

		$doc_id = $this->test_revise_document();

		$this->assertEquals( 2, count( $wpdr->get_revisions( $doc_id ) ) );

	}


	/**
	 * Test get_revision_number().
	 */
	public function test_get_revision_number() {

		global $wpdr;

		$doc_id    = $this->test_revise_document();
		$revisions = $wpdr->get_revisions( $doc_id );
		$last      = end( $revisions );
		$this->assertEquals( 1, $wpdr->get_revision_number( $last->ID ) );
		$this->assertEquals( $last->ID, $wpdr->get_revision_id( 1, $doc_id ) );

	}


	/**
	 * Tests varify_post_type() with the various ways used throughout
	 */
	public function test_verify_post_type() {
		global $wpdr;

		$doc_id = $this->test_add_document();

		$_GET['post_type'] = 'document';
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via explicit' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post_type'] );

		$_GET['post'] = $doc_id;
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via get' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post'] );

		$_REQUEST['post_id'] = $doc_id;
		$this->assertTrue( $wpdr->verify_post_type(), 'verify post type via request (post_id)' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_REQUEST['post_id'] );

		// phpcs:disable WordPress.Variables.GlobalVariables.OverrideProhibited
		global $post;
		$post = get_post( $doc_id );
		// phpcs:enable WordPress.Variables.GlobalVariables.OverrideProhibited

		$this->assertTrue( $wpdr->verify_post_type( $post ), 'verify post type via global $post' );
		unset( $post );

		$this->assertTrue( $wpdr->verify_post_type( $doc_id ) );
	}

}
