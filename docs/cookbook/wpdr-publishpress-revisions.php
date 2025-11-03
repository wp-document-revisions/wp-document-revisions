<?php
/**
 * Plugin Name: WP Document Revisions - PublishPress Revisions Compatibility
 * Description: Enables PublishPress Revisions to work seamlessly with WP Document Revisions for scheduled revision publishing.
 * Version: 1.0.0
 * Author: WP Document Revisions Contributors
 * Author URI: https://github.com/wp-document-revisions/wp-document-revisions
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-document-revisions
 *
 * @package WP_Document_Revisions
 * @subpackage Compatibility
 */

/**
 * WP Document Revisions - PublishPress Revisions Compatibility
 *
 * This compatibility layer enables PublishPress Revisions to work seamlessly
 * with WP Document Revisions, allowing scheduled publication of document revisions.
 *
 * INSTALLATION:
 * 1. Copy this file to your wp-content/mu-plugins/ directory
 * 2. Both WP Document Revisions and PublishPress Revisions must be installed and active
 * 3. Configure PublishPress Revisions settings as documented
 *
 * For full documentation, see:
 * https://github.com/wp-document-revisions/wp-document-revisions/blob/master/docs/cookbook/publishpress-revisions-integration.md
 */

/**
 * Class to handle compatibility between WP Document Revisions and PublishPress Revisions.
 */
class WP_Document_Revisions_PublishPress_Compatibility {

	/**
	 * Initialize the compatibility layer.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'add_hooks' ) );
	}

	/**
	 * Add WordPress hooks when both plugins are active.
	 */
	public function add_hooks() {
		// Only proceed if both WPDR and PublishPress Revisions are active.
		if ( ! $this->is_wpdr_active() || ! $this->is_publishpress_revisions_active() ) {
			return;
		}

		// Remove the revision log meta box for draft revisions.
		add_action( 'document_edit', array( $this, 'remove_revision_log_metabox' ) );

		// Modify the permalink for draft document revisions.
		add_filter( 'document_permalink', array( $this, 'modify_permalink' ), 10, 2 );

		// Fix attachment parent relationship when revisions are approved.
		add_filter( 'document_serve_attachment', array( $this, 'serve_attachment' ), 10, 2 );

		// Ensure preview links use the correct format for documents.
		add_filter( 'revisionary_preview_link_type', array( $this, 'preview_link_type' ), 10, 2 );
	}

	/**
	 * Check if WP Document Revisions is active.
	 *
	 * @return bool True if WPDR is active.
	 */
	private function is_wpdr_active() {
		return class_exists( 'WP_Document_Revisions' );
	}

	/**
	 * Check if PublishPress Revisions is active.
	 *
	 * @return bool True if PublishPress Revisions is active.
	 */
	private function is_publishpress_revisions_active() {
		// Check for the main PublishPress Revisions class.
		return defined( 'PUBLISHPRESS_REVISIONS_VERSION' ) || class_exists( 'PublishPress_Revisions' );
	}

	/**
	 * Check if a post is a document.
	 *
	 * @param WP_Post|int|null $post Post object or ID.
	 * @return bool True if the post is a document.
	 */
	private function is_document( $post = null ) {
		global $wpdr;

		if ( ! $wpdr ) {
			return false;
		}

		return $wpdr->verify_post_type( $post );
	}

	/**
	 * Check if a post is a document revision (draft state).
	 *
	 * @param WP_Post|int|null $post Post object or ID.
	 * @return bool True if the post is a document revision.
	 */
	private function is_document_revision( $post = null ) {
		if ( ! $this->is_document( $post ) ) {
			return false;
		}

		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}

		return 'draft' === $post->post_status;
	}

	/**
	 * Remove the revision log meta box from document edit screens for draft revisions.
	 *
	 * Draft revisions managed by PublishPress don't need the standard revision log
	 * since they're handled through the PublishPress workflow.
	 */
	public function remove_revision_log_metabox() {
		global $post;

		if ( ! $this->is_document_revision( $post ) ) {
			return;
		}

		remove_meta_box( 'revision-summary', 'document', 'normal' );
	}

	/**
	 * Modify the permalink for draft document revisions.
	 *
	 * This filter hook ensures the permalink is processed through WPDR's logic
	 * in the correct order when working with PublishPress Revisions. While this
	 * method doesn't modify the link directly (WPDR handles the actual permalink
	 * generation), hooking into this filter ensures compatibility with both plugins'
	 * permalink handling.
	 *
	 * Note: This hook can be extended in the future if custom permalink modifications
	 * are needed for draft revisions.
	 *
	 * @param string  $link The document permalink.
	 * @param WP_Post $post The document post object.
	 * @return string The permalink (unmodified by this method).
	 */
	public function modify_permalink( $link, $post ) {
		if ( ! $this->is_document_revision( $post ) ) {
			return $link;
		}

		// WPDR's permalink logic handles the URL structure for draft revisions.
		// This hook ensures compatibility with PublishPress Revisions workflow.
		return $link;
	}

	/**
	 * Fix attachment parent when serving documents from approved revisions.
	 *
	 * When PublishPress approves a revision, the attachment initially has the
	 * revision post as its parent rather than the original document. This filter
	 * corrects the parent relationship.
	 *
	 * @param WP_Post $attach The attachment post object.
	 * @param int     $rev_id The revision ID (unused but required by filter signature).
	 * @return WP_Post The attachment with corrected parent.
	 */
	public function serve_attachment( $attach, $rev_id ) {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $attach || ! is_object( $attach ) ) {
			return $attach;
		}

		// Validate attachment parent ID before querying.
		if ( ! is_numeric( $attach->post_parent ) || $attach->post_parent <= 0 ) {
			return $attach;
		}

		// Check if the attachment's parent is a revision (has a parent itself).
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$parent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d",
				$attach->post_parent
			)
		);

		// If the attachment's parent has a parent (meaning it's a revision),
		// update the attachment to point to the original document.
		if ( $parent && (int) $parent > 0 ) {
			$result = wp_update_post(
				array(
					'ID'          => $attach->ID,
					'post_parent' => $parent,
				)
			);

			// Log error if update fails, but still return the attachment.
			if ( is_wp_error( $result ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'WPDR PublishPress Compatibility: Failed to update attachment parent - ' . $result->get_error_message() );
				return $attach;
			}

			$attach->post_parent = $parent;
		}

		return $attach;
	}

	/**
	 * Ensure the preview link type for draft document revisions is ID-only.
	 *
	 * PublishPress Revisions supports different preview link formats. For documents,
	 * the ID-only format works best to avoid routing conflicts.
	 *
	 * @param string  $preview_link The preview link type.
	 * @param WP_Post $post         Post object.
	 * @return string The preview link type.
	 */
	public function preview_link_type( $preview_link, $post ) {
		if ( ! $this->is_document_revision( $post ) ) {
			return $preview_link;
		}

		return 'id_only';
	}
}

// Initialize the compatibility layer.
new WP_Document_Revisions_PublishPress_Compatibility();
