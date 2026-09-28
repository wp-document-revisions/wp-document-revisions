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

Called after a user overrides another user's lock on a document, from the editor's override link or the `override-document-lock` ability. Receives the document ID, the user now holding the lock and the previous lock owner.

In: trait-wp-document-revisions-revisions.php

## Action document_permalink_updated

Called after a document's slug is changed from the edit screen. Receives the document ID, the new slug and the previous slug. The previous slug is also stored in `_wp_old_slug`, so WordPress redirects the old URL. (Since 5.6.0.)

In: trait-wp-document-revisions-rewrites.php

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

Fires after extracted text is successfully cached for a revision attachment. Receives the attachment ID and, since 5.6.0, the document ID. Used internally by the AI summary scheduler to queue a follow-on cron event; third-party consumers (search indexing, embedding generation, etc.) can hook this to react to new extracted content without monkey-patching the cache class.

In: includes/class-wp-document-revisions-text-extractor-cache.php

## Action wpdr_text_cleared

Fires after cached extracted text is removed from a revision attachment, for example when a document opts out of extraction. Receives the attachment ID and the document ID, so search indexes and other consumers of `wpdr_text_extracted` can drop the text too. (Since 5.6.0.)

In: includes/class-wp-document-revisions-text-extractor-cache.php
