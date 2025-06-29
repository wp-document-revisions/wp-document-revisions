<?php
/**
 * Tests Revisions Limits.
 *
 * @author Copilot.
 * @package WP_Document_Revisions
 */

/**
 * Test case to ensure the manage_document_revisions_limit method works correctly
 * in the main class and handles different scenarios properly.
 */
class WP_Document_Revisions_Revision_Limit_Test extends WP_UnitTestCase {

	/**
	 * Test document with ID
	 *
	 * @var int
	 */
	private static $document_id;

	/**
	 * Test regular post with ID
	 *
	 * @var int
	 */
	private static $post_id;

	/**
	 * Set up test fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// Create a test document.
		self::$document_id = $factory->post->create(
			array(
				'post_title'   => 'Test Document for Revision Limits',
				'post_content' => 'Test content',
				'post_type'    => 'document',
				'post_status'  => 'private',
			)
		);

		// Create a regular post.
		self::$post_id = $factory->post->create(
			array(
				'post_title'   => 'Regular Post',
				'post_content' => 'Test content',
				'post_type'    => 'post',
				'post_status'  => 'publish',
			)
		);
	}

	/**
	 * Test that document revision limits are unlimited by default.
	 */
	public function test_document_revision_limits_unlimited() {
		global $wpdr;

		$document = get_post( self::$document_id );
		$this->assertNotEmpty( $document, 'Test document should exist' );
		$this->assertEquals( 'document', $document->post_type, 'Post should be a document' );

		// Test that wp_revisions_to_keep returns -1 (unlimited) for documents.
		$revision_limit = wp_revisions_to_keep( $document );
		$this->assertEquals( -1, $revision_limit, 'Document revisions should be unlimited (-1)' );
	}

	/**
	 * Test that regular posts are not affected by document revision limits.
	 */
	public function test_regular_post_revision_limits_unaffected() {
		$post = get_post( self::$post_id );
		$this->assertNotEmpty( $post, 'Test post should exist' );
		$this->assertEquals( 'post', $post->post_type, 'Post should be a regular post' );

		// Add a filter to set regular post revisions to a specific limit.
		add_filter( 'wp_revisions_to_keep', function( $num, $post ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			if ( isset( $post->post_type ) && 'post' === $post->post_type ) {
				return 5; // Set regular posts to 5 revisions
			}
			return $num;
		}, 
		5,
		2 ); // Lower priority than document filter.

		// For regular posts, the revision limit should be 5 (from our filter above).
		$revision_limit = wp_revisions_to_keep( $post );
		$this->assertEquals( 5, $revision_limit, 'Regular posts should have the limit set by the test filter (5)' );

		// Remove the test filter.
		remove_all_filters( 'wp_revisions_to_keep' );

		// Re-add the document filter that was removed by remove_all_filters.
		global $wpdr;
		add_filter( 'wp_revisions_to_keep', array( $wpdr, 'manage_document_revisions_limit' ), 999, 2 );
	}

	/**
	 * Test that the document_revisions_limit filter works correctly.
	 */
	public function test_document_revisions_limit_filter() {
		$document = get_post( self::$document_id );

		// Add a filter to customize document revision limits.
		add_filter(
			'document_revisions_limit',
			function ( $num ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				return 5; // Custom limit.
			}
		);

		$revision_limit = wp_revisions_to_keep( $document );
		$this->assertEquals( 5, $revision_limit, 'Custom document revision limit should be respected' );

		// Remove the filter to clean up.
		remove_all_filters( 'document_revisions_limit' );

		// Test that it goes back to unlimited.
		$revision_limit = wp_revisions_to_keep( $document );
		$this->assertEquals( -1, $revision_limit, 'Should return to unlimited after removing custom filter' );
	}

	/**
	 * Test that the method exists in the main class and not in admin class.
	 */
	public function test_method_location() {
		global $wpdr;

		// Method should exist in main class.
		$this->assertTrue( method_exists( $wpdr, 'manage_document_revisions_limit' ), 'Method should exist in main class' );

		// If admin class is loaded, method should NOT exist there.
		if ( isset( $wpdr->admin ) && ! is_null( $wpdr->admin ) ) {
			$this->assertFalse( method_exists( $wpdr->admin, 'manage_document_revisions_limit' ), 'Method should not exist in admin class' );
		}
	}

	/**
	 * Test method with null/invalid post parameter.
	 */
	public function test_method_with_invalid_post() {
		global $wpdr;

		// Test with null post.
		$result = $wpdr->manage_document_revisions_limit( 3, null );
		$this->assertEquals( 3, $result, 'Should return original value for null post' );

		// Test with invalid post.
		$fake_post = new stdClass();
		$result    = $wpdr->manage_document_revisions_limit( 3, $fake_post );
		$this->assertEquals( 3, $result, 'Should return original value for invalid post' );
	}

	/**
	 * Clean up test fixtures.
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$document_id, true );
		wp_delete_post( self::$post_id, true );
	}
}
