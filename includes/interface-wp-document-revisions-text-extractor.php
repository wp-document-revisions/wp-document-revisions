<?php
/**
 * Text extractor interface.
 *
 * Implementations turn the bytes of an uploaded document file into plain text
 * for downstream features (search, AI-generated revision summaries, exports).
 *
 * Register an implementation via the `wpdr_text_extractors` filter:
 *
 *     add_filter( 'wpdr_text_extractors', function ( array $extractors ): array {
 *         $extractors[] = new My_Custom_Extractor();
 *         return $extractors;
 *     } );
 *
 * The first registered extractor whose `supports()` returns true for the file's
 * MIME type wins. To override a built-in extractor for a given MIME type,
 * prepend with `array_unshift()` instead of appending.
 *
 * @since 5.0.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pluggable text extractor.
 */
interface WP_Document_Revisions_Text_Extractor {

	/**
	 * Whether this extractor can handle the given MIME type.
	 *
	 * @param string $mime_type the MIME type of the file (e.g. "application/pdf").
	 * @return bool true if this extractor can extract text from files of this type.
	 */
	public function supports( string $mime_type ): bool;

	/**
	 * Extract plain text from a file on disk.
	 *
	 * Implementations should be defensive: an unreadable, malformed, or
	 * unsupported file should return an empty string rather than throwing,
	 * unless something has gone genuinely wrong (out of memory, dependency
	 * missing, etc.) — in which case throw a
	 * WP_Document_Revisions_Text_Extraction_Exception so the dispatcher can
	 * surface a useful error to the logs.
	 *
	 * @param string $file_path absolute path to the file on disk.
	 * @param string $mime_type the MIME type of the file.
	 * @return string extracted text, or empty string if nothing could be extracted.
	 * @throws WP_Document_Revisions_Text_Extraction_Exception On hard failure.
	 */
	public function extract( string $file_path, string $mime_type ): string;
}
