## Frequently Asked Questions

### How do I show documents on the front end of my site?

Add the Document Library block, or the `[document_library]` shortcode, to any post or page. It lists documents as a list, a table or a grid of cards, shows only the documents each visitor can read, and lets you pick which details appear, such as workflow state, author and revision count. The plugin also has a block and shortcode for a document's revisions, and a block and widget for recently revised documents. See [Shortcodes and blocks](shortcodes.md).

You can also link straight to any public document. Its permalink always serves the latest revision. Because documents are WordPress posts, a list of the documents each user can see is at yourdomain.com/documents/ by default, and many built-in WordPress features, such as search and archives, work with them.

### Who can see my documents?

Each document has one of three visibilities:

- **Private:** the document's owner, plus logged-in users allowed to read private documents (by default, Editors and Administrators). To limit access by workflow state or another taxonomy, see the taxonomy permissions answer below.
- **Password protected:** anyone with the document's password.
- **Public:** anyone with the document's URL.

### Are the documents I upload secure?

Every request through a document's link goes through WordPress's own login and permission checks, the same ones that keep private and unpublished posts hidden. Uploaded files get hashed names, so nobody can guess a file's address. By default, though, files sit in your uploads folder, where the web server could hand them out directly to anyone who learned a name. For more protection, move the document upload folder outside the web root under **Settings → Media → Document Upload Directory** (on multisite, **Network Admin → Settings**).

### How do permissions work?

Documents have their own capabilities, such as `edit_documents` and `read_private_documents`, which start out matching the built-in roles' permissions for posts (other roles start with subscriber-level access). Change them with a role editor plugin such as [Members](https://wordpress.org/plugins/members/), or in code with the `document_caps` filter.

### Can I make it so that users can only access documents assigned to them, or documents they created?

Yes. Each document has an owner, which you can change on the edit-document screen (by default, the person who created it). A private document can be read only by its owner and by users with the `read_private_documents` capability, which by default only Editors and Administrators have. To keep a role to its own documents, make sure it doesn't have that capability, using a role editor such as [Members](https://wordpress.org/plugins/members/).

### Can I limit access by workflow state, department or another taxonomy?

