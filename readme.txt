=== Plugin Name ===
Contributors: benbalter
Donate link: http://ben.balter.com/
Tags: documents, uploads, attachments, document management, enterprise, version control, revisions, collaboration, journalism, government, files, revision log, document management
Requires at least: 3.2
Tested up to: 3.2
Stable tag: 0.5

WordPress Document Revisions – a workflow management and version control system for WordPress

== Description ==

WordPress Document Management – a workflow management and version control system for WordPress building on its existing core competencies. By treating documents as a custom post type, users can leverage the power of WordPress’s extensive attachment, revision, taxonomy, and URL rewriting functionalities. Document permalinks can be routed through the traditional rewrite structure such that the latest revision of a file always remains at a static, authenticated URL, and users can toggle the visibility of documents (both internally and externally) as they currently do with post statuses and permissions. Similarly, file locking can extend WordPress’s autosave functionality (as a ping), revision logs can extend WordPress’s existing revision relationship and can be outputted as a traditional RSS feed, etc.

[Read More in my original post "When all you have is a pair of bolt cutters..."](http://ben.balter.com/2011/04/04/when-all-you-have-is-a-pair-of-bolt-cutters/)

**Google Summer of Code 2011 project. Still under development. Not for production. This will break.**

Please feel free to download and kick the tires. Community feedback is greatly appreciated, but please note that this project is still under development, and is not quite ready for production environments.

**Features**

* Support for any file type (docs, spreadsheets, images, PDFs, etc.)
* Stores unlimited revisions with a revision log message for each
* Each file gets a permanent URL that always points to the latest version of the file
* Each revision gets its own unique url (e.g., my-document-revision-3.doc)
* Toggle documents between public, private, and password protected
* Secure: filenames are hashed on upload and files are only accessible through WordPress's proven authentication system
* Can move document upload folder to location outside of web root to further secure files
* Helps you track and organize documents as they move through your organization's workflow
* RSS Feeds of revisions

**Known Issues**

* See the [development backlog](http://gsoc.trac.wordpress.org/query?status=accepted&status=assigned&status=new&status=reopened&component=Document+Revisions&col=id&col=summary&col=status&col=type&col=priority&col=milestone&col=component&order=priority) for a list of known issues

== Installation ==

1. Download and Install
1. Upload a new document by clicking "Add Document" under the "Documents" Menu

== Changelog ==

= 0.5 =
* Initial release
