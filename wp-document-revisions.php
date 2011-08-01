<?php
/*
Plugin Name: WP Document Revisions
Plugin URI: http://
Description: Document Revisioning and Version Control for WordPress; GSoC 2011.
Version: 0.5.6
Author: Benjamin J. Balter
Author URI: http://ben.balter.com
License: GPL2
*/

class Document_Revisions {
	static $instance;
	static $key_length = 32;
	static $meta_key = 'document_revisions_feed_key';
	
	/**
	 * Initiates an instance of the class and adds hooks
	 * @since 0.5
	 */	
	function __construct() {
	
		self::$instance = $this;

		//admin
		add_action( 'auth_redirect', array( &$this, 'admin_init' ) );
		
		//CPT/CT
		add_action( 'init', array( &$this, 'register_cpt' ) );
		add_action( 'init', array( &$this, 'register_ct' ) );
		add_action( 'admin_init', array( &$this, 'initialize_workflow_states' ) );
		add_action( 'init', array( &$this, 'add_caps' ), 1 );

		//rewrites and permalinks
		add_filter( 'rewrite_rules_array' , array( &$this, 'revision_rewrite' ) );		
		add_filter( 'init', array( &$this, 'inject_rules' ) );
		add_action( 'post_type_link', array(&$this,'permalink'), 10, 4 );
		add_action( 'post_link', array(&$this,'permalink'), 10, 4 );
		add_filter( 'single_template', array(&$this, 'serve_file'), 10, 1 );
	 	add_filter( 'query_vars', array(&$this, 'add_query_var'), 10, 4 );
		register_activation_hook( __FILE__, 'flush_rewrite_rules' );
		add_filter( 'default_feed', array( &$this, 'hijack_feed' ), 10, 2);
		add_action( 'do_feed_revision_log', array( &$this, 'do_feed_revision_log' ) );
		add_action( 'template_redirect', array( $this, 'revision_feed_auth' ) );
		add_filter( 'get_sample_permalink_html', array(&$this, 'sample_permalink_html_filter'), 10, 4);

		//uploads
		add_filter( 'upload_dir', array( &$this, 'document_upload_dir_filter' ), 10, 2);
		add_filter( 'attachment_link', array( &$this, 'attachment_link_filter'), 10, 2);
		add_filter( 'wp_handle_upload_prefilter', array(&$this, 'filename_rewrite' ) );
		add_filter( 'wp_handle_upload', array( &$this, 'rewrite_file_url' ), 10, 2);

		//locking
		add_action( 'wp_ajax_override_lock', array( &$this, 'override_lock' ) );
		
	}

	/**
	 * Extends class with admin functions when in admin backend
	 * @since 0.5
	 */
	function admin_init() {
		include( dirname( __FILE__ ) . '/admin.php' );
		$this->admin = new Document_Revisions_Admin( self::$instance );
	}

	/** 
	 * Registers the document custom post type
	 * @since 0.5
	 */
	function register_cpt(){
		
		$labels = array(
		'name' => _x( 'Documents', 'post type general name', 'wp_document_revisions' ),
		'singular_name' => _x( 'Document', 'post type singular name', 'wp_document_revisions' ),
		'add_new' => _x( 'Add Document', 'document', 'wp_document_revisions' ),
		'add_new_item' => __( 'Add New Document', 'wp_document_revisions' ),
		'edit_item' => __( 'Edit Document', 'wp_document_revisions' ),
		'new_item' => __( 'New Document', 'wp_document_revisions' ),
		'view_item' => __( 'View Document', 'wp_document_revisions' ),
		'search_items' => __( 'Search Documents', 'wp_document_revisions' ),
		'not_found' =>__( 'No documents found', 'wp_document_revisions' ),
		'not_found_in_trash' => __( 'No documents found in Trash', 'wp_document_revisions' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'Documents', 'wp_document_revisions' ),
		);
		
		$args = array(
		'labels' => $labels,
		'publicly_queryable' => true,
		'public' => true,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => array( 'document', 'documents'),
		'map_meta_cap' => true,
		'has_archive' => false, 
		'hierarchical' => false,
		'menu_position' => null,
		'register_meta_box_cb' => array( &$this->admin, 'meta_cb' ),
		'supports' => array( 'title', 'author', 'revisions', 'excerpt', 'custom-fields' )
		); 
		
		register_post_type( 'document', apply_filters( 'document_revisions_cpt', $args ) );
		
		}
		
