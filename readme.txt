=== Plugin Name ===
Contributors: benbalter
Donate link: http://ben.balter.com/
Tags: documents, uploads, attachments, document management, enterprise, version control, revisions, collaboration, journalism, government, files, revision log, document management
Requires at least: 3.2
Tested up to: 3.3
Stable tag: trunk

WordPress Document Revisions – a document management and version control plugin that allows teams of any size to collaboratively edit files

== Description ==

**Google Summer of Code 2011 project. Still under development. Not for production. This will break.**

Please feel free to download and kick the tires. Community feedback is greatly appreciated, but please note that this project is still under development, and is not quite ready for production environments.

Read More in my original post ["When all you have is a pair of bolt cutters…](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/)

--------------------------------

[WP Document Revisions](http://wordpress.org/extend/plugins/wp-document-revisions/) is a [document management](http://en.wikipedia.org/wiki/Document_management_system) and [version control](http://en.wikipedia.org/wiki/Revision_control) plugin. Built for time-sensitive and mission-critical projects, teams can collaboratively edit files of any format -- text documents, spreadsheets, images, sheet music... anything -- all the while, seamlessly tracking the document's progress as it moves through your organization's existing workflow.

**Powerful Collaboration Tools ***With great power does not have to come great complexity. *Based on a simple philosophy of putting powerful but intuitive tools in the hands of managers and content creators, WP Document Revisions leverages many of the essential WordPress features that, for more than eight years, have been tested and proven across countless industries -- posts, attachments, revisions, taxonomies, authentication, and permalinks -- to make collaborating on the creation and publication of documents a natural endeavor. Think of it as an [open-source and more intuitive version](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/) of the popular Microsoft collaboration suite, [Sharepoint.](http://sharepoint.microsoft.com/en-us/Pages/default.aspx)

**Document History **At each step of the authoring process, WP Document Revisions gives you an instant snapshot of your team's progress and the document's history. It even gives you the option to revert back to a previous revision -- so don't fret if you make a mistake -- or receive updates on changes to the document right in your favorite feed reader.

**Access Control **Each document is given a persistent URL (e.g., yourcompany.com/documents/2011/08/TPS-Report.doc) which can be private (securely delivered only to members of your organization), password protected (available only to those you select), or public (published and hosted for the world to see). If you catch a typo and upload a new version, that URL will continue to point to the latest version, regardless of how many changes you make.

**Enterprise Security** Worried about storing propriety or sensitive information? WP Document Revisions was built from the first line of code with government- and enterprise-grade security in mind. Each file is masked behind an anonymous 128-bit [MD5 hash](http://en.wikipedia.org/wiki/MD5) as soon as it touches the server, and requests for files are transparently routed through WordPress's time-tested URL rewriting, authentication, and permission systems (which can even [integrate with existing enterprise active directory](http://wordpress.org/extend/plugins/active-directory-integration/) or [LDAP servers](http://wordpress.org/extend/plugins/simple-ldap-login/)). Need more security? WP Document Revisions allows you to store documents in a folder above the `htdocs` or `public_html` [web root](http://httpd.apache.org/docs/2.0/mod/core.html#documentroot), further ensuring that only those you authorize have access to your work.

**Customization** WP Document Revisions recognizes that no two teams are identical, and as a result, molds to your firm's needs, not the other way around. Need to track additional information associated with a document? Departments, editors, issues, sections, even arbitrary key-value pairs -- whatever you can throw at it, it can handle. Development and customization costs are further minimized by its extensive plugin API, and the [WP Document Revisions Custom Taxonomy Generator](#) makes it easy for even the uninitiated to add custom fields and taxonomies to documents. Simply put, virtually every aspect of the plugin's functionality from workflow states to user-level permissions can be fully customized to your team's unique needs.

**The Vitals:**

* Support for any file type (docs, spreadsheets, images, PDFs -- anything!)
* Securely stores unlimited revisions of your business's essential files
* Provides a full file history in the form of a revision log, accessible via RSS
* Helps you track and organize documents as they move through your organization's existing workflow
* Each file gets a permanent, authenticated URL that always points to the latest version
* Each revision gets its own unique url (e.g.,TPS-Report-revision-3.doc) accessible only to those you deem
* Files are intuitively checked out and locked to prevent revisions from colliding
* Toggle documents between public, private, and password protected with a single mouse click
* Secure: filenames are hashed on upload and files are only accessible through WordPress's proven authentication system
* Can move document upload folder to location outside of web root to further ensure government- and enterprise-grade security
* Easily translated to your local language

Want to give it a try? Simply [download WP Document Revisions](http://wordpress.org/extend/plugins/wp-document-revisions/) from the WordPress Plugin Repository.

*WP Document Revisions was **developed by a [law student and a business student](http://ben.balter.com) **with a [grant from Google](http://code.google.com/soc/),** and in close coordination with and under the watchful eye of WordPress.org's lead developers.*** (Neither relationship should imply a formal endorsement.) **

**Known Issues**

* See the [development backlog](http://gsoc.trac.wordpress.org/query?status=accepted&status=assigned&status=new&status=reopened&component=Document+Revisions&col=id&col=summary&col=status&col=type&col=priority&col=milestone&col=component&order=priority) for a list of known issues

== Screenshots ==
1. A typical WP Document Revisions edit document screen.

== Installation ==

1. Download and Install
1. Upload a new document by clicking "Add Document" under the "Documents" Menu

== Frequently Asked Questions ==

= How do permissions work? =

There are default permissions (based off the default post permissions), but they can be overridden either with third-party plugins such as the [Members plugin](http://wordpress.org/extend/plugins/members/), or for developers, via the <code>document_permissions</code> filter.

= What types of documents can my team collaborate on? =

In short, any. By default, WordPress accepts [most common file types](http://en.support.wordpress.com/accepted-filetypes/), but this can easily by modified to accept just about any file type. In WordPress multisite, the allowed file types are set on the Network Admin page. In non-multisite installs, you can simply install a 3d party plugin to do the same. The only other limitation may be maximum file size, which can be modified in your php.ini file or directly in wp-config.php

= Are the documents I upload secure? =

WP Document Revisions was built from the ground up with security in mind. Each request for a file is run through WordPress's time-tested and proven authentication system (the same system that prevents private or un-published posts from being viewed) and documents filenames are hashed upon upload, thus preventing them from being accessed directly. For additional security, you can move the document upload folder above the web root, (via settings->media->document upload folder). Because WP Document Revisions relies on a custom capability, user permissions can be further refined to prevent certain user roles from accessing certain documents.

== Changelog ==

= 0.5 =
* Initial release
