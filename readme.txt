=== WP Document Revisions ===

Contributors: benbalter, nwjames
Tags: documents, document management, version control, collaboration, revisions
Requires at least: 4.9
Tested up to: 6.9
Stable tag: 3.8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.

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
- **[Plugin Actions](https://wp-document-revisions.github.io/wp-document-revisions/actions/)** - Available WordPress actions
- **[Plugin Filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/)** - Available WordPress filters
- **[Plugin Shortcodes and Widget](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/)** - Display documents on your site
- **[Useful Plugins and Tools](https://wp-document-revisions.github.io/wp-document-revisions/useful-plugins-and-tools/)** - Extend functionality
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


=== Security Policy ===

To report a security vulnerability, please email [ben@balter.com](mailto:ben@balter.com).


== Where to get help or report an issue ==

- For getting started and general documentation, please browse, and feel free to contribute to [the project documentation](https://wp-document-revisions.github.io/wp-document-revisions/).
- For support questions ("How do I", "I can't seem to", etc.) please search and if not already answered, open a thread in the [Support Forums](https://wordpress.org/support/plugin/wp-document-revisions).
- For technical issues (e.g., to submit a bug or feature request) please search and if not already filed, [open an issue on GitHub](https://github.com/wp-document-revisions/wp-document-revisions/issues).
- For implementation, and all general questions ("Is it possible to..", "Has anyone..."), please search, and if not already answered, post a topic to the [general discussion list serve](https://groups.google.com/forum/#!forum/wp-document-revisions)

== Things to check before reporting an issue ==

- Are you using the latest version of WordPress?
- Are you using the latest version of the plugin?
- Does the problem occur even when you deactivate all plugins and use the default theme?
- Have you tried deactivating and reactivating the plugin?
- Has your issue [already been reported](https://github.com/wp-document-revisions/wp-document-revisions/issues)?

== What to include in an issue ==

- What steps can another user take to recreate the issue?
- What is the expected outcome of that action?
- What is the actual outcome of that action?
- Are there any screenshots or screencasts that may be helpful to include?
- Only include one bug per issue. If you have discovered two bugs, please file two issues.


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

- **WordPress:** 4.9 or higher
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
- [PublishPress Revisions](https://wordpress.org/plugins/publishpress-revisions/)


== Changelog ==

Numbers in brackets show the issue number in https://github.com/wp-document-revisions/wp-document-revisions/issues/

= 3.8.1 =

Update README.

= 3.8.0 =

== Security ==

* Fix CVE-2025-68585: Add missing authorization check to update_post_slug_field by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/429

== Bug fixes ==

* Address Link Date field issue #389 by @NeilWJames in https://github.com/wp-document-revisions/wp-document-revisions/pull/390
* Address #414 - Bug on upload, Cannot read properties of undefined by @NeilWJames in https://github.com/wp-document-revisions/wp-document-revisions/pull/417

== Developer fixes ==

* Fix PHPDoc tags: Replace non-standard @returns with @return by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/394
* Improve test suite with better assertions, edge cases, and utility coverage by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/392
* Optimize performance: reduce database queries and regex operations by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/402
* Fix: Regenerate minified JS files and prevent Prettier from formatting them by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/404
* Add comprehensive front-end JavaScript test suite by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/410
* Small corrections by @NeilWJames in https://github.com/wp-document-revisions/wp-document-revisions/pull/408
* Replace PHPUnit string assertions with strpos-based alternatives for compatibility by @Copilot in https://github.com/wp-document-revisions/wp-document-revisions/pull/434

**Full Changelog**: https://github.com/wp-document-revisions/wp-document-revisions/compare/3.7.2...v3.8.0

= 3.7.2 =

For complete changelog, see [GitHub](https://wp-document-revisions.github.io/wp-document-revisions/changelog/)
