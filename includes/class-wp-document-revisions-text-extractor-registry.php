<?php
/**
 * Registry/dispatcher for text extractors.
 *
 * Resolves a MIME type to the first registered extractor that claims to
 * support it. Extractors are registered via the `wpdr_text_extractors` filter
 * and walked in array order — first match wins. To override a built-in
 * extractor for a MIME type, prepend your extractor with `array_unshift()`
 * (or run your filter callback at a higher priority that does the same);
 * a plain `$extractors[] =` append will lose to anything already registered
 * for that type.
 *
 * Phase 1 of issue #514: dispatcher only. No caching, no async, no built-in
 * extractors yet — those land in later phases.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves MIME types to registered extractors and dispatches extraction.
 */
class WP_Document_Revisions_Text_Extractor_Registry {

	/**
	 * Return all registered extractors, in filter order.
	 *
	 * Non-extractor entries are filtered out defensively so a misbehaving
	 * third-party filter cannot crash the dispatcher.
	 *
	 * @return WP_Document_Revisions_Text_Extractor[] registered extractors.
	 */
	public static function get_extractors(): array {
		/**
		 * Filter the list of registered text extractors.
		 *
		 * Extractors are tried in array order; the first one whose
		 * supports() returns true for a file's MIME type is used.
		 * Prepend (`array_unshift()`) to override a built-in for the
		 * same MIME type — a plain append loses to anything already
		 * registered.
		 *
		 * @since 4.1.0
		 *
		 * @param WP_Document_Revisions_Text_Extractor[] $extractors registered extractors.
		 */
		$extractors = apply_filters( 'wpdr_text_extractors', array() );

		if ( ! is_array( $extractors ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$extractors,
				static function ( $extractor ): bool {
					return $extractor instanceof WP_Document_Revisions_Text_Extractor;
				}
			)
		);
	}

	/**
	 * Find the first registered extractor that supports the given MIME type.
	 *
	 * @param string $mime_type the MIME type to look up.
	 * @return WP_Document_Revisions_Text_Extractor|null matching extractor, or null if none.
	 */
	public static function find_for( string $mime_type ): ?WP_Document_Revisions_Text_Extractor {
		if ( '' === $mime_type ) {
			return null;
		}

		foreach ( self::get_extractors() as $extractor ) {
			if ( $extractor->supports( $mime_type ) ) {
				return $extractor;
			}
		}

		return null;
	}

	/**
	 * Extract text from a file by dispatching to a registered extractor.
	 *
	 * Returns an empty string if no extractor claims the MIME type, the file
	 * is unreadable, or the chosen extractor throws a hard failure (the
	 * exception is logged but not propagated to the caller).
	 *
	 * @param string $file_path absolute path to the file on disk.
	 * @param string $mime_type the MIME type of the file.
	 * @return string extracted text, or empty string.
	 */
	public static function extract( string $file_path, string $mime_type ): string {
		if ( '' === $file_path || ! is_readable( $file_path ) ) {
			return '';
		}

		$extractor = self::find_for( $mime_type );
		if ( null === $extractor ) {
			return '';
		}

		try {
			return $extractor->extract( $file_path, $mime_type );
		} catch ( WP_Document_Revisions_Text_Extraction_Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Document Revisions: text extraction failed for ' . $file_path . ': ' . $e->getMessage() );
			return '';
		}
	}
}
