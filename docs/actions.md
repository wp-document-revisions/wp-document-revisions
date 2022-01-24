# WP-Documents-Revisions Action Hooks

This plugin makes use of many action hooks to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones.

## Action change_document_workflow_state

Called when the post is saved and Workflow_State taxonomy value is changed. (Only post_ID and new value are available)

In: class-wp-document-revisions-admin.php

## Action document_change_workflow_state

Called when the post is saved and Workflow_State taxonomy value is changed. (post_ID, new and old value are available)

In: class-wp-document-revisions-admin.php

## Action document_edit

Called as part of the Workflow_State taxonomy when putting the metabox on the admin page

In: class-wp-document-revisions-admin.php

## Action document_lock_notice

Called when putting the lock notice on the admin edit screen.

In: class-wp-document-revisions-admin.php

## Action document_lock_override

Called after trying to over-ride the lock and possibly a notice has been sent.

In: class-wp-document-revisions.php

## Action document_saved

Called when a document has been saved and all plugin processing done.

In: class-wp-document-revisions-admin.php

## Action document_serve_done

Called just after serving the file to the user.

In: class-wp-document-revisions.php

## Action serve_document

Called just before serving the file to the user.

In: class-wp-document-revisions.php

