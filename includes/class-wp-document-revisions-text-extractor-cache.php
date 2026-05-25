<?php
/**
 * SHA-256-keyed cache for extracted text.
 *
 * Stored as post meta on the revision attachment:
 *   _wpdr_extracted_text           — the extracted plain text (may be empty)
 *   _wpdr_extracted_text_hash      — SHA-256 of the file at the time of extraction
 *   _wpdr_extracted_text_extractor — identity of the extractor that produced the text
 *   _wpdr_extraction_failed        — SHA-256 of a file whose extraction threw
 *
 * `get()` returns a `?string`: `null` for a cache miss (no meta, or the
 * file's current hash does not match the stored one), or the cached string
 * (possibly empty) on hit. This distinction lets callers tell the difference
 * between "not extracted yet" and "extracted to an empty string" (e.g., a
 * scanned PDF or a password-protected file) without re-running the extractor.
 *
 * Phase 7 of issue #514 adds the `_wpdr_extraction_failed` flag for hard
 * failures: when an extractor throws, the scheduler records the file's hash
 * here and refuses to retry against the same content. A successful set() or
 * a file replacement clears the flag automatically.
 *
 * @since 5.0.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hash-keyed post-meta cache for extracted document text.
 */
class WP_Document_Revisions_Text_Extractor_Cache {

	/**
	 * Post meta key for the cached extracted text.
	 *
	 * @var string
	 */
	const META_KEY_TEXT = '_wpdr_extracted_text';

	/**
	 * Post meta key for the SHA-256 of the file at extraction time.
	 *
	 * @var string
	 */
	const META_KEY_HASH = '_wpdr_extracted_text_hash';

	/**
	 * Post meta key for the identity of the extractor that produced the text.
	 *
	 * The value is a single string formatted by {@see self::identity_for()}:
	 * the extractor's fully-qualified class name, optionally suffixed with
	 * `@<version>` when the class defines a public `VERSION` constant. Stored
	 * so a later WP-CLI backfill can target everything produced by an
	 * outdated tool for reprocessing without re-extracting the entire library.
	 *
	 * @var string
	 */
	const META_KEY_EXTRACTOR = '_wpdr_extracted_text_extractor';

	/**
	 * Post meta key for the SHA-256 of a file whose extraction threw.
	 *
	 * Phase 7 of issue #514: when an extractor throws a hard failure, the
	 * scheduler records the file's hash here so the async retry path does
	 * not re-attempt extraction against the same broken file. The flag is
	 * automatically cleared on the next successful set() or whenever the
	 * file's content changes (the recorded hash no longer matches).
	 *
	 * @var string
	 */
	const META_KEY_FAILED_HASH = '_wpdr_extraction_failed';

	/**
	 * Look up cached text for an attachment whose file hash matches the
	 * stored one.
	 *
	 * @param int    $attachment_id ID of the revision attachment post.
	 * @param string $file_path     absolute path to the current file on disk.
	 * @return string|null cached text on hit (possibly empty), null on miss.
	 */
	public static function get( int $attachment_id, string $file_path ): ?string {
		if ( $attachment_id <= 0 ) {
			return null;
		}

		$current_hash = self::file_hash( $file_path );
		if ( null === $current_hash ) {
			return null;
		}

		$cached_hash = (string) get_post_meta( $attachment_id, self::META_KEY_HASH, true );
		if ( '' === $cached_hash || $cached_hash !== $current_hash ) {
			return null;
		}

		$cached_text = get_post_meta( $attachment_id, self::META_KEY_TEXT, true );
		// Meta returns '' when unset, which we tolerate as "cached as empty
		// extraction". The hash match above is what proves the cache hit.
		return is_string( $cached_text ) ? $cached_text : '';
	}

	/**
	 * Store extracted text and the SHA-256 of the file it came from.
	 *
	 * Silently no-ops on invalid attachment IDs or unhashable files so a
	 * caching failure cannot break the calling extraction path. When
	 * `$identity` is non-null it is written to the
	 * {@see self::META_KEY_EXTRACTOR} meta; when null any existing identity
	 * meta is cleared so a future query cannot misattribute the cached text
	 * to a tool that did not produce it.
	 *
	 * @param int         $attachment_id ID of the revision attachment post.
	 * @param string      $file_path     absolute path to the file the text came from.
	 * @param string      $text          extracted text to cache (may be empty).
	 * @param string|null $identity      identity string for the producing extractor,
	 *                                   typically from {@see self::identity_for()}.
	 *                                   Null skips writing identity meta.
	 * @return void
	 */
	public static function set( int $attachment_id, string $file_path, string $text, ?string $identity = null ): void {
		if ( $attachment_id <= 0 ) {
			return;
		}

		$current_hash = self::file_hash( $file_path );
		if ( null === $current_hash ) {
			return;
		}

		update_post_meta( $attachment_id, self::META_KEY_TEXT, $text );
		update_post_meta( $attachment_id, self::META_KEY_HASH, $current_hash );
		if ( null === $identity ) {
			delete_post_meta( $attachment_id, self::META_KEY_EXTRACTOR );
		} else {
			update_post_meta( $attachment_id, self::META_KEY_EXTRACTOR, $identity );
		}
		// A successful extraction supersedes any previously-recorded failure
		// for this file, so the async scheduler is free to run again if the
		// file is later replaced.
		delete_post_meta( $attachment_id, self::META_KEY_FAILED_HASH );

		/**
		 * Fires after extracted text is successfully cached for a
		 * revision attachment.
		 *
		 * Phase 11's AI-summary scheduler listens for this so it can
		 * queue a summary cron event once extracted text is available.
		 * Third parties can hook this for downstream consumers
		 * (search indexing, embeddings, etc.) without monkey-patching
		 * the cache class.
		 *
		 * @since 5.0.0
		 *
		 * @param int $attachment_id ID of the revision attachment whose
		 *                           extracted text was just cached.
		 */
		do_action( 'wpdr_text_extracted', $attachment_id );
	}

