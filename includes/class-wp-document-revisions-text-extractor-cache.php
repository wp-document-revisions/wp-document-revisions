<?php
/**
 * SHA-256-keyed cache for extracted text.
 *
 * Stored as post meta on the revision attachment:
 *   _wpdr_extracted_text       — the extracted plain text (may be empty)
 *   _wpdr_extracted_text_hash  — SHA-256 of the file at the time of extraction
 *   _wpdr_extraction_failed    — SHA-256 of a file whose extraction threw
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
 * @since 4.1.0
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
	 * caching failure cannot break the calling extraction path.
	 *
	 * @param int    $attachment_id ID of the revision attachment post.
	 * @param string $file_path     absolute path to the file the text came from.
	 * @param string $text          extracted text to cache (may be empty).
	 * @return void
	 */
	public static function set( int $attachment_id, string $file_path, string $text ): void {
		if ( $attachment_id <= 0 ) {
			return;
		}

		$current_hash = self::file_hash( $file_path );
		if ( null === $current_hash ) {
			return;
		}

		update_post_meta( $attachment_id, self::META_KEY_TEXT, $text );
		update_post_meta( $attachment_id, self::META_KEY_HASH, $current_hash );
		// A successful extraction supersedes any previously-recorded failure
		// for this file, so the async scheduler is free to run again if the
		// file is later replaced.
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
