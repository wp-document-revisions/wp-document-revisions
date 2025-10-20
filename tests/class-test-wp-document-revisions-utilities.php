<?php
/**
 * Tests for utility and helper functions.
 *
 * @author GitHub Copilot
 * @package WP_Document_Revisions
 */

/**
 * Utility function tests for WP Document Revisions
 */
class Test_WP_Document_Revisions_Utilities extends Test_Common_WPDR {

	/**
	 * Test extract_document_id with various input formats.
	 */
	public function test_extract_document_id_various_formats() {
		global $wpdr;

		// Test with numeric string.
		$result = $wpdr->extract_document_id( '123' );
		self::assertEquals( 123, $result, 'Should extract document ID from numeric string' );

		// Test with formatted document ID (<!-- WPDR 123 -->).
		$result = $wpdr->extract_document_id( '<!-- WPDR 456 -->' );
		self::assertEquals( 456, $result, 'Should extract document ID from formatted comment string' );

		// Test with empty string.
		$result = $wpdr->extract_document_id( '' );
		self::assertFalse( $result, 'Should return false for empty string' );

		// Test with non-numeric string.
		$result = $wpdr->extract_document_id( 'not a number' );
		self::assertFalse( $result, 'Should return false for non-numeric string' );

		// Test with null.
		$result = $wpdr->extract_document_id( null );
		self::assertFalse( $result, 'Should return false for null input' );
	}

	/**
	 * Test format_doc_id function.
	 */
	public function test_format_doc_id() {
		global $wpdr;

		// Test with valid ID.
		$result = $wpdr->format_doc_id( 123 );
		self::assertIsString( $result, 'format_doc_id should return a string' );
		self::assertStringContainsString( '123', $result, 'Formatted ID should contain the document ID' );
		self::assertStringContainsString( 'WPDR', $result, 'Formatted ID should contain WPDR marker' );

		// Test with zero.
		$result = $wpdr->format_doc_id( 0 );
		self::assertIsString( $result, 'format_doc_id should return string even for ID 0' );

		// Test round-trip: format then extract.
		$original_id = 789;
		$formatted   = $wpdr->format_doc_id( $original_id );
		$extracted   = $wpdr->extract_document_id( $formatted );
		self::assertEquals( $original_id, $extracted, 'Should be able to extract same ID after formatting' );
	}

