## Frequently Asked Questions

### I'm a user/developer/administrator... can I contribute?

Of course. Please! WP Document Revisions is an open source project and is supported by the efforts of an entire community. We'd love for you to get involved. Whatever your level of skill or however much time you can give, your contribution is greatly appreciated. Check out the "[How to Contribute" page](https://github.com/benbalter/WP-Document-Revisions/wiki/How-to-Contribute) for more information.

### Does it work on Mac? PC? Mobile?

WP Document Revisions should work on just about any system with a browser. You can easily collaborate between, Mac, PC, and even Linux systems. Mobile browsers, such as iOS or Android should be able to download files, but may not be able to upload new versions in all cases.

### What are the different levels of visibility?

Each document can have one of three "visibilities":

* Private - visible only to logged in users (this can be further refined either based on users or based on the document's status)
* Password Protected - Non-logged in users can view files, but they will require a document-specific password
* Public - Anyone with the document's URL can download and view the file

### How many people can access a document at a time?

A virtually unlimited number of people can *view* a document at the same time, but only one user can *edit* a document at a time.

### While a file is "checked out" can others view it? What about a previous versions?

Yes.

### Is there a time limit for checking out a file?

No. So long as the user remains on the document page (it's okay if the window is minimized, etc.), the user will retain the file lock. By default, administrators can override this lock at any time. The origin lock-holder will receive a notification.

### Does it keep track of each individual's changes?

Yes and no. It will track who uploaded each version of the file, and will provide an opportunity to describe those changes. For more granular history, the plugin is designed to work with a format's unique history features, such as tracked changes in Microsoft Word.

### How do permissions work?

There are default permissions (based off the default post permissions), but they can be overridden either with third-party plugins such as the [Members plugin](http://wordpress.org/extend/plugins/members/), or for developers, via the <code>document_permissions</code> filter.

### What types of documents can my team collaborate on?

In short, any. By default, WordPress accepts [most common file types](http://en.support.wordpress.com/accepted-filetypes/), but this can easily by modified to accept just about any file type. In WordPress multisite, the allowed file types are set on the Network Admin page. In non-multisite installs, you can simply install a 3d party plugin to do the same. The only other limitation may be maximum file size, which can be modified in your php.ini file or directly in wp-config.php

### Are the documents I upload secure?

WP Document Revisions was built from the ground up with security in mind. Each request for a file is run through WordPress's time-tested and proven authentication system (the same system that prevents private or un-published posts from being viewed) and documents filenames are hashed upon upload, thus preventing them from being accessed directly. For additional security, you can move the document upload folder above the web root, (via settings->media->document upload folder). Because WP Document Revisions relies on a custom capability, user permissions can be further refined to prevent certain user roles from accessing certain documents.

### Is there any additional documentation?

In the top right corner of the edit document screen (where you upload the document or make other changes) and on the document list (where you can search or sort documents), there is a small menu labeled "help". Both should provide some contextual guidance. Additional information may be available on the [WP Document Revisions page](http://ben.balter.com/2011/08/29/document-management-version-control-for-wordpress/).

### What happens if I lose internet connectivity while I have a file checked out?

WP Document Revisions will "ping" the server every minute to let it know that you have the file open. If for some reason you lose connectivity, the server will give you roughly a two minute grace period before it lifts the file lock. If it's brief (e.g., WiFi disconnected), you should be fine, but if it's for an extended period of time (e.g., a flight), you may find that someone else has checked the file out. You do not need to re-download the file (if no one else has modified it), simply remain on the document page to maintain the file lock.

### Do you have any plans to implement a front end?

In short, "no", because each site's use would be radically different. Although, you can always link directly to the permalink of any public document, which will always point the latest revision and is available on the document edit screen (right click on the "download" link), or through the add-link wizard when editing a post or page (simply search for the document you want). The long answer, is "it's really easy to adapt a front end to your needs." There are more than 35 document-specific API hooks, and the plugin exposes two global functions, `get_documents()` and `get_document_revisions()`, all of which are designed to allow plugin and theme developers to extend the plugins native functionality (details below). Looking for a slightly more out-of-the-box solution? One site I know of uses a combination of two plugins [count shortcode](http://wordpress.org/extend/plugins/count-shortcode/), which can make a front end to browse documents, especially in coordination with a [faceted search widget](http://wordpress.org/extend/plugins/faceted-search-widget/).

### No really, how do I present documents on the front end?

A chronological list of all documents a user has access to can be seen at yourdomain.com/documents/. Moreover, because documents are really posts, many built in WordPress features should work and public documents should act similar to posts on the front end (searching, archives, etc.). The plugin comes with a customizable recently revised documents widget, as well as two shortcodes to display documents and document revisions (details below).

### Can WP Document Revisions work in my language?

Yes! So far WP Document Revisions has been translated to French and Spanish, and is designed to by fully internationalized. If you enjoy the plugin and are interested in contributing a translation (it's super easy), please take a look at the [Translating WordPress](http://codex.wordpress.org/Translating_WordPress) page and the plugin's [translations repository](http://translations.benbalter.com/projects/wp-document-revisions/). If you do translate the plugin, please be sure to [contact the plugin author](http://ben.balter.com/contact/) so that it can be included in future releases for other to use.

### Will in work with WordPress MultiSite

Yes! Each site can have its own document repository (with the ability to give users different permissions on each repository), or you can create one shared document repository across all sites.

### Will it work over HTTPS (SSL)

Yes. Just follow the [standard WordPress SSL instructions](http://codex.wordpress.org/Administration_Over_SSL).

### Can I tag my documents? What about categories or some other grouping?

Yes. You can use the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/) to add taxonomies, or can share your existing taxonomies (e.g., the ones you use for posts) with documents. Just select "custom post type" under "Link To", and enter "document" as the custom post type.

### Can I put my documents in folders?

WP Document Revisions doesn't use the traditional folder metaphor to organize files. Instead, the same document can be described multiple ways, or in folder terms, be in multiple folders at once. This gives you more control over your documents and how they are organized. You can add a folder taxonomy with the [WordPress Custom Taxonomy Generator](http://themergency.com/generators/wordpress-custom-taxonomy/). Just select "custom post type" under "Link To", and enter "document" as the custom post type and be sure to select the "Hierarchical" option.

### What if I want even more control over my workflow?

Take a look at the [Edit Flow Plugin](http://wordpress.org/extend/plugins/edit-flow/) which allows you to set up notifications based on roles, in-line comments, assign all sorts of metadata to posts, create a team calendar, budget, etc. [WP Document Revisions will detect if Edit Flow](http://ben.balter.com/2011/10/24/advanced-workflow-management-tools-for-wp-document-revisions/) is installed and activated, and will adapt accordingly (removing the workflow-state dialogs, registering documents with Edit Flow, etc.). If you're looking for even more control over your team's work flow, using the two plugins in conjunction is the way to go.

### Can I make it so that users can only access documents assigned to them (or documents that they create)?

Yes. Each document has an "owner" which can be changed from a dialog on the edit-document screen at the time you create it, or later in the process (by default, the document owner is the person that creates it). If the document is marked as private, only users with the read_private_documents capability can access it. Out of the box, this is set to Authors and below, but you can customize things via the [Members plugin](http://wordpress.org/extend/plugins/members/) (head over to roles after installing).

### How do I use the documents shortcode?

In a post or page, simply type `[documents]` to display a list of documents. The shortcode accepts *most* [Standard WP_Query parameters](http://codex.wordpress.org/Class_Reference/WP_Query#Parameters) which should allow you to fine tune the output. Parameters are passed in the form of, for example, `[documents numberposts="5"]`. Specifically, the shortcode accepts: `author`, `author_name`, `cat`, `category__and`, `category_name`, `day`, `hour`, `meta_compare`, `meta_key`, `meta_value`, `meta_value_num`, `minute`, `monthnum`, `name`, `numberposts`, `order`, `orderby`, `p`, `post_parent`, `post_status`, `second`, `tag`, `tag_id`, `{tax}`, `w`, and `year`.

If you're using a custom taxonomy, you can add the taxonomy name as a parameter in your shortcode. For example, if your custom taxonomy is called "document_categories", you can write insert a shortcode like this:

`[documents numberposts="6" document_categories="category-name"]`

(Where "category-name" is the taxonomy's slug)

### How do I use the document revisions shortcode?

In a post or page, simply type `[document_revisions id="100"]` where ID is the ID of the document for which you would like to list revisions. You can find the ID in the URL of the edit document page. To limit the number of revisions displayed, passed the "number" argument, e.g., to display the 5 most recent revisions `[document_revisions id="100" number="5"]`.

### How do I use the recently revised documents widget?

Go to your theme's widgets page (if your theme supports widgets), and drag the widget to a sidebar of you choice. Once in a sidebar, you will be presented with options to customize the widget's functionality.

### How do I use the `get_documents` function in my theme or plugin?

Simply call `get_documents()`. Get documents accepts an array of [Standard WP_Query parameters](http://codex.wordpress.org/Class_Reference/WP_Query#Parameters) as an argument. Use it as you would get_posts. It returns an array of document objects. The `post_content` of each document object is the attachment ID of the revision. `get_permalink()` with that document's ID will also get the proper document permalink (e.g., to link to the document).

### How do I use the `get_document_revisions` function in my theme or plugin?

Simply call `get_document_revisions( 100 )` where 100 represents the ID of the document you'd like to query. The function returns an array of revisions objects. Each revisions's `post_content` represents the ID of that revisions attachment object. `get_permalink()` should work with that revision's ID to get the revision permalink (e.g., to link to the revision directly).

### Can I set the upload directory on multisite installs if I don't want to network activate the plugin?

Yes. There's a plugin in the [WP Document Revisions Code Cookbook](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook) to help with that. Just install and network activate.

### Can I limit access to documents based on workflow state, department, or some other custom taxonomy?

Yes. Download (and optionally customize) the [taxonomy permissions](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook/blob/master/taxonomy-permissions.php) plugin from the [Code Cookbook](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook). Out of the box, it will register a "departments" taxonomy (which can be easily changed at the top of the file, if you want to limit access by a different taxonomy), and will create additional permissions based on that taxonomy's terms using WordPress's built-in capabilities system. So for example, instead simply looking at `edit_document` to determine permissions, it will also look at `edit_document_in_marketing`, for example. You can create additional roles and assign capabilities using a plugin like [Members](http://wordpress.org/extend/plugins/members/).

### Is it possible to do a bulk import of existing documents / files already on the server?

Yes. It will need to be slightly customized to meet your needs, but take a look at the [Bulk Import Script](https://github.com/benbalter/WP-Document-Revisions-Code-Cookbook/blob/master/bulk-import.php) in the code cookbook.

### I have some strange upload problems, but the files are still there somehow.

If you assign custom roles to your users, make sure you add the `edit_posts` capability to any user that can upload files. See bugs [#8234](http://core.trac.wordpress.org/ticket/8234) and [#21091](http://core.trac.wordpress.org/ticket/21091)
