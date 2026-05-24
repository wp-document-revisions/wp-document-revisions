<?php
/**
 * Tests for the SHA-256-keyed extracted-text cache.
 *
 * Phase 6 of issue #514: verifies that a second wpdr_extract_text() call
 * against the same revision and the same file does not re-invoke the
 * extractor, and that the cache invalidates when the file content changes.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_Text_Extractor_Cache + wpdr_extract_text() caching.
 */
class Test_WP_Document_Revisions_Text_Extractor_Cache extends Test_Common_WPDR {

	/**
	 * Load the counting-fake helper class once per test class run.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
	}

	/**
	 * Clear filters and cache meta so tests don't leak.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		parent::tear_down();
	}

	/**
	 * Register a counting fake extractor at a higher priority than the
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
	 * Create a document + attached text file, return the attachment ID.
	 *
	 * @return int attachment ID.
	 */
	private function create_text_attachment(): int {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'Cache Test Document',
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
	 * Cache::get returns null when no meta has been written yet.
	 */
	public function test_cache_get_returns_null_on_miss() {
		$attach_id = $this->create_text_attachment();

		$file_path = get_attached_file( $attach_id );
		self::assertNull( WP_Document_Revisions_Text_Extractor_Cache::get( $attach_id, $file_path ) );
	}

	/**
	 * Cache::set followed by Cache::get returns the stored text.
	 */
	public function test_cache_set_then_get_returns_stored_text() {
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'hello cache' );

		self::assertSame( 'hello cache', WP_Document_Revisions_Text_Extractor_Cache::get( $attach_id, $file_path ) );
	}

	/**
	 * Cache stores empty strings — a legitimate "extractor returned nothing"
	 * is distinguishable from "not cached yet" via the hash check.
	 */
	public function test_cache_set_then_get_returns_empty_string_distinctly() {
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, '' );

