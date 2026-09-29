<?php
/**
 * Tests is_document_upload() and the document upload start/end actions (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document upload hook tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Document_Upload_Hooks extends WP_UnitTestCase {

	/**
	 * Earlier test classes start document uploads that never generate metadata, so reset the
	 * per-request flag.
	 */
	public function set_up() {
		parent::set_up();
		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
	}

	/**
	 * Clean up request state.
	 */
	public function tear_down() {
		global $wpdr;
		unset( $_POST['upload_source'], $_POST['post_id'] );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		WP_Document_Revisions::$doc_image = true;
		parent::tear_down();
	}

	/**
	 * A document upload fires start then end, with is_document_upload() true in between.
	 */
	public function test_document_upload_lifecycle() {
		global $wpdr;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$doc = self::factory()->post->create( array( 'post_type' => 'document' ) );

		$events = array();
		$start  = function ( $file, $document_id, $orig ) use ( &$events, $wpdr ) {
			$events[] = array( 'start', $document_id, $orig, $wpdr->is_document_upload() );
		};
		$end    = function ( $attachment_id, $document_id ) use ( &$events, $wpdr ) {
			$events[] = array( 'end', $document_id, $attachment_id, $wpdr->is_document_upload() );
		};
		add_action( 'document_upload_start', $start, 10, 3 );
		add_action( 'document_upload_end', $end, 10, 2 );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$_POST['upload_source'] = 'wp-document-revisions';
		$_POST['post_id']       = $doc;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		self::assertFalse( $wpdr->is_document_upload() );
		$file = $wpdr->filename_rewrite( array( 'name' => 'report.pdf' ) );
		self::assertTrue( $wpdr->is_document_upload() );

		$attach = self::factory()->attachment->create( array( 'post_parent' => $doc ) );
		apply_filters( 'wp_generate_attachment_metadata', array(), $attach, 'create' );

		remove_action( 'document_upload_start', $start, 10 );
		remove_action( 'document_upload_end', $end, 10 );

		self::assertFalse( $wpdr->is_document_upload() );
		self::assertNotSame( 'report.pdf', $file['name'], 'The file name is hashed.' );
		self::assertSame(
			array(
				array( 'start', $doc, 'report.pdf', true ),
				array( 'end', $doc, $attach, false ),
			),
			$events
		);
	}

	/**
	 * Ordinary media uploads don't count.
	 */
	public function test_media_upload_is_not_document_upload() {
		global $wpdr;

		$wpdr->filename_rewrite( array( 'name' => 'photo.jpg' ) );

		$started = did_action( 'document_upload_start' );
		$wpdr->filename_rewrite( array( 'name' => 'photo2.jpg' ) );

		self::assertFalse( $wpdr->is_document_upload() );
		self::assertSame( $started, did_action( 'document_upload_start' ) );
	}
}
