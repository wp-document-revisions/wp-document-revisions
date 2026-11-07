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

= I'm a user/developer/administrator... can I contribute? =

Of course. Please! WP Document Revisions is an open source project and is supported by the efforts of an entire community. We'd love for you to get involved. Whatever your level of skill or however much time you can give, your contribution is greatly appreciated. Check out the "[How to Contribute" page](https://github.com/wp-document-revisions/wp-document-revisions/wiki/How-to-Contribute) for more information.

= Does it work on Mac? PC? Mobile? =

WP Document Revisions should work on just about any system with a browser. You can easily collaborate between, Mac, PC, and even Linux systems. Mobile browsers, such as iOS or Android should be able to download files, but may not be able to upload new versions in all cases.

= What are the different levels of visibility? =

Each document can have one of three "visibilities":

- Private - visible only to logged in users (this can be further refined either based on users or based on the document's status)
- Password Protected - Non-logged in users can view files, but they will require a document-specific password
- Public - Anyone with the document's URL can download and view the file

= How many people can access a document at a time? =

A virtually unlimited number of people can _view_ a document at the same time, but only one user can _edit_ a document at a time.

= While a file is "checked out" can others view it? What about a previous versions? =

Yes.

= Is there a time limit for checking out a file? =

No. So long as the user remains on the document page (it's okay if the window is minimized, etc.), the user will retain the file lock. By default, administrators can override this lock at any time. The origin lock-holder will receive a notification.

= Does it keep track of each individual's changes? =

Yes and no. It will track who uploaded each version of the file, and will provide an opportunity to describe those changes. For more granular history, the plugin is designed to work with a format's unique history features, such as tracked changes in Microsoft Word.

= How do permissions work? =

There are default permissions (based off the default post permissions), but they can be overridden either with third-party plugins such as the [Members plugin](https://wordpress.org/plugins/members/), or for developers, via the <code>document_caps</code> filter.

= What types of documents can my team collaborate on? =

In short, any. By default, WordPress accepts [most common file types](http://en.support.wordpress.com/accepted-filetypes/), but this can easily by modified to accept just about any file type. In WordPress multisite, the allowed file types are set on the Network Admin page. In non-multisite installs, you can simply install a 3d party plugin to do the same. The only other limitation may be maximum file size, which can be modified in your php.ini file or directly in wp-config.php

= Are the documents I upload secure? =

WP Document Revisions was built from the ground up with security in mind. Each request for a file is run through WordPress's time-tested and proven authentication system (the same system that prevents private or un-published posts from being viewed) and documents filenames are hashed upon upload, thus preventing them from being accessed directly. For additional security, you can move the document upload folder above the web root, (via settings->media->document upload folder). Because WP Document Revisions relies on a custom capability, user permissions can be further refined to prevent certain user roles from accessing certain documents.

= Is there any additional documentation? =

In the top right corner of the edit document screen (where you upload the document or make other changes) and on the document list (where you can search or sort documents), there is a small menu labeled "help". Both should provide some contextual guidance. Additional information may be available on the [WP Document Revisions page](http://ben.balter.com/2011/08/29/document-management-version-control-for-wordpress/).

= What happens if I lose internet connectivity while I have a file checked out? =

WP Document Revisions will "ping" the server every minute to let it know that you have the file open. If for some reason you lose connectivity, the server will give you roughly a two minute grace period before it lifts the file lock. If it's brief (e.g., WiFi disconnected), you should be fine, but if it's for an extended period of time (e.g., a flight), you may find that someone else has checked the file out. You do not need to re-download the file (if no one else has modified it), simply remain on the document page to maintain the file lock.

= How do I show documents on the front end of my site? =

Add the Document Library block, or the `[document_library]` shortcode, to any post or page. It lists documents as a list, a table or a grid of cards, shows only the documents each visitor can read, and lets you pick which details appear, such as workflow state, author and revision count. The plugin also has blocks and shortcodes for a document's revisions and for recently revised documents. See [Shortcodes and blocks](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/).

You can also link straight to any public document. Its permalink always serves the latest revision. Because documents are WordPress posts, a list of the documents each user can see is at yourdomain.com/documents/, and many built-in WordPress features, such as search and archives, work with them.

For a fully custom front end, developers can use the `get_documents()` and `get_document_revisions()` functions and the plugin's [actions](https://wp-document-revisions.github.io/wp-document-revisions/actions/) and [filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/).

= Can WP Document Revisions work in my language? =

Yes! WP Document Revisions is fully internationalized, and WordPress installs its translations automatically as language packs. Translations are contributed by volunteers on [translate.wordpress.org](https://wp-document-revisions.github.io/wp-document-revisions/https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/). If your language isn't complete yet, you can help: see [Translations](./translations/) for how to review and suggest translations. It only takes a WordPress.org account.

= Will it work with WordPress Multisite? =

Yes! Each site can have its own document repository (with the ability to give users different permissions on each repository), or you can create one shared document repository across all sites.

= Will it work over HTTPS (SSL)? =

Yes. Just follow the [standard WordPress SSL instructions](https://wordpress.org/support/article/administration-over-ssl/).

= Can I tag my documents? What about categories or some other grouping? =

Yes. You can use the [Simple Taxonomy Refreshed plugin](https://wordpress.org/plugins/simple-taxonomy-refreshed/) to add taxonomies, or can share your existing taxonomies (e.g., the ones you use for posts) with documents.

= Can I put my documents in folders? =

WP Document Revisions doesn't use the traditional folder metaphor to organize files. Instead, the same document can be described multiple ways, or in folder terms, be in multiple folders at once. This gives you more control over your documents and how they are organized. You can add a folder taxonomy with the [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/) plugin. Just add the taxonomy with a post type of "Documents", and as the "Hierarchical" set to True.

Since a document can have many categories assigned at the same time, this is logically equivalent to being in many folders simultaneously.

= What if I want even more control over my workflow? =

Take a look at the [Edit Flow Plugin](https://wordpress.org/plugins/edit-flow/) which allows you to set up notifications based on roles, in-line comments, assign all sorts of metadata to posts, create a team calendar, budget, etc. WP Document Revisions will detect if [Edit Flow](http://ben.balter.com/2011/10/24/advanced-workflow-management-tools-for-wp-document-revisions/) is installed and activated, and will adapt accordingly (removing the workflow-state dialogs, registering documents with Edit Flow, etc.). If you're looking for even more control over your team's work flow, using the two plugins in conjunction is the way to go.

Equally the [PublishPress Plugin](https://publishpress.com), a fork of Edit Flow, is detected and can be used with WP Document Revisions in exactly the same manner as Edit Flow.

= I want some small changes to the processing, but there are few configuration options. How do I do this? =

Yes, there are few Settings. However there are many filters that allows processing to be configured to your requirement. These are described [here](https://wp-document-revisions.github.io/wp-document-revisions/filters/). This will need some coding to be done.

= Can I make it so that users can only access documents assigned to them (or documents that they create)? =

Yes. Each document has an "owner" which can be changed from a dialog on the edit-document screen at the time you create it, or later in the process (by default, the document owner is the person that creates it). If the document is marked as private, only users with the read_private_documents capability can access it. Out of the box, this is set to Authors and below, but you can customize things via the [Members plugin](https://wordpress.org/plugins/members/) (head over to roles after installing).

= How do I use the documents shortcode? =

In a post or page, simply type `[documents]` to display a list of documents.
More information is on [this](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/) page.

= How do I use the document revisions shortcode? =

In a post or page, simply type `[document_revisions id="100"]` where ID is the ID of the document for which you would like to list revisions.
More information is on [this](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/) page.

= How do I use the recently revised documents widget? =

Go to your theme's widgets page (if your theme supports widgets), and drag the widget to a sidebar of you choice. Once in a sidebar, you will be presented with options to customize the widget's functionality.

= How do I use the `get_documents` function in my theme or plugin? =

Simply call `get_documents()`. Get documents accepts an array of [Standard WP_Query parameters](https://developer.wordpress.org/reference/classes/wp_query/#parameters) as an argument. Use it as you would get_posts. It returns an array of document objects. The `post_content` of each document object is the attachment ID of the revision. `get_permalink()` with that document's ID will also get the proper document permalink (e.g., to link to the document).

= How do I use the `get_document_revisions` function in my theme or plugin? =

Simply call `get_document_revisions( 100 )` where 100 represents the ID of the document you'd like to query. The function returns an array of revisions objects. Each revisions's `post_content` represents the ID of that revisions attachment object. `get_permalink()` should work with that revision's ID to get the revision permalink (e.g., to link to the revision directly).

= Can I set the upload directory on multisite installs if I don't want to network activate the plugin? =

Yes. There's a plugin in the [WP Document Revisions Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) to help with that. Just install and network activate.

= Can I limit access to documents based on workflow state, department, or some other custom taxonomy? =

Yes. Download (and optionally customize) the [taxonomy permissions](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/blob/master/taxonomy-permissions.php) plugin from the [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook). Out of the box, it will register a "departments" taxonomy (which can be easily changed at the top of the file, if you want to limit access by a different taxonomy), and will create additional permissions based on that taxonomy's terms using WordPress's built-in capabilities system. So for example, instead simply looking at `edit_document` to determine permissions, it will also look at `edit_document_in_marketing`, for example. You can create additional roles and assign capabilities using a plugin like [Members](https://wordpress.org/plugins/members).

= Is it possible to do a bulk import of existing documents / files already on the server? =

Yes. It will need to be slightly customized to meet your needs, but take a look at the [Bulk Import Script](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/blob/master/bulk-import.php) in the code cookbook.

= Can I use the block editor (Gutenberg) with documents? =

Yes. Block editor support is opt-in — by default, documents use the classic editor, which provides a streamlined, purpose-built upload interface. To enable the block editor, add both the `document_show_in_rest` and `document_use_block_editor` filters to your theme's `functions.php` or a custom plugin. See the [Block Editor Support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/) page for full details, including known limitations.

= What happens when I delete (uninstall) the plugin? =

WP Document Revisions includes an `uninstall.php` that runs when the plugin is deleted from the WordPress admin. It removes plugin options (`document_upload_directory`, `document_slug`, `document_link_date`, `wpdr_db_version`), user meta (feed keys), and all 13 custom document capabilities from every role. Your uploaded document files and document posts are **not** deleted — only the plugin's settings and capabilities are cleaned up.

= What is the Abilities API? =

Starting with WordPress 6.9, the plugin registers 4 document abilities (`check-document-access`, `get-document-info`, `get-document-revisions`, `override-document-lock`) via the WordPress Abilities API. These make document operations discoverable by REST clients, AI agents, and the WordPress command palette. The abilities are feature-gated and only registered when WordPress 6.9+ is detected.

The three read-only abilities (`check-document-access`, `get-document-info`, `get-document-revisions`) are also exposed to AI agents over the [Model Context Protocol](https://modelcontextprotocol.io/) when the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) is installed — no separate MCP server is required. Every request still runs through the same per-document permission checks (`read_document`, `read_document_revisions`), so agents only ever see documents the authenticated user is allowed to read. The `override-document-lock` ability changes state and is deliberately **not** exposed over MCP, keeping the agent-facing surface read-only.


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
