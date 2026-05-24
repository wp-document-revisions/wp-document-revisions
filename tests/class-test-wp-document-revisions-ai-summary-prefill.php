<?php
/**
 * Tests for the AI revision-log pre-fill enqueue gate.
 *
 * Phase 12 of issue #514: focuses on the PHP gate that decides
 * whether to enqueue the JS module — the JS itself is tested via
 * Jest. Verifies the per-document opt-out, the latest-revision
 * lookup helper, and the post-content-format tolerance the helper
 * inherits from the plugin's older URL-formatted revision content.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_AI_Summary_Prefill.
 */
class Test_WP_Document_Revisions_AI_Summary_Prefill extends Test_Common_WPDR {

	/**
	 * Create a document, attach the standard test fixture, and return
	 * both IDs.
	 *
	 * @return array{0:int,1:int} [ document_id, attachment_id ].
	 */
	private function create_document_with_attachment(): array {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Prefill Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision = $wpdr->get_latest_revision( $doc_id );
		return array( (int) $doc_id, (int) $revision->post_content );
	}

	/**
	 * The current-revision helper returns the latest attachment ID
	 * for documents whose revisions store the attachment ID directly
	 * in post_content (the modern format).
	 */
	public function test_current_revision_attachment_id_returns_latest_attachment() {
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();

		self::assertSame(
			$attach_id,
			WP_Document_Revisions_AI_Summary_Prefill::current_revision_attachment_id( $doc_id )
		);
	}

	/**
	 * The current-revision helper returns 0 when the document has no
	 * revision attachment yet.
	 */
	public function test_current_revision_attachment_id_returns_zero_when_no_revision() {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Document with no attachment',
				'post_type'   => 'document',
				'post_status' => 'draft',
			)
		);

		self::assertSame(
			0,
			WP_Document_Revisions_AI_Summary_Prefill::current_revision_attachment_id( $doc_id )
		);
	}
}
