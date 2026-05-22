<?php
/**
 * Hard-failure exception for text extractors.
 *
 * Implementations of WP_Document_Revisions_Text_Extractor throw this when the
 * file cannot be processed for reasons beyond "this file has no text" (missing
 * binary, corrupted dependency, out-of-memory, etc.). The dispatcher catches
 * these and returns an empty string so a single bad file does not break the
 * caller, but the exception message reaches the error log first.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thrown by a WP_Document_Revisions_Text_Extractor on hard failure.
 */
class WP_Document_Revisions_Text_Extraction_Exception extends RuntimeException {
}
