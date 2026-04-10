<?php
/**
 * Main class for WP Document Revisions.
 *
 * @since 3.0.0
 * @package WP_Document_Revisions
 */

/**
 * Main WP_Document_Revisions class definition.
 */
class WP_Document_Revisions {
	use WP_Document_Revisions_Rewrites;
	use WP_Document_Revisions_File_Handler;
	use WP_Document_Revisions_Revisions;
	use WP_Document_Revisions_Query;

	/**
	 * Singleton instance.
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Length of feed key.
	 *
	 * @var int
	 */
	public static $key_length = 32;

	/**
	 * User meta key used auth feeds.
	 *
	 * @var string
	 */
	public static $meta_key = 'document_revisions_feed_key';

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	public $version = '3.9.0';

	/**
	 * The WP default upload directory cache.
	 *
	 * @var array
	 *
	 * @since 3.2
	 */
	public static $wp_default_dir = array();

	/**
	 * The document upload directory cache.
	 *
	 * @var string | null
	 *
	 * @since 3.2
	 */
	public static $wpdr_document_dir = null;

	/**
	 * The document admin class.
	 *
	 * @var object | null
	 *
	 * @since 3.5
	 */
	public $admin = null;

	/**
	 * Whether processing document or image directory.
	 *
	 * @var bool
	 *
	 * @since 3.2
	 */
	public static $doc_image = true;

	/**
	 * Identify if processing document or image directory.
	 *
	 * @return bool
	 *
	 * @since 3.2
	 */
	public function is_doc_image(): bool {
		return self::$doc_image;
	}

	/**
	 * Taxonomy key - Workflow state or EditFlow or PublishPress statuses to use.
	 *
	 * @var string
	 *
	 * @since 3.3.0
	 */
	public static $taxonomy_key_val = 'workflow_state';

	/**
	 * Function to return Taxonomy key.
	 *
	 * @return string
	 *
	 * @since 3.3.0
	 */
	public static function taxonomy_key(): string {
		return self::$taxonomy_key_val;
	}

	/**
	 * List of document revisions to keep (used to keep them if other processes would delete them).
	 *
	 * @var int[][]
	 *
	 * @since 3.7.0
	 */
	private static $revns = array();

	/**
	 * Initiates an instance of the class and adds hooks.
	 *
	 * @since 0.5
	 */
	public function __construct() {
		self::$instance = $this;

		// set the standard default directory - creating the cache (before applying filter).
		self::$wp_default_dir = wp_upload_dir( null, true, true );

		// admin. translations need to be called on init, not plugins_loaded.
		add_action( 'plugins_loaded', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'i18n' ), 5 );
		add_action( 'admin_notices', array( &$this, 'activation_error_notice' ) );

		// CPT/CT.
		add_action( 'init', array( &$this, 'register_cpt' ) );
		add_action( 'init', array( &$this, 'register_ct' ), 2000 ); // note: low priority to allow for edit flow/publishpress support.
		add_action( 'admin_init', array( &$this, 'initialize_workflow_states' ) );
		// check whether to invoke old or new count method (Change will need #38843 - deal with beta release).
		global $wp_version;
		$vers = strpos( $wp_version, '-' );
		$vers = $vers ? substr( $wp_version, 0, $vers ) : $wp_version;
		if ( version_compare( $vers, '5.7' ) >= 0 ) {
			// core method introduced with version 5.7.
			add_filter( 'update_post_term_count_statuses', array( &$this, 'review_count_statuses' ), 30, 2 );
		} else {
			add_action( 'admin_init', array( &$this, 'register_term_count_cb' ), 2000 ); // note: late and low priority to allow for all taxonomies.
		}
		add_filter( 'the_content', array( &$this, 'content_filter' ), 1 );

		// filter the queries to ensure readable.
		add_action( 'pre_get_posts', array( &$this, 'retrieve_documents' ) );

