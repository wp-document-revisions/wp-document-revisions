<?php
/**
 * Tests for the AI summary generator + storage layer.
 *
 * Phase 11 of issue #514: verifies the cache-trigger → cron-schedule
 * → generate → store pipeline, the diff-status → summary-kind mapping,
 * the input-hash-based cache, the prompt filter, the opt-out skip
 * paths, and the review-state operations.
 *
 * The WordPress 7.0 AI Client is mocked via the
 * `wpdr_ai_summary_generator` and `wpdr_ai_summary_available` filters
 * so the suite runs without a live provider.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_AI_Summary.
 */
class Test_WP_Document_Revisions_AI_Summary extends Test_Common_WPDR {

	/**
	 * Load the counting-fake extractor for cache-warming.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
	}

	/**
	 * Reset filters + cron between tests so injected generators do
	 * not leak.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		remove_all_filters( 'wpdr_ai_summary_available' );
		remove_all_filters( 'wpdr_ai_summary_generator' );
		remove_all_filters( 'wpdr_ai_summary_prompt' );
		remove_all_filters( 'wpdr_ai_summary_delay' );
		_set_cron_array( array() );
		parent::tear_down();
	}

	/**
	 * Force-enable the AI pipeline and return a canned summary for
	 * every prompt the test exercises.
	 *
	 * @param string $canned Text to return from the injected generator.
	 * @return void
	 */
	private function inject_canned_generator( string $canned = 'Canned summary for testing.' ): void {
		add_filter( 'wpdr_ai_summary_available', '__return_true' );
		add_filter(
			'wpdr_ai_summary_generator',
			static function () use ( $canned ): string {
				return $canned;
			}
		);
	}

	/**
	 * Register a counting fake extractor that reads file contents
	 * verbatim, so two attachments with distinct bodies produce
	 * distinct extracted text.
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
	 * Create a document, attach a text file with the given body, and
	 * return its revision attachment ID.
	 *
	 * @param string $body file body to write before attaching.
	 * @param int    $doc_id existing document ID to attach to, or 0 to create one.
	 * @return array{0:int,1:int} [ document_id, attachment_id ].
	 */
	private function attach_revision( string $body, int $doc_id = 0 ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( self::$test_file, $body );

		if ( 0 === $doc_id ) {
			$doc_id = self::factory()->post->create(
				array(
					'post_title'  => 'AI Summary Test Document',
					'post_type'   => 'document',
					'post_status' => 'publish',
				)
			);
		}
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision = $wpdr->get_latest_revision( $doc_id );
		return array( (int) $doc_id, (int) $revision->post_content );
	}

	/**
	 * Cache::set() fires the wpdr_text_extracted action so the summary
	 * scheduler can queue itself.
	 */
	public function test_cache_set_fires_wpdr_text_extracted_action() {
		$captured = 0;
		add_action(
			'wpdr_text_extracted',
			static function ( int $id ) use ( &$captured ): void {
				$captured = $id;
			}
		);

		list( , $attach_id ) = $this->attach_revision( 'hello' );
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'hello', 'X' );

