<?php
/**
 * Tests for the per-document text-extraction opt-out.
 *
 * Phase 8 of issue #514: verifies the predicate, the save handler, the
 * cache-clearing pass on the disabled transition, and the scheduler /
 * sync-helper skip paths.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_Text_Extraction_Opt_Out.
 */
class Test_WP_Document_Revisions_Text_Extraction_Opt_Out extends Test_Common_WPDR {

	/**
	 * Load the counting-fake helper class once per test class run.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
	}

	/**
	 * Clear filters, cron, and meta state so tests don't leak.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		_set_cron_array( array() );
		parent::tear_down();
	}

	/**
	 * Register a counting fake extractor at higher priority than the
	 * built-ins so we have a stable instance whose call count we can read.
	 *
	 * @param string $mime MIME type the fake should claim.
	 * @return WPDR_Test_Counting_Text_Extractor the registered fake.
	 */
	private function register_counting_fake( string $mime = 'text/plain' ): WPDR_Test_Counting_Text_Extractor {
		$fake = new WPDR_Test_Counting_Text_Extractor( $mime );
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
	 * Create a document, attach the standard test fixture, and return both
	 * the document ID and the revision attachment ID.
	 *
	 * @return array{0:int,1:int} [ document_id, attachment_id ].
	 */
	private function create_document_with_attachment(): array {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Opt-Out Test Document',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision  = $wpdr->get_latest_revision( $doc_id );
		$attach_id = (int) $revision->post_content;

		return array( (int) $doc_id, $attach_id );
	}

	/**
	 * Predicate returns false when no opt-out meta is set on the document.
	 */
	public function test_predicate_returns_false_when_meta_unset() {
		list( $doc_id, ) = $this->create_document_with_attachment();

		self::assertFalse( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $doc_id ) );
	}

	/**
	 * Predicate returns true when the opt-out meta is '1'.
	 */
	public function test_predicate_returns_true_when_meta_is_one() {
		list( $doc_id, ) = $this->create_document_with_attachment();
		update_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );

		self::assertTrue( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $doc_id ) );
	}

	/**
	 * Predicate returns false for a non-positive document ID without the
	 * sitewide kill switch — the "no parent" attachment case.
	 */
	public function test_predicate_returns_false_for_zero_document_id_without_constant() {
		self::assertFalse( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( 0 ) );
		self::assertFalse( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( -1 ) );
	}

	/**
	 * Cache::clear() removes every cache-managed meta key.
	 */
	public function test_cache_clear_removes_all_meta_keys() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'hello', 'Some_Extractor@1.0.0' );
		WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $attach_id, $file_path );

		WP_Document_Revisions_Text_Extractor_Cache::clear( $attach_id );

		self::assertSame( '', (string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_TEXT, true ) );
		self::assertSame( '', (string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_HASH, true ) );
		self::assertSame( '', (string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_EXTRACTOR, true ) );
		self::assertSame( '', (string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_FAILED_HASH, true ) );
	}

	/**
	 * Scheduler does not enqueue an event when the document is opted out.
	 */
	public function test_scheduler_skips_when_document_opted_out() {
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();

		// add_attachment ran during create_document_with_attachment(); flush
		// any already-scheduled event so we can observe a clean skip.
		_set_cron_array( array() );

		update_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );
		WP_Document_Revisions_Text_Extractor_Scheduler::maybe_schedule( $attach_id );

		self::assertFalse(
			wp_next_scheduled(
				WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
				array( $attach_id )
			)
		);
	}

	/**
	 * Cron handler bails without invoking the extractor when the document
	 * is opted out — covers the case where opt-out is flipped between
	 * scheduling and pickup.
	 */
	public function test_scheduler_run_skips_when_document_opted_out() {
		$fake = $this->register_counting_fake();
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		update_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );

		self::assertSame( 0, $fake->calls, 'Cron handler should not invoke an extractor on an opted-out document' );
	}

	/**
	 * Synchronous wpdr_extract_text() returns '' for revisions of an
	 * opted-out document without touching the extractor.
	 */
	public function test_sync_helper_skips_when_document_opted_out() {
		$fake = $this->register_counting_fake();
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		update_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );

		$text = wpdr_extract_text( $attach_id );

		self::assertSame( '', $text );
		self::assertSame( 0, $fake->calls, 'Sync helper should not invoke an extractor on an opted-out document' );
	}

	/**
	 * Clearing cached text for a document removes cache meta from every
	 * revision attachment and unschedules any pending cron events.
	 */
	public function test_clear_cached_text_for_document_removes_meta_and_cron() {
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		$file_path                  = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'cached text', 'Some_Extractor@1.0.0' );
		self::assertNotSame(
			'',
			(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_TEXT, true )
		);

		// Sanity check: there should be a scheduled event from the original
		// add_attachment fire, since the document was not opted out yet.
		self::assertNotFalse(
			wp_next_scheduled(
				WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
				array( $attach_id )
			)
		);

		WP_Document_Revisions_Text_Extraction_Opt_Out::clear_cached_text_for_document( $doc_id );

		self::assertSame(
			'',
			(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_TEXT, true ),
			'Cache text meta should be cleared'
		);
		self::assertFalse(
			wp_next_scheduled(
				WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
				array( $attach_id )
			),
			'Pending cron event should be unscheduled'
		);
	}

	/**
	 * Save handler stores the flag and clears cached text on the transition
	 * from "allowed" to "disabled". Drives the production path the meta-box
	 * form posts go through.
	 */
	public function test_save_handler_enables_flag_and_clears_cache() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		$file_path                  = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'cached', 'Some_Extractor@1.0.0' );

		$_POST = array(
			WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD => wp_create_nonce(
				WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_ACTION
			),
			WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD => '1',
		);

		try {
			WP_Document_Revisions_Text_Extraction_Opt_Out::save( $doc_id, get_post( $doc_id ) );

			self::assertSame(
				'1',
				(string) get_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, true )
			);
			self::assertSame(
				'',
				(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_TEXT, true ),
				'Enabling opt-out should clear previously cached text'
			);
		} finally {
			$_POST = array();
		}
	}

	/**
	 * Save handler removes the flag when the checkbox is unchecked.
	 */
	public function test_save_handler_removes_flag_when_unchecked() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_id, ) = $this->create_document_with_attachment();
		update_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );

		$_POST = array(
			WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD => wp_create_nonce(
				WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_ACTION
			),
			// Checkbox absent — same shape as an unchecked HTML form submission.
		);

		try {
			WP_Document_Revisions_Text_Extraction_Opt_Out::save( $doc_id, get_post( $doc_id ) );

			self::assertSame(
				'',
				(string) get_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, true )
			);
		} finally {
			$_POST = array();
		}
	}

	/**
	 * Save handler bails (no meta change) without a valid nonce.
	 */
	public function test_save_handler_requires_valid_nonce() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_id, ) = $this->create_document_with_attachment();

		$_POST = array(
			WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD => 'invalid-nonce',
			WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD  => '1',
		);

		try {
			WP_Document_Revisions_Text_Extraction_Opt_Out::save( $doc_id, get_post( $doc_id ) );

			self::assertSame(
				'',
				(string) get_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, true ),
				'Invalid nonce should leave meta untouched'
			);
		} finally {
			$_POST = array();
		}
	}

	/**
	 * Save handler bails when the current user lacks edit_document.
	 */
	public function test_save_handler_requires_edit_document_capability() {
		// Subscribers cannot edit_document.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		list( $doc_id, ) = $this->create_document_with_attachment();

		$_POST = array(
			WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD => wp_create_nonce(
				WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_ACTION
			),
			WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD => '1',
		);

		try {
			WP_Document_Revisions_Text_Extraction_Opt_Out::save( $doc_id, get_post( $doc_id ) );

			self::assertSame(
				'',
				(string) get_post_meta( $doc_id, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, true ),
				'Save without edit_document should leave meta untouched'
			);
		} finally {
			$_POST = array();
		}
	}
}
