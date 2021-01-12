<?php
/**
 * Verifies basic CRUD operations of WP Document Revisions
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP Document Revisions
 */

/**
 * Main WP Document Revisions tests
 */
class Test_WP_Document_Revisions extends Test_Common_WPDR {

	/**
	 * Editor user id
	 *
	 * @var integer $editor_user_id
	 */
	private static $editor_user_id;

	/**
	 * Workflow_state term id
	 *
	 * @var integer $ws_term_id
	 */
	private static $ws_term_id;

	/**
	 * Author Public Post ID
	 *
	 * @var integer $editor_public_post
	 */
	private static $editor_public_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer $editor_private_post
	 */
	private static $editor_private_post;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
	// phpcs:enable
		console_log( 'Test_Main' );

		// create users.
		// Note that editor can do everything admin can do.
		self::$editor_user_id = $factory->user->create( array( 'role' => 'editor' ) );

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->add_caps();

		// flush cache for good measure.
		wp_cache_flush();

		// add terms and use one.
		$wpdr->initialize_workflow_states();
		$ws_terms         = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::$ws_term_id = $ws_terms[0]->term_id;

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( $factory, self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_public_post, self::$ws_term_id, 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file );

		// add second attachment.
		self::add_document_attachment( $factory, self::$editor_public_post, self::$test_file2 );

		// Editor Private.
		self::$editor_private_post = $factory->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$editor_user_id,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document Editor Private' );

		// add term and attachment.
		$terms = wp_set_post_terms( self::$editor_private_post, self::$ws_term_id, 'workflow_state' );
		self::add_document_attachment( $factory, self::$editor_private_post, self::$test_file );
	}

	/**
	 * Tests that the test Document stuctures are correct.
	 */
	public function test_structure() {
		self::verify_structure( self::$editor_public_post, 2, 2 );
		self::verify_structure( self::$editor_private_post, 1, 1 );
	}

	/**
	 * Make sure plugin is activated.
	 */
	public function test_class_exists() {
		console_log( ' class_exists' );

		self::assertTrue( class_exists( 'WP_Document_Revisions' ), 'Document_Revisions class not defined' );
	}


	/**
	 * Post type is properly registered.
	 */
	public function test_post_type_exists() {
		console_log( ' post_type_exists' );

		self::assertTrue( post_type_exists( 'document' ), 'Document post type does not exist' );
	}

	/**
	 * Workflow states exists and are initialized.
	 */
	public function test_workflow_states_exist() {
		console_log( ' workflow_states_exist' );

		$terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		self::assertFalse( is_wp_error( $terms ), 'Workflow State taxonomy does not exist' );
		self::assertCount( 4, $terms, 'Initial Workflow States not properly registered' );
	}

	/**
	 * Validate the get_attachments function are few different ways.
	 */
	public function test_get_attachments() {
		console_log( ' get_attachments' );

		global $wpdr;

		global $current_user;
		unset( $current_user );
		wp_set_current_user( self::$editor_user_id );
		wp_cache_flush();

		// Use editor public post.

		// grab an attachment.
		$attachments = $wpdr->get_attachments( self::$editor_public_post );
		$attachment  = end( $attachments );

		// grab a revision.
		$revisions = $wpdr->get_revisions( self::$editor_public_post );
		$revision  = end( $revisions );

		// get as postID.
		self::assertCount( 2, $attachments, 'Bad attachment count via get_attachments as postID' );

		// get as Object.
		self::assertCount( 2, $wpdr->get_attachments( get_post( self::$editor_public_post ) ), 'Bad attachment count via get_attachments as Object' );

		// get as a revision.
		self::assertCount( 2, $wpdr->get_attachments( $revision->ID ), 'Bad attachment count via get_attachments as revisionID' );

		// get as attachment.
		self::assertCount( 2, $wpdr->get_attachments( $attachment->ID ), 'Bad attachment count via get_attachments as attachmentID' );
	}

	/**
	 * Verify the get_file_Type function works.
	 */
	public function test_file_type() {
		console_log( ' file_type' );

		global $wpdr;

		// grab an attachment.
		$attachments = $wpdr->get_attachments( self::$editor_private_post );
		$attachment  = end( $attachments );

		self::assertEquals( '.txt', $wpdr->get_file_type( self::$editor_private_post ), "Didn't detect filetype via document ID" );
		self::assertEquals( '.txt', $wpdr->get_file_type( $attachment->ID ), "Didn't detect filetype via attachment ID" );
	}

	/**
	 * Make sure get_revisions() works.
	 */
	public function test_get_revisions() {
		console_log( ' get_revisions' );

		global $wpdr;

		self::assertEquals( 3, count( $wpdr->get_revisions( self::$editor_public_post ) ) );
		self::assertEquals( 2, count( $wpdr->get_revisions( self::$editor_private_post ) ) );
	}

	/**
	 * Test get_revision_number().
	 */
	public function test_get_revision_number() {
		console_log( ' get_revision_number' );

		global $wpdr;

		$revisions = $wpdr->get_revisions( self::$editor_private_post );
		$last      = end( $revisions );
		self::assertEquals( 1, $wpdr->get_revision_number( $last->ID ) );
		self::assertEquals( $last->ID, $wpdr->get_revision_id( 1, self::$editor_private_post ) );
	}

	/**
	 * Tests verify_post_type() with the various ways used throughout
	 */
	public function test_verify_post_type() {
		console_log( ' verify_post_type' );

		global $wpdr;

		$_GET['post_type'] = 'document';
		self::assertTrue( $wpdr->verify_post_type(), 'verify post type via explicit' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post_type'] );

		$_GET['post'] = self::$editor_public_post;
		self::assertTrue( $wpdr->verify_post_type(), 'verify post type via get' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_GET['post'] );

		$_REQUEST['post_id'] = self::$editor_public_post;
		self::assertTrue( $wpdr->verify_post_type(), 'verify post type via request (post_id)' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		unset( $_REQUEST['post_id'] );

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		global $post;
		$post = get_post( self::$editor_public_post );
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		self::assertTrue( $wpdr->verify_post_type( $post ), 'verify post type via global $post' );
		unset( $post );

		self::assertTrue( $wpdr->verify_post_type( self::$editor_public_post ) );
	}

}