	/**
	 * Registers custom status taxonomy
	 * @since 0.5
	 * @todo is this the best name? Document Status? Don't want to confuse w/ wp's status field; it's somewhat of a term of art used by SharePoint, Drupal, etc.
	 */
	function register_ct() {
	
 		$labels = array(
 		 'name' => _x( 'Workflow States', 'taxonomy general name', 'wp_document_revisions' ),
 		 'singular_name' => _x( 'Workflow State', 'taxonomy singular name', 'wp_document_revisions'),
 		 'search_items' =>__( 'Search Workflow States', 'wp_document_revisions' ),
 		 'all_items' => __( 'All Workflow States', 'wp_document_revisions' ),
 		 'parent_item' => __( 'Parent Workflow State', 'wp_document_revisions' ),
 		 'parent_item_colon' => __( 'Parent Workflow State:', 'wp_document_revisions' ),
 		 'edit_item' => __( 'Edit Workflow State', 'wp_document_revisions' ), 
 		 'update_item' => __( 'Update Workflow State', 'wp_document_revisions' ),
 		 'add_new_item' => __( 'Add New Workflow State', 'wp_document_revisions' ),
 		 'new_item_name' => __( 'New Workflow State Name', 'wp_document_revisions' ),
 		 'menu_name' => __( 'Workflow States', 'wp_document_revisions' ),
 		); 	
 	
 		register_taxonomy( 'workflow_state', array('document'), apply_filters( 'document_revisions_ct', array(
 	 		'hierarchical' => false,
 	 		'labels' => $labels,
 	 		'show_ui' => true,
 	 		'query_var' => false,
 	 		'rewrite' => false,
 	 	) ) );
	
	}
	
	/**
	 * Propagates initial workflow states on plugin activation
	 * @since 0.5
	 * @todo are these the best initial states?
	 */
	function initialize_workflow_states() {

		$terms = get_terms( 'workflow_state', array( 'hide_empty' => false ) );

		if ( !empty( $terms ) )
			return false;
			
		$states = array( 	
					__('In Progress', 'wp_document_revisions') => __('Document is in the process of being written', 'wp_document_revisions'), 
					__('Initial Draft', 'wp_document_revisions') => __('Document is being edited and refined','wp_document_revisions'), 
					__('Under Review', 'wp_document_revisions') => __('Document is pending final review', 'wp_document_revisions'), 
					__('Final', 'wp_document_revisions') => __('Document is in its final form', 'wp_document_revisions'), 
				);
		
		$states = apply_filters( 'default_workflow_states', $states );
		
		foreach ( $states as $state => $desc ) {
			wp_insert_term( $state, 'workflow_state', array( 'description' => $desc ) );
		}
	
	}
	
	/**
	 * Defaults document visibility to private 
	 * @since 0.5
	 */
	function make_private() {
		global $post;	
		
		//verify that this is a new document		
		if ( !isset( $post) || !$this->verify_post_type() || strlen( $post->post_content ) > 0 )
			return;
		
		$post_pre = $post;

		if ( $post->post_status == 'draft' || $post->post_status == 'auto-draft' )
			$post->post_status = 'private';
	
		$post = apply_filters( 'document_to_private', $post, $post_pre );
	
	}
	
	/**
	 * Given a post object, returns all attached uploads
	 * @param object $post post object
	 * @returns object all attached uploads
	 * @since 0.5
	 */
	function get_attachments( $post = '' ) {
		
		if ($post == '')
			global $post;
			
		if ( !is_object( $post ) )
			$post = get_post( $post );
		
		if ( !isset( $post->ID ) )
			return false;

		$args = array(	
				'post_parent' => $post->ID, 
				'post_status' => 'inherit', 
				'post_type' => 'attachment', 
				'order' => 'DESC', 
				'orderby' => 'post_date',
				);

		$args = apply_filters( 'document_revision_query', $args );
		
		return get_children( $args );
	
	}

	/**
	 * Checks if document is locked, if so, returns the lock holder's name
	 * @param object $post the post object
	 * @returns bool|string false if no lock, user's display name if locked
	 * @since 0.5
	 */
	function get_document_lock( $post ) {
	
		//get the post lock
		if ( !( $user = wp_check_post_lock( $post->ID ) ) ) 
			return false;
			 
		//get displayname from userID
		$last_user = get_userdata( $user );
		return ( $last_user ) ? $last_user->display_name : __( 'Somebody' );
				
	}
	
