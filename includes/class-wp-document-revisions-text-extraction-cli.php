<?php
/**
 * WP-CLI command: text extraction backfill.
 *
 * `wp document-revisions extract-text [--all|--missing|--id=<id>]
 *     [--extractor=<class>] [--force] [--dry-run]`
 *
 * Walks revision attachments under the `document` custom post type and
 * runs (or skips) text extraction so an existing library can populate the
 * cache that newly-uploaded documents fill automatically via the async
 * scheduler. Honours the phase-8 opt-out (both the sitewide constant and
 * the per-document checkbox) — opted-out documents are reported as
 * skipped rather than processed.
 *
 * Selector flags (one is required):
 *
 *   --all          Walk every document in the library.
 *   --missing      Walk only revision attachments that have no cached
 *                  text AND are not currently on the failure list. The
 *                  failure-list check stops the command from looping
 *                  forever on a PDF the extractor already threw on;
 *                  --force is the escape hatch to retry failures.
 *   --id=<id>      Walk a single document by ID.
 *
 * Modifier flags:
 *
 *   --extractor=<class>  Only process attachments whose recorded
 *                        extractor identity contains this substring,
 *                        e.g. `--extractor=PDF_Text_Extractor` or
 *                        `--extractor=Vendor_Name@1.0.0`. Attachments
 *                        with no recorded identity (never extracted, or
 *                        from before phase 7) do not match.
 *   --force              Re-extract even when cached text is already
 *                        present; also clears the failure flag.
 *   --dry-run            Print what would be done without writing
 *                        anything (no extractor invocation, no cache
 *                        writes).
 *
 * Phase 9 of issue #514.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Backfill text extraction across an existing document library.
 */
class WP_Document_Revisions_Text_Extraction_CLI_Command {

	/**
	 * Batch size used when paginating documents for `--all` / `--missing`.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 100;

	/**
	 * Entry point dispatched by `wp document-revisions extract-text`.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Process every document in the library.
	 *
	 * [--missing]
	 * : Process only revision attachments with no cached text that are
	 * not already on the failure list.
	 *
	 * [--id=<id>]
	 * : Process a single document by ID.
	 *
	 * [--extractor=<class>]
	 * : Only process attachments whose recorded extractor identity
	 * matches this substring.
	 *
	 * [--force]
	 * : Re-extract even when cached text is already present.
	 *
	 * [--dry-run]
	 * : Print what would be done without invoking any extractor or
	 * writing cache meta.
	 *
	 * @param array<int, string>     $args       positional CLI arguments (unused).
	 * @param array<string, string>  $assoc_args parsed `--flag` arguments.
	 * @return void
	 */
	public static function extract_text( array $args, array $assoc_args ): void {
		unset( $args );

		$error = self::validate_selector( $assoc_args );
		if ( null !== $error ) {
			WP_CLI::error( $error );
			return;
		}

		$document_ids = self::collect_target_document_ids( $assoc_args );
		if ( empty( $document_ids ) ) {
			WP_CLI::success( 'No matching documents.' );
			return;
		}

		$dry_run    = self::flag_set( $assoc_args, 'dry-run' );
		$totals     = array(
			'extracted' => 0,
			'skipped'   => 0,
			'failed'    => 0,
		);

		foreach ( $document_ids as $document_id ) {
			if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $document_id ) ) {
				WP_CLI::log( sprintf( 'doc %d: opted out of text extraction — skipped', $document_id ) );
				++$totals['skipped'];
				continue;
			}

