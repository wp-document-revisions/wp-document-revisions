=== Plugin Name ===
Contributors: benbalter
Donate link: http://ben.balter.com/
Tags: documents, uploads, attachments, document management, enterprise, version control, revisions, collaboration, journalism, government, files, revision log, document management, intranet
Requires at least: 3.2
Tested up to: 3.3
Stable tag: 1.0

A document management and version control plugin that allows teams of any size to collaboratively edit files and manage their workflow.

== Description ==

[WP Document Revisions](http://wordpress.org/extend/plugins/wp-document-revisions/) is a [document management](http://en.wikipedia.org/wiki/Document_management_system) and [version control](http://en.wikipedia.org/wiki/Revision_control) plugin. Built for time-sensitive and mission-critical projects, teams can collaboratively edit files of any format -- text documents, spreadsheets, images, sheet music... anything -- all the while, seamlessly tracking the document's progress as it moves through your organization's existing workflow.

*Additional information, including a screencast of a typical use case, is available on the [WP Document Revisions page](http://ben.balter.com/2011/08/29/document-management-version-control-for-wordpress/).*

**WP Document Revisions is three things:**

1. A **document management system** (DMS), to track, store, and organize files of any format
2. A **collaboration tool** to empower teams to collaboratively draft, edit, and refine documents
3. A **file hosting solution** to publish and securely deliver files to a team, to clients, or to the public

**Powerful Collaboration Tools** - *With great power does not have to come great complexity.* Based on a simple philosophy of putting powerful but intuitive tools in the hands of managers and content creators, WP Document Revisions leverages many of the essential WordPress features that, for more than eight years, have been tested and proven across countless industries -- posts, attachments, revisions, taxonomies, authentication, and permalinks -- to make collaborating on the creation and publication of documents a natural endeavor. Think of it as an [open-source and more intuitive version](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/) of the popular Microsoft collaboration suite, [Sharepoint.](http://sharepoint.microsoft.com/en-us/Pages/default.aspx)

**Document History** - At each step of the authoring process, WP Document Revisions gives you an instant snapshot of your team's progress and the document's history. It even gives you the option to revert back to a previous revision -- so don't fret if you make a mistake -- or receive updates on changes to the document right in your favorite feed reader.

**Access Control** - Each document is given a persistent URL (e.g., yourcompany.com/documents/2011/08/TPS-Report.doc) which can be private (securely delivered only to members of your organization), password protected (available only to those you select such as clients or contractors), or public (published and hosted for the world to see). If you catch a typo and upload a new version, that URL will continue to point to the latest version, regardless of how many changes you make.

**Enterprise Security** - Worried about storing propriety or sensitive information? WP Document Revisions was built from the first line of code with government- and enterprise-grade security in mind. Each file is masked behind an anonymous 128-bit [MD5 hash](http://en.wikipedia.org/wiki/MD5) as soon as it touches the server, and requests for files are transparently routed through WordPress's time-tested URL rewriting, authentication, and permission systems (which can even [integrate with existing enterprise active directory](http://wordpress.org/extend/plugins/active-directory-integration/) or [LDAP servers](http://wordpress.org/extend/plugins/simple-ldap-login/)). Need more security? WP Document Revisions allows you to store documents in a folder above the `htdocs` or `public_html` [web root](http://httpd.apache.org/docs/2.0/mod/core.html#documentroot), further ensuring that only those you authorize have access to your work.

**Customization** - WP Document Revisions recognizes that no two teams are identical, and as a result, molds to your firm's needs, not the other way around. Need to track additional information associated with a document? Departments, editors, issues, sections, even arbitrary key-value pairs -- whatever you can throw at it, it can handle. Development and customization costs are further minimized by its extensive plugin API, and the [WP Document Revisions Custom Field Generator](http://wordpress.org/extend/plugins/wp-document-revisions-custom-taxonomy-and-field-generator/) makes it easy for even the uninitiated to add custom fields and taxonomies to documents. Need an audit trail to track check-ins and check-outs? User-level permissions based on the document's state or another custom taxonomy? Support for third-party encryption? Check out the [WP Document Revisions Code Cookbook](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook) for sample code. Simply put, virtually every aspect of the plugin's functionality from workflow states to user-level permissions can be fully customized to your team's unique needs.

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
* Easily translated to your local language

*WP Document Revisions was developed by a [law student and a business student](http://ben.balter.com) with a [grant from Google](http://code.google.com/soc/), and in close coordination with and under the watchful eye of WordPress.org's lead developers (Although neither relationship should imply an endorsement). Special thanks to Jon Cave, Aaron Jorbin, Mitcho Erlewine, and Andrew Nacin for their guidance.*

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

= I keep getting a "file not found" every time I try to download a document, what gives? =

Make sure you have [pretty permalinks](http://codex.wordpress.org/Using_Permalinks#Using_.22Pretty.22_permalinks) enabled, and that other posts are available via the domain.com/yyyy/mm/post/ format (rather than domain.com/?p=1234).

= What happens if I lose internet connectivity while I have a file checked out? =

WP Document Revisions will "ping" the server every minute to let it know that you have the file open. If for some reason you lose connectivity, the server will give you roughly a two minute grace period before it lifts the file lock. If it's brief (e.g., WiFi disconnected), you should be fine, but if it's for an extended period of time (e.g., a flight), you may find that someone else has checked the file out. You do not need to re-download the file (if no one else has modified it), simply remain on the document page to maintain the file lock.

= Do you have any plans to implement a front end? =

In short, "no", because everyone's use would be different. Although, you can always link directly to the permalink of any public document, which will always point the latest revision. The long answer, is "it's really easy to adapt a front end to your needs." One site I know of uses a combination of two plugins [count shortcode](http://wordpress.org/extend/plugins/count-shortcode/), which can make a front end to browse documents, especially in coordination with a [faceted search widget](http://wordpress.org/extend/plugins/faceted-search-widget/). Because documents are really posts, many built in WordPress features should work based on the query. You can modify it with a handful of filters or URL changes, e.g., <code>?post_type=document</code>. Also of note, the [code cookbook](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook) has an example of how to implement a [revision shortcode](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook/blob/master/revision-shortcode.php).

= Can WP Document Revisions work in my language? = 
Yes! WP Document Revisions has not been translated *yet*, but is designed to by fully internationalized.  If you enjoy the plugin and are interested in contributing a translation (it's super easy), please take a look at the [Translating WordPress](http://codex.wordpress.org/Translating_WordPress) page. If you do translate the plugin, please be sure to [contact the plugin author](http://ben.balter.com/contact/) so that it can be included in future releases for other to use.

= Will in work with WordPress MultiSite = 
Yes! Each site can have its own document repository (with the ability to give users different permissions on each repository), or you can create one shared document repository across all sites.

= Will it work over HTTPS (SSL) =
Yes. Just follow the [standard WordPress SSL instructions](http://codex.wordpress.org/Administration_Over_SSL).

= Can I tag my documents? =
Yes. You can use the [WP Document Revisions Custom Field Generator](http://wordpress.org/extend/plugins/wp-document-revisions-custom-taxonomy-and-field-generator/) to add tags, or can share your existing tags (e.g., the ones you use for posts) with documents.

= Can I put my documents in folders? =
WP Document Revisions doesn't use the traditional folder metaphor to organize files. Instead, the same document can be described multiple ways, or in folder terms, be in multiple folders at once. This gives you more control over your documents and how they are organized. You can add a folder taxonomy with the [WP Document Revisions Custom Field Generator](http://wordpress.org/extend/plugins/wp-document-revisions-custom-taxonomy-and-field-generator/). Be sure to select the "Hierarchical (like categories)" option.

== Changelog ==

= 1.0.1 =
* Better handling of uploads in WordPress versions 3.3 and above
* Added shadow to document menu icon (thanks to Ryan Imel of WPCandy.com)
* Fixed E_WARNING level error for undefined index on workflow_state_nonce when saving posts with WP_DEBUG on
* Corrected typos in contextual help dropdown
* Fixed permission issue where published documents were not accessible to non-logged in users

= 1.0 =
* Stable Release

= 0.6 =
* Release Candidate 1
* [Revision Log](http://gsoc.trac.wordpress.org/log/2011/BenBalter)

= 0.5 =
* Initial release