		self::assertSame( '', WP_Document_Revisions_Text_Extractor_Cache::get( $attach_id, $file_path ) );
	}

	/**
	 * Cache::get returns null when the file content has changed since the
	 * cached hash was written.
	 */
	public function test_cache_get_returns_null_when_file_hash_differs() {
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'old text' );

		// Overwrite the file's contents so the SHA-256 changes.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file_path, 'new file body that differs from the original' );

		self::assertNull( WP_Document_Revisions_Text_Extractor_Cache::get( $attach_id, $file_path ) );
	}

	/**
	 * Cache::get returns null for a non-existent file path.
	 */
	public function test_cache_get_returns_null_for_unreadable_file() {
		$attach_id = $this->create_text_attachment();

		self::assertNull(
			WP_Document_Revisions_Text_Extractor_Cache::get( $attach_id, '/no/such/file.txt' )
		);
	}

	/**
	 * Cache::get and Cache::set are no-ops on invalid attachment IDs.
	 */
	public function test_cache_handles_invalid_attachment_id() {
		self::assertNull( WP_Document_Revisions_Text_Extractor_Cache::get( 0, self::$test_file ) );
		self::assertNull( WP_Document_Revisions_Text_Extractor_Cache::get( -1, self::$test_file ) );

		// set() should silently no-op rather than throwing.
		WP_Document_Revisions_Text_Extractor_Cache::set( 0, self::$test_file, 'irrelevant' );
		WP_Document_Revisions_Text_Extractor_Cache::set( -1, self::$test_file, 'irrelevant' );
	}

	/**
	 * First wpdr_extract_text() call invokes the extractor and writes the
	 * cache; the second call against the same revision and the same file
	 * content returns the cached text without re-invoking the extractor.
	 */
	public function test_wpdr_extract_text_caches_after_first_call() {
		$fake      = $this->register_counting_fake();
		$attach_id = $this->create_text_attachment();

		$text1 = wpdr_extract_text( $attach_id );
		self::assertSame( 1, $fake->calls, 'First call should invoke the extractor exactly once' );
		self::assertNotSame( '', $text1, 'Test fixture is non-empty, so first extraction should return text' );

		$text2 = wpdr_extract_text( $attach_id );
		self::assertSame( 1, $fake->calls, 'Second call should be served from cache' );
		self::assertSame( $text1, $text2 );
	}

	/**
	 * Cache invalidates when the underlying file's content changes — the
	 * next wpdr_extract_text() call re-invokes the extractor against the
	 * new content.
	 */
	public function test_wpdr_extract_text_reruns_when_file_content_changes() {
		$fake      = $this->register_counting_fake();
		$attach_id = $this->create_text_attachment();

		wpdr_extract_text( $attach_id );
		self::assertSame( 1, $fake->calls );

		$file_path = get_attached_file( $attach_id );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file_path, 'updated body content' );

		$text = wpdr_extract_text( $attach_id );
		self::assertSame( 2, $fake->calls, 'Hash mismatch should trigger re-extraction' );
		self::assertStringContainsString( 'updated body content', $text );
	}

	/**
	 * Cache is keyed per attachment: a second attachment with different
	 * content does not share the first one's cached text.
	 */
	public function test_cache_isolated_per_attachment() {
		$fake = $this->register_counting_fake();

		$first  = $this->create_text_attachment();
		$second = $this->create_text_attachment();

		wpdr_extract_text( $first );
		wpdr_extract_text( $second );

		self::assertSame( 2, $fake->calls, 'Each attachment should miss its own cache on first call' );

		wpdr_extract_text( $first );
		wpdr_extract_text( $second );

		self::assertSame( 2, $fake->calls, 'Subsequent calls should be served from cache for each attachment' );
	}

	/**
	 * Caching an empty extraction prevents the extractor from running again
	 * on subsequent reads — important so a scanned PDF or password-protected
	 * file is parsed at most once per file revision.
	 */
	public function test_wpdr_extract_text_caches_empty_extractions() {
		// Configure the fake to return '' so we exercise the
		// "extractor returned empty" branch without depending on file content.
		$fake      = $this->register_counting_fake( 'text/plain', '' );
		$attach_id = $this->create_text_attachment();

		self::assertSame( '', wpdr_extract_text( $attach_id ) );
		self::assertSame( 1, $fake->calls );

		self::assertSame( '', wpdr_extract_text( $attach_id ) );
		self::assertSame( 1, $fake->calls, 'Empty extraction should still be served from cache on the second call' );
	}

	/**
	 * Passing a non-attachment post ID returns '' without touching the
	 * cache (defensive guard against a caller passing the parent document
	 * ID instead of the attachment ID).
	 */
	public function test_wpdr_extract_text_refuses_non_attachment_post() {
		$fake   = $this->register_counting_fake();
		$doc_id = self::factory()->post->create(
			array(
				'post_title' => 'Just a regular post',
				'post_type'  => 'post',
			)
		);

		self::assertSame( '', wpdr_extract_text( $doc_id ) );
		self::assertSame( 0, $fake->calls, 'Non-attachment posts should not reach the extractor' );

		// Cache should not have been written either.
		self::assertSame(
			'',
			(string) get_post_meta( $doc_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_HASH, true )
		);
	}

	/**
	 * Identity helper returns the bare class name for an extractor with no
	 * VERSION constant — graceful degradation for third-party impls.
	 */
	public function test_identity_for_returns_class_when_no_version_constant() {
		$fake = new WPDR_Test_Counting_Text_Extractor();

		self::assertSame(
			'WPDR_Test_Counting_Text_Extractor',
			WP_Document_Revisions_Text_Extractor_Cache::identity_for( $fake )
		);
	}

	/**
	 * Identity helper suffixes the class name with @<version> when the
	 * class defines a public VERSION constant.
	 */
	public function test_identity_for_includes_version_constant_when_present() {
		$pdf = new WP_Document_Revisions_PDF_Text_Extractor();

		$identity = WP_Document_Revisions_Text_Extractor_Cache::identity_for( $pdf );

		self::assertStringStartsWith( 'WP_Document_Revisions_PDF_Text_Extractor@', $identity );
		self::assertSame(
			'WP_Document_Revisions_PDF_Text_Extractor@' . WP_Document_Revisions_PDF_Text_Extractor::VERSION,
			$identity
		);
	}

	/**
	 * Cache write with an explicit identity populates the META_KEY_EXTRACTOR meta.
	 */
	public function test_set_writes_extractor_identity_meta() {
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set(
			$attach_id,
			$file_path,
			'hello',
			'My_Custom_Extractor@2.0.0'
		);

		self::assertSame(
			'My_Custom_Extractor@2.0.0',
			(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_EXTRACTOR, true )
		);
	}

	/**
	 * Cache write with a null identity clears any stale identity meta — so a
	 * caller that downgrades to "I don't know which tool produced this"
	 * cannot leave the previous tool's identity in place.
	 */
	public function test_set_with_null_identity_clears_existing_extractor_meta() {
		$attach_id = $this->create_text_attachment();
		$file_path = get_attached_file( $attach_id );

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'first', 'Old_Extractor@1.0.0' );
		self::assertSame(
			'Old_Extractor@1.0.0',
			(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_EXTRACTOR, true )
		);

		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'second', null );

		self::assertSame(
			'',
			(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_EXTRACTOR, true )
		);
	}

	/**
	 * Synchronous extraction records the dispatching extractor's identity in
	 * post meta so a WP-CLI backfill can target outdated tooling.
	 */
	public function test_wpdr_extract_text_records_extractor_identity() {
		$this->register_counting_fake();
		$attach_id = $this->create_text_attachment();

		wpdr_extract_text( $attach_id );

		self::assertSame(
			'WPDR_Test_Counting_Text_Extractor',
			(string) get_post_meta( $attach_id, WP_Document_Revisions_Text_Extractor_Cache::META_KEY_EXTRACTOR, true )
		);
	}

	/**
	 * An unreadable file path does not wipe a previously-cached extraction.
	 * Simulates a transient I/O blip on a real revision.
	 */
	public function test_unreadable_file_does_not_invalidate_existing_cache() {
		$fake      = $this->register_counting_fake();
		$attach_id = $this->create_text_attachment();

		$text = wpdr_extract_text( $attach_id );
		self::assertSame( 1, $fake->calls );

		// Move the file away so the next get_attached_file() returns a path
		// that no longer exists.
		$file_path  = get_attached_file( $attach_id );
		$moved_path = $file_path . '.bak';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		rename( $file_path, $moved_path );

		try {
			$during = wpdr_extract_text( $attach_id );
			self::assertSame( '', $during, 'Unreadable file should yield empty for this call' );
			self::assertSame( 1, $fake->calls, 'Extractor should not run on an unreadable file' );
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			rename( $moved_path, $file_path );
		}

		// And the cache should still be there once the file is back.
		$after = wpdr_extract_text( $attach_id );
		self::assertSame( 1, $fake->calls, 'Cache should still serve the original text after the file is restored' );
		self::assertSame( $text, $after );
	}
}
