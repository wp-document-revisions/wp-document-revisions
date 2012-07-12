<?php
/**
 * Exposes certain templating functions to the global scope
 * Useful for customizing themes and building a front end for WP Document Revisions
 * @since 1.2
 */

if ( !function_exists( 'get_documents' ) ) {

	/**
	 * Retreives all documents matching a query
	 * Takes standard WP_Query parameters
	 * See in-line documentation in wp-document-revisions.php for more information ( function get_documents() )
	 * @param array $args WP_Query parameters
	 * @param bool $return_attachements whether to return attachment or revisions objects
	 * @return array array of post objects
	 */
	function get_documents( $args = array(), $return_attachments = false ) {

		global $wpdr;
		if ( !$wpdr )
			$wpdr = Document_Revisions::$instance;

		return $wpdr->get_documents( $args, $return_attachments );
	}


}

if ( !function_exists( 'get_document_revisions' ) ) {

	/**
	 * Retrievs all revisions for a given document, sorted in reverse chronological order
	 * See in-line documentation in wp-document-revisions.php for more information ( function get_revisions() )
	 * @param int $documentID the ID of the document
	 * @return array array of revision-post objects
	 */
	function get_document_revisions( $documentID ) {

		global $wpdr;
		if ( !$wpdr )
			$wpdr = Document_Revisions::$instance;

		return $wpdr->get_revisions( $documentID );
	}


}