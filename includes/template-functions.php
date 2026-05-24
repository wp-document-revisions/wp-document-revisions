<?php
/**
 * Exposes certain templating functions to the global scope
 * Useful for customizing themes and building a front end for WP Document Revisions.
 *
 * @since 1.2
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_documents' ) ) {

	/**
	 * Retrieves all documents matching a query.
	 * Takes standard WP_Query parameters
	 * See in-line documentation in wp-document-revisions.php for more information ( function get_documents() )
	 *
	 * @param array $args WP_Query parameters.
	 * @param bool  $return_attachments whether to return attachment or revisions objects.
	 * @return array array of post objects
	 */
	function get_documents( array $args = array(), bool $return_attachments = false ): array { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

		global $wpdr;
		if ( ! $wpdr ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$wpdr = new WP_Document_Revisions();
		}

		return $wpdr->get_documents( $args, $return_attachments );
	}
}

if ( ! function_exists( 'get_document_revisions' ) ) {

	/**
	 * Retrieves all revisions for a given document, sorted in reverse chronological order.
	 * See in-line documentation in wp-document-revisions.php for more information ( function get_revisions() )
	 *
	 * @param int $document_id the ID of the document.
	 * @return array array of revision-post objects
	 */
	function get_document_revisions( int $document_id ): array { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

		global $wpdr;
		if ( ! $wpdr ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$wpdr = new WP_Document_Revisions();
		}

		return $wpdr->get_revisions( $document_id );
	}
}

if ( ! function_exists( 'wpdr_extract_text' ) ) {

	/**
	 * Extract plain text from a document revision's attached file.
	 *
	 * Looks up the attachment's file path and MIME type, then dispatches to
	 * the first registered text extractor that supports that MIME type.
	 * Returns an empty string if no extractor is registered for the type, the
	 * file is missing, or the extractor declines to produce text.
	 *
	 * Phase 6 of issue #514 wraps the registry dispatch with a SHA-256-keyed
	 * post-meta cache: subsequent calls against the same revision and the
	 * same file return the cached text without re-running the extractor. The
	 * cache invalidates automatically whenever the file content changes.
	 *
	 * @since 5.0.0
	 *
	 * @param int $revision_id the attachment/revision post ID.
	 * @return string extracted text, or empty string.
	 */
	function wpdr_extract_text( int $revision_id ): string {
		if ( $revision_id <= 0 ) {
			return '';
		}

		// Cache lives on the attachment post; bail (without touching the
		// cache) if someone passes a parent document or another post type.
		if ( 'attachment' !== get_post_type( $revision_id ) ) {
			return '';
		}

		// Phase 8: honour the sitewide kill switch and the per-document
		// opt-out flag. A cached value from before the opt-out was set is
		// already cleared on the save handler that flipped the flag; this
		// check stops a fresh extraction from running.
		$parent_id = (int) wp_get_post_parent_id( $revision_id );
		if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $parent_id ) ) {
			return '';
		}

		$file_path = get_attached_file( $revision_id );
		if ( ! is_string( $file_path ) || '' === $file_path || ! is_readable( $file_path ) ) {
			// Unreadable file: do NOT invalidate or overwrite the cache —
			// a transient I/O blip shouldn't blow away previously-extracted
			// text. Return '' for this call only.
			return '';
		}

		$cached = WP_Document_Revisions_Text_Extractor_Cache::get( $revision_id, $file_path );
		if ( null !== $cached ) {
			return $cached;
		}

		$mime_type = get_post_mime_type( $revision_id );
		if ( ! is_string( $mime_type ) || '' === $mime_type ) {
			return '';
		}

		// Bypass the lenient Registry::extract() dispatcher so we can observe
		// extractor throws here and mark the file as failed — otherwise a
		// malformed PDF would be retried on every read.
		$extractor = WP_Document_Revisions_Text_Extractor_Registry::find_for( $mime_type );
		if ( null === $extractor ) {
			return '';
		}

		try {
			$text = $extractor->extract( $file_path, $mime_type );
		} catch ( Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Document Revisions: text extraction failed for ' . $file_path . ': ' . $e->getMessage() );
			WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $revision_id, $file_path );
			return '';
		}

		$identity = WP_Document_Revisions_Text_Extractor_Cache::identity_for( $extractor );
		WP_Document_Revisions_Text_Extractor_Cache::set( $revision_id, $file_path, $text, $identity );
		return $text;
	}
}
