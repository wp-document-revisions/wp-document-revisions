<?php
/**
 * Tests for the WP-CLI text-extraction backfill helpers.
 *
 * Phase 9 of issue #514: exercises the pure-PHP helpers that
 * {@see WP_Document_Revisions_Text_Extraction_CLI_Command::extract_text()}
 * delegates to (selector validation, document discovery, per-attachment
 * filtering). The WP_CLI-facing entry point itself is not unit-tested
 * here because its side effects (`WP_CLI::log` / `success` / `error`)
 * require the WP-CLI runtime; the helpers are the testability seam.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_Text_Extraction_CLI_Command.
 */
class Test_WP_Document_Revisions_Text_Extraction_CLI_Command extends Test_Common_WPDR {

	/**
	 * Pull in the CLI command class (only auto-loaded when WP_CLI is
	 * defined; tests run without it).
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../includes/class-wp-document-revisions-text-extraction-cli-command.php';
	}

	/**
	 * Clear cron + filters so test state does not leak.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_text_extractors' );
		_set_cron_array( array() );
		parent::tear_down();
	}

	/**
	 * Create a document, attach the standard test fixture, return both IDs.
	 *
	 * @param string $title document title to disambiguate fixtures.
	 * @return array{0:int,1:int} [ document_id, attachment_id ].
	 */
	private function create_document_with_attachment( string $title = 'CLI Test Document' ): array {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => $title,
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
	 * Selector validation requires one of --all, --missing, --id.
	 */
	public function test_validate_selector_requires_one_selector() {
		$err = WP_Document_Revisions_Text_Extraction_CLI_Command::validate_selector( array() );

		self::assertIsString( $err );
		self::assertStringContainsString( '--all', $err );
		self::assertStringContainsString( '--missing', $err );
		self::assertStringContainsString( '--id', $err );
	}

	/**
	 * Selector validation rejects --all and --missing together.
	 */
	public function test_validate_selector_rejects_all_and_missing() {
		$err = WP_Document_Revisions_Text_Extraction_CLI_Command::validate_selector(
			array(
				'all'     => true,
				'missing' => true,
			)
		);

		self::assertIsString( $err );
		self::assertStringContainsString( 'mutually exclusive', $err );
	}

	/**
	 * Selector validation accepts each valid combination.
	 */
	public function test_validate_selector_accepts_valid_combinations() {
		self::assertNull(
			WP_Document_Revisions_Text_Extraction_CLI_Command::validate_selector( array( 'all' => true ) )
		);
		self::assertNull(
			WP_Document_Revisions_Text_Extraction_CLI_Command::validate_selector( array( 'missing' => true ) )
		);
		self::assertNull(
			WP_Document_Revisions_Text_Extraction_CLI_Command::validate_selector( array( 'id' => '42' ) )
		);
		// --id with another selector is allowed; --id wins.
		self::assertNull(
			WP_Document_Revisions_Text_Extraction_CLI_Command::validate_selector(
				array(
					'all' => true,
					'id'  => '42',
				)
			)
		);
	}

	/**
	 * `--id=<id>` returns just that document when it exists and is a document.
	 */
	public function test_collect_target_document_ids_id_returns_single_document() {
		list( $doc_id, ) = $this->create_document_with_attachment();

		$ids = WP_Document_Revisions_Text_Extraction_CLI_Command::collect_target_document_ids(
			array( 'id' => (string) $doc_id )
		);

		self::assertSame( array( $doc_id ), $ids );
	}

	/**
	 * `--id` with a non-existent ID returns an empty list.
	 */
	public function test_collect_target_document_ids_unknown_id_returns_empty() {
		$ids = WP_Document_Revisions_Text_Extraction_CLI_Command::collect_target_document_ids(
			array( 'id' => '999999' )
		);

		self::assertSame( array(), $ids );
	}

	/**
	 * `--id` pointing at a non-document post returns an empty list.
	 */
	public function test_collect_target_document_ids_non_document_id_returns_empty() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Just a post' ) );

		$ids = WP_Document_Revisions_Text_Extraction_CLI_Command::collect_target_document_ids(
			array( 'id' => (string) $post_id )
		);

		self::assertSame( array(), $ids );
	}

	/**
	 * When --id and --all are both present, --id wins and only the
	 * single document is returned (the documented precedence rule).
	 */
	public function test_collect_target_document_ids_id_wins_over_all() {
		list( $doc_a ) = $this->create_document_with_attachment( 'doc-a' );
		list( $doc_b ) = $this->create_document_with_attachment( 'doc-b' );

		$ids = WP_Document_Revisions_Text_Extraction_CLI_Command::collect_target_document_ids(
			array(
				'all' => true,
				'id'  => (string) $doc_a,
			)
		);

		self::assertSame( array( $doc_a ), $ids );
		self::assertNotContains( $doc_b, $ids, '--id must win and ignore --all when both are present' );
	}