Yes, with the [taxonomy permissions plugin](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/tree/master/wpdr-taxonomy-permissions) from the Code Cookbook. It adds a "departments" taxonomy (you can point it at a different one) and a capability for each term, so a role needs `edit_document_in_marketing`, say, as well as the usual document capabilities. Assign those capabilities to roles with a plugin like [Members](https://wordpress.org/plugins/members/).

### How does checking out a document work?

Opening a document to edit it checks it out, and nobody else can upload a new version until you're done. While it's checked out, anyone who can read the document can still view and download the current version, and users who can read revisions (by default, Contributors and up) can download earlier ones.

You keep the lock while the document's edit page stays open. WordPress releases it if the page sits idle, with no mouse or keyboard activity, for about an hour. Editors and Administrators can override a lock, and by default the person who held it gets an email.

### What happens if I lose my internet connection while I have a document checked out?

While the page is open, WordPress checks in with the server every minute or two. If the connection drops, the lock lifts about two and a half minutes after the last check-in. A brief Wi-Fi drop is fine. After a long one, such as a flight, someone else may have checked the document out. If nobody else holds the lock when you reconnect, the page takes it back, but check the revision history before uploading, in case someone added a version while you were away.

### Does it track each person's changes?

It records who uploaded each version and when, and asks for a short summary of what changed. For changes inside a file, use the file format's own history, such as tracked changes in Microsoft Word.

### Can it email people when a document changes?

Yes. Turn on notifications under **Settings → Media**, and the plugin emails your list of addresses, plus the document's author, when a document changes workflow state. Emails for new versions are a separate option. Both are off until you turn them on, and the person who made the change never gets an email about it. See [Document Notifications](notifications.md).

### What file types can I upload?

Anything WordPress allows, which covers [the common file types](https://wordpress.org/documentation/article/media-library-screen/) out of the box. On multisite, the network admin sets the allowed types under **Network Admin → Settings**. On a single site, a plugin can add more, and developers can allow extra types for documents only with the `document_allowed_mimes` filter. The other limit is file size, which your host sets in PHP's `upload_max_filesize` and `post_max_size`.

### Does it work on Mac, Windows and mobile?

It works in any modern browser, so Mac, Windows and Linux users can share the same documents. Mobile browsers can view and download documents, but uploading new versions from a phone may not work in every browser.

### Can I put my documents in folders?

Sort of, and with more flexibility. Instead of one folder per file, a document can carry any number of categories or other terms, which works like being in several folders at once. To add a folder-style taxonomy, use [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/): add a taxonomy for the "Documents" post type and set "Hierarchical" to true. The Document Library block can then filter by it.

### Can I tag documents, or group them some other way?

Yes. Documents don't come with any taxonomies, so add your own with [Simple Taxonomy Refreshed](https://wordpress.org/plugins/simple-taxonomy-refreshed/), which can also share your existing post categories and tags with documents.

### What if I want more control over my workflow?

The plugin comes with editable workflow states and email notifications. For custom statuses, install [PublishPress Statuses](https://wordpress.org/plugins/publishpress-statuses/) or [Edit Flow](https://wordpress.org/plugins/edit-flow/). If its statuses are turned on for Documents, WP Document Revisions hands workflow states over to it. Edit Flow also adds editorial comments, a calendar and role-based notifications.

### Will it work with WordPress Multisite?

Yes. Each site has its own document library and permissions. The upload folder is one network-wide setting, and `%site_id%` in it keeps each site's files apart. If you don't network-activate the plugin but still want to set the upload folder for the whole network, use the [network administration plugin](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/blob/master/network-admin.php) from the Code Cookbook.

### Will it work over HTTPS?

Yes. Follow WordPress's [HTTPS instructions](https://developer.wordpress.org/advanced-administration/security/https/).

### Can WP Document Revisions work in my language?

Yes. WordPress installs the plugin's translations automatically as language packs, which volunteers write on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/wp-document-revisions/). If your language isn't finished, you can help with nothing more than a WordPress.org account. See [Translations](translations.md).

### Can I use the block editor (Gutenberg) with documents?

Yes, if you opt in. Documents use the classic editor by default, because its upload screen is built for documents. To switch, add both the `document_show_in_rest` and `document_use_block_editor` filters in your theme's `functions.php` or a small plugin. See [Block Editor Support](block-editor.md), including its known limitations.

### Can I bulk import files that are already on my server?

Yes, with some changes for your setup. Start from the [bulk import script](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook/blob/master/bulk-import.php) in the Code Cookbook.

### What happens when I delete the plugin?

Deleting it from the Plugins screen removes its settings (including notification settings), each user's feed key and the plugin's capabilities from every role. Your documents and their files stay put. On multisite, it cleans up only the site it's deleted from, plus every site's feed keys.

### Where can I find more help?

The **Help** tab at the top right of the document screens explains each one. The [documentation site](https://wp-document-revisions.github.io/wp-document-revisions/) covers everything else, and the [support forum](https://wordpress.org/support/plugin/wp-document-revisions/) is the place to ask.

### Can I contribute?

Please do. WP Document Revisions is open source and runs on volunteer contributions of code, documentation, translations and bug reports. See [How to Contribute](CONTRIBUTING.md).

### I want to change how something works, but there's no setting for it. What can I do?

The plugin keeps its settings short and puts the rest behind [filters](filters.md) and [actions](actions.md), so most changes take a few lines of PHP. The [Code Cookbook](https://github.com/wp-document-revisions/wp-document-revisions-Code-Cookbook) has ready-made examples, such as an audit trail and taxonomy-based permissions.

### How do I list documents in my own theme or plugin code?

Call `get_documents()`. It takes the same arguments as [`WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/#parameters) and, much like `get_posts()`, returns document posts, limited to the ones the current user can read. `get_permalink()` with a document's ID gives the link that always serves its latest version.

For one document's history, call `get_document_revisions( 100 )`, where 100 is the document's ID. It returns the document's revisions, newest first, and `get_permalink()` with a revision's ID links straight to that version. See [Data Structure](data-structure.md) for how documents, revisions and their files are stored.

### What is the Abilities API?

On WordPress 6.9 and later, the plugin registers four document abilities with the WordPress Abilities API: `wp-document-revisions/check-document-access`, `wp-document-revisions/get-document-info`, `wp-document-revisions/get-document-revisions` and `wp-document-revisions/override-document-lock`. REST clients and AI agents can discover and use them, and the `document_register_abilities` filter turns them off.

When the [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) is installed, the three read-only abilities are also available to AI agents over the [Model Context Protocol](https://modelcontextprotocol.io/), with no separate MCP server to run. Every request goes through the same per-document permission checks, so an agent sees only the documents its user can read. The lock override changes data, so it's left out of MCP on purpose.