		self::assertSame( $attach_id, $captured, 'wpdr_text_extracted should fire with the attachment ID' );
	}

	/**
	 * maybe_schedule queues a single cron event for a revision attachment.
	 */
	public function test_maybe_schedule_queues_cron_event() {
		list( , $attach_id ) = $this->attach_revision( 'body' );
		_set_cron_array( array() );

		WP_Document_Revisions_AI_Summary::maybe_schedule( $attach_id );

		$next = wp_next_scheduled(
			WP_Document_Revisions_AI_Summary::CRON_ACTION,
			array( $attach_id )
		);
		self::assertIsInt( $next );
	}

	/**
	 * maybe_schedule skips attachments whose parent document is
	 * opted out of text extraction.
	 */
	public function test_maybe_schedule_skips_opted_out_document() {
		list( $doc_id, $attach_id ) = $this->attach_revision( 'body' );
		update_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );
		_set_cron_array( array() );

		WP_Document_Revisions_AI_Summary::maybe_schedule( $attach_id );

		self::assertFalse(
			wp_next_scheduled(
				WP_Document_Revisions_AI_Summary::CRON_ACTION,
				array( $attach_id )
			)
		);
	}

	/**
	 * Initial upload (no prior revision) routes to the 'document' kind
	 * and sends the new text to the AI Client.
	 */
	public function test_run_first_revision_uses_document_kind() {
		$this->register_counting_fake();
		$this->inject_canned_generator( 'doc summary' );
		list( , $attach_id ) = $this->attach_revision( "alpha\nbeta" );

		WP_Document_Revisions_AI_Summary::run( $attach_id );

		$stored = WP_Document_Revisions_AI_Summary::get( $attach_id );
		self::assertIsArray( $stored );
		self::assertSame( 'document', $stored['kind'] );
		self::assertSame( 'doc summary', $stored['text'] );
	}

	/**
	 * Second revision with distinct text routes to 'change' kind.
	 */
	public function test_run_second_revision_with_diff_uses_change_kind() {
		$this->register_counting_fake();
		$this->inject_canned_generator( 'change summary' );

		list( $doc_id, $first_id ) = $this->attach_revision( "alpha\nbeta\ngamma" );
		// Warm the cache for the first revision so the diff lookup
		// finds prior text.
		wpdr_extract_text( $first_id );

		list( , $second_id ) = $this->attach_revision( "alpha\nBETA\ngamma", $doc_id );

		WP_Document_Revisions_AI_Summary::run( $second_id );

		$stored = WP_Document_Revisions_AI_Summary::get( $second_id );
		self::assertIsArray( $stored );
		self::assertSame( 'change', $stored['kind'] );
		self::assertSame( 'change summary', $stored['text'] );
	}

	/**
	 * Identical text between prior and new revision routes to
	 * 'no_change' kind WITHOUT invoking the AI client (the fixed
	 * message is stored directly).
	 */
	public function test_run_identical_text_uses_no_change_kind_without_ai_call() {
		$this->register_counting_fake();
		add_filter( 'wpdr_ai_summary_available', '__return_true' );
		$ai_calls = 0;
		add_filter(
			'wpdr_ai_summary_generator',
			static function () use ( &$ai_calls ): string {
				++$ai_calls;
				return 'should-not-be-stored';
			}
		);

		list( $doc_id, $first_id ) = $this->attach_revision( "same text" );
		wpdr_extract_text( $first_id );
		list( , $second_id )       = $this->attach_revision( "same text", $doc_id );

		WP_Document_Revisions_AI_Summary::run( $second_id );

		$stored = WP_Document_Revisions_AI_Summary::get( $second_id );
		self::assertSame( 'no_change', $stored['kind'] );
		self::assertSame(
			WP_Document_Revisions_AI_Summary::NO_CHANGE_MESSAGE,
			$stored['text']
		);
		self::assertSame( 0, $ai_calls, 'no_change should not invoke the AI client' );
	}

	/**
	 * `too_large` from the diff utility falls back to a 'document'
	 * summary of the new text. Forced via a tight `max_chars` filter
	 * so the test fixture doesn't have to produce a real megabyte diff.
	 */
	public function test_run_too_large_diff_falls_back_to_document_kind() {
		$this->register_counting_fake();
		$this->inject_canned_generator( 'fallback doc summary' );
		add_filter(
			'wpdr_text_diff_max_chars',
			static function (): int {
				return 5;
			}
		);

		list( $doc_id, $first_id ) = $this->attach_revision( "alpha\nbeta" );
		wpdr_extract_text( $first_id );
		list( , $second_id )       = $this->attach_revision( "completely different\nlines\nhere", $doc_id );

		WP_Document_Revisions_AI_Summary::run( $second_id );

		$stored = WP_Document_Revisions_AI_Summary::get( $second_id );
		self::assertSame( 'document', $stored['kind'] );
		self::assertSame( 'fallback doc summary', $stored['text'] );
	}

	/**
	 * When the prior revision has no extracted text (scanned PDF, etc.)
	 * the run falls back to a 'document' summary of the new text.
	 *
	 * Manually populates the prior revision's cache with an empty
	 * extraction (simulating the scanned-PDF / unsupported-format
	 * case) instead of trying to register two different extractors
	 * for the same MIME type.
	 */
	public function test_run_old_text_missing_falls_back_to_document_kind() {
		list( $doc_id, $first_id ) = $this->attach_revision( 'old' );
		$first_path                = get_attached_file( $first_id );
		WP_Document_Revisions_Text_Extractor_Cache::set( $first_id, $first_path, '', 'TestExtractor' );

		// Register the file-reading fake AFTER the prior cache is set,
		// so the second revision (cache cold) extracts via the fake
		// while the first (cache hit on '') stays empty.
		$this->register_counting_fake();
		$this->inject_canned_generator( 'doc-only summary' );

		list( , $second_id ) = $this->attach_revision( 'new text content', $doc_id );

		WP_Document_Revisions_AI_Summary::run( $second_id );

		$stored = WP_Document_Revisions_AI_Summary::get( $second_id );
		self::assertSame( 'document', $stored['kind'] );
		self::assertSame( 'doc-only summary', $stored['text'] );
	}

	/**
	 * When the new revision has no extracted text the run stores an
	 * 'unavailable' record without invoking the AI client. This is
	 * the contract that lets phase 12's UI render "summary pending"
	 * rather than "generation failed."
	 */
	public function test_run_new_text_missing_stores_unavailable_without_ai_call() {
		$empty_fake = new WPDR_Test_Counting_Text_Extractor( 'text/plain', '' );
		add_filter(
			'wpdr_text_extractors',
			static function ( array $extractors ) use ( $empty_fake ): array {
				array_unshift( $extractors, $empty_fake );
				return $extractors;
			}
		);
		add_filter( 'wpdr_ai_summary_available', '__return_true' );
		$ai_calls = 0;
		add_filter(
			'wpdr_ai_summary_generator',
			static function () use ( &$ai_calls ): string {
				++$ai_calls;
				return 'unreachable';
			}
		);

		list( , $attach_id ) = $this->attach_revision( "something" );

		WP_Document_Revisions_AI_Summary::run( $attach_id );

		$stored = WP_Document_Revisions_AI_Summary::get( $attach_id );
		self::assertSame( 'unavailable', $stored['kind'] );
		self::assertSame( '', $stored['text'] );
		self::assertSame( 0, $ai_calls );
	}

	/**
	 * When the WP AI Client is unavailable, run() stores nothing —
	 * deliberately leaving the meta absent so a future cron pass
	 * after WP 7.0 is enabled produces a real summary.
	 */
	public function test_run_skips_when_ai_unavailable() {
		$this->register_counting_fake();
		add_filter( 'wpdr_ai_summary_available', '__return_false' );

		list( , $attach_id ) = $this->attach_revision( "alpha\nbeta" );

		WP_Document_Revisions_AI_Summary::run( $attach_id );

		self::assertNull(
			WP_Document_Revisions_AI_Summary::get( $attach_id ),
			'AI-unavailable run() should leave the summary meta absent'
		);
	}

	/**
	 * Re-running cron against unchanged inputs is a no-op (the AI
	 * client is not re-invoked).
	 */
	public function test_run_is_idempotent_for_unchanged_inputs() {
		$this->register_counting_fake();
		add_filter( 'wpdr_ai_summary_available', '__return_true' );
		$ai_calls = 0;
		add_filter(
			'wpdr_ai_summary_generator',
			static function () use ( &$ai_calls ): string {
				++$ai_calls;
				return 'summary';
			}
		);

		list( , $attach_id ) = $this->attach_revision( "alpha\nbeta" );
		WP_Document_Revisions_AI_Summary::run( $attach_id );
		WP_Document_Revisions_AI_Summary::run( $attach_id );

		self::assertSame( 1, $ai_calls, 'AI should only be called once for unchanged inputs' );
	}

	/**
	 * The wpdr_ai_summary_prompt filter receives the kind and revision
	 * ID (but not the input text) and the returned template is what
	 * the AI sees, prepended to the input.
	 */
	public function test_prompt_filter_sees_kind_and_revision_id() {
		$this->register_counting_fake();
		add_filter( 'wpdr_ai_summary_available', '__return_true' );

		$captured_kind          = '';
		$captured_revision_id   = 0;
		$captured_prompt_passed = '';

		add_filter(
			'wpdr_ai_summary_prompt',
			static function ( string $default, string $kind, int $rev_id ) use ( &$captured_kind, &$captured_revision_id ): string {
				$captured_kind        = $kind;
				$captured_revision_id = $rev_id;
				return 'CUSTOM PROMPT';
			},
			10,
			3
		);
		add_filter(
			'wpdr_ai_summary_generator',
			static function ( $default, string $prompt ) use ( &$captured_prompt_passed ): string {
				$captured_prompt_passed = $prompt;
				return 'ok';
			},
			10,
			2
		);

		list( , $attach_id ) = $this->attach_revision( "first revision text" );
		WP_Document_Revisions_AI_Summary::run( $attach_id );

		self::assertSame( 'document', $captured_kind, 'First revision uses document kind' );
		self::assertSame( $attach_id, $captured_revision_id );
		self::assertStringStartsWith( 'CUSTOM PROMPT', $captured_prompt_passed );
		self::assertStringContainsString( 'first revision text', $captured_prompt_passed );
	}

	/**
	 * prior_revision_id walks the document's attachments and returns
	 * the entry immediately after the given one (older revision).
	 */
	public function test_prior_revision_id_returns_immediately_older_attachment() {
		list( $doc_id, $first_id )  = $this->attach_revision( 'one' );
		list( , $second_id )        = $this->attach_revision( 'two', $doc_id );
		list( , $third_id )         = $this->attach_revision( 'three', $doc_id );

		self::assertSame( $second_id, WP_Document_Revisions_AI_Summary::prior_revision_id( $third_id, $doc_id ) );
		self::assertSame( $first_id, WP_Document_Revisions_AI_Summary::prior_revision_id( $second_id, $doc_id ) );
		self::assertSame( 0, WP_Document_Revisions_AI_Summary::prior_revision_id( $first_id, $doc_id ) );
	}

	/**
	 * set_reviewed writes the reviewer ID + timestamp when given a
	 * positive user ID, and clears them when given 0.
	 */
	public function test_set_reviewed_writes_and_clears_meta() {
		$this->register_counting_fake();
		$this->inject_canned_generator( 'summary' );
		list( , $attach_id ) = $this->attach_revision( 'body' );
		WP_Document_Revisions_AI_Summary::run( $attach_id );

		$reviewer = self::factory()->user->create();
		self::assertTrue( WP_Document_Revisions_AI_Summary::set_reviewed( $attach_id, $reviewer ) );

		$stored = WP_Document_Revisions_AI_Summary::get( $attach_id );
		self::assertSame( $reviewer, $stored['reviewed_by'] );
		self::assertGreaterThan( 0, $stored['reviewed_at'] );

		self::assertTrue( WP_Document_Revisions_AI_Summary::set_reviewed( $attach_id, 0 ) );
		$stored = WP_Document_Revisions_AI_Summary::get( $attach_id );
		self::assertSame( 0, $stored['reviewed_by'] );
		self::assertSame( 0, $stored['reviewed_at'] );
	}

	/**
	 * Storing a new summary clears any prior review state — the
	 * previous review applied to text that no longer reflects the
	 * current revision.
	 */
	public function test_store_clears_prior_review_state() {
		$this->register_counting_fake();
		$this->inject_canned_generator( 'first' );
		list( , $attach_id ) = $this->attach_revision( 'body' );
		WP_Document_Revisions_AI_Summary::run( $attach_id );

		$reviewer = self::factory()->user->create();
		WP_Document_Revisions_AI_Summary::set_reviewed( $attach_id, $reviewer );

		// Direct store() with a different input_hash simulates the
		// invariant the cron run upholds: a new summary lands.
		WP_Document_Revisions_AI_Summary::store( $attach_id, 'document', 'second', 'different-hash' );

		$stored = WP_Document_Revisions_AI_Summary::get( $attach_id );
		self::assertSame( 'second', $stored['text'] );
		self::assertSame( 0, $stored['reviewed_by'], 'Reviewing should be reset on new summary' );
		self::assertSame( 0, $stored['reviewed_at'] );
	}
}
