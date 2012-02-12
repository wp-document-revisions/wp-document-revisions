<?php

class Document_Revisions_Admin {

	static $parent;
	static $instance;

	/**
	 * Register's admin hooks
	 * Note: we are at auth_redirect, first possible hook is admin_menu
	 * @since 0.5
	 */
	function __construct( &$instance = null) {

		self::$instance = &$this;

		//create or store parent instance
		if ( $instance === null )
			self::$parent = new Document_Revisions;
		else
			self::$parent = &$instance;

		//help and messages
		add_filter( 'post_updated_messages', array(&$this, 'update_messages') );
		add_action( 'contextual_help', array(&$this, 'add_help_text'), 10, 3 ); //pre-3.3
		add_action( 'admin_head', array(&$this, 'add_help_tab') ); //3.3+

		//edit document screen
		add_action( 'admin_head', array( &$this, 'make_private' ) );
		add_filter( 'media_meta', array( &$this, 'media_meta_hack'), 10, 1);
		add_filter( 'media_upload_form_url', array( &$this, 'post_upload_handler' ) );
		add_action( 'save_post', array( &$this, 'workflow_state_save' ) );
		add_action( 'admin_init', array( &$this, 'enqueue_edit_scripts' ) );
		add_action( '_wp_put_post_revision', array( &$this, 'revision_filter'), 10, 1 );
		add_filter( 'default_hidden_meta_boxes', array( &$this, 'hide_postcustom_metabox'), 10, 2 );
		add_action( 'admin_footer', array( &$this, 'bind_upload_cb' ) );

		//document list
		add_filter( 'manage_edit-document_columns', array( &$this, 'rename_author_column' ) );
		add_filter( 'manage_edit-document_columns', array( &$this, 'add_workflow_state_column' ) );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'workflow_state_column_cb' ), 10, 2 );
		add_filter( 'manage_edit-document_columns', array( &$this, 'add_currently_editing_column' ), 20 );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'currently_editing_column_cb' ), 10, 2 );

		//settings
		add_action( 'admin_init', array( &$this, 'settings_fields') );
		add_action( 'update_wpmu_options', array( &$this, 'network_upload_location_save' ) );
		add_action( 'wpmu_options', array( &$this, 'network_upload_location_cb' ) );
		add_action( 'network_admin_notices', array( &$this, 'network_settings_errors' ) );
		add_filter( 'wp_redirect', array( &$this, 'network_settings_redirect' ) );

		//profile
		add_action( 'show_user_profile', array( $this, 'rss_key_display' ) );
		add_action( 'personal_options_update', array( &$this, 'profile_update_cb' ) );
		add_action( 'edit_user_profile_update', array( &$this, 'profile_update_cb' ) );

		//Queue up JS
		add_action( 'admin_init', array( &$this, 'enqueue_js' ) );

		//media filters
		add_action( 'admin_init', array( &$this, 'filter_from_media' ) );

		//cleanup
		add_action( 'delete_post', array( &$this, 'delete_attachments_with_document'), 10, 1 );

		//edit flow support
		//note: ef_loaded hook is fired on plugins loaded, far too early
		//we can still remove our hooks, just need to check if edit_flow is a class
		add_action( 'admin_init', array( &$this, 'edit_flow_admin_support' ), 20 );

	}


	/**
	 * Provides support to call functions of the parent class natively
	 * @since 1.0
	 * @param function $function the function to call
	 * @param array $args the arguments to pass to the function
	 * @returns mixed the result of the function
	 */
	function __call( $function, $args ) {

		if ( method_exists( self::$parent, $function ) ) {
			return call_user_func_array( array( &self::$parent, $function ), $args );
		} else {
			//function does not exist, provide error info
			$backtrace = debug_backtrace();
			trigger_error( 'Call to undefined method ' . $function . ' on line ' . $backtrace[1][line] . ' of ' . $backtrace[1][file], E_USER_ERROR );
			die();
		}

	}


	/**
	 * Provides support to call properties of the parent class natively
	 * @since 1.0
	 * @param string $name the property to fetch
	 * @returns mixed the property's value
	 */
	function __get( $name ) {
		return Document_Revisions::$$name;
	}


	/**
	 * Registers update messages
	 * @param array $messages messages array
	 * @returns array messages array with doc. messages
	 * @since 0.5
	 */
	function update_messages( $messages ) {
		global $post, $post_ID;

		$messages['document'] = array(
			1 => sprintf( __( 'Document updated. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink($post_ID) ) ),
			2 => __( 'Custom field updated.', 'wp-document-revisions' ),
			3 => __( 'Custom field deleted.', 'wp-document-revisions' ),
			4 => __( 'Document updated.', 'wp-document-revisions' ),
			5 => isset($_GET['revision']) ? sprintf( __( 'Document restored to revision from %s', 'wp-document-revisions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Document published. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink($post_ID) ) ),
			7 => __( 'Document saved.', 'wp-document-revisions' ),
			8 => sprintf( __( 'Document submitted. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( 'Document scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview document</a>', 'wp-document-revisions' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Document draft updated. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}


	/**
	 * Adds help tabs to 3.3+ help tab API
	 * @since 1.1
	 * @uses get_help_text()
	 */
	function add_help_tab( ) {

		$screen = get_current_screen();

		//the class WP_Screen won't exist pre-3.3
		if ( !class_exists( 'WP_Screen' ) || !$screen || $screen->post_type != 'document' )
			return $screen;

		$help = $this->get_help_text( );

		//loop through each tab in the help array and add
		foreach ( $help as $title => $content ) {
			$screen->add_help_tab( array(
					'title'   => $title,
					'id'      => str_replace( ' ', '_', $title ),
					'content' => $content,
				) );
		}

	}


	/**
	 * Helper function to provide help text as either an array or as a string
	 * @param object $screen the current screen
	 * @param bool $return_array whether to return as an array or string
	 * @returns array|string the help text
	 * @since 1.1
	 */
	function get_help_text( $screen = null, $return_array = true ) {

		if ( $screen == null )
			$screen = get_current_screen();

		//parent key is the id of the current screen
		//child key is the title of the tab
		//value is the help text (as HTML)
		$help = array(
			'document' => array(
				__( 'Basic Usage', 'wp-document-revisions' ) =>
				__( '<p>This screen allows users to collaboratively edit documents and track their revision history. To begin, enter a title for the document, click <code>Upload New Version</code> and select the file from your computer.</p>
										<p>Once successfully uploaded, you can enter a revision log message, assign the document an author, and describe its current workflow state.</p>
										<p>When done, simply click <code>Update</code> to save your changes</p>', 'wp-document-revisions' ),
				__( 'Revision Log', 'wp-document-revisions' ) =>
				__( '<p>The revision log provides a short summary of the changes reflected in a particular revision. Used widely in the open-source community, it provides a comprehensive history of the document at a glance.</p>
										<p>You can download and view previous versions of the document by clicking the timestamp in the revision log. You can also restore revisions by clicking the <code>restore</code> button beside the revision.</p>', 'wp-document-revisions' ),
				__( 'Workflow State', 'wp-document-revisions' ) =>
				__( '<p>The workflow state field can be used to help team members understand at what stage a document sits within a particular organization&quot;s workflow. The field is optional, and can be customized or queried by clicking <code>Workflow States</code> on the left-hand side.</p>', 'wp-document-revisions' ) ,
				__( 'Publishing Documents', 'wp-document-revisions' ) =>
				__( '<p>By default, uploaded documents are only accessible to logged in users. Documents can be published, thus making them accessible to the world, by toggling their visibility in the "Publish" box in the top right corner. Any document marked as published will be accessible to anyone with the proper URL.</p>', 'wp-document-revisions' ),
			),
			'edit-document' => array(
				__( 'Documents', 'wp-document-revisions' ) =>
				__( '<p>Below is a all documents to which you have access. Click the document title to edit the document or download the latest version.</p>
									<p>To add a new document, click <strong>Add Document</strong> on the left-hand side.</p>
									<p>To view all documents at a particular workflow state, click <strong>Workflow States</strong> in the menu on the left.</p>', 'wp-document-revisions' ),
			),
		);

		//if we don't have any help text for this screen, just kick
		if ( !isset( $help[ $screen->id] ) )
			return ( $return_array ) ? array() : '';

		if ( $return_array )
			return apply_filters( 'document_help_array', $help[ $screen->id], $screen );

		//convert array into string for pre-3.3 compatability
		$output = '';
		foreach ( $help[ $screen->id] as $label => $text )
			$output .= '<h4>' . __( $label, 'wp-document-revisions' ) . '</h4>' . __( $text, 'wp-document-revisions' );

		return apply_filters( 'document_help', $output, $screen );

	}


	/**
	 * Registers help text with WP for pre-3.3 versions
	 * @uses get_hel_text()
	 * @since 0.5
	 */
	function add_help_text( $contextual_help, $screen_id, $screen ) {

		if ( isset( $screen->post_type) && $screen->post_type != 'document' )
			return $contextual_help;

		$contextual_help = $this->get_help_text( $screen, false );

		return apply_filters( 'document_help', $contextual_help );
	}


	/**
	 * Callback to manage metaboxes on edit page
	 * @ since 0.5
	 */
	function meta_cb() {

		global $post;

		//remove unused meta boxes
		remove_meta_box( 'revisionsdiv', 'document', 'normal' );
		remove_meta_box( 'postexcerpt', 'document', 'normal' );
		remove_meta_box( 'tagsdiv-workflow_state', 'document', 'side' );

		//add our meta boxes
		add_meta_box( 'revision-summary', __('Revision Summary', 'wp-document-revisions'), array(&$this, 'revision_summary_cb'), 'document', 'normal', 'default' );
		add_meta_box( 'document', __('Document', 'wp-document-revisions'), array(&$this, 'document_metabox'), 'document', 'normal', 'high' );

		if ( $post->post_content != '' )
			add_meta_box('revision-log', 'Revision Log', array( &$this, 'revision_metabox'), 'document', 'normal', 'low' );

		if ( taxonomy_exists( 'workflow_state' ) )
			add_meta_box( 'workflow-state', __('Workflow State', 'wp-document-revisions'), array( &$this, 'workflow_state_metabox_cb'), 'document', 'side', 'default' );

		add_action( 'admin_head', array( &$this, 'admin_css') );

		//move author div to make room for ours
		remove_meta_box( 'authordiv', 'document', 'normal' );
	
		//only add author div if user can give someone else ownership
		if ( current_user_can( 'edit_others_documents' ) )
			add_meta_box( 'authordiv', __('Owner', 'wp-document-revisions'), array( &$this, 'post_author_meta_box' ), 'document', 'side', 'low' );

		//lock notice
		add_action( 'admin_notices', array( &$this, 'lock_notice' ) );

		do_action( 'document_edit' );
	}


	/**
	 * Forces postcustom metabox to be hidden by default, despite the fact that the CPT creates it
	 * @since 1.0
	 * @param array $hidden the default hidden metaboxes
	 * @param array $screen the current screen
	 * @returns array defaults with postcustom
	 */
	function hide_postcustom_metabox( $hidden, $screen ) {

		if ( !$screen->id == 'document' )
			return $hidden;

		$hidden[] = 'postcustom';

		return $hidden;
	}


	/**
	 * Inject CSS into admin head
	 * @since 0.5
	 */
	function admin_css() {
		global $post; ?>
		<style>
			#postdiv {display:none;}
			#lock-notice {background-color: #D4E8BA; border-color: #92D43B; }
			#document-revisions {width: 100%; text-align: left;}
			#document-revisions td {padding: 5px 0 0 0;}
			#workflow-state select {margin-left: 25px; width: 150px;}
			#authordiv select {width: 150px;}
			#lock_override {float:right; text-align: right; margin-top: 10px; padding-bottom: 5px; }
			#revision-summary {display:none;}
			#autosave-alert {display:none;}
			<?php if ( $this->get_document_lock( $post ) ) { ?>
			#publish, #add_media, #lock-notice {display: none;}
			<?php } ?>
			<?php do_action('document_admin_css'); ?>
		</style>
	<?php }


	/**
	 * Metabox to provide common document functions
	 * @param object $post the post object
	 * @since 0.5
	 */
	function document_metabox($post) {?>
		<input type="hidden" id="content" name="content" value="<?php echo esc_attr( $post->post_content) ; ?>" />
		<?php
		if ( $lock_holder = $this->get_document_lock( $post ) ) { ?>
			<div id="lock_override" class="hide-if-no-js"><?php printf( __('%s has prevented other users from making changes.', 'wp-document-revisions'), $lock_holder ); ?>
			<?php if ( current_user_can( 'override_document_lock' ) ) { ?>
				<?php _e('<br />If you believe this is in error you can <a href="#" id="override_link">override the lock</a>, but their changes will be lost.', 'wp-document-revisions'); ?>
			<?php } ?>
			</div>
		<?php } ?>
		<div id="lock_override"><a href='media-upload.php?post_id=<?php echo $post->ID; ?>&TB_iframe=1' id='content-add_media' class='thickbox add_media button' title='Upload Document' onclick='return false;' ><?php _e( 'Upload New Version', 'wp-document-revisions' ); ?></a></div>
		<?php
		$latest_version = $this->get_latest_revision( $post->ID );
		if ( $latest_version ) {
?>
		<p><strong><?php _e( 'Latest Version of the Document', 'wp-document-revisions' ); ?>:</strong>
		<strong><a href="<?php echo get_permalink( $post->ID ); ?>" target="_BLANK"><?php _e( 'Download', 'wp-document-revisions' ); ?></a></strong><br />
			<em><?php printf( __( 'Checked in <abbr class="timestamp" title="%1$s" id="%2$s">%3$s</abbr> ago by %4$s', 'wp-document-revisions' ), $latest_version->post_date, strtotime( $latest_version->post_date ), human_time_diff( (int) get_post_modified_time( 'U', null, $post->ID ), current_time( 'timestamp' ) ), get_the_author_meta( 'display_name', $latest_version->post_author ) ) ?></a></em>
		</p>
		<?php } //end if latest version ?>
		<div class="clear"></div>
		<?php }


	/**
	 * Custom excerpt metabox CB
	 * @since 0.5
	 */
	function revision_summary_cb() { ?>
		<label class="screen-reader-text" for="excerpt"><?php _e('Revision Summary'); ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"></textarea>
		<p><?php _e( 'Revision summaries are optional notes to store along with this revision that allow other users to quickly and easily see what changes you made without needing to open the actual file.', 'wp-document-revisions' ); ?></a></p>
	<?php }


	/**
	 * Creates revision log metabox
	 * @param object $post the post object
	 * @since 0.5
	 */
	function revision_metabox( $post ) {

		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		$revisions = $this->get_revisions( $post->ID );

?>
		<table id="document-revisions">
			<tr class="header">
				<th><?php _e( 'Modified', 'wp-document-revisions'); ?></th>
				<th><?php _e( 'User', 'wp-document-revisions' ); ?></th>
				<th style="width:50%"><?php _e( 'Summary', 'wp-document-revisions' ); ?></th>
				<?php if ( $can_edit_post ) { ?><th><?php _e( 'Actions', 'wp-document-revisions' ); ?></th><?php } ?>
			</tr>
		<?php

		foreach ( $revisions as $revision ) {

			if ( !current_user_can( 'read_post', $revision->ID ) || wp_is_post_autosave( $revision ) )
				continue;
?>
		<tr>
			<td><a href="<?php echo get_permalink( $revision->ID ); ?>" title="<?php echo $revision->post_date; ?>" class="timestamp" id="<?php echo strtotime( $revision->post_date ); ?>"><?php echo human_time_diff( strtotime( $revision->post_date ), current_time('timestamp') ); ?></a></td>
			<td><?php echo get_the_author_meta( 'display_name', $revision->post_author ); ?></td>
			<td><?php echo $revision->post_excerpt; ?></td>
			<?php if ( $can_edit_post ) { ?><td>
				<?php if ( $post->ID != $revision->ID ) { ?>
					<a href="<?php echo wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'action' => 'restore' ), 'revision.php' ), "restore-post_$post->ID|$revision->ID" ); ?>" class="revision"><?php _e( 'Restore', 'wp-document-revisions'); ?></a>
				<?php } ?>
				</td>
			<?php } ?>
	</tr>
		<?php
		}
?>
		</table>
		<?php $key = $this->get_feed_key(); ?>
		<p style="padding-top: 10px;";><a href="<?php echo add_query_arg( 'key', $key, get_permalink( $post->ID ) . '/feed/' ); ?>"><?php _e( 'RSS Feed', 'wp-document-revisions' ); ?></a></p>
		<?php

	}


	/**
	 * Forces autosave to load
	 * By default, if there's a lock on the post, auto save isn't loaded; we want it in case lock is overridden
	 * @since 0.5
	 */
	function enqueue_edit_scripts() {

		if ( !$this->verify_post_type() )
			return;

		wp_enqueue_script( 'autosave' );
		add_thickbox();
		wp_enqueue_script('media-upload');

	}


	/**
	 * Registers the document settings
	 * @since 0.5
	 */
	function settings_fields() {
		register_setting( 'media', 'document_upload_directory', array( &$this, 'sanitize_upload_dir') );
		add_settings_field( 'document_upload_directory', 'Document Upload Directory', array( &$this, 'upload_location_cb' ), 'media', 'uploads' );

	}


	/**
	 * Verifies that upload directory is a valid directory before updating the setting
	 * Attempts to create the directory if it does not exist
	 * @param string $dir path to the new directory
	 * @returns bool|string false on fail, path to new dir on sucess
	 * @since 1.0
	 */
	function sanitize_upload_dir( $dir ) {

		//if the path is not absolute, assume it's relative to ABSPATH
		if ( substr( $dir, 0, 1) != '/' )
			$dir = ABSPATH . $dir;

		//directory does not exist
		if ( !is_dir( $dir ) ) {

			//attempt to make the directory
			if ( !mkdir( $dir ) ) {

				//could not make the directory
				$msg = __( 'Please enter a valid document upload directory.', 'wp-document-revisions' );
				add_settings_error( 'document_upload_directory', 'invalid-document-upload-dir', $msg, 'error' );
				return false;

			}

		}

		//dir didn't change
		if ( $dir == get_option( 'document_upload_directory' ) )
			return $dir;

		//dir changed, throw warning
		$msg = __( 'Document upload directory changed, but existing uploads may need to be moved to the new folder to ensure they remain accessible.', 'wp-document-revisions' );
		add_settings_error( 'document_upload_directory', 'document-upload-dir-change', $msg, 'updated' );

		//trim and return
		return rtrim($dir, "/");

	}


	/**
	 * Adds upload directory option to network admin page
	 * @since 1.0
	 */
	function network_upload_location_cb() { ?>
		<h3><?php _e( 'Document Settings', 'wp-document-revisions'); ?></h3>
		<table id="document_settings" class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Document Upload Directory', 'wp-document-revisions'); ?></th>
				<td>
					<?php $this->upload_location_cb(); ?>
					<?php wp_nonce_field( 'network_documnet_upload_location', 'document_upload_location_nonce' ); ?>
				</td>
			</tr>
		</table>
	<?php }


	/**
	 * Callback to validate and save the network upload directory
	 * @since 1.0
	 */
	function network_upload_location_save() {

		if ( !isset( $_POST['document_upload_location_nonce'] ) )
			return;

		//verify nonce
		if ( !wp_verify_nonce( $_POST['document_upload_location_nonce'], 'network_documnet_upload_location' ) )
			wp_die( __('Not authorized' ) );

		//auth
		if ( !current_user_can( 'manage_network_options' ) )
			wp_die( __('Not authorized' ) );

		//verify upload dir
		$dir = $this->sanitize_upload_dir( $_POST['document_upload_directory'] );

		//becuase there's a redirect, and there's no settings API, force settings errors into a transient
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		//if the dir didn't validate, kick
		if ( !$dir )
			return;

		//save the dir
		update_option( 'document_upload_directory', $dir );

	}


	/**
	 * Adds settings errors to network settings page for document upload directory CB
	 * @since 1.0
	 */
	function network_settings_errors() {
		settings_errors( 'document_upload_directory' );
	}


	/**
	 * Appends the settings-updated query arg to the network admin settings redirect so that the settings API can work
	 * @param string $location the URL being redirected to
	 * @returns string the modified location
	 * @since 1.0
	 */
	function network_settings_redirect( $location ) {

		//Verify redirect string from /wp-admin/network/edit.php line 164
		if ( $location != add_query_arg( 'updated', 'true', network_admin_url( 'settings.php' ) ) )
			return $location;

		//append the settings-updated query arg and return
		return add_query_arg( 'settings-updated', 'true', $location );

	}


	/**
	 * Callback to create the upload location settings field
	 * @since 0.5
	 */
	function upload_location_cb() { ?>
	<input name="document_upload_directory" type="text" id="document_upload_directory" value="<?php echo esc_attr( $this->document_upload_dir() ); ?>" class="regular-text code" />
<span class="description"><?php _e( 'Directory in which to store uploaded documents. The default is in your <code>wp_content/uploads</code> folder, but it may be moved to a folder outside of the <code>htdocs</code> or <code>public_html</code> folder for added security.', 'wp-document-revisions' ); ?></span>
	<?php }


	/**
	 * Callback to inject JavaScript in page after upload is complete (pre 3.3)
	 * @param int $id the ID of the attachment
	 * @since 0.5
	 */
	function post_upload_js( $id ) {

		//get the post object
		$post = get_post( $id );

		//get the extension from the post object to pass along to the client
		$extension = $this->get_file_type( $post );

		//begin output buffer so the javascript can be returned as a string, rather than output directly to the browser
		ob_start();

		?><script>
		var attachmentID = <?php echo (int) $id; ?>;
		var extension = '<?php echo $extension; ?>';
		jQuery(document).ready(function($) { postDocumentUpload( extension, attachmentID ) });
		</script><?php

		//get contents of output buffer
		$js = ob_get_contents();

		//dump output buffer
		ob_end_clean();

		//return javascript
		return $js;
	}


	/**
	 * Binds our post-upload javascript callback to the plupload event
	 * Note: in footer because it has to be called after handler.js is loaded and initialized
	 * @since 1.2.1
	 */
	function bind_upload_cb() {
		global $pagenow;
		if ( $pagenow != 'media-upload.php' )
			return;
?>
		<script>jQuery(document).ready(function(){bindPostDocumentUploadCB()});</script>
	<?php }


	/**
	 * Ugly, Ugly hack to sneak post-upload JS into the iframe *pre 3.3*
	 * If there was a hook there, I wouldn't have to do this
	 * @param string $meta dimensions / post meta
	 * @returns string meta + js to process post
	 * @since 0.5
	 */
	function media_meta_hack( $meta ) {

		if ( !$this->verify_post_type( ) )
			return $meta;

		global $post;
		$latest = $this->get_latest_attachment( $post->ID );

		do_action('document_upload', $latest, $post->ID );

		$meta .= $this->post_upload_js( $latest->ID );

		return $meta;

	}


	/**
	 * Hook to follow file uploads to automate attaching the document to the post
	 * @param string $filter whatever we really should be filtering
	 * @returns string the same stuff they gave us, like we were never here
	 * @since 0.5
	 */
	function post_upload_handler( $filter ) {

		//if we're not posting this is the initial form load, kick
		if ( !$_POST )
			return $filter;

		if ( !$this->verify_post_type ( $_POST['post_id'] ) )
			return $filter;

		//get the object that is our new attachment
		$latest = $this->get_latest_attachment( $_POST['post_id'] );

		do_action('document_upload', $latest, $_POST['post_id'] );

		echo $this->post_upload_js( $latest->ID );

		//prevent hook from fireing a 2nd time
		remove_filter( 'media_meta', array( &$this, 'media_meta_hack'), 10, 1);

		//should probably give this back...
		return $filter;

	}


	/**
	 * Retrieves the most recent file attached to a post
	 * @param int $post_id the parent post
	 * @returns object the attachment object
	 * @since 0.5
	 */
	function get_latest_attachment( $post_id ) {
		$attachments = $this->get_attachments( $post_id );

		return reset( $attachments );
	}


	/**
	 * Callback to display lock notice on top of edit page
	 * @since 0.5
	 */
	function lock_notice() {
		global $post;

		do_action( 'document_lock_notice', $post );

		//if there is no page var, this is a new document, no need to warn
		if ( !isset( $_GET['post'] ) )
			return;

?>
 		<div class="error" id="lock-notice"><p><?php _e( 'You currently have this file checked out. No other user can edit this document so long as you remain on this page.', 'wp-document-revisions' ); ?></p></div>
 	<?php }


	/**
	 * Callback to add RSS key field to profile page
	 * @since 0.5
	 */
	function rss_key_display( ) {
		$key = $this->get_feed_key();
?>
		<div class="tool-box">
		<h3> <?php _e( 'Feed Privacy', 'wp-document-revisions' ); ?></h3>
		<table class="form-table">
			<tr id="document_revisions_feed_key">
				<th><label for="feed_key"><?php _e( 'Secret Feed Key', 'wp-document-revisions' ); ?></label></th>
				<td>
					<input type="text" value="<?php echo esc_attr( $key ); ?>" class="regular-text" readonly="readonly" /><br />
					<span class="description"><?php _e( 'To protect your privacy, you need to append a key to feeds for use in feed readers.', 'wp-document-revisions' ); ?></span><br />
					<?php wp_nonce_field( 'generate-new-feed-key', '_document_revisions_nonce' ); ?>
					<?php submit_button( __( 'Generate New Key', 'wp-document-revisions' ), 'secondary', 'generate-new-feed-key', false ); ?>

				</td>
			</tr>
		</table>
		<?php
	}


	/**
	 * Retrieves feed key user meta; generates if necessary
	 * @since 0.5
	 * @param int $user UserID
	 * @returns string the feed key
	 */
	function get_feed_key( $user = null ) {

		$key = get_user_option( $this->meta_key, $user );

		if ( !$key )
			$key = $this->generate_new_feed_key();

		return $key;

	}


	/**
	 * Generates, saves, and returns new feed key
	 * @param int $user UserID
	 * @returns string feed key
	 * @since 0.5
	 */
	function generate_new_feed_key( $user = null ) {

		if ( !$user )
			$user = get_current_user_id();

		$key = wp_generate_password( $this->key_length, false, false );
		update_user_option( $user, $this->meta_key, $key );

		return $key;
	}


	/**
	 * Callback to handle profile updates
	 * @since 0.5
	 */
	function profile_update_cb() {

		if ( isset( $_POST['generate-new-feed-key'] ) && isset( $_POST['_document_revisions_nonce'] ) && wp_verify_nonce( $_POST['_document_revisions_nonce'], 'generate-new-feed-key' ) )
			$this->generate_new_feed_key();

	}


	/**
	 * Renames author column on document list to "owner"
	 * @param array $defaults the default column labels
	 * @returns array the modified column labels
	 * @since 1.0.4
	 */
	function rename_author_column( $defaults ) {

		if ( isset( $defaults['author'] ) )
			$defaults['author'] = __( 'Owner', 'wp-document-revisions' );

		return $defaults;
	}


	/**
	 * Splices workflow state column as 2nd (3rd) column on documents page
	 * @since 0.5
	 * @param array $defaults the original columns
	 * @returns array our spliced columns
	 */
	function add_workflow_state_column( $defaults ) {

		//get checkbox and title
		$output = array_slice( $defaults, 0, 2 );

		//splice in workflow state
		$output['workflow_state'] = __( 'Workflow State', 'wp-document-revisions' );

		//get the rest of the columns
		$output = array_merge( $output, array_slice( $defaults, 2 ) );

		//return
		return $output;
	}


	/**
	 * Callback to output data for workflow state column
	 * @param string $column_name the name of the column being propegated
	 * @param int $post_id the ID of the post being displayed
	 * @since 0.5
	 */
	function workflow_state_column_cb( $column_name, $post_id ) {

		//verify column
		if ( $column_name != 'workflow_state' )
			return;

		//verify post type
		if ( !$this->verify_post_type( $post_id ) )
			return;

		//get terms
		$state = wp_get_post_terms( $post_id, 'workflow_state' );

		//verify state exists
		if ( sizeof( $state ) == 0)
			return;

		//output (no return)
		echo '<a href="' . esc_url( add_query_arg( 'workflow_state', $state[0]->slug) ) . '">' . $state[0]->name . '</a>';

	}


	/**
	 * Splices in Currently Editing column to document list
	 * @param array $defaults the original columns
	 * @returns array our spliced columns
	 * @since 1.1
	 */
	function add_currently_editing_column( $defaults ) {

		//get checkbox, title, and workflow state
		$output = array_slice( $defaults, 0, 3 );

		//splice in workflow state
		$output['currently_editing'] = __( 'Currently Editing', 'wp-document-revisions' );

		//get the rest of the columns
		$output = array_merge( $output, array_slice( $defaults, 2 ) );

		//return
		return $output;
	}


	/**
	 * Callback to output data for currently editing column
	 * @param string $column_name the name of the column being propegated
	 * @param int $post_id the ID of the post being displayed
	 * @since 1.1
	 */
	function currently_editing_column_cb( $column_name, $post_id ) {

		//verify column
		if ( $column_name != 'currently_editing' )
			return;

		//verify post type
		if ( !$this->verify_post_type( $post_id ) )
			return;

		$lock = $this->get_document_lock( $post_id );

		//output will be display name, if any
		if ( $lock )
			echo $lock;

	}


	/**
	 * Callback to generate metabox for workflow state
	 * @param object $post the post object
	 * @since 0.5
	 */
	function workflow_state_metabox_cb( $post ) {

		wp_nonce_field( plugin_basename( __FILE__ ), 'workflow_state_nonce' );

		$current_state = wp_get_post_terms( $post->ID, 'workflow_state' );
		$states = get_terms( 'workflow_state', array( 'hide_empty'=> false ) );
?>
		<label for="workflow_state"><?php _e( 'Current State', 'wp-document-revisions' ); ?>:</label>
		<select name="workflow_state" id="workflow_state">
			<option></option>
			<?php foreach ( $states as $state ) { ?>
			<option value="<?php echo $state->slug; ?>" <?php if ( $current_state ) selected( $current_state[0]->slug, $state->slug ); ?>><?php echo $state->name; ?></option>
			<?php } ?>
		</select>
		<?php

	}


	/**
	 * Callback to save workflow_state metbox
	 * @param int $post_id the ID of the post being edited
	 * @since 0.5
	 */
	function workflow_state_save( $post_id ) {

		//verify form submit
		if ( !$_POST )
			return;

		//autosave check
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		//verify CPT
		if ( !$this->verify_post_type( $post_id ) )
			return;

		//nonce is a funny word
		if ( !wp_verify_nonce( $_POST['workflow_state_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		//check permissions
		if ( !current_user_can( 'edit_post', $post_id ) )
			return;

		//associate taxonomy with parent, not revision
		if ( wp_is_post_revision( $post_id ) )
			$post_id = wp_is_post_revision( $post_id );

		$old = wp_get_post_terms( $post_id,  'workflow_state', true );

		//no change, keep moving
		if ( $old[0]->term_id == $_POST['workflow_state'] )
			return;

		//all's good, let's save
		wp_set_post_terms( $post_id, array( $_POST['workflow_state'] ), 'workflow_state' );

		do_action( 'change_document_workflow_state', $post_id, $_POST['workflow_state'] );

	}


	/**
	 * Slightly modified document author metabox because the current one is ugly
	 * @since 0.5
	 * @param object $post the post object
	 */
	function post_author_meta_box( $post ) {
		global $user_ID;
?>
		<label class="screen-reader-text" for="post_author_override"><?php _e( 'Owner', 'wp-document-revisions' ); ?></label>
		<?php _e( 'Document Owner', 'wp-document-revisions' ); ?>:
		<?php
		wp_dropdown_users( array(
				'who'              => apply_filters( 'document_revisions_owners', '' ),
				'name'             => 'post_author_override',
				'selected'         => empty($post->ID) ? $user_ID : $post->post_author,
				'include_selected' => true
			) );
	}


	function enqueue_js() {

		//only include JS on document pages
		if ( !$this->verify_post_type() )
			return;

		//translation strings
		$data = array(
			'restoreConfirmation' => __( "Are you sure you want to restore this revision?\n\nIf you do, no history will be lost. This revision will be copied and become the most recent revision.", 'wp-document-revisions'),
			'lockNeedle'          => __( 'is currently editing this'), //purposely left out text domain
			'postUploadNotice'    => __( '<div id="message" class="updated" style="display:none"><p>File uploaded successfully. Add a revision summary below (optional) or press <em>Update</em> to save your changes.</p></div>'),
			'lostLockNotice'      => __('Your lock on the document %s has been overridden. Any changes will be lost.', 'wp-document-revisions' ),
			'lockError'           => __( 'An error has occurred, please try reloading the page.', 'wp-document-revisions' ),
			'lostLockNoticeTitle' => __( 'Lost Document Lock', 'wp-document-revisions' ),
			'lostLockNoticeLogo'  => admin_url('images/logo.gif'),
			'minute'              => __('%d mins', 'wp-document-revisions'),
			'minutes'             => __('%d mins', 'wp-document-revisions' ),
			'hour'                => __('%d hour', 'wp-document-revisions'),
			'hours'               => __('%d hours', 'wp-document-revisions'),
			'day'                 => __('%d day', 'wp-document-revisions'),
			'days'                => __('%d days', 'wp-document-revisions'),
			'offset'              => get_option( 'gmt_offset' ) * 3600,
		);

		$suffix = ( WP_DEBUG ) ? '.dev' : '';
		wp_enqueue_script( 'wp_document_revisions', plugins_url('/js/wp-document-revisions' . $suffix . '.js', dirname( __FILE__ ) ), array('jquery') );
		wp_localize_script( 'wp_document_revisions', 'wp_document_revisions', $data );

	}


	/**
	 * Joins wp_posts on itself so posts can be filter by post_parent's type
	 * @param string $join the original join statement
	 * @return string the modified join statement
	 */
	function filter_media_join( $join ) {
		global $wpdb;

		$join .= " LEFT OUTER JOIN {$wpdb->posts} wpdr_post_parent ON wpdr_post_parent.ID = {$wpdb->posts}.post_parent";

		return $join;

	}


	/*
	 * Exclude children of documents from query
	 * @param string $where the original where statement
	 * @return string the modified where statement
	 */
	function filter_media_where( $where ) {
		global $wpdb;

		//fix for mysql column ambiguity, see http://core.trac.wordpress.org/ticket/19779
		$where = str_replace( ' post_parent < 1', " {$wpdb->posts}.post_parent < 1", $where );

		$where .= " AND ( wpdr_post_parent.post_type IS NULL OR wpdr_post_parent.post_type != 'document' )";

		return $where;

	}


	/**
	 * Filters documents from media galleries
	 * @uses filter_media_where()
	 * @uses filter_media_join()
	 */
	function filter_from_media( ) {

		global $pagenow;

		//verify the page
		if ( $pagenow != 'upload.php' && $pagenow != 'media-upload.php' )
			return;

		//note: hook late so that unnattached filter can hook in, if necessary
		add_filter( 'posts_join_paged', array( &$this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( &$this, 'filter_media_where' ), 20 );

	}


	/**
	 * Requires all document revisions to have attachments
	 * Prevents initial autosave drafts from appearing as a revision after document upload
	 * @param int $id the post id
	 * @since 1.0
	 */
	function revision_filter( $id ) {

		//verify post type
		if ( !$this->verify_post_type( ) )
			return;

		$post = get_post( $id );
		if ( strlen( $post->post_content ) == 0 )
			wp_delete_post( $id, true );

	}


	/**
	 * Deletes all attachments associated with a document or revision
	 * @since 1.0
	 * @param int $postID the id of the deleted post
	 */
	function delete_attachments_with_document( $postID ) {

		if ( !$this->verify_post_type( $postID ) )
			return;

		$post = get_post( $postID );

		if ( is_numeric( $post->post_content ) && get_post( $post->post_content ) )
			wp_delete_attachment( $post->post_content, false );

	}


	/**
	 * Provides support for edit flow and disables the default workflow state taxonomy
	 * @since 1.1
	 */
	function edit_flow_admin_support() {

		if ( !class_exists( 'edit_flow' ) || !apply_filters( 'document_revisions_use_edit_flow', true ) )
			return false;

		remove_filter( 'manage_edit-document_columns', array( &$this, 'add_workflow_state_column' ) );
		remove_action( 'manage_document_posts_custom_column', array( &$this, 'workflow_state_column_cb' ) );
		remove_action( 'save_post', array( &$this, 'workflow_state_save' ) );
		remove_action( 'admin_head', array( &$this, 'make_private' ) );

	}


}