		// rewrites and permalinks.
		/**
		 * Filter to stop direct file access to documents (specify the URL element (or trailing part) to traverse to the document directory.
		 *
		 * By default, documents can be accessed directly using a URL to the document file.
		 *
		 * To stop this, add an element to specify all or part of the URL to access the document.
		 *
		 * If you have a separate document library and you specify the access path to the library
		 * then it is safe to use.
		 *
		 * You should not use this if your document library contains files using MD5-encoded names
		 * that are not documents and that could be read directly since it creates a pattern-based rule
		 * to block access.
		 *
		 * @since 3.6
		 *
		 * @param string ''  helps define a more specific sub-directory for no direct file access.
		 */
		if ( '' !== apply_filters( 'document_stop_file_access_pattern', '' ) ) {
			add_action( 'generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ) );
			add_filter( 'mod_rewrite_rules', array( &$this, 'mod_rewrite_rules' ) );
		}
		add_filter( 'rewrite_rules_array', array( &$this, 'revision_rewrite' ) );
		add_filter( 'transient_rewrite_rules', array( &$this, 'revision_rewrite' ) );
		add_action( 'init', array( &$this, 'inject_rules' ) );
		add_action( 'post_type_link', array( &$this, 'permalink' ), 10, 3 );
		add_action( 'post_link', array( &$this, 'permalink' ), 10, 3 );
		add_filter( 'template_include', array( &$this, 'serve_file' ), 10, 1 );
		add_filter( 'serve_document_auth', array( &$this, 'serve_document_auth' ), 10, 3 );
		add_action( 'parse_request', array( &$this, 'ie_cache_fix' ) );
		add_filter( 'query_vars', array( &$this, 'add_query_var' ) );
		add_filter( 'default_feed', array( &$this, 'hijack_feed' ) );
		add_action( 'do_feed_revision_log', array( &$this, 'do_feed_revision_log' ) );
		add_action( 'template_redirect', array( &$this, 'revision_feed_auth' ) );
		add_filter( 'get_sample_permalink_html', array( &$this, 'sample_permalink_html_filter' ), 10, 5 );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );
		add_filter( 'image_downsize', array( &$this, 'image_downsize' ), 10, 3 );
		add_filter( 'document_path', array( &$this, 'wamp_document_path_filter' ), 9, 1 );
		add_filter( 'redirect_canonical', array( &$this, 'redirect_canonical_filter' ), 10, 2 );
		add_action( 'wp_ajax_sample-permalink', array( &$this, 'update_post_slug_field' ), 0 );

		// RSS.
		add_filter( 'private_title_format', array( &$this, 'no_title_prepend' ), 20 );
		add_filter( 'protected_title_format', array( &$this, 'no_title_prepend' ), 20 );
		add_filter( 'the_title', array( &$this, 'add_revision_num_to_title' ), 20, 2 );

		// uploads.
		add_filter( 'attachment_link', array( &$this, 'attachment_link_filter' ), 10, 2 );
		add_filter( 'get_attached_file', array( &$this, 'get_attached_file_filter' ), 10, 2 );
		add_filter( 'wp_handle_upload_prefilter', array( &$this, 'filename_rewrite' ) );
		add_filter( 'wp_handle_upload', array( &$this, 'rewrite_file_url' ), 10, 2 );
		// Hide slug by changing metadata name - do early in case of WPML.
		add_filter( 'wp_generate_attachment_metadata', array( &$this, 'hide_doc_attach_slug' ), 5, 3 );
		// initialise document directory (will itself populate cache).
		$this->document_upload_dir();

		// locking.
		add_action( 'wp_ajax_override_lock', array( &$this, 'override_lock' ) );

		// cache clean.
		add_action( 'save_post_document', array( &$this, 'clear_cache' ), 20, 3 );

		// Edit Flow or PublishPress Statuses.
		add_action( 'ef_module_options_loaded', array( &$this, 'edit_flow_support' ) );
		add_action( 'pp_statuses_init', array( &$this, 'publishpress_statuses_support' ), 20 );
		// always called to determine whether user has turned off workflow_state support.
		add_action( 'init', array( &$this, 'disable_workflow_states' ), 1900 );

		// don't leak summary information if user can't access admin pages.
		add_filter( 'get_the_excerpt', array( &$this, 'empty_excerpt_return' ), 10, 2 );

		// no next/previous navigation links (would appear on password entry page).
		add_filter( 'get_next_post_where', array( &$this, 'suppress_adjacent_doc' ), 10, 5 );
		add_filter( 'get_previous_post_where', array( &$this, 'suppress_adjacent_doc' ), 10, 5 );

		// block external processes from deleting revisions.
		add_filter( 'pre_delete_post', array( $this, 'possibly_delete_revision' ), 9999, 3 );

		// revisions management.
		add_filter( 'wp_revisions_to_keep', array( $this, 'manage_document_revisions_limit' ), 999, 2 );

