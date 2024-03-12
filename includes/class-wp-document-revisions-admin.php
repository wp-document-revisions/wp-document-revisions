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
	 * The last_but_one revision
	 *
	 * @var $last_but_one_revn
	 */
	private static $last_but_one_revn = null;

	/**
	 * The last_but_one revision excerpt
	 *
	 * @var $last_revn_excerpt
	 */
	private static $last_revn_excerpt = null;

	/**
	 * The last revision
	 *
	 * @var $last_revn
	 */
	private static $last_revn = null;

	/**
	 * Register's admin hooks
	 * Note: we are at auth_redirect, first possible hook is admin_menu
	 *
	 * @since 0.5
	 * @param unknown $instance (optional, reference).
	 */
	public function __construct( &$instance = null ) {
		self::$instance = &$this;

		// create or store parent instance.
		if ( null === $instance ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = &$instance;
		}

		// help and messages.
		add_filter( 'post_updated_messages', array( &$this, 'update_messages' ) );
		add_action( 'admin_head', array( &$this, 'add_help_tab' ) );

		// edit document screen.
		add_action( 'admin_head', array( &$this, 'make_private' ) );
		add_action( 'set_object_terms', array( &$this, 'workflow_state_save' ), 10, 6 );
		add_action( 'save_post_document', array( &$this, 'save_document' ) );
		add_action( 'admin_init', array( &$this, 'enqueue_edit_scripts' ) );
		add_action( '_wp_put_post_revision', array( &$this, 'revision_filter' ), 10, 1 );
		add_filter( 'wp_save_post_revision_post_has_changed', array( &$this, 'identify_last_but_one' ), 10, 3 );
		add_filter( 'default_hidden_meta_boxes', array( &$this, 'hide_postcustom_metabox' ), 10, 2 );
		add_action( 'admin_print_footer_scripts', array( &$this, 'bind_upload_cb' ), 99 );
		add_action( 'admin_head', array( &$this, 'hide_upload_header' ) );
		add_action( 'admin_head', array( &$this, 'check_upload_files' ) );
		add_filter( 'media_upload_tabs', array( &$this, 'media_upload_tabs_computer' ) );
		// Although the Post Type Supports Editor, don't use block editor.
		add_filter( 'use_block_editor_for_post', array( &$this, 'no_use_block_editor' ), 10, 2 );
		add_action( 'edit_form_after_title', array( &$this, 'prepare_editor' ) );
		add_filter( 'wp_editor_settings', array( &$this, 'document_editor_setting' ), 10, 2 );
		add_filter( 'tiny_mce_before_init', array( &$this, 'modify_content_class' ) );

		// document list.
		add_filter( 'manage_document_posts_columns', array( &$this, 'rename_author_column' ) );
		add_filter( 'manage_document_posts_columns', array( &$this, 'add_currently_editing_column' ), 20 );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'currently_editing_column_cb' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( &$this, 'filter_documents_list' ) );
		add_filter( 'parse_query', array( &$this, 'convert_workflow_state_to_post_status' ) );
		add_filter( 'wp_dropdown_users_args', array( $this, 'filter_user_dropdown' ), 10, 2 );

		// settings.
		add_action( 'admin_init', array( &$this, 'settings_fields' ) );
		add_action( 'update_wpmu_options', array( &$this, 'network_upload_location_save' ) );
		add_action( 'update_wpmu_options', array( &$this, 'network_slug_save' ) );
		add_action( 'wpmu_options', array( &$this, 'network_settings_cb' ) );
		add_action( 'network_admin_notices', array( &$this, 'network_settings_errors' ) );
		add_filter( 'wp_redirect', array( &$this, 'network_settings_redirect' ) );

		// revisions management.
		add_filter( 'wp_revisions_to_keep', array( &$this, 'manage_document_revisions_limit' ), 10, 2 );

		// profile.
		add_action( 'show_user_profile', array( $this, 'rss_key_display' ) );
		add_action( 'personal_options_update', array( &$this, 'profile_update_cb' ) );
		add_action( 'edit_user_profile_update', array( &$this, 'profile_update_cb' ) );

		// Queue up JS.
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue' ) );

		// media filters.
		add_action( 'admin_init', array( &$this, 'filter_from_media' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_from_media_grid' ) );

		// cleanup.
		add_action( 'delete_post', array( &$this, 'delete_attachments_with_document' ) );

		// edit flow or publishpress support.
		add_action( 'init', array( &$this, 'disable_workflow_states' ), 1901 );  // After main class called.

		// admin css.
		add_filter( 'admin_body_class', array( &$this, 'admin_body_class_filter' ) );

		// admin dashboard.
		add_action( 'wp_dashboard_setup', array( &$this, 'setup_dashboard' ) );
	}


	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 1.0
	 * @param function $funct the function to call.
	 * @param array    $args  the arguments to pass to the function.
	 * @returns mixed the result of the function.
	 */
	public function __call( $funct, $args ) {
		return call_user_func_array( array( &self::$parent, $funct ), $args );
	}


	/**
	 * Provides support to call properties of the parent class natively.
	 *
	 * @since 1.0
	 * @param string $name the property to fetch.
	 * @returns mixed the property's value
	 */
	public function __get( $name ) {
		return WP_Document_Revisions::$$name;
	}


	/**
	 * Registers update messages
	 *
	 * @since 0.5
	 * @param array $messages messages array.
	 * @returns array messages array with doc. messages
	 */
	public function update_messages( $messages ) {
		global $post, $post_id;

		$messages['document'] = array(
			// translators: %s is the download link.
			1  => sprintf( __( 'Document updated. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink( $post_id ) ) ),
			2  => __( 'Custom field updated.', 'wp-document-revisions' ),
			3  => __( 'Custom field deleted.', 'wp-document-revisions' ),
			4  => __( 'Document updated.', 'wp-document-revisions' ),
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// translators: %s is the revision ID.
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Document restored to revision from %s', 'wp-document-revisions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			// translators: %s is the download link.
			6  => sprintf( __( 'Document published. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink( $post_id ) ) ),
			7  => __( 'Document saved.', 'wp-document-revisions' ),
			// translators: %s is the download link.
			8  => sprintf( __( 'Document submitted. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
			// translators: %1$s is the date, %2$s is the preview link.
			9  => sprintf( __( 'Document scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview document</a>', 'wp-document-revisions' ), date_i18n( sprintf( _x( '%1$s @ %2$s', '%1$s: date; %2$s: time', 'wp-document-revisions' ), get_option( 'date_format' ), get_option( 'time_format' ) ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ) ),
			// translators: %s is the link to download the document.
			10 => sprintf( __( 'Document draft updated. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
		);

		return $messages;
	}


	/**
	 * Adds help tabs to 3.3+ help tab API.
	 *
	 * @since 1.1
	 * @uses get_help_text()
	 * @return void
	 */
	public function add_help_tab() {
		$screen = get_current_screen();

		// only interested in document post_types.
		if ( 'document' !== $screen->post_type ) {
			return;
		}
		// loop through each tab in the help array and add.
		foreach ( $this->get_help_text( $screen ) as $title => $content ) {
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
	 * Helper function to provide help text as an array.
	 *
	 * @since 1.1
	 * @param WP_Screen $screen (optional) the current screen.
	 * @returns array the help text
	 */
	public function get_help_text( $screen = null ) {
		if ( is_null( $screen ) ) {
			$screen = get_current_screen();
		}

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			'document'      => array(
				__( 'Basic Usage', 'wp-document-revisions' ) =>
				'<p>' . __( 'This screen allows users to collaboratively edit documents and track their revision history. To begin, enter a title for the document, optionally enter a description, and click <code>Upload New Version</code> and select the file from your computer.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'Once successfully uploaded, you can enter a revision log message, assign the document an author, and describe its current workflow state.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'When done, simply click <code>Update</code> to save your changes', 'wp-document-revisions' ) . '</p>',
				__( 'Document Description', 'wp-document-revisions' ) =>
				'<p>' . __( 'The document description provides a short user-oriented summary of the document.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'This can be used with the Document List or Latest Documents blocks or their shortcode or widget equivalents.', 'wp-document-revisions' ) . '</p>',
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

		// if we don't have any help text for this screen, just kick.
		if ( ! $screen instanceof WP_Screen || ! isset( $help[ $screen->id ] ) ) {
			return array();
		}

		/**
		 * Filters the default help text for current screen.
		 *
		 * @param string[]  $help   default help text for current screen.
		 * @param WP_Screen $screen current screen name.
		 */
		return apply_filters( 'document_help_array', $help[ $screen->id ], $screen );
	}

	/**
	 * Callback to manage metaboxes on edit page.
	 * @ since 0.5
	 */
	public function meta_cb() {
		global $post;

		// remove unused meta boxes.
		remove_meta_box( 'revisionsdiv', 'document', 'normal' );
		remove_meta_box( 'postexcerpt', 'document', 'normal' );
		remove_meta_box( 'slugdiv', 'document', 'normal' );
		remove_meta_box( 'tagsdiv-workflow_state', 'document', 'side' );

		// add our meta boxes.
		add_meta_box( 'revision-summary', __( 'Revision Summary', 'wp-document-revisions' ), array( &$this, 'revision_summary_cb' ), 'document', 'normal', 'default' );
		add_meta_box( 'document', __( 'Document', 'wp-document-revisions' ), array( &$this, 'document_metabox' ), 'document', 'normal', 'high' );

		if ( ! empty( $post->post_content ) ) {
			add_meta_box( 'revision-log', __( 'Revision Log', 'wp-document-revisions' ), array( &$this, 'revision_metabox' ), 'document', 'normal', 'low' );
		}

		if ( taxonomy_exists( 'workflow_state' ) && ! $this->disable_workflow_states() ) {
			add_meta_box( 'workflow-state', __( 'Workflow State', 'wp-document-revisions' ), array( &$this, 'workflow_state_metabox_cb' ), 'document', 'side', 'default' );
		}

		// move author div to make room for ours.
		remove_meta_box( 'authordiv', 'document', 'normal' );

		// only add author div if user can give someone else ownership.
		if ( current_user_can( 'edit_others_documents' ) ) {
			add_meta_box( 'authordiv', __( 'Owner', 'wp-document-revisions' ), array( &$this, 'post_author_meta_box' ), 'document', 'side', 'low' );
		}

		// By default revisions are unlimited, but user filter may have limited number. Check if impact.
		add_action( 'admin_notices', array( &$this, 'check_document_revisions_limit' ) );

		// lock notice.
		add_action( 'admin_notices', array( &$this, 'lock_notice' ) );

		do_action( 'document_edit' );
	}


	/**
	 * Use Classic Editor for Documents (as need to constrain options).
	 *
	 * @since 3.4.0
	 *
	 * @param bool    $use_block_editor Whether the post can be edited or not.
	 * @param WP_Post $post             The post being checked.
	 */
	public function no_use_block_editor( $use_block_editor, $post ) {
		// switch off for documents.
		if ( $this->verify_post_type( $post ) ) {
			return false;
		}
		return $use_block_editor;
	}


	/**
	 * Invoke the editor for a description to be entered.
	 *
	 * @ since 3.4.0
	 *
	 * @param WP_Post $post Post object.
	 */
	public function prepare_editor( $post ) {
		if ( 'document' !== $post->post_type ) {
			return;
		}

		echo '<h2>' . esc_html__( 'Document Description', 'wp-document-revisions' ) . '</h2>';

		// convert old format to new.
		if ( is_numeric( $post->post_content ) ) {
			$post->post_content = $this->format_doc_id( $post->post_content );
		}
	}


	/**
	 * Modify the standard Classic editor settings for a simple description to be entered.
	 *
	 * @since 3.4.0
	 *
	 * @param array  $settings  Array of editor arguments.
	 * @param string $editor_id Unique editor identifier, e.g. 'content'. Accepts 'classic-block'
	 *                          when called from block editor's Classic block.
	 */
	public function document_editor_setting( $settings, $editor_id ) {
		// only interested in content.
		if ( 'content' !== $editor_id ) {
			return $settings;
		}

		global $post;
		if ( 'document' === $post->post_type || $this->verify_post_type( $post->ID ) ) {
			// restricted capacity for document content.
			return array(
				'wpautop'       => false,
				'media_buttons' => false,
				'textarea_name' => 'doc_descr',
				'textarea_rows' => 8,
				'teeny'         => false,
				'quicktags'     => false,
			);
		}
		return $settings;
	}

	/**
	 * Modify the 'content' class for documents otherwise it indents everything in the editor.
	 *
	 * @since 3.4.0
	 *
	 * @param array $settings  Array of tiny_mce arguments.
	 */
	public function modify_content_class( $settings ) {
		// check on document only affects these.
		if ( array_key_exists( 'body_class', $settings ) && 0 === strpos( $settings['body_class'], 'content post-type-document' ) ) {
			$settings['content_css'] = $settings['content_css'] . ',' . plugins_url( '/css/wpdr-content.css', __DIR__ );
		}
		return $settings;
	}


	/**
	 * Forces postcustom metabox to be hidden by default, despite the fact that the CPT creates it.
	 *
	 * @since 1.0
	 * @param array     $hidden the default hidden metaboxes.
	 * @param WP_Screen $screen the current screen.
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
	 * to style the page (e.g., when the document is locked).
	 *
	 * @param String $body_class the existing body class(es).
	 */
	public function admin_body_class_filter( $body_class ) {
		global $post;

		if ( ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
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
	 * and javascript not compatible for that usecase as written.
	 *
	 * @since 1.2.4
	 */
	public function hide_upload_header() {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'media-upload.php' === $pagenow && ( isset( $_GET['post_id'] ) ? $this->verify_post_type( sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) ) : false ) ) {
			?>
			<style>
				#media-upload-header {display:none;}
			</style>
			<?php
		}
	}


	/**
	 * Check that those having edit_document can upload documents.
	 *
	 * @since 3.3
	 */
	public function check_upload_files() {
		global $typenow, $pagenow;

		if ( ! current_user_can( 'edit_documents' ) || 'document' !== $typenow || current_user_can( 'upload_files' ) ) {
			return;
		}

		if ( 'post.php' === $pagenow ) {
			?>
			<div class="notice notice-warning is-dismissible"><p>
			<?php esc_html_e( 'You do not have the upload_files capability!', 'wp-document-revisions' ); ?>
			</p><p>
			<?php esc_html_e( 'You will not be able to upload documents though you may be able change some attributes.', 'wp-document-revisions' ); ?>
			</p></div>
			<?php
		} elseif ( 'post-new.php' === $pagenow ) {
			?>
			<div class="notice notice-warning is-dismissible"><p>
			<?php esc_html_e( 'You do not have the upload_files capability!', 'wp-document-revisions' ); ?>
			</p><p>
			<?php esc_html_e( 'You will not be able to upload any documents.', 'wp-document-revisions' ); ?>
			</p></div>
			<?php
			// Need to switch off save capability.
		}
	}


	/**
	 * Metabox to provide common document functions.
	 *
	 * @since 0.5
	 * @param object $post the post object.
	 */
	public function document_metabox( $post ) {
		// convert old format to new.
		if ( is_numeric( $post->post_content ) ) {
			$post->post_content = $this->format_doc_id( $post->post_content );
		}
		?>
		<input type="hidden" id="post_content" name="post_content" value="<?php echo esc_attr( $post->post_content ); ?>" />
		<input type="hidden" id="curr_content" name="curr_content" value="Unset" />
		<?php
		$lock_holder = $this->get_document_lock( $post );
		if ( $lock_holder ) {
			?>
			<div id="lock_override" class="hide-if-no-js">
			<?php
			// translators: %s is the user that holds the document lock.
			printf( esc_html__( '%s has prevented other users from making changes.', 'wp-document-revisions' ), esc_html( $lock_holder ) );
			if ( current_user_can( 'override_document_lock' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
				_e( '<br />If you believe this is in error you can <a href="#" id="override_link">override the lock</a>, but their changes will be lost.', 'wp-document-revisions' );
			}
			?>
			</div>
		<?php } ?>
		<div id="lock_override">
			<a href="media-upload.php?post_id=<?php echo intval( $post->ID ); ?>&TB_iframe=1" id="content-add_media" class="thickbox add_media button" title="<?php esc_attr_e( 'Upload Document', 'wp-document-revisions' ); ?>" onclick="return false;" >
				<?php esc_html_e( 'Upload New Version', 'wp-document-revisions' ); ?>
			</a>
		</div>
		<?php
		$latest_version = $this->get_latest_revision( $post->ID );
		if ( is_object( $latest_version ) ) {
			?>
			<p>
			<strong><?php esc_html_e( 'Latest Version of the Document', 'wp-document-revisions' ); ?></strong>
			<strong><a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_BLANK"><?php esc_html_e( 'Download', 'wp-document-revisions' ); ?></a></strong><br />
			<em>
			<?php
			$mod_date = $latest_version->post_modified;
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			// translators: %1$s is the post modified date in words, %2$s is the post modified date in time format, %3$s is how long ago the post was modified, %4$s is the author's name.
			printf( __( 'Checked in <abbr class="timestamp" title="%1$s" id="A%2$s">%3$s</abbr> ago by %4$s', 'wp-document-revisions' ), $mod_date, strtotime( $mod_date ), human_time_diff( (int) get_post_modified_time( 'U', true, $post->ID ), time() ), get_the_author_meta( 'display_name', $latest_version->post_author ) );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</em>
			</p>
		<?php } ?>
		<div class="clear"></div>
		<?php
	}


	/**
	 * Custom excerpt metabox CB.
	 *
	 * @since 0.5
	 */
	public function revision_summary_cb() {
		?>
		<label class="screen-reader-text" for="excerpt"><?php esc_html_e( 'Revision Summary', 'wp-document-revisions' ); ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"></textarea>
		<p><?php esc_html_e( 'Revision summaries are optional notes to store along with this revision that allow other users to quickly and easily see what changes you made without needing to open the actual file.', 'wp-document-revisions' ); ?></p>
		<?php
	}


	/**
	 * Creates revision log metabox.
	 *
	 * @since 0.5
	 * @param object $post the post object.
	 */
	public function revision_metabox( $post ) {
		$can_edit_doc = current_user_can( 'edit_document', $post->ID );
		$revisions    = $this->get_revisions( $post->ID );
		$key          = $this->get_feed_key();
		?>
		<table id="document-revisions">
			<thead>
			<tr class="header">
				<th><?php esc_html_e( 'Modified', 'wp-document-revisions' ); ?></th>
				<th><?php esc_html_e( 'User', 'wp-document-revisions' ); ?></th>
				<th style="width:50%"><?php esc_html_e( 'Summary', 'wp-document-revisions' ); ?></th>
				<?php
				if ( $can_edit_doc ) {
					?>
					<th><?php esc_html_e( 'Actions', 'wp-document-revisions' ); ?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
		<?php

		$i = 0;
		foreach ( $revisions as $revision ) {
			++$i;
			if ( ! current_user_can( 'read_document', $revision->ID ) ) {
				continue;
			}
			// preserve original file extension on revision links.
			// this will prevent mime/ext security conflicts in IE when downloading.
			$attach = $this->get_document( $revision->ID );
			if ( $attach ) {
				$fn   = get_post_meta( $attach->ID, '_wp_attached_file', true );
				$fno  = pathinfo( $fn, PATHINFO_EXTENSION );
				$info = pathinfo( get_permalink( $revision->ID ) );
				$fn   = $info['dirname'] . '/' . $info['filename'];
				// Only add extension if permalink doesnt contain post id as it becomes invalid.
				if ( ! strpos( $info['filename'], '&p=' ) ) {
					$fn .= '.' . $fno;
				}
			} else {
				$fn = get_permalink( $revision->ID );
			}
			?>
			<tr>
				<td><a href="<?php echo esc_url( $fn ); ?>" title="<?php echo esc_attr( $revision->post_modified ); ?>" class="timestamp"><?php echo esc_html( human_time_diff( strtotime( $revision->post_modified_gmt ), time() ) ); ?></a></td>
				<td><?php echo esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ); ?></td>
				<td><?php echo esc_html( $revision->post_excerpt ); ?></td>
				<?php if ( $can_edit_doc && $post->ID !== $revision->ID && $i > 2 ) { ?>
					<td><a href="
					<?php
					echo esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'revision' => $revision->ID,
									'action'   => 'restore',
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
		</tbody>
		</table>
		<p style="padding-top: 10px;"><a href="<?php echo esc_url( add_query_arg( 'key', $key, get_post_comments_feed_link( $post->ID ) ) ); ?>"><?php esc_html_e( 'RSS Feed', 'wp-document-revisions' ); ?></a></p>
		<?php
	}


	/**
	 * Forces autosave to load
	 * By default, if there's a lock on the post, auto save isn't loaded; we want it in case lock is overridden.
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
	 * Only load documents from Computer.
	 *
	 * @since 3.3
	 *
	 * @param string[] $_default_tabs An array of media tabs.
	 */
	public function media_upload_tabs_computer( $_default_tabs ) {
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( $this->verify_post_type() && isset( $_GET['action'] ) ) {
			// keep just load from computer for the document (but not the thumbnail).
			unset( $_default_tabs['type_url'] );
			unset( $_default_tabs['gallery'] );
			unset( $_default_tabs['library'] );
		}

		return $_default_tabs;
	}


	/**
	 * Registers the document settings.
	 *
	 * @since 0.5
	 */
	public function settings_fields() {
		register_setting( 'media', 'document_upload_directory', array( &$this, 'sanitize_upload_dir' ) );
		register_setting( 'media', 'document_slug', array( &$this, 'sanitize_document_slug' ) );
		add_settings_field( 'document_upload_directory', __( 'Document Upload Directory', 'wp-document-revisions' ), array( &$this, 'upload_location_cb' ), 'media', 'uploads' );
		add_settings_field( 'document_slug', __( 'Document Slug', 'wp-document-revisions' ), array( &$this, 'document_slug_cb' ), 'media', 'uploads' );
		register_setting(
			'media',
			'document_link_date',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( &$this, 'sanitize_link_date' ),
			)
		);
		add_settings_field(
			'document_link_date',
			__( 'Document Date in Permalink', 'wp-document-revisions' ),
			array( &$this, 'link_date_cb' ),
			'media',
			'uploads'
		);
	}


	/**
	 * Verifies that upload directory is a valid directory before updating the setting
	 * Attempts to create the directory if it does not exist.
	 *
	 * @since 1.0
	 * @param string $dir path to the new directory.
	 * @returns bool|string false on fail, path to new dir on sucess
	 */
	public function sanitize_upload_dir( $dir ) {
		// empty string passed.
		if ( '' === $dir ) {
			return $this->document_upload_dir();
		}

		// if the path is not absolute (Linux and Windows tests), assume it's relative to ABSPATH.
		if ( 0 !== strpos( $dir, '/' ) && ! preg_match( '|^.:\\\|', $dir ) ) {
			$dir = ABSPATH . $dir;
		}

		// dir didn't change.
		if ( $this->document_upload_dir() === $dir ) {
			return $dir;
		}

		// don't fire more than once.
		if ( ! get_settings_errors( 'document_upload_directory' ) ) {
			if ( ! is_multisite() ) {
				// does directory exist.
				if ( ! is_dir( $dir ) ) {
					add_settings_error( 'document_upload_directory', 'document-upload-dir-exists', __( 'Document directory does not appear to exist. Please review value.', 'wp-document-revisions' ), 'updated' );
				} elseif ( ! wp_is_writable( $dir ) ) {
					add_settings_error( 'document_upload_directory', 'document-upload-dir-write', __( 'Document directory is not writable. Please check permissions.', 'wp-document-revisions' ), 'updated' );
				}
			}
			// dir changed, throw warning.
			add_settings_error( 'document_upload_directory', 'document-upload-dir-change', __( 'Document upload directory changed, but existing uploads may need to be moved to the new folder to ensure they remain accessible.', 'wp-document-revisions' ), 'updated' );
		}

		// update plugin cache with new value.
		global $wpdr;
		$wpdr::$wpdr_document_dir = trailingslashit( trim( $dir ) );

		// trim and return.
		return $wpdr::$wpdr_document_dir;
	}


	/**
	 * Sanitize slug prior to saving.
	 *
	 * @param string $slug new slug.
	 * @return string sanitized slug
	 */
	public function sanitize_document_slug( $slug ) {
		$slug = sanitize_title( $slug, 'documents' );

		// unchanged.
		if ( $slug === $this->document_slug() ) {
			return $slug;
		}

		// new slug isn't yet stored
		// but queue up a rewrite rule flush to ensure slug takes effect on next request.
		add_action( 'shutdown', 'flush_rewrite_rules' );

		add_settings_error( 'document_slug', 'document-slug-change', __( 'Document slug changed, but some previously published URLs may now be broken.', 'wp-document-revisions' ), 'updated' );

		return $slug;
	}


	/**
	 * Sanitize link_date option prior to saving.
	 *
	 * @since 3.5.0
	 *
	 * @param string $link_date value to represent whether to add the year/month into the permalink.
	 * @return string sanitized value
	 */
	public function sanitize_link_date( $link_date ) {
		return (bool) $link_date;
	}


	/**
	 * Adds upload directory and document slug options to network admin page.
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
	 * Adds link_date option to permalink page.
	 *
	 * @since 3.5.0
	 */
	public function link_date_cb() {
		?>
		<label for="document_link_date">
		<input name="document_link_date" type="checkbox" id="document_link_date" value="1" <?php checked( '1', get_option( 'document_link_date' ) ); ?> />
		<?php esc_html_e( 'Remove the year and month element /yyyy/mm from the document permalink.', 'wp-document-revisions' ); ?></label><br />
		<span class="description">
		<?php esc_html_e( 'By default the document permalink will contain the post year and month.', 'wp-document-revisions' ); ?><br />
		<?php esc_html_e( 'The delivered rewrite rules support both formats.', 'wp-document-revisions' ); ?>
		</span>
		<?php
	}


	/**
	 * Callback to validate and save the network upload directory.
	 *
	 * @since 1.0
	 */
	public function network_upload_location_save() {
		if ( ! isset( $_POST['document_upload_location_nonce'] ) ) {
			return;
		}

		// verify nonce, auth.
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['document_upload_location_nonce'] ) ), 'network_document_upload_location' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify upload dir.
		$upload_dir = ( isset( $_POST['document_upload_directory'] ) ? sanitize_text_field( wp_unslash( $_POST['document_upload_directory'] ) ) : '' );
		$dir        = $this->sanitize_upload_dir( $upload_dir );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient.
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the dir is valid, save it.
		if ( $dir ) {
			update_site_option( 'document_upload_directory', $dir );
			// update plugin cache with new value.
			global $wpdr;
			$wpdr::$wpdr_document_dir = trailingslashit( trim( $dir ) );
		}
	}


	/**
	 * Callback to validate and save slug on network settings page.
	 */
	public function network_slug_save() {
		if ( ! isset( $_POST['document_slug_nonce'] ) ) {
			return;
		}

		// verify nonce, auth.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['document_slug_nonce'] ) ), 'network_document_slug' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify upload dir.
		$slug = ( isset( $_POST['document_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['document_slug'] ) ) : '' );
		$slug = $this->sanitize_document_slug( $slug );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient.
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the dir is valid, save it.
		if ( $slug ) {
			update_site_option( 'document_slug', $slug );
		}
	}


	/**
	 * Adds settings errors to network settings page for document upload directory CB.
	 *
	 * @since 1.0
	 */
	public function network_settings_errors() {
		settings_errors( 'document_upload_directory' );
		settings_errors( 'document_slug' );
	}


	/**
	 * Appends the settings-updated query arg to the network admin settings redirect so that the settings API can work.
	 *
	 * @since 1.0
	 * @param string $location the URL being redirected to.
	 * @returns string the modified location
	 */
	public function network_settings_redirect( $location ) {
		// Verify redirect string from /wp-admin/network/edit.php line 164.
		if ( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php' ) ) === $location ) {
			// append the settings-updated query arg and return.
			$location = add_query_arg( 'settings-updated', 'true', $location );
		}

		return $location;
	}


	/**
	 * Callback to create the upload location settings field.
	 *
	 * @since 0.5
	 */
	public function upload_location_cb() {
		?>
		<input name="document_upload_directory" type="text" id="document_upload_directory" value="<?php echo esc_attr( $this->document_upload_dir() ); ?>" class="large-text code" /><br />
		<span class="description">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
		_e( 'Directory in which to store uploaded documents. The default is in your <code>wp-content/uploads</code> folder (or another default uploads folder defined elsewhere), but it may be moved to a folder outside of the <code>htdocs</code> or <code>public_html</code> folder for added security.', 'wp-document-revisions' );
		?>
		</span>
		<?php if ( is_multisite() ) : ?>
		<span class="description">
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.UnsafePrintingFunction
			// translators: %site_id% is not interpolated and should not be translated.
			_e( 'You may optionally include the string <code>%site_id%</code> within the path to separate files by site.', 'wp-document-revisions' );
			// phpcs:enable WordPress.Security.EscapeOutput.UnsafePrintingFunction
			?>
		</span>
			<?php
		endif;
	}


	/**
	 * Callback to create the document slug settings field
	 */
	public function document_slug_cb() {
		// phpcs:ignore
		$year_month = ( get_option( 'document_link_date' ) ? '' : '/' . date( 'Y' ) . '/' . date( 'm' ) );
		?>
		<code><?php echo esc_html( trailingslashit( home_url() ) ); ?><input name="document_slug" type="text" id="document_slug" value="<?php echo esc_attr( $this->document_slug() ); ?>" class="medium-text" /><?php echo esc_html( $year_month ); ?>/<?php esc_html_e( 'example-document-title', 'wp-document-revisions' ); ?>.txt</code><br />
		<span class="description">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
		_e( '"Slug" with which to prefix all URLs for documents (and the document archive). Default is <code>documents</code>.', 'wp-document-revisions' );
		echo '<br />';
		echo '</span>';
	}


	/**
	 * Binds our post-upload javascript callback to the plupload event
	 * Note: in footer because it has to be called after handler.js is loaded and initialized.
	 *
	 * @since 1.2.1
	 */
	public function bind_upload_cb() {
		global $pagenow;

		if ( 'media-upload.php' === $pagenow ) {
			?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function() {window.WPDocumentRevisions.bindPostDocumentUploadCB()});
			</script>
			<?php
		}
	}


	/**
	 * Retrieves the most recent file attached to a post.
	 *
	 * @since 0.5
	 * @param int $post_id the parent post.
	 * @returns object the attachment object
	 */
	public function get_latest_attachment( $post_id ) {
		$attachments = $this->get_attachments( $post_id );

		return reset( $attachments );
	}


	/**
	 * Callback to display lock notice on top of edit page.
	 *
	 * @since 0.5
	 */
	public function lock_notice() {
		global $post;

		do_action( 'document_lock_notice', $post );

		// if there is no page var, this is a new document, no need to warn.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post'] ) ) :
			?>
		<div class="error" id="lock-notice"><p><?php esc_html_e( 'You currently have this file checked out. No other user can edit this document so long as you remain on this page.', 'wp-document-revisions' ); ?></p></div>
			<?php
			endif;
	}


	/**
	 * Callback to add RSS key field to profile page.
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
		</div>
		<?php
	}


	/**
	 * Retrieves feed key user meta; generates if necessary.
	 *
	 * @since 0.5
	 * @param int $user (optional) UserID.
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
	 * Generates, saves, and returns new feed key.
	 *
	 * @since 0.5
	 * @param int $user (optional) UserID.
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
	 * Callback to handle profile updates.
	 *
	 * @since 0.5
	 */
	public function profile_update_cb() {
		if ( isset( $_POST['generate-new-feed-key'] ) && isset( $_POST['_document_revisions_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_document_revisions_nonce'] ) ), 'generate-new-feed-key' ) ) {
			$this->generate_new_feed_key();
		}
	}

	/**
	 * Ensures that any system limit on revisions does not apply to documents.
	 *
	 * @since 3.2.2
	 *
	 * @param int     $num  default value for the number of revisions for the post_type.
	 * @param WP_Post $post current post.
	 */
	public function manage_document_revisions_limit( $num, $post ) {
		if ( ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
			return $num;
		}

		// Set default number as unlimited.
		$num = -1;
		/**
		 * Filters the number of revisions to keep for documents.
		 *
		 * This should normally be unlimited and setting it can make attachments unaccessible.
		 *
		 * Note particularly that Autosaves are revisions, so count towards the total.
		 *
		 * @since 3.2.2
		 *
		 * @param int -1 (unlimited).
		 */
		$num = apply_filters( 'document_revisions_limit', $num );

		return $num;
	}


	/**
	 * Ensures that an error box appears if the revisions for a post has reached a system limit.
	 *
	 * @since 3.2.2
	 */
	public function check_document_revisions_limit() {
		global $post;

		if ( ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
			return;
		}

		$num = wp_revisions_to_keep( $post );

		if ( 0 === $num ) {
			// setting revisions to 0 makes no sense for this plugin.
			?>
			<div class="notice notice-error"><p>
			<?php
			esc_html_e( 'Maximum number of document revisions set to zero using a filter. Check your configuration.', 'wp-document-revisions' );
			?>
			</p></div>
			<?php

		} elseif ( 0 < $num ) {
			// need to check that we're not at the limit.
			$revisions = count( wp_get_post_revisions( $post->ID ) );

			if ( $num < $revisions ) {
				?>
				<div class="notice notice-error"><p>
				<?php
				esc_html_e( 'More revisions exist for this document than is permitted. Making changes will delete data.', 'wp-document-revisions' );
				?>
				</p></div>
				<?php

			} elseif ( $num === $revisions ) {
				?>
				<div class="notice notice-error"><p>
				<?php
				esc_html_e( 'Maximum number of revisions reached for this document. Making changes will delete data.', 'wp-document-revisions' );
				?>
				</p></div>
				<?php

			}
		}
	}

	/**
	 * Allow some filtering of the All Documents list.
	 */
	public function filter_documents_list() {
		global $typenow;
		// Only applies to document post type.
		if ( 'document' === $typenow ) {
			$tax_slug = self::$parent->taxonomy_key();
			if ( ! empty( $tax_slug ) ) {
				// Filter by workflow state/edit flow/publishpress state.
				// Note that the name is always workflow state as using post_status will invoke default status handling.
				// However it may be different on coming back.
				$so_all = __( 'All workflow states', 'wp-document-revisions' );
				if ( 'workflow_state' !== $tax_slug ) {
					$so_all = __( 'All statuses', 'wp-document-revisions' );
				}
				$args = array(
					'name'            => 'workflow_state',
					'show_option_all' => $so_all,
					'taxonomy'        => $tax_slug,
					'hide_empty'      => false,
					'value_field'     => 'slug',
					'selected'        => filter_input( INPUT_GET, 'workflow_state', FILTER_SANITIZE_SPECIAL_CHARS ),
				);
				wp_dropdown_categories( $args );
			}

			// Add (and later remove) the action to get only document authors.
			if ( current_user_can( 'read_private_documents' ) ) {
				add_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
			}
			// author/owner filtering.
			$args = array(
				'name'                => 'author',
				'show_option_all'     => __( 'All owners', 'wp-document-revisions' ),
				'value_field'         => 'slug',
				'selected'            => filter_input( INPUT_GET, 'author', FILTER_SANITIZE_SPECIAL_CHARS ),
				'orderby'             => 'name',
				'order'               => 'ASC',
				'wpdr_added'          => 'list',
				'has_published_posts' => array( 'document' ),
			);
			wp_dropdown_users( $args );
			remove_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
		}
	}

	/**
	 * Filter the user dropdown args to add additional arguments that are normally filtered out. .
	 *
	 * @since 3.6
	 * @param array $query_args  The query arguments for get_users().
	 * @param array $parsed_args The arguments passed to wp_dropdown_users() combined with the defaults.
	 */
	public function filter_user_dropdown( $query_args, $parsed_args ) {
		if ( array_key_exists( 'wpdr_added', $parsed_args ) ) {
			if ( 'list' === $parsed_args['wpdr_added'] ) {
				$query_args['has_published_posts'] = $parsed_args['has_published_posts'];
			}
		}
		return $query_args;
	}

	/**
	 * If the user can read Private documents, then include private in the selection.
	 *
	 * @since 3.6
	 * @param Object $query the WP_Query object.
	 */
	public function pre_user_query( $query ) {
		if ( current_user_can( 'read_private_documents' ) ) {
			$query->query_where = str_replace( "= 'publish'", "IN ('publish', 'private')", $query->query_where );
		}
	}

	/**
	 * Need to manipulate workflow_state into taxonomy slug for EF/PP.
	 *
	 * Only invoked if taxonomy slug needs to be changed.
	 *
	 * @param Object $query the WP_Query object.
	 */
	public function convert_workflow_state_to_post_status( $query ) {
		global $pagenow, $typenow;
		if ( 'edit.php' === $pagenow && 'document' === $typenow ) {
			if ( 'workflow_state' !== self::$parent->taxonomy_key() && array_key_exists( 'workflow_state', $query->query_vars ) ) {
				// parameter sent using 'workflow_state', look up with the appropriate taxonomy key.
				$query->query_vars[ self::$parent->taxonomy_key() ] = $query->query_vars['workflow_state'];
			}
		}
	}

	/**
	 * Renames author column on document list to "owner".
	 *
	 * @since 1.0.4
	 * @param array $defaults the default column labels.
	 * @returns array the modified column labels
	 */
	public function rename_author_column( $defaults ) {
		if ( isset( $defaults['author'] ) ) {
			$defaults['author'] = __( 'Owner', 'wp-document-revisions' );
		}

		return $defaults;
	}


	/**
	 * Splices in Currently Editing column to document list.
	 *
	 * @since 1.1
	 * @param array $defaults the original columns.
	 * @returns array our spliced columns
	 */
	public function add_currently_editing_column( $defaults ) {
		// get checkbox and title.
		$output = array_slice( $defaults, 0, 2 );

		// splice in workflow state.
		$output['currently_editing'] = __( 'Currently Editing', 'wp-document-revisions' );

		// get the rest of the columns.
		$output = array_merge( $output, array_slice( $defaults, 2 ) );

		return $output;
	}


	/**
	 * Callback to output data for currently editing column.
	 *
	 * @since 1.1
	 * @param string $column_name the name of the column being propegated.
	 * @param int    $post_id the ID of the post being displayed.
	 */
	public function currently_editing_column_cb( $column_name, $post_id ) {
		// verify column.
		if ( 'currently_editing' === $column_name && $this->verify_post_type( $post_id ) ) {

			// output will be display name, if any.
			$lock = $this->get_document_lock( $post_id );
			if ( $lock ) {
				echo esc_html( $lock );
			}
		}
	}


	/**
	 * Callback to generate metabox for workflow state.
	 *
	 * @since 0.5
	 * @param object $post the post object.
	 */
	public function workflow_state_metabox_cb( $post ) {
		wp_nonce_field( 'wp-document-revisions', 'workflow_state_nonce' );

		$current_state = wp_get_post_terms(
			$post->ID,
			'workflow_state'
		);
		$states        = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		?>
		<label for="workflow_state"><?php esc_html_e( 'Current State', 'wp-document-revisions' ); ?>:</label>
		<select name="workflow_state" id="workflow_state">
			<option></option>
			<?php foreach ( $states as $state ) { ?>
			<option value="<?php echo esc_attr( $state->slug ); ?>"
				<?php
				if ( $current_state ) {
					selected( $current_state[0]->slug, $state->slug );
				}
				?>
				><?php echo esc_html( $state->name ); ?></option>
			<?php } ?>
		</select>
		<?php
	}

	/**
	 * Callback to identify change in workflow_state to provide an action point.
	 *
	 * @since 0.5
	 *
	 * @param int    $doc_id     the document ID.
	 * @param array  $terms      the new terms.
	 * @param array  $tt_ids     the new term IDs.
	 * @param string $taxonomy   the taxonomy being changed.
	 * @param bool   $append     whether it is being appended or replaced.
	 * @param array  $old_tt_ids term taxonomy ID array before the change.
	 */
	public function workflow_state_save( $doc_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		// Only interested in replacement to this taxonomy, so if not, bail early.
		if ( 'workflow_state' !== $taxonomy || $append ) {
			return;
		}
		// autosave check.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// only main item.
		if ( wp_is_post_revision( $doc_id ) ) {
			return;
		}

		// verify CPT.
		if ( ! $this->verify_post_type( $doc_id ) ) {
			return;
		}

		// check permissions.
		if ( ! current_user_can( 'edit_document', $doc_id ) ) {
			return;
		}

		if ( empty( $tt_ids ) ) {
			$new_id = '';
		} else {
			$term_obj = get_term_by( 'term_taxonomy_id', $tt_ids[0], $taxonomy );
			$new_id   = $term_obj->term_id;
		}
		if ( empty( $old_tt_ids ) ) {
			$old_id = '';
		} else {
			$term_obj = get_term_by( 'term_taxonomy_id', $old_tt_ids[0], $taxonomy );
			$old_id   = $term_obj->term_id;
		}

		if ( $new_id === $old_id ) {
			return;
		}

		// Old action hook.
		do_action( 'change_document_workflow_state', $doc_id, $new_id );

		// Replacement action hook.
		do_action( 'document_change_workflow_state', $doc_id, $new_id, $old_id );
	}

	/**
	 * Callback to unlink loaded featured image.
	 *
	 * @since 3.3
	 * @param int $doc_id the ID of the post being edited.
	 */
	public function save_document( $doc_id ) {
		// autosave check.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// check permissions.
		if ( ! current_user_can( 'edit_document', $doc_id ) ) {
			return;
		}

		$parent = wp_is_post_revision( $doc_id );
		if ( false !== $parent ) {
			$doc_id = $parent;
		}
		// if document has featured image loaded with document, then make sure it has no parent
		// done after we make sure that we have doc_id being parent.
		$thumb = get_post_meta( $doc_id, '_thumbnail_id', true );
		if ( $thumb > 0 ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_parent` = 0 WHERE `id` = %d AND `post_parent` = %d ",
				$thumb,
				$doc_id
			);
			$res        = $wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( $thumb, 'posts' );
			clean_post_cache( $thumb );
		}

		// find the attachment id now (as content might be cached) and we might delete the cache.
		$content   = get_post_field( 'post_content', $doc_id );
		$attach_id = $this->extract_document_id( $content );

		// Let's work on Workflow state, Verify nonce.
		if ( ! isset( $_POST['workflow_state_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['workflow_state_nonce'] ) ), 'wp-document-revisions' ) ) {
			return;
		}
		$ws = ( isset( $_POST['workflow_state'] ) ? sanitize_text_field( wp_unslash( $_POST['workflow_state'] ) ) : '' );

		// Save it.
		wp_set_post_terms( $doc_id, array( $ws ), 'workflow_state' );

		// is the permalink useful.
		$doc_post = get_post( $doc_id );
		$new_guid = self::$parent->permalink( $doc_post->guid, $doc_post, false, '' );
		if ( $new_guid !== $doc_post->guid ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `guid` = %s WHERE `id` = %d AND `post_parent` = 0 ",
				$new_guid,
				$doc_id
			);
			$res        = $wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $doc_id );
		}

		// can we merge the revisions.
		if ( is_null( self::$last_revn ) || is_null( self::$last_but_one_revn ) ) {
			null;
		} else {
			// Yes. Need to delete the last_but one revision and update the excerpt on the last revision and the post to keep timestamps.
			wp_delete_post_revision( self::$last_but_one_revn );
			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_excerpt` = %s WHERE `id` IN ( %d, %d ) AND `post_excerpt` <> %s ",
				self::$last_revn_excerpt,
				self::$last_revn,
				$doc_id,
				self::$last_revn_excerpt
			);
			$res        = $wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery	
			wp_cache_delete( self::$last_revn, 'posts' );
			wp_cache_delete( $doc_id, 'posts' );
			clean_post_cache( self::$last_revn_excerpt );
			clean_post_cache( self::$last_revn );
		}

		/**
		 * Fires once a document has been saved and all plugin processing done.
		 *
		 * @since 3.4.0
		 *
		 * @param int $doc_id    id of Document post.
		 * @param int $attach_id id of Attachment post.
		 */
		do_action( 'document_saved', $doc_id, $attach_id );
	}

	/**
	 * Slightly modified document author metabox because the current one is ugly.
	 *
	 * @since 0.5
	 * @param object $post the post object.
	 */
	public function post_author_meta_box( $post ) {
		global $user_id;
		?>
		<label class="screen-reader-text" for="post_author_override"><?php esc_html_e( 'Owner', 'wp-document-revisions' ); ?></label>
		<?php esc_html_e( 'Document Owner', 'wp-document-revisions' ); ?>:
		<?php
		wp_dropdown_users(
			array(
				'name'             => 'post_author_override',
				'selected'         => empty( $post->ID ) ? $user_id : $post->post_author,
				'include_selected' => true,
				'capability'       => 'edit_documents',
			)
		);
	}


	/**
	 * Back Compat.
	 */
	public function enqueue_js() {
		_deprecated_function( __FUNCTION__, '1.3.2 of WP Document Revisions', 'enqueue' );
		$this->enqueue();
	}


	/**
	 * Enqueue admin JS and CSS files.
	 */
	public function enqueue() {
		// only include JS on document pages.
		if ( ! $this->verify_post_type() ) {
			return;
		}

		// translation strings.
		$data = array(
			'restoreConfirmation' => __( 'Are you sure you want to restore this revision? If you do, no history will be lost. This revision will be copied and become the most recent revision.', 'wp-document-revisions' ),
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'lockNeedle'          => __( 'is currently editing this' ), // purposely left out text domain.
			'postUploadNotice'    => '<div id="message" class="updated" style="display:none"><p>' . __( 'File uploaded successfully. Add a revision summary below (optional) and press <strong>Update</strong> to save your changes.', 'wp-document-revisions' ) . '</p></div>',
			'postDesktopNotice'   => '<div id="message" class="update-nag" style="display:none"><p>' . __( 'After you have saved your document in your office software, <a href="#" onClick="location.reload();">reload this page</a> to see your changes.', 'wp-document-revisions' ) . '</p></div>',
			// translators: %s is the title of the document.
			'lostLockNotice'      => __( 'Your lock on the document %s has been overridden. Any changes will be lost.', 'wp-document-revisions' ),
			'lockError'           => __( 'An error has occurred, please try reloading the page.', 'wp-document-revisions' ),
			'lostLockNoticeTitle' => __( 'Lost Document Lock', 'wp-document-revisions' ),
			'lostLockNoticeLogo'  => admin_url( 'images/logo.gif' ),
			// translators: %d is the numeric minutes, when singular.
			'minute'              => __( '%d mins', 'wp-document-revisions' ),
			// translators: %d is the numeric minutes, when plural.
			'minutes'             => __( '%d mins', 'wp-document-revisions' ),
			// translators: %d is the numeric hour, when singular.
			'hour'                => __( '%d hour', 'wp-document-revisions' ),
			// translators: %d is the numeric hour, when plural.
			'hours'               => __( '%d hours', 'wp-document-revisions' ),
			// translators: %d is the numeric day, when singular.
			'day'                 => __( '%d day', 'wp-document-revisions' ),
			// translators: %d is the numeric days, when plural.
			'days'                => __( '%d days', 'wp-document-revisions' ),
			'offset'              => get_option( 'gmt_offset' ) * 3600,
			'nonce'               => wp_create_nonce( 'wp-document-revisions' ),
		);

		$wpdr = self::$parent;

		// Enqueue JS.
		$suffix = ( WP_DEBUG ) ? '.dev' : '';
		$path   = '/js/wp-document-revisions' . $suffix . '.js';
		$vers   = ( WP_DEBUG ) ? filemtime( dirname( __DIR__ ) . $path ) : $wpdr->version;
		wp_enqueue_script(
			'wp_document_revisions',
			plugins_url( $path, __DIR__ ),
			array( 'jquery' ),
			$vers,
			false
		);
		wp_localize_script( 'wp_document_revisions', 'wp_document_revisions', $data );

		// enqueue CSS.
		wp_enqueue_style( 'wp-document-revisions', plugins_url( '/css/style.css', __DIR__ ), null, $wpdr->version );
	}


	/**
	 * Joins wp_posts on itself so posts can be filter by post_parent's type.
	 *
	 * @param string $join the original join statement.
	 * @return string the modified join statement
	 */
	public function filter_media_join( $join ) {
		global $wpdb;

		$join .= " LEFT OUTER JOIN {$wpdb->posts} wpdr_post_parent ON wpdr_post_parent.ID = {$wpdb->posts}.post_parent";

		return $join;
	}


	/**
	 * Exclude children of documents from query.
	 *
	 * @param string $where the original where statement.
	 * @return string the modified where statement
	 */
	public function filter_media_where( $where ) {
		global $wpdb;

		$where .= " AND ( wpdr_post_parent.post_type IS NULL OR wpdr_post_parent.post_type != 'document' )";

		return $where;
	}


	/**
	 * Filters documents from media galleries.
	 *
	 * @uses filter_media_where()
	 * @uses filter_media_join()
	 */
	public function filter_from_media() {
		global $pagenow;

		// verify the page.
		if ( 'upload.php' !== $pagenow && 'media-upload.php' !== $pagenow ) {
			return;
		}

		// note: hook late so that unattached filter can hook in, if necessary.
		add_filter( 'posts_join_paged', array( &$this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( &$this, 'filter_media_where' ), 20 );
	}

	/**
	 * Filters documents from the media grid view when queried via Ajax. This uses
	 * the same filters from the list view applied in `filter_from_media()`.
	 *
	 * @param Object $query the WP_Query object.
	 * @return mixed
	 */
	public function filter_from_media_grid( $query ) {
		// note: hook late so that unattached filter can hook in, if necessary.
		add_filter( 'posts_join_paged', array( $this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( $this, 'filter_media_where' ), 20 );

		return $query;
	}

	/**
	 * Requires all document revisions to have attachments
	 * Prevents initial autosave drafts from appearing as a revision after document upload.
	 *
	 * @since 1.0
	 * @param int $revision_id the revision post id.
	 */
	public function revision_filter( $revision_id ) {
		// verify post type.
		if ( ! $this->verify_post_type( $revision_id ) ) {
			return;
		}

		$revision = get_post( $revision_id );
		// delete revision if there is no content.
		if ( 0 === strlen( $revision->post_content ) ) {
			wp_delete_post_revision( $revision_id );
			return;
		}

		// set last_revision (used in routine save_document to possibly merge revisions).
		self::$last_revn = $revision_id;
	}

	/**
	 * Identify the 'last but one' revision in case we will merge them.
	 *
	 * @param bool    $post_has_changed Whether the post has changed.
	 * @param WP_Post $last_revision    The last revision post object.
	 * @param WP_Post $post             The post object.
	 * @return bool.
	 */
	public function identify_last_but_one( $post_has_changed, $last_revision, $post ) {
		// only interested if post changed.
		if ( ! $post_has_changed ) {
			return $post_has_changed;
		}

		// verify post type.
		if ( ! $this->verify_post_type( $post->ID ) ) {
			return $post_has_changed;
		}

		// misuse of filter, but can use to determine whether the revisions can be merged.
		// keep revision if title or content (document linked only) changed. Also if author changed.
		if ( $post->post_title !== $last_revision->post_title || $post->post_author !== $last_revision->post_author ) {
			return true;
		}
		global $wpdr;
		if ( $wpdr->extract_document_id( $post->post_content ) !== $wpdr->extract_document_id( $last_revision->post_content ) ) {
			return true;
		}

		// normally only title, content and excerpt name for a revision to be created.
		$time_diff = time() - strtotime( $last_revision->post_modified_gmt );

		/**
		 * Filters whether to merge two revisions for a change in excerpt (generally where taxonomy change made late).
		 *
		 * Changes to the title or content (document) will always create an additional revision.
		 * But if there are two revisions within a user-defined time of each other and only one has excerpt text, they can be merged.
		 *
		 * @since 3.4
		 *
		 * @param int 0 number of seconds between revisions to NOT create an extra revision.
		 */
		if ( $time_diff < apply_filters( 'document_revisions_merge_revisions', 0 ) ) {
			// only here as excerpt changed.
			if ( '' === $post->post_excerpt || '' === $last_revision->post_excerpt ) {
				// possible merge, set last_but_one_revision (used in routine save_document).
				self::$last_but_one_revn = $last_revision->ID;
				self::$last_revn_excerpt = ( '' === $post->post_excerpt ? $last_revision->post_excerpt : $post->post_excerpt );
				return true;
			}
		}

		return $post_has_changed;
	}


	/**
	 * Deletes all attachments associated with a document or revision.
	 *
	 * @since 1.0
	 * @param int $post_id the id of the deleted post.
	 */
	public function delete_attachments_with_document( $post_id ) {
		if ( ! $this->verify_post_type( $post_id ) ) {
			return;
		}

		$document = get_post( $post_id );

		// not for an attachment or an autosave revision.
		if ( 'attachment' === $document->post_type || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$attach = $this->get_document( $post_id );
		if ( ! $attach ) {
			// no attachment.
			return;
		}

		// make sure that the attachment is not refered to by another post (ignore autosave).
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$doc_link = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM $wpdb->posts WHERE %d IN (post_parent, id) AND (post_content = %d OR post_content LIKE %s ) AND post_name != %s ",
				$document->post_parent,
				$attach->ID,
				$this->format_doc_id( $attach->ID ) . '%',
				strval( $document->post_parent ) . '-autosave-v1'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		if ( '1' === $doc_link ) {
			// have to access the document upload directory, so add it.
			add_filter( 'upload_dir', array( self::$parent, 'document_upload_dir_filter' ) );

			// look for attachment meta (before deleting attachment).
			$meta = get_post_meta( $attach->ID, '_wp_attachment_metadata', true );
			wp_delete_attachment( $attach->ID, true );

			// delete any remaining metadata images.
			global $wpdr;
			$file_dir = trailingslashit( $wpdr::$wpdr_document_dir );
			if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $size => $sizeinfo ) {
					wp_delete_file_from_directory( $file_dir . $sizeinfo['file'], $file_dir );
				}
			}
		}

		// have looked for the upload directory, so remove it.
		remove_filter( 'upload_dir', array( self::$parent, 'document_upload_dir_filter' ) );
	}

	/**
	 * Remove all hooks that activate workflow state support
	 * use filter `document_use_workflow_states` to disable.
	 */
	public function disable_workflow_states() {
		if ( self::$parent->use_workflow_states() ) {
			return false;
		}

		remove_action( 'set_object_terms', array( &$this, 'workflow_state_save' ) );

		// Have changed taxonomy key for EF/PP support, so switch off make private.
		if ( ! empty( self::$parent->taxonomy_key() ) && 'workflow_state' !== self::$parent->taxonomy_key() ) {
			remove_action( 'admin_head', array( &$this, 'make_private' ) );
		}
		return true;
	}

	/**
	 * Defaults document visibility to private.
	 *
	 * @since 0.5
	 */
	public function make_private() {
		global $post;

		// verify that this is a new document.
		if ( ! isset( $post ) || ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) || strlen( $post->post_content ) > 0 ) {
			return;
		}

		$post_pre = clone $post;

		if ( 'draft' === $post->post_status || 'auto-draft' === $post->post_status ) {
			$post->post_status = 'private';
		}

		/**
		 * Filters setting the new document status to private.
		 *
		 * @since 0.5
		 *
		 * @param WP_Post $post     link to (new) global post.
		 * @param WP_Post $post_pre link to clone of global post.
		 */
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = apply_filters( 'document_to_private', $post, $post_pre );
	}


	/**
	 * Set up revisions on admin dashboard.
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
	 * Callback to display documents on admin dashboard.
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

		$documents = $this->get_documents( $query );

		// no documents, don't bother.
		if ( ! $documents ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<ul>';

		foreach ( $documents as $document ) {
			$link = ( current_user_can( 'edit_document', $document->ID ) ) ? add_query_arg(
				array(
					'post'   => $document->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			) : get_permalink( $document->ID );
			// translators: %1$s is the time ago in words, %2$s is the author, %3$s is the post status.
			$format_string = __( '%1$s ago by %2$s [%3$s]', 'wp-document-revisions' );
			?>
			<li>
				<a href="<?php echo esc_attr( $link ); ?>"><?php echo esc_html( get_the_title( $document->ID ) ); ?></a><br />
				<?php
				printf(
					esc_html( $format_string ),
					esc_html( human_time_diff( strtotime( $document->post_modified_gmt ), time() ) ),
					esc_html( get_the_author_meta( 'display_name', $document->post_author ) ),
					esc_html( ucwords( $document->post_status ) )
				);
				?>
			</li>
			<?php
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</ul>';
	}
}