	/**
	 * Given a file, returns the file's extension
	 * @param string $file URL, path, or filename to file
	 * @returns string extension
	 * @since 0.5
	 */
	function get_extension( $file ) {

		$extension = '.' . pathinfo( $file, PATHINFO_EXTENSION );

		//don't return a . extension
		if ( $extension == '.' )
			return '';
			
 		return apply_filters( 'document_extension', $extension, $file );
	
	}
	
	/**
	 * Gets a file extension from a post
	 * @param object $post post object
	 * @returns string the extension to the latest revision
	 * @since 0.5
	 */
	function get_file_type( $post = '' ) {
	 	
	 	if ( $post == '' )
	 		global $post;
	 		 
		return $this->get_extension( $this->get_latest_version_url( $post->ID ) );
			
	}
	
	/**
	 * Adds document CPT rewrite rules
	 * @since 0.5
	 */
	function inject_rules(){
	
		global $wp_rewrite;
		$wp_rewrite->add_rewrite_tag( "%document%", '([^.]+)\.[A-Za-z0-9]{3,4}?', 'document=' );

	}

	/**
	 * Adds document rewrite rules to the rewrite array
	 * @since 0.5
	 * @param $rules array rewrite rules
	 * @returns array rewrite rules
	 */
	function revision_rewrite( $rules ) {
	
		$my_rules = array();
		
		//revisions in the form of yyyy/mm/[slug]-revision-##.[extension], yyyy/mm/[slug]-revision-##.[extension]/, yyyy/mm/[slug]-revision-##/ and yyyy/mm/[slug]-revision-## 
		$my_rules['documents/([0-9]{4})/([0-9]{1,2})/([^.]+)-' . __( 'revision', 'wp-document-revisions' ) . '-([0-9]+)\.[A-Za-z0-9]{3,4}/?$'] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]&revision=$matches[4]';
		
