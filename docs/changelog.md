## Changelog

Numbers in brackets show the issue number in https://github.com/wp-document-revisions/wp-document-revisions/issues/

### 3.7.2

Correct plugin metadata (no code chages).

### 3.7.1

Correct tested up to version (no code chages).

### 3.7.0

- NEW: Protect document revision deletion by Database cleaners that use WordPress API to delete them. (#364)
- NEW: Filter 'document_allow_revision_deletion' to allow revision deletion by trusted plugins,e.g. PublishPress Revisions. (#364)
- NEW: PublishPress support withdrawn as replaced by PublishPress Statuses.
- NEW: Filter 'document_validate' to bypass structure validation for a specific document.
- NEW: Filter 'document_thumbnail' used to override the thumbnail image size on document lists.
- NEW: Image size post_thumbnail no longer created when theme does not create it; instead equivalent used on rendering. (#356)
- FIX: Document revision limits not working in non-admin contexts (e.g. with PublishPress Revisions plugin). (#366)
- FIX: Additional edge cases for revision deletion protection by plugin-managed operations. (#368)
- FIX: Document slug sanitization to prevent invalid characters like whitespace in URLs. (#369)
- FIX: Translations need to be called on 'init', not 'plugins_loaded'.
- FIX: Uploading twice between document saves creates orphan attachment on deletion (#353)
- FIX: TypeError: window.WPDocumentRevisions is undefined (#348)
- FIX: Ensure File descriptor of Document Upload includes subdir component. (#342)
- DEV: Improved test coverage and compatibility testing for PHP 7.4-8.3 and WordPress 4.9+.
- DEV: Updated REST API tests for compatibility with latest WordPress versions. (#347)

### 3.6.0

- NEW: Integrate with PublishPress Statuses plugin for custom statuses. (#335)
- NEW: Accessibility rules states that links to PDF documents should have visible references. Blocks have an explicit switch. (#322)
- NEW: User pulldowns will show only relevant users. (#321)
- NEW: Filter 'document_post_thumbnail' used to define the post-thumbnail image size (if not set by theme). (#339)
- NEW: Filter 'document_use_wp_filesystem' used to serve document (instead of PHP readfile). Irrelevant if the file is compressed on output. (#320)
- NEW: Filter 'document_internal_filename' for updating internal file name additionally passed the original name. (#319)
- NEW: Filter 'document_validate_md5' to switch off attachment MD5 format validation. (#318)
- NEW: Optionally stop direct web access to document files to force access only via WordPress. (#317)
- NEW: If a role already has "read_documents" capability, do not touch capabilities on plugin reactivation. (#315)
- NEW: Filter 'document_home_url' to allow changes to be made to it (used with WPML). (#329)
- FIX: Ensure File descriptor of Document Upload includes subdir component. (#342)
- FIX: Use with plugin EditFlow gives PHP 8.0 error. (#331)
- FIX: Typo in description of default upload location. (#328)
- FIX: Filter 'document_revisions_owner' withdrawn as parameter acted on (who) deprecated in WP 5.9. (#316)
- FIX: Updates to document description do not enable the Submit button
- DEV: JS scripts will be called with Defer in WP 6.3 onwards. (#314)
- DEV: Review for WP Coding standard 3.0 (#313)

### 3.5.0

- SECURITY: Rest media interface may expose document name.
- NEW: Site can decide to save permalinks without year/month part.
- NEW: Permalinks may be updated on the documents screen.
- FIX: guid field for documents was generally incorrect. Will be stored as a valid value.
- FIX: Upload directory processing reviewed and simplified.
- FIX: Document permalink month can be incorrect when saved at month end. (#300).
- FIX: Valid document may not be found.
  " FIX: Improve notification process when activation user does not have edit_documents capability.

### 3.4.0

- SECURITY: WordPress can create images for PDF documents which if used would leak the hidden document name so image name changed.
- NEW: An action 'document_saved' is provided for processing after a document has been saved or updated and all plugin processing complete. (#278)
- NEW: A filter 'document_serve_attachment' is provided to review the attachment id being served. Return false to stop display. (#278)
- NEW: A filter 'document_show_in_rest' is provided to display document data via the REST interface using document permissions. {#258, #259)
- NEW: A tool is provided to validate the internal structure of all documents that the user can edit. If fixable then a button is displayed to fix it. (#260)
- NEW: A user-oriented description may be entered for each document. This can be displayed with the Documents List shortcode and Latest Documents widget or their block equivalents. (#263)
- NEW: These blocks can also display the featured image or generated image for PDF documents. (#264)
- NEW: Blocks extended to support standard Colour and Fontsize attributes. (#264}
- NEW: Revisions can be merged if made within a user-defined interval using filter 'document_revisions_merge_revisions' (Default 0 = No merging). (#263)
- FIX: jQuery ready verb usage removed. (#262}
- FIX: Caching strategy reviewed to ensure updates delivered to users. (#261}
- FIX: Blocks used incorrect, but previously tolerated, parameter for RadioControls rendering them difficult to use.
- FIX: Blocks are categorised within the Editor differently with 5.8

### 3.3.1

- FIX: Content-Length header suppressed for HTTP/2 File Serve. {#254)
- FIX: MOD_DEFLATE modifies etag, so no caching occurred in this case.
- FIX: Gzip process invoked for encodings gzip, x-gzip and deflate.

### 3.3.0

- SECURITY: Password-protected document can leak existence (by showing next/previous)
- SECURITY: Queries on post_status do not do proper permissions check
- SECURITY: Suppress excerpt output in feeds to stop information leakage
- SECURITY: WP creates images when saving PDF documents (using the encoded name). These were being left when deleting the document.
- NEW: Rewrite rules extended to access documents without year/month and/or file extension. (#253) @NeilWJames
- NEW: Use standard WP process for Taxonomy workflow_state on Document Admin List. Note that it will change the column order seen as taxonomiees are on the end.
- NEW: Implement Gutenberg Blocks for Shortcodes and Widget.
- NEW: Integrate with either Edit-flow or PublishPress plugins
- NEW: Taxonomy workflow_state is set as show_in_rest.
- NEW: Add action 'document_serve_done' which can be use to delete decrypted files (needed for encrypted at rest files)
- NEW: Add filter 'document_buffer_size' to define file writing buffer size (Default 0 = No buffering).
- NEW: Add filter 'document_output_sent_is_ok' to serve file even if output already written.
- NEW: Add filter 'document_read_uses_read' to use read_document capability (and not read) to read documents
- NEW: Add filter 'document_serve_use_gzip' to determine if gzip should be used to serve file (subject to browser negotiation).
- NEW: Add filter 'document_serve' to filter the file to be served (needed for encrypted at rest files)
- NEW: New Crowdin updates (#244, #245)
- FIX: Access to revisions when permalink structure not defined.
- FIX: Design conflict with Elementor (#230) @NeilWJames
- FIX: Document directory incorrect test for Absolute/Relative entry on Windows implementations
- FIX: Document Taxonomies using default term counts will use same method as WORKFLOW_STATE, i.e. count all not-trashed documents
- FIX: Ensure the action point to detect change in workflow_state worked (for CookBook functionality).
- FIX: Fix error in time difference display when client and server are in different time zones
- FIX: Fix WP 5.7 Breaking change (#38843) for Term Counts. (#250) @NeilWJames
- FIX: Remove existing workaround for WP bug 16215 and long time fixed - and made information incorrect
- FIX: Remove restore option on the current document and latest revision as it makes no sense.
- FIX: Review document serving process to try to identify where other plugins could output text and corrupt file download
- FIX: Review documentation. (#208) @NeilWJames
- FIX: Review of Rewrite rules with/without trailing slash; also extend file extension length
- FIX: Testing of blocks showed that if document taxonomies are changed, then existing blocks may not work. Some changes are now handled. (#217) @NeilWJames
- FIX: Fixing compatibility issue with double slash in Documents URL when using WPML (#218) @BobbyKarabinakis
- DEV: Update code to WP Coding Standards 2.2.1 (and fix new sniff errors)
- DEV: Update coveralls to 2.2, dealerdirect/codesniffer to 0.6, phpunit/phpunit to 8.5 and wp/cli to 2.4.1
- DEV: Rewrite Test library to increase code coverage.
- DEV: Use GitHub Actions for CI (#251)
- DEV: Fixed wp_die() tests ending tests prematurely (#252)

### 3.2.4

- Address technical debt for WP Document Standards (#192) @NeilWJames
- On plugin activation, check that the user has edit_documents capability. If not, a warning message will be output that the menu may be incorrect. (#180) @NeilWJames
- PHPCS review (#179) @NeilWJames
- Bump phpunit/phpunit from 8.2.5 to 8.3.4 (#177) @dependabot-preview
- Addresses phpunit and toolset versions and prepare for future release (#174) @NeilWJames
- Version 3.2.2 gives an "property of non-object" at line 1403 on load (#161) @NeilWJames

### 3.2.3

- Full phpcs 2.2 standardisation, complete filter documentation (#192) @NeilWJames
- On plugin activation, admin warning if user doesn't have edit_documents capability (#180) @NeilWJames
- PHPCS Review (no functional changes) (#179) @NeilWJames
- Review for WP Coding standard 2.1.1 and newer phpunit (#174) @NeilWJames
- Bump version to V3.2.3 and Tested WP 5.2.2 (#174) @NeilWJames

### 3.2.2

- Version 3.2.2 gives an "property of non-object" at line 1403 on load (#161) @NeilWJames
- Add default capabilities only when they are absent. (#146) @NeilWJames
- Fix multi-network (needs WP 4.6) (#143) @geminorum
- Allow Sites to use WP_POST_REVISIONS for other post types (#140)
- Media Library URL’s change after plugin update (#139) @NeilWJames
- New crowdin translations (#137/#138) @benbalter

### 3.2.1

- Fix for $wp_query->query_vars being null (#136) @benbalter
- Media Library URL’s change after plugin update (#139) @NeilWJames
- New Crowdin translations (#137) @benbalter
- New Crowdin translations (#138) @benbalter

### 3.2.0

- Enable filter by workflow_state on Admin screen (#121) @NeilWJames
- missing translate on metabox titles (#122) @geminorum
- Addresses #124 (is_feed has doing_it_wrong error) (#125) @NeilWJames
- New Crowdin translations (#120) @benbalter
- Allow HTTP headers to be filtered in serve_file() (#123) @jeremyfelt
- Small fixes in Admin function (#126) @NeilWJames
- Support Featured Images (#131) @NeilWJames
- Error if directory option not present (#132) @NeilWJames
- Create Edit link on document shortcode (#133) @NeilWJames
- Bump version to V3.2 and Tested WP 4.9.8 (#134) @NeilWJames

### 3.1.2

Fix for 404 error when serving documents from non-standard upload directory.

### 3.1.1

Updated documentation.

### 3.1.0

- NEW: Added dashboard widget (#109, props @NeilWJames)
- NEW: Added Finish translation (Props @xcoded)
- NEW: Added Spanish translation (Props @alejnavarro)
- NEW: Added Indonesian translation (Props @barzah and @fajarsdq)
- NEW: Provide a way to filter or skip mime type detection (#106, props @jeremyfelt)
- FIX: Do not escape end list widget HTML in function widget (#99, props @NeilWJames)
- FIX: Only cache revisions output if revisions exist (#101, props @jeremyfelt)
- FIX: Update WPCS and adjust code to meet new standards (#104, props @jeremyfelt)
- FIX: Fix possible data pollution in archive views (#103, props @jeremyfelt)
- FIX: Account for `the_title` filter used with only one arg (#105, props @jeremyfelt)
- FIX: Don't verify posts that don't exist (#107, props @jeremyfelt)
- FIX: Fixed issue with translation files not properly loading (#108, props @NeilWJames)
- FIX: Better multisite support (#113, props @JonasBrand)
- DEV: You can now contribute to the project's translation's via Crowdin: https://crowdin.com/project/wordpress-document-revisions
- DEV: Added Contributor Code of Conduct
- DEV: Updated contributing documentation

### 3.0.1

- Fix for calling the wrong escaping function in the widget code.

### 3.0.0

- [Dropped support for WordPress prior to version 3.3](https://github.com/wp-document-revisions/wp-document-revisions/pull/94)
- [Dropped WebDav support](https://github.com/wp-document-revisions/wp-document-revisions/pull/95)
- Implemented [WordPress coding standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards)
- Added additional nonces
- Added additional translation strings and comments
- Updated documentation

### 2.2.0

- [Add filter to allow opt-out of WebDAV Edit Document](https://github.com/wp-document-revisions/wp-document-revisions/pull/74)
- [Filter documents from the grid view in Media, queried via Ajax](https://github.com/wp-document-revisions/wp-document-revisions/pull/78)
- [Added code to preserve file exts on revision links](https://github.com/wp-document-revisions/wp-document-revisions/pull/81), and
- [Verify a post ID has been passed before verifying post type](https://github.com/wp-document-revisions/wp-document-revisions/pull/86)
- [Use H2 for "Feed Privacy" heading](https://github.com/wp-document-revisions/wp-document-revisions/pull/91)

### 2.0.0

- Note: The project is looking for additional contributors. Please consider contributing your time: <https://github.com/wp-document-revisions/wp-document-revisions/blob/master/docs/how-to-contribute.md>
- Added (beta) WebDAV support thanks to @linuxBozo and @poorgeek (<https://github.com/wp-document-revisions/wp-document-revisions/pull/69>)
- Added Brazilian Portuguese translation thanks to @rafaelfunchal
- Significantly improved automated testing via WP-CLI
- Better guarding against binary files being corrupted by other plugins
- Improved documentation (<https://github.com/wp-document-revisions/wp-document-revisions/tree/master/docs>)

### 1.3.6

- Fix for workflow state not properly saving under certain circumstances
- Added Italian translation, props @guterboit
- Added Russian translation, props Evgeny Vlasov
- Updated all translations
- Workflow state saving improvements, props @cojennin ([#48](https://github.com/wp-document-revisions/wp-document-revisions/pull/48))
- Fix restore revision link bug, props @cojennin ([#55](https://github.com/wp-document-revisions/wp-document-revisions/issues/55))
- Welcome @cojennin to the core team. [Want to join?](https://github.com/wp-document-revisions/wp-document-revisions/wiki/How-to-Contribute)

### 1.3.5

- Added Dutch translation, props @tijscruysen.
- To prevent potential errors, verify `workflow_state` is set before checking for change, props @rachelbaker.
- Added `document_custom_feed` and `document_verify_feed_key` filters to customize feed behavior, props @nodakjones.
- Prevent errors when newly added documents do not have attached files, props @rachelbaker.
- Better compatibility with WordPress 3.5 media uploader
- Significant Javascript improvements to prevent conflicts with other plugins

### 1.3.4

- Testing framework no longer distributed with the plugin.
- Added Swedish translation, special thanks to Daniel Kroon, [Examinare AB](http://www.examinare.biz/), Sweden.
- Added Czech translation set, special thanks to Hynek Šťavík.

### 1.3.3

- Fix for fatal error (undefined function) when Edit Flow custom post status were enabled, props [Leho Kraav](http://leho.kraav.com/), fixes [#24](https://github.com/wp-document-revisions/wp-document-revisions/issues/24)
- Fix for [testing framework](https://github.com/nb/wordpress-tests) not being properly included in plugin repository due to bad [deploy script](https://github.com/benbalter/Github-to-WordPress-Plugin-Directory-Deployment-Script)
- Added German translation (de_DE), special thanks to [Konstantin Obenland](http://en.wp.obenland.it/)
- Added Chinese translation (zh_CN), special thanks to Tim Ren
- Updated Spanish, French, and Norwegian translations

### 1.3.2

- Plugin documentation now maintained in [collaboratively edited wiki](https://github.com/wp-document-revisions/wp-document-revisions/wiki). Feel free to contribute!
- Created listserv to provide a discussion forum for users of and contributors, as well as general annoucements. [Feel free to join!](https://groups.google.com/forum/#!forum/wp-document-revisions)
- Added Norwegian translation, special thanks to Daniel Haugen
- [Crisper menu icon](https://github.com/wp-document-revisions/wp-document-revisions/commit/00ffe42daabacf90091cc3638dd2658c2376f01a), special thanks to [Phil Russell](www.optionotter.com)
- Pushpin icon [replaced with Retina document icon](https://github.com/wp-document-revisions/wp-document-revisions/commit/c36ee849512f77432db8e6783a0bc4389f33f0ab) on document list and document edit screen, special thanks to [Marvin Rühe](https://github.com/Marv51)
- Unit tests now utilizes newer [wordpress-tests](https://github.com/nb/wordpress-tests) framework, as recently adopted by core
- `serve_file` [now hooks](https://github.com/wp-document-revisions/wp-document-revisions/commit/57cac162e40255efb29c354754e9a0b8df05a2ef) into `template_include` filter (rather than `template_single`) to prevent potential conflict with themes/plugins hooking into subsequent filters and producing extranous output after the document is served which would result in corrupting some files
- Fix for `document_to_private` filter [not properly passing](https://github.com/wp-document-revisions/wp-document-revisions/commit/04922d73eb63172e79f2f9e86e4002cee032e4ef) the pre-filtered document object, props [Marvin Rühe](https://github.com/Marv51).
- [Better loading](https://github.com/wp-document-revisions/wp-document-revisions/commit/0349f8ebcf931f6b2731b42ae795b3191ce9ed45) of administrative functions
- [Better toggling](https://github.com/wp-document-revisions/wp-document-revisions/commit/6947310c06a8267573835c6b4bc04e3ad1b29405) of Workflow state support for integration with Edit Flow and other plugins
- Administrative CSS [now stored in a separate file](https://github.com/wp-document-revisions/wp-document-revisions/commit/f61385b674c67adf820c9e240fe3eadb4b1cf3a2) (rather than being injected directly to document head), and [loads via `enqueue_style` API](https://github.com/wp-document-revisions/wp-document-revisions/commit/11a583edfeb307a939e7686fca4712a195ac4059)
- Administrative CSS and Javascript files now versioned based on plugin version to allow for better caching

### 1.3.1

- Better permalink support for draft and pending documents
- Whenever possible browser will attempt to display documents in browser, rather than prompting with save as dialog (e.g., PDFs)
- Fix for function `get_file_type()` breaking the global `$post` variable when no document argument is supplied
- Improved Spanish translation with additional strings (special thanks, [elarequi](http://www.labitacoradeltigre.com))

### 1.3

- Plugin now includes unit tests to ensure security and stability, and [undergoes extensive testing](http://travis-ci.org/#!/wp-document-revisions/wp-document-revisions) (WordPress 3.2/3.3/Trunk, Multisite/single, PHP 5.3/5.4) via continuous integration service Travis CI prior to release.
- Translations now curated on [collaborative editing platform GlotPress](http://translations.benbalter.com/projects/wp-document-revisions/) if any user would like to submit a translation ([no technical knowledge necessary](http://translations.benbalter.com/projects/how-to-translate))
- If you would like to help out by testing early releases, please try the continuously updated [development version](https://github.com/wp-document-revisions/wp-document-revisions/tree/develop). Any [feedback](https://github.com/wp-document-revisions/wp-document-revisions/issues?direction=desc&sort=created&state=open), technical or prose is helpful.
- Added Spanish Translation Support (es_ES — special thanks to [TradiArt](http://www.tradiart.com/))
- Document URL slug (used for archive and prefixing all documents) now customizable via settings page and translatable. (e.g., <http://domain.com/documentos/2012/04/test.txt> rather than /documents/)
- Subscribers and unauthenticated users no longer have the ability to read revisions by default (you can override this setting using the [Members plugin](http://wordpress.org/plugins/members/).
- Attempts to access unauthorized files now properly respond with HTTP code 403 (rather than 500 previously). Note: attempting to access private documents will continue to result in 404s.
- Enhanced authentication prior to serving files now provides developers more granular control of permissions via `serve_document_auth` filter.
- Better Edit Flow support (can now toggle document support on and off using native Edit Flow user interface). Note: You may need to manually toggle on custom status support for documents after upgrading.
- Default document upload directory now honors WordPress-wide defaults and features enhanced multisite support
- Ability to separate documents on server by site subfolder on multisite installs

### 1.2.4

- Better support for custom document upload directories on multisite installs
- Gallery, URL, and Media Library links now hidden from media upload popup when uploading revisions
- Fix for plugin breaking media gallery when filtered by mimetype (MySQL ambiguity error)
- Fix for upload new version button appearing for locked out users in WordPress 3.3
- Fix for upload new version button not appearing after document lock override on WordPress 3.3

### 1.2.3

- Owner metabox no longer displays if user does not have the ability to `edit_others_documents`
- Fix for serving documents via SSL to Internet Explorer version 8 and earlier
- GPL License now distributed with plugin
- Code cleanup, minor bug fixes, and additional inline documentation

### 1.2.2

- Plugin [posted to Github](https://github.com/wp-document-revisions/wp-document-revisions) if developers would like to fork and contribute
- Documents shortcode now accepts additional parameters. See the FAQ for a full list.
- Performance and scalability improvements to backend; files attached to documents are now excluded from media lists by join statements rather than subqueries
- If plugin is unable to locate requested file on server, standard theme's 404 template is served (rather than serving "404 — file not found" via `wp_die()` previously) and E_USER_NOTICE level error is thrown. Diagnostic information will be available via debug bar (if WP_DEBUG is enabled) or in the standard PHP error log
- `/documents/` now supports pagination
- Support for linking to revisions with ugly permalinks
- Custom post type's `has_archive` property changed to `true` to help with theme compatibility
- Fix for fatal error when user without `read_document_revisions` capability called `wp_get_attachment_url()` on file attached to a revision
- Fix for broken permalink returned when get_permalink is called multiple times on the same document revision
- Fix for wp_get_attachment_image_src returning broken URLs or the direct path to the document
- Fix for "`Call-time pass-by-reference has been deprecated`" error when running certain versions of PHP
- General code cleanup

### 1.2.1

- French translation (Special thanks to [Hubert CAMPAN](http://omnimaki.com/))
- Enhanced support for running on WAMP systems (XAMPP, etc.)
- Improved integration with WordPress 3.3's new upload handler
- Significant performance improvements to `verify_post_type()` method
- Document requests no longer canonically 301 redirect with a trailing slash
- Fix for wp_get_attachment_url returning the attachment URL, rather than the document permalink when called directly
- Menu item now reads "All Documents" (rather than simply "Documents") for clarity
- Fix for E_WARNING level error on edit-tags.php with custom taxonomies
- Taxonomy counts (e.g., workflow states) now reflects non-published documents
- Better translation support (see the [FAQ](http://wordpress.org/plugins/wp-document-revisions/faq/) if you are interested in translating the plugin into your language)
- Compatibility fix for WordPress SEO's "Clean Permalinks" mode

### 1.2

- Added shortcode to display list of documents meeting specified criteria
- Added shortcode to display a document's revisions (formerly in code cookbook)
- Added widget to display recently revised documents (formerly in code cookbook)
- Created new global `get_documents()` and `get_document_revisions()` functions to help build and customize themes and plugins
- Added filter to `wp_get_attachment_url` to force document/revision urls when attachments are queried directly
- Better organization of plugin files within plugin folder
- Fixed bug where revision summary would not display under certain circumstances

### 1.1

- Added support for the [Edit Flow Plugin](http://wordpress.org/plugins/edit-flow/) if installed
- Added "Currently Editing" column to documents list to display document's lock holder, if any
- Added support for new help tabs in WordPress versions 3.3 and greater
- Fixed bug where media library would trigger an SQL error when no documents had been uploaded
- Fixed bug where owner dropdown on edit screen would only list "author" level users
- "- Latest Revision" only appended to titles on feeds

### 1.0.5

- Fixed bug where password-protected documents would not prompt for password under certain circumstances

### 1.0.4

- Significant performance improvements (now relies on wp_cache)
- Feed improvements (performance improvements, more consistent handling of authors and timestamps)
- Workflow States in documents list are now link to a list of all documents in that workflow state
- Changed "Author" column heading to "Owner" in documents list to prevent confusion
- If a revision's attachment ID is unknown, the plugin now defaults to the latest attached file, rather than serving a 404

### 1.0.3

- A list of all documents a user (or visitor) has permission to view is now available at yourdomain.com/documents/
- Changed functions get_latest_version and get_latest_version_url to "revision" instead of "version" for consistency
- Forces get_latest_revision to rely on get_revisions to fix inconsistencies in WP revision author bug
- Support for ugly permalink structures
- Changing metabox options does not enable the publish button on non-document pages
- Changing the title or other text fields enables the update button
- Fix for authors not having capability to edit documents by default
- No longer displays attachment ID when posts are queried via the frontend

### 1.0.2

- Fixed bug where RSS feeds would erroneously deny access to authorized users in multisite installs

### 1.0.1

- Better handling of uploads in WordPress versions 3.3 and above
- Added shadow to document menu icon (thanks to Ryan Imel of WPCandy.com)
- Fixed E_WARNING level error for undefined index on workflow_state_nonce when saving posts with WP_DEBUG on
- Corrected typos in contextual help dropdown
- Fixed permission issue where published documents were not accessible to non-logged in users
- Fixed last-modified author not displaying the proper author on document-edit screen

### 1.0

- Stable Release

### 0.6

- Release Candidate 1
- [Revision Log](http://gsoc.trac.wordpress.org/log/2011/BenBalter)

### 0.5

- Initial beta

### 0.1

- Proof of concept prototype
