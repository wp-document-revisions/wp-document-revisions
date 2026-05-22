<?php
/**
 * SHA-256-keyed cache for extracted text.
 *
 * Stored as post meta on the revision attachment:
 *   _wpdr_extracted_text       — the extracted plain text (may be empty)
 *   _wpdr_extracted_text_hash  — SHA-256 of the file at the time of extraction
 *
 * `get()` returns a `?string`: `null` for a cache miss (no meta, or the
 * file's current hash does not match the stored one), or the cached string
 * (possibly empty) on hit. This distinction lets callers tell the difference
 * between "not extracted yet" and "extracted to an empty string" (e.g., a
 * scanned PDF or a password-protected file) without re-running the extractor.
 *
 * Phase 6 caches every result, including `''`. Phase 7 will layer a
 * `_wpdr_extraction_failed` flag on top to distinguish empty-by-design from
 * extractor-failed — until then, this cache treats all returns from the
 * registry as cacheable and invalidates them only when the file content
 * changes.
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
