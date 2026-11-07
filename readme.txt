=== WP Document Revisions ===

Contributors: benbalter, nwjames
Tags: documents, document management, version control, collaboration, revisions
Requires at least: 5.9
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 5.8.3
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
- **[Document Notifications](https://wp-document-revisions.github.io/wp-document-revisions/notifications/)** - Email people when a document changes state or gets a new revision
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

= Features Available via the Code Cookbook =

The [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) has sample code for these:

- **Audit Trail** - creates check in / check out audit trail for all documents
- **Taxonomy-based Permissions** - allows setting user-level permissions based on a custom taxonomy such as department
- **Third Party Encryption** - example of how to integrate at rest encryption using third-party tools
- **Rename Documents** - changes all references to "Documents" in the interface to any label of your choosing
- **Bulk Import** - how to batch import a directory (or other list) of files as documents
- **Filetype Taxonomy** - Adds support to filter by filetype
- **Track Changes** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility
- **Change Tracker** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility
- **WPML Support** - Integration with WPML


== Frequently Asked Questions ==

= How do I show documents on the front end of my site? =

Add the Document Library block, or the `[document_library]` shortcode, to any post or page. It lists documents as a list, a table or a grid of cards, shows only the documents each visitor can read, and lets you pick which details appear, such as workflow state, author and revision count. The plugin also has blocks and shortcodes for a document's revisions and for recently revised documents. See [Shortcodes and blocks](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/).

You can also link straight to any public document. Its permalink always serves the latest revision. Because documents are WordPress posts, a list of the documents each user can see is at yourdomain.com/documents/, and many built-in WordPress features, such as search and archives, work with them.

= Who can see my documents? =

Each document has one of three visibilities:

- **Private:** only logged-in users who are allowed to read private documents. You can narrow this further by user or by workflow state.
- **Password protected:** anyone with the document's password.
- **Public:** anyone with the document's URL.

= Are the documents I upload secure? =

Every request for a file goes through WordPress's own login and permission checks, the same ones that keep private and unpublished posts hidden. Uploaded files get hashed names, so nobody can guess a file's address. For more protection, move the document upload folder outside the web root under **Settings → Media → Document Upload Directory**, so the web server can't hand out files directly at all.

= How do permissions work? =

Documents have their own capabilities, such as `edit_documents` and `read_private_documents`, which start out matching each role's permissions for posts. Change them with a role editor plugin such as [Members](https://wordpress.org/plugins/members/), or in code with the `document_caps` filter.

= Can I make it so that users can only access documents assigned to them, or documents they created? =

Yes. Each document has an owner, which you can change on the edit-document screen (by default, the person who created it). A private document can be read only by users with the `read_private_documents` capability, and a role editor such as [Members](https://wordpress.org/plugins/members/) lets you take that capability away from roles that should see only their own documents.

= Can I limit access by workflow state, department or another taxonomy? =

Yes, with the [taxonomy permissions plugin](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/tree/master/wpdr-taxonomy-permissions) from the Code Cookbook. It adds a "departments" taxonomy (you can point it at a different one) and a capability for each term, so a role needs `edit_document_in_marketing`, say, as well as `edit_document`. Assign those capabilities to roles with a plugin like [Members](https://wordpress.org/plugins/members/).

= How does checking out a document work? =

Opening a document to edit it checks it out, and nobody else can upload a new version until you're done. Anyone allowed to read it can still view and download the current version and every earlier one while it's checked out.

There's no time limit. You keep the lock as long as the document's page stays open, even in a minimized window. Administrators can override a lock, and the person who held it gets an email.

= What happens if I lose my internet connection while I have a document checked out? =

The page tells the server every minute that you still have the document open. If the connection drops, you get about two minutes before the lock lifts. A brief Wi-Fi drop is fine. After a long one, such as a flight, someone else may have checked the document out. If nobody uploaded a new version in the meantime, you don't need to download it again: stay on the document's page and you'll keep the lock.

= Does it track each person's changes? =

It records who uploaded each version and when, and asks for a short summary of what changed. For changes inside a file, use the file format's own history, such as tracked changes in Microsoft Word.

= Can it email people when a document changes? =

Yes. Turn on notifications under **Settings → Media**, and the plugin emails a list of people when a document moves to a new workflow state or gets a new version. Notifications are off until you turn them on. See [Document Notifications](https://wp-document-revisions.github.io/wp-document-revisions/notifications/).

= What file types can I upload? =

Anything WordPress allows, which covers [the common file types](https://wordpress.org/documentation/article/media-library-screen/) out of the box. On multisite, the network admin sets the allowed types under **Network Admin → Settings**. On a single site, a plugin can add more. The other limit is file size, which your host sets in PHP's `upload_max_filesize` and `post_max_size`.

= Does it work on Mac, Windows and mobile? =

It works in any modern browser, so Mac, Windows and Linux users can share the same documents. Mobile browsers can view and download documents, but uploading new versions from a phone may not work in every browser.

= Can I put my documents in folders? =

Sort of, and with more flexibility. Instead of one folder per file, a document can carry any number of categories or other terms, which works like being in several folders at once. To add a folder-style taxonomy, use [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/): add a taxonomy for the "Documents" post type and set "Hierarchical" to true. The Document Library block can then filter by it.

= Can I tag documents, or group them some other way? =

Yes. Add taxonomies with [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/), or share your existing post categories and tags with documents.

= What if I want more control over my workflow? =

The plugin comes with editable workflow states and email notifications. For more, such as editorial comments, a content calendar and role-based notifications, install [PublishPress](https://wordpress.org/plugins/publishpress/) or [Edit Flow](https://wordpress.org/plugins/edit-flow/). WP Document Revisions detects either one and hands workflow states over to it.

= Will it work with WordPress Multisite? =

Yes. Each site can have its own document library, with its own permissions, or all sites can share one. If you don't network-activate the plugin but still want to set the upload folder for the whole network, use the [network administration plugin](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/blob/master/network-admin.php) from the Code Cookbook.

= Will it work over HTTPS? =

Yes. Follow WordPress's [HTTPS instructions](https://developer.wordpress.org/advanced-administration/security/https/).

= Can WP Document Revisions work in my language? =

Yes. WordPress installs the plugin's translations automatically as language packs, which volunteers write on [translate.wordpress.org](https://wp-document-revisions.github.io/wp-document-revisions/https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/). If your language isn't finished, you can help with nothing more than a WordPress.org account. See [Translations](translations/).

= Can I use the block editor (Gutenberg) with documents? =

Yes, if you opt in. Documents use the classic editor by default, because its upload screen is built for documents. To switch, add both the `document_show_in_rest` and `document_use_block_editor` filters in your theme's `functions.php` or a small plugin. See [Block Editor Support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/), including its known limitations.

= Can I bulk import files that are already on my server? =

Yes, with some changes for your setup. Start from the [bulk import script](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/blob/master/bulk-import.php) in the Code Cookbook.

= What happens when I delete the plugin? =

Deleting it from the Plugins screen removes its settings (including notification settings), each user's feed key and the plugin's capabilities from every role. Your documents and their files stay put.

= Where can I find more help? =

The **Help** tab at the top right of the document screens explains each one. The [documentation site](https://wp-document-revisions.github.io/wp-document-revisions/) covers everything else, and the [support forum](https://wordpress.org/support/plugin/wp-document-revisions/) is the place to ask.

= Can I contribute? =

Please do. WP Document Revisions is open source and runs on volunteer contributions of code, documentation, translations and bug reports. See [How to Contribute](https://wp-document-revisions.github.io/wp-document-revisions/CONTRIBUTING/).

= I want to change how something works, but there's no setting for it. What can I do? =

The plugin keeps its settings short and puts the rest behind [filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/) and [actions](https://wp-document-revisions.github.io/wp-document-revisions/actions/), so most changes take a few lines of PHP. The [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) has ready-made examples, such as an audit trail and taxonomy-based permissions.

= How do I list documents in my own theme or plugin code? =

Call `get_documents()`. It takes the same arguments as [`WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/#parameters) and, much like `get_posts()`, returns document posts, limited to the ones the current user can read. `get_permalink()` with a document's ID gives the link that always serves its latest version.

For one document's history, call `get_document_revisions( 100 )`, where 100 is the document's ID. It returns the document's revisions, newest first, and `get_permalink()` with a revision's ID links straight to that version. See [Data Structure](https://wp-document-revisions.github.io/wp-document-revisions/data-structure/) for how documents, revisions and their files are stored.

= What is the Abilities API? =

On WordPress 6.9 and later, the plugin registers four document abilities with the WordPress Abilities API: `check-document-access`, `get-document-info`, `get-document-revisions` and `override-document-lock`. REST clients, AI agents and the WordPress command palette can discover and use them.

When the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) is installed, the three read-only abilities are also available to AI agents over the [Model Context Protocol](https://modelcontextprotocol.io/), with no separate MCP server to run. Every request goes through the same per-document permission checks, so an agent sees only the documents its user can read. `override-document-lock` changes data, so it's left out of MCP on purpose.


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

    git clone https://github.com/wp-document-revisions/wp-document-revisions.git
    cd wp-document-revisions
    composer install --no-dev

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


== Screenshots ==

1. Every document keeps a complete, audited revision history — who changed what and when, each with a summary and one-click restore.
2. Manage all your documents in one place — see each document's owner, workflow state, and who currently has it checked out, and filter by state or owner.
3. Model your team's review process with fully customizable workflow states.


== Changelog ==

Numbers in brackets show the issue number in https://github.com/wp-document-revisions/wp-document-revisions/issues/

= 5.8.3 =

* Activating the plugin for the first time no longer shows a false "You do not have the edit_documents capability" warning. On WordPress Playground, including the Live Preview on WordPress.org, the plugin activates on every visit, so every session showed it. (#887, #890) Thanks to [@NeilWJames](https://github.com/NeilWJames) for reporting it.
* When the server can't read a document's file, for example after a PHP upgrade changes the account PHP runs as, opening the document now shows a message explaining that the file isn't readable, instead of a blank error page. (#885, #889)
* The plugin no longer bundles its Norwegian (Bokmål), Polish and Turkish translations. WordPress now installs these from translate.wordpress.org language packs, so nothing changes on sites using those languages. (#894)
* Code examples on the WordPress.org plugin page now display as code. (#901)

= 5.8.2 =

* Security: WordPress's embed lookup (oEmbed) gave out the stored file name of a document's files. Document files sit in the uploads folder by default, so on sites that don't block that folder, anyone could use the name to download the file of a password-protected document, or any earlier version of a published one. The embed lookup now refuses document files, and visitors who can't edit a document see the document's title instead of a file's stored name ([GHSA-5452-pc3q-85f6](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-5452-pc3q-85f6)).
* Security: on sites that turn on documents in the REST API with the `document_show_in_rest` filter (off by default), a Contributor could make another user's document go back to serving one of its older files the next time that document was saved. If a document is serving an old file, upload the current file again ([GHSA-c47p-xwvp-x5rm](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-c47p-xwvp-x5rm)).
* Security: REST term counts for document taxonomies included draft, pending and private documents, so anyone could see how many non-public documents carry each workflow state or custom document taxonomy term. They now count only published documents, unless the user can already see every document ([GHSA-vp9c-qx2m-x9r8](https://github.com/wp-document-revisions/wp-document-revisions/security/advisories/GHSA-vp9c-qx2m-x9r8)). Thanks to Andrew Melek for reporting it.
* A document URL with a malformed `revision` value, such as `?revision=abc`, now gets the normal not-found response instead of a server error. (#874) Thanks to Andrew Melek for reporting it.
* The plugin now stops sending uploads to the document folder in the same place it marks a document upload as finished, so that redirect can't stay on for the rest of the request when its file-name step is skipped. (#862)

= 5.8.1 =

* Uploading a new version of a document now replaces the document's file in a few rare cases where it didn't, such as an image or audio file with an embedded title, or a site using the `document_internal_filename` filter. Before, the upload appeared to succeed, but clicking Update put the document back on its old file. If a document got stuck on its old file, upload the new version again. (#858) Thanks to [@cvladan](https://profiles.wordpress.org/cvladan/) for reporting it.

For complete changelog, see [GitHub](https://wp-document-revisions.github.io/wp-document-revisions/changelog/)
