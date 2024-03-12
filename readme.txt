=== WP Document Revisions ===

Contributors: benbalter, nwjames
Tags: documents, uploads, attachments, document management, enterprise, version control, revisions, collaboration, journalism, government, files, revision log, document management, intranet, digital asset management
Requires at least: 4.6
Tested up to: 6.4.2
Stable tag: 3.6.0

== Description ==

[WP Document Revisions](https://wordpress.org/plugins/wp-document-revisions/) is a [document management](https://en.wikipedia.org/wiki/Document_management_system) and [version control](http://en.wikipedia.org/wiki/Revision_control) plugin. Built for time-sensitive and mission-critical projects, teams can collaboratively edit files of any format -- text documents, spreadsheets, images, sheet music... anything -- all the while, seamlessly tracking the document's progress as it moves through your organization's existing workflow.

= WP Document Revisions is three things =

1. A **document management system** (DMS), to track, store, and organize files of any format
2. A **collaboration tool** to empower teams to collaboratively draft, edit, and refine documents
3. A **file hosting solution** to publish and securely deliver files to a team, to clients, or to the public

See [**the full list of features**](https://wp-document-revisions.github.io/wp-document-revisions/features/) for more information.

== Documentation ==

See [the full documentation](https://wp-document-revisions.github.io/wp-document-revisions)

= Learn =

* [Features and Overview](https://wp-document-revisions.github.io/wp-document-revisions/features/)
* [Screenshots](https://wp-document-revisions.github.io/wp-document-revisions/screenshots/)
* [Installation](https://wp-document-revisions.github.io/wp-document-revisions/installation/)
* [Frequently Asked Questions](https://wp-document-revisions.github.io/wp-document-revisions/frequently-asked-questions/)
* [Links](https://wp-document-revisions.github.io/wp-document-revisions/links/)
* [Where to get Support or Report an Issue](https://wp-document-revisions.github.io/wp-document-revisions/SUPPORT/)
* [Translations](https://wp-document-revisions.github.io/wp-document-revisions/translations/)
* [Plugin Actions](https://wp-document-revisions.github.io/wp-document-revisions/actions/)
* [Plugin Filters](https://wp-document-revisions.github.io/wp-document-revisions/filters/)
* [Plugin Shortcodes and Widget](https://wp-document-revisions.github.io/wp-document-revisions/shortcodes/)
* [Useful Plugins and Tools](https://wp-document-revisions.github.io/wp-document-revisions/useful-plugins-and-tools/)

= Get Involved =

* [How to Contribute](https://wp-document-revisions.github.io/wp-document-revisions/CONTRIBUTING/)
* [Join the List Serve](https://groups.google.com/forum/#!forum/wp-document-revisions)


== Features ==

<iframe width="560" height="315" src="https://www.youtube.com/embed/VpsTNSiJKis" frameborder="0" allowfullscreen></iframe>

= Overview =

**Powerful Collaboration Tools** - *With great power does not have to come great complexity.* Based on a simple philosophy of putting powerful but intuitive tools in the hands of managers and content creators, WP Document Revisions leverages many of the essential WordPress features that, for more than eight years, have been tested and proven across countless industries — posts, attachments, revisions, taxonomies, authentication, and permalinks — to make collaborating on the creation and publication of documents a natural endeavor. Think of it as an [open-source and more intuitive version](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/) of the popular Microsoft collaboration suite, [Sharepoint.](http://sharepoint.microsoft.com/en-us/Pages/default.aspx)

**Document History** - At each step of the authoring process, WP Document Revisions gives you an instant snapshot of your team's progress and the document's history. It even gives you the option to revert back to a previous revision — so don't fret if you make a mistake — or receive updates on changes to the document right in your favorite feed reader.

**Access Control** - Each document is given a persistent URL (e.g., yourcompany.com/documents/2011/08/TPS-Report.doc) which can be private (securely delivered only to members of your organization), password protected (available only to those you select such as clients or contractors), or public (published and hosted for the world to see). If you catch a typo and upload a new version, that URL will continue to point to the latest version, regardless of how many changes you make.

**Enterprise Security** - Worried about storing propriety or sensitive information? WP Document Revisions was built from the first line of code with government- and enterprise-grade security in mind. Each file is masked behind an anonymous 128-bit [MD5 hash](http://en.wikipedia.org/wiki/MD5) as soon as it touches the server, and requests for files are transparently routed through WordPress's time-tested URL rewriting, authentication, and permission systems (which can even [integrate with existing enterprise active directory](http://wordpress.org/extend/plugins/active-directory-integration/) or [LDAP servers](http://wordpress.org/extend/plugins/simple-ldap-login/)). Need more security? WP Document Revisions allows you to store documents in a folder above the `htdocs` or `public_html` [web root](http://httpd.apache.org/docs/2.0/mod/core.html#documentroot), further ensuring that only those you authorize have access to your work.

**Customization** - WP Document Revisions recognizes that no two teams are identical, and as a result, molds to your firm's needs, not the other way around. Need to track additional information associated with a document? Departments, editors, issues, sections, even arbitrary key-value pairs — whatever you can throw at it, it can handle. Development and customization costs are further minimized by its extensive plugin API, and the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/) makes it easy for even the uninitiated to add custom taxonomies to documents. Need an audit trail to track check-ins and check-outs? User-level permissions based on the document's state or another custom taxonomy? Support for third-party encryption? Check out the [WP Document Revisions Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) for sample code. Looking for even more advanced control of your workflow? WP Document Revisions will detect the popular workflow plugin [Edit Flow](http://editflow.org), if installed, and will automatically pull [Edit Flow’s advanced workflow management tools](http://ben.balter.com/2011/10/24/advanced-workflow-management-tools-for-wp-document-revisions/) into WP Document Revisions. Simply put, virtually every aspect of the plugin's functionality from workflow states to user-level permissions can be fully customized to your team's unique needs.

**Future Proof** - Switching costs a concern? WP Document Revisions is built with tomorrow's uncertainty in mind. Equally at home in an in-house server room as it is in the cloud, moving individual files or entire document repositories in and out of WP Document Revisions is a breeze (history and all). And since the software is open-source, you can easily add tools to automate the process of moving to or integrating with future third-party systems.

= Features =

* Support for any file type (docs, spreadsheets, images, PDFs — anything!)
* Securely stores unlimited revisions of your business's essential files
* Provides a full file history in the form of a revision log, accessible via RSS
* Helps you track and organize documents as they move through your organization's existing workflow
* Each file gets a permanent, authenticated URL that always points to the latest version
* Each revision gets its own unique url (e.g.,TPS-Report-revision-3.doc) accessible only to those you deem
* Files are intuitively checked out and locked to prevent revisions from colliding
* Toggle documents between public, private, and password protected with a single mouse click
* Runs in-house or in the cloud
* Secure: filenames are hashed on upload and files are only accessible through WordPress's proven authentication system
* Can move document upload folder to location outside of web root to further ensure government- and enterprise-grade security
* Documents and Revisions shortcodes, Recently Revised Documents widget
* Multisite and Windows (XAMPP) support
* French and Spanish language support (easily translated to your language)
* Integration with [Edit Flow](https://editflow.org)
* Recently Revised Documents Widget, shortcodes, and templating functions for front-end integration

= Features Available via the [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) =

* **Audit Trail** - creates check in / check out audit trail for all documents
* **Taxonomy-based Permissions** - allows setting user-level permissions based on a custom taxonomy such as department
* **Third Party Encryption** - example of how to integrate at rest encryption using third-party tools
* **Rename Documents** - changes all references to "Documents" in the interface to any label of your choosing
* **State Change Notification** - how to use document api to allow users to receive notification whenever documents change workflow state
* **Bulk Import** - how to batch import a directory (or other list) of files as documents
* **Filetype Taxonomy** - Adds support to filter by filetype
* **Track Changes** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility
* **Change Tracker** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility


=== Security Policy ===

To report a security vulnerability, please email [ben@balter.com](mailto:ben@balter.com).


== Where to get help or report an issue ==

* For getting started and general documentation, please browse, and feel free to contribute to [the project documentation](https://wp-document-revisions.github.io/wp-document-revisions/).
* For support questions ("How do I", "I can't seem to", etc.) please search and if not already answered, open a thread in the [Support Forums](https://wordpress.org/support/plugin/wp-document-revisions).
* For technical issues (e.g., to submit a bug or feature request) please search and if not already filed, [open an issue on GitHub](https://github.com/wp-document-revisions/wp-document-revisions/issues).
* For implementation, and all general questions ("Is it possible to..", "Has anyone..."), please search, and if not already answered, post a topic to the [general discussion list serve](https://groups.google.com/forum/#!forum/wp-document-revisions)

== Things to check before reporting an issue ==

* Are you using the latest version of WordPress?
* Are you using the latest version of the plugin?
* Does the problem occur even when you deactivate all plugins and use the default theme?
* Have you tried deactivating and reactivating the plugin?
* Has your issue [already been reported](https://github.com/wp-document-revisions/wp-document-revisions/issues)?

== What to include in an issue ==

* What steps can another user take to recreate the issue?
* What is the expected outcome of that action?
* What is the actual outcome of that action?
* Are there any screenshots or screencasts that may be helpful to include?
* Only include one bug per issue. If you have discovered two bugs, please file two issues.


=== WP-Documents-Revisions Action Hooks ===

This plugin makes use of many action hooks to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones.

== Action change_document_workflow_state ==

Called when the post is saved and Workflow_State taxonomy value is changed. (Only post_ID and new value are available)

In: class-wp-document-revisions-admin.php

== Action document_change_workflow_state ==

Called when the post is saved and Workflow_State taxonomy value is changed. (post_ID, new and old value are available)

In: class-wp-document-revisions-admin.php

== Action document_edit ==

Called as part of the Workflow_State taxonomy when putting the metabox on the admin page

In: class-wp-document-revisions-admin.php

== Action document_lock_notice ==

Called when putting the lock notice on the admin edit screen.

In: class-wp-document-revisions-admin.php

== Action document_lock_override ==

Called after trying to over-ride the lock and possibly a notice has been sent.

In: class-wp-document-revisions.php

== Action document_saved ==

Called when a document has been saved and all plugin processing done.

In: class-wp-document-revisions-admin.php

== Action document_serve_done ==

Called just after serving the file to the user.

In: class-wp-document-revisions.php

== Action serve_document ==

Called just before serving the file to the user.

In: class-wp-document-revisions.php



== Changelog ==

Numbers in brackets show the issue number in https://github.com/wp-document-revisions/wp-document-revisions/issues/

= 3.6.0 =

* NEW: Integrate with PublishPress Statuses plugin for custom statuses. (#335)
* NEW: Accessibility rules states that links to PDF documents should have visible references. Blocks have an explicit switch. (#322)
* NEW: User pulldowns will show only relevant users. (#321)
* NEW: Filter 'document_post_thumbnail' used to define the post-thumbnail image size (if not set by theme). (#339)
* NEW: Filter 'document_use_wp_filesystem' used to serve document (instead of PHP readfile). Irrelevant if the file is compressed on output. (#320)
* NEW: Filter 'document_internal_filename' for updating internal file name additionally passed the original name. (#319)
* NEW: Filter 'document_validate_md5' to switch off attachment MD5 format validation. (#318)
* NEW: Optionally stop direct web access to document files to force access only via WordPress. (#317)
* NEW: If a role already has "read_documents" capability, do not touch capabilities on plugin reactivation. (#315)
* NEW: Filter 'document_home_url' to allow changes to be made to it (used with WPML). (#329)
* FIX: Ensure File descriptor of Document Upload includes subdir component. (#342)
* FIX: Use with plugin EditFlow gives PHP 8.0 error. (#331)
* FIX: Typo in description of default upload location. (#328)	
* FIX: Filter 'document_revisions_owner' withdrawn as parameter acted on (who) deprecated in WP 5.9. (#316)
* FIX: Updates to document description do not enable the Submit button
* DEV: JS scripts will be called with Defer in WP 6.3 onwards. (#314)
* DEV: Review for WP Coding standard 3.0 (#313)

= 3.5.0 =

* SECURITY: Rest media interface may expose document name. 
* NEW: Site can decide to save permalinks without year/month part.
* NEW: Permalinks may be updated on the documents screen.
* FIX: guid field for documents was generally incorrect. Will be stored as a valid value.
* FIX: Upload directory processing reviewed and simplified.
* FIX: Document permalink month can be incorrect when saved at month end. (#300).
* FIX: Valid document may not be found.
" FIX: Improve notification process when activation user does not have edit_documents capability.

= 3.4.0 =

* SECURITY: WordPress can create images for PDF documents which if used would leak the hidden document name so image name changed.
* NEW: An action 'document_saved' is provided for processing after a document has been saved or updated and all plugin processing complete. (#278)
* NEW: A filter 'document_serve_attachment' is provided to review the attachment id being served. Return false to stop display. (#278)
* NEW: A filter 'document_show_in_rest' is provided to display document data via the REST interface using document permissions. {#258, #259)
* NEW: A tool is provided to validate the internal structure of all documents that the user can edit. If fixable then a button is displayed to fix it. (#260)
* NEW: A user-oriented description may be entered for each document. This can be displayed with the Documents List shortcode and Latest Documents widget or their block equivalents. (#263)
* NEW: These blocks can also display the featured image or generated image for PDF documents. (#264)
* NEW: Blocks extended to support standard Colour and Fontsize attributes. (#264}
* NEW: Revisions can be merged if made within a user-defined interval using filter 'document_revisions_merge_revisions' (Default 0 = No merging). (#263)
* FIX: jQuery ready verb usage removed. (#262}
* FIX: Caching strategy reviewed to ensure updates delivered to users. (#261}
* FIX: Blocks used incorrect, but previously tolerated, parameter for RadioControls rendering them difficult to use.
* FIX: Blocks are categorised within the Editor differently with 5.8

= 3.3.1 =

* FIX: Content-Length header suppressed for HTTP/2 File Serve. {#254)
* FIX: MOD_DEFLATE modifies etag, so no caching occurred in this case.
* FIX: Gzip process invoked for encodings gzip, x-gzip and deflate.

= 3.3.0 =

* SECURITY: Password-protected document can leak existence (by showing next/previous)
* SECURITY: Queries on post_status do not do proper permissions check
* SECURITY: Suppress excerpt output in feeds to stop information leakage
* SECURITY: WP creates images when saving PDF documents (using the encoded name). These were being left when deleting the document.
* NEW: Rewrite rules extended to access documents without year/month and/or file extension.  (#253) @NeilWJames
* NEW: Use standard WP process for Taxonomy workflow_state on Document Admin List. Note that it will change the column order seen as taxonomiees are on the end.
* NEW: Implement Gutenberg Blocks for Shortcodes and Widget.
* NEW: Integrate with either Edit-flow or PublishPress plugins
* NEW: Taxonomy workflow_state is set as show_in_rest.
* NEW: Add action 'document_serve_done' which can be use to delete decrypted files (needed for encrypted at rest files)
* NEW: Add filter 'document_buffer_size' to define file writing buffer size (Default 0 = No buffering).
* NEW: Add filter 'document_output_sent_is_ok' to serve file even if output already written.
* NEW: Add filter 'document_read_uses_read' to use read_document capability (and not read) to read documents
* NEW: Add filter 'document_serve_use_gzip' to determine if gzip should be used to serve file (subject to browser negotiation).
* NEW: Add filter 'document_serve' to filter the file to be served (needed for encrypted at rest files)
* NEW: New Crowdin updates (#244, #245)
* FIX: Access to revisions when permalink structure not defined.
* FIX: Design conflict with Elementor (#230) @NeilWJames
* FIX: Document directory incorrect test for Absolute/Relative entry on Windows implementations
* FIX: Document Taxonomies using default term counts will use same method as WORKFLOW_STATE, i.e. count all not-trashed documents 
* FIX: Ensure the action point to detect change in workflow_state worked (for CookBook functionality).
* FIX: Fix error in time difference display when client and server are in different time zones
* FIX: Fix WP 5.7 Breaking change (#38843) for Term Counts.  (#250) @NeilWJames
* FIX: Remove existing workaround for WP bug 16215 and long time fixed - and made information incorrect
* FIX: Remove restore option on the current document and latest revision as it makes no sense.
* FIX: Review document serving process to try to identify where other plugins could output text and corrupt file download
* FIX: Review documentation. (#208) @NeilWJames
* FIX: Review of Rewrite rules with/without trailing slash; also extend file extension length
* FIX: Testing of blocks showed that if document taxonomies are changed, then existing blocks may not work. Some changes are now handled. (#217) @NeilWJames
* FIX: Fixing compatibility issue with double slash in Documents URL when using WPML (#218) @BobbyKarabinakis
* DEV: Update code to WP Coding Standards 2.2.1 (and fix new sniff errors)
* DEV: Update coveralls to 2.2, dealerdirect/codesniffer to 0.6, phpunit/phpunit to 8.5 and wp/cli to 2.4.1
* DEV: Rewrite Test library to increase code coverage.
* DEV: Use GitHub Actions for CI (#251)
* DEV: Fixed wp_die() tests ending tests prematurely (#252)

= 3.2.4 =

* Address technical debt for WP Document Standards  (#192) @NeilWJames
* On plugin activation, check that the user has edit_documents capability. If not, a warning message will be output that the menu may be incorrect. (#180) @NeilWJames
* PHPCS review (#179) @NeilWJames
* Bump phpunit/phpunit from 8.2.5 to 8.3.4 (#177) @dependabot-preview
* Addresses phpunit and toolset versions and prepare for future release (#174) @NeilWJames
* Version 3.2.2 gives an "property of non-object" at line 1403 on load (#161) @NeilWJames

= 3.2.3 =

* Full phpcs 2.2 standardisation, complete filter documentation (#192) @NeilWJames
* On plugin activation, admin warning if user doesn't have edit_documents capability (#180) @NeilWJames
* PHPCS Review (no functional changes) (#179) @NeilWJames
* Review for WP Coding standard 2.1.1 and newer phpunit (#174) @NeilWJames
* Bump version to V3.2.3 and Tested WP 5.2.2 (#174) @NeilWJames

= 3.2.2 =

* Version 3.2.2 gives an "property of non-object" at line 1403 on load (#161) @NeilWJames
* Add default capabilities only when they are absent. (#146) @NeilWJames
* Fix multi-network (needs WP 4.6) (#143) @geminorum 
* Allow Sites to use WP_POST_REVISIONS for other post types (#140)
* Media Library URL’s change after plugin update (#139) @NeilWJames
* New crowdin translations (#137/#138) @benbalter

= 3.2.1 =

* Fix for $wp_query->query_vars being null (#136) @benbalter
* Media Library URL’s change after plugin update (#139) @NeilWJames
* New Crowdin translations (#137) @benbalter
* New Crowdin translations (#138) @benbalter

= 3.2.0 =

* Enable filter by workflow_state on Admin screen (#121) @NeilWJames
* missing translate on metabox titles (#122) @geminorum
* Addresses #124 (is_feed has doing_it_wrong error) (#125) @NeilWJames
* New Crowdin translations (#120) @benbalter
* Allow HTTP headers to be filtered in serve_file() (#123) @jeremyfelt
* Small fixes in Admin function (#126) @NeilWJames
* Support Featured Images (#131) @NeilWJames
* Error if directory option not present (#132) @NeilWJames
* Create Edit link on document shortcode (#133) @NeilWJames
* Bump version to V3.2 and Tested WP 4.9.8 (#134) @NeilWJames

= 3.1.2 =

Fix for 404 error when serving documents from non-standard upload directory.

= 3.1.1 =

Updated documentation.

= 3.1.0 =

* NEW: Added dashboard widget (#109, props @NeilWJames)
* NEW: Added Finish translation (Props @xcoded)
* NEW: Added Spanish translation (Props @alejnavarro)
* NEW: Added Indonesian translation (Props @barzah and @fajarsdq)
* NEW: Provide a way to filter or skip mime type detection (#106, props @jeremyfelt)
* FIX: Do not escape end list widget HTML in function widget (#99, props @NeilWJames)
* FIX: Only cache revisions output if revisions exist (#101, props @jeremyfelt)
* FIX: Update WPCS and adjust code to meet new standards (#104, props @jeremyfelt)
* FIX: Fix possible data pollution in archive views (#103, props @jeremyfelt)
* FIX: Account for `the_title` filter used with only one arg (#105, props @jeremyfelt)
* FIX: Don't verify posts that don't exist (#107, props @jeremyfelt)
* FIX: Fixed issue with translation files not properly loading (#108, props @NeilWJames)
* FIX: Better multisite support (#113, props @JonasBrand)
* DEV: You can now contribute to the project's translation's via Crowdin: https://crowdin.com/project/wordpress-document-revisions
* DEV: Added Contributor Code of Conduct
* DEV: Updated contributing documentation

= 3.0.1 =

* Fix for calling the wrong escaping function in the widget code.

= 3.0.0 =

* [Dropped support for WordPress prior to version 3.3](https://github.com/wp-document-revisions/wp-document-revisions/pull/94)
* [Dropped WebDav support](https://github.com/wp-document-revisions/wp-document-revisions/pull/95)
* Implemented [WordPress coding standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards)
* Added additional nonces
* Added additional translation strings and comments
* Updated documentation

= 2.2.0 =

* [Add filter to allow opt-out of WebDAV Edit Document](https://github.com/wp-document-revisions/wp-document-revisions/pull/74)
* [Filter documents from the grid view in Media, queried via Ajax](https://github.com/wp-document-revisions/wp-document-revisions/pull/78)
* [Added code to preserve file exts on revision links](https://github.com/wp-document-revisions/wp-document-revisions/pull/81), and
* [Verify a post ID has been passed before verifying post type](https://github.com/wp-document-revisions/wp-document-revisions/pull/86)
* [Use H2 for "Feed Privacy" heading](https://github.com/wp-document-revisions/wp-document-revisions/pull/91)

= 2.0.0 =

* Note: The project is looking for additional contributors. Please consider contributing your time: <https://github.com/wp-document-revisions/wp-document-revisions/blob/master/docs/how-to-contribute.md>
* Added (beta) WebDAV support thanks to @linuxBozo and @poorgeek (<https://github.com/wp-document-revisions/wp-document-revisions/pull/69>)
* Added Brazilian Portuguese translation thanks to @rafaelfunchal
* Significantly improved automated testing via WP-CLI
* Better guarding against binary files being corrupted by other plugins
* Improved documentation (<https://github.com/wp-document-revisions/wp-document-revisions/tree/master/docs>)

= 1.3.6 =

* Fix for workflow state not properly saving under certain circumstances
* Added Italian translation, props @guterboit
* Added Russian translation, props Evgeny Vlasov
* Updated all translations
* Workflow state saving improvements, props @cojennin ([#48](https://github.com/wp-document-revisions/wp-document-revisions/pull/48))
* Fix restore revision link bug, props @cojennin ([#55](https://github.com/wp-document-revisions/wp-document-revisions/issues/55))
* Welcome @cojennin to the core team. [Want to join?](https://github.com/wp-document-revisions/wp-document-revisions/wiki/How-to-Contribute)

= 1.3.5 =

* Added Dutch translation, props @tijscruysen.
* To prevent potential errors, verify `workflow_state` is set before checking for change, props @rachelbaker.
* Added `document_custom_feed` and `document_verify_feed_key` filters to customize feed behavior, props @nodakjones.
* Prevent errors when newly added documents do not have attached files, props @rachelbaker.
* Better compatibility with WordPress 3.5 media uploader
* Significant Javascript improvements to prevent conflicts with other plugins

= 1.3.4 =

* Testing framework no longer distributed with the plugin.
* Added Swedish translation, special thanks to Daniel Kroon, [Examinare AB](http://www.examinare.biz/), Sweden.
* Added Czech translation set, special thanks to Hynek Šťavík.

= 1.3.3 =

* Fix for fatal error (undefined function) when Edit Flow custom post status were enabled, props [Leho Kraav](http://leho.kraav.com/), fixes [#24](https://github.com/wp-document-revisions/wp-document-revisions/issues/24)
* Fix for [testing framework](https://github.com/nb/wordpress-tests) not being properly included in plugin repository due to bad [deploy script](https://github.com/benbalter/Github-to-WordPress-Plugin-Directory-Deployment-Script)
* Added German translation (de_DE), special thanks to [Konstantin Obenland](http://en.wp.obenland.it/)
* Added Chinese translation (zh_CN), special thanks to Tim Ren
* Updated Spanish, French, and Norwegian translations

= 1.3.2 =

* Plugin documentation now maintained in [collaboratively edited wiki](https://github.com/wp-document-revisions/wp-document-revisions/wiki). Feel free to contribute!
* Created listserv to provide a discussion forum for users of and contributors, as well as general annoucements. [Feel free to join!](https://groups.google.com/forum/#!forum/wp-document-revisions)
* Added Norwegian translation, special thanks to Daniel Haugen
* [Crisper menu icon](https://github.com/wp-document-revisions/wp-document-revisions/commit/00ffe42daabacf90091cc3638dd2658c2376f01a), special thanks to [Phil Russell](www.optionotter.com)
* Pushpin icon [replaced with Retina document icon](https://github.com/wp-document-revisions/wp-document-revisions/commit/c36ee849512f77432db8e6783a0bc4389f33f0ab) on document list and document edit screen, special thanks to [Marvin Rühe](https://github.com/Marv51)
* Unit tests now utilizes newer [wordpress-tests](https://github.com/nb/wordpress-tests) framework, as recently adopted by core
* `serve_file` [now hooks](https://github.com/wp-document-revisions/wp-document-revisions/commit/57cac162e40255efb29c354754e9a0b8df05a2ef) into `template_include` filter (rather than `template_single`) to prevent potential conflict with themes/plugins hooking into subsequent filters and producing extranous output after the document is served which would result in corrupting some files
* Fix for `document_to_private` filter [not properly passing](https://github.com/wp-document-revisions/wp-document-revisions/commit/04922d73eb63172e79f2f9e86e4002cee032e4ef) the pre-filtered document object, props [Marvin Rühe](https://github.com/Marv51).
* [Better loading](https://github.com/wp-document-revisions/wp-document-revisions/commit/0349f8ebcf931f6b2731b42ae795b3191ce9ed45) of administrative functions
* [Better toggling](https://github.com/wp-document-revisions/wp-document-revisions/commit/6947310c06a8267573835c6b4bc04e3ad1b29405) of Workflow state support for integration with Edit Flow and other plugins
* Administrative CSS [now stored in a separate file](https://github.com/wp-document-revisions/wp-document-revisions/commit/f61385b674c67adf820c9e240fe3eadb4b1cf3a2) (rather than being injected directly to document head), and [loads via `enqueue_style` API](https://github.com/wp-document-revisions/wp-document-revisions/commit/11a583edfeb307a939e7686fca4712a195ac4059)
* Administrative CSS and Javascript files now versioned based on plugin version to allow for better caching

= 1.3.1 =

* Better permalink support for draft and pending documents
* Whenever possible browser will attempt to display documents in browser, rather than prompting with save as dialog (e.g., PDFs)
* Fix for function `get_file_type()` breaking the global `$post` variable when no document argument is supplied
* Improved Spanish translation with additional strings (special thanks, [elarequi](http://www.labitacoradeltigre.com))

= 1.3 =

* Plugin now includes unit tests to ensure security and stability, and [undergoes extensive testing](http://travis-ci.org/#!/wp-document-revisions/wp-document-revisions) (WordPress 3.2/3.3/Trunk, Multisite/single, PHP 5.3/5.4) via continuous integration service Travis CI prior to release.
* Translations now curated on [collaborative editing platform GlotPress](http://translations.benbalter.com/projects/wp-document-revisions/) if any user would like to submit a translation ([no technical knowledge necessary](http://translations.benbalter.com/projects/how-to-translate))
* If you would like to help out by testing early releases, please try the continuously updated [development version](https://github.com/wp-document-revisions/wp-document-revisions/tree/develop). Any [feedback](https://github.com/wp-document-revisions/wp-document-revisions/issues?direction=desc&sort=created&state=open), technical or prose is helpful.
* Added Spanish Translation Support (es_ES — special thanks to [TradiArt](http://www.tradiart.com/))
* Document URL slug (used for archive and prefixing all documents) now customizable via settings page and translatable. (e.g., <http://domain.com/documentos/2012/04/test.txt> rather than /documents/)
* Subscribers and unauthenticated users no longer have the ability to read revisions by default (you can override this setting using the [Members plugin](http://wordpress.org/extend/plugins/members/).
* Attempts to access unauthorized files now properly respond with HTTP code 403 (rather than 500 previously). Note: attempting to access private documents will continue to result in 404s.
* Enhanced authentication prior to serving files now provides developers more granular control of permissions via `serve_document_auth` filter.
* Better Edit Flow support (can now toggle document support on and off using native Edit Flow user interface). Note: You may need to manually toggle on custom status support for documents after upgrading.
* Default document upload directory now honors WordPress-wide defaults and features enhanced multisite support
* Ability to separate documents on server by site subfolder on multisite installs

= 1.2.4 =

* Better support for custom document upload directories on multisite installs
* Gallery, URL, and Media Library links now hidden from media upload popup when uploading revisions
* Fix for plugin breaking media gallery when filtered by mimetype (MySQL ambiguity error)
* Fix for upload new version button appearing for locked out users in WordPress 3.3
* Fix for upload new version button not appearing after document lock override on WordPress 3.3

= 1.2.3 =

* Owner metabox no longer displays if user does not have the ability to `edit_others_documents`
* Fix for serving documents via SSL to Internet Explorer version 8 and earlier
* GPL License now distributed with plugin
* Code cleanup, minor bug fixes, and additional inline documentation

= 1.2.2 =

* Plugin [posted to Github](https://github.com/wp-document-revisions/wp-document-revisions) if developers would like to fork and contribute
* Documents shortcode now accepts additional parameters. See the FAQ for a full list.
* Performance and scalability improvements to backend; files attached to documents are now excluded from media lists by join statements rather than subqueries
* If plugin is unable to locate requested file on server, standard theme's 404 template is served (rather than serving "404 — file not found" via `wp_die()` previously) and E_USER_NOTICE level error is thrown. Diagnostic information will be available via debug bar (if WP_DEBUG is enabled) or in the standard PHP error log
* `/documents/` now supports pagination
* Support for linking to revisions with ugly permalinks
* Custom post type's `has_archive` property changed to `true` to help with theme compatibility
* Fix for fatal error when user without `read_document_revisions` capability called `wp_get_attachment_url()` on file attached to a revision
* Fix for broken permalink returned when get_permalink is called multiple times on the same document revision
* Fix for wp_get_attachment_image_src returning broken URLs or the direct path to the document
* Fix for "`Call-time pass-by-reference has been deprecated`" error when running certain versions of PHP
* General code cleanup

= 1.2.1 =

* French translation (Special thanks to [Hubert CAMPAN](http://omnimaki.com/))
* Enhanced support for running on WAMP systems (XAMPP, etc.)
* Improved integration with WordPress 3.3's new upload handler
* Significant performance improvements to `verify_post_type()` method
* Document requests no longer canonically 301 redirect with a trailing slash
* Fix for wp_get_attachment_url returning the attachment URL, rather than the document permalink when called directly
* Menu item now reads "All Documents" (rather than simply "Documents") for clarity
* Fix for E_WARNING level error on edit-tags.php with custom taxonomies
* Taxonomy counts (e.g., workflow states) now reflects non-published documents
* Better translation support (see the [FAQ](http://wordpress.org/extend/plugins/wp-document-revisions/faq/) if you are interested in translating the plugin into your language)
* Compatibility fix for WordPress SEO's "Clean Permalinks" mode

= 1.2 =

* Added shortcode to display list of documents meeting specified criteria
* Added shortcode to display a document's revisions (formerly in code cookbook)
* Added widget to display recently revised documents (formerly in code cookbook)
* Created new global `get_documents()` and `get_document_revisions()` functions to help build and customize themes and plugins
* Added filter to `wp_get_attachment_url` to force document/revision urls when attachments are queried directly
* Better organization of plugin files within plugin folder
* Fixed bug where revision summary would not display under certain circumstances

= 1.1 =

* Added support for the [Edit Flow Plugin](http://wordpress.org/extend/plugins/edit-flow/) if installed
* Added "Currently Editing" column to documents list to display document's lock holder, if any
* Added support for new help tabs in WordPress versions 3.3 and greater
* Fixed bug where media library would trigger an SQL error when no documents had been uploaded
* Fixed bug where owner dropdown on edit screen would only list "author" level users
* "- Latest Revision" only appended to titles on feeds

= 1.0.5 =

* Fixed bug where password-protected documents would not prompt for password under certain circumstances

= 1.0.4 =

* Significant performance improvements (now relies on wp_cache)
* Feed improvements (performance improvements, more consistent handling of authors and timestamps)
* Workflow States in documents list are now link to a list of all documents in that workflow state
* Changed "Author" column heading to "Owner" in documents list to prevent confusion
* If a revision's attachment ID is unknown, the plugin now defaults to the latest attached file, rather than serving a 404

= 1.0.3 =

* A list of all documents a user (or visitor) has permission to view is now available at yourdomain.com/documents/
* Changed functions get_latest_version and get_latest_version_url to "revision" instead of "version" for consistency
* Forces get_latest_revision to rely on get_revisions to fix inconsistencies in WP revision author bug
* Support for ugly permalink structures
* Changing metabox options does not enable the publish button on non-document pages
* Changing the title or other text fields enables the update button
* Fix for authors not having capability to edit documents by default
* No longer displays attachment ID when posts are queried via the frontend

= 1.0.2 =

* Fixed bug where RSS feeds would erroneously deny access to authorized users in multisite installs

= 1.0.1 =

* Better handling of uploads in WordPress versions 3.3 and above
* Added shadow to document menu icon (thanks to Ryan Imel of WPCandy.com)
* Fixed E_WARNING level error for undefined index on workflow_state_nonce when saving posts with WP_DEBUG on
* Corrected typos in contextual help dropdown
* Fixed permission issue where published documents were not accessible to non-logged in users
* Fixed last-modified author not displaying the proper author on document-edit screen

= 1.0 =

* Stable Release

= 0.6 =

* Release Candidate 1
* [Revision Log](http://gsoc.trac.wordpress.org/log/2011/BenBalter)

= 0.5 =

* Initial beta

= 0.1 =

* Proof of concept prototype


=== WP-Documents-Revisions Data Design and Data Structure ===

== Requirements ==

- To maintain a reference to a document and to hold a list of published versions of the documents.
	- It is not particularly concerned about how the document is created and the process to arrive at the state ready to upload.
	- It will maintain a status of where it is in the publishing process.

- It makes use of a custom post type "document" and revisions to maintain the history of Document file uploads.

- The Document file will be uploaded using the standard Media loader.
	- This will result in an Attachment post being created with the Document post as its parent.
	- It will not be visible in the Media library as Queries to the Media library remove attachments with parents that are documents.
	- Document files can be stored in a different host library.

- The Document file should not be accessible directly by the user, but ideally via the WP interface.
	- This will be supported by changing the uploaded file name to be an MD5-hash of the original file name and load time.
	- This can be supplemented by changing .htaccess rules to stop direct access to files with MD5 format names
	- Standard WP processing may create a JPEG image of PDF uploads.
	- Since it will store these using the MD5 file name that will be downloaded to the user this would expose the MD5 file name. Therefore there is a process to change these images to use another name.

- The document post record can also support Featured Images.
	- If loaded via the Edit document page, it would be considered as a Document file. So the parent post identifier will be removed to eliminate confusion between it being a Featured Image and a Document file being stored.

- Since version 3.4 of the plugin, it is possible to enter a user-oriented description that can be displayed to users with the shortcodes or blocks provided with the plugin.  

- An audit trail of changes to published versions of the Document file.
	- The user can enter a reason for changing the Document including uploading a Document file; changing the Document Description; or Title; or any Taxonomy element.
	- This reason will be stored in the Excerpt field.
	- The aggregate information may be displayed as a Revision Log.
   
- Use will be made of the standard WP Revisions functionality to contain the Audit Trail itself.
	- Standard WP processing creates a Revision if any one of these fields are changed: title, content or excerpt.
	- Since all Attachments are linked to the parent Document record, by storing the Attachment Id in the content field, then a Revision record will be created automatically. 

- This plugin is delivered with just one Taxonomy - Workflow_State. This shows the status of the Document file in its processing.
	- This is not considered very useful for user data classification.
	- However, being a generic tool, sites can use of a dedicated Taxonomy plugin.

== Data Structure ==

The records held in the database will be:

1. Document Record

- post_content contains the id of the latest Document file attachment record.
	- When a Document file is loaded on editing this Document record, the post_content will be modified to contain the ID of the Attachment record created.
	- In plugin versions prior to 3.4, this would simply be the numeric ID.
	- Subsequent versions hold this in the form of an HTML comment "&lt;!-- WPDR nnn --&gt;" where nnn is the ID of an attachment post. It can also contain a text Document description.
	- When editing the post, this field is decomposed into its two parts of ID and description with program management of the former and user management of the latter, recombined automatically when changes are made.

- post_excerpt will contain any comment entered when the document record is updated.

- As taxonomy records are held only against this Document record, there is no effective audit trail of changes to Taxonomy. Changes can be noted manually in the excerpt field

2. Attachment Record(s)

There can be multiple Attachment records, one for each Document file loaded.

- The name and title of the Attachment record is set to a MD5 hash of the original file name and the load time.

- The Document file name is also set as this MD5 hash.

- post_parent is set to the Document Record ID.

- When a PDF Document file is loaded, then standard WP processing will attempt to make a JPEG image of the first page as a thumbnail (using all sizes). These will be held in the same directory as the Document file.
	- However if the file name is MD5Hash.pdf, then these images will be called MD5Hash-pdf.jpg.
	- If used on a page, this would expose the name of the file to the user.
	- To avoid this, there is a process to transform this name to another (essentially random) MD5 and rename these image files.
	- Once done, a postmeta record is created with these new file names (and a field denoting this process has been done).

- If a Featured Image is loaded whilst editing the Document record, this would also have the same post_parent set, so in this case, the post_parent is set to 0 leaving the functional postmeta link to denote the presence of the featured image.

3. Revision Record(s)

When saving a Document Record, standard WP processing will be invoked to detect a change in title, content or excerpt fields. If one is found then a Revision record is created.

There can be multiple Revision records held, one for each saving event where a change in these fields are detected.

Because the document content contains the latest Attachment ID, an upload of a revised Document file will create a new document Revision record.


=== WP-Documents-Revisions Filters ===

This plugin makes use of many filters to tailor the delivered processing according to a site's needs.

Most of them are named with a leading 'document-' but there are a few additional non-standard ones shown at the bottom.

== Filter document_block_taxonomies ==

In: class-wp-document-revisions-front-end.php

Filters the Document taxonomies (allowing users to select the first three for the block widget.

== Filter document_buffer_size ==

In: class-wp-document-revisions.php

Filter to define file writing buffer size (Default 0 = No buffering).

== Filter document_caps ==

In: class-wp-document-revisions.php

Filters the default capabilities provided by the plugin.
Note that by default all custom roles will have the default Subscriber access.

== Filter document_content_disposition_inline ==

In: class-wp-document-revisions.php

Sets the content disposition header to open the document (inline) or to save it (attachment).
Ordinarily set as inline but can be changed.

== Filter document_custom_feed ==

In: class-wp-document-revisions.php

Sets to false to use the standard RSS feed.

== Filter document_extension ==

In: class-wp-document-revisions.php

Allows the document file extension to be manipulated.

== Filter document_help_array ==

In: class-wp-document-revisions-admin.php

Filters the default help text for current screen.

== Filter document_home_url ==

In: class-wp-document-revisions.php

Filters the home_url() for WPML and translated documents.

== Filter document_internal_filename ==

In: class-wp-document-revisions.php

Filters the encoded file name for the attached document (on save).

== Filter document_lock_check ==

In: class-wp-document-revisions.php

Filters the user locking the document file.

== Filter document_lock_override_email ==

In: class-wp-document-revisions.php

Filters the lost lock document email text.

== Filter document_output_sent_is_ok ==

In: class-wp-document-revisions.php

Filter to serve file even if output already written.

== Filter document_path ==

In: class-wp-document-revisions.php

Filters the file name for WAMP settings (filter routine provided by plugin).

== Filter document_permalink ==

In: class-wp-document-revisions.php

Filters the Document permalink.

== Filter document_post_thumbnail ==

In: class-wp-document-revisions.php

Filters the post-thumbnail size parameters (used only if this image size has not been set).

== Filter document_read_uses_read ==

In: class-wp-document-revisions.php

Filters the users capacities to require read (or read_document) capability.

== Filter document_revision_query ==

In: class-wp-document-revisions.php

Filters the plugin query to fetch all the attachments of a parent post.

== Filter document_revisions_cpt ==

In: class-wp-document-revisions.php

Filters the delivered document type definition prior to registering it.

== Filter document_revisions_ct ==

In: class-wp-document-revisions.php

Filters the default structure and label values of the workflow_state taxonomy on declaration.

== Filter document_revisions_limit ==

In: class-wp-document-revisions-admin.php

Filters the number of revisions to keep for documents.

== Filter document_revisions_merge_revisions ==

In: class-wp-document-revisions-admin.php

Filters whether to merge two revisions for a change in excerpt (generally where taxonomy change made late).

== Filter document_revisions_mimetype ==

In: class-wp-document-revisions.php

Filters the MIME type for a file before it is processed by WP Document Revisions.

== Filter document_revisions_serve_file_headers ==

In: class-wp-document-revisions.php

Filters the HTTP headers sent when a file is served through WP Document Revisions.

== Filter document_revisions_use_edit_flow ==

In: class-wp-document-revisions.php

Filter to switch off integration with Edit_Flow/PublishPress statuses.

== Filter document_rewrite_rules ==

In: class-wp-document-revisions.php

Filters the Document rewrite rules.

== Filter document_serve ==

In: class-wp-document-revisions.php

Filters file name of document served. (Useful if file is encrypted at rest).

== Filter document_serve_attachment ==

In: class-wp-document-revisions.php

Filter the attachment post to serve (Return false to stop display).

== Filter document_serve_use_gzip ==

In: class-wp-document-revisions.php

Filter to determine if gzip should be used to serve file (subject to browser negotiation).

== Filter document_shortcode_atts ==

In: class-wp-document-revisions-front-end.php

Filters the Document shortcode attributes.

== Filter document_shortcode_show_edit ==

In: class-wp-document-revisions-front-end.php

Filters the controlling option to display an edit option against each document.

== Filter document_show_in_rest ==

In: class-wp-document-revisions.php

Filters the show_in_rest parameter from its default value of fa1se.

== Filter document_slug ==

In: class-wp-document-revisions.php

Filters the document slug.

== Filter document_stop_file_access_pattern ==

In: class-wp-document-revisions.php

Filter to stop direct file access to documents (specify the URL element (or trailing part) to traverse to the document directory.

== Filter document_taxonomy_term_count ==

In: class-wp-document-revisions.php

Filter to select which taxonomies with default term count to be modified to count all non-trashed posts.

== Filter document_title ==

In: class-wp-document-revisions.php

Filter the document title from the post.

== Filter document_to_private ==

In: class-wp-document-revisions-admin.php

Filters setting the new document status to private.

== Filter document_use_workflow_states ==

In: class-wp-document-revisions.php

Filter to switch off use of standard Workflow States taxonomy. For internal use.

== Filter document_use_wp_filesystem ==

In: class-wp-document-revisions.php

Filter whether WP_FileSystem used to serve document (or PHP readfile). Irrelevant if file compressed on output.

== Filter document_validate_md5 ==

In: class-wp-document-revisions-validate-structure.php

Filter to switch off md5 format attachment validation.

== Filter document_verify_feed_key ==

In: class-wp-document-revisions.php

Allows the RSS feed to be switched off.

== Filter default_workflow_states ==

In: class-wp-document-revisions.php

Filters the default workflow state values.

== Filter lock_override_notice_subject ==

In: class-wp-document-revisions.php

Filters the locked document email subject text.

== Filter lock_override_notice_message ==

In: class-wp-document-revisions.php

Filters the locked document email message text.

== Filter send_document_override_notice ==

In: class-wp-document-revisions.php

Filters the option to send a locked document override email

== Filter serve_document_auth ==

In: class-wp-document-revisions.php

Filters the decision to serve the document through WP Document Revisions.


== Frequently Asked Questions ==

= I'm a user/developer/administrator... can I contribute? =

Of course. Please! WP Document Revisions is an open source project and is supported by the efforts of an entire community. We'd love for you to get involved. Whatever your level of skill or however much time you can give, your contribution is greatly appreciated. Check out the "[How to Contribute" page](https://github.com/wp-document-revisions/wp-document-revisions/wiki/How-to-Contribute) for more information.

= Does it work on Mac? PC? Mobile? =

WP Document Revisions should work on just about any system with a browser. You can easily collaborate between, Mac, PC, and even Linux systems. Mobile browsers, such as iOS or Android should be able to download files, but may not be able to upload new versions in all cases.

= What are the different levels of visibility? =

Each document can have one of three "visibilities":

* Private - visible only to logged in users (this can be further refined either based on users or based on the document's status)
* Password Protected - Non-logged in users can view files, but they will require a document-specific password
* Public - Anyone with the document's URL can download and view the file

= How many people can access a document at a time? =

A virtually unlimited number of people can *view* a document at the same time, but only one user can *edit* a document at a time.

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

= Do you have any plans to implement a front end? =

In short, "no", because each site's use would be radically different. Although, you can always link directly to the permalink of any public document, which will always point the latest revision and is available on the document edit screen (right click on the "download" link), or through the add-link wizard when editing a post or page (simply search for the document you want). The long answer, is "it's really easy to adapt a front end to your needs." There are more than 35 document-specific API hooks, and the plugin exposes two global functions, `get_documents()` and `get_document_revisions()`, all of which are designed to allow plugin and theme developers to extend the plugins native functionality (details below). Looking for a slightly more out-of-the-box solution? One site I know of uses a combination of two plugins [count shortcode](https://wordpress.org/plugins/count-shortcode/), which can make a front end to browse documents, especially in coordination with a [faceted search widget](https://wordpress.org/plugins/faceted-search-widget/).

= No really, how do I present documents on the front end? =

A chronological list of all documents a user has access to can be seen at yourdomain.com/documents/. Moreover, because documents are really posts, many built in WordPress features should work and public documents should act similar to posts on the front end (searching, archives, etc.). The plugin comes with a customizable recently revised documents widget, as well as two shortcodes to display documents and document revisions (details below).

= Can WP Document Revisions work in my language? =

Yes! So far WP Document Revisions has been translated to French and Spanish, and is designed to by fully internationalized. If you enjoy the plugin and are interested in contributing a translation (it's super easy), please take a look at the [Translating WordPress](http://codex.wordpress.org/Translating_WordPress) page and the plugin's [translations repository](http://translations.benbalter.com/projects/wp-document-revisions/). If you do translate the plugin, please be sure to [contact the plugin author](http://ben.balter.com/contact/) so that it can be included in future releases for other to use.

= Will in work with WordPress MultiSite =

Yes! Each site can have its own document repository (with the ability to give users different permissions on each repository), or you can create one shared document repository across all sites.

= Will it work over HTTPS (SSL) =

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


== Installation ==

= Automatic Install =

1. Login to your WordPress site as an Administrator, or if you haven't already, complete the famous [WordPress Five Minute Install](https://wordpress.org/support/article/how-to-install-wordpress/)
2. Navigate to Plugins->Add New from the menu on the left
3. Search for WP Document Revisions
4. Click "Install"
5. Click "Activate Now"

= Manual Install =

1. Download the plugin from the link in the top left corner
2. Unzip the file, and upload the resulting "wp-document-revisions" folder to your "/wp-content/plugins directory" as "/wp-content/plugins/wp-document-revisions"
3. Log into your WordPress install as an administrator, and navigate to the plugins screen from the left-hand menu
4. Activate WP Document Revisions


== Links ==

* [Source Code](https://github.com/wp-document-revisions/wp-document-revisions/) (GitHub)
* [Development version](https://github.com/wp-document-revisions/wp-document-revisions/tree/develop) ([Build Status](http://travis-ci.org/#!/wp-document-revisions/wp-document-revisions))
* [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook)
* [Translations](http://translations.benbalter.com/projects/wp-document-revisions/) (GlotPres)
* [Project Wiki](https://github.com/wp-document-revisions/wp-document-revisions/wiki)
* [Where to get Support or Report an Issue](https://github.com/wp-document-revisions/wp-document-revisions/wiki/Where-to-get-Support-or-Report-an-Issue)
* [How to Contribute](https://github.com/wp-document-revisions/wp-document-revisions/wiki/How-to-Contribute)


== Screenshots ==

\###1. A typical WP Document Revisions edit document screen.###

![A typical WP Document Revisions edit document screen.](https://raw.githubusercontent.com/wp-document-revisions/wp-document-revisions/master/screenshot-1.png)


=== WP-Documents-Revisions Shortcodes and Widget ===

These shortcodes and widget are available both in their historic form and as blocks.

Existing shortcodes can be converted to and from their block forms.

They are held in a grouping called `WP Document Revisions`.

Since the blocks make use of dynamically-generated content, the same code is used to create this for a shortcode/widget.

== Documents Shortcode ==

In a post or page, simply type `[documents]` to display a list of documents. 

= WP_Query parameters =

The shortcode accepts *most* [Standard WP_Query parameters](https://developer.wordpress.org/reference/classes/wp_query/) which should allow you to fine tune the output. Parameters are passed in the form of, for example, `[documents numberposts="5"]`. 

Specifically, the shortcode accepts: `author__in`, `author__not_in`, `author_name`, `author`, `cat`, `category__and`, `category__in`, `category__not_in`, `category_name`, `date_query`, `day`, `has_password`, `hour`, `m`, `meta_compare`, `meta_key`, `meta_query`, `meta_value_num`, `meta_value`, `minute`, `monthnum`, `name`, `numberposts`, `order`, `orderby`, `p`, `page_id`, `pagename`, `post__in`, `post__not_in`, `post_name__in`, `post_parent__in`, `post_parent__not_in`, `post_parent`, `post_password`, `post_status`, `s`, `second`, `tag__and`, `tag__in`, `tag__not_in`, `tag_id`, `tag_slug__and`, `tag_slug__in`, `tag`, `tax_query`, `title`, `w` and `year`.

If you're using a custom taxonomy, you can add the taxonomy name as a parameter in your shortcode. For example, if your custom taxonomy is called "document_categories", you can write insert a shortcode like this:

`[documents document_categories="category-name" numberposts="6"]`

(Where "category-name" is the taxonomy term's slug)

It accepts the "query_var" parameter of the taxonomies used for documents. That is, if you have defined a taxonomy for your documents with slug "document_categories". If you have not defined the query_var parameter then you use the slug. However if you have set query_var to "doc_cat", say, then you can insert a shortcode as

`[documents doc_cat="category-name" numberposts="5"]`

Important parameters WP_Query will be the ordering and number of posts to display.

`numberposts` (with a number parameter) will give the maximum number of posts to display.

`order` (with value 'ASC' or 'DESC') gives the ordering,

`orderby` (with a string value) gives the field to order the documents. Common values are "title", "date", "name", "modified" and "ID".

= Display parameters =

It is also possible to add formatting parameters: 

`show_edit` (with a true/false parameter) that can add a link next to each document shown in the list that the user is able to edit by them. This permits the user to edit the document directly from the list. A value set here will override the default behaviour.

As delivered, administrators will have the show_edit implicitly active. A filter `document_shortcode_show_edit` can be used to set this for additional user roles.

`new_tab` (with a true/false parameter) that will open the document in a new browser tab rather than in the current one.

`show_pdf` (with a true/false parameter) that, for accessibility, will display `(PDF)` as part of links if this links to a PDF document.

`show_thumb` (with a true/false parameter) that will display a featured image (or generated one from the first page of PDF documents) if provided.

`show_descr` (with a true/false parameter) that will output the entered description if provided.

All these boolean variables can be entered without a value (with default value true except for `show_thumb` whose default value is false). 

= Block Usage =

When using the block version of the shortcode called `Document List`, some compromises have been necessary.

Since queries are often selecting a single taxonomy value, the block provides the possibility to select single values from up to three taxonomies. Since there can be more than three taxomomies attached to documents, a filter `document_block_taxonomies` allows the list of taxonomies to be edited to select the taxonomies to be displayed.

The parameters `numberposts`, `order`, `orderby`, `show_edit`,`new_tab`, `show_thumb` and `show_descr` are directly supported. However, since there are many other parameters are possible, as well as differet structures, additional parameters may be entered as a text field as held in the shortcode.

= Document Taxonomy Changes =

Note that this section does *not* refer to the terms used within a taxonomy are changed but to changes made when taxonomies are registereed with documents.

It is possible that the taxonomies associated with documents are changed. Since the three taxonomies are chosen when the block is created, then if the taxonomies linked are subsequently changed, then a warning/error message "Taxonomy details in this block have changed." may be seen when the block is output.

The resolution to this issue is to transform the block to a shortcode and then back to a block again. A side effect will be to lose any "supports" properties (see below) that have been used. 

== Document Revisions Shortcode ==

In a post or page, simply type `[document_revisions id="100"]` where ID is the ID of the document for which you would like to list revisions. 

You can find the ID in the URL of the edit document page. 

To limit the number of revisions displayed, passed the "number" argument, e.g., to display the 5 most recent revisions `[document_revisions id="100" number="5"]`.

= Display parameters =

It is also possible to add formatting parameters:

`numberposts` (with a number parameter) will give the maximum number of revisions to display.

`summary` (with a true/false parameter) that will add the excerpt for the revision to the output.

`new_tab` (with a true/false parameter) that will open the revision in a new browser tab rather than in the current one.

`show_pdf` (with a true/false parameter) that, for accessibility, will display `(PDF)` as part of links if this links to a PDF document.

These boolean variables can be entered without a value (with default value true ). 

= Block Usage =

When using the block version of the shortcode called `Document Revisions`, a change have been necessary.

`number` is a reserved word within javascript so `numberposts` is also supported even for the shortcode format. `numberposts` is used by the block.

Since the block is dynamically displayed as parameters are entered, if the post number entered is not a document, then an appropriate message will be entered.

== Latest Documents Widget ==

Go to your theme's widgets page (if your theme supports widgets), and drag the widget to a sidebar of you choice. Once in a sidebar, you will be presented with options to customize the widget's functionality.

= Display parameters =

It is also possible to add formatting parameters:

`numberposts` (with a number parameter) will give the maximum number of revisions to display.

`Post Status` allowing the selection "publish", "private" or "draft", or combination of them.

`show_thumb` (with a true/false parameter) that will display a featured image (or generated one from the first page of PDF documents) if provided.

`show_descr` (with a true/false parameter) that will output the entered description if provided.

`show_author`(with a true/false parameter) that will identify the document author.

`new_tab` (with a true/false parameter) that will open the revision in a new browser tab rather than in the current one.

`show_pdf` (with a true/false parameter) that, for accessibility, will display `(PDF)` as part of links if this links to a PDF document.

= Block Usage =

The block version of the widget called `Latest Documents` can be used on pages or posts. It cannot be converted to or from a shortcode block as there is no equivalent.

== Block supports properties ==

Additionally, later versions of WordPress provide for blocks to support additional display attributes that will be applied to the block on rendering *if the theme allows it*.

These attributes are align, color, spacing and typography and these attributes have been added to all blocks.


== Translations ==

Interested in translating WP Document Revisions? You can do so [via Crowdin](https://crowdin.com/project/wordpress-document-revisions), or by submitting a pull request.

* French - [Hubert CAMPAN](http://omnimaki.com/)
* Spanish - [IBIDEM GROUP](https://www.ibidemgroup.com), [TradiArt](http://www.tradiart.com/), and [elarequi](http://www.labitacoradeltigre.com)
* Norwegian - Daniel Haugen
* German -[Konstantin Obenland](http://en.wp.obenland.it/)
* Chinese - Tim Ren
* Swedish - Daniel Kroon, [Examinare AB](http://www.examinare.biz/), Sweden.
* Czech - Hynek Šťavík
* Italian - @guterboit
* Russian - Evgeny Vlasov
* Dutch - @tijscruysen


== Useful plugins and tools ==

= Permissions management =

* [Members � Membership & User Role Editor Plugin](https://wordpress.org/plugins/members/)

	(Previously called Members)

= Taxonomy management =

* [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/)

= Email notification and distribution =

* [Email Notice for WP Document Revisions](https://wordpress.org/plugins/email-notice-wp-document-revisions/)

= Document workflow management =

* [Edit Flow](https://wordpress.org/plugins/edit-flow/)
* [PublishPress Planner](https://wordpress.org/plugins/publishpress/)

	(Previously called PublishPress)
* [PublishPress Statuses](https://wordpress.org/plugins/publishpress-statuses/)
