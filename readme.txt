=== WP Document Revisions ===

Contributors: benbalter, nwjames
Tags: documents, document management, version control, collaboration, revisions
Requires at least: 5.9
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 5.6.1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Document management and version control for WordPress. Collaborate on files, track every revision, and auto-generate change summaries with AI.

== Description ==

== What is WP Document Revisions? ==

[WP Document Revisions](https://wordpress.org/plugins/wp-document-revisions/) is a [document management](https://en.wikipedia.org/wiki/Document_management_system) and [version control](http://en.wikipedia.org/wiki/Revision_control) plugin. Built for time-sensitive and mission-critical projects, teams can collaboratively edit files of any format -- text documents, spreadsheets, images, sheet music... anything -- all the while, seamlessly tracking the document's progress as it moves through your organization's existing workflow.

= WP Document Revisions is three things =

1. **📁 Document Management System (DMS)** - Track, store, and organize files of any format
2. **👥 Collaboration Tool** - Empower teams to collaboratively draft, edit, and refine documents
3. **🔒 File Hosting Solution** - Publish and securely deliver files to teams, clients, or the public

= ✨ Modern and AI-ready =

WP Document Revisions keeps pace with modern WordPress:

- **🔍 Full-text search inside your files** — native text extraction from PDF, DOCX, and ODT documents (pluggable for more formats) makes document *contents* searchable, not just their titles.
- **🤖 AI-generated revision summaries** — powered by the [WordPress 7.0 AI Client](https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/), each new revision can be summarized automatically from a diff against the prior version and pre-filled into the revision log for your review (opt-in, with per-document and sitewide opt-outs).
- **🧱 Block editor support** — an opt-in Gutenberg editing experience with a document sidebar panel for uploads, revision summaries, and lock status.
- **🔌 REST API & WP-CLI** — manage documents headlessly and backfill the search/AI cache across your whole library from the command line.

See [**the full list of features**](https://wp-document-revisions.github.io/wp-document-revisions/features/) for more information.

== 📚 Documentation ==

**[Complete Documentation Site](https://wp-document-revisions.github.io/wp-document-revisions)** - Your one-stop resource for everything about WP Document Revisions.

= 🎯 Quick Start Guides =

- **[Installation](https://wp-document-revisions.github.io/wp-document-revisions/installation/)** - Get up and running in minutes
- **[Features and Overview](https://wp-document-revisions.github.io/wp-document-revisions/features/)** - Discover what WP Document Revisions can do
- **[Screenshots](https://wp-document-revisions.github.io/wp-document-revisions/screenshots/)** - See the plugin in action

= 📖 User Documentation =

- **[Frequently Asked Questions](https://wp-document-revisions.github.io/wp-document-revisions/frequently-asked-questions/)** - Common questions answered
- **[Block Editor Support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/)** - Opt-in Gutenberg editing for documents
- **[Plugin Actions](https://wp-document-revisions.github.io/wp-document-revisions/actions/)** - Available WordPress actions
- **[Plugin Filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/)** - Available WordPress filters
- **[Plugin Shortcodes and Widget](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/)** - Display documents on your site
- **[Useful Plugins and Tools](https://wp-document-revisions.github.io/wp-document-revisions/useful-plugins-and-tools/)** - Extend functionality
- **[Cookbook](https://wp-document-revisions.github.io/wp-document-revisions/cookbook/README/)** - Integration guides and recipes
- **[Translations](https://wp-document-revisions.github.io/wp-document-revisions/translations/)** - Multi-language support
- **[Links](https://wp-document-revisions.github.io/wp-document-revisions/links/)** - Additional resources

= 🆘 Support & Community =

- **[Where to get Support or Report an Issue](https://wp-document-revisions.github.io/wp-document-revisions/SUPPORT/)** - Get help when you need it
- **[How to Contribute](https://wp-document-revisions.github.io/wp-document-revisions/CONTRIBUTING/)** - Join our community
- **[Join the Mailing List](https://groups.google.com/forum/#!forum/wp-document-revisions)** - Stay updated


== Features ==

<iframe width="560" height="315" src="https://www.youtube.com/embed/VpsTNSiJKis" frameborder="0" allowfullscreen></iframe>

= Overview =

**Powerful Collaboration Tools** - _With great power does not have to come great complexity._ Based on a simple philosophy of putting powerful but intuitive tools in the hands of managers and content creators, WP Document Revisions leverages many of the essential WordPress features that, for more than eight years, have been tested and proven across countless industries — posts, attachments, revisions, taxonomies, authentication, and permalinks — to make collaborating on the creation and publication of documents a natural endeavor. Think of it as an [open-source and more intuitive version](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/) of the popular Microsoft collaboration suite, [Sharepoint.](http://sharepoint.microsoft.com/en-us/Pages/default.aspx)

**Document History** - At each step of the authoring process, WP Document Revisions gives you an instant snapshot of your team's progress and the document's history. It even gives you the option to revert back to a previous revision — so don't fret if you make a mistake — or receive updates on changes to the document right in your favorite feed reader.

**Access Control** - Each document is given a persistent URL (e.g., yourcompany.com/documents/2011/08/TPS-Report.doc) which can be private (securely delivered only to members of your organization), password protected (available only to those you select such as clients or contractors), or public (published and hosted for the world to see). If you catch a typo and upload a new version, that URL will continue to point to the latest version, regardless of how many changes you make.

**Enterprise Security** - Worried about storing propriety or sensitive information? WP Document Revisions was built from the first line of code with government- and enterprise-grade security in mind. Each file is masked behind an anonymous 128-bit [MD5 hash](http://en.wikipedia.org/wiki/MD5) as soon as it touches the server, and requests for files are transparently routed through WordPress's time-tested URL rewriting, authentication, and permission systems (which can even [integrate with existing enterprise active directory](http://wordpress.org/plugins/active-directory-integration/) or [LDAP servers](http://wordpress.org/extend/plugins/simple-ldap-login/)). Need more security? WP Document Revisions allows you to store documents in a folder above the `htdocs` or `public_html` [web root](http://httpd.apache.org/docs/2.0/mod/core.html#documentroot), further ensuring that only those you authorize have access to your work.

**Customization** - WP Document Revisions recognizes that no two teams are identical, and as a result, molds to your firm's needs, not the other way around. Need to track additional information associated with a document? Departments, editors, issues, sections, even arbitrary key-value pairs — whatever you can throw at it, it can handle. Development and customization costs are further minimized by its extensive plugin API, and the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/) makes it easy for even the uninitiated to add custom taxonomies to documents. Need an audit trail to track check-ins and check-outs? User-level permissions based on the document's state or another custom taxonomy? Support for third-party encryption? Check out the [WP Document Revisions Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) for sample code. Looking for even more advanced control of your workflow? WP Document Revisions will detect the popular workflow plugin [Edit Flow](http://editflow.org), if installed, and will automatically pull [Edit Flow’s advanced workflow management tools](http://ben.balter.com/2011/10/24/advanced-workflow-management-tools-for-wp-document-revisions/) into WP Document Revisions. Simply put, virtually every aspect of the plugin's functionality from workflow states to user-level permissions can be fully customized to your team's unique needs.

**Future Proof** - Switching costs a concern? WP Document Revisions is built with tomorrow's uncertainty in mind. Equally at home in an in-house server room as it is in the cloud, moving individual files or entire document repositories in and out of WP Document Revisions is a breeze (history and all). And since the software is open-source, you can easily add tools to automate the process of moving to or integrating with future third-party systems.

= Features =

- Support for any file type (docs, spreadsheets, images, PDFs — anything!)
- Securely stores unlimited revisions of your business's essential files
- Provides a full file history in the form of a revision log, accessible via RSS
- Helps you track and organize documents as they move through your organization's existing workflow
- Each file gets a permanent, authenticated URL that always points to the latest version
- Each revision gets its own unique url (e.g.,TPS-Report-revision-3.doc) accessible only to those you deem
- Files are intuitively checked out and locked to prevent revisions from colliding
- Toggle documents between public, private, and password protected with a single mouse click
- Runs in-house or in the cloud
- Secure: filenames are hashed on upload and files are only accessible through WordPress's proven authentication system
- Can move document upload folder to location outside of web root to further ensure government- and enterprise-grade security
- Documents and Revisions shortcodes, Recently Revised Documents widget
- Multisite and Windows (XAMPP) support
- Fully translatable, with community translations delivered as WordPress.org language packs ([help translate](https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/))
- Integration with [Edit Flow](https://editflow.org), PublishPress or PublishPress Statuses.
- Opt-in [Block Editor (Gutenberg) support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/) with document sidebar panel
- REST API security hardening: attachment data sanitized for non-editors, attachment ownership validation
- WordPress Abilities API integration (WP 6.9+) for AI agents and the command palette, with read-only abilities exposed over the Model Context Protocol via the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)
- Native text extraction from PDF, DOCX, and ODT files (pluggable for additional formats), cached per-attachment for search and AI use
- AI-generated revision summaries via the [WordPress 7.0 AI Client](https://wp-document-revisions.github.io/wp-document-revisions/https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/), computed from a unified diff of the new revision against the prior one and pre-filled into the revision log for editor review. See the [Text Extraction & AI cookbook entry](cookbook/text-extraction-and-ai-summaries/) for customization recipes
- WP-CLI `document-revisions extract-text` command to backfill the extraction cache across an existing library, with `--all`/`--missing`/`--id`/`--extractor`/`--force`/`--dry-run` selectors
- WP-CLI `document-revisions validate [--fix]` command that runs the Validate Structure checks across every document and applies the available fixes
- Per-document and sitewide opt-outs for text extraction and AI pre-fill — extraction respects `WPDR_TEXT_EXTRACTION`, AI pre-fill respects `WPDR_AI_SUMMARY_PREFILL` and the core `WP_AI_SUPPORT` constant
- Clean uninstall: options, user meta, and capabilities removed on plugin deletion
- Deactivation hook flushes rewrite rules for clean deactivation
- Recently Revised Documents Widget, shortcodes, and templating functions for front-end integration
- Opt-in email notifications when a document changes workflow state or gains a new revision, with a configurable recipient list (see [Document Notifications](https://wp-document-revisions.github.io/wp-document-revisions/notifications/))

= Features Available via the [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) =

- **Audit Trail** - creates check in / check out audit trail for all documents
- **Taxonomy-based Permissions** - allows setting user-level permissions based on a custom taxonomy such as department
- **Third Party Encryption** - example of how to integrate at rest encryption using third-party tools
- **Rename Documents** - changes all references to "Documents" in the interface to any label of your choosing
- **Bulk Import** - how to batch import a directory (or other list) of files as documents
- **Filetype Taxonomy** - Adds support to filter by filetype
- **Track Changes** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility
- **Change Tracker** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility
- **WPML Support** - Integration with WPML


== Installation ==

= 🚀 Automatic Install (Recommended) =

1. **Log into WordPress Admin** - Login to your WordPress site as an Administrator, or if you haven't already, complete the [WordPress installation](https://wordpress.org/support/article/how-to-install-wordpress/)
2. **Go to Plugins** - Navigate to **Plugins > Add New** from the left menu
3. **Search** - Search for "WP Document Revisions"
4. **Install** - Click **"Install Now"** next to WP Document Revisions
5. **Activate** - Click **"Activate"** to enable the plugin

= 📦 Manual Install =

1. **Download** - Get the latest version from [WordPress.org](https://wordpress.org/plugins/wp-document-revisions/) or [GitHub Releases](https://github.com/wp-document-revisions/wp-document-revisions/releases/latest)
2. **Upload** - Unzip the file and upload the "wp-document-revisions" folder to your `/wp-content/plugins/` directory
3. **Activate** - Log into WordPress admin, go to **Plugins**, and activate "WP Document Revisions"

= 💻 Developer Install =

For development or contributing:

```bash
git clone https://github.com/wp-document-revisions/wp-document-revisions.git
cd wp-document-revisions
composer install --no-dev
```

= ⚙️ Requirements =

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **File Permissions:** WordPress must be able to write to the uploads directory

= 🎯 Next Steps =

After installation, you'll find a new **Documents** menu in your WordPress admin. Start by:

1. **Creating your first document** - Go to Documents > Add New
2. **Setting up workflow states** (optional) - Go to Documents > Workflow States
3. **Configuring permissions** - Review Settings > Document Revisions

Need help? Check our [FAQ](https://wp-document-revisions.github.io/wp-document-revisions/frequently-asked-questions/) or [get support](https://wp-document-revisions.github.io/wp-document-revisions/SUPPORT/).


== Links ==

- **[Source Code](https://github.com/wp-document-revisions/wp-document-revisions/)** (GitHub)
- **[Latest Release](https://github.com/wp-document-revisions/wp-document-revisions/releases/latest)** - Download the newest version
- **[WordPress.org Plugin Page](https://wordpress.org/plugins/wp-document-revisions/)** - Official plugin listing
- **[Development Version](https://github.com/wp-document-revisions/wp-document-revisions/tree/develop)** ([CI Status](https://github.com/wp-document-revisions/wp-document-revisions/actions/workflows/ci.yml))
- **[Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook)** - Code examples and customizations
- **[Translations](https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/)** (translate.wordpress.org)
- **[Where to get Support or Report an Issue](https://wp-document-revisions.github.io/wp-document-revisions/SUPPORT/)** - Get help when you need it
- **[How to Contribute](https://wp-document-revisions.github.io/wp-document-revisions/CONTRIBUTING/)** - Join our community


=== Document Notifications ===

WP Document Revisions can email interested people when a document moves through your workflow — when it **changes workflow state** (for example, moved to *Under Review* or *Approved*) or when a **new revision** is saved. This turns workflow states from passive labels into an active review loop: the reviewer actually hears about it.

Notifications are **opt-in and disabled by default**, so enabling the plugin (or upgrading) never starts sending mail on its own.

== Enabling notifications ==

Notifications are configured under **Settings → Media**, in the **Document Notifications** section:

- **Email notifications when documents change** — the master switch. Off by default; nothing is sent until you turn it on.
- **Notify these email addresses** — a site-wide recipient list (one address per line, or comma-separated).
- **Notify when a document changes workflow state** — on by default (only takes effect once the master switch is on).
- **Notify when a new revision of a document is saved** — off by default.

== Who gets notified ==

For each event, the recipients are:

- everyone on the site-wide recipient list, **plus**
- the document's author,

with two rules always applied:

- **the person who made the change is never notified of their own change**, and
- duplicate and invalid addresses are removed.

Each recipient receives their own copy — the recipient list is never exposed to the others, and no stray copy is sent to the site administrator.

> **Note on bulk edits:** because a notification is sent per document, changing the workflow state of many documents at once (for example, a bulk edit) sends one notification per document. Use the `document_notification_recipients` filter to suppress or reroute mail in that situation if needed.

== Customizing with filters ==

Every part of the behavior is filterable. See [Filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/) for full signatures. In brief:

- `document_notify_enabled`, `document_notify_on_state_change`, `document_notify_on_new_revision` — force-enable or force-disable the master switch and each event.
- `document_notification_recipients` — receives `( array $emails, int $doc_id, string $event, int $actor_id )`. This is the seam for advanced routing: notify prior editors, or route by workflow state (e.g. send *Under Review* transitions to a review team).
- `document_notification_subject`, `document_notification_message` — customize the email subject and body.
- `document_notification_headers` — add email headers (e.g. a `From:` or `Reply-To:`).

= Example: route "Under Review" to a review team =

```php
add_filter(
	'document_notification_recipients',
	function ( $emails, $doc_id, $event, $actor_id ) {
		if ( 'state_change' !== $event ) {
			return $emails;
		}
		$states = wp_get_object_terms( $doc_id, 'workflow_state', array( 'fields' => 'names' ) );
		if ( in_array( 'Under Review', $states, true ) ) {
			$emails[] = 'review-team@example.com';
		}
		return $emails;
	},
	10,
	4
);
```

== Delivery ==

Notifications are sent through WordPress's standard `wp_mail()`. On sites with high mail volume or a slow mail server, install a transactional-email/SMTP plugin so delivery does not add latency to saving a document.


== Screenshots ==

1. Every document keeps a complete, audited revision history — who changed what and when, each with a summary and one-click restore.
2. Manage all your documents in one place — see each document's owner, workflow state, and who currently has it checked out, and filter by state or owner.
3. Model your team's review process with fully customizable workflow states.


== Translations ==

**Help bring WP Document Revisions to your language.** Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/), and WordPress installs them automatically as language packs, so every translated string reaches every site using that language. No coding or GitHub account is needed. A free WordPress.org account is enough.

= The most helpful thing you can do: review suggestions =

Thirty languages already have a nearly complete set of suggested translations waiting for review. Some carry over from the plugin's earlier translators, and most are machine translations that need a fluent speaker to check them. A language pack is published once a language reaches 90% approved translations, so reviewing is the fastest way to get the plugin working in your language.

1. Sign in with your [WordPress.org account](https://login.wordpress.org/register).
2. Open the [plugin's translation project](https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/) and choose your language.
3. Filter by **Waiting** to see the suggestions, then fix anything that reads wrong. You can also translate any untranslated strings.
4. To approve suggestions yourself, [request Project Translation Editor access](https://make.wordpress.org/polyglots/handbook/plugin-theme-authors-guide/pte-request/) for your language, or ask your language's [local Polyglots team](https://make.wordpress.org/polyglots/teams/) to review them.

New to translating WordPress? The [Polyglots handbook](https://make.wordpress.org/polyglots/handbook/translating/) covers the basics, including each language's style guide and glossary.

= Found a bad translation? =

Suggest a better one on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/) rather than opening a pull request. Translation files bundled with the plugin are overridden by WordPress.org language packs, so fixes belong there.

Thanks to everyone who has contributed translations, including:

- French - [Hubert CAMPAN](http://omnimaki.com/)
- Spanish - [IBIDEM GROUP](https://www.ibidemgroup.com), [TradiArt](http://www.tradiart.com/), and [elarequi](http://www.labitacoradeltigre.com)
- Norwegian - Daniel Haugen
- German -[Konstantin Obenland](http://en.wp.obenland.it/)
- Chinese - Tim Ren
- Swedish - Daniel Kroon, [Examinare AB](http://www.examinare.biz/), Sweden.
- Czech - Hynek Šťavík
- Italian - @guterboit
- Russian - Evgeny Vlasov
- Dutch - @tijscruysen


== Useful plugins and tools ==

= Permissions management =

- [Members - Membership & User Role Editor Plugin](https://wordpress.org/plugins/members/)

  (Previously called Members)

= Taxonomy management =

- [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/)

= Email notification and distribution =

- [Email Notice for WP Document Revisions](https://wordpress.org/plugins/email-notice-wp-document-revisions/)

= Document workflow management =

- [Edit Flow](https://wordpress.org/plugins/edit-flow/)
- [PublishPress Statuses](https://wordpress.org/plugins/publishpress-statuses/)
- [PublishPress Revisions](https://wp-document-revisions.github.io/wp-document-revisions/https://wordpress.org/plugins/publishpress-revisions/) - See the [integration guide](cookbook/publishpress-revisions-integration/) for scheduling document revisions


== Changelog ==

Numbers in brackets show the issue number in https://github.com/wp-document-revisions/wp-document-revisions/issues/

= 5.6.1 =

* Security: a user who could edit their own documents (Contributors and up, by default) could use the Validate Structure fix endpoint to rename and delete other media files on the site. Fixes now only touch the document's own attachments ([GHSA-wmqm-qwm3-9qgf](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-wmqm-qwm3-9qgf)).
* Security: the documents shortcode, the Documents List and Latest Documents blocks, and the `get_documents()` template function could list other users' draft, pending and private documents (titles, descriptions and authors) when asked for those statuses, and showed descriptions of password-protected documents. Lists now only include documents the viewer can read, and skip the description and thumbnail of password-protected documents. The `post_password` shortcode attribute has been removed ([GHSA-xwv7-7xmq-wmxq](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-xwv7-7xmq-wmxq)).
* Security: the document revisions shortcode and block listed the revision history of documents the viewer couldn't read. They now check the document can be read ([GHSA-cmhr-7795-vvfw](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-cmhr-7795-vvfw)).
* Security: the AI summary and diff REST endpoints served password-protected documents, and summaries of earlier revisions to users without the `read_document_revisions` capability. They now check access the same way as downloading the file ([GHSA-987w-vg4c-32r3](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-987w-vg4c-32r3)).
* Security: on sites that enable the REST API for documents (off by default), the plugin's REST checks could be skipped by changing the case of the route (for example `/wp/v2/Documents`), and did not apply to the revisions list route ([GHSA-fcf8-gjj8-pg9w](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-fcf8-gjj8-pg9w)).

= 5.6.0 =

* Speed up the Media Library on sites with many attachments. Hiding document files from the library joined the posts table to itself, which took several seconds per page on large libraries and ran on every scroll now that WordPress 7.1 loads the library with infinite scroll. It now uses a subquery that returns the same results in a fraction of the time. (#725, #730)
* The document's attachment id (`_document_attachment_id` meta) can now only be set by the plugin. REST requests that include it have it ignored, and users can no longer edit it as a custom field. (#774)
* Fix other users' private documents appearing in status queries that span several post types (e.g. from search plugins). (#766)
* Fix fatal errors when a theme or plugin applies core filters such as `get_next_post_where` with fewer or unexpected arguments. (#732, #744)
* Fix the Revision Log box missing for documents created before 5.0. Their attachment id is stored only in the document content, which is stripped for editing before the plugin looked for it, so the id was never saved and the box stayed hidden until a new version was uploaded. (#726, #731)
* Fix a doubled slash in the document upload path, which broke uploads through stream wrappers such as `s3://`. (#745)
* Draft and pending document links now use the site's home URL rather than the WordPress install URL, fixing links on Bedrock and other subdirectory installs. (#746)
* Fix the network "Document Date in Permalink" setting having no effect on multisite permalinks. (#747)
* Fix stale document revision lists after revisions or attachments were added or deleted outside a document save, which persisted with a persistent object cache. (#751)
* Validate Structure no longer treats a document's featured image, or another image uploaded to it later, as the document's file. It now uses the document's stored attachment id when that still names one of its files. (#767, #773)
* The `override-document-lock` ability now checks that you can edit the document before reporting its lock state, and overrides the lock the same way the editor does: it notifies the previous owner and fires `document_lock_override`. (#749)
* The document editor, Revision Log, revision shortcode/block, feed, widgets and `get-document-revisions` ability now show who uploaded the current version instead of the document's owner. New `document_revision_author` filter. (#750)
* Requests for a document with no file now return 404 instead of 403. `document_no_document_response_code` also receives the document and revision. (#761)
* The front-end stylesheet now loads only on pages that show a document Edit link. New `document_register_blocks` filter to turn off the plugin's blocks. (#758)
* JSON, XML and SVG documents are now compressed on download like text files. New `document_compressible_mimetypes` filter. (#760)
* The document upload directory is now resolved when first needed and per site on multisite, so offload plugins such as S3-Uploads apply. New `document_upload_directory` filter. (#752)
* Changing a document's slug from the edit screen now keeps its old URL working (redirected by WordPress), and fires a new `document_permalink_updated` action. (#753)
* Validate Structure's script now loads only on its own screen, and a new `document_validate_structure_capability` filter controls who can use it. (#748)
* New `wp document-revisions validate [--fix]` WP-CLI command runs the Validate Structure checks, and optionally the fixes, across all documents. (#768)
* The documents list has sortable File and Revisions columns and a "Missing file" filter. (#770)
* New `document_serve_redirect_url` filter to send authorized document requests to a CDN or signed storage URL, and a public `get_raw_attachment_url()`. (#763)
* Documents can now be handed to the web server with X-Sendfile, X-Accel-Redirect or X-LiteSpeed-Location (opt-in via `document_serve_sendfile_header`). (#769)
* New `is_document_upload()` method and `document_upload_start` / `document_upload_end` actions for integrations such as offload plugins. (#762)
* New `document_allowed_mimes` and `document_upload_size_limit` filters set allowed file types and the size limit for document uploads separately from the Media Library (useful on multisite). (#771)
* Document REST responses include a read-only `document_file` field (for users who can edit the document). (#765)
* New `wpdr_get_document_text()` helper and `wpdr_text_cleared` action; `wpdr_text_extracted` also receives the document ID. (#764)
* With text extraction or the AI pre-fill turned off sitewide, their meta box options and the summary/diff REST routes are no longer shown or registered. New `document_text_extraction_disabled` and `document_ai_prefill_disabled` filters. (#757)
* New `document_show_empty_state` and `document_show_review_prompt` filters turn off the first-run notice and the review prompt. (#754)
* New `document_register_abilities` and `document_get_info_ability_capability` filters control the plugin's Abilities API integration. (#755)
* New `document_show_description_editor` filter hides the Document Description editor on the classic edit screen. (#756)
* New hooks are documented under [Filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/) and [Actions](https://wp-document-revisions.github.io/wp-document-revisions/actions/). (#759)

= 5.5.1 =

For complete changelog, see [GitHub](https://wp-document-revisions.github.io/wp-document-revisions/changelog/)
