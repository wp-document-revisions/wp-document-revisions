# WP-Documents-Revisions Filters

This plugin makes use of many filters to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones shown at the bottom.

## Filter default_workflow_states

In: class-wp-document-revisions.php

Filters the default workflow state values.

## Filter document_ai_prefill_disabled

In: class-wp-document-revisions-text-extraction-opt-out.php

Filters whether the AI revision-log pre-fill is turned off sitewide. Defaults to whether `WPDR_AI_SUMMARY_PREFILL` is defined as `false`. When true, the pre-fill checkbox isn't shown in the "Text Extraction & AI" meta box. (Since 5.6.0.)

## Filter document_ai_summary_allow_private

In: class-wp-document-revisions-ai-summary.php

Filters whether AI summaries are generated and shown for private and password-protected documents. Generating a summary sends the document's text to the site's AI provider, so these documents are skipped by default. Receives `$allow` (default `false`) and the document `WP_Post`. Return true to allow summaries. (Since 5.7.0.)

## Filter document_allow_revision_deletion

In: trait-wp-document-revisions-revisions.php

Filter to allow revision deletion. Set to true to bypass these tests and allow delete.
Note that this should be used when deleting revisions by trusted plugins e.g. PublishPress Revisions.

## Filter document_block_taxonomies

In: class-wp-document-revisions-front-end.php

