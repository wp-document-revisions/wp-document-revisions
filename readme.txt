=== WP Document Revisions ===

Contributors: benbalter, nwjames
Tags: documents, document management, version control, collaboration, revisions
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 5.3.0
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
- Multiple language support including French, Spanish and German (easily translated to your language)
- Integration with [Edit Flow](https://editflow.org), PublishPress or PublishPress Statuses.
- Opt-in [Block Editor (Gutenberg) support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/) with document sidebar panel
- REST API security hardening: attachment data sanitized for non-editors, attachment ownership validation
- WordPress Abilities API integration (WP 6.9+) for AI agents and the command palette, with read-only abilities exposed over the Model Context Protocol via the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)
- Native text extraction from PDF, DOCX, and ODT files (pluggable for additional formats), cached per-attachment for search and AI use
- AI-generated revision summaries via the [WordPress 7.0 AI Client](https://wp-document-revisions.github.io/wp-document-revisions/https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/), computed from a unified diff of the new revision against the prior one and pre-filled into the revision log for editor review. See the [Text Extraction & AI cookbook entry](cookbook/text-extraction-and-ai-summaries/) for customization recipes
- WP-CLI `document-revisions extract-text` command to backfill the extraction cache across an existing library, with `--all`/`--missing`/`--id`/`--extractor`/`--force`/`--dry-run` selectors
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
- **[Translations](https://crowdin.com/project/wordpress-document-revisions)** (Crowdin)
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

Interested in translating WP Document Revisions? You can do so [via Crowdin](https://crowdin.com/project/wordpress-document-revisions), or by submitting a pull request.

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

= 5.4.0 =

* Add an inline document preview: the new `[document_preview]` shortcode and matching **Document Preview** block embed a document's latest revision directly in a post or page (PDFs and images render inline; other file types offer a download link). The preview is served through the document's permalink, so it always shows the current revision and respects the document's existing access control. (#634)
* Add opt-in email notifications (Settings → Media) that alert a configurable recipient list, plus the document's author, when a document changes workflow state or gains a new revision. Disabled by default; the person making the change is never notified of their own change; fully customizable via filters. Supersedes the former code-cookbook recipe by making state-change notifications a built-in feature.
* Fix a fatal `TypeError` when serving or resolving a document's attached file on newer WordPress: the `get_attached_file` filter now accepts the attachment ID as either an integer or a numeric string (WordPress core passes a string in some paths), casting it internally. Previously the strict `int` type hint could abort the request.
* Fix Abilities API registration on WordPress 6.9+: the plugin's ability category was registered without the now-required `description`, which caused the category — and therefore all four document abilities — to silently fail to register. The category now includes a description and the abilities register as intended.
* Expose the three read-only Abilities API abilities (`check-document-access`, `get-document-info`, `get-document-revisions`) to AI agents over the Model Context Protocol via the WordPress MCP Adapter — no bundled MCP server required. The mutating `override-document-lock` ability is intentionally excluded, keeping the agent-facing surface read-only, and every call continues to enforce per-document read permissions.

= 5.3.0 =

* The WordPress.org "Live Preview" now opens with sample documents that already carry revision history and workflow states, so the preview demonstrates version control and workflow at a glance instead of showing an empty list. (#632)
* Add a first-run prompt on the Documents screen that points new users (and Live Preview visitors) to add their first document, replacing the blank empty-state table. (#632)
* Occasionally invite established users to leave a WordPress.org review — shown only after real, successful use, dismissible, and never repeated once dismissed. (#632)
* Documentation: block editor (Gutenberg) support is now described as supported and opt-in rather than experimental, and the readme leads with the plugin's full-text search and AI-generated revision summary capabilities. (#632)

= 5.2.0 =

For complete changelog, see [GitHub](https://wp-document-revisions.github.io/wp-document-revisions/changelog/)
