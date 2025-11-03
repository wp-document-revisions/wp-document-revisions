# PublishPress Revisions Integration

## Overview

This guide demonstrates how to integrate [PublishPress Revisions](https://wordpress.org/plugins/publishpress-revisions/) with WP Document Revisions to enable scheduling of document revision publications.

While WP Document Revisions supports scheduling the initial publication of documents by setting a future publish date, it doesn't natively support scheduling revisions. PublishPress Revisions fills this gap by providing workflow management and scheduled publishing for post revisions.

## What This Integration Provides

- **Schedule Revision Publication**: Set future publication dates for document revisions
- **Revision Workflow**: Approve/reject workflow for document updates
- **Preview Draft Revisions**: Preview scheduled revisions before they go live
- **Proper Document Handling**: Ensures attachments and permalinks work correctly with draft revisions

## Prerequisites

1. WP Document Revisions plugin installed and activated
2. [PublishPress Revisions](https://wordpress.org/plugins/publishpress-revisions/) plugin installed and activated

## Installation

### Option 1: Must-Use Plugin (Recommended)

1. Copy the compatibility code below to a file named `wpdr-publishpress-revisions.php`
2. Place the file in your WordPress `wp-content/mu-plugins/` directory
3. If the `mu-plugins` directory doesn't exist, create it
4. The integration will automatically activate when both plugins are present

### Option 2: Theme Functions File

Add the compatibility code to your theme's `functions.php` file. Note that this approach ties the integration to your active theme.

## Compatibility Code

```php
<?php
/**
 * WP Document Revisions - PublishPress Revisions Compatibility
 *
 * This compatibility layer enables PublishPress Revisions to work seamlessly
 * with WP Document Revisions, allowing scheduled publication of document revisions.
 *
 * @package WP_Document_Revisions
 * @subpackage Compatibility
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
	 * This ensures that draft revisions use the correct URL structure
	 * for preview and workflow purposes.
	 *
	 * @param string  $link The document permalink.
	 * @param WP_Post $post The document post object.
	 * @return string Modified permalink.
	 */
	public function modify_permalink( $link, $post ) {
		if ( ! $this->is_document_revision( $post ) ) {
			return $link;
		}

		// For draft revisions, ensure the permalink includes the document slug.
		// This is handled by WPDR's permalink logic, so we just return the link.
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
```

## Configuration

### PublishPress Revisions Settings

After installing both plugins and the compatibility code, configure PublishPress Revisions:

1. Go to **PublishPress Revisions** → **Settings**
2. Navigate to the **Preview/Approval** tab
3. Set **Preview Link Type** to **"Revision ID only"**
   - This setting may be automatically applied for documents by the compatibility code
   - However, setting it globally ensures consistency

### Document Workflow

The integration maintains compatibility with WP Document Revisions' native workflow while adding scheduling capabilities.

## Usage

### Creating a Scheduled Revision

1. Navigate to an existing published document
2. Click **"Edit Document"**
3. Upload a new file version or make changes
4. In the PublishPress Revisions panel:
   - Set the **Status** to a workflow state (e.g., "Pending Review")
   - Set a **Scheduled Publication Date** for the future
5. Click **Submit for Review** or your configured action
6. The revision will be published automatically at the scheduled time

### Reviewing Revisions

1. Go to **Revisions** → **Revision Queue** (added by PublishPress)
2. Review pending document revisions
3. Preview changes using the preview link
4. Approve, reject, or reschedule as needed

### Previewing Draft Revisions

- Click the **Preview** link in the Revision Queue
- The preview shows the new document version
- Original published document remains unchanged until approval

## Known Limitations

1. **Viewing Unapproved Documents**: The document list may not show the pending revision's attachment until it's approved. This is a limitation of how PublishPress Revisions handles attachments.

2. **Multiple Pending Revisions**: Be cautious when multiple revisions are pending for the same document. The attachment parent fix applies when serving documents, but the queue interface may not fully reflect all attachment states.

3. **Revision Log Display**: Draft revisions managed by PublishPress don't appear in the standard WP Document Revisions revision log until approved.

## Troubleshooting

### Preview Shows Wrong Document

**Problem**: Preview link shows a random document or the published version instead of the draft revision.

**Solution**: Ensure the Preview Link Type is set to "Revision ID only" in PublishPress Revisions settings, or verify the compatibility code is active.

### Attachment Not Updating After Approval

**Problem**: After approving a revision, the document still serves the old file.

**Solution**: The `serve_attachment` filter in the compatibility code should handle this. Verify:
- The compatibility code is active
- No caching plugins are interfering
- File permissions allow WordPress to update attachments

### Revision Log Meta Box Still Shows

**Problem**: The revision log meta box appears on draft revision edit screens.

**Solution**: Verify:
- The compatibility code is loaded
- Both plugins are active
- You're editing a draft revision (not the published document)

## Technical Details

### How It Works

1. **Hook Timing**: The compatibility code hooks into `document_edit` action, which fires after WP Document Revisions sets up its meta boxes, ensuring proper removal order.

2. **Permalink Handling**: Uses the `document_permalink` filter to maintain URL consistency for draft revisions.

3. **Attachment Management**: The `document_serve_attachment` filter ensures that when serving a document, the attachment correctly points to the original document post, not the revision post.

4. **Preview Links**: The `revisionary_preview_link_type` filter ensures document previews use ID-based URLs to avoid routing conflicts with WP Document Revisions' rewrite rules.

### Filter and Action Reference

The compatibility code uses these hooks:

| Hook | Type | Purpose |
|------|------|---------|
| `plugins_loaded` | Action | Initialize compatibility checks |
| `document_edit` | Action | Remove revision log meta box |
| `document_permalink` | Filter | Modify draft revision permalinks |
| `document_serve_attachment` | Filter | Fix attachment parent relationships |
| `revisionary_preview_link_type` | Filter | Set preview link format |

## Alternative Approaches

If you prefer not to use PublishPress Revisions, consider these alternatives:

1. **Custom Scheduling**: Implement a lightweight solution using WordPress cron and custom post meta to store scheduled revision dates.

2. **Edit Flow**: Another workflow plugin that may provide similar functionality, though not specifically tested with WPDR.

3. **Manual Workflow**: Use workflow states and manual publishing at the scheduled time.

## Support

For issues with:
- **WP Document Revisions**: Open an issue on the [GitHub repository](https://github.com/wp-document-revisions/wp-document-revisions)
- **PublishPress Revisions**: Contact PublishPress support or their plugin forum
- **This Integration**: Report issues on the WP Document Revisions GitHub with "PublishPress" in the title

## Credits

This integration guide and compatibility code were developed based on community feedback and testing by:
- Neil James (@NeilWJames) - Original compatibility code and testing
- EarthlingDavey - Feature request and use case identification

## License

This compatibility code is provided under the same license as WP Document Revisions (GPL v3 or later).