	/**
	 * Format an identity string for an extractor instance.
	 *
	 * Returns the fully-qualified class name, optionally suffixed with
	 * `@<version>` when the class defines a public `VERSION` constant. This
	 * is the value written to {@see self::META_KEY_EXTRACTOR} by callers of
	 * {@see self::set()}, and the key a future WP-CLI backfill can match
	 * against when reprocessing the output of an outdated tool.
	 *
	 * Third-party extractors that do not define `VERSION` get class-only
	 * identity (graceful degradation, no interface contract change).
	 *
	 * @param WP_Document_Revisions_Text_Extractor $extractor producing extractor.
	 * @return string identity string suitable for {@see self::META_KEY_EXTRACTOR}.
	 */
	public static function identity_for( WP_Document_Revisions_Text_Extractor $extractor ): string {
		$class = get_class( $extractor );
		if ( defined( $class . '::VERSION' ) ) {
			$version = (string) constant( $class . '::VERSION' );
			if ( '' !== $version ) {
				return $class . '@' . $version;
			}
		}
		return $class;
	}

	/**
	 * Delete every cache-managed meta key for an attachment.
	 *
	 * Removes the extracted text, its source hash, the producing extractor
	 * identity, and the failure flag. Used by phase 8 when a document is
	 * opted out of text extraction so previously-cached output cannot leak
	 * through readers that hit the cache directly, and by phase 9's WP-CLI
	 * `--force` path to guarantee a clean re-extraction.
	 *
	 * The failed-hash key is included on purpose: clearing the text without
	 * clearing the failure flag would leave the attachment unable to retry
	 * extraction (its current hash would still match the failure record), so
	 * a later opt-in or `--force` would silently noop.
	 *
	 * @param int $attachment_id ID of the revision attachment post.
	 * @return void
	 */
	public static function clear( int $attachment_id ): void {
		if ( $attachment_id <= 0 ) {
			return;
		}

		delete_post_meta( $attachment_id, self::META_KEY_TEXT );
		delete_post_meta( $attachment_id, self::META_KEY_HASH );
		delete_post_meta( $attachment_id, self::META_KEY_EXTRACTOR );
		delete_post_meta( $attachment_id, self::META_KEY_FAILED_HASH );
	}

	/**
	 * Record that extraction threw a hard failure for this file.
	 *
	 * The current SHA-256 is stored so the failure flag self-invalidates
	 * when the file content changes — replacing a malformed PDF with a
	 * fresh one triggers a retry without manual intervention.
	 *
	 * @param int    $attachment_id ID of the revision attachment post.
	 * @param string $file_path     absolute path to the file that failed.
	 * @return void
	 */
	public static function mark_failed( int $attachment_id, string $file_path ): void {
		if ( $attachment_id <= 0 ) {
			return;
		}

		$current_hash = self::file_hash( $file_path );
		if ( null === $current_hash ) {
			return;
		}

		update_post_meta( $attachment_id, self::META_KEY_FAILED_HASH, $current_hash );
	}

	/**
	 * Whether this attachment's current file is on the extraction-failure
	 * list (and therefore should be skipped by the async retry path).
	 *
	 * Returns false if the file's content has changed since the failure was
	 * recorded — a replaced file is a fresh extraction target.
	 *
	 * @param int    $attachment_id ID of the revision attachment post.
	 * @param string $file_path     absolute path to the current file on disk.
	 * @return bool true when the file failed before AND still has the same content.
	 */
	public static function is_failed( int $attachment_id, string $file_path ): bool {
		if ( $attachment_id <= 0 ) {
			return false;
		}

		$current_hash = self::file_hash( $file_path );
		if ( null === $current_hash ) {
			return false;
		}

		$failed_hash = (string) get_post_meta( $attachment_id, self::META_KEY_FAILED_HASH, true );
		return '' !== $failed_hash && $failed_hash === $current_hash;
	}

	/**
	 * Hash a file with SHA-256, returning null on any I/O failure.
	 *
	 * @param string $file_path absolute path to a readable file.
	 * @return string|null hex-encoded SHA-256, or null on failure.
	 */
	private static function file_hash( string $file_path ): ?string {
		if ( '' === $file_path || ! is_readable( $file_path ) ) {
			return null;
		}
		$hash = hash_file( 'sha256', $file_path );
		return false === $hash ? null : $hash;
	}
}
