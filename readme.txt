=== WP Document Revisions ===
Contributors: benbalter
Donate link: http://ben.balter.com/donate/?utm_source=wp&utm_medium=org_plugin_page&utm_campaign=document_revisions
Tags: documents, uploads, attachments, document management, enterprise, version control, revisions, collaboration, journalism, government, files, revision log, document management, intranet, digital asset management, dam
Requires at least: 3.2
Tested up to: 3.4
Stable tag: 1.2.3

A document management and version control plugin that allows teams of any size to collaboratively edit files and manage their workflow.

== Description ==

[WP Document Revisions](http://wordpress.org/extend/plugins/wp-document-revisions/) is a [document management](http://en.wikipedia.org/wiki/Document_management_system) and [version control](http://en.wikipedia.org/wiki/Revision_control) plugin. Built for time-sensitive and mission-critical projects, teams can collaboratively edit files of any format -- text documents, spreadsheets, images, sheet music... anything -- all the while, seamlessly tracking the document's progress as it moves through your organization's existing workflow.

*Additional information, including a screencast of a typical use case, is available on the [WP Document Revisions page](http://ben.balter.com/2011/08/29/document-management-version-control-for-wordpress/).*

**WP Document Revisions is three things:**

1. A **document management system** (DMS), to track, store, and organize files of any format
2. A **collaboration tool** to empower teams to collaboratively draft, edit, and refine documents
3. A **file hosting solution** to publish and securely deliver files to a team, to clients, or to the public

[youtube http://www.youtube.com/watch?v=VpsTNSiJKis]

**Powerful Collaboration Tools** - *With great power does not have to come great complexity.* Based on a simple philosophy of putting powerful but intuitive tools in the hands of managers and content creators, WP Document Revisions leverages many of the essential WordPress features that, for more than eight years, have been tested and proven across countless industries -- posts, attachments, revisions, taxonomies, authentication, and permalinks -- to make collaborating on the creation and publication of documents a natural endeavor. Think of it as an [open-source and more intuitive version](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/) of the popular Microsoft collaboration suite, [Sharepoint.](http://sharepoint.microsoft.com/en-us/Pages/default.aspx)

**Document History** - At each step of the authoring process, WP Document Revisions gives you an instant snapshot of your team's progress and the document's history. It even gives you the option to revert back to a previous revision -- so don't fret if you make a mistake -- or receive updates on changes to the document right in your favorite feed reader.

**Access Control** - Each document is given a persistent URL (e.g., yourcompany.com/documents/2011/08/TPS-Report.doc) which can be private (securely delivered only to members of your organization), password protected (available only to those you select such as clients or contractors), or public (published and hosted for the world to see). If you catch a typo and upload a new version, that URL will continue to point to the latest version, regardless of how many changes you make.

**Enterprise Security** - Worried about storing propriety or sensitive information? WP Document Revisions was built from the first line of code with government- and enterprise-grade security in mind. Each file is masked behind an anonymous 128-bit [MD5 hash](http://en.wikipedia.org/wiki/MD5) as soon as it touches the server, and requests for files are transparently routed through WordPress's time-tested URL rewriting, authentication, and permission systems (which can even [integrate with existing enterprise active directory](http://wordpress.org/extend/plugins/active-directory-integration/) or [LDAP servers](http://wordpress.org/extend/plugins/simple-ldap-login/)). Need more security? WP Document Revisions allows you to store documents in a folder above the `htdocs` or `public_html` [web root](http://httpd.apache.org/docs/2.0/mod/core.html#documentroot), further ensuring that only those you authorize have access to your work.

**Customization** - WP Document Revisions recognizes that no two teams are identical, and as a result, molds to your firm's needs, not the other way around. Need to track additional information associated with a document? Departments, editors, issues, sections, even arbitrary key-value pairs -- whatever you can throw at it, it can handle. Development and customization costs are further minimized by its extensive plugin API, and the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/) makes it easy for even the uninitiated to add custom  taxonomies to documents. Need an audit trail to track check-ins and check-outs? User-level permissions based on the document's state or another custom taxonomy? Support for third-party encryption? Check out the [WP Document Revisions Code Cookbook](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook) for sample code. Looking for even more advanced control of your workflow? WP Document Revisions will detect the popular workflow plugin [Edit Flow](http://editflow.org), if installed, and will automatically pull [Edit Flow’s advanced workflow management tools](http://ben.balter.com/2011/10/24/advanced-workflow-management-tools-for-wp-document-revisions/) into WP Document Revisions. Simply put, virtually every aspect of the plugin's functionality from workflow states to user-level permissions can be fully customized to your team's unique needs.

**Future Proof** - Switching costs a concern? WP Document Revisions is built with tomorrow's uncertainty in mind. Equally at home in an in-house server room as it is in the cloud, moving individual files or entire document repositories in and out of WP Document Revisions is a breeze (history and all). And since the software is open-source, you can easily add tools to automate the process of moving to or integrating with future third-party systems.

**The Vitals:**

* Support for any file type (docs, spreadsheets, images, PDFs -- anything!)
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
* French language supportt
* Easily translated to your local language

**Features Available via the [Code Cookbook](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook):**

* **Audit Trail** - creates check in / check out audit trail for all documents
* **State Permissions** - allows setting user-level permissions based on a custom taxonomy such as workflow state or other document status
* **Third Party Encryption** - example of how to integrate at rest encryption using third-party tools
* **Rename Documents** - changes all references to "Documents" in the interface to any label of your choosing
* **State Change Notification** - how to use document api to allow users to receive notification whenever documents change workflow state
* **Bulk Import** - how to batch import a directory (or other list) of files as documents
* **Recently Revised Widget** - example of how to list recently revised documents in a widget
* **Filetype Taxonomy** - Adds support to filter by filetype
* **Track Changes** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility
* **Remove Workflow States** - Completely removes Workflow state taxonomy backend and UI
* **Revision Shortcode** - Code sample to demonstrate short code to list revisions
* **Change Tracker** - Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility

*WP Document Revisions was developed by a [law student and a business student](http://ben.balter.com) with a [grant from Google](http://code.google.com/soc/), and in close coordination with and under the watchful eye of WordPress.org's lead developers (Although neither relationship should imply an endorsement). Special thanks to Jon Cave, Aaron Jorbin, Mitcho Erlewine, and Andrew Nacin for their guidance.*

[Photo via [antphotos](http://www.flickr.com/photos/antphotos/3903433061/)]

== Screenshots ==
1. A typical WP Document Revisions edit document screen.

== Installation ==

= Automatic Install =
1. Login to your WordPress site as an Administrator, or if you haven't already, complete the famous [WordPress Five Minute Install](http://codex.wordpress.org/Installing_WordPress)
2. Navigate to Plugins->Add New from the menu on the left
3. Search for WP Document Revisions
4. Click "Install"
5. Click "Activate Now"

= Manual Install =
1. Download the plugin from the link in the top left corner
2. Unzip the file, and upload the resulting "wp-document-revisions" folder to your "/wp-content/plugins directory" as "/wp-content/plugins/wp-document-revisions"
3. Log into your WordPress install as an administrator, and navigate to the plugins screen from the left-hand menu
4. Activate WP Document Revisions

== Frequently Asked Questions ==

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

There are default permissions (based off the default post permissions), but they can be overridden either with third-party plugins such as the [Members plugin](http://wordpress.org/extend/plugins/members/), or for developers, via the <code>document_permissions</code> filter.

= What types of documents can my team collaborate on? =

In short, any. By default, WordPress accepts [most common file types](http://en.support.wordpress.com/accepted-filetypes/), but this can easily by modified to accept just about any file type. In WordPress multisite, the allowed file types are set on the Network Admin page. In non-multisite installs, you can simply install a 3d party plugin to do the same. The only other limitation may be maximum file size, which can be modified in your php.ini file or directly in wp-config.php

= Are the documents I upload secure? =

WP Document Revisions was built from the ground up with security in mind. Each request for a file is run through WordPress's time-tested and proven authentication system (the same system that prevents private or un-published posts from being viewed) and documents filenames are hashed upon upload, thus preventing them from being accessed directly. For additional security, you can move the document upload folder above the web root, (via settings->media->document upload folder). Because WP Document Revisions relies on a custom capability, user permissions can be further refined to prevent certain user roles from accessing certain documents.

= Is there any additional documentation? =

In the top right corner of the edit document screen (where you upload the document or make other changes) and on the document list (where you can search or sort documents), there is a small menu labeled "help". Both should provide some contextual guidance. Additional information may be available on the [WP Document Revisions page](http://ben.balter.com/2011/08/29/document-management-version-control-for-wordpress/).

= What happens if I lose internet connectivity while I have a file checked out? =

WP Document Revisions will "ping" the server every minute to let it know that you have the file open. If for some reason you lose connectivity, the server will give you roughly a two minute grace period before it lifts the file lock. If it's brief (e.g., WiFi disconnected), you should be fine, but if it's for an extended period of time (e.g., a flight), you may find that someone else has checked the file out. You do not need to re-download the file (if no one else has modified it), simply remain on the document page to maintain the file lock.

= Do you have any plans to implement a front end? =
In short, "no", because each site's use would be radically different. Although, you can always link directly to the permalink of any public document, which will always point the latest revision and is available on the document edit screen (right click on the "download" link), or through the add-link wizard when editing a post or page (simply search for the document you want). The long answer, is "it's really easy to adapt a front end to your needs." There are more than 35 document-specific API hooks, and the plugin exposes two global functions, `get_documents()` and `get_document_revisions()`, all of which are designed to allow plugin and theme developers to extend the plugins native functionality (details below). Looking for a slightly more out-of-the-box solution? One site I know of uses a combination of two plugins [count shortcode](http://wordpress.org/extend/plugins/count-shortcode/), which can make a front end to browse documents, especially in coordination with a [faceted search widget](http://wordpress.org/extend/plugins/faceted-search-widget/).

= No really, how do I present documents on the front end? =
A chronological list of all documents a user has access to can be seen at yourdomain.com/documents/. Moreover, because documents are really posts, many built in WordPress features should work and public documents should act similar to posts on the front end (searching, archives, etc.). The plugin comes with a customizable recently revised documents widget, as well as two shortcodes to display documents and document revisions (details below).

= Can WP Document Revisions work in my language? = 
Yes! So far WP Document Revisions has been translated to frencn, and is designed to by fully internationalized.  If you enjoy the plugin and are interested in contributing a translation (it's super easy), please take a look at the [Translating WordPress](http://codex.wordpress.org/Translating_WordPress) page. If you do translate the plugin, please be sure to [contact the plugin author](http://ben.balter.com/contact/) so that it can be included in future releases for other to use.

= Will in work with WordPress MultiSite = 
Yes! Each site can have its own document repository (with the ability to give users different permissions on each repository), or you can create one shared document repository across all sites.

= Will it work over HTTPS (SSL) =
Yes. Just follow the [standard WordPress SSL instructions](http://codex.wordpress.org/Administration_Over_SSL).

= Can I tag my documents? What about categories or some other grouping? =
Yes. You can use the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/) to add taxonomies, or can share your existing taxonomies (e.g., the ones you use for posts) with documents. Just select "custom post type" under "Link To", and enter "document" as the custom post type.

= Can I put my documents in folders? =
WP Document Revisions doesn't use the traditional folder metaphor to organize files. Instead, the same document can be described multiple ways, or in folder terms, be in multiple folders at once. This gives you more control over your documents and how they are organized. You can add a folder taxonomy with the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/). Just select "custom post type" under "Link To", and enter "document" as the custom post type and be sure to select the "Hierarchical" option.

= What if I want even more control over my workflow? =
Take a look at the [Edit Flow Plugin](http://wordpress.org/extend/plugins/edit-flow/) which allows you to set up notifications based on roles, in-line comments, assign all sorts of metadata to posts, create a team calendar, budget, etc. [WP Document Revisions will detect if Edit Flow](http://ben.balter.com/2011/10/24/advanced-workflow-management-tools-for-wp-document-revisions/) is installed and activated, and will adapt accordingly (removing the workflow-state dialogs, registering documents with Edit Flow, etc.). If you're looking for even more control over your team's work flow, using the two plugins in conjunction is the way to go.

= Can I make it so that users can only access documents assigned to them (or documents that they create)? =
Yes. Each document has an "owner" which can be changed from a dialog on the edit-document screen at the time you create it, or later in the process (by default, the document owner is the person that creates it). If the document is marked as private, only users with the read_private_documents capability can access it. Out of the box, this is set to Authors and below, but you can customize things via the  [Members plugin](http://wordpress.org/extend/plugins/members/) (head over to roles after installing).

= How do I use the documents shortcode? =
In a post or page, simply type `[documents]` to display a list of documents. The shortcode accepts *most* [Standard WP_Query parameters](http://codex.wordpress.org/Class_Reference/WP_Query#Parameters) which should allow you to fine tune the output. Parameters are passed in the form of, for example, `[documents numposts="5"]`. Specifically, the shortcode accepts: `author`, `author_name`, `cat`, `category__and`, `category_name`, `day`, `hour`, `meta_compare`, `meta_key`, `meta_value`, `meta_value_num`, `minute`, `monthnum`, `name`, `numberposts`, `order`, `orderby`, `p`, `post_parent`, `post_status`, `second`, `tag`, `tag_id`, `{tax}`, `w`, and `year`.

= How do I use the document revisions shortcode? =
In a post or page, simply type `[document_revisions id="100"]` where ID is the ID of the document for which you would like to list revisions. You can find the ID in the URL of the edit document page. To limit the number of revisions displayed, passed the "number" argument, e.g., to display the 5 most recent revisions `[document_revisions id="100" number="5"]`.

= How do I use the recently revised documents widget? =
Go to your theme's widgets page (if your theme supports widgets), and drag the widget to a sidebar of you choice. Once in a sidebar, you will be presented with options to customize the widget's functionality.

= How do I use the `get_documents` function in my theme or plugin? =
Simply call `get_documents()`. Get documents accepts an array of [Standard WP_Query parameters](http://codex.wordpress.org/Class_Reference/WP_Query#Parameters) as an argument. Use it as you would get_posts. It returns an array of document objects. The `post_content` of each document object is the attachment ID of the revision. `get_permalink()` with that document's ID will also get the proper document permalink (e.g., to link to the document).

= How do I use the `get_document_revisions` function in my theme or plugin? =
Simply call `get_document_revisions( 100 )` where 100 represents the ID of the document you'd like to query. The function returns an array of revisions objects. Each revisions's `post_content` represents the ID of that revisions attachment object. `get_permalink()` should work with that revision's ID to get the revision permalink (e.g., to link to the revision directly).

= I'm a developer, can I contribute? =
Of course. Feel free to [fork the project on GitHub](https://github.com/benbalter/WP-Document-Revisions) and submit your contributions via pull request.

== Changelog ==

= 1.2.3 =
* Owner metabox no longer displays if user does not have the ability to `edit_others_documents`
* Fix for serving documents via SSL to Internet Explorer version 8 and earlier
* GPL License now distributed with plugin
* Code cleanup, minor bug fixes, and additional inline documentation

= 1.2.2 =
* Plugin [posted to Github](https://github.com/benbalter/WP-Document-Revisions) if developers would like to fork and contribute 
* Documents shortcode now accepts additional parameters. See the FAQ for a full list.
* Performance and scalability improvements to backend; files attached to documents are now excluded from media lists by join statements rather than subqueries
* If plugin is unable to locate requested file on server, standard theme's 404 template is served (rather than serving "404 -- file not found" via `wp_die()` previously) and E_USER_NOTICE level error is thrown. Diagnostic information will be available via debug bar (if WP_DEBUG is enabled) or in the standard PHP error log
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
* Addded widget to display recently revised documents (formerly in code cookbook)
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

== Upgrade Notice ==

= 1.2 =
* Widgets, shortcodes, and templating functions, oh my!

= 1.1 =
* Edit Flow support, bug fixes, ui improvements

= 1.0.5 =
* Fixed bug where password-protected documents would not prompt for password under certain circumstances

= 1.0.4 =
* Significant performance improvements, interface improvements, better feed handling, and bug fixes

= 1.0.3 =
* Minor improvements to revision handling, permalinks, permissions, the edit document screen, and front-end compatibility

= 1.0.2 =
* Fix for authentication of RSS feeds in multisite installs

= 1.0.1 =
* Minor fixes and improvements
