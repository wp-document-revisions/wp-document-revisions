# WP-Documents-Revisions Action Hooks

This plugin makes use of many action hooks to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones.

## Action change_document_workflow_state

Called when the post is saved and Workflow_State taxonomy value is changed. (Only post_ID and new value are available)

In: trait-wp-document-revisions-admin-editor.php

## Action document_change_workflow_state

Called when the post is saved and Workflow_State taxonomy value is changed. (post_ID, new and old value are available)

In: trait-wp-document-revisions-admin-editor.php

## Action document_edit

Called as part of the Workflow_State taxonomy when putting the metabox on the admin page

In: trait-wp-document-revisions-admin-editor.php

## Action document_lock_notice

Called when putting the lock notice on the admin edit screen.

In: trait-wp-document-revisions-admin-editor.php

## Action document_lock_override

Called after trying to over-ride the lock and possibly a notice has been sent.

In: trait-wp-document-revisions-revisions.php

## Action document_saved

Called when a document has been saved and all plugin processing done.

In: trait-wp-document-revisions-admin-editor.php

## Action document_serve_done

Called just after serving the file to the user.

In: trait-wp-document-revisions-file-handler.php

## Action serve_document

Called just before serving the file to the user.

In: trait-wp-document-revisions-file-handler.php

## Action wpdr_text_extracted

Fires after extracted text is successfully cached for a revision attachment. Receives the attachment ID. Used internally by the AI summary scheduler to queue a follow-on cron event; third-party consumers (search indexing, embedding generation, etc.) can hook this to react to new extracted content without monkey-patching the cache class.

In: includes/class-wp-document-revisions-text-extractor-cache.php