			foreach ( self::attachment_ids_for_document( $document_id ) as $attachment_id ) {
				if ( ! self::should_process( $attachment_id, $assoc_args ) ) {
					++$totals['skipped'];
					continue;
				}
				$outcome = self::process_attachment( $attachment_id, $dry_run );
				++$totals[ $outcome ];
			}
		}

		WP_CLI::success(
			sprintf(
				'Done. extracted=%d skipped=%d failed=%d',
				$totals['extracted'],
				$totals['skipped'],
				$totals['failed']
			)
		);
	}

	/**
	 * Validate that exactly one selector flag was provided. Returns a
	 * human-readable error string when validation fails, or null on
	 * success.
	 *
	 * @param array<string, string> $assoc_args parsed associative args.
	 * @return string|null error message, or null when valid.
	 */
	public static function validate_selector( array $assoc_args ): ?string {
		$has_all     = self::flag_set( $assoc_args, 'all' );
		$has_missing = self::flag_set( $assoc_args, 'missing' );
		$has_id      = isset( $assoc_args['id'] ) && '' !== (string) $assoc_args['id'];

		$selectors = (int) $has_all + (int) $has_missing + (int) $has_id;
		if ( 0 === $selectors ) {
			return 'one of --all, --missing, or --id=<id> is required.';
		}
		if ( $has_all && $has_missing ) {
			return '--all and --missing are mutually exclusive.';
		}
		return null;
	}

	/**
	 * Resolve the document IDs to walk based on selector flags.
	 *
	 * `--id=<id>` wins over `--all` / `--missing` when combined. The
	 * `--all` / `--missing` paths paginate via `WP_Query` with
	 * `fields => 'ids'` so only integers (not full WP_Post objects) are
	 * accumulated; flushing the object cache between batches would
	 * invalidate the post lookups `extract_text()` is about to do on the
	 * returned IDs, so the loop deliberately does not flush.
	 *
	 * @param array<string, string> $assoc_args parsed associative args.
	 * @return array<int, int> document post IDs, in WP_Query order.
	 */
	public static function collect_target_document_ids( array $assoc_args ): array {
		if ( isset( $assoc_args['id'] ) && '' !== (string) $assoc_args['id'] ) {
			$document_id = (int) $assoc_args['id'];
			if ( $document_id <= 0 || 'document' !== get_post_type( $document_id ) ) {
				return array();
			}
			return array( $document_id );
		}

		$document_ids = array();
		$page         = 1;
		do {
			$query = new WP_Query(
				array(
					'post_type'      => 'document',
					'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
					'posts_per_page' => self::BATCH_SIZE,
					'paged'          => $page,
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);
			foreach ( $query->posts as $id ) {
				$document_ids[] = (int) $id;
			}
			$page_size = count( $query->posts );
			++$page;
		} while ( self::BATCH_SIZE === $page_size );

		return $document_ids;
	}

	/**
	 * Whether an attachment passes the per-attachment filters
	 * (`--missing`, `--extractor`, and the implicit cache-already-warm
	 * skip overridden by `--force`).
	 *
	 * @param int                   $attachment_id ID of the revision attachment.
	 * @param array<string, string> $assoc_args    parsed associative args.
	 * @return bool true when the attachment should be processed.
	 */
	public static function should_process( int $attachment_id, array $assoc_args ): bool {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! is_string( $file_path ) || '' === $file_path || ! is_readable( $file_path ) ) {
			return false;
		}

		$force     = self::flag_set( $assoc_args, 'force' );
		$missing   = self::flag_set( $assoc_args, 'missing' );
		$extractor = isset( $assoc_args['extractor'] ) ? (string) $assoc_args['extractor'] : '';

		$cached_text = WP_Document_Revisions_Text_Extractor_Cache::get( $attachment_id, $file_path );
		$is_failed   = WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attachment_id, $file_path );

		if ( $missing ) {
			// "Missing" excludes the failure list deliberately: extraction
			// already threw on this content once and would throw again
			// without intervention. --force is the documented escape hatch.
			if ( $is_failed && ! $force ) {
				return false;
			}
			if ( null !== $cached_text ) {
				return false;
			}
		} elseif ( null !== $cached_text && ! $force ) {
			// --all / --id with no --force: don't re-extract what's already cached.
			return false;
		}

		if ( '' !== $extractor ) {
			$identity = (string) get_post_meta(
				$attachment_id,
				WP_Document_Revisions_Text_Extractor_Cache::META_KEY_EXTRACTOR,
				true
			);
			if ( '' === $identity || false === strpos( $identity, $extractor ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the revision attachment IDs for a document, in revision order.
	 *
	 * Delegates to `$wpdr->get_attachments()` (the same helper the admin
	 * trait uses to enumerate revisions) so behaviour matches what users
	 * see in the document edit screen.
	 *
	 * @param int $document_id ID of the document post.
	 * @return array<int, int> attachment IDs.
	 */
	public static function attachment_ids_for_document( int $document_id ): array {
		global $wpdr;
		if ( ! $wpdr || ! method_exists( $wpdr, 'get_attachments' ) ) {
			return array();
		}
		$attachments = $wpdr->get_attachments( $document_id );
		if ( ! is_array( $attachments ) ) {
			return array();
		}
		$ids = array();
		foreach ( $attachments as $attachment ) {
			$id = isset( $attachment->ID ) ? (int) $attachment->ID : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * Run extraction for a single attachment, returning the outcome
	 * bucket for the totals report.
	 *
	 * @param int  $attachment_id ID of the revision attachment.
	 * @param bool $dry_run       when true, only log the intended action.
	 * @return string one of 'extracted', 'skipped', or 'failed'.
	 */
	private static function process_attachment( int $attachment_id, bool $dry_run ): string {
		$file_path = get_attached_file( $attachment_id );
		if ( ! is_string( $file_path ) || '' === $file_path ) {
			WP_CLI::log( sprintf( 'attach %d: no file path — skipped', $attachment_id ) );
			return 'skipped';
		}

		$mime_type = (string) get_post_mime_type( $attachment_id );
		if ( '' === $mime_type ) {
			WP_CLI::log( sprintf( 'attach %d: no MIME type — skipped', $attachment_id ) );
			return 'skipped';
		}

		$extractor = WP_Document_Revisions_Text_Extractor_Registry::find_for( $mime_type );
		if ( null === $extractor ) {
			WP_CLI::log(
				sprintf( 'attach %d: no extractor for %s — skipped', $attachment_id, $mime_type )
			);
			return 'skipped';
		}

		if ( $dry_run ) {
			WP_CLI::log(
				sprintf( 'attach %d: would extract via %s', $attachment_id, get_class( $extractor ) )
			);
			return 'extracted';
		}

		try {
			$text = $extractor->extract( $file_path, $mime_type );
		} catch ( Throwable $e ) {
			WP_CLI::log(
				sprintf( 'attach %d: extraction threw (%s) — marked failed', $attachment_id, $e->getMessage() )
			);
			WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $attachment_id, $file_path );
			return 'failed';
		}

		$identity = WP_Document_Revisions_Text_Extractor_Cache::identity_for( $extractor );
		WP_Document_Revisions_Text_Extractor_Cache::set( $attachment_id, $file_path, $text, $identity );
		WP_CLI::log(
			sprintf( 'attach %d: extracted via %s (%d chars)', $attachment_id, get_class( $extractor ), strlen( $text ) )
		);
		return 'extracted';
	}

	/**
	 * Whether a boolean WP-CLI flag is present and truthy. WP-CLI
	 * normally passes flag-only args as `true` (boolean), but tests
	 * exercise the helpers directly with string arrays, so accept either.
	 *
	 * @param array<string, mixed> $assoc_args parsed associative args.
	 * @param string               $key        flag name without leading dashes.
	 * @return bool true when the flag should be treated as set.
	 */
	private static function flag_set( array $assoc_args, string $key ): bool {
		if ( ! array_key_exists( $key, $assoc_args ) ) {
			return false;
		}
		$value = $assoc_args[ $key ];
		if ( is_bool( $value ) ) {
			return $value;
		}
		return '' !== (string) $value && '0' !== (string) $value && 'false' !== (string) $value;
	}
}
