<?php
/**
 * WP Document Revisions Admin Functionality
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package WP Document Revisions
 */

/**
 * The WP Admin backend object
 */
class WP_Document_Revisions_Admin {

	/**
	 * The parent WP Document Revisions instance
	 *
	 * @var $parent
	 */
	public static $parent;

	/**
	 * The singelton instance
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Register's admin hooks
	 * Note: we are at auth_redirect, first possible hook is admin_menu
	 *
	 * @since 0.5
	 * @param unknown $instance (optional, reference)
	 */
	public function __construct( &$instance = null ) {

		self::$instance = &$this;

		// create or store parent instance
		if ( null === $instance ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = &$instance;
		}

		// help and messages
		add_filter( 'post_updated_messages', array( &$this, 'update_messages' ) );
		add_action( 'admin_head', array( &$this, 'add_help_tab' ) ); // 3.3+

		// edit document screen
		add_action( 'admin_head', array( &$this, 'make_private' ) );
		add_action( 'save_post', array( &$this, 'workflow_state_save' ) );
		add_action( 'admin_init', array( &$this, 'enqueue_edit_scripts' ) );
		add_action( '_wp_put_post_revision', array( &$this, 'revision_filter' ), 10, 1 );
		add_filter( 'default_hidden_meta_boxes', array( &$this, 'hide_postcustom_metabox' ), 10, 2 );
		add_action( 'admin_footer', array( &$this, 'bind_upload_cb' ) );
		add_action( 'admin_head', array( &$this, 'hide_upload_header' ) );

		// document list
		add_filter( 'manage_document_posts_columns', array( &$this, 'rename_author_column' ) );
		add_filter( 'manage_document_posts_columns', array( &$this, 'add_workflow_state_column' ) );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'workflow_state_column_cb' ), 10, 2 );
		add_filter( 'manage_document_posts_columns', array( &$this, 'add_currently_editing_column' ), 20 );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'currently_editing_column_cb' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( &$this, 'filter_documents_list' ) );
		add_filter( 'parse_query', array( &$this, 'convert_id_to_term' ) );

		// settings
		add_action( 'admin_init', array( &$this, 'settings_fields' ) );
		add_action( 'update_wpmu_options', array( &$this, 'network_upload_location_save' ) );
		add_action( 'update_wpmu_options', array( &$this, 'network_slug_save' ) );
		add_action( 'wpmu_options', array( &$this, 'network_settings_cb' ) );
		add_action( 'network_admin_notices', array( &$this, 'network_settings_errors' ) );
		add_filter( 'wp_redirect', array( &$this, 'network_settings_redirect' ) );

		// profile
		add_action( 'show_user_profile', array( $this, 'rss_key_display' ) );
		add_action( 'personal_options_update', array( &$this, 'profile_update_cb' ) );
		add_action( 'edit_user_profile_update', array( &$this, 'profile_update_cb' ) );

		// Queue up JS
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue' ) );

		// media filters
		add_action( 'admin_init', array( &$this, 'filter_from_media' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_from_media_grid' ) );

		// cleanup
		add_action( 'delete_post', array( &$this, 'delete_attachments_with_document' ), 10, 1 );

		// edit flow support
		add_action( 'admin_init', array( &$this, 'disable_workflow_states' ), 20 );

		// admin css
		add_filter( 'admin_body_class', array( &$this, 'admin_body_class_filter' ) );

		// admin dashboard
		add_action( 'wp_dashboard_setup', array( &$this, 'setup_dashboard' ) );

	}


	/**
	 * Provides support to call functions of the parent class natively
	 *
	 * @since 1.0
	 * @param function $function the function to call
	 * @param array    $args the arguments to pass to the function
	 * @returns mixed the result of the function
	 */
	public function __call( $function, $args ) {
		return call_user_func_array( array( &self::$parent, $function ), $args );
	}


	/**
	 * Provides support to call properties of the parent class natively
	 *
	 * @since 1.0
	 * @param string $name the property to fetch
	 * @returns mixed the property's value
	 */
	public function __get( $name ) {
		return WP_Document_Revisions::$$name;
	}


	/**
	 * Registers update messages
	 *
	 * @since 0.5
	 * @param array $messages messages array
	 * @returns array messages array with doc. messages
	 */
	public function update_messages( $messages ) {
		global $post, $post_id;

		$messages['document'] = array(
			// translators: %s is the download link
				1 => sprintf( __( 'Document updated. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink( $post_id ) ) ),
			2 => __( 'Custom field updated.', 'wp-document-revisions' ),
			3 => __( 'Custom field deleted.', 'wp-document-revisions' ),
			4 => __( 'Document updated.', 'wp-document-revisions' ),
			// translators: %s is the revision ID
			// @codingStandardsIgnoreLine WordPress.Security.NonceVerification.NoNonceVerification
				5 => isset( $_GET['revision'] ) ? sprintf( __( 'Document restored to revision from %s', 'wp-document-revisions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			// translators: %s is the download link
				6 => sprintf( __( 'Document published. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink( $post_id ) ) ),
			7 => __( 'Document saved.', 'wp-document-revisions' ),
			// translators: %s is the download link
				8 => sprintf( __( 'Document submitted. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
			// translators: %1$s is the date, %2$s is the preview link
				9 => sprintf( __( 'Document scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview document</a>', 'wp-document-revisions' ), date_i18n( sprintf( _x( '%1$s @ %2$s', '%1$s: date; %2$s: time', 'wp-document-revision' ), get_option( 'date_format' ), get_option( 'time_format' ) ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ) ),
			// translators: %s is the link to download the document
				10 => sprintf( __( 'Document draft updated. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
		);

		return $messages;
	}


	/**
	 * Adds help tabs to 3.3+ help tab API
	 *
	 * @since 1.1
	 * @uses get_help_text()
	 * @return void
	 */
	public function add_help_tab() {

		$screen = get_current_screen();

		// loop through each tab in the help array and add
		foreach ( $this->get_help_text() as $title => $content ) {
			$screen->add_help_tab(
				array(
					'title'   => $title,
					'id'      => str_replace( ' ', '_', $title ),
					'content' => $content,
				)
			);
		}
	}


	/**
	 * Helper function to provide help text as either an array or as a string
	 *
	 * @since 1.1
	 * @param object $screen (optional) the current screen
	 * @param bool   $return_array (optional) whether to return as an array or string
	 * @returns array|string the help text
	 */
	public function get_help_text( $screen = null, $return_array = true ) {

		if ( is_null( $screen ) ) {
			$screen = get_current_screen();
		}

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML)
		$help = array(
			'document' => array(
				__( 'Basic Usage', 'wp-document-revisions' ) =>
				'<p>' . __( 'This screen allows users to collaboratively edit documents and track their revision history. To begin, enter a title for the document, click <code>Upload New Version</code> and select the file from your computer.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'Once successfully uploaded, you can enter a revision log message, assign the document an author, and describe its current workflow state.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'When done, simply click <code>Update</code> to save your changes', 'wp-document-revisions' ) . '</p>',
				__( 'Revision Log', 'wp-document-revisions' ) =>
				'<p>' . __( 'The revision log provides a short summary of the changes reflected in a particular revision. Used widely in the open-source community, it provides a comprehensive history of the document at a glance.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'You can download and view previous versions of the document by clicking the timestamp in the revision log. You can also restore revisions by clicking the <code>restore</code> button beside the revision.', 'wp-document-revisions' ) . '</p>',
				__( 'Workflow State', 'wp-document-revisions' ) =>
				'<p>' . __( 'The workflow state field can be used to help team members understand at what stage a document sits within a particular organization&quot;s workflow. The field is optional, and can be customized or queried by clicking <code>Workflow States</code> on the left-hand side.', 'wp-document-revisions' ) . '</p>',
				__( 'Publishing Documents', 'wp-document-revisions' ) =>
				'<p>' . __( 'By default, uploaded documents are only accessible to logged in users. Documents can be published, thus making them accessible to the world, by toggling their visibility in the "Publish" box in the top right corner. Any document marked as published will be accessible to anyone with the proper URL.', 'wp-document-revisions' ) . '</p>',
			),
			'edit-document' => array(
				__( 'Documents', 'wp-document-revisions' ) =>
				'<p>' . __( 'Below is a list of all documents to which you have access. Click the document title to edit the document or download the latest version.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'To add a new document, click <strong>Add Document</strong> on the left-hand side.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'To view all documents at a particular workflow state, click <strong>Workflow States</strong> in the menu on the left.', 'wp-document-revisions' ) . '</p>',
			),
		);

		// if we don't have any help text for this screen, just kick
		if ( ! isset( $help[ $screen->id ] ) ) {
			return ( $return_array ) ? array() : '';
		}

		if ( $return_array ) {
			return apply_filters( 'document_help_array', $help[ $screen->id ], $screen );
		}

		return apply_filters( 'document_help', $output, $screen );

	}

	/**
	 * Callback to manage metaboxes on edit page
	 * @ since 0.5
	 */
	public function meta_cb() {

		global $post;

		// remove unused meta boxes
		remove_meta_box( 'revisionsdiv', 'document', 'normal' );
		remove_meta_box( 'postexcerpt', 'document', 'normal' );
		remove_meta_box( 'tagsdiv-workflow_state', 'document', 'side' );

		// add our meta boxes
		add_meta_box( 'revision-summary', __( 'Revision Summary', 'wp-document-revisions' ), array( &$this, 'revision_summary_cb' ), 'document', 'normal', 'default' );
		add_meta_box( 'document', __( 'Document', 'wp-document-revisions' ), array( &$this, 'document_metabox' ), 'document', 'normal', 'high' );

		if ( '' !== $post->post_content ) {
			add_meta_box( 'revision-log', __( 'Revision Log', 'wp-document-revisions' ), array( &$this, 'revision_metabox' ), 'document', 'normal', 'low' );
		}

		if ( taxonomy_exists( 'workflow_state' ) && ! $this->disable_workflow_states() ) {
			add_meta_box( 'workflow-state', __( 'Workflow State', 'wp-document-revisions' ), array( &$this, 'workflow_state_metabox_cb' ), 'document', 'side', 'default' );
		}

		// move author div to make room for ours
		remove_meta_box( 'authordiv', 'document', 'normal' );

		// only add author div if user can give someone else ownership
		if ( current_user_can( 'edit_others_documents' ) ) {
			add_meta_box( 'authordiv', __( 'Owner', 'wp-document-revisions' ), array( &$this, 'post_author_meta_box' ), 'document', 'side', 'low' );
		}

		// lock notice
		add_action( 'admin_notices', array( &$this, 'lock_notice' ) );

		do_action( 'document_edit' );
	}


	/**
	 * Forces postcustom metabox to be hidden by default, despite the fact that the CPT creates it
	 *
	 * @since 1.0
	 * @param array $hidden the default hidden metaboxes
	 * @param array $screen the current screen
	 * @returns array defaults with postcustom
	 */
	public function hide_postcustom_metabox( $hidden, $screen ) {
		if ( 'document' === $screen->id ) {
			$hidden[] = 'postcustom';
		}

		return $hidden;
	}


	/**
	 * Filter the admin body class to add additional classes which we can use conditionally
	 * to style the page (e.g., when the document is locked
	 *
	 * @param String $body_class the existing body class(es)
	 */
	public function admin_body_class_filter( $body_class ) {

		global $post;

		if ( ! $this->verify_post_type() ) {
			return $body_class;
		}

		$body_class .= ' document';

		if ( $this->get_document_lock( $post ) ) {
			$body_class .= ' document-locked';
		}

		return $body_class;
	}


	/**
	 * Hide header (gallery, URL, library, etc.) links from media-upload
	 * No real use case for a revision being an already uploaded file,
	 * and javascript not compatible for that usecase as written
	 *
	 * @since 1.2.4
	 */
	public function hide_upload_header() {

		global $pagenow;

		// @codingStandardsIgnoreLine WordPress.Security.NonceVerification.NoNonceVerification
		if ( 'media-upload.php' === $pagenow && $this->verify_post_type( $_GET['post_id'] ) ) { ?>
			<style>
				#media-upload-header {display:none;}
			</style>
			<?php
		}
	}


	/**
	 * Metabox to provide common document functions
	 *
	 * @since 0.5
	 * @param object $post the post object
	 */
	public function document_metabox( $post ) {
		?>
		<input type="hidden" id="content" name="content" value="<?php echo esc_attr( $post->post_content ); ?>" />
		<?php
		$lock_holder = $this->get_document_lock( $post );
		if ( $lock_holder ) {
			?>
			<div id="lock_override" class="hide-if-no-js">
			<?php
			// translators: %s is the user that holds the document lock
			printf( esc_html__( '%s has prevented other users from making changes.', 'wp-document-revisions' ), esc_html( $lock_holder ) );
			if ( current_user_can( 'override_document_lock' ) ) {
				// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
				_e( '<br />If you believe this is in error you can <a href="#" id="override_link">override the lock</a>, but their changes will be lost.', 'wp-document-revisions' );
			}
			?>
			</div>
		<?php } ?>
		<div id="lock_override">
		<?php $latest_version = $this->get_latest_revision( $post->ID ); ?>
			<a href="media-upload.php?post_id=<?php echo intval( $post->ID ); ?>&TB_iframe=1" id="content-add_media" class="thickbox add_media button" title="<?php esc_attr_e( 'Upload Document', 'wp-document-revisions' ); ?>" onclick="return false;" >
				<?php esc_html_e( 'Upload New Version', 'wp-document-revisions' ); ?>
			</a>
		</div>
		<?php
		// @codingStandardsIgnoreStart WordPress.XSS.EscapeOutput.OutputNotEscaped
		if ( $latest_version = $this->get_latest_revision( $post->ID ) ) { ?>
		<p>
			<strong><?php esc_html_e( 'Latest Version of the Document', 'wp-document-revisions' ); ?>:</strong>
			<strong><a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_BLANK"><?php esc_html_e( 'Download', 'wp-document-revisions' ); ?></a></strong><br />
			<em><?php printf( __( 'Checked in <abbr class="timestamp" title="%1$s" id="%2$s">%3$s</abbr> ago by %4$s', 'wp-document-revisions' ), $latest_version->post_date, strtotime( $latest_version->post_date ), human_time_diff( (int) get_post_modified_time( 'U', null, $post->ID ), current_time( 'timestamp' ) ), get_the_author_meta( 'display_name', $latest_version->post_author ) ) ?></a></em>
		</p>
		<?php
			} //end if latest version
			// @codingStandardsIgnoreEnd WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
		<div class="clear"></div>
		<?php
	}


	/**
	 * Custom excerpt metabox CB
	 *
	 * @since 0.5
	 */
	public function revision_summary_cb() {
		?>
		<label class="screen-reader-text" for="excerpt"><?php esc_html_e( 'Revision Summary' ); ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"></textarea>
		<p><?php esc_html_e( 'Revision summaries are optional notes to store along with this revision that allow other users to quickly and easily see what changes you made without needing to open the actual file.', 'wp-document-revisions' ); ?></a></p>
		<?php
	}


	/**
	 * Creates revision log metabox
	 *
	 * @since 0.5
	 * @param object $post the post object
	 */
	public function revision_metabox( $post ) {

		$can_edit_post = current_user_can( 'edit_document', $post->ID );
		$revisions = $this->get_revisions( $post->ID );
		$key = $this->get_feed_key();
		?>
		<table id="document-revisions">
			<tr class="header">
				<th><?php esc_html_e( 'Modified', 'wp-document-revisions' ); ?></th>
				<th><?php esc_html_e( 'User', 'wp-document-revisions' ); ?></th>
				<th style="width:50%"><?php esc_html_e( 'Summary', 'wp-document-revisions' ); ?></th>
				<?php
				if ( $can_edit_post ) {
					?>
<th><?php esc_html_e( 'Actions', 'wp-document-revisions' ); ?></th><?php } ?>
			</tr>
		<?php

		foreach ( $revisions as $revision ) {

			if ( ! current_user_can( 'read_post', $revision->ID ) || wp_is_post_autosave( $revision ) ) {
				continue;
			}
			// preserve original file extension on revision links.
			// this will prevent mime/ext security conflicts in IE when downloading.
			if ( is_numeric( $revision->post_content ) ) {
				$fn = get_post_meta( $revision->post_content, '_wp_attached_file', true );
				$fno = pathinfo( $fn, PATHINFO_EXTENSION );
				$info = pathinfo( get_permalink( $revision->ID ) );
				$fn = $info['dirname'] . '/' . $info['filename'] . '.' . $fno;
			} else {
				$fn = get_permalink( $revision->ID );
			}
			?>
			<tr>
				<td><a href="<?php echo esc_url( $fn ); ?>" title="<?php echo esc_attr( $revision->post_date ); ?>" class="timestamp" id="<?php echo esc_attr( strtotime( $revision->post_date ) ); ?>"><?php echo esc_html( human_time_diff( strtotime( $revision->post_date ), current_time( 'timestamp' ) ) ); ?></a></td>
				<td><?php echo esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ); ?></td>
				<td><?php echo esc_html( $revision->post_excerpt ); ?></td>
				<?php if ( $can_edit_post && $post->ID !== $revision->ID ) { ?>
					<td><a href="
					<?php
					echo esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'revision' => $revision->ID,
									'action' => 'restore',
								),
								'revision.php'
							),
							"restore-post_$revision->ID"
						)
					);
					?>
				" class="revision"><?php esc_html_e( 'Restore', 'wp-document-revisions' ); ?></a></td>
				<?php } ?>
			</tr>
			<?php
		}
		?>
		</table>
		<p style="padding-top: 10px;"><a href="<?php echo esc_url( add_query_arg( 'key', $key, get_post_comments_feed_link( $post->ID ) ) ); ?>"><?php esc_html_e( 'RSS Feed', 'wp-document-revisions' ); ?></a></p>
		<?php
	}


	/**
	 * Forces autosave to load
	 * By default, if there's a lock on the post, auto save isn't loaded; we want it in case lock is overridden
	 *
	 * @since 0.5
	 */
	public function enqueue_edit_scripts() {

		if ( ! $this->verify_post_type() ) {
			return;
		}

		wp_enqueue_script( 'autosave' );
		add_thickbox();
		wp_enqueue_script( 'media-upload' );
	}


	/**
	 * Registers the document settings
	 *
	 * @since 0.5
	 */
	public function settings_fields() {
		register_setting( 'media', 'document_upload_directory', array( &$this, 'sanitize_upload_dir' ) );
		register_setting( 'media', 'document_slug', array( &$this, 'sanitize_document_slug' ) );
		add_settings_field( 'document_upload_directory', __( 'Document Upload Directory', 'wp-document-revisions' ), array( &$this, 'upload_location_cb' ), 'media', 'uploads' );
		add_settings_field( 'document_slug', __( 'Document Slug', 'wp-document-revisions' ), array( &$this, 'document_slug_cb' ), 'media', 'uploads' );
	}


	/**
	 * Verifies that upload directory is a valid directory before updating the setting
	 * Attempts to create the directory if it does not exist
	 *
	 * @since 1.0
	 * @param string $dir path to the new directory
	 * @returns bool|string false on fail, path to new dir on sucess
	 */
	public function sanitize_upload_dir( $dir ) {

		// empty string passed
		if ( '' === $dir ) {
			return $this->document_upload_dir();
		}

		// if the path is not absolute, assume it's relative to ABSPATH
		if ( '/' !== substr( $dir, 0, 1 ) ) {
			$dir = ABSPATH . $dir;
		}

		// dir didn't change
		if ( $this->document_upload_dir() === $dir ) {
			return $dir;
		}

		// don't fire more than once
		if ( ! get_settings_errors( 'document_upload_directory' ) ) {
			// dir changed, throw warning
			add_settings_error( 'document_upload_directory', 'document-upload-dir-change', __( 'Document upload directory changed, but existing uploads may need to be moved to the new folder to ensure they remain accessible.', 'wp-document-revisions' ), 'updated' );
		}

		// clear cache so that it can be repopulated with new value
		$this->clear_document_dir_cache();

		// trim and return
		return rtrim( $dir, '/' );
	}


	/**
	 * Sanitize slug prior to saving
	 *
	 * @param string $slug new slug
	 * @return string sanitized slug
	 */
	public function sanitize_document_slug( $slug ) {

		$slug = sanitize_title( $slug, 'documents' );

		// unchanged
		if ( $slug === $this->document_slug() ) {
			return $slug;
		}

		// new slug isn't yet stored
		// but queue up a rewrite rule flush to ensure slug takes effect on next request
		add_action( 'shutdown', 'flush_rewrite_rules' );

		add_settings_error( 'document_slug', 'document-slug-change', __( 'Document slug changed, but some previously published URLs may now be broken.', 'wp-document-revisions' ), 'updated' );

		return $slug;
	}


	/**
	 * Adds upload directory and document slug options to network admin page
	 *
	 * @since 1.0
	 */
	public function network_settings_cb() {
		?>
		<h3><?php esc_html_e( 'Document Settings', 'wp-document-revisions' ); ?></h3>
		<table id="document_settings" class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Document Upload Directory', 'wp-document-revisions' ); ?></th>
				<td>
					<?php $this->upload_location_cb(); ?>
					<?php wp_nonce_field( 'network_document_upload_location', 'document_upload_location_nonce' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Document Slug', 'wp-document-revisions' ); ?></th>
				<td>
					<?php $this->document_slug_cb(); ?>
					<?php wp_nonce_field( 'network_document_slug', 'document_slug_nonce' ); ?>
				</td>
			</tr>
		</table>
		<?php
	}


	/**
	 * Callback to validate and save the network upload directory
	 *
	 * @since 1.0
	 */
	public function network_upload_location_save() {

		if ( ! isset( $_POST['document_upload_location_nonce'] ) ) {
			return;
		}

		// verify nonce, auth
		if ( ! wp_verify_nonce( $_POST['document_upload_location_nonce'], 'network_document_upload_location' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify upload dir
		$dir = $this->sanitize_upload_dir( $_POST['document_upload_directory'] );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the dir is valid, save it
		if ( $dir ) {
			update_site_option( 'document_upload_directory', $dir );
			$this->clear_document_dir_cache();
		}
	}


	/**
	 * Callback to validate and save slug on network settings page
	 */
	public function network_slug_save() {

		if ( ! isset( $_POST['document_slug_nonce'] ) ) {
			return;
		}

		// verify nonce, auth
		if ( ! wp_verify_nonce( $_POST['document_slug_nonce'], 'network_document_slug' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify upload dir
		$slug = $this->sanitize_document_slug( $_POST['document_slug'] );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the dir is valid, save it
		if ( $slug ) {
			update_site_option( 'document_slug', $slug );
		}
	}


	/**
	 * Adds settings errors to network settings page for document upload directory CB
	 *
	 * @since 1.0
	 */
	public function network_settings_errors() {
		settings_errors( 'document_upload_directory' );
		settings_errors( 'document_slug' );
	}


	/**
	 * Appends the settings-updated query arg to the network admin settings redirect so that the settings API can work
	 *
	 * @since 1.0
	 * @param string $location the URL being redirected to
	 * @returns string the modified location
	 */
	public function network_settings_redirect( $location ) {

		// Verify redirect string from /wp-admin/network/edit.php line 164
		if ( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php' ) ) === $location ) {
			// append the settings-updated query arg and return
			$location = add_query_arg( 'settings-updated', 'true', $location );
		}

		return $location;
	}


	/**
	 * Callback to create the upload location settings field
	 *
	 * @since 0.5
	 */
	public function upload_location_cb() {
		?>
		<input name="document_upload_directory" type="text" id="document_upload_directory" value="<?php echo esc_attr( $this->document_upload_dir() ); ?>" class="large-text code" /><br />
		<span class="description">
		<?php
			// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
			_e( 'Directory in which to store uploaded documents. The default is in your <code>wp_content/uploads</code> folder (or another default uploads folder defined elsewhere), but it may be moved to a folder outside of the <code>htdocs</code> or <code>public_html</code> folder for added security.', 'wp-document-revisions' ); ?></span>
		<?php if ( is_multisite() ) : ?>
		<span class="description">
			<?php
			// @codingStandardsIgnoreStart WordPress.XSS.EscapeOutput.OutputNotEscaped
			// translators: %site_id% is not interpolated and should not be translated
			_e( 'You may optionally include the string <code>%site_id%</code> within the path to separate files by site.', 'wp-document-revisions' );
			// @codingStandardsIgnoreEnd WordPress.XSS.EscapeOutput.OutputNotEscaped
			?>
		</span>
			<?php
		endif;
	}


	/**
	 * Callback to create the document slug settings field
	 */
	public function document_slug_cb() {
		?>
	<code><?php bloginfo( 'url' ); ?>/<input name="document_slug" type="text" id="document_slug" value="<?php echo esc_attr( $this->document_slug() ); ?>" class="medium-text" />/<?php echo esc_html( date( 'Y' ) ); ?>/<?php echo esc_html( date( 'm' ) ); ?>/<?php esc_html_e( 'example-document-title', 'wp-document-revisions' ); ?>.txt</code><br />
	<span class="description">
		<?php
		// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
		_e( '"Slug" with which to prefix all URLs for documents (and the document archive). Default is <code>documents</code>.', 'wp-document-revisions' ); ?></span>
		<?php
	}


	/**
	 * Callback to inject JavaScript in page after upload is complete (pre 3.3)
	 *
	 * @since 0.5
	 * @param int $id the ID of the attachment
	 * @return unknown
	 */
	public function post_upload_js( $id ) {

		// get the post object
		$document = get_post( $id );

		// begin output buffer so the javascript can be returned as a string, rather than output directly to the browser
		ob_start();

		?>
		<script>
		var attachmentID = <?php echo (int) $id; ?>;
		var extension = '<?php echo esc_js( $this->get_file_type( $document ) ); ?>';
		jQuery(document).ready(function($) { postDocumentUpload( extension, attachmentID ) });
		</script>
		<?php

		// get contents of output buffer
		$js = ob_get_contents();

		// dump output buffer
		ob_end_clean();

		// return javascript
		return $js;
	}


	/**
	 * Binds our post-upload javascript callback to the plupload event
	 * Note: in footer because it has to be called after handler.js is loaded and initialized
	 *
	 * @since 1.2.1
	 */
	public function bind_upload_cb() {
		global $pagenow;

		if ( 'media-upload.php' === $pagenow ) :
			?>
		<script>jQuery(document).ready(function(){bindPostDocumentUploadCB()});</script>
			<?php
		endif;
	}


	/**
	 * Retrieves the most recent file attached to a post
	 *
	 * @since 0.5
	 * @param int $post_id the parent post
	 * @returns object the attachment object
	 */
	public function get_latest_attachment( $post_id ) {
		$attachments = $this->get_attachments( $post_id );

		return reset( $attachments );
	}


	/**
	 * Callback to display lock notice on top of edit page
	 *
	 * @since 0.5
	 */
	public function lock_notice() {
		global $post;

		do_action( 'document_lock_notice', $post );

		// if there is no page var, this is a new document, no need to warn
		// @codingStandardsIgnoreLine WordPress.Security.NonceVerification.NoNonceVerification
		if ( isset( $_GET['post'] ) ) :
			?>
		<div class="error" id="lock-notice"><p><?php esc_html_e( 'You currently have this file checked out. No other user can edit this document so long as you remain on this page.', 'wp-document-revisions' ); ?></p></div>
			<?php
			endif;
	}


	/**
	 * Callback to add RSS key field to profile page
	 *
	 * @since 0.5
	 */
	public function rss_key_display() {
		$key = $this->get_feed_key();
		?>
		<div class="tool-box">
		<h2><?php esc_html_e( 'Feed Privacy', 'wp-document-revisions' ); ?></h2>
		<table class="form-table">
			<tr id="document_revisions_feed_key">
				<th><label for="feed_key"><?php esc_html_e( 'Secret Feed Key', 'wp-document-revisions' ); ?></label></th>
				<td>
					<input type="text" value="<?php echo esc_attr( $key ); ?>" class="regular-text" readonly="readonly" /><br />
					<span class="description"><?php esc_html_e( 'To protect your privacy, you need to append a key to feeds for use in feed readers.', 'wp-document-revisions' ); ?></span><br />
					<?php wp_nonce_field( 'generate-new-feed-key', '_document_revisions_nonce' ); ?>
					<?php submit_button( __( 'Generate New Key', 'wp-document-revisions' ), 'secondary', 'generate-new-feed-key', false ); ?>

				</td>
			</tr>
		</table>
		<?php
	}


	/**
	 * Retrieves feed key user meta; generates if necessary
	 *
	 * @since 0.5
	 * @param int $user (optional) UserID
	 * @returns string the feed key
	 */
	public function get_feed_key( $user = null ) {

		$key = get_user_option( $this->meta_key, $user );

		if ( ! $key ) {
			$key = $this->generate_new_feed_key();
		}

		return $key;
	}


	/**
	 * Generates, saves, and returns new feed key
	 *
	 * @since 0.5
	 * @param int $user (optional) UserID
	 * @returns string feed key
	 */
	public function generate_new_feed_key( $user = null ) {

		if ( ! $user ) {
			$user = get_current_user_id();
		}

		$key = wp_generate_password( $this->key_length, false, false );
		update_user_option( $user, $this->meta_key, $key );

		return $key;
	}


	/**
	 * Callback to handle profile updates
	 *
	 * @since 0.5
	 */
	public function profile_update_cb() {

		if ( isset( $_POST['generate-new-feed-key'] ) && isset( $_POST['_document_revisions_nonce'] ) && wp_verify_nonce( $_POST['_document_revisions_nonce'], 'generate-new-feed-key' ) ) {
			$this->generate_new_feed_key();
		}
	}

	/**
	 * Allow some filtering of the All Documents list
	 */
	public function filter_documents_list() {
		global $typenow, $wp_query;
		// Only applies to document post type
		if ( 'document' === $typenow ) {

			// Filter by workflow state/edit flow state
			$tax_slug = 'workflow_state';
			if ( $this->disable_workflow_states() ) {
				$tax_slug = EF_Custom_Status::taxonomy_key;
			}
			$args = array(
				'name' => 'workflow_state',
				'show_option_all' => __( 'All workflow states', 'wp-document-revisions' ),
				'hide_empty' => false,
				'taxonomy' => $tax_slug,
			);

			// set selected workflow state
			if ( isset( $wp_query->query[ $tax_slug ] ) ) {
				$term_id = $wp_query->query[ $tax_slug ];
				if ( ! is_numeric( $term_id ) && '0' !== $term_id ) {
					$term = get_term_by( 'slug', $wp_query->query[ $tax_slug ], $tax_slug );
					$term_id = $term->term_id;
				}
				$args['selected'] = $term_id;
				wp_dropdown_categories( $args );
			} else {
				if ( taxonomy_exists( 'workflow_state' ) && ! $this->disable_workflow_states() ) {
					wp_dropdown_categories( $args );
				}
			}

			// author/owner filtering
			$args = array(
				'name' => 'author',
				'show_option_all' => __( 'All owners', 'wp-document-revisions' ),
			);
		// @codingStandardsIgnoreStart WordPress.Security.NonceVerification.NoNonceVerification
		if ( isset( $_GET['author'] ) ) {
				$args['selected'] = $_GET['author'];
			}
		// @codingStandardsIgnoreEnd WordPress.Security.NonceVerification.NoNonceVerification
			wp_dropdown_users( $args );
		}
	}

	/**
	 * Converts id to term used in filter dropdown
	 *
	 * @param Object $query the WP_Query object
	 */
	public function convert_id_to_term( $query ) {
		global $pagenow, $typenow;
		if ( 'edit.php' === $pagenow && 'document' === $typenow ) {
			$tax_slug = 'workflow_state';
			if ( $this->disable_workflow_states() ) {
				$tax_slug = EF_Custom_Status::taxonomy_key;
			}
			$var = &$query->query_vars[ $tax_slug ];
			if ( isset( $var ) && is_numeric( $var ) && '0' !== $var ) {
				$term = get_term_by( 'id', $var, $tax_slug );
				$var = $term->slug;
			}
		}
	}

	/**
	 * Renames author column on document list to "owner"
	 *
	 * @since 1.0.4
	 * @param array $defaults the default column labels
	 * @returns array the modified column labels
	 */
	public function rename_author_column( $defaults ) {

		if ( isset( $defaults['author'] ) ) {
			$defaults['author'] = __( 'Owner', 'wp-document-revisions' );
		}

		return $defaults;
	}


	/**
	 * Splices workflow state column as 2nd (3rd) column on documents page
	 *
	 * @since 0.5
	 * @param array $defaults the original columns
	 * @returns array our spliced columns
	 */
	public function add_workflow_state_column( $defaults ) {

		// get checkbox and title
		$output = array_slice( $defaults, 0, 2 );

		// splice in workflow state
		$output['workflow_state'] = __( 'Workflow State', 'wp-document-revisions' );

		// get the rest of the columns
		$output = array_merge( $output, array_slice( $defaults, 2 ) );

		return $output;
	}


	/**
	 * Callback to output data for workflow state column
	 *
	 * @since 0.5
	 * @param string $column_name the name of the column being propegated
	 * @param int    $post_id the ID of the post being displayed
	 */
	public function workflow_state_column_cb( $column_name, $post_id ) {

		// verify column
		if ( 'workflow_state' === $column_name && $this->verify_post_type( $post_id ) ) {

			// get terms
			$state = wp_get_post_terms( $post_id, 'workflow_state' );

			// verify state exists
			if ( 0 === count( $state ) ) {
				return;
			}

			// give the workflow state output (but with no return)
			echo '<a href="' . esc_url( add_query_arg( 'workflow_state', $state[0]->slug ) ) . '">' . esc_html( $state[0]->name ) . '</a>';
		}
	}


	/**
	 * Splices in Currently Editing column to document list
	 *
	 * @since 1.1
	 * @param array $defaults the original columns
	 * @returns array our spliced columns
	 */
	public function add_currently_editing_column( $defaults ) {

		// get checkbox, title, and workflow state
		$output = array_slice( $defaults, 0, 3 );

		// splice in workflow state
		$output['currently_editing'] = __( 'Currently Editing', 'wp-document-revisions' );

		// get the rest of the columns
		$output = array_merge( $output, array_slice( $defaults, 2 ) );

		return $output;
	}


	/**
	 * Callback to output data for currently editing column
	 *
	 * @since 1.1
	 * @param string $column_name the name of the column being propegated
	 * @param int    $post_id the ID of the post being displayed
	 */
	public function currently_editing_column_cb( $column_name, $post_id ) {

		// verify column
		if ( 'currently_editing' === $column_name && $this->verify_post_type( $post_id ) ) {

			// output will be display name, if any
			$lock = $this->get_document_lock( $post_id );
			if ( $lock ) {
				echo esc_html( $lock );
			}
		}
	}


	/**
	 * Callback to generate metabox for workflow state
	 *
	 * @since 0.5
	 * @param object $post the post object
	 */
	public function workflow_state_metabox_cb( $post ) {

		wp_nonce_field( 'wp-document-revisions', 'workflow_state_nonce' );

		$current_state = wp_get_post_terms(
			$post->ID,
			'workflow_state'
		);
		$states = get_terms(
			'workflow_state',
			array( 'hide_empty' => false )
		);
		?>
		<label for="workflow_state"><?php esc_html_e( 'Current State', 'wp-document-revisions' ); ?>:</label>
		<select name="workflow_state" id="workflow_state">
			<option></option>
			<?php foreach ( $states as $state ) { ?>
			<option value="<?php echo esc_attr( $state->slug ); ?>"
				<?php
				if ( $current_state ) {
					selected( $current_state[0]->slug, $state->slug );}
				?>
><?php echo esc_html( $state->name ); ?></option>
			<?php } ?>
		</select>
		<?php

	}


	/**
	 * Callback to save workflow_state metabox
	 *
	 * @since 0.5
	 * @param int $doc_id the ID of the post being edited
	 */
	public function workflow_state_save( $doc_id ) {

		// autosave check
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// verify CPT
		if ( ! $this->verify_post_type( $doc_id ) ) {
			return;
		}

		// verify nonce
		if ( ! isset( $_POST['workflow_state_nonce'] ) || ! wp_verify_nonce( $_POST['workflow_state_nonce'], 'wp-document-revisions' ) ) {
			return;
		}

		// check permissions
		if ( ! current_user_can( 'edit_document', $doc_id ) ) {
			return;
		}

		// associate taxonomy with parent, not revision
		if ( wp_is_post_revision( $doc_id ) ) {
			$doc_id = wp_is_post_revision( $doc_id );
		}

		// if document has featured image loaded with document, then make sure it has no parent
		// done after we make sure that we have doc_id being parent
		global $wpdb;
		$thumb = get_post_meta( $doc_id, '_thumbnail_id', true );
		if ( $thumb > 0 ) {
			// @codingStandardsIgnoreStart WordPress.DB.PreparedSQL.NotPrepared
			$post_table = "{$wpdb->prefix}posts";
			$sql = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_parent` = 0 WHERE `id` = %d AND `post_parent` = %d ",
				$thumb,
				$doc_id
			);
			$res = $wpdb->query( $sql );
			// @codingStandardsIgnoreEnd WordPress.DB.PreparedSQL.NotPrepared
		}

		$old = wp_get_post_terms( $doc_id, 'workflow_state', true );

		// no change, keep moving
		if ( isset( $old[0] ) && $old[0]->slug === $_POST['workflow_state'] ) {
			return;
		}

		// all's good, let's save
		wp_set_post_terms( $doc_id, array( $_POST['workflow_state'] ), 'workflow_state' );

		do_action( 'change_document_workflow_state', $doc_id, $_POST['workflow_state'] );
	}


	/**
	 * Slightly modified document author metabox because the current one is ugly
	 *
	 * @since 0.5
	 * @param object $post the post object
	 */
	public function post_author_meta_box( $post ) {
		global $user_id;
		?>
		<label class="screen-reader-text" for="post_author_override"><?php esc_html_e( 'Owner', 'wp-document-revisions' ); ?></label>
		<?php esc_html_e( 'Document Owner', 'wp-document-revisions' ); ?>:
		<?php
		wp_dropdown_users(
			array(
				'who'              => apply_filters( 'document_revisions_owners', '' ),
				'name'             => 'post_author_override',
				'selected'         => empty( $post->ID ) ? $user_id : $post->post_author,
				'include_selected' => true,
			)
		);
	}


	/**
	 * Back Compat
	 */
	public function enqueue_js() {
		_deprecated_function( __FUNCTION__, '1.3.2 of WP Document Revisions', 'enqueue' );
		$this->enqueue();

	}


	/**
	 * Enqueue admin JS and CSS files
	 */
	public function enqueue() {

		// only include JS on document pages
		if ( ! $this->verify_post_type() ) {
			return;
		}

		// translation strings
		$data = array(
			'restoreConfirmation' => __( 'Are you sure you want to restore this revision? If you do, no history will be lost. This revision will be copied and become the most recent revision.', 'wp-document-revisions' ),
			'lockNeedle'          => __( 'is currently editing this' ), // purposely left out text domain
			'postUploadNotice'    => '<div id="message" class="updated" style="display:none"><p>' . __( 'File uploaded successfully. Add a revision summary below (optional) or press <strong>Update</strong> to save your changes.', 'wp-document-revisions' ) . '</p></div>',
			'postDesktopNotice'   => '<div id="message" class="update-nag" style="display:none"><p>' . __( 'After you have saved your document in your office software, <a href="#" onClick="location.reload();">reload this page</a> to see your changes.', 'wp-document-revisions' ) . '</p></div>',
			// translators: %s is the title of the document
			'lostLockNotice'      => __( 'Your lock on the document %s has been overridden. Any changes will be lost.', 'wp-document-revisions' ),
			'lockError'           => __( 'An error has occurred, please try reloading the page.', 'wp-document-revisions' ),
			'lostLockNoticeTitle' => __( 'Lost Document Lock', 'wp-document-revisions' ),
			'lostLockNoticeLogo'  => admin_url( 'images/logo.gif' ),
			// translators: %d is the numeric minutes, when singular
			'minute'              => __( '%d mins', 'wp-document-revisions' ),
			// translators: %d is the numeric minutes, when plural
			'minutes'             => __( '%d mins', 'wp-document-revisions' ),
			// translators: %d is the numeric hour, when singular
			'hour'                => __( '%d hour', 'wp-document-revisions' ),
			// translators: %d is the numeric hour, when plural
			'hours'               => __( '%d hours', 'wp-document-revisions' ),
			// translators: %d is the numeric day, when singular
			'day'                 => __( '%d day', 'wp-document-revisions' ),
			// translators: %d is the numeric days, when plural
			'days'                => __( '%d days', 'wp-document-revisions' ),
			'offset'              => get_option( 'gmt_offset' ) * 3600,
			'nonce'               => wp_create_nonce( 'wp-document-revisions' ),
		);

		$wpdr = self::$parent;

		// Enqueue JS
		$suffix = ( WP_DEBUG ) ? '.dev' : '';
		wp_enqueue_script(
			'wp_document_revisions',
			plugins_url( '/js/wp-document-revisions' . $suffix . '.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			$wpdr->version,
			false
		);
		wp_localize_script( 'wp_document_revisions', 'wp_document_revisions', $data );

		// enqueue CSS
		wp_enqueue_style( 'wp-document-revisions', plugins_url( '/css/style.css', dirname( __FILE__ ) ), null, $wpdr->version );

	}


	/**
	 * Joins wp_posts on itself so posts can be filter by post_parent's type
	 *
	 * @param string $join the original join statement
	 * @return string the modified join statement
	 */
	public function filter_media_join( $join ) {
		global $wpdb;

		$join .= " LEFT OUTER JOIN {$wpdb->posts} wpdr_post_parent ON wpdr_post_parent.ID = {$wpdb->posts}.post_parent";

		return $join;
	}



	/**
	 * Exclude children of documents from query
	 *
	 * @param string $where the original where statement
	 * @return string the modified where statement
	 */
	public function filter_media_where( $where ) {
		global $wpdb;

		// fix for mysql column ambiguity
		// see http://core.trac.wordpress.org/ticket/19779 and http://core.trac.wordpress.org/ticket/20193
		$where = str_replace( ' post_parent < 1', " {$wpdb->posts}.post_parent < 1", $where );
		$where = str_replace( '(post_mime_type LIKE', "({$wpdb->posts}.post_mime_type LIKE", $where );

		$where .= " AND ( wpdr_post_parent.post_type IS NULL OR wpdr_post_parent.post_type != 'document' )";

		return $where;
	}


	/**
	 * Filters documents from media galleries
	 *
	 * @uses filter_media_where()
	 * @uses filter_media_join()
	 */
	public function filter_from_media() {

		global $pagenow;

		// verify the page
		if ( 'upload.php' !== $pagenow && 'media-upload.php' !== $pagenow ) {
			return;
		}

		// note: hook late so that unnattached filter can hook in, if necessary
		add_filter( 'posts_join_paged', array( &$this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( &$this, 'filter_media_where' ), 20 );
	}

	/**
	 * Filters documents from the media grid view when queried via Ajax. This uses
	 * the same filters from the list view applied in `filter_from_media()`.
	 *
	 * @param Object $query the WP_Query object
	 * @return mixed
	 */
	public function filter_from_media_grid( $query ) {
		// note: hook late so that unnattached filter can hook in, if necessary
		add_filter( 'posts_join_paged', array( $this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( $this, 'filter_media_where' ), 20 );

		return $query;
	}

	/**
	 * Requires all document revisions to have attachments
	 * Prevents initial autosave drafts from appearing as a revision after document upload
	 *
	 * @since 1.0
	 * @param int $id the post id
	 */
	public function revision_filter( $id ) {

		// verify post type
		if ( ! $this->verify_post_type( $id ) ) {
			return;
		}

		$document = get_post( $id );
		if ( 0 === strlen( $document->post_content ) ) {
			wp_delete_post( $id, true );
		}
	}


	/**
	 * Deletes all attachments associated with a document or revision
	 *
	 * @since 1.0
	 * @param int $post_id the id of the deleted post
	 */
	public function delete_attachments_with_document( $post_id ) {

		if ( ! $this->verify_post_type( $post_id ) ) {
			return;
		}

		$document = get_post( $post_id );

		if ( is_numeric( $document->post_content ) && get_post( $document->post_content ) ) {
			wp_delete_attachment( $document->post_content, false );
		}
	}


	/**
	 * Provides support for edit flow and disables the default workflow state taxonomy
	 *
	 * @since 1.1
	 */
	public function edit_flow_admin_support() {
		_deprecated_function( 'edit_flow_admin_support', '1.3.2 of WP Document Revisions', 'disable_workflow_states' );
	}


	/**
	 * Remove all hooks that activate workflow state support
	 * use filter `document_use_workflow_states` to disable
	 */
	public function disable_workflow_states() {

		if ( self::$parent->use_workflow_states() ) {
			return false;
		}

		remove_filter( 'manage_document_posts_columns', array( &$this, 'add_workflow_state_column' ) );
		remove_action( 'manage_document_posts_custom_column', array( &$this, 'workflow_state_column_cb' ) );
		remove_action( 'save_post', array( &$this, 'workflow_state_save' ) );
		remove_action( 'admin_head', array( &$this, 'make_private' ) );
		return true;
	}

	/**
	 * Defaults document visibility to private
	 *
	 * @since 0.5
	 */
	public function make_private() {
		global $post;

		// verify that this is a new document
		if ( ! isset( $post ) || ! $this->verify_post_type( $post ) || strlen( $post->post_content ) > 0 ) {
			return;
		}

		$post_pre = clone $post;

		if ( 'draft' === $post->post_status || 'auto-draft' === $post->post_status ) {
			$post->post_status = 'private';
		}

		// @codingStandardsIgnoreLine WordPress.Variables.GlobalVariables.OverrideProhibited
		$post = apply_filters( 'document_to_private', $post, $post_pre );

	}


	/**
	 * Set up revisions on admin dashboard
	 * @ since 3.0.1
	 */
	public function setup_dashboard() {
		wp_add_dashboard_widget(
			'wpdr_dashboard',
			__( 'Recently Revised Documents', 'wp-document-revisions' ),
			array(
				&$this,
				'dashboard_display',
			)
		);
	}


	/**
	 * Callback to display documents on admin dashboard
	 * @ since 3.0.1
	 */
	public function dashboard_display() {

		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}

		$query = array(
			'orderby'     => 'modified',
			'order'       => 'DESC',
			'numberposts' => 5,
		);

		$documents = $wpdr->get_documents( $query );

		// no documents, don't bother
		if ( ! $documents ) {
			return;
		}

		// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
		echo '<ul>';

		foreach ( $documents as $document ) :
			$link = ( current_user_can( 'edit_document', $document->ID ) ) ? add_query_arg(
				array(
					'post' => $document->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			) : get_permalink( $document->ID );
			// translators: %1$s is the time ago in words, %2$s is the author, %3$s is the post status
			$format_string = __( '%1$s ago by %2$s [%3$s]', 'wp-document-revisions' );
			?>
			<li>
				<a href="<?php echo esc_attr( $link ); ?>"><?php echo esc_html( get_the_title( $document->ID ) ); ?></a><br />
				<?php
				printf(
					esc_html( $format_string ),
					esc_html( human_time_diff( strtotime( $document->post_modified_gmt ) ) ),
					esc_html( get_the_author_meta( 'display_name', $document->post_author ) ),
					esc_html( ucwords( $document->post_status ) )
				);
				?>
			</li>
			<?php
		endforeach;

		// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
		echo '</ul>';
	}
}
