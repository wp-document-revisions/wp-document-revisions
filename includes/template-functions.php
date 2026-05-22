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
	 * Phase 1 of issue #514: pure dispatch, no caching. Caching and async
	 * scheduling land in later phases.
	 *
	 * @since 4.1.0
	 *
	 * @param int $revision_id the attachment/revision post ID.
	 * @return string extracted text, or empty string.
	 */
	function wpdr_extract_text( int $revision_id ): string {
		if ( $revision_id <= 0 ) {
			return '';
		}

		$file_path = get_attached_file( $revision_id );
		if ( ! is_string( $file_path ) || '' === $file_path ) {
			return '';
		}

		$mime_type = get_post_mime_type( $revision_id );
		if ( ! is_string( $mime_type ) || '' === $mime_type ) {
			return '';
		}

		return WP_Document_Revisions_Text_Extractor_Registry::extract( $file_path, $mime_type );
	}
}
