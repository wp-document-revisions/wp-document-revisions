# WP-Documents-Revisions Filters

This plugin makes use of many filters to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones shown at the bottom.

## Filter document_block_taxonomies

In: class-wp-document-revisions-front-end.php

Filters the Document taxonomies (allowing users to select the first three for the block widget.

## Filter document_buffer_size

In: class-wp-document-revisions.php

Filter to define file writing buffer size (Default 0 = No buffering).

## Filter document_caps

In: class-wp-document-revisions.php

Filters the default capabilities provided by the plugin.
Note that by default all custom roles will have the default Subscriber access.

## Filter document_content_disposition_inline

In: class-wp-document-revisions.php

Sets the content disposition header to open the document (inline) or to save it (attachment).
Ordinarily set as inline but can be changed.

## Filter document_custom_feed

In: class-wp-document-revisions.php

Sets to false to use the standard RSS feed.

## Filter document_extension

In: class-wp-document-revisions.php

Allows the document file extension to be manipulated.

## Filter document_help_array

In: class-wp-document-revisions-admin.php

Filters the default help text for current screen.

## Filter document_home_url

In: class-wp-document-revisions.php

Filters the home_url() for WPML and translated documents.

## Filter document_internal_filename

In: class-wp-document-revisions.php

Filters the encoded file name for the attached document (on save).

## Filter document_lock_check

In: class-wp-document-revisions.php

Filters the user locking the document file.

## Filter document_lock_override_email

In: class-wp-document-revisions.php

Filters the lost lock document email text.

## Filter document_output_sent_is_ok

In: class-wp-document-revisions.php

Filter to serve file even if output already written.

## Filter document_path

In: class-wp-document-revisions.php

Filters the file name for WAMP settings (filter routine provided by plugin).

## Filter document_permalink

In: class-wp-document-revisions.php

Filters the Document permalink.

## Filter document_post_thumbnail

In: class-wp-document-revisions.php

Filters the post-thumbnail size parameters (used only if this image size has not been set).

## Filter document_read_uses_read

In: class-wp-document-revisions.php

Filters the users capacities to require read (or read_document) capability.

## Filter document_revision_query

In: class-wp-document-revisions.php

Filters the plugin query to fetch all the attachments of a parent post.

## Filter document_revisions_cpt

In: class-wp-document-revisions.php

Filters the delivered document type definition prior to registering it.

## Filter document_revisions_ct

In: class-wp-document-revisions.php

Filters the default structure and label values of the workflow_state taxonomy on declaration.

## Filter document_revisions_limit

In: class-wp-document-revisions-admin.php

Filters the number of revisions to keep for documents.

## Filter document_revisions_merge_revisions

In: class-wp-document-revisions-admin.php

Filters whether to merge two revisions for a change in excerpt (generally where taxonomy change made late).

## Filter document_revisions_mimetype

In: class-wp-document-revisions.php

Filters the MIME type for a file before it is processed by WP Document Revisions.

## Filter document_revisions_serve_file_headers

In: class-wp-document-revisions.php

Filters the HTTP headers sent when a file is served through WP Document Revisions.

## Filter document_revisions_use_edit_flow

In: class-wp-document-revisions.php

Filter to switch off integration with Edit_Flow/PublishPress statuses.

## Filter document_rewrite_rules

In: class-wp-document-revisions.php

Filters the Document rewrite rules.

## Filter document_serve

In: class-wp-document-revisions.php

Filters file name of document served. (Useful if file is encrypted at rest).

## Filter document_serve_attachment

In: class-wp-document-revisions.php

Filter the attachment post to serve (Return false to stop display).

## Filter document_serve_use_gzip

In: class-wp-document-revisions.php

Filter to determine if gzip should be used to serve file (subject to browser negotiation).

## Filter document_shortcode_atts

In: class-wp-document-revisions-front-end.php

Filters the Document shortcode attributes.

## Filter document_shortcode_show_edit

In: class-wp-document-revisions-front-end.php

Filters the controlling option to display an edit option against each document.

## Filter document_show_in_rest

In: class-wp-document-revisions.php

Filters the show_in_rest parameter from its default value of fa1se.

## Filter document_slug

In: class-wp-document-revisions.php

Filters the document slug.

## Filter document_stop_file_access_pattern

In: class-wp-document-revisions.php

Filter to stop direct file access to documents (specify the URL element (or trailing part) to traverse to the document directory.

## Filter document_taxonomy_term_count

In: class-wp-document-revisions.php

Filter to select which taxonomies with default term count to be modified to count all non-trashed posts.

## Filter document_title

In: class-wp-document-revisions.php

Filter the document title from the post.

## Filter document_to_private

In: class-wp-document-revisions-admin.php

Filters setting the new document status to private.

## Filter document_use_workflow_states

In: class-wp-document-revisions.php

Filter to switch off use of standard Workflow States taxonomy. For internal use.

## Filter document_use_wp_filesystem

In: class-wp-document-revisions.php

Filter whether WP_FileSystem used to serve document (or PHP readfile). Irrelevant if file compressed on output.

## Filter document_validate_md5

In: class-wp-document-revisions-validate-structure.php

Filter to switch off md5 format attachment validation.

## Filter document_verify_feed_key

In: class-wp-document-revisions.php

Allows the RSS feed to be switched off.

## Filter default_workflow_states

In: class-wp-document-revisions.php

Filters the default workflow state values.

## Filter lock_override_notice_subject

In: class-wp-document-revisions.php

Filters the locked document email subject text.

## Filter lock_override_notice_message

In: class-wp-document-revisions.php

Filters the locked document email message text.

## Filter send_document_override_notice

In: class-wp-document-revisions.php

Filters the option to send a locked document override email

## Filter serve_document_auth

In: class-wp-document-revisions.php

Filters the decision to serve the document through WP Document Revisions.
