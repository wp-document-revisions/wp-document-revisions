<?php
/**
 * Tests for the unified-diff helper.
 *
 * Phase 10 of issue #514: verifies the status taxonomy, context-line and
 * size-cap behaviour, the renderer's unified-format output (block header
 * format, line prefixes), and the diff_revisions() loading path.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_Text_Diff + the unified renderer.
 */
class Test_WP_Document_Revisions_Text_Diff extends Test_Common_WPDR {

	/**
	 * Load the counting-fake helper for diff_revisions tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
	}

	/**
	 * Clear filters and cron so tests don't leak.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		remove_all_filters( 'wpdr_text_diff_context_lines' );
		remove_all_filters( 'wpdr_text_diff_max_chars' );
		_set_cron_array( array() );
		parent::tear_down();
	}

	/**
	 * Register a counting fake extractor that returns the file contents.
	 *
	 * @return WPDR_Test_Counting_Text_Extractor the registered fake.
	 */
	private function register_counting_fake(): WPDR_Test_Counting_Text_Extractor {
		$fake = new WPDR_Test_Counting_Text_Extractor( 'text/plain' );
		add_filter(
			'wpdr_text_extractors',
			static function ( array $extractors ) use ( $fake ): array {
				array_unshift( $extractors, $fake );
				return $extractors;
			}
		);
		return $fake;
	}

	/**
	 * Create a document, attach a text file with the given content,
	 * and return its revision attachment ID.
	 *
	 * @param string $body the file body to write before attaching.
	 * @return int the attachment ID.
	 */
	private function create_text_attachment_with_body( string $body ): int {
		// Use the suite's standard test_file fixture path, but overwrite
		// the bytes so each fixture has the body we want diffed.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( self::$test_file, $body );

		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Diff Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision = $wpdr->get_latest_revision( $doc_id );
		return (int) $revision->post_content;
	}

	/**
	 * Identical inputs report status `identical` with an empty diff.
	 */
	public function test_diff_text_identical_returns_identical_status() {
		$result = WP_Document_Revisions_Text_Diff::diff_text( "alpha\nbeta\ngamma", "alpha\nbeta\ngamma" );

		self::assertSame( 'identical', $result['status'] );
		self::assertSame( '', $result['diff'] );
	}

	/**
	 * Both-empty inputs are also `identical` (the empty diff case).
	 */
	public function test_diff_text_both_empty_returns_identical_status() {
		$result = WP_Document_Revisions_Text_Diff::diff_text( '', '' );

		self::assertSame( 'identical', $result['status'] );
		self::assertSame( '', $result['diff'] );
	}

	/**
	 * A small one-line edit returns status `ok` and a unified-format diff.
	 */
	public function test_diff_text_small_edit_returns_ok_with_unified_diff() {
		$old    = "alpha\nbeta\ngamma";
		$new    = "alpha\nBETA\ngamma";
		$result = WP_Document_Revisions_Text_Diff::diff_text( $old, $new );

		self::assertSame( 'ok', $result['status'] );
		self::assertStringContainsString( '@@', $result['diff'], 'Unified diff should contain a block header' );
		self::assertStringContainsString( '-beta', $result['diff'] );
		self::assertStringContainsString( '+BETA', $result['diff'] );
		// Context line "alpha" should appear (prefixed with a single space).
		self::assertStringContainsString( ' alpha', $result['diff'] );
	}

	/**
	 * Wholesale rewrites that exceed `max_chars` report `too_large` and
	 * suppress the diff (callers fall back to document-summary mode).
	 */
	public function test_diff_text_wholesale_rewrite_returns_too_large() {
		// Build two distinct multi-line bodies large enough to blow a
		// small per-call cap. 200 unique lines on each side ensures the
		// rendered diff is well over 200 chars.
		$old = '';
		$new = '';
		for ( $i = 0; $i < 200; $i++ ) {
			$old .= "old line {$i}\n";
			$new .= "new line {$i}\n";
		}

		$result = WP_Document_Revisions_Text_Diff::diff_text(
			$old,
			$new,
			array( 'max_chars' => 200 )
		);

		self::assertSame( 'too_large', $result['status'] );
		self::assertSame( '', $result['diff'], 'Too-large diff should be suppressed' );
	}

	/**
	 * Empty old + non-empty new (file changed from empty/scan to text)
	 * returns `ok` with each new-side line appearing as an addition.
	 * Note that `explode("\n", "")` yields `[""]`, so the diff also
	 * includes a deletion of that single empty line — that's expected
	 * and not what this test is asserting against.
	 */
	public function test_diff_text_empty_to_content_renders_as_additions() {
		$result = WP_Document_Revisions_Text_Diff::diff_text( '', "alpha\nbeta\ngamma" );

		self::assertSame( 'ok', $result['status'] );
		self::assertStringContainsString( '+alpha', $result['diff'] );
		self::assertStringContainsString( '+beta', $result['diff'] );
		self::assertStringContainsString( '+gamma', $result['diff'] );
	}

