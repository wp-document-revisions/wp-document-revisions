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
 * @since 5.0.0
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
	 * Default largest file, in bytes, handed to an extractor (20 MB).
	 *
	 * Filterable via `wpdr_text_extraction_max_file_size`.
	 *
	 * @var int
	 */
	const DEFAULT_MAX_FILE_SIZE = 20971520;

	/**
	 * Default largest total uncompressed size, in bytes, of a DOCX or ODT
	 * archive handed to an extractor (100 MB).
	 *
	 * Filterable via `wpdr_text_extraction_max_uncompressed_size`.
	 *
	 * @var int
	 */
	const DEFAULT_MAX_UNCOMPRESSED_SIZE = 104857600;

	/**
	 * Zip-based MIME types whose archive contents are checked before extraction.
	 *
	 * @var string[]
	 */
	private const ZIP_MIME_TYPES = array(
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.oasis.opendocument.text',
	);

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
		 * Entries that are not instances of
		 * WP_Document_Revisions_Text_Extractor are silently skipped by
		 * the dispatcher.
		 *
		 * @since 5.0.0
		 *
		 * @param array $extractors Registered extractors. Each entry should
		 *                          implement WP_Document_Revisions_Text_Extractor;
		 *                          other values are ignored.
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
			self::check_limits( $file_path, $mime_type );
			return $extractor->extract( $file_path, $mime_type );
		} catch ( WP_Document_Revisions_Text_Extraction_Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Document Revisions: text extraction failed for ' . $file_path . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Refuse files too large to extract safely.
	 *
	 * Called before every extractor run so a huge upload, or a DOCX/ODT that
	 * inflates to far more than its size on disk, can't tie up a request or
	 * cron worker. Callers treat the exception like any other extraction
	 * failure.
	 *
	 * @since 5.7.0
	 *
	 * @param string $file_path absolute path to the file on disk.
	 * @param string $mime_type the MIME type of the file.
	 * @return void
	 * @throws WP_Document_Revisions_Text_Extraction_Exception When the file exceeds a limit.
	 */
	public static function check_limits( string $file_path, string $mime_type ): void {
		/**
		 * Filters the largest file, in bytes, that text extraction will read.
		 *
		 * Larger files are skipped and treated as an extraction failure.
		 *
		 * @since 5.7.0
		 *
		 * @param int    $max_size  Maximum file size in bytes. Zero disables the check.
		 * @param string $file_path Path of the file about to be extracted.
		 * @param string $mime_type MIME type of the file.
		 */
		$max_size = (int) apply_filters( 'wpdr_text_extraction_max_file_size', self::DEFAULT_MAX_FILE_SIZE, $file_path, $mime_type );
		if ( $max_size > 0 ) {
			$size = @filesize( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $size && $size > $max_size ) {
				throw new WP_Document_Revisions_Text_Extraction_Exception(
					esc_html( sprintf( 'file is %d bytes, over the %d byte extraction limit', (int) $size, $max_size ) )
				);
			}
		}

		if ( in_array( $mime_type, self::ZIP_MIME_TYPES, true ) ) {
			self::check_archive_limits( $file_path, $mime_type );
		}
	}

	/**
	 * Refuse a DOCX/ODT archive whose contents inflate past the uncompressed limit.
	 *
	 * Sums the uncompressed sizes recorded in the archive's central directory,
	 * which is cheap to read. If the file can't be opened as a zip, the check is
	 * skipped and the extractor handles the file as it would have before.
	 *
	 * @since 5.7.0
	 *
	 * @param string $file_path absolute path to the file on disk.
	 * @param string $mime_type the MIME type of the file.
	 * @return void
	 * @throws WP_Document_Revisions_Text_Extraction_Exception When the archive exceeds the limit.
	 */
	private static function check_archive_limits( string $file_path, string $mime_type ): void {
		/**
		 * Filters the largest total uncompressed size, in bytes, of a DOCX or ODT
		 * file that text extraction will read.
		 *
		 * Larger archives (including zip bombs) are skipped and treated as an
		 * extraction failure.
		 *
		 * @since 5.7.0
		 *
		 * @param int    $max_size  Maximum uncompressed size in bytes. Zero disables the check.
		 * @param string $file_path Path of the file about to be extracted.
		 * @param string $mime_type MIME type of the file.
		 */
		$max_size = (int) apply_filters( 'wpdr_text_extraction_max_uncompressed_size', self::DEFAULT_MAX_UNCOMPRESSED_SIZE, $file_path, $mime_type );
		if ( $max_size <= 0 || ! class_exists( 'ZipArchive' ) ) {
			return;
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $file_path ) ) {
			return;
		}

		$total = 0;
		for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$stat = $zip->statIndex( $i );
			if ( is_array( $stat ) ) {
				$total += (int) $stat['size'];
			}
			if ( $total > $max_size ) {
				break;
			}
		}
		$zip->close();

		if ( $total > $max_size ) {
			throw new WP_Document_Revisions_Text_Extraction_Exception(
				esc_html( sprintf( 'archive inflates to more than the %d byte extraction limit', $max_size ) )
			);
		}
	}
}
