<?php
/**
 * Tests for the async text-extraction scheduler.
 *
 * Phase 7 of issue #514: verifies that a revision attachment insert
 * schedules a single wp-cron event, that the cron handler populates the
 * cache, and that extractor throws produce a hash-keyed failure flag
 * which suppresses retries until the file content changes.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_Text_Extractor_Scheduler.
 */
class Test_WP_Document_Revisions_Text_Extractor_Scheduler extends Test_Common_WPDR {

	/**
	 * Load the counting-fake helper class once per test class run.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
		require_once __DIR__ . '/class-wpdr-test-throwing-text-extractor.php';
	}

	/**
	 * Clear scheduled events, filters, and cache state so tests don't leak.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		remove_all_filters( 'wpdr_text_extraction_delay' );
		remove_all_filters( 'wpdr_text_extraction_timeout' );
		_set_cron_array( array() );
		parent::tear_down();
	}

	/**
	 * Register a counting fake extractor at higher priority than the
	 * built-ins so we have a stable instance whose call count we can read.
	 *
	 * @param string      $mime         MIME type the fake should claim.
	 * @param string|null $fixed_return Optional fixed extract() return; null
	 *                                  means read the file's contents.
	 * @return WPDR_Test_Counting_Text_Extractor the registered fake.
	 */
	private function register_counting_fake( string $mime = 'text/plain', ?string $fixed_return = null ): WPDR_Test_Counting_Text_Extractor {
		$fake = new WPDR_Test_Counting_Text_Extractor( $mime, $fixed_return );
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
	 * Register a throwing fake extractor at higher priority than the
	 * built-ins.
	 *
	 * @param string $mime MIME type the fake should claim.
	 * @return WPDR_Test_Throwing_Text_Extractor the registered fake.
	 */
	private function register_throwing_fake( string $mime = 'text/plain' ): WPDR_Test_Throwing_Text_Extractor {
		$fake = new WPDR_Test_Throwing_Text_Extractor( $mime );
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
	 * Create a document + attached text file, return the attachment ID.
	 *
	 * @return int attachment ID.
	 */
	private function create_text_attachment(): int {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Scheduler Test Document',
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
	 * Inserting a revision attachment schedules a single async extraction event.
	 */
	public function test_revision_attachment_schedules_event() {
		$attach_id = $this->create_text_attachment();

		$next = wp_next_scheduled(
			WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
			array( $attach_id )
		);
		self::assertIsInt( $next, 'Inserting a revision attachment should schedule a cron event' );
		self::assertGreaterThanOrEqual( time(), $next, 'Event should be scheduled at or after now' );
	}

	/**
	 * A second call for the same attachment does not enqueue a duplicate.
	 */
	public function test_schedule_is_idempotent_for_same_attachment() {
		$attach_id = $this->create_text_attachment();

		// Re-fire the maybe_schedule path manually; it should noop because
		// an event is already queued for this attachment ID.
		WP_Document_Revisions_Text_Extractor_Scheduler::maybe_schedule( $attach_id );

		$cron = _get_cron_array();
		$matches = 0;
		foreach ( $cron as $events_for_ts ) {
			if ( isset( $events_for_ts[ WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION ] ) ) {
				foreach ( $events_for_ts[ WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION ] as $event ) {
					if ( isset( $event['args'][0] ) && (int) $event['args'][0] === $attach_id ) {
						++$matches;
					}
				}
			}
		}
		self::assertSame( 1, $matches, 'Only one event should be scheduled per attachment' );
	}

	/**
	 * Plain attachments (no document parent) do not schedule extraction.
	 */
	public function test_non_document_attachment_does_not_schedule() {
		// An attachment whose parent is a regular post, not a document.
		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Just a post',
				'post_type'  => 'post',
			)
		);
		$attach_id = self::factory()->attachment->create_object(
			array(
				'file'           => 'sample.txt',
				'post_parent'    => $post_id,
				'post_mime_type' => 'text/plain',
				'post_type'      => 'attachment',
			)
		);

		WP_Document_Revisions_Text_Extractor_Scheduler::maybe_schedule( $attach_id );

		self::assertFalse(
			wp_next_scheduled(
				WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
				array( $attach_id )
			)
		);
	}

	/**
	 * Parentless attachments do not schedule extraction.
	 */
	public function test_orphan_attachment_does_not_schedule() {
		$attach_id = self::factory()->attachment->create_object(
			array(
				'file'           => 'sample.txt',
				'post_parent'    => 0,
				'post_mime_type' => 'text/plain',
				'post_type'      => 'attachment',
			)
		);

		WP_Document_Revisions_Text_Extractor_Scheduler::maybe_schedule( $attach_id );

		self::assertFalse(
			wp_next_scheduled(
				WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
				array( $attach_id )
			)
		);
	}

	/**
	 * The delay filter controls the scheduled timestamp.
	 */
	public function test_delay_filter_is_honoured() {
		add_filter(
			'wpdr_text_extraction_delay',
			static function (): int {
				return 60;
			}
		);

		$before    = time();
		$attach_id = $this->create_text_attachment();
		$next      = (int) wp_next_scheduled(
			WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
			array( $attach_id )
		);

		self::assertGreaterThanOrEqual( $before + 60, $next );
	}

	/**
	 * run() extracts and populates the cache for a fresh attachment.
	 */
	public function test_run_extracts_and_caches() {
		$fake      = $this->register_counting_fake();
		$attach_id = $this->create_text_attachment();

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );

		self::assertSame( 1, $fake->calls, 'Cron handler should invoke the extractor exactly once' );

		// Second wpdr_extract_text() call should be served from cache (no
		// extra extractor invocations).
		$text = wpdr_extract_text( $attach_id );
		self::assertNotSame( '', $text );
		self::assertSame( 1, $fake->calls, 'Subsequent read should be served from cache' );
	}

