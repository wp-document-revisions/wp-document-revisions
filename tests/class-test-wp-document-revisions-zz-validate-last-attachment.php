<?php
/**
 * Tests which attachment Validate Structure treats as a document's file (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Validate Structure last-attachment tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Validate_Last_Attachment extends Test_Common_WPDR {

	/**
	 * Call the private get_last_attachment().
	 *
	 * @param int $doc_id document ID.
	 * @return int|false
	 */
	private function last_attachment( $doc_id ) {
		$method = new ReflectionMethod( 'WP_Document_Revisions_Validate_Structure', 'get_last_attachment' );
		$method->setAccessible( true );
		return $method->invoke( null, $doc_id );
	}

	/**
	 * Create a child attachment with a given stored file name.
	 *
	 * @param int    $doc_id document ID.
	 * @param string $file   _wp_attached_file value.
	 * @return int
	 */
	private function child( $doc_id, $file ) {
		$id = self::factory()->attachment->create( array( 'post_parent' => $doc_id ) );
		update_post_meta( $id, '_wp_attached_file', $file );
		return $id;
	}

	/**
	 * Without the meta, a later unhashed image doesn't count as the document's file.
	 */
	public function test_ignores_later_images() {
		$doc      = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$file     = $this->child( $doc, '2026/09/' . md5( 'report' ) . '.pdf' );
		$featured = $this->child( $doc, '2026/09/cover-photo.jpg' );
		set_post_thumbnail( $doc, $featured );
		$this->child( $doc, '2026/09/another-photo.jpg' );

		self::assertSame( $file, $this->last_attachment( $doc ) );
	}

	/**
	 * The attachment id meta wins while it names a child attachment.
	 */
	public function test_prefers_attachment_meta() {
		$doc     = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$current = $this->child( $doc, '2026/08/' . md5( 'current' ) . '.pdf' );
		$this->child( $doc, '2026/09/' . md5( 'newer' ) . '.pdf' );
		update_post_meta( $doc, '_document_attachment_id', $current );

		self::assertSame( $current, $this->last_attachment( $doc ) );
	}

	/**
	 * Meta naming a missing or non-child attachment is ignored.
	 */
	public function test_ignores_stale_attachment_meta() {
		$doc  = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$file = $this->child( $doc, '2026/09/' . md5( 'report' ) . '.pdf' );
		$orph = self::factory()->attachment->create( array( 'post_parent' => 0 ) );

		update_post_meta( $doc, '_document_attachment_id', $orph );
		self::assertSame( $file, $this->last_attachment( $doc ) );

		update_post_meta( $doc, '_document_attachment_id', PHP_INT_MAX );
		self::assertSame( $file, $this->last_attachment( $doc ) );
	}

	/**
	 * The newest hashed file wins.
	 */
	public function test_newest_document_file() {
		$doc = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$this->child( $doc, '2026/08/' . md5( 'v1' ) . '.pdf' );
		$v2 = $this->child( $doc, '2026/09/' . md5( 'v2' ) . '.pdf' );

		self::assertSame( $v2, $this->last_attachment( $doc ) );
	}

	/**
	 * Legacy unhashed files and documents without attachments.
	 */
	public function test_fallbacks() {
		$doc    = self::factory()->post->create( array( 'post_type' => 'document' ) );
		$legacy = $this->child( $doc, '2019/01/old-report.pdf' );
		self::assertSame( $legacy, $this->last_attachment( $doc ) );

		$empty = self::factory()->post->create( array( 'post_type' => 'document' ) );
		self::assertFalse( $this->last_attachment( $empty ) );
	}
}