	/**
	 * Test get_document function with different input types.
	 */
	public function test_get_document_input_types() {
		global $wpdr;

		// Create a test document with an attachment.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		// Add an attachment to the document.
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		// Test with integer ID.
		$result = $wpdr->get_document( $doc_id );
		self::assertInstanceOf( WP_Post::class, $result, 'get_document should return WP_Post for valid document ID with attachment' );
		self::assertEquals( 'attachment', $result->post_type, 'get_document should return attachment post type' );

		// Test with WP_Post object.
		$doc    = get_post( $doc_id );
		$result = $wpdr->get_document( $doc );
		self::assertInstanceOf( WP_Post::class, $result, 'get_document should accept WP_Post object' );

		// Test with non-existent ID.
		$result = $wpdr->get_document( 999999 );
		self::assertFalse( $result, 'get_document should return false for non-existent document ID' );

		// Test with document without attachment.
		$doc_no_attach = self::factory()->post->create(
			array(
				'post_title'  => 'Document Without Attachment',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		$result        = $wpdr->get_document( $doc_no_attach );
		self::assertFalse( $result, 'get_document should return false for document without attachment' );

		// Test with invalid post type.
		$regular_post = self::factory()->post->create(
			array(
				'post_title' => 'Regular Post',
				'post_type'  => 'post',
			)
		);
		$result       = $wpdr->get_document( $regular_post );
		self::assertFalse( $result, 'get_document should return false for non-document post type' );
	}

	/**
	 * Test filename_rewrite with various filenames.
	 */
	public function test_filename_rewrite_special_cases() {
		global $wpdr;

		// Create a test document for context.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		$_POST['post_id'] = $doc_id;
		$_POST['type']    = 'file';
		$wpdr::$doc_image = false;

		// Test with simple filename.
		$result = $wpdr->filename_rewrite( array( 'name' => 'simple.txt' ) );
		self::assertIsArray( $result, 'filename_rewrite should return array' );
		self::assertArrayHasKey( 'name', $result, 'Result should have name key' );

		// Test with spaces.
		$result = $wpdr->filename_rewrite( array( 'name' => 'file with spaces.doc' ) );
		self::assertIsArray( $result, 'Should handle filenames with spaces' );
		self::assertNotEmpty( $result['name'], 'Rewritten filename should not be empty' );

		// Test with Unicode characters.
		$result = $wpdr->filename_rewrite( array( 'name' => 'tëst-dócumént.pdf' ) );
		self::assertIsArray( $result, 'Should handle Unicode characters' );
		self::assertNotEmpty( $result['name'], 'Rewritten filename should not be empty' );

		// Test with multiple dots.
		$result = $wpdr->filename_rewrite( array( 'name' => 'file.backup.v2.txt' ) );
		self::assertIsArray( $result, 'Should handle multiple dots in filename' );
		self::assertNotEmpty( $result['name'], 'Rewritten filename should not be empty' );

		// Clean up.
		unset( $_POST['post_id'], $_POST['type'] );
	}

	/**
	 * Test get_latest_revision function.
	 */
	public function test_get_latest_revision_edge_cases() {
		global $wpdr;

		// Create document with attachment (required for get_latest_revision to work).
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Document With Attachment',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		// Add an attachment to create a revision.
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		$result = $wpdr->get_latest_revision( $doc_id );
		self::assertInstanceOf( WP_Post::class, $result, 'get_latest_revision should return a revision for document with attachment' );

		// Test with document without attachment - should return false.
		$doc_no_attach = self::factory()->post->create(
			array(
				'post_title'  => 'Document Without Attachment',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		$result = $wpdr->get_latest_revision( $doc_no_attach );
		self::assertFalse( $result, 'get_latest_revision should return false for document without attachment' );

		// Test with non-existent document.
		$result = $wpdr->get_latest_revision( 999999 );
		self::assertFalse( $result, 'get_latest_revision should return false for non-existent document' );

		// Test with null input.
		$result = $wpdr->get_latest_revision( null );
		self::assertFalse( $result, 'get_latest_revision should handle null input gracefully' );
	}

	/**
	 * Test is_locked function.
	 */
	public function test_is_locked_function() {
		global $wpdr;

		// Create a test document.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Lock Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		// Document should not be locked initially.
		$result = $wpdr->is_locked( $doc_id );
		self::assertFalse( $result, 'New document should not be locked' );

		// Test with WP_Post object.
		$doc    = get_post( $doc_id );
		$result = $wpdr->is_locked( $doc );
		self::assertFalse( $result, 'is_locked should accept WP_Post object' );

		// Test with non-existent document.
		$result = $wpdr->is_locked( 999999 );
		self::assertFalse( $result, 'is_locked should return false for non-existent document' );
	}

	/**
	 * Test get_documents with various filters.
	 */
	public function test_get_documents_filtering() {
		global $wpdr;

		// Create some test documents with attachments (required for get_documents to return them).
		$pub_doc = self::factory()->post->create(
			array(
				'post_title'  => 'Public Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $pub_doc, self::$test_file );

		$draft_doc = self::factory()->post->create(
			array(
				'post_title'  => 'Draft Document',
				'post_type'   => 'document',
				'post_status' => 'draft',
			)
		);
		self::add_document_attachment( self::factory(), $draft_doc, self::$test_file );

		// Get all documents.
		$all_docs = $wpdr->get_documents();
		self::assertIsArray( $all_docs, 'get_documents should return an array' );
		self::assertNotEmpty( $all_docs, 'get_documents should return documents with attachments' );

		// Verify each returned item is a WP_Post.
		foreach ( $all_docs as $doc ) {
			self::assertInstanceOf( WP_Post::class, $doc, 'Each item should be a WP_Post object' );
			// Note: get_documents returns revisions, not the document post itself.
			self::assertContains( $doc->post_type, array( 'document', 'revision' ), 'Each item should be a document or revision' );
		}
	}

	/**
	 * Test verify_post_type with various scenarios.
	 */
	public function test_verify_post_type_scenarios() {
		global $wpdr;

		// Create a document.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		// Test with direct post ID.
		$result = $wpdr->verify_post_type( $doc_id );
		self::assertTrue( $result, 'verify_post_type should return true for document post type' );

		// Test with WP_Post object.
		$doc    = get_post( $doc_id );
		$result = $wpdr->verify_post_type( $doc );
		self::assertTrue( $result, 'verify_post_type should accept WP_Post object' );

		// Test with regular post.
		$regular_post = self::factory()->post->create(
			array(
				'post_title' => 'Regular Post',
				'post_type'  => 'post',
			)
		);
		$result       = $wpdr->verify_post_type( $regular_post );
		self::assertFalse( $result, 'verify_post_type should return false for non-document post types' );

		// Test with page.
		$page   = self::factory()->post->create(
			array(
				'post_title' => 'Test Page',
				'post_type'  => 'page',
			)
		);
		$result = $wpdr->verify_post_type( $page );
		self::assertFalse( $result, 'verify_post_type should return false for page post type' );
	}
}
