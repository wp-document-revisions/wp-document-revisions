=== WP Document Revisions ===

Contributors: benbalter, nwjames
Tags: documents, document management, version control, collaboration, revisions
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 4.1.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.

== Description ==

== What is WP Document Revisions? ==

[WP Document Revisions](https://wordpress.org/plugins/wp-document-revisions/) is a [document management](https://en.wikipedia.org/wiki/Document_management_system) and [version control](http://en.wikipedia.org/wiki/Revision_control) plugin. Built for time-sensitive and mission-critical projects, teams can collaboratively edit files of any format -- text documents, spreadsheets, images, sheet music... anything -- all the while, seamlessly tracking the document's progress as it moves through your organization's existing workflow.

= WP Document Revisions is three things =

1. **📁 Document Management System (DMS)** - Track, store, and organize files of any format
2. **👥 Collaboration Tool** - Empower teams to collaboratively draft, edit, and refine documents
3. **🔒 File Hosting Solution** - Publish and securely deliver files to teams, clients, or the public

See [**the full list of features**](https://wp-document-revisions.github.io/wp-document-revisions/features/) for more information.

== 📚 Documentation ==

**[Complete Documentation Site](https://wp-document-revisions.github.io/wp-document-revisions)** - Your one-stop resource for everything about WP Document Revisions.

= 🎯 Quick Start Guides =

- **[Installation](https://wp-document-revisions.github.io/wp-document-revisions/installation/)** - Get up and running in minutes
- **[Features and Overview](https://wp-document-revisions.github.io/wp-document-revisions/features/)** - Discover what WP Document Revisions can do
- **[Screenshots](https://wp-document-revisions.github.io/wp-document-revisions/screenshots/)** - See the plugin in action

= 📖 User Documentation =

- **[Frequently Asked Questions](https://wp-document-revisions.github.io/wp-document-revisions/frequently-asked-questions/)** - Common questions answered
- **[Block Editor Support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/)** - ⚠️ Experimental Gutenberg support (opt-in)
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
- Opt-in [Block Editor (Gutenberg) support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/) with document sidebar panel (experimental)
- REST API security hardening: attachment data sanitized for non-editors, attachment ownership validation
- WordPress Abilities API integration (WP 6.9+) for AI agents and the command palette
- Native text extraction from PDF, DOCX, and ODT files (pluggable for additional formats), cached per-attachment for search and AI use
- AI-generated revision summaries via the [WordPress 7.0 AI Client](https://wp-document-revisions.github.io/wp-document-revisions/https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/), computed from a unified diff of the new revision against the prior one and pre-filled into the revision log for editor review. See the [Text Extraction & AI cookbook entry](cookbook/text-extraction-and-ai-summaries/) for customization recipes
- WP-CLI `document-revisions extract-text` command to backfill the extraction cache across an existing library, with `--all`/`--missing`/`--id`/`--extractor`/`--force`/`--dry-run` selectors
- Per-document and sitewide opt-outs for text extraction and AI pre-fill — extraction respects `WPDR_TEXT_EXTRACTION`, AI pre-fill respects `WPDR_AI_SUMMARY_PREFILL` and the core `WP_AI_SUPPORT` constant
- Clean uninstall: options, user meta, and capabilities removed on plugin deletion
- Deactivation hook flushes rewrite rules for clean deactivation
- Recently Revised Documents Widget, shortcodes, and templating functions for front-end integration

= Features Available via the [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) =

- **Audit Trail** - creates check in / check out audit trail for all documents
- **Taxonomy-based Permissions** - allows setting user-level permissions based on a custom taxonomy such as department
- **Third Party Encryption** - example of how to integrate at rest encryption using third-party tools
- **Rename Documents** - changes all references to "Documents" in the interface to any label of your choosing
- **State Change Notification** - how to use document api to allow users to receive notification whenever documents change workflow state
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


== Screenshots ==

\###1. A typical WP Document Revisions edit document screen.###

![A typical WP Document Revisions edit document screen.](https://raw.githubusercontent.com/wp-document-revisions/wp-document-revisions/master/screenshot-1.png)


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

= 4.1.0 =

Adds native text extraction and AI-generated revision summaries for document libraries. The full design and the twelve PRs that implemented it are tracked in #514; a smaller set of deferred follow-ups is in #531.

= # Features =

* #514: Native text extraction from PDF (via `smalot/pdfparser`), DOCX, and ODT (via `phpoffice/phpword`). Extraction runs out-of-band via wp-cron after a revision is uploaded; results are SHA-256-keyed against the file's contents and stored as post meta on the attachment so re-extracting unchanged content is a no-op. New `wpdr_text_extractors` filter lets a site register custom extractors for additional formats (see the [cookbook recipe](https://wp-document-revisions.github.io/wp-document-revisions/cookbook/text-extraction-and-ai-summaries/#recipe-1-register-a-custom-extractor)).
* #514: AI-generated revision summaries via the [WordPress 7.0 AI Client](https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/). After extraction completes, a second cron event computes a unified diff between the new revision's text and the prior revision's, sends it to the AI Client with a filterable prompt, and stores the 1–3-sentence summary as post meta on the attachment. Falls back gracefully to summarising the new document directly when the diff is too large or the prior revision has no extractable text. Skips silently when the AI Client is unavailable (older WordPress) or `WP_AI_SUPPORT` is `false`.
* #514: Admin-editor JS pre-fills the AI summary into the revision log textarea on the document edit screen, with a dismiss link. Never clobbers user-typed content. Per-document opt-out ("Do not pre-fill the revision log with AI suggestions") in a new "Text Extraction & AI" sidebar meta box; sitewide opt-out via the `WPDR_AI_SUMMARY_PREFILL` constant.
* #514: Per-document and sitewide opt-outs for text extraction itself ("Skip text extraction for this document" checkbox, `WPDR_TEXT_EXTRACTION` constant). Flipping the per-document opt-out on clears every cache-managed meta key on the document's revision attachments and unschedules pending cron events.
* #514: WP-CLI `document-revisions extract-text` command for backfilling the extraction cache across an existing library. Selectors: `--all`, `--missing` (excludes failure-list entries to prevent infinite retry on malformed files), `--id=<id>`. Modifiers: `--extractor=<class>` to target reprocessing by tool identity, `--force` to bypass cache + failure list, `--dry-run`.
* #514: Read + review REST endpoints under `wpdr/v1`. `GET /documents/<doc>/revisions/<rev>/summary` returns the cached summary with a `status` envelope (`pending` / `ready` / `unavailable`); `read_document` capability. `POST .../summary/review` marks a summary as human-reviewed; `edit_document` capability. Capability mapping by [@NeilWJames](https://github.com/NeilWJames).
* #514: New action `wpdr_text_extracted` fires after extracted text is cached, so third-party search-indexing or embedding consumers can hook without monkey-patching the cache class.
* Adds a [Text Extraction & AI Summaries cookbook recipe](https://wp-document-revisions.github.io/wp-document-revisions/cookbook/text-extraction-and-ai-summaries/) covering custom extractors, prompt customization, the four opt-out switches, the WP-CLI backfill, alternative AI providers, and the REST surface.

= 4.0.7 =

= # Bug Fixes =

* Fix #494: restore attachment ID in `post_content` when classic-editor upload save fails. Two root causes addressed: `wp_kses_post` stripping the `<!-- WPDR N -->` HTML comment for users without `unfiltered_html` (fixed via `restore_document_attachment_id` on `wp_insert_post_data`), and JS upload callback not firing leaving `post_content` empty (fixed via `save_document` fallback to `get_latest_attachment()`).
* Fix PHP `TypeError` in `filter_from_media_grid()`: the `ajax_query_attachments_args` filter passes an `array`, not a `WP_Query` object, so the incorrect type hint caused a fatal error that prevented media library items from loading in the block editor.
* Add WP Plugin Check compliance: phpcs ignore directives for non-prefixed hook names and other plugin-check messages.
* Remove 252 PHPStan baseline suppressions by resolving the underlying type errors.
* Update filter/action documentation to reflect file-splitting of trait files.
* Exclude build artifacts from distributed plugin package via `.distignore`.
* Add `npm ci && npm run build` step to deploy workflow so compiled block JS is included in the WordPress.org distribution.
* Exclude `src/` (uncompiled JSX source) from the WordPress.org distribution via `.distignore`.

= 4.0.6 =

For complete changelog, see [GitHub](https://wp-document-revisions.github.io/wp-document-revisions/changelog/)