	/**
	 * Non-empty old + empty new (file replaced with a scanned image
	 * whose text extraction is empty) returns `ok` with a diff that's
	 * all deletions.
	 */
	public function test_diff_text_content_to_empty_renders_as_pure_deletion() {
		$result = WP_Document_Revisions_Text_Diff::diff_text( "alpha\nbeta\ngamma", '' );

		self::assertSame( 'ok', $result['status'] );
		self::assertStringContainsString( '-alpha', $result['diff'] );
		self::assertStringContainsString( '-beta', $result['diff'] );
		self::assertStringContainsString( '-gamma', $result['diff'] );
	}

	/**
	 * Context-line option controls how much surrounding context the
	 * renderer emits — verified by comparing a 1-line edit at the
	 * middle of a long file with context=0 vs context=3.
	 */
	public function test_diff_text_context_lines_option_is_honoured() {
		$old = "L1\nL2\nL3\nL4\nL5\nL6\nL7\nL8\nL9";
		$new = "L1\nL2\nL3\nL4\nCHANGED\nL6\nL7\nL8\nL9";

		$tight = WP_Document_Revisions_Text_Diff::diff_text( $old, $new, array( 'context_lines' => 0 ) );
		$wide  = WP_Document_Revisions_Text_Diff::diff_text( $old, $new, array( 'context_lines' => 3 ) );

		self::assertSame( 'ok', $tight['status'] );
		self::assertSame( 'ok', $wide['status'] );
		self::assertGreaterThan(
			strlen( $tight['diff'] ),
			strlen( $wide['diff'] ),
			'Larger context should produce a larger rendered diff'
		);
		self::assertStringNotContainsString( ' L4', $tight['diff'], 'context=0 should omit surrounding L4' );
		self::assertStringContainsString( ' L4', $wide['diff'], 'context=3 should include surrounding L4' );
	}

	/**
	 * The `wpdr_text_diff_max_chars` filter shifts the cap globally.
	 */
	public function test_diff_text_max_chars_filter_changes_cap() {
		add_filter(
			'wpdr_text_diff_max_chars',
			static function (): int {
				return 5;
			}
		);

		$result = WP_Document_Revisions_Text_Diff::diff_text( 'a', 'b' );

		self::assertSame( 'too_large', $result['status'] );
	}

	/**
	 * The renderer subclass emits the exact `@@ -x,y +a,b @@` form that
	 * standard unified-diff parsers expect.
	 */
	public function test_renderer_emits_canonical_block_header() {
		require_once ABSPATH . WPINC . '/wp-diff.php';
		require_once __DIR__ . '/../includes/class-wp-document-revisions-unified-diff-renderer.php';

		$diff     = new Text_Diff(
			'auto',
			array(
				array( 'a', 'b', 'c' ),
				array( 'a', 'X', 'c' ),
			)
		);
		$renderer = new WP_Document_Revisions_Unified_Diff_Renderer();
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$renderer->_leading_context_lines  = 3;
		$renderer->_trailing_context_lines = 3;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$output = (string) $renderer->render( $diff );

		// With 3 lines of context on both sides of a single-line change
		// inside a 3-line file, both old and new sides have length 3.
		self::assertStringContainsString( '@@ -1,3 +1,3 @@', $output );
		self::assertStringContainsString( '-b', $output );
		self::assertStringContainsString( '+X', $output );
	}

	/**
	 * `diff_revisions` returns `old_text_missing` when wpdr_extract_text
	 * returns empty for the original side (e.g. scanned PDF, no cached
	 * text). The new side is not consulted in that case.
	 */
	public function test_diff_revisions_reports_old_text_missing() {
		// Counting fake configured to return '' simulates an extracted-
		// empty file (scanned PDF, etc.).
		$fake = new WPDR_Test_Counting_Text_Extractor( 'text/plain', '' );
		add_filter(
			'wpdr_text_extractors',
			static function ( array $extractors ) use ( $fake ): array {
				array_unshift( $extractors, $fake );
				return $extractors;
			}
		);

		$old = $this->create_text_attachment_with_body( 'whatever' );
		$new = $this->create_text_attachment_with_body( 'also whatever' );

		$result = WP_Document_Revisions_Text_Diff::diff_revisions( $old, $new );

		self::assertSame( 'old_text_missing', $result['status'] );
		self::assertSame( '', $result['diff'] );
	}

	/**
	 * `diff_revisions` happy path: two attachments with distinct text
	 * produce a unified diff.
	 */
	public function test_diff_revisions_produces_diff_between_two_attachments() {
		$this->register_counting_fake();
		$old = $this->create_text_attachment_with_body( "alpha\nbeta\ngamma" );
		$new = $this->create_text_attachment_with_body( "alpha\nBETA\ngamma" );

		$result = WP_Document_Revisions_Text_Diff::diff_revisions( $old, $new );

		self::assertSame( 'ok', $result['status'] );
		self::assertStringContainsString( '-beta', $result['diff'] );
		self::assertStringContainsString( '+BETA', $result['diff'] );
	}
}