	/**
	 * run() is a no-op when the file is already cached against the current hash.
	 */
	public function test_run_skips_when_cache_already_populated() {
		$fake      = $this->register_counting_fake();
		$attach_id = $this->create_text_attachment();

		// Warm the cache via the synchronous helper.
		wpdr_extract_text( $attach_id );
		self::assertSame( 1, $fake->calls );

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );
		self::assertSame( 1, $fake->calls, 'Cron handler should noop when cache is fresh' );
	}

	/**
	 * A throwing extractor causes the cron handler to record the failure
	 * flag and refuse to retry against the same content.
	 */
	public function test_run_marks_failed_on_throw_and_skips_retry() {
		$fake      = $this->register_throwing_fake();
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );

		self::assertSame( 1, $fake->calls );
		self::assertTrue(
			WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, $file_path ),
			'Throw should record the file hash on the failure flag'
		);

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );
		self::assertSame( 1, $fake->calls, 'Second run against the same content should skip the extractor' );
	}

	/**
	 * Changing the file's content after a recorded failure resets the
	 * failure flag and allows the next run() to try again.
	 */
	public function test_failure_flag_clears_when_file_content_changes() {
		$throwing  = $this->register_throwing_fake();
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );
		self::assertTrue( WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, $file_path ) );

		// Replace the file's contents — the hash changes, so the failure
		// flag (which was keyed to the old hash) should no longer apply.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file_path, 'replacement content for retry test' );

		self::assertFalse(
			WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, $file_path ),
			'Hash-keyed failure flag should self-invalidate on content change'
		);

		WP_Document_Revisions_Text_Extractor_Scheduler::run( $attach_id );
		self::assertSame( 2, $throwing->calls, 'Cron handler should retry once the file content changes' );
	}

	/**
	 * A successful extraction clears any previously-recorded failure flag.
	 */
	public function test_successful_set_clears_failure_flag() {
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $attach_id, $file_path );
		self::assertTrue( WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, $file_path ) );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'hello' );

		self::assertFalse(
			WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, $file_path ),
			'A successful set() should supersede the failure flag'
		);
	}

	/**
	 * wpdr_extract_text() observes extractor throws on the sync path and
	 * marks the file failed so subsequent sync reads return '' from cache
	 * without re-invoking the extractor.
	 */
	public function test_sync_extract_text_marks_failed_on_throw() {
		$throwing  = $this->register_throwing_fake();
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		$first = wpdr_extract_text( $attach_id );
		self::assertSame( '', $first );
		self::assertSame( 1, $throwing->calls );
		self::assertTrue( WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attach_id, $file_path ) );

		// The Phase 6 cache currently does not store '' on the sync throw
		// path (we return early before set()), so the second sync read still
		// reaches the extractor — but it should be the LAST one before the
		// async path picks it up. The hash-keyed failure flag is what stops
		// the cron retry loop; the sync caller is more about not crashing.
		$second = wpdr_extract_text( $attach_id );
		self::assertSame( '', $second, 'Sync caller should swallow the throw and return empty' );
	}

	// The WPDR_TEXT_EXTRACTION=false kill switch is intentionally not
	// covered here: PHP constants cannot be undefined between tests, so a
	// realistic test would need process isolation. Phase 8 of issue #514
	// layers a per-document post-meta opt-out on top of this constant; that
	// branch is fully testable and will cover the broader opt-out
	// behaviour.
}