	/**
	 * `--all` returns every document in the library.
	 */
	public function test_collect_target_document_ids_all_returns_every_document() {
		list( $doc_a ) = $this->create_document_with_attachment( 'doc-a' );
		list( $doc_b ) = $this->create_document_with_attachment( 'doc-b' );

		$ids = WP_Document_Revisions_Text_Extraction_CLI_Command::collect_target_document_ids(
			array( 'all' => true )
		);

		self::assertContains( $doc_a, $ids );
		self::assertContains( $doc_b, $ids );
	}

	/**
	 * `attachment_ids_for_document` delegates to $wpdr->get_attachments and
	 * returns the revision attachment IDs.
	 */
	public function test_attachment_ids_for_document_returns_revision_ids() {
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();

		$ids = WP_Document_Revisions_Text_Extraction_CLI_Command::attachment_ids_for_document( $doc_id );

		self::assertContains( $attach_id, $ids );
	}

	/**
	 * `should_process` returns false for non-attachment posts.
	 */
	public function test_should_process_returns_false_for_non_attachment() {
		$post_id = self::factory()->post->create();

		self::assertFalse(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process( $post_id, array( 'all' => true ) )
		);
	}

	/**
	 * `--all` without --force skips attachments that already have cached text.
	 */
	public function test_should_process_skips_cached_attachment_without_force() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'cached', 'Some_Extractor@1.0.0' );

		self::assertFalse(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array( 'all' => true )
			)
		);
	}

	/**
	 * `--force` overrides the cache check.
	 */
	public function test_should_process_force_overrides_cache() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'cached', 'Some_Extractor@1.0.0' );

		self::assertTrue(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array(
					'all'   => true,
					'force' => true,
				)
			)
		);
	}

	/**
	 * `--missing` skips attachments that already have cached text.
	 */
	public function test_should_process_missing_skips_cached() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::set( $attach_id, $file_path, 'cached', 'Some_Extractor@1.0.0' );

		self::assertFalse(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array( 'missing' => true )
			)
		);
	}

	/**
	 * `--missing` returns true for an attachment that has never been extracted.
	 */
	public function test_should_process_missing_returns_true_for_fresh_attachment() {
		list( , $attach_id ) = $this->create_document_with_attachment();

		self::assertTrue(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array( 'missing' => true )
			)
		);
	}

	/**
	 * `--missing` skips attachments on the failure list without --force.
	 * This is the loop-prevention contract documented on the command.
	 */
	public function test_should_process_missing_skips_failed_without_force() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $attach_id, $file_path );

		self::assertFalse(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array( 'missing' => true )
			)
		);
	}

	/**
	 * `--missing --force` retries attachments on the failure list.
	 */
	public function test_should_process_missing_force_retries_failed() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $attach_id, $file_path );

		self::assertTrue(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array(
					'missing' => true,
					'force'   => true,
				)
			)
		);
	}

	/**
	 * `--extractor=<class>` matches as a substring against the recorded identity.
	 */
	public function test_should_process_extractor_filter_matches_substring() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::set(
			$attach_id,
			$file_path,
			'cached',
			'WP_Document_Revisions_PDF_Text_Extractor@0.9.0'
		);

		// Substring match on class fragment with --force so cache doesn't block.
		self::assertTrue(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array(
					'all'       => true,
					'force'     => true,
					'extractor' => 'PDF_Text_Extractor',
				)
			)
		);

		// Substring match on version fragment.
		self::assertTrue(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array(
					'all'       => true,
					'force'     => true,
					'extractor' => '@0.9.0',
				)
			)
		);
	}

	/**
	 * `--extractor=<class>` skips attachments whose recorded identity does not match.
	 */
	public function test_should_process_extractor_filter_skips_mismatch() {
		list( , $attach_id ) = $this->create_document_with_attachment();
		$file_path           = get_attached_file( $attach_id );
		WP_Document_Revisions_Text_Extractor_Cache::set(
			$attach_id,
			$file_path,
			'cached',
			'WP_Document_Revisions_PDF_Text_Extractor@1.0.0'
		);

		self::assertFalse(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array(
					'all'       => true,
					'force'     => true,
					'extractor' => 'Some_Other_Extractor',
				)
			)
		);
	}

	/**
	 * `--extractor=<class>` skips attachments with no recorded identity meta.
	 */
	public function test_should_process_extractor_filter_skips_when_identity_missing() {
		list( , $attach_id ) = $this->create_document_with_attachment();

		self::assertFalse(
			WP_Document_Revisions_Text_Extraction_CLI_Command::should_process(
				$attach_id,
				array(
					'all'       => true,
					'force'     => true,
					'extractor' => 'WP_Document_Revisions_PDF_Text_Extractor',
				)
			)
		);
	}
}
