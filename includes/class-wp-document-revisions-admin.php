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

	use WP_Document_Revisions_Admin_Editor;
	use WP_Document_Revisions_Admin_List;
	use WP_Document_Revisions_Admin_Settings;

	/**
	 * The parent WP Document Revisions instance
	 *
	 * @var object
	 */
	public static $parent;

	/**
	 * The singelton instance
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * The last_but_one revision
	 *
	 * @var int | null
	 */
	private static $last_but_one_revn = null;

	/**
	 * The last_but_one revision excerpt
	 *
	 * @var string | null
	 */
	private static $last_revn_excerpt = null;

	/**
	 * The last revision
	 *
	 * @var int | null
	 */
	private static $last_revn = null;

	/**
	 * List of document attachments (used to ensure all deleted on document deletion.
	 *
	 * @var int[] | null
	 */
	private static $attachmts = null;

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
		add_action( 'admin_head', array( &$this, 'make_private' ), 20 );
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
		add_action( 'update_wpmu_options', array( &$this, 'network_link_date_save' ) );
		add_action( 'wpmu_options', array( &$this, 'network_settings_cb' ) );
		add_action( 'network_admin_notices', array( &$this, 'network_settings_errors' ) );
		add_filter( 'wp_redirect', array( &$this, 'network_settings_redirect' ) );

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
		add_action( 'before_delete_post', array( &$this, 'list_attachments_with_document' ) );
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
	 * @param string $funct the function to call.
	 * @param array  $args  the arguments to pass to the function.
	 * @return mixed the result of the function.
	 */
	public function __call( $funct, array $args ) {
		return call_user_func_array( array( &self::$parent, $funct ), $args );
	}

	/**
	 * Provides support to call properties of the parent class natively.
	 *
	 * @since 1.0
	 * @param string $name the property to fetch.
	 * @return mixed the property's value
	 */
	public function __get( string $name ) {
		return WP_Document_Revisions::$$name;
	}
}
