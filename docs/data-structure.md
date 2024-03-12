# WP-Documents-Revisions Data Design and Data Structure

## Requirements

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

## Data Structure

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