		//revision feeds in the form of yyyy/mm/[slug]-revision-##.[extension]/feed/, yyyy/mm/[slug]-revision-##/feed/, etc.
		$my_rules['documents/([0-9]{4})/([0-9]{1,2})/([^.]+)(\.[A-Za-z0-9]{3,4})?/feed/?$'] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]&feed=feed';
		
		//documents in the form of yyyy/mm/[slug]-revision-##.[extension], yyyy/mm/[slug]-revision-##.[extension]/
		$my_rules['documents/([0-9]{4})/([0-9]{1,2})/([^.]+)\.[A-Za-z0-9]{3,4}/?$'] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]';
		
		$my_rules = apply_filters( 'document_rewrite_rules', $my_rules, $rules );
			
		return $my_rules + $rules;
	}

	/**
	 * Tell's WP to recognize document query vars
	 * @since 0.5
	 * @param array $vars the query vars
	 * @returns array the modified query vars
	 */
	function add_query_var( $vars ) {
		$vars[] = "revision";
		$vars[] = "document";
		return $vars;
	}

	/**
	 * Builds document post type permalink
	 * @param string $link original permalink
	 * @param object $post post object
	 * @returns string the real permalink 
	 * @since 0.5
	 */
	function permalink( $link, $post, $leavename, $sample = '' ) {

		//if this isn't our post type, kick
		if( !$this->verify_post_type( $post ) )
			return $link;

		//check if it's a revision
		if ( $post->post_type == 'revision' ) {
			$parent = get_post( $post->post_parent );
			$revision_num = $this->get_revision_number( $post->ID );
			$parent->post_name = $parent->post_name . __('-revision-', 'wp-document-revisions') . $revision_num;
			$post = $parent;
		}

		// build documents/yyyy/mm/slug		 
		$extension = $this->get_file_type( $post );
		
		$timestamp = strtotime($post->post_date);
		$link = home_url() . '/documents/' . date('Y',$timestamp) . '/' . date('m',$timestamp) . '/';
		$link .= ( $leavename ) ? '%document%' : $post->post_name;
		$link .= $extension ;
				
		$link = apply_filters( 'document_permalink', $link, $post );
				
		return $link;
	}
	
	/**
	 * Filters permalink displayed on edit screen in the event that there is no attachment yet uploaded
	 * @param string $html original HTML
	 * @param int $id Post ID
	 * @rerurns string modified HTML
	 * @since 0.5
	 */
	function sample_permalink_html_filter( $html, $id ) {

		$post = get_post( $id );
				
		//verify post type
		if ( !$this->verify_post_type( $post ) )
			return $html;
				
		//grab attachments
		$attachments = $this->get_attachments( $post );
			
		//if no attachments, return nothing
		if ( sizeof( $attachments ) == 0)
			return '';
		
		//otherwise return html unfiltered
		return $html;
 	}
	
	/**
	 * For a given post, builds a 1-indexed array of revision post ID's
	 * @param int $post_id the parent post id
	 * @returns array array of revisions
	 * @since 0.5
	 */
	function get_revision_indices( $post_id ) {
	
		$revs = wp_get_post_revisions( $post_id, array( 'order' => 'ASC' ) );
		
		$i = 1;
		foreach ( $revs as $rev )
			$output[ $i++ ] = $rev->ID;
		
		return $output;		
		
	}
	
	/**
	 * Given a revision id (post->ID) returns the revisions spot in the sequence
	 * @param int $revisiion_id the post ID of the revision
	 * @returns int revision #
	 * @since 0.5
	 */
	function get_revision_number( $revision_id ) {
	
		$revision = get_post( $revision_id );
	
		if ( !isset( $revision->post_parent ) )
			return false;
				
		$index = $this->get_revision_indices( $revision->post_parent );
		
		return array_search( $revision_id, $index );
		
	}
	
	/**
	 * Given a revision number (e.g., 4 from foo-revision-4) returns the revision ID
	 * @param int $revision_num the 1-indexed revision #
	 * @param int $post_id the ID of the parent post
	 * @returns int the ID of the revision
	 * @since 0.5
	 */
	function get_revision_id( $revision_num, $post_id ) {
	
		$index = $this->get_revision_indices( $post_id );
		
		return ( isset( $index[ $revision_num ] ) ) ? $index[ $revision_num ] : false;
	
	}
	
	/**
	 * Serves document files
	 * @param int $version ID of revision to serve
	 * @since 0.5
	 */
	function serve_file( $template ) {
		global $post;
		
		if ( !$this->verify_post_type( $post ) )
			return $template;
				
		//grab the post revision if any
		$version = get_query_var( 'revision' );
		
		//if there's not a post revision given, default to the latest
		if ( !$version ) {
			$revision = $this->get_latest_version( $post->ID );
		} else { 
			$rev_id = $this->get_revision_id ( $version, $post->ID );
			$rev_post = get_post ( $rev_id );
			$revision = get_post( $rev_post->post_content );
		}
				
		//get file
		$file = get_attached_file( $revision->ID );
		
		//return 404 if the file is a dud or malformed
		if ( validate_file( $file ) || !is_file( $file ) ) {
			status_header( 404 );
			wp_die( __( '404 &#8212; File not found.', 'wp-document-revisions' ) );
		}

		if ( !current_user_can( 'read_document', $post->ID ) || ( $version && !current_user_can( 'read_document_revsisions' ) ) )
			wp_die( __( 'You are not authorized to access that file.', 'wp-document-revisions' ) );
		
		do_action( 'serve_document', $revision->ID, $file );

		// We may override this later.
		status_header( 200 );

		//rest inspired by wp-includes/ms-files.php.
		$mime = wp_check_filetype( $file );
		if ( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
			$mime[ 'type' ] = mime_content_type( $file );

		if ( $mime[ 'type' ] )
			$mimetype = $mime[ 'type' ];
		else
			$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
			
		//fake the filename
		$filename = $post->post_name;
		$filename .= ( $version == '' ) ? '' : __( '-revision-', 'wp-document-revisions' ) . $version;
		$filename .= $this->get_extension( wp_get_attachment_url( $revision->ID ) );
		header ( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		//filetype and length
		header( 'Content-Type: ' . $mimetype ); // always send this
		header( 'Content-Length: ' . filesize( $file ) );

		//modified
		$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
		$etag = '"' . md5( $last_modified ) . '"';
		header( "Last-Modified: $last_modified GMT" );
		header( 'ETag: ' . $etag );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

		// Support for Conditional GET
		$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

		if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

		$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		
		// If string is empty, return 0. If not, attempt to parse into a timestamp
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

		// Make a timestamp for our most recent modification...
		$modified_timestamp = strtotime($last_modified);

		if ( ( $client_last_modified && $client_etag )
			? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
			: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
			) {
			status_header( 304 );
			exit;
		}
		
		//in case this is a large file, remove PHP time limits		
		set_time_limit( 0 );										

		// If we made it this far, just serve the file
		readfile( $file );

		exit();	
	}
	
	/**
	 * Given a post ID, returns the latest revision attachment
	 * @param int $id Post ID
	 * @returns object latest revision object
	 *
	 */
	function get_latest_version( $id ) {
	
		$post = get_post( $id );
		
		//verify post type
		if ( !$this->verify_post_type( $post ) )
			return false;
		
		//verify that there's an upload ID in the content field
		if ( !is_numeric( $post->post_content ) )
			return false;
			
		return get_post( $post->post_content );	
		
	}
	
	/**
	 * Returns the URL to a post's latest revision 
	 * @param int $id post ID
	 * @return string|bool URL to revision or false if no attachment
	 * @since 0.5
	 */
	function get_latest_version_url( $id ) {
	
		$latest = $this->get_latest_version( $id );
		
		if ( !$latest )
			return false;

		return wp_get_attachment_url( $latest->ID );				
	}

	/**
	 * Calculated path to upload documents
	 * @return string path to document
	 * @since 0.5
	 */ 	
 	function document_upload_dir() {

		//If user has specified, that's it
		if ( $dir = get_option( 'document_upload_directory' ) )
			return $dir;
					
		//If user hasn't specified, see if they have specified a generic upload path
		if ( $dir = get_option( 'upload_path' ) )
			return ABSPATH . $dir;
		
		//If no options set, default to wp-content/uploads
		return ABSPATH . 'wp-content/uploads';
						 	
 	}
 	
 	/**
 	 * Modifies location of uploaded document revisions
 	 * @param array $dir defaults passed from WP
 	 * @returns array $dir modified directory
 	 * @since 0.5
 	 */
 	function document_upload_dir_filter( $dir ) {
		
		if ( !$this->verify_post_type ( ) )
			return $dir;	 
			
		$dir['path'] = $this->document_upload_dir() . $dir['subdir'];
		$dir['url'] = home_url( '/documents' ) . $dir['subdir'];
		$dir['basedir'] = $this->document_upload_dir();
		$dir['baseurl'] = home_url( '/documents' );

		return $dir;

 	}
 	
 	/**
 	 * Hides file's true location from users in the Gallery
 	 * @param string $link URL to file's tru location
 	 * @param int $id attachment ID
 	 * @returns string empty string
 	 * @since 0.5
 	 */
 	function attachment_link_filter ( $link, $id ) {
 	
 		if ( !$this->verify_post_type( $id ) )
 			return $link;
 		
 		return '';
 	}
 	
 	/**
 	 * Rewrites uploaded revisions filename with secure hash to mask true location
 	 * @param array $file file data from WP
 	 * @returns array $file file with new filename
 	 * @since 0.5
 	 */
 	function filename_rewrite( $file ) {
 	
		//verify this is a document
		if ( !$this->verify_post_type( $_POST['post_id'] ) )
			return $file;
					
		//hash and replace filename, appending extension
		$file['name'] = md5( $file['name'] .time() ) . $this->get_extension( $file['name'] );

		$file = apply_filters( 'document_internal_filename', $file );

		return $file;
		
	}
	
	/**
	 * Rewrites a file URL to it's public URL
	 * @param array $file file object from WP
	 * @returns array modified file array
	 * @since 0.5
	 */
	function rewrite_file_url( $file ) {
		
		//verify that this is a document
		if ( !$this->verify_post_type( $_POST['post_id'] ) )
			return $file;

		$file['url'] = get_permalink( $_POST['post_id'] );

		return $file;
		
	} 	
	
	/**
	 * Checks if a given post is a document
	 * @param object|int either a post object or a postID
	 * @returns bool true if document, false if not
	 * @since 0.5
	 */
	function verify_post_type( $post = '' ) {
	
		//check for post_type query arg
		if ( $post == '' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'document' )
			return true;
			
		//allow the argument to be called without arguments if post is a global
		if ( $post == '')
			global $post;
		
		//if post isn't set, try get vars
		if ( !$post ) 
			$post = ( isset( $_GET['post'] ) ) ? $_GET['post'] : null;
		
		//look for post_id via post or get (media upload)
		if ( !$post ) 
			$post = ( isset( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : null;
			
		//if post is the postID, grab the object
		if ( !is_object( $post ) )
			$post = get_post ( $post );
						
		//support for revissions amd attachments
		if ( isset( $post->post_type ) && ( $post->post_type == 'revision' || $post->post_type == 'attachment' ) )
			$post = get_post( $post->post_parent );
		
		return ( isset( $post->post_type ) && $post->post_type == 'document' );
		
	}
	
	/**
	 * Callback to handle revision RSS feed
	 * @since 0.5
	 */
	function do_feed_revision_log() {
	
		include('revision-feed.php');
		exit();	
	
	}	
	
	/**
	 * Intercepts RSS feed redirect and forces our custom feed
	 * @param string $default the original feed
	 * @returns string the slug for our feed
	 * @since 0.5
	 */
	function hijack_feed( $default ) {

		if ( !$this->verify_post_type() )
			return $default;
			
		return 'revision_log';
	
	}
	
	/**
	 * Verifies that users are auth'd to view a revision feed
	 * @since 0.5
	 */
	function revision_feed_auth() {
		global $wpdb;
		
		if ( !$this->verify_post_type() )
			return;

		if ( is_feed() && !$this->validate_feed_key() ) 
			wp_die( __( 'Sorry, this is a private feed.', 'wp-document-revisions' ) );
		
	
	}
	
	/**
	 * Checks feed key before serving revision RSS feed
	 * @returns bool
	 * @since 0.5
	 */
	function validate_feed_key() {
	
		global $wpdb;
	
		//verify key exists
		if ( empty( $_GET['key'] ) )
			return false;

		//make alphanumeric
		$key = preg_replace( '/[^a-z0-9]/i', '', $_GET['key'] );

		//verify length
		if ( self::$key_length != strlen( $key ) )
			return false;

		//lookup key
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s", 'wp_' . self::$meta_key, $key ) ) )
			return true;

		return false;
	}
	
	/**
	 * Ajax Callback to change filelock on lock override
	 *
	 * @param bool $send_notice whether or not to send an e-mail to the former lock owner
	 * @since 0.5
	 */
	
	function override_lock( $send_notice = true ) {
			
		//verify current user can edit
		//consider a specific permission check here
		if ( !$_POST['post_id'] || !current_user_can( 'edit_post' , $_POST['post_id'] ) || !current_user_can( 'override_document_lock' ) )
			wp_die( __( 'Not authorized', 'wp_document_revisions') );
			
		//verify that there is a lock
		if ( !( $current_owner = wp_check_post_lock($_POST['post_id'] ) ) ) 
			die( '-1' );
		
		//update the lock
		wp_set_post_lock( $_POST['post_id'] );
		
		//get the current user ID
		$current_user = wp_get_current_user();
		
		if ( $send_notice )
			$this->send_override_notice( $_POST['post_id'], $current_owner, $current_user->ID );
			
		do_action( 'document_lock_override', $_POST['post_id'], $current_user->ID, $current_owner );
		
		die( '1' );
	
	}
	
	/**
	 * E-mails current lock owner to notify them that they lost their file lock
	 *
	 * @param int $post_id id of document lock being overridden
	 * @param int $owner_id id of current document owner
	 * @param int $current_user_id id of user overriding lock
	 * @returns bool true on sucess, false on fail
	 * @since 0.5
	 */
	function send_override_notice( $post_id, $owner_id , $current_user_id ) {
			
		//get lock owner's details
		$lock_owner = get_userdata( $owner_id );
		
		//get the current user's detaisl
		$current_user = wp_get_current_user( $current_user_id );
		
		//get the post
		$post = get_post( $post_id );

		//build the subject
		$subject = sprintf( __( '%1$s: %2$s has overridden your lock on %3$2', 'wp_document_revisions' ), get_bloginfo( 'name' ), $current_user->display-name, $post->post_title );
		$subject = apply_filters( 'lock_override_notice_subject', $subject );
		
		//build the message
		$message = sprintf( __('Dear %s:', 'wp_document_revisions' ), $lock_owner->display-name) . "\n\n";
		$message .= sprintf( __('%1$s (%2$s), has overridden your lock on the document %3$s (%4$s).', 'wp_document_revisions' ), $current_user->display-name,  $current_user->user_email, $post->post_title,get_permalink( $post->ID ) ) . "\n\n";
		$message .= __('Any changes you have made will be lost.', 'wp_document_revisions' ) . "\n\n";
		$message .= sprintf( __('- The %s Team', 'wp_document_revisions' ), get_bloginfo( 'name' ) );
		$message = apply_filters( 'lock_override_notice_message', $message );
		
		apply_filters( 'document_lock_override_email', $message, $post_id, $current_user_id, $lock_owner );
		
		//send mail
		return wp_mail( $lock_owner->user_email, $subject , $message );
	
	}
		
	/**
	 * Adds doc-specific caps to all roles so that 3rd party plugins can manage them
	 * Gives admins all caps
	 
	 * @since 1.0
	 */
	function add_caps() {
	
		$wp_roles = new WP_Roles();
		
		//default role => capability mapping; based off of _post options
		//can be overridden by 3d party plugins
		$defaults = array( 
				'administrator' => 
					array( 
					    'edit_documents' => true,
					    'edit_others_documents' => true, 
					    'edit_private_documents' => true, 						
					    'edit_published_documents' => true, 
					    'read_documents' => true, 
						'read_document_revisions' => true,
					    'read_private_documents' => true, 
					    'delete_documents' => true, 
					    'delete_others_documents' => true, 
					    'delete_private_documents' => true, 
					    'delete_published_documents' => true, 
					    'publish_documents' => true, 
					    'override_document_lock' => true, 
					),
				'editor' => 
					array( 
					    'edit_documents' => true,
					    'edit_others_documents' => true, 
					    'edit_private_documents' => true, 						
					    'edit_published_documents' => true, 
					    'read_documents' => true, 
						'read_document_revisions' => true,
					    'read_private_documents' => true, 
					    'delete_documents' => true, 
					    'delete_others_documents' => true, 
					    'delete_private_documents' => true, 
					    'delete_published_documents' => true, 
					    'publish_documents' => true, 
					    'override_document_lock' => true, 
					),
				'author' =>
					array( 
					    'edit_documenst' => true,
					    'edit_others_documents' => false, 
					    'edit_private_documents' => false, 						
					    'edit_published_documents' => true, 
					    'read_documents' => true, 
						'read_document_revisions' => true,
					    'read_private_documents' => false, 
					    'delete_documents' => true, 
					    'delete_others_documents' => false, 
					    'delete_private_documents' => false, 
					    'delete_published_documents' => true, 
					    'publish_documents' => true, 
					    'override_document_lock' => false, 
					),
				'contributor' =>
					array( 
					    'edit_documents' => true,
					    'edit_others_documents' => false, 
					    'edit_private_documents' => false, 						
					    'edit_published_documents' => false, 
					    'read_documents' => true, 
						'read_document_revisions' => true,
					    'read_private_documents' => false, 
					    'delete_documents' => true, 
					    'delete_others_documents' => false, 
					    'delete_private_documents' => false, 
					    'delete_published_documents' => false, 
					    'publish_documents' => false, 
					    'override_document_lock' => false, 
					),
				'subscriber' =>
					array( 
					    'edit_documents' => false,
					    'edit_others_documents' => false, 
					    'edit_private_documents' => false, 						
					    'edit_published_documents' => false, 
					    'read_documents' => true, 
						'read_document_revisions' => true,
					    'read_private_documents' => false, 
					    'delete_documents' => false, 
					    'delete_others_documents' => false, 
					    'delete_private_documents' => false, 
					    'delete_published_documents' => false, 
					    'publish_documents' => false, 
					    'override_document_lock' => false, 
					),
				);

		foreach (  $wp_roles->role_names as $role=>$label ) { 
			
			//if the role is a standard role, map the default caps, otherwise, map as a subscriber
			$caps = ( array_key_exists( $role, $defaults ) ) ? $defaults[$role] : $defaults['subscriber'];
			
			$caps = apply_filters( 'document_caps', $caps, $role );
					
			//loop and assign
			foreach ( $caps as $cap=>$grant )				
				$wp_roles->add_cap( $role, $cap, $grant );
		
		}
	
	}

	/**
	 * Remove before final
	 */
	function debug( $var, $die = true ) {
	
		if ( !current_user_can( 'manage_options' ) || !WP_DEBUG )
			return;
			
		echo "<PRE>\n";
		print_r( $var );
		echo "\n</PRE>\n";
		
		if ( $die )
			die();
	
	}
	
}

new Document_Revisions;



?>