Filters the Document taxonomies (allowing users to select the first three for the block widget.

## Filter document_buffer_size

In: trait-wp-document-revisions-file-handler.php

Filter to define file writing buffer size (Default 0 = No buffering).

## Filter document_caps

In: class-wp-document-revisions.php

Filters the default capabilities provided by the plugin.
Note that by default all custom roles will have the default Subscriber access.

## Filter document_check_orphans

In: class-wp-document-revisionsvalidate-structure.php

Filter to Switch off checking for orphan documents.

## Filter document_content_disposition_inline

In: trait-wp-document-revisions-file-handler.php

Sets the content disposition header to open the document (inline) or to save it (attachment).
Ordinarily set as inline but can be changed.

## Filter document_custom_feed

In: trait-wp-document-revisions-file-handler.php

Sets to false to use the standard RSS feed.

## Filter document_extension

In: trait-wp-document-revisions-file-handler.php

Allows the document file extension to be manipulated.

## Filter document_get_info_ability_capability

In: class-wp-document-revisions.php

Filters the capability needed to use the `get-document-info` ability. Default `read_documents`. The ability also checks `read_document` on the specific document. (Since 5.6.0.)

## Filter document_help_array

In: trait-wp-document-revisions-admin-editor.php

Filters the default help text for current screen.

## Filter document_home_url

In: trait-wp-document-revisions-rewrites.php

Filters the home_url() for WPML and translated documents. Since 5.6.0 it also applies to draft and pending document links (`?post_type=document&p=N`).

## Filter document_internal_filename

In: trait-wp-document-revisions-file-handler.php

Filters the encoded file name for the attached document (on save).

## Filter document_library_query_args

In: class-wp-document-revisions-document-library.php

Filters the query arguments of the Document Library block and `[document_library]` shortcode. Documents the viewer can't read are still removed.

## Filter document_library_row

In: class-wp-document-revisions-document-library.php

Filters the HTML of each field shown for a document in the Document Library. Return an empty string for a field to leave it out of that document's row.

## Filter document_lock_check

In: trait-wp-document-revisions-revisions.php

Filters the user locking the document file.

## Filter document_lock_override_email

In: trait-wp-document-revisions-revisions.php

Filters the lost lock document email text.

## Filter document_no_document_response_code

In: trait-wp-document-revisions-file-handler.php

Filters the HTTP response code when a document or revision has no file to serve. Default 404 (403 before 5.6.0). Receives the code, the document post and the document or revision ID selected (0 if none was found).

> [!WARNING]  
> Modifying the response code from the default value of 403 may introduce an existence vulnerability.
> 
> By comparing 403 responses (revision found but not authorized) to 404 responses (revision not found)
> a non-authorized user can determine if a document exists or not.

## Filter document_output_sent_is_ok

In: trait-wp-document-revisions-file-handler.php

Filter to serve file even if output already written.

## Filter document_path

In: trait-wp-document-revisions-file-handler.php, class-wp-document-revisions-validate-structure.php

Filters the file name for WAMP settings (filter routine provided by plugin).

## Filter document_permalink

In: trait-wp-document-revisions-rewrites.php

Filters the Document permalink.

## Filter document_post_thumbnail

In: trait-wp-document-revisions-query.php

Filters the post-thumbnail size parameters (used only if this image size has not been set).

## Filter document_read_uses_read

In: class-wp-document-revisions.php, class-wp-document-revisions-front-end.php, class-wp-document-revisions-manage-rest.php, trait-wp-document-revisions-file-handler.php

Filters the users capacities to require read (default) (or read_document) capability to read a document.

## Filter document_register_abilities

In: class-wp-document-revisions.php

Filters whether to register the plugin's abilities, and their category, with the Abilities API. Return `false` to keep documents out of the Abilities API entirely, including its REST endpoints and any MCP adapter. (Since 5.6.0.)

## Filter document_register_blocks

In: class-wp-document-revisions-front-end.php

Filters whether to register the plugin's blocks (documents list, revisions list, document preview and recently revised documents). The shortcodes and the classic widget are unaffected. (Since 5.6.0.)

## Filter document_revision_author

In: trait-wp-document-revisions-revisions.php

Filters the user shown as having saved a document version (the "Checked in ... by" line, Revision Log, revisions shortcode and block, feed, widgets and `get-document-revisions` ability). Receives the user ID and the document or revision post. By default, the current version shows the author of the latest revision, falling back to the document owner. (Since 5.6.0.)

## Filter document_revision_query

In: trait-wp-document-revisions-revisions.php

Filters the plugin query to fetch all the attachments of a parent post.

## Filter document_revisions_cpt

In: class-wp-document-revisions.php

Filters the delivered document type definition prior to registering it.

## Filter document_revisions_ct

In: class-wp-document-revisions.php

Filters the default structure and label values of the workflow_state taxonomy on declaration.

## Filter document_revisions_limit

In: trait-wp-document-revisions-query.php

Filters the number of revisions to keep for documents.

## Filter document_revisions_merge_revisions

In: trait-wp-document-revisions-admin-editor.php

Filters whether to merge two revisions for a change in excerpt (generally where taxonomy change made late).

## Filter document_revisions_mimetype

In: trait-wp-document-revisions-file-handler.php

Filters the MIME type for a file before it is processed by WP Document Revisions. Receives the MIME type (null), the file path and, since 5.5.0, the attachment ID.

## Filter document_revisions_serve_file_headers

In: trait-wp-document-revisions-file-handler.php

Filters the HTTP headers sent when a file is served through WP Document Revisions.

## Filter document_revisions_use_edit_flow

In: class-wp-document-revisions.php

Filter to switch off integration with Edit_Flow/PublishPress statuses.

## Filter document_rewrite_rules

In: trait-wp-document-revisions-rewrites.php

Filters the Document rewrite rules.

## Filter document_serve

In: trait-wp-document-revisions-file-handler.php

Filters file name of document served. (Useful if file is encrypted at rest).

## Filter document_serve_attachment

In: trait-wp-document-revisions-file-handler.php

Filter the attachment post to serve (Return false to stop display).

## Filter document_serve_use_gzip

In: trait-wp-document-revisions-file-handler.php

Filter to determine if gzip should be used to serve file (subject to browser negotiation).

Defaults to true only when the browser accepts gzip/deflate and the document's MIME type is `text/*`. Use `add_filter( 'document_serve_use_gzip', '__return_true' )` to compress other types too.

## Filter document_shortcode_atts

In: class-wp-document-revisions-front-end.php

Filters the Document shortcode attributes.

## Filter document_shortcode_show_edit

In: class-wp-document-revisions-front-end.php

Filters the controlling option to display an edit option against each document.

## Filter document_show_description_editor

In: class-wp-document-revisions.php

Filters whether the classic document edit screen shows the Document Description editor. Return `false` for sites that don't use descriptions; existing descriptions are kept. Has no effect in block editor mode. (Since 5.6.0.)

## Filter document_show_empty_state

In: trait-wp-document-revisions-admin-list.php

Filters whether to show the first-run "Add your first document" notice on the documents list. (Since 5.6.0.)

## Filter document_show_in_rest

In: class-wp-document-revisions.php

Filters the show_in_rest parameter from its default value of false. Must be set to true to enable the block editor or REST API access for documents. Enabling this exposes document data via the WordPress REST API using document permissions. See [Block Editor Support](block-editor.md).

## Filter document_show_review_prompt

In: trait-wp-document-revisions-admin-list.php

Filters whether to show the WordPress.org review prompt. Receives the current user ID. Return `false` to turn it off for everyone. (Since 5.6.0.)

## Filter document_slug

In: trait-wp-document-revisions-file-handler.php

Filters the document slug.

## Filter document_stop_file_access_pattern

In: class-wp-document-revisions.php, trait-wp-document-revisions-rewrites.php

Filter to stop direct file access to documents (specify the URL element (or trailing part) to traverse to the document directory.

## Filter document_taxonomy_term_count

In: trait-wp-document-revisions-query.php

Filter to select which taxonomies with default term count to be modified to count all non-trashed posts.

## Filter document_text_extraction_disabled

In: class-wp-document-revisions-text-extraction-opt-out.php

Filters whether text extraction is turned off sitewide. Defaults to whether `WPDR_TEXT_EXTRACTION` is defined as `false`. When true, no text is extracted, the "Text Extraction & AI" meta box isn't shown and the `wpdr/v1` summary and diff REST routes aren't registered. (Since 5.6.0.)

## Filter document_thumbnail

In: class-wp-document-revisions-front-end.php, class-wp-document-revisions-recently-revised-widget.php

Filters the post thumbnail size on blocks/shortcodes - default thumbnail.

## Filter document_title

In: trait-wp-document-revisions-query.php

Filter the document title from the post.

## Filter document_to_private

In: trait-wp-document-revisions-admin-editor.php

Filters setting the new document status to private.

## Filter document_upload_directory

In: trait-wp-document-revisions-file-handler.php

Filters the directory documents are stored in, e.g. to use a stream wrapper such as `s3://bucket/documents`. Applied when the directory is first needed on each site, after other plugins have loaded. (Since 5.6.0.)

## Filter document_use_block_editor

In: class-wp-document-revisions.php, class-wp-document-revisions-manage-rest.php, trait-wp-document-revisions-admin-editor.php

Filters whether to enable opt-in block editor (Gutenberg) support for documents. Default is false. When set to true, enables REST API write methods, registers post meta for REST, adds excerpt support to the document post type, and hides the main editor canvas. Must be used together with `document_show_in_rest` filter. See [Block Editor Support](block-editor.md).

## Filter document_use_workflow_states

In: class-wp-document-revisions.php

Filter to switch off use of standard Workflow States taxonomy. For internal use.

## Filter document_use_wp_filesystem

In: trait-wp-document-revisions-file-handler.php

Filter whether WP_FileSystem used to serve document (or PHP readfile). Irrelevant if file compressed on output.

## Filter document_validate

In: class-wp-document-revisions-validate-structure.php

Filter whether to validate the document structure for a documrnt.

## Filter document_validate_md5

In: class-wp-document-revisions-validate-structure.php

Filter to switch off md5 format attachment validation.

## Filter document_validate_orphans

In: class-wp-document-revisions-validate-structure.php

Filters the list of orphan attachment records found for a document.

## Filter document_validate_structure_capability

In: class-wp-document-revisions-validate-structure.php

Filters the capability needed to see the Validate Structure screen and use its fixes. Default `edit_documents`; fixing a document also requires permission to edit it. For example, return `manage_options` to limit it to administrators. (Since 5.6.0.)

## Filter document_verify_feed_key

In: trait-wp-document-revisions-file-handler.php

Allows the RSS feed to be switched off.

## Filter lock_override_notice_subject

In: trait-wp-document-revisions-revisions.php

Filters the locked document email subject text.

## Filter lock_override_notice_message

In: trait-wp-document-revisions-revisions.php

Filters the locked document email message text.

## Filter send_document_override_notice

In: trait-wp-document-revisions-revisions.php

Filters the option to send a locked document override email

## Filter serve_document_auth

In: trait-wp-document-revisions-file-handler.php

Filters the decision to serve the document through WP Document Revisions.

## Filter wpdr_text_extractors

In: includes/class-wp-document-revisions-text-extractor-registry.php

Ordered list of `WP_Document_Revisions_Text_Extractor` implementations. The first whose `supports($mime_type)` returns true wins. Prepend with `array_unshift()` to override the built-in PDF and DOCX extractors; append to add support for additional file formats. See the [Text Extraction & AI Summaries cookbook entry](cookbook/text-extraction-and-ai-summaries.md#recipe-1-register-a-custom-extractor) for an example.

## Filter wpdr_text_extraction_delay

In: includes/class-wp-document-revisions-text-extractor-scheduler.php

Seconds between a revision attachment insert and the cron event that runs extraction. Default 10. Raise on sites where post-save listeners are slow.

## Filter wpdr_text_extraction_timeout

In: includes/class-wp-document-revisions-text-extractor-scheduler.php

Hard timeout, in seconds, applied via `set_time_limit()` inside the extraction cron handler and the AI diff REST endpoint. Default 30. Advisory only — no-op when `safe_mode` is on or `set_time_limit` is disabled.

## Filter wpdr_text_extraction_max_file_size

In: includes/class-wp-document-revisions-text-extractor-registry.php

Largest file, in bytes, that text extraction will process. Larger files are treated as failed extractions. Default 20 MB. Set to 0 to turn the check off. Receives the limit, the file path and the MIME type. (Since 5.7.0.)

## Filter wpdr_text_extraction_max_uncompressed_size

In: includes/class-wp-document-revisions-text-extractor-registry.php

Largest total unzipped size, in bytes, of a Word or OpenDocument file that text extraction will process. This guards against zip bombs. Default 100 MB. Set to 0 to turn the check off. Receives the limit, the file path and the MIME type. (Since 5.7.0.)

## Filter wpdr_text_diff_context_lines

In: includes/class-wp-document-revisions-text-diff.php

Lines of context per hunk emitted by the unified-diff helper when summarising a revision. Default 3. Set higher to give the AI more surrounding context for references like "Section 4.2."

## Filter wpdr_text_diff_max_chars

In: includes/class-wp-document-revisions-text-diff.php

Maximum rendered-diff size, in characters, before the helper reports `too_large` and the summary path falls back to summarising the new document directly. Default 50000.

## Filter wpdr_ai_summary_available

In: includes/class-wp-document-revisions-ai-summary.php

Force-enable (return true) or force-disable (return false) the AI summary generation pipeline. The default check is `function_exists( 'wp_ai_client_prompt' )` (WordPress 7.0+) combined with the `WP_AI_SUPPORT` constant. Tests and sites running an alternative provider via `wpdr_ai_summary_generator` use this to force-enable without WP 7.0.

## Filter wpdr_ai_summary_generator

In: includes/class-wp-document-revisions-ai-summary.php

Intercept the AI call. Receives `(string|null $default, string $prompt)`; return a string to short-circuit the WP AI Client invocation (used by the test suite and by sites running an alternative SDK), or null to defer to the default `wp_ai_client_prompt()` path.

## Filter wpdr_ai_summary_prompt

In: includes/class-wp-document-revisions-ai-summary.php

Customize the system prompt template. Receives `(string $default, string $kind, int $attachment_id)` where `$kind` is one of `'change'` or `'document'`. Return a string to replace the template. The input text (diff or extracted text) is deliberately NOT passed — it is concatenated after the template, not substituted into it. See the [cookbook recipe](cookbook/text-extraction-and-ai-summaries.md#recipe-2-customize-the-ai-summary-prompt) for an example.

## Filter wpdr_ai_summary_delay

In: includes/class-wp-document-revisions-ai-summary.php

Seconds between text extraction completing and the summary cron event firing. Default 10. Mirrors `wpdr_text_extraction_delay` for the second stage of the pipeline.

## Filter wpdr_ai_summary_timeout

In: includes/class-wp-document-revisions-ai-summary.php

Hard timeout, in seconds, for a single summary generation call. Default 60. Applied via `set_time_limit()` inside the cron handler (advisory).

## Filter document_notify_enabled

In: includes/class-wp-document-revisions-notifications.php

Master switch for document email notifications. Receives the boolean value of the `document_notify_enabled` site option. Return true to force-enable or false to force-disable notifications regardless of the setting.

## Filter document_notify_on_state_change

In: includes/class-wp-document-revisions-notifications.php

Whether to notify on workflow-state changes. Receives the boolean value of the `document_notify_on_state_change` site option (default true). Return false to suppress state-change notifications.

## Filter document_notify_on_new_revision

In: includes/class-wp-document-revisions-notifications.php

Whether to notify when a new revision is saved. Receives the boolean value of the `document_notify_on_new_revision` site option (default false). Return true to enable new-revision notifications.

## Filter document_notification_recipients

In: includes/class-wp-document-revisions-notifications.php

The list of email addresses to notify. Receives `( string[] $emails, int $doc_id, string $event, int $actor_id )` where `$event` is `'state_change'` or `'new_revision'`. The default list is the site-wide recipient list plus the document author, with the acting user removed and duplicates/invalid addresses dropped. Return a modified array to reroute — this is the seam for per-document subscribers or state-based routing (e.g. sending *Under Review* transitions to a review team). Addresses are re-validated after filtering.

## Filter document_notification_subject

In: includes/class-wp-document-revisions-notifications.php

The notification email subject. Receives `( string $subject, int $doc_id, string $event )`.

## Filter document_notification_message

In: includes/class-wp-document-revisions-notifications.php

The notification email body. Receives `( string $message, int $doc_id, string $event )`.

## Filter document_notification_headers

In: includes/class-wp-document-revisions-notifications.php

Email headers for notifications. Receives `( string[] $headers, int $doc_id, string $event )`; defaults to an empty array. Use to add a `From:`, `Reply-To:`, or other headers.
