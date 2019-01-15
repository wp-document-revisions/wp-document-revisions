## Changelog

### 3.2.1

* Fix for $wp_query->query_vars being null (#136) @benbalter
* Media Library URL’s change after plugin update (#139) @NeilWJames
* New Crowdin translations (#137) @benbalter
* New Crowdin translations (#138) @benbalter

### 3.2.0

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

### 3.1.2

Fix for 404 error when serving documents from non-standard upload directory.

### 3.1.1

Updated documentation.

### 3.1.0

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

### 3.0.1

* Fix for calling the wrong escaping function in the widget code.

### 3.0.0

* [Dropped support for WordPress prior to version 3.3](https://github.com/benbalter/wp-document-revisions/pull/94)
* [Dropped WebDav support](https://github.com/benbalter/wp-document-revisions/pull/95)
* Implemented [WordPress coding standards](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards)
* Added additional nonces
* Added additional translation strings and comments
* Updated documentation

### 2.2.0

* [Add filter to allow opt-out of WebDAV Edit Document](https://github.com/benbalter/wp-document-revisions/pull/74)
* [Filter documents from the grid view in Media, queried via Ajax](https://github.com/benbalter/wp-document-revisions/pull/78)
* [Added code to preserve file exts on revision links](https://github.com/benbalter/wp-document-revisions/pull/81), and
* [Verify a post ID has been passed before verifying post type](https://github.com/benbalter/wp-document-revisions/pull/86)
* [Use H2 for "Feed Privacy" heading](https://github.com/benbalter/wp-document-revisions/pull/91)

### 2.0.0

* Note: The project is looking for additional contributors. Please consider contributing your time: <https://github.com/benbalter/wp-document-revisions/blob/master/docs/how-to-contribute.md>
* Added (beta) WebDAV support thanks to @linuxBozo and @poorgeek (<https://github.com/benbalter/wp-document-revisions/pull/69>)
* Added Brazilian Portuguese translation thanks to @rafaelfunchal
* Significantly improved automated testing via WP-CLI
* Better guarding against binary files being corrupted by other plugins
* Improved documentation (<https://github.com/benbalter/wp-document-revisions/tree/master/docs>)

### 1.3.6

* Fix for workflow state not properly saving under certain circumstances
* Added Italian translation, props @guterboit
* Added Russian translation, props Evgeny Vlasov
* Updated all translations
* Workflow state saving improvements, props @cojennin ([#48](https://github.com/benbalter/WP-Document-Revisions/pull/48))
* Fix restore revision link bug, props @cojennin ([#55](https://github.com/benbalter/WP-Document-Revisions/issues/55))
* Welcome @cojennin to the core team. [Want to join?](https://github.com/benbalter/WP-Document-Revisions/wiki/How-to-Contribute)

### 1.3.5

* Added Dutch translation, props @tijscruysen.
* To prevent potential errors, verify `workflow_state` is set before checking for change, props @rachelbaker.
* Added `document_custom_feed` and `document_verify_feed_key` filters to customize feed behavior, props @nodakjones.
* Prevent errors when newly added documents do not have attached files, props @rachelbaker.
* Better compatibility with WordPress 3.5 media uploader
* Significant Javascript improvements to prevent conflicts with other plugins

### 1.3.4

* Testing framework no longer distributed with the plugin.
* Added Swedish translation, special thanks to Daniel Kroon, [Examinare AB](http://www.examinare.biz/), Sweden.
* Added Czech translation set, special thanks to Hynek Šťavík.

### 1.3.3

* Fix for fatal error (undefined function) when Edit Flow custom post status were enabled, props [Leho Kraav](http://leho.kraav.com/), fixes [#24](https://github.com/benbalter/WP-Document-Revisions/issues/24)
* Fix for [testing framework](https://github.com/nb/wordpress-tests) not being properly included in plugin repository due to bad [deploy script](https://github.com/benbalter/Github-to-WordPress-Plugin-Directory-Deployment-Script)
* Added German translation (de_DE), special thanks to [Konstantin Obenland](http://en.wp.obenland.it/)
* Added Chinese translation (zh_CN), special thanks to Tim Ren
* Updated Spanish, French, and Norwegian translations

### 1.3.2

* Plugin documentation now maintained in [collaboratively edited wiki](https://github.com/benbalter/WP-Document-Revisions/wiki). Feel free to contribute!
* Created listserv to provide a discussion forum for users of and contributors, as well as general annoucements. [Feel free to join!](https://groups.google.com/forum/#!forum/wp-document-revisions)
* Added Norwegian translation, special thanks to Daniel Haugen
* [Crisper menu icon](https://github.com/benbalter/WP-Document-Revisions/commit/00ffe42daabacf90091cc3638dd2658c2376f01a), special thanks to [Phil Russell](www.optionotter.com)
* Pushpin icon [replaced with Retina document icon](https://github.com/benbalter/WP-Document-Revisions/commit/c36ee849512f77432db8e6783a0bc4389f33f0ab) on document list and document edit screen, special thanks to [Marvin Rühe](https://github.com/Marv51)
* Unit tests now utilizes newer [wordpress-tests](https://github.com/nb/wordpress-tests) framework, as recently adopted by core
* `serve_file` [now hooks](https://github.com/benbalter/WP-Document-Revisions/commit/57cac162e40255efb29c354754e9a0b8df05a2ef) into `template_include` filter (rather than `template_single`) to prevent potential conflict with themes/plugins hooking into subsequent filters and producing extranous output after the document is served which would result in corrupting some files
* Fix for `document_to_private` filter [not properly passing](https://github.com/benbalter/WP-Document-Revisions/commit/04922d73eb63172e79f2f9e86e4002cee032e4ef) the pre-filtered document object, props [Marvin Rühe](https://github.com/Marv51).
* [Better loading](https://github.com/benbalter/WP-Document-Revisions/commit/0349f8ebcf931f6b2731b42ae795b3191ce9ed45) of administrative functions
* [Better toggling](https://github.com/benbalter/WP-Document-Revisions/commit/6947310c06a8267573835c6b4bc04e3ad1b29405) of Workflow state support for integration with Edit Flow and other plugins
* Administrative CSS [now stored in a separate file](https://github.com/benbalter/WP-Document-Revisions/commit/f61385b674c67adf820c9e240fe3eadb4b1cf3a2) (rather than being injected directly to document head), and [loads via `enqueue_style` API](https://github.com/benbalter/WP-Document-Revisions/commit/11a583edfeb307a939e7686fca4712a195ac4059)
* Administrative CSS and Javascript files now versioned based on plugin version to allow for better caching

### 1.3.1

* Better permalink support for draft and pending documents
* Whenever possible browser will attempt to display documents in browser, rather than prompting with save as dialog (e.g., PDFs)
* Fix for function `get_file_type()` breaking the global `$post` variable when no document argument is supplied
* Improved Spanish translation with additional strings (special thanks, [elarequi](http://www.labitacoradeltigre.com))

### 1.3

* Plugin now includes unit tests to ensure security and stability, and [undergoes extensive testing](http://travis-ci.org/#!/benbalter/WP-Document-Revisions) (WordPress 3.2/3.3/Trunk, Multisite/single, PHP 5.3/5.4) via continuous integration service Travis CI prior to release.
* Translations now curated on [collaborative editing platform GlotPress](http://translations.benbalter.com/projects/wp-document-revisions/) if any user would like to submit a translation ([no technical knowledge necessary](http://translations.benbalter.com/projects/how-to-translate))
* If you would like to help out by testing early releases, please try the continuously updated [development version](https://github.com/benbalter/WP-Document-Revisions/tree/develop). Any [feedback](https://github.com/benbalter/WP-Document-Revisions/issues?direction=desc&sort=created&state=open), technical or prose is helpful.
* Added Spanish Translation Support (es_ES — special thanks to [TradiArt](http://www.tradiart.com/))
* Document URL slug (used for archive and prefixing all documents) now customizable via settings page and translatable. (e.g., <http://domain.com/documentos/2012/04/test.txt> rather than /documents/)
* Subscribers and unauthenticated users no longer have the ability to read revisions by default (you can override this setting using the [Members plugin](http://wordpress.org/extend/plugins/members/).
* Attempts to access unauthorized files now properly respond with HTTP code 403 (rather than 500 previously). Note: attempting to access private documents will continue to result in 404s.
* Enhanced authentication prior to serving files now provides developers more granular control of permissions via `serve_document_auth` filter.
* Better Edit Flow support (can now toggle document support on and off using native Edit Flow user interface). Note: You may need to manually toggle on custom status support for documents after upgrading.
* Default document upload directory now honors WordPress-wide defaults and features enhanced multisite support
* Ability to separate documents on server by site subfolder on multisite installs

### 1.2.4

* Better support for custom document upload directories on multisite installs
* Gallery, URL, and Media Library links now hidden from media upload popup when uploading revisions
* Fix for plugin breaking media gallery when filtered by mimetype (MySQL ambiguity error)
* Fix for upload new version button appearing for locked out users in WordPress 3.3
* Fix for upload new version button not appearing after document lock override on WordPress 3.3

### 1.2.3

* Owner metabox no longer displays if user does not have the ability to `edit_others_documents`
* Fix for serving documents via SSL to Internet Explorer version 8 and earlier
* GPL License now distributed with plugin
* Code cleanup, minor bug fixes, and additional inline documentation

### 1.2.2

* Plugin [posted to Github](https://github.com/benbalter/WP-Document-Revisions) if developers would like to fork and contribute
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

### 1.2.1

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

### 1.2

* Added shortcode to display list of documents meeting specified criteria
* Added shortcode to display a document's revisions (formerly in code cookbook)
* Added widget to display recently revised documents (formerly in code cookbook)
* Created new global `get_documents()` and `get_document_revisions()` functions to help build and customize themes and plugins
* Added filter to `wp_get_attachment_url` to force document/revision urls when attachments are queried directly
* Better organization of plugin files within plugin folder
* Fixed bug where revision summary would not display under certain circumstances

### 1.1

* Added support for the [Edit Flow Plugin](http://wordpress.org/extend/plugins/edit-flow/) if installed
* Added "Currently Editing" column to documents list to display document's lock holder, if any
* Added support for new help tabs in WordPress versions 3.3 and greater
* Fixed bug where media library would trigger an SQL error when no documents had been uploaded
* Fixed bug where owner dropdown on edit screen would only list "author" level users
* "- Latest Revision" only appended to titles on feeds

### 1.0.5

* Fixed bug where password-protected documents would not prompt for password under certain circumstances

### 1.0.4

* Significant performance improvements (now relies on wp_cache)
* Feed improvements (performance improvements, more consistent handling of authors and timestamps)
* Workflow States in documents list are now link to a list of all documents in that workflow state
* Changed "Author" column heading to "Owner" in documents list to prevent confusion
* If a revision's attachment ID is unknown, the plugin now defaults to the latest attached file, rather than serving a 404

### 1.0.3

* A list of all documents a user (or visitor) has permission to view is now available at yourdomain.com/documents/
* Changed functions get_latest_version and get_latest_version_url to "revision" instead of "version" for consistency
* Forces get_latest_revision to rely on get_revisions to fix inconsistencies in WP revision author bug
* Support for ugly permalink structures
* Changing metabox options does not enable the publish button on non-document pages
* Changing the title or other text fields enables the update button
* Fix for authors not having capability to edit documents by default
* No longer displays attachment ID when posts are queried via the frontend

### 1.0.2

* Fixed bug where RSS feeds would erroneously deny access to authorized users in multisite installs

### 1.0.1

* Better handling of uploads in WordPress versions 3.3 and above
* Added shadow to document menu icon (thanks to Ryan Imel of WPCandy.com)
* Fixed E_WARNING level error for undefined index on workflow_state_nonce when saving posts with WP_DEBUG on
* Corrected typos in contextual help dropdown
* Fixed permission issue where published documents were not accessible to non-logged in users
* Fixed last-modified author not displaying the proper author on document-edit screen

### 1.0

* Stable Release

### 0.6

* Release Candidate 1
* [Revision Log](http://gsoc.trac.wordpress.org/log/2011/BenBalter)

### 0.5

* Initial beta

### 0.1

* Proof of concept prototype
