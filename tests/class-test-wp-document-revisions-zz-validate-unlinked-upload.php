<?php
/**
 * Tests that Validate Structure offers to link a newer upload that never got linked (#858).
 *
 * Before #858, uploading a new version whose file name had capitals, spaces or accents left the
 * document on its old file. The new file was then just an orphan, and the orphan fix deletes it.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Validate Structure unlinked-upload tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Validate_Unlinked_Upload extends WP_UnitTestCase {

	/**
	 * Call the private check_for_orphans().
	 *
	 * @param int $attach_id the document's current attachment id.
	 * @param int $doc_id    document ID.
	 * @return array<string, mixed>|false
	 */
	private function check( $attach_id, $doc_id ) {
		$method = new ReflectionMethod( 'WP_Document_Revisions_Validate_Structure', 'check_for_orphans' );
		$method->setAccessible( true );
		return $method->invoke( null, $attach_id, $doc_id );
	}

	/**
	 * Create a child attachment with a given stored file name.
	 *
	 * @param int    $doc_id document ID.
	 * @param string $file   _wp_attached_file value.
	 * @return int
	 */
	private function child( $doc_id, $file ) {
		$id = self::factory()->attachment->create(
			array(
				'post_parent' => $doc_id,
				'post_status' => 'inherit',
			)
		);
		update_post_meta( $id, '_wp_attached_file', $file );
		return $id;
	}

	/**
	 * Record a revision of the document that links an attachment.
	 *
	 * @param int $doc_id    document ID.
	 * @param int $attach_id attachment id.
	 * @return void
	 */
	private function revision( $doc_id, $attach_id ) {
		global $wpdr;
		self::factory()->post->create(
			array(
				'post_type'    => 'revision',
				'post_status'  => 'inherit',
				'post_parent'  => $doc_id,
				'post_name'    => $doc_id . '-revision-v1',
				'post_content' => $wpdr->format_doc_id( $attach_id ),
			)
		);
	}

	/**
	 * Create a document linked to a hashed file, as a saved document is.
	 *
	 * @return array{0:int,1:int} the document id and its current attachment id.
	 */
	private function document() {
		global $wpdr;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$doc = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$old = $this->child( $doc, '2026/09/' . md5( 'first' ) . '.pdf' );
		wp_update_post(
			array(
				'ID'           => $doc,
				'post_content' => $wpdr->format_doc_id( $old ),
			)
		);
		update_post_meta( $doc, '_document_attachment_id', $old );
		$this->revision( $doc, $old );
		return array( $doc, $old );
	}

	/**
	 * A newer hashed file nothing links to is reported as an unlinked upload, and fixing it
	 * links the document to it.
	 */
	public function test_unlinked_newer_upload_is_reported_and_linked() {
		global $wpdr;
		list( $doc, $old ) = $this->document();
		$new               = $this->child( $doc, '2026/10/' . md5( 'second' ) . '.pdf' );

		$problem = $this->check( $old, $doc );
		self::assertIsArray( $problem );
		self::assertSame( 16, $problem['code'] );
		self::assertSame( $new, $problem['parm'] );

		self::assertTrue( WP_Document_Revisions_Validate_Structure::fix_problem( $doc, 16, $new ) );
		self::assertSame( $new, (int) $wpdr->extract_document_id( get_post_field( 'post_content', $doc, 'db' ) ) );
		self::assertSame( $new, (int) get_post_meta( $doc, '_document_attachment_id', true ) );

		// The old file stays in the revision history, so nothing is left to report.
		self::assertFalse( $this->check( $new, $doc ) );
	}

	/**
	 * With several unlinked uploads, the newest one is offered.
	 */
	public function test_newest_unlinked_upload_wins() {
		list( $doc, $old ) = $this->document();
		$this->child( $doc, '2026/10/' . md5( 'second' ) . '.pdf' );
		$newest = $this->child( $doc, '2026/10/' . md5( 'third' ) . '.pdf' );

		$problem = $this->check( $old, $doc );
		self::assertSame( 16, $problem['code'] );
		self::assertSame( $newest, $problem['parm'] );
	}

	/**
	 * Restoring an older version leaves the newer file in the revision history, so it isn't
	 * an unlinked upload.
	 */
	public function test_restored_version_is_not_reported() {
		global $wpdr;
		list( $doc, $old ) = $this->document();
		$new               = $this->child( $doc, '2026/10/' . md5( 'second' ) . '.pdf' );
		$this->revision( $doc, $new );

		// Restore the first version.
		wp_update_post(
			array(
				'ID'           => $doc,
				'post_content' => $wpdr->format_doc_id( $old ),
			)
		);
		$this->revision( $doc, $old );

		self::assertFalse( $this->check( $old, $doc ) );
	}

	/**
	 * Orphans that aren't newer document files are still reported as orphans to delete.
	 */
	public function test_other_orphans_are_still_orphans() {
		list( $doc, $old ) = $this->document();

		// A newer file that isn't stored as a document file, such as an image.
		$this->child( $doc, '2026/10/cover-photo.jpg' );
		$problem = $this->check( $old, $doc );
		self::assertSame( 14, $problem['code'] );

		// An older document file, e.g. from uploading twice before saving.
		$doc2 = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$this->child( $doc2, '2026/09/' . md5( 'older' ) . '.pdf' );
		$cur = $this->child( $doc2, '2026/09/' . md5( 'current' ) . '.pdf' );
		$this->revision( $doc2, $cur );
		$problem = $this->check( $cur, $doc2 );
		self::assertSame( 14, $problem['code'] );
		self::assertSame( $cur, $problem['parm'] );
	}

	/**
	 * The fix only links the attachment the check would offer.
	 */
	public function test_fix_rejects_other_attachments() {
		list( $doc, $old ) = $this->document();
		$older             = $this->child( $doc, '2026/10/' . md5( 'second' ) . '.pdf' );
		$this->child( $doc, '2026/10/' . md5( 'third' ) . '.pdf' );
		$other_doc = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$foreign   = $this->child( $other_doc, '2026/10/' . md5( 'foreign' ) . '.pdf' );

		foreach ( array( $old, $older, $foreign ) as $parm ) {
			$result = WP_Document_Revisions_Validate_Structure::fix_problem( $doc, 16, $parm );
			self::assertWPError( $result, "Linked attachment $parm" );
		}
		self::assertSame( $old, (int) get_post_meta( $doc, '_document_attachment_id', true ) );
	}
}
