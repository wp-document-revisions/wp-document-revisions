<?php
/**
 * Tests for edge cases and improved coverage.
 *
 * @author GitHub Copilot
 * @package WP_Document_Revisions
 */

/**
 * Edge case tests for WP Document Revisions
 */
class Test_WP_Document_Revisions_Edge_Cases extends Test_Common_WPDR {

	/**
	 * Test get_revisions with non-existent post ID.
	 */
	public function test_get_revisions_with_invalid_post_id() {
		global $wpdr;

		// Test with non-existent post ID.
		$revisions = $wpdr->get_revisions( 999999 );
		self::assertEmpty( $revisions, 'get_revisions should return empty array for non-existent post ID' );

		// Test with zero.
		$revisions = $wpdr->get_revisions( 0 );
		self::assertEmpty( $revisions, 'get_revisions should return empty array for post ID of 0' );

		// Test with negative number.
		$revisions = $wpdr->get_revisions( -1 );
		self::assertEmpty( $revisions, 'get_revisions should return empty array for negative post ID' );
	}

	/**
	 * Test get_attachments with non-document post type.
	 */
	public function test_get_attachments_with_non_document_post() {
		global $wpdr;

		// Create a regular post (not a document).
		$regular_post_id = self::factory()->post->create(
			array(
				'post_title'  => 'Regular Post',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$attachments = $wpdr->get_attachments( $regular_post_id );
		self::assertEmpty( $attachments, 'get_attachments should return empty array for non-document post types' );
	}

	/**
	 * Test get_file_type with document without attachment.
	 */
	public function test_get_file_type_without_attachment() {
		global $wpdr;

		// Create a document without attachment.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Document Without Attachment',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		$file_type = $wpdr->get_file_type( $doc_id );
		self::assertEmpty( $file_type, 'get_file_type should return empty string for document without attachment' );
	}

	/**
	 * Test verify_post_type with various invalid inputs.
	 */
	public function test_verify_post_type_with_invalid_inputs() {
		global $wpdr;

		// Clear any existing superglobals.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post'], $_GET['post_type'], $_REQUEST['post_id'] );

		// Test with non-existent post ID.
		$result = $wpdr->verify_post_type( 999999 );
		self::assertFalse( $result, 'verify_post_type should return false for non-existent post ID' );

		// Test with null.
		$result = $wpdr->verify_post_type( null );
		self::assertFalse( $result, 'verify_post_type should return false for null input' );
	}

	/**
	 * Test get_revision_number with invalid revision.
	 */
	public function test_get_revision_number_with_invalid_revision() {
		global $wpdr;

		// Test with non-existent revision ID.
		$result = $wpdr->get_revision_number( 999999 );
		self::assertFalse( $result, 'get_revision_number should return false for non-existent revision ID' );

		// Test with zero.
		$result = $wpdr->get_revision_number( 0 );
		self::assertFalse( $result, 'get_revision_number should return false for revision ID of 0' );
	}

	/**
	 * Test get_revision_id with invalid inputs.
	 */
	public function test_get_revision_id_with_invalid_inputs() {
		global $wpdr;

		// Test with non-existent document.
		$result = $wpdr->get_revision_id( 1, 999999 );
		self::assertFalse( $result, 'get_revision_id should return false for non-existent document' );

		// Test with invalid revision number.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		$result = $wpdr->get_revision_id( 999, $doc_id );
		self::assertFalse( $result, 'get_revision_id should return false for non-existent revision number' );
	}

	/**
	 * Test that empty document titles are handled gracefully.
	 */
	public function test_document_with_empty_title() {
		// Create a document with empty title.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => '',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		$doc = get_post( $doc_id );
		self::assertNotNull( $doc, 'Document with empty title should be created successfully' );
		self::assertEquals( 'document', $doc->post_type, 'Post type should be document' );
	}

	/**
	 * Test document creation with very long title.
	 */
	public function test_document_with_long_title() {
		// Create a document with very long title (255+ characters).
		$long_title = str_repeat( 'a', 300 );
		$doc_id     = self::factory()->post->create(
			array(
				'post_title'  => $long_title,
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		$doc = get_post( $doc_id );
		self::assertNotNull( $doc, 'Document with long title should be created successfully' );
		self::assertNotEmpty( $doc->post_title, 'Document title should not be empty' );
	}

	/**
	 * Test get_documents with various status filters.
	 */
	public function test_get_documents_with_status_filters() {
		global $wpdr;

		// Create documents with different statuses and attachments.
		$published_doc = self::factory()->post->create(
			array(
				'post_title'  => 'Published Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $published_doc, self::$test_file );

		$draft_doc = self::factory()->post->create(
			array(
				'post_title'  => 'Draft Document',
				'post_type'   => 'document',
				'post_status' => 'draft',
			)
		);
		self::add_document_attachment( self::factory(), $draft_doc, self::$test_file );

		$private_doc = self::factory()->post->create(
			array(
				'post_title'  => 'Private Document',
				'post_type'   => 'document',
				'post_status' => 'private',
			)
		);
		self::add_document_attachment( self::factory(), $private_doc, self::$test_file );

		// Get all documents (should respect current user's permissions).
		$all_docs = $wpdr->get_documents();
		self::assertTrue( is_array( $all_docs ), 'get_documents should return an array' );
		self::assertNotEmpty( $all_docs, 'get_documents should return documents with attachments' );
	}

	/**
	 * Test attachment handling with special characters in filename.
	 */
	public function test_attachment_with_special_characters() {
		global $wpdr;

		// Create a document.
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Document with Special Chars',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);

		// The filename_rewrite function should handle special characters.
		$filename = array( 'name' => 'test file with spaces & special.txt' );
		$result   = $wpdr->filename_rewrite( $filename );

		self::assertTrue( is_array( $result ), 'filename_rewrite should return an array' );
		self::assertArrayHasKey( 'name', $result, 'Result should have name key' );
		self::assertNotEmpty( $result['name'], 'Rewritten filename should not be empty' );
	}

	/**
	 * Test update_post_slug_field authorization check - user without edit_document capability.
	 */
	public function test_update_post_slug_field_unauthorized() {
		global $wpdr;

		// Create an editor user who will have edit_document capability.
		$editor_user_id = self::factory()->user->create(
			array(
				'user_nicename' => 'Editor',
				'role'          => 'editor',
			)
		);

		// Create a subscriber user who will NOT have edit_document capability.
		$subscriber_user_id = self::factory()->user->create(
			array(
				'user_nicename' => 'Subscriber',
				'role'          => 'subscriber',
			)
		);

		// Flush roles to ensure capabilities are set correctly.
		self::flush_roles();

		// Create a document as editor.
		wp_set_current_user( $editor_user_id );
		wp_cache_flush();

		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Document for Slug Update',
				'post_type'   => 'document',
				'post_status' => 'publish',
				'post_author' => $editor_user_id,
			)
		);

		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		// Store the original slug before attempting unauthorized modification.
		$original_doc  = get_post( $doc_id );
		$original_slug = $original_doc->post_name;

		// Switch to subscriber user who doesn't have edit_document capability.
		wp_set_current_user( $subscriber_user_id );
		wp_cache_flush();

		// Set up AJAX context so check_ajax_referer uses wp_die() instead of die().
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Override the AJAX die handler to use the standard test die handler.
		add_filter( 'wp_die_ajax_handler', '_wpdr_die_handler_filter' );

		// Create nonce for the subscriber user.
		$nonce = wp_create_nonce( 'samplepermalink' );

		// Set up $_POST and $_REQUEST variables needed for the function.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_POST['post_id']                 = $doc_id;
		$_POST['new_title']               = 'New Title';
		$_POST['new_slug']                = 'new-slug';
		$_POST['samplepermalinknonce']    = $nonce;
		$_REQUEST['samplepermalinknonce'] = $nonce;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Call the function and expect WPDieException due to unauthorized access.
		$exception = null;
		try {
			ob_start();
			$wpdr->update_post_slug_field();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// Should fail with WPDieException because subscriber doesn't have edit_document capability.
		self::assertNotNull( $exception, 'Expected WPDieException for unauthorized user' );

		// Verify the exception message contains 'Not authorized' to ensure correct authorization check failed.
		self::assertStringContainsString( 'Not authorized', $exception->getMessage(), 'Exception message should indicate authorization failure' );

		// Verify the document slug was NOT modified.
		$doc_after = get_post( $doc_id );
		self::assertEquals( $original_slug, $doc_after->post_name, 'Document slug should not have been modified by unauthorized user' );

		// Clean up.
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', '_wpdr_die_handler_filter' );
		unset( $_POST['post_id'], $_POST['new_title'], $_POST['new_slug'], $_POST['samplepermalinknonce'], $_REQUEST['samplepermalinknonce'] );
	}

	/**
	 * Test update_post_slug_field authorization check - user with edit_document capability.
	 */
	public function test_update_post_slug_field_authorized() {
		global $wpdr;

		// Create an editor user who will have edit_document capability.
		$editor_user_id = self::factory()->user->create(
			array(
				'user_nicename' => 'EditorAuth',
				'role'          => 'editor',
			)
		);

		// Flush roles to ensure capabilities are set correctly.
		self::flush_roles();

		// Create a document as editor.
		wp_set_current_user( $editor_user_id );
		wp_cache_flush();

		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Test Document for Authorized Slug Update',
				'post_type'   => 'document',
				'post_status' => 'publish',
				'post_author' => $editor_user_id,
			)
		);

		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		// Set up AJAX context so check_ajax_referer uses wp_die() instead of die().
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Override the AJAX die handler to use the standard test die handler.
		add_filter( 'wp_die_ajax_handler', '_wpdr_die_handler_filter' );

		// Create nonce for the editor user.
		$nonce = wp_create_nonce( 'samplepermalink' );

		// Set up $_POST and $_REQUEST variables needed for the function.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$_POST['post_id']                 = $doc_id;
		$_POST['new_title']               = 'New Title';
		$_POST['new_slug']                = 'new-authorized-slug';
		$_POST['samplepermalinknonce']    = $nonce;
		$_REQUEST['samplepermalinknonce'] = $nonce;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Call the function - should proceed without WPDieException for authorization.
		// Note: The function does call wp_die() at the end to output the permalink HTML,
		// but this is different from the authorization wp_die().
		$exception = null;
		try {
			ob_start();
			$wpdr->update_post_slug_field();
			ob_end_clean();
		} catch ( WPDieException $e ) {
			$exception = $e;
			ob_end_clean();
		}

		// The function calls wp_die() at the end to output HTML, so we expect an exception.
		// But the message should NOT be "Not authorized" - it should be the permalink HTML.
		if ( null !== $exception ) {
			self::assertStringNotContainsString(
				'Not authorized',
				$exception->getMessage(),
				'Authorized user should not get "Not authorized" error'
			);
		}

		// Verify the slug was actually updated.
		$updated_doc = get_post( $doc_id );
		self::assertEquals( 'new-authorized-slug', $updated_doc->post_name, 'Slug should have been updated' );

		// Clean up.
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', '_wpdr_die_handler_filter' );
		unset( $_POST['post_id'], $_POST['new_title'], $_POST['new_slug'], $_POST['samplepermalinknonce'], $_REQUEST['samplepermalinknonce'] );
	}
}
