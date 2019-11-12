# WP-Documents-Revisions Filters

This plugin makes use of many filters to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones shown at the bottom.

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

## Filter document_help

In: class-wp-document-revisions-admin.php

Filters the (UNDEFINED) help text for current screen.

## Filter document_help_array

In: class-wp-document-revisions-admin.php

Filters the default help text for current screen.

## Filter document_internal_filename

In: class-wp-document-revisions.php

Filters the encoded file name for the attached document.

## Filter document_lock_check

In: class-wp-document-revisions.php

Filters the user locking the document file.

## Filter document_lock_override_email

In: class-wp-document-revisions.php

Filters the lost lock document email text.

## Filter document_path

In: class-wp-document-revisions.php

Filters the file name for WAMP settings (filter routine provided by plugin).

## Filter document_permalink

In: class-wp-document-revisions.php

Filters the Document permalink.

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

## Filter document_revisions_mimetype

In: class-wp-document-revisions.php

Filters the MIME type for a file before it is processed by WP Document Revisions.

## Filter document_revisions_owners

In: class-wp-document-revisions-admin.php

Filters the author metabox query for document owners.

## Filter document_revisions_serve_file_headers

In: class-wp-document-revisions.php

Filters the HTTP headers sent when a file is served through WP Document Revisions.

## Filter document_revisions_use_edit_flow

In: class-wp-document-revisions.php

Filter to switch off use of Edit_Flow capabilities.

## Filter document_rewrite_rules

In: class-wp-document-revisions.php

Filters the Document rewrite rules.

## Filter document_shortcode_atts

In: class-wp-document-revisions-front-end.php

Filters the Document shortcode attributes.

## Filter document_shortcode_show_edit

In: class-wp-document-revisions-front-end.php

Filters the controlling option to display an edit option against each document.

## Filter document_slug

In: class-wp-document-revisions.php

Filters the document slug.

## Filter document_title

In: class-wp-document-revisions.php

Filter the document title from the post.

## Filter document_to_private

In: class-wp-document-revisions-admin.php

Filters setting the new document status to private.

## Filter document_use_workflow_states

In: class-wp-document-revisions.php

Filter to switch off use of standard Workflow States taxonomy. For internal use.

## Filter document_verify_feed_key

In: class-wp-document-revisions.php

Allows the RSS feed to be switched off.

## Filter default_workflow_state

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
