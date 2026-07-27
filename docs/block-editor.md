# Block Editor (Gutenberg) Support

> **ℹ️ Opt-in feature:** Block editor support and the REST API for documents are supported but opt-in. The classic editor remains the default because it provides a streamlined, purpose-built upload interface. Enable the block editor when you prefer the modern WordPress editing experience for documents. Found a rough edge? Please [report it](https://github.com/wp-document-revisions/wp-document-revisions/issues).

WP Document Revisions supports the WordPress block editor (Gutenberg) for documents. By default, documents use the classic editor, which provides a streamlined, purpose-built upload interface. Enable the block editor when you want the newer WordPress editing experience for documents — the upload flow is exercised by the plugin's end-to-end test suite.

## How to Opt In

Add the following two filters to your theme's `functions.php` or a custom plugin:

```php
// Enable REST API for documents (required for block editor).
add_filter( 'document_show_in_rest', '__return_true' );

// Enable the block editor for documents.
add_filter( 'document_use_block_editor', '__return_true' );
```

Alternatively, create a [must-use plugin](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/) at `wp-content/mu-plugins/enable-block-editor.php`:

```php
<?php
// Enable the block editor for documents.
add_filter( 'document_show_in_rest', '__return_true' );
add_filter( 'document_use_block_editor', '__return_true' );
```

To disable the block editor, simply remove these filters.

Both filters are required. `document_show_in_rest` exposes documents to the WordPress REST API, and `document_use_block_editor` configures the plugin for block editor compatibility (enables Gutenberg for documents, allows REST write methods, adds excerpt support, and registers post meta). Enabling `document_show_in_rest` alone provides read-only REST API access without the block editor.

## Document Sidebar Panel

When the block editor is enabled, a **Document** panel appears in the Settings sidebar with the following features:

### File Upload

- **Upload Document** / **Upload New Version** button opens the WordPress media library
- Select any file from the media library or upload a new one
- A success notice confirms your selection: *"Document selected. Press Update to save."*
- The file is stored as document metadata and synced to `post_content` on save

### File Information

- **File type badge** displays the file extension (e.g., PDF, DOCX, XLSX) as a visual label
- **Filename** shown alongside the badge
- **Download link** to access the current file directly

### Revision Summary

A textarea field for describing what changed in this version. This maps to the post excerpt and appears in the document's revision history, just like the classic editor's "Revision Summary" metabox.

### Lock Status

When another user is editing the document, a warning banner shows their name and the upload button is disabled. This uses the WordPress core editor lock (not the plugin's custom document lock with `document_lock_check` filter).

### Save Protection

New documents cannot be published until a file is attached. The editor's Publish/Update button is locked until you select a file.

### Error Handling

- If a previously attached file is deleted from the media library, an error notice alerts you
- Failed media selections display an error notice

## How It Differs from the Classic Editor

| Feature | Classic Editor | Block Editor |
|---------|---------------|-------------|
| Upload method | ThickBox iframe with plupload | WordPress media library modal |
| File storage | Direct `post_content` write | REST API meta sync |
| Feedback | Inline HTML notices | WordPress Snackbar notices |
| Lock system | Plugin's `document_lock_check` filter | Core editor lock |
| Revision summary | Custom metabox | Sidebar textarea (post excerpt) |
| Workflow states | Taxonomy metabox | Taxonomy panel in sidebar |

## Technical Details

### REST API

When block editor support is enabled, documents are exposed via the WordPress REST API at `/wp-json/wp/v2/{document_slug}/`. The plugin registers a `document_attachment_id` post meta field for REST read/write, and automatically syncs it to `post_content` (where the attachment reference is stored as `<!-- WPDR {ID} -->`).

POST, PUT, and DELETE methods are allowed when the block editor is enabled. All write methods require a valid nonce.

### Blocks

WP Document Revisions also provides three Gutenberg blocks for displaying documents on the front end, available regardless of whether the document post type uses the block editor:

- **Documents List** (`wp-document-revisions/documents-shortcode`) — Displays a list of documents, equivalent to the `[documents]` shortcode
- **Recently Revised Documents** (`wp-document-revisions/documents-widget`) — Shows recently updated documents, equivalent to the sidebar widget
- **Document Revisions** (`wp-document-revisions/revisions-shortcode`) — Shows the revision history for a specific document, equivalent to the `[document_revisions]` shortcode

## Known Limitations

- **Document locking** — Uses WordPress core's lock mechanism, which differs from the plugin's custom `document_lock_check` filter used in the classic editor
- **REST API exposure** — Requires the REST API to be enabled for documents, which exposes document endpoints to authenticated users with appropriate capabilities
- **Content area hidden** — The main editor canvas is hidden since documents don't use post body content; all management happens via the sidebar panels
- **Revision restore** — The Revision Log panel displays history but does not yet support restoring previous revisions (use the classic editor for that)