		// load front-end features (shortcode, widgets, etc.).
		// For shortcode blocks, json endpoint need to link back to front end and widget so make global.
		global $wpdr_fe, $wpdr_widget;

		if ( ! $wpdr_fe ) {
			require_once __DIR__ . '/class-wp-document-revisions-front-end.php';
			$wpdr_fe = new WP_Document_Revisions_Front_End( $this );
		}
		if ( ! $wpdr_widget ) {
			require_once __DIR__ . '/class-wp-document-revisions-recently-revised-widget.php';
			$wpdr_widget = new WP_Document_Revisions_Recently_Revised_Widget();
			add_action( 'widgets_init', array( $wpdr_widget, 'wpdr_widgets_init' ) );
			add_action( 'init', array( $wpdr_widget, 'wpdr_widgets_block_init' ), 99 );
		}

		// load validation code.
		require_once __DIR__ . '/class-wp-document-revisions-validate-structure.php';
		new WP_Document_Revisions_Validate_Structure( $this );

		// Manage REST interface for documents (include code).
		add_action( 'rest_api_init', array( &$this, 'manage_rest' ) );
	}

	/**
	 * Callback called when the plugin is initially activated
	 *
	 * Registers custom capabilities and flushes rewrite rules.
	 *
	 * @return void
	 */
	public function activation_hook(): void {
		$this->add_caps();
		flush_rewrite_rules();
		if ( ! current_user_can( 'edit_documents' ) ) {
			// Unfortunately we cannot create a message directly out of the activation process, so create transient data.
			set_transient( 'wpdr_activation_issue', get_current_user_id() );
		}
	}

	/**
	 * Called after the plugin is initially activated and checks whether there was a problem with the user not having edit_documents capability.
	 *
	 * This can occur if the (admin) user has multiple roles with one denying access overriding the admin access.
	 *
	 * @since 3.2.3
	 * @return void
	 */
	public function activation_error_notice(): void {
		$transient = get_transient( 'wpdr_activation_issue' );
		if ( false !== $transient && get_current_user_id() === (int) $transient ) {
			delete_transient( 'wpdr_activation_issue' );
			// timing of initial permissions being set as can give message before initial activation.
			?>
			<div class="notice notice-warning is-dismissible"><p>
			<?php esc_html_e( 'You have activated the plugin WP Document Revisions', 'wp-document-revisions' ); ?>
			</p><p>
			<?php esc_html_e( 'You do not have the edit_documents capability possibly due to multiple conficting roles or use of a custom role!', 'wp-document-revisions' ); ?>
			</p><p>
			<?php esc_html_e( 'The Documents menu may not be displayed completely with the "All Documents" and "Add Document" options missing', 'wp-document-revisions' ); ?>
			</p></div>
			<?php esc_html_e( 'You should first check whether you have multiple roles and that each has edit_documents capability.', 'wp-document-revisions' ); ?>
			</p></div>
			<?php
		}
	}

	/**
	 * Init i18n files
	 * Must be done early on init because they need to be in place when register_cpt is called.
	 */
	public function i18n(): void {
		load_plugin_textdomain( 'wp-document-revisions', false, plugin_basename( dirname( __DIR__ ) ) . '/languages/' );
	}

	/**
	 * Extends class with admin functions when in admin backend.
	 *
	 * @since 0.5
	 * @param bool $test whether to force the test scenario.
	 */
	public function admin_init( bool $test = false ): void {

		// check not already defined.
		if ( ! is_null( $this->admin ) ) {
			return;
		}

		// Unless under test, only fire on admin + escape hatch to prevent fatal errors.
		if ( is_admin() || class_exists( 'WP_UnitTestCase' ) || $test ) {
			if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
				include_once __DIR__ . '/trait-wp-document-revisions-admin-editor.php';
				include_once __DIR__ . '/trait-wp-document-revisions-admin-list.php';
				include_once __DIR__ . '/trait-wp-document-revisions-admin-settings.php';
				include_once __DIR__ . '/class-wp-document-revisions-admin.php';
			}
			$this->admin = new WP_Document_Revisions_Admin( self::$instance );
		}
	}

	/**
	 * Registers the document custom post type.
	 *
	 * @since 0.5
	 */
	public function register_cpt(): void {
		$labels = array(
			'name'                  => _x( 'Documents', 'post type general name', 'wp-document-revisions' ),
			'singular_name'         => _x( 'Document', 'post type singular name', 'wp-document-revisions' ),
			'add_new'               => _x( 'Add Document', 'document', 'wp-document-revisions' ),
			'add_new_item'          => __( 'Add New Document', 'wp-document-revisions' ),
			'edit_item'             => __( 'Edit Document', 'wp-document-revisions' ),
			'new_item'              => __( 'New Document', 'wp-document-revisions' ),
			'view_item'             => __( 'View Document', 'wp-document-revisions' ),
			'view_items'            => __( 'View Documents', 'wp-document-revisions' ),
			'search_items'          => __( 'Search Documents', 'wp-document-revisions' ),
			'not_found'             => __( 'No documents found', 'wp-document-revisions' ),
			'not_found_in_trash'    => __( 'No documents found in Trash', 'wp-document-revisions' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Documents', 'wp-document-revisions' ),
			'all_items'             => __( 'All Documents', 'wp-document-revisions' ),
			'featured_image'        => __( 'Document Image', 'wp-document-revisions' ),
			'set_featured_image'    => __( 'Set Document Image', 'wp-document-revisions' ),
			'remove_featured_image' => __( 'Remove Document Image', 'wp-document-revisions' ),
			'use_featured_image'    => __( 'Use as Document Image', 'wp-document-revisions' ),
		);

		$args = array(
			'labels'               => $labels,
			'publicly_queryable'   => true,
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'query_var'            => true,
			'rewrite'              => true,
			'capability_type'      => array( 'document', 'documents' ),
			'map_meta_cap'         => true,
			'has_archive'          => true,
			'hierarchical'         => false,
			'menu_position'        => 42,
			'register_meta_box_cb' => array( $this->admin, 'meta_cb' ),
			'supports'             => array( 'title', 'editor', 'author', 'revisions', 'custom-fields', 'thumbnail' ),
			'menu_icon'            => plugins_url( '../img/menu-icon.png', __FILE__ ),
		);

		// Ordinarily show_in_rest is set to false, but can turn it on.
		/**
		 * Filters the show_in_rest parameter from its default value of fa1se.
		 *
		 * @since 3.4
		 *
		 * @param boolean false default not to be in rest.
		 */
		if ( apply_filters( 'document_show_in_rest', false ) ) {
			$args['show_in_rest'] = true;
			$args['rest_base']    = $this->document_slug();
		}

		// Ordinarily read_post (read_document) maps to read, but if read not to be used, we need to map to primitive read_documents.
		/**
		 * Filters the users capacities to require read (or read_document) capability.
		 *
		 * @since 3.3
		 *
		 * @param boolean true  default action to capability read for documents (not read_document).
		 */
		// user requires read_document and not just read to read document.
		if ( ! apply_filters( 'document_read_uses_read', true ) ) {
			// invoke logic to require read_documents instead of default read .
			$args['capabilities'] = array(
				'read' => 'read_documents',
			);
			if ( ! current_user_can( 'read_documents' ) ) {
				// user does not have read_documents capability, so any need to be filtered out of results.
				add_filter( 'posts_results', array( &$this, 'posts_results' ), 10, 2 );
			}
		}

		/**
		 * Filters the delivered document type definition prior to registering it.
		 *
		 * @since 0.5
		 *
		 * @param array $args delivered document type definition.
		 */
		register_post_type( 'document', apply_filters( 'document_revisions_cpt', $args ) );

		// Although default is to support thumbnails on document, this could be filtered away.
		if ( post_type_supports( 'document', 'thumbnail' ) && current_theme_supports( 'post-thumbnails', 'document' ) ) {
			// Thumbnails are supported.
			// Ensure that there is a post-thumbnail size set - could/should be set by theme - default copy from thumbnail.
			if ( ! array_key_exists( 'post-thumbnail', wp_get_additional_image_sizes() ) ) {
				// get sizing dynamically.
				add_filter( 'post_thumbnail_size', array( &$this, 'document_featured_image_size' ), 10, 2 );
			}
		}

		// Set Global for Document Image from Cookie doc_image (may be updated later).
		self::$doc_image = ( isset( $_COOKIE['doc_image'] ) ? 'true' === $_COOKIE['doc_image'] : true );
	}

	/**
	 * Registers custom status taxonomy.
	 *
	 * @since 0.5
	 */
	public function register_ct(): void {
		$labels = array(
			'name'              => _x( 'Workflow States', 'taxonomy general name', 'wp-document-revisions' ),
			'singular_name'     => _x( 'Workflow State', 'taxonomy singular name', 'wp-document-revisions' ),
			'search_items'      => __( 'Search Workflow States', 'wp-document-revisions' ),
			'all_items'         => __( 'All Workflow States', 'wp-document-revisions' ),
			'parent_item'       => __( 'Parent Workflow State', 'wp-document-revisions' ),
			'parent_item_colon' => __( 'Parent Workflow State:', 'wp-document-revisions' ),
			'edit_item'         => __( 'Edit Workflow State', 'wp-document-revisions' ),
			'update_item'       => __( 'Update Workflow State', 'wp-document-revisions' ),
			'add_new_item'      => __( 'Add New Workflow State', 'wp-document-revisions' ),
			'new_item_name'     => __( 'New Workflow State Name', 'wp-document-revisions' ),
			'menu_name'         => __( 'Workflow States', 'wp-document-revisions' ),
		);

		// check whether to invoke old or new count method (Change will need #38843 - deal with beta release).
		global $wp_version;
		$vers = strpos( $wp_version, '-' );
		$vers = $vers ? substr( $wp_version, 0, $vers ) : $wp_version;
		if ( version_compare( $vers, '5.7' ) >= 0 ) {
			// core method introduced with version 5.7. callback not needed.
			$ucc = '';
		} else {
			$ucc = array( &$this, 'term_count_cb' );
		}

		/**
		 * Filters the default structure and label values of the workflow_state taxonomy on declaration.
		 *
		 * @since 0.5
		 *
		 * @param array of default structure and label workflow_state values.
		 */
		register_taxonomy(
			'workflow_state',
			array( 'document' ),
			apply_filters(
				'document_revisions_ct',
				array(
					'hierarchical'          => false,
					'labels'                => $labels,
					'show_ui'               => true,
					'rewrite'               => false,
					'update_count_callback' => $ucc,
					'show_admin_column'     => true,
					'show_in_rest'          => true,
				)
			)
		);
	}

	/**
	 * Propagates initial workflow states on plugin activation.
	 *
	 * @since 0.5
	 * @return void
	 */
	public function initialize_workflow_states(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);

		if ( ! empty( $terms ) ) {
			return;
		}

		$states = array(
			__( 'In Progress', 'wp-document-revisions' )   => __( 'Document is in the process of being written', 'wp-document-revisions' ),
			__( 'Initial Draft', 'wp-document-revisions' ) => __( 'Document is being edited and refined', 'wp-document-revisions' ),
			__( 'Under Review', 'wp-document-revisions' )  => __( 'Document is pending final review', 'wp-document-revisions' ),
			__( 'Final', 'wp-document-revisions' )         => __( 'Document is in its final form', 'wp-document-revisions' ),
		);

		/**
		 * Filters the default workflow state values.
		 *
		 * @since 0.5
		 *
		 * @param array $states default workflow_state values.
		 */
		$states = apply_filters( 'default_workflow_states', $states );

		foreach ( $states as $state => $desc ) {
			wp_insert_term(
				$state,
				'workflow_state',
				array(
					'description' => $desc,
				)
			);
		}
	}

	/**
	 * Checks if the attachment is a document.
	 *
	 * @since 3.5.0.
	 *
	 * @param WP_Post $attach An attachment object.
	 */
	public static function check_doc_attach( WP_Post $attach ): bool {
		if ( 'document' !== get_post_type( $attach->post_parent ) ) {
			return false;
		}
		// normal document attachment.
		if ( $attach->post_title === $attach->post_name ) {
			return true;
		}
		// duplicate name.
		if ( 0 === strpos( $attach->post_name, $attach->post_title . '-' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Adds doc-specific caps to all roles so that 3rd party plugins can manage them
	 * Gives admins all caps.

	 * @since 1.0
	 */
	public function add_caps(): void {
		global $wp_roles;
		if ( ! is_object( $wp_roles ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_roles = new WP_Roles();
		}

		// default role => capability mapping; based off of _post options
		// can be overridden by 3d party plugins.
		$defaults = array(
			'administrator' =>
			array(
				'edit_documents'             => true,
				'edit_others_documents'      => true,
				'edit_private_documents'     => true,
				'edit_published_documents'   => true,
				'read_documents'             => true,
				'read_document_revisions'    => true,
				'read_private_documents'     => true,
				'delete_documents'           => true,
				'delete_others_documents'    => true,
				'delete_private_documents'   => true,
				'delete_published_documents' => true,
				'publish_documents'          => true,
				'override_document_lock'     => true,
			),
			'editor'        =>
			array(
				'edit_documents'             => true,
				'edit_others_documents'      => true,
				'edit_private_documents'     => true,
				'edit_published_documents'   => true,
				'read_documents'             => true,
				'read_document_revisions'    => true,
				'read_private_documents'     => true,
				'delete_documents'           => true,
				'delete_others_documents'    => true,
				'delete_private_documents'   => true,
				'delete_published_documents' => true,
				'publish_documents'          => true,
				'override_document_lock'     => true,
			),
			'author'        =>
			array(
				'edit_documents'             => true,
				'edit_others_documents'      => false,
				'edit_private_documents'     => false,
				'edit_published_documents'   => true,
				'read_documents'             => true,
				'read_document_revisions'    => true,
				'read_private_documents'     => false,
				'delete_documents'           => true,
				'delete_others_documents'    => false,
				'delete_private_documents'   => false,
				'delete_published_documents' => true,
				'publish_documents'          => true,
				'override_document_lock'     => false,
			),
			'contributor'   =>
			array(
				'edit_documents'             => true,
				'edit_others_documents'      => false,
				'edit_private_documents'     => false,
				'edit_published_documents'   => false,
				'read_documents'             => true,
				'read_document_revisions'    => true,
				'read_private_documents'     => false,
				'delete_documents'           => true,
				'delete_others_documents'    => false,
				'delete_private_documents'   => false,
				'delete_published_documents' => false,
				'publish_documents'          => false,
				'override_document_lock'     => false,
			),
			'subscriber'    =>
			array(
				'edit_documents'             => false,
				'edit_others_documents'      => false,
				'edit_private_documents'     => false,
				'edit_published_documents'   => false,
				'read_documents'             => true,
				'read_document_revisions'    => false,
				'read_private_documents'     => false,
				'delete_documents'           => false,
				'delete_others_documents'    => false,
				'delete_private_documents'   => false,
				'delete_published_documents' => false,
				'publish_documents'          => false,
				'override_document_lock'     => false,
			),
		);

		foreach ( $wp_roles->role_names as $role => $label ) {

			// if the role is a standard role, map the default caps, otherwise, map as a subscriber.
			$caps = ( array_key_exists( $role, $defaults ) ) ? $defaults[ $role ] : $defaults['subscriber'];

			/**
			 * Filter the default capabilities.
			 *
			 * @param array  $caps the default set of capabilities for the role.
			 * @param string $role the role being reviewed (all will be reviewed in turn).
			 */
			$caps = apply_filters( 'document_caps', $caps, $role );

			$role_caps = $wp_roles->roles[ $role ]['capabilities'];
			// if the 'read_documents' capability exists for the role, then assume others are as required.
			if ( array_key_exists( 'read_documents', $role_caps ) ) {
				continue;
			}
			// loop  through capacities for role.
			foreach ( $caps as $cap => $grant ) {
				// add only missing capabilities.
				if ( ! array_key_exists( $cap, $role_caps ) ) {
					$wp_roles->add_cap( $role, $cap, $grant );
				}
			}
		}
	}

	/**
	 * Provides support for edit flow and disables the default workflow state taxonomy.
	 */
	public function edit_flow_support(): void {
		// verify edit flow is enabled.
		/**
		 * Filter to switch off integration with Edit_Flow statuses.
		 *
		 * @param boolean true default value to use Edit_Flow processes if installed and active.
		 */
		if ( ! class_exists( 'EF_Custom_Status' ) || ! apply_filters( 'document_revisions_use_edit_flow', true ) ) {
			return;
		}

		global $edit_flow;

		// verify custom_status is enabled.
		if ( ! $edit_flow->custom_status->module_enabled( 'custom_status' ) ) {
			return;
		}

		// prevent errors if options aren't init'd yet.
		if ( ! isset( $edit_flow->custom_status->module->options->post_types['document'] ) ) {
			return;
		}

		// check if enabled.
		if ( 'off' === $edit_flow->custom_status->module->options->post_types['document'] ) {
			return;
		}

		// update the taxonomy key.
		self::$taxonomy_key_val = EF_Custom_Status::taxonomy_key;

		// EF doesn't add Status to Document view so need to add it.
		add_filter( 'manage_document_posts_columns', array( &$this, 'add_post_status_column' ) );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'post_status_column_cb' ), 10, 2 );

		// workflow_state will be used as a query_var, but is not one.
		add_filter( 'query_vars', array( &$this, 'add_qv_workflow_state' ), 10 );

		// we are going to use Edit_Flow / Publish Press processes if installed and active.
		// make sure use_workflow_states returns false.
		add_filter( 'document_use_workflow_states', '__return_false' );
	}

	/**
	 * Provides support for PublishPress Statuses and disables the default workflow state taxonomy.
	 *
	 * @since 3.2.3
	 */
	public function publishpress_statuses_support(): void {
		// verify publishpress is enabled.
		/**
		 * Filter to switch off integration with PublishPress statuses.
		 *
		 * @param boolean true default value to use PublishPress Statuses processes if installed and active.
		 */
		if ( ! class_exists( 'PublishPress_Statuses' ) || ! apply_filters( 'document_revisions_use_edit_flow', true ) ) {
			return;
		}

		// prevent errors if options aren't init'd yet.
		if ( ! isset( PublishPress_Statuses::instance()->options->post_types['document'] ) ) {
			return;
		}

		// check if enabled for documents.
		if ( 'off' === PublishPress_Statuses::instance()->options->enabled ) {
			return;
		}

		// update the taxonomy key.
		self::$taxonomy_key_val = 'post_status';

		// PPS doesn't add Status to Document view so need to add it.
		add_filter( 'manage_document_posts_columns', array( &$this, 'add_post_status_column' ) );
		add_action( 'manage_document_posts_custom_column', array( &$this, 'post_status_column_cb' ), 10, 2 );

		// workflow_state will be used as a query_var, but is not one.
		add_filter( 'query_vars', array( &$this, 'add_qv_workflow_state' ), 10 );

		// we are going to use PPS processes if installed and active.
		// make sure use_workflow_states returns false.
		add_filter( 'document_use_workflow_states', '__return_false' );
	}

	/**
	 * Toggles workflow states on and off.
	 *
	 * @return bool true if workflow states are on, otherwise false
	 */
	public function use_workflow_states(): bool {
		/**
		 * Filter to switch off use of Edit_Flow statuses and taxonomy.
		 *
		 * @param boolean true default value to use Edit_Flow processes if installed and active. Normally internally used.
		 */
		return apply_filters( 'document_use_workflow_states', true );
	}

	/**
	 * Removes front-end hooks to add workflow state support.
	 */
	public function disable_workflow_states(): void {
		if ( $this->use_workflow_states() ) {
			return;
		}

		// Have not changed taxonomy key for EF/PP support, so user turned off and neither should exist.
		if ( 'workflow_state' === self::$taxonomy_key_val ) {
			self::$taxonomy_key_val = '';
		}

		remove_action( 'admin_init', array( &$this, 'initialize_workflow_states' ) );
		remove_action( 'init', array( &$this, 'register_ct' ), 2000 );
	}

	/**
	 * Manage Rest interface for documents.
	 * Information needs to be hidden when REST is used.
	 */
	public function manage_rest(): void {
		$obj = get_post_type_object( 'document' );
		if ( ! $obj->show_in_rest ) {
			return;
		}

		global $wpdr_mr;
		if ( ! $wpdr_mr ) {
			if ( ! class_exists( 'WP_Document_Revisions_Manage_Rest' ) ) {
				include_once __DIR__ . '/class-wp-document-revisions-manage-rest.php';
			}
			$wpdr_mr = new WP_Document_Revisions_Manage_Rest( $this );
		}
	}

	/**
	 * Remove nocache headers from document downloads on IE < 8
	 * Hooked into parse_request so we can fire after request is parsed, but before headers are sent
	 * See http://support.microsoft.com/kb/323308.
	 *
	 * @param Object $wp The global WP object.
	 * @return the WP global object
	 */
	public function ie_cache_fix( object $wp ): object {
		// SSL check.
		if ( ! is_ssl() ) {
			return $wp;
		}

		// IE check.
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) || stripos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 'MSIE' ) === false ) {
			return $wp;
		}

		// verify that they are requesting a document.
		if ( ! isset( $wp->query_vars['post_type'] ) || 'document' !== $wp->query_vars['post_type'] ) {
			return $wp;
		}

		add_filter( 'nocache_headers', '__return_empty_array' );

		return $wp;
	}
}
