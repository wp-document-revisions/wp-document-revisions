<?php
/*
Plugin Name: WP Document Revisions
Plugin URI: http://ben.balter.com/2011/08/29/wp-document-revisions-document-management-version-control-wordpress/
Description: A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.
Version: 1.2.3
Author: Benjamin J. Balter
Author URI: http://ben.balter.com
License: GPL3
*/

/*  WP Document Revisions
 *
 *  A document management and version control plugin for WordPress that allows
 *  teams of any size to collaboratively edit files and manage their workflow.
 *
 *  Copyright (C) 2011-2012  Benjamin J. Balter  ( ben@balter.com -- http://ben.balter.com )
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright 2011-2012
 *  @license GPL v3
 *  @version 1.2.3
 *  @package WP_Document_Revisions
 *  @author Benjamin J. Balter <ben@balter.com>
 */

class Document_Revisions {
	static $instance;
	static $key_length = 32;
	static $meta_key   = 'document_revisions_feed_key';

	/**
	 * Initiates an instance of the class and adds hooks
	 * @since 0.5
	 */
	function __construct() {

		self::$instance = &$this;

		//admin
		add_action( 'auth_redirect', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'i18n' ), 5 );

		//CPT/CT
		add_action( 'init', array( &$this, 'register_cpt' ) );
		add_action( 'init', array( &$this, 'register_ct' ) );
		add_action( 'admin_init', array( &$this, 'initialize_workflow_states' ) );
		register_activation_hook( __FILE__, array( &$this, 'add_caps' ) );
		add_filter( 'the_content', array( &$this, 'content_filter' ), 1 );
		add_action( 'wp_loaded', array( &$this, 'register_term_count_cb' ), 100, 1 );

		//rewrites and permalinks
		add_filter( 'rewrite_rules_array' , array( &$this, 'revision_rewrite' ) );
		add_filter( 'init', array( &$this, 'inject_rules' ) );
		add_action( 'post_type_link', array(&$this, 'permalink'), 10, 4 );
		add_action( 'post_link', array(&$this, 'permalink'), 10, 4 );
		add_filter( 'single_template', array(&$this, 'serve_file'), 10, 1 );
		add_action( 'parse_request', array( &$this, 'ie_cache_fix' ) );
		add_filter( 'query_vars', array(&$this, 'add_query_var'), 10, 4 );
		register_activation_hook( __FILE__, 'flush_rewrite_rules' );
		add_filter( 'default_feed', array( &$this, 'hijack_feed' ), 10, 2);
		add_action( 'do_feed_revision_log', array( &$this, 'do_feed_revision_log' ) );
		add_action( 'template_redirect', array( $this, 'revision_feed_auth' ) );
		add_filter( 'get_sample_permalink_html', array(&$this, 'sample_permalink_html_filter'), 10, 4);
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );
		add_filter( 'image_downsize', array( &$this, 'image_downsize'), 10, 3 );
		add_filter( 'document_path', array( &$this, 'wamp_document_path_filter' ), 9, 1 );
		add_filter( 'redirect_canonical', array( &$this, 'redirect_canonical_filter' ), 10, 2 );

		//RSS
		add_filter( 'private_title_format', array( &$this, 'no_title_prepend' ), 20, 1 );
		add_filter( 'protected_title_format', array( &$this, 'no_title_prepend' ), 20, 1 );
		add_filter( 'the_title', array( &$this, 'add_revision_num_to_title' ), 20, 1 );

		//uploads
		add_filter( 'upload_dir', array( &$this, 'document_upload_dir_filter' ), 10, 2);
		add_filter( 'attachment_link', array( &$this, 'attachment_link_filter'), 10, 2);
		add_filter( 'wp_handle_upload_prefilter', array(&$this, 'filename_rewrite' ) );
		add_filter( 'wp_handle_upload', array( &$this, 'rewrite_file_url' ), 10, 2);

		//locking
		add_action( 'wp_ajax_override_lock', array( &$this, 'override_lock' ) );

		//cache
		add_action( 'save_post', array( &$this, 'clear_cache' ), 10, 1 );

		//edit flow
		add_action( 'ef_loaded', array( &$this, 'edit_flow_support' ) );

		//load front-end features (shortcode, widgets, etc.)
		include dirname( __FILE__ ) . '/includes/front-end.php';
		new Document_Revisions_Front_End( $this );

	}


	/**
	 * Init i18n files
	 * Must be done early on init because they need to be in place when register_cpt is called
	 */
	function i18n() {
		load_plugin_textdomain( 'wp-document-revisions', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Extends class with admin functions when in admin backend
	 * @since 0.5
	 */
	function admin_init() {
		include dirname( __FILE__ ) . '/includes/admin.php';
		$this->admin = new Document_Revisions_Admin( self::$instance );
	}


	/**
	 * Registers the document custom post type
	 * @since 0.5
	 */
	function register_cpt() {

		$labels = array(
			'name'               => _x( 'Documents', 'post type general name', 'wp-document-revisions' ),
			'singular_name'      => _x( 'Document', 'post type singular name', 'wp-document-revisions' ),
			'add_new'            => _x( 'Add Document', 'document', 'wp-document-revisions' ),
			'add_new_item'       => __( 'Add New Document', 'wp-document-revisions' ),
			'edit_item'          => __( 'Edit Document', 'wp-document-revisions' ),
			'new_item'           => __( 'New Document', 'wp-document-revisions' ),
			'view_item'          => __( 'View Document', 'wp-document-revisions' ),
			'search_items'       => __( 'Search Documents', 'wp-document-revisions' ),
			'not_found'          => __( 'No documents found', 'wp-document-revisions' ),
			'not_found_in_trash' => __( 'No documents found in Trash', 'wp-document-revisions' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Documents', 'wp-document-revisions' ),
			'all_items'          => __( 'All Documents', 'wp-document-revisions' ),
		);

		$args = array(
			'labels'               => $labels,
			'publicly_queryable'   => true,
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'query_var'            => true,
			'rewrite'              => true,
			'capability_type'      => array( 'document', 'documents'),
			'map_meta_cap'         => true,
			'has_archive'          => true,
			'hierarchical'         => false,
			'menu_position'        => null,
			'register_meta_box_cb' => array( &$this->admin, 'meta_cb' ),
			'supports'             => array( 'title', 'author', 'revisions', 'excerpt', 'custom-fields' ),
			'menu_icon'            => plugins_url( '/menu-icon.png', __FILE__ ),
		);

		register_post_type( 'document', apply_filters( 'document_revisions_cpt', $args ) );

	}


	/**
	 * Registers custom status taxonomy
	 * @since 0.5
	 */
	function register_ct() {

		$labels = array(
			'name'              => _x( 'Workflow States', 'taxonomy general name', 'wp-document-revisions' ),
			'singular_name'     => _x( 'Workflow State', 'taxonomy singular name', 'wp-document-revisions'),
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

		register_taxonomy( 'workflow_state', array('document'), apply_filters( 'document_revisions_ct', array(
					'hierarchical'          => false,
					'labels'                => $labels,
					'show_ui'               => true,
					'rewrite'               => false,
					'update_count_callback' => array( &$this, 'term_count_cb' ),
				) ) );

	}


	/**
	 * Propagates initial workflow states on plugin activation
	 * @since 0.5
	 * @return unknown
	 */
	function initialize_workflow_states() {

		$terms = get_terms( 'workflow_state', array( 'hide_empty' => false ) );

		if ( !empty( $terms ) )
			return false;

		$states = array(
			__('In Progress', 'wp-document-revisions')   => __('Document is in the process of being written', 'wp-document-revisions'),
			__('Initial Draft', 'wp-document-revisions') => __('Document is being edited and refined', 'wp-document-revisions'),
			__('Under Review', 'wp-document-revisions')  => __('Document is pending final review', 'wp-document-revisions'),
			__('Final', 'wp-document-revisions')         => __('Document is in its final form', 'wp-document-revisions'),
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
	 * @since 0.5
	 * @param object $post (optional) post object
	 * @return object all attached uploads
	 */
	function get_attachments( $post = '' ) {

		if ($post == '')
			global $post;

		//verify that it's an object
		if ( !is_object( $post ) )
			$post = get_post( $post );

		//check for revisions
		if ( $parent = wp_is_post_revision( $post ) )
			$post = get_post( $parent );

		//check for attachments
		if ( $post->post_type == 'attachment' )
			$post = get_post( $post->post_parent );

		if ( !isset( $post->ID ) )
			return array();

		$args = array(
			'post_parent' => $post->ID,
			'post_status' => 'inherit',
			'post_type'   => 'attachment',
			'order'       => 'DESC',
			'orderby'     => 'post_date',
		);

		$args = apply_filters( 'document_revision_query', $args );

		return get_children( $args );

	}


	/**
	 * Checks if document is locked, if so, returns the lock holder's name
	 * @since 0.5
	 * @param object|int $post the post object or postID
	 * @return bool|string false if no lock, user's display name if locked
	 */
	function get_document_lock( $post ) {

		if ( !is_object( $post ) )
			$post = get_post( $post );

		if ( !$post )
			return false;

		//get the post lock
		if ( !( $user = wp_check_post_lock( $post->ID ) ) )
			$user = false;

		//allow others to shortcircuit
		$user = apply_filters( 'document_lock_check', $user, $post );

		if ( !$user )
			return false;

		//get displayname from userID
		$last_user = get_userdata( $user );
		return ( $last_user ) ? $last_user->display_name : __( 'Somebody' );

	}


	/**
	 * Given a file, returns the file's extension
	 * @since 0.5
	 * @param string $file URL, path, or filename to file
	 * @return string extension
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
	 * @since 0.5
	 * @param object|int $post document or attachment
	 * @return string the extension to the latest revision
	 */
	function get_file_type( $post = '' ) {

		if ( $post == '' )
			global $post;

		if ( !is_object( $post ) )
			$post = get_post( $post );

		if ( get_post_type( $post ) == 'attachment' )
			$file = get_attached_file( $post->ID );
		else
			$file = $this->get_latest_revision_url( $post->ID );

		return $this->get_extension( $file );

	}


	/**
	 * Adds document CPT rewrite rules
	 * @since 0.5
	 */
	function inject_rules() {

		global $wp_rewrite;
		$wp_rewrite->add_rewrite_tag( "%document%", '([^.]+)\.[A-Za-z0-9]{3,4}?', 'document=' );

	}


	/**
	 * Adds document rewrite rules to the rewrite array
	 * @since 0.5
	 * @param $rules array rewrite rules
	 * @return array rewrite rules
	 */
	function revision_rewrite( $rules ) {

		$my_rules = array();

		//revisions in the form of yyyy/mm/[slug]-revision-##.[extension], yyyy/mm/[slug]-revision-##.[extension]/, yyyy/mm/[slug]-revision-##/ and yyyy/mm/[slug]-revision-##
		$my_rules['documents/([0-9]{4})/([0-9]{1,2})/([^.]+)-' . __( 'revision', 'wp-document-revisions' ) . '-([0-9]+)\.[A-Za-z0-9]{3,4}/?$'] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]&revision=$matches[4]';

		//revision feeds in the form of yyyy/mm/[slug]-revision-##.[extension]/feed/, yyyy/mm/[slug]-revision-##/feed/, etc.
		$my_rules['documents/([0-9]{4})/([0-9]{1,2})/([^.]+)(\.[A-Za-z0-9]{3,4})?/feed/?$'] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]&feed=feed';

		//documents in the form of yyyy/mm/[slug]-revision-##.[extension], yyyy/mm/[slug]-revision-##.[extension]/
		$my_rules['documents/([0-9]{4})/([0-9]{1,2})/([^.]+)\.[A-Za-z0-9]{3,4}/?$'] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]';

		// site.com/documents/ should list all documents that user has access to (private, public)
		$my_rules['documents/?$'] = 'index.php?post_type=document';
		$my_rules['documents/page/?([0-9]{1,})/?$'] = 'index.php?post_type=document&paged=$matches[1]';

		$my_rules = apply_filters( 'document_rewrite_rules', $my_rules, $rules );

		return $my_rules + $rules;
	}


	/**
	 * Tell's WP to recognize document query vars
	 * @since 0.5
	 * @param array $vars the query vars
	 * @return array the modified query vars
	 */
	function add_query_var( $vars ) {
		$vars[] = "revision";
		$vars[] = "document";
		return $vars;
	}


	/**
	 * Builds document post type permalink
	 * @since 0.5
	 * @param string $link original permalink
	 * @param object $post post object
	 * @param unknown $leavename
	 * @param unknown $sample (optional)
	 * @return string the real permalink
	 */
	function permalink( $link, $post, $leavename, $sample = '' ) {

		global $wp_rewrite;
		$revision_num = false;

		//if this isn't our post type, kick
		if ( !$this->verify_post_type( $post ) )
			return $link;

		//check if it's a revision
		if ( $post->post_type == 'revision' ) {
			$parent = clone get_post( $post->post_parent );
			$revision_num = $this->get_revision_number( $post->ID );
			$parent->post_name = $parent->post_name . __('-revision-', 'wp-document-revisions') . $revision_num;
			$post = $parent;
		}

		//if no permastruct
		if ( $wp_rewrite->permalink_structure == '' ) {
			$link = site_url( '?post_type=document&p=' . $post->ID );
			if ( $revision_num ) $link = add_query_arg( 'revision', $revision_num, $link );
			return apply_filters( 'document_permalink', $link, $post );
		}

		// build documents/yyyy/mm/slug
		$extension = $this->get_file_type( $post );

		$timestamp = strtotime($post->post_date);
		$link = home_url() . '/documents/' . date('Y', $timestamp) . '/' . date('m', $timestamp) . '/';
		$link .= ( $leavename ) ? '%document%' : $post->post_name;
		$link .= $extension ;

		$link = apply_filters( 'document_permalink', $link, $post );

		return $link;
	}


	/**
	 * Filters permalink displayed on edit screen in the event that there is no attachment yet uploaded
	 * @rerurns string modified HTML
	 * @since 0.5
	 * @param string $html original HTML
	 * @param int $id Post ID
	 * @return unknown
	 */
	function sample_permalink_html_filter( $html, $id ) {

		$post = get_post( $id );

		//verify post type
		if ( !$this->verify_post_type( $post ) )
			return $html;

		//grab attachments
		$attachments = $this->get_attachments( $post );

		//if no attachments, return nothing
		if ( empty( $attachments ) )
			return '';

		//otherwise return html unfiltered
		return $html;
	}


	/**
	 * Retrieves all revisions for a given post (including the current post)
	 * Workaround for #16215 to ensure revision author is accurate
	 * http://core.trac.wordpress.org/ticket/16215
	 * @since 1.0
	 * @param int $postID the post ID
	 * @return array array of post objects
	 */
	function get_revisions( $postID ) {

		// Revision authors are actually shifted by one
		// This moves each revision author up one, and then uses the post_author as the initial revision

		//get the actual post
		$post = get_post( $postID );

		if ( !$post )
			return false;

		if ( $cache = wp_cache_get( $postID, 'document_revisions' ) )
			return $cache;

		//correct the modified date
		$post->post_date = date( 'Y-m-d H:i:s', (int) get_post_modified_time( 'U', null, $postID ) );

		//grab the post author
		$post_author = $post->post_author;

		//fix for Quotes in the most recent post because it comes from get_post
		$post->post_excerpt = html_entity_decode( $post->post_excerpt );

		//get revisions, and prepend the post
		$revs = wp_get_post_revisions( $postID, array( 'order' => 'DESC' ) );
		array_unshift( $revs, $post );

		//loop through revisions
		foreach ( $revs as $ID => &$rev ) {

			//if this is anything other than the first revision, shift author 1
			if ( $ID < sizeof( $revs ) - 1)
				$rev->post_author = $revs[$ID+1]->post_author;

			//if last revision, get the post author
			else
				$rev->post_author = $post_author;

		}

		wp_cache_set( $postID, $revs, 'document_revisions' );

		return $revs;

	}


	/**
	 * Returns a modified WP Query object of a document and its revisions
	 * Corrects the authors bug
	 * @since 1.0.4
	 * @param int $postID the ID of the document
	 * @param bool $feed (optional) whether this is a feed
	 * @return obj|bool the WP_Query object, false on failure
	 */
	function get_revision_query( $postID, $feed = false ) {

		$posts = $this->get_revisions( $postID );

		if ( !$posts )
			return false;

		$rev_query = new WP_Query();
		$rev_query->posts = $posts;
		$rev_query->post_count = sizeof( $posts );
		$rev_query->is_feed = $feed;

		return $rev_query;

	}


	/**
	 * For a given post, builds a 1-indexed array of revision post ID's
	 * @since 0.5
	 * @param int $post_id the parent post id
	 * @return array array of revisions
	 */
	function get_revision_indices( $post_id ) {

		if ( $cache = wp_cache_get( $post_id, 'document_revision_indices' ) )
			return $cache;

		$revs = wp_get_post_revisions( $post_id, array( 'order' => 'ASC' ) );

		$i = 1;
		foreach ( $revs as $rev )
			$output[ $i++ ] = $rev->ID;

		wp_cache_set( $post_id, $output, 'document_revision_indices' );

		return $output;

	}


	/**
	 * Given a revision id (post->ID) returns the revisions spot in the sequence
	 * @since 0.5
	 * @param unknown $revision_id
	 * @return int revision #
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
	 * @since 0.5
	 * @param int $revision_num the 1-indexed revision #
	 * @param int $post_id the ID of the parent post
	 * @return int the ID of the revision
	 */
	function get_revision_id( $revision_num, $post_id ) {

		$index = $this->get_revision_indices( $post_id );

		return ( isset( $index[ $revision_num ] ) ) ? $index[ $revision_num ] : false;

	}


	/**
	 * Serves document files
	 * @since 0.5
	 * @param unknown $template
	 * @return unknown
	 */
	function serve_file( $template ) {
		global $post;
		global $wp_query;
		global $wp;

		if ( !$this->verify_post_type( $post ) )
			return $template;

		//if this is a passworded document and no password is sent
		//use the normal template which should prompt for password
		if ( post_password_required( $post ) )
			return $template;

		//grab the post revision if any
		$version = get_query_var( 'revision' );

		//if there's not a post revision given, default to the latest
		if ( !$version )
			$rev_id = $this->get_latest_revision( $post->ID );
		else
			$rev_id = $this->get_revision_id ( $version, $post->ID );

		$rev_post = get_post ( $rev_id );
		$revision = get_post( $rev_post->post_content );

		$file = get_attached_file( $revision->ID );

		//flip slashes for WAMP settups to prevent 404ing on the next line
		$file = apply_filters( 'document_path', $file );

		//return 404 if the file is a dud or malformed
		if ( !is_file( $file ) ) {

			//note: this message will log to apache's php error log and/or to the screen/debug bar
			trigger_error( "Unable to read file '$file' while attempting to serve the document '$post->post_title'" );

			//this will send 404 and no cache headers
			//and tell wp_query that this is a 404 so that is_404() works as expected
			//and theme formats appropriatly
			$wp_query->posts = array();
			$wp_query->queried_object = null;
			$wp->handle_404();

			//tell WP to serve the theme's standard 404 template, this is a filter after all...
			return get_404_template();

		}

		if ( $post->post_status != 'publish' &&
			( !current_user_can( 'read_document', $post->ID ) ||
				( $version && !current_user_can( 'read_document_revisions' ) ) ) )
			wp_die( __( 'You are not authorized to access that file.', 'wp-document-revisions' ) );

		do_action( 'serve_document', $post->ID, $file );

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

		//we want the true attachment URL, not the permalink, so temporarily remove our filter
		remove_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ) );
		$filename .= $this->get_extension( wp_get_attachment_url( $revision->ID ) );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );

		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

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

		if ( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
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
	 * Depricated for consistency of terms
	 * @param unknown $id
	 * @return unknown
	 */
	function get_latest_version( $id ) {
		_deprecated_function( __FUNCTION__, '1.0.3 of WP Document Revisions', 'get_latest_version' );
		return $this->get_latest_revision( $id );
	}


	/**
	 * Given a post ID, returns the latest revision attachment
	 * @param int $id Post ID
	 * @return object latest revision object
	 */
	function get_latest_revision( $id ) {

		$revisions = $this->get_revisions( $id );

		if ( !$revisions )
			return false;

		//verify that there's an upload ID in the content field
		//if there's no upload ID for some reason, default to latest attached upload
		if ( !is_numeric( $revisions[0]->post_content ) ) {

			$attachments = $this->get_attachments( $id );

			if ( empty( $attachments ) )
				return false;

			$latest_attachment = reset( $attachments );
			$revisions[0]->post_content = $latest_attachment->ID;

		}

		return $revisions[0];

	}


	/**
	 * Deprecated for consistency sake
	 * @param unknown $id
	 * @return unknown
	 */
	function get_latest_version_url( $id ) {
		_deprecated_function( __FUNCTION__, '1.0.3 of WP Document Revisions', 'get_latest_revision_url' );
		return $this->get_latest_revision_url( $id );
	}


	/**
	 * Returns the URL to a post's latest revision
	 * @since 0.5
	 * @param int $id post ID
	 * @return string|bool URL to revision or false if no attachment
	 */
	function get_latest_revision_url( $id ) {

		$latest = $this->get_latest_revision( $id );

		if ( !$latest )
			return false;

		//temporarily remove our filter to get the true URL, not the permalink
		remove_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ) );
		$url = wp_get_attachment_url( $latest->post_content );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );

		return $url;
	}


	/**
	 * Calculated path to upload documents
	 * @since 0.5
	 * @return string path to document
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
	 * @since 0.5
	 * @param array $dir defaults passed from WP
	 * @return array $dir modified directory
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
	 * @since 0.5
	 * @param string $link URL to file's tru location
	 * @param int $id attachment ID
	 * @return string empty string
	 */
	function attachment_link_filter( $link, $id ) {

		if ( !$this->verify_post_type( $id ) )
			return $link;

		return '';
	}


	/**
	 * Rewrites uploaded revisions filename with secure hash to mask true location
	 * @since 0.5
	 * @param array $file file data from WP
	 * @return array $file file with new filename
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
	 * @since 0.5
	 * @param array $file file object from WP
	 * @return array modified file array
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
	 * note: We can't use the screen API because A) used on front end, and B) admin_init is too early (enqueue scripts)
	 * @param object|int either a post object or a postID
	 * @since 0.5
	 * @param unknown $post (optional)
	 * @return bool true if document, false if not
	 */
	function verify_post_type( $post = false ) {

		//check for post_type query arg (post new)
		if ( $post == false && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'document' )
			return true;

		//if post isn't set, try get vars (edit post)
		if ( $post == false )
			$post = ( isset( $_GET['post'] ) ) ? $_GET['post'] : false;

		//look for post_id via post or get (media upload)
		if ( $post == false )
			$post = ( isset( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : false;


		$post_type = get_post_type( $post );

		//if post is really an attachment or revision, look to the post's parent
		if ( $post_type == 'attachment' || $post_type == 'revision' )
			$post_type = get_post_type( get_post( $post )->post_parent );

		return $post_type == 'document';

	}


	/**
	 * Clears cache on post_save
	 * @param int $postID the post ID
	 */
	function clear_cache( $postID ) {
		wp_cache_delete( $postID, 'document_post_type' );
		wp_cache_delete( $postID, 'document_revision_indices' );
		wp_cache_delete( $postID, 'document_revisions' );
	}


	/**
	 * Callback to handle revision RSS feed
	 * @since 0.5
	 */
	function do_feed_revision_log() {

		//because we're in function scope, pass $post as a global
		global $post;

		//remove this filter to A) prevent trimming and B) to prevent WP from using the attachID if there's no revision log
		remove_filter( 'get_the_excerpt', 'wp_trim_excerpt'  );
		remove_filter( 'get_the_excerpt', 'twentyeleven_custom_excerpt_more' );

		//include feed and die
		include dirname( __FILE__ ) . '/includes/revision-feed.php';
		exit();

	}


	/**
	 * Intercepts RSS feed redirect and forces our custom feed
	 * @since 0.5
	 * @param string $default the original feed
	 * @return string the slug for our feed
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
	 * @since 0.5
	 * @return bool
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
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s", $wpdb->prefix . self::$meta_key, $key ) ) )
			return true;

		return false;
	}


	/**
	 * Ajax Callback to change filelock on lock override
	 *
	 * @since 0.5
	 * @param bool $send_notice (optional) whether or not to send an e-mail to the former lock owner
	 */

	function override_lock( $send_notice = true ) {

		//verify current user can edit
		//consider a specific permission check here
		if ( !$_POST['post_id'] || !current_user_can( 'edit_post' , $_POST['post_id'] ) || !current_user_can( 'override_document_lock' ) )
			wp_die( __( 'Not authorized', 'wp-document-revisions') );

		//verify that there is a lock
		if ( !( $current_owner = wp_check_post_lock($_POST['post_id'] ) ) )
			die( '-1' );

		//update the lock
		wp_set_post_lock( $_POST['post_id'] );

		//get the current user ID
		$current_user = wp_get_current_user();

		if ( apply_filters( 'send_document_override_notice', $send_notice ) )
			$this->send_override_notice( $_POST['post_id'], $current_owner, $current_user->ID );

		do_action( 'document_lock_override', $_POST['post_id'], $current_user->ID, $current_owner );

		die( '1' );

	}


	/**
	 * E-mails current lock owner to notify them that they lost their file lock
	 *
	 * @since 0.5
	 * @param int $post_id id of document lock being overridden
	 * @param int $owner_id id of current document owner
	 * @param int $current_user_id id of user overriding lock
	 * @return bool true on sucess, false on fail
	 */
	function send_override_notice( $post_id, $owner_id , $current_user_id ) {

		//get lock owner's details
		$lock_owner = get_userdata( $owner_id );

		//get the current user's detaisl
		$current_user = wp_get_current_user( $current_user_id );

		//get the post
		$post = get_post( $post_id );

		//build the subject
		$subject = sprintf( __( '%1$s: %2$s has overridden your lock on %3$s', 'wp-document-revisions' ), get_bloginfo( 'name' ), $current_user->display_name, $post->post_title );
		$subject = apply_filters( 'lock_override_notice_subject', $subject );

		//build the message
		$message = sprintf( __('Dear %s:', 'wp-document-revisions' ), $lock_owner->display_name) . "\n\n";
		$message .= sprintf( __('%1$s (%2$s), has overridden your lock on the document %3$s (%4$s).', 'wp-document-revisions' ), $current_user->display_name,  $current_user->user_email, $post->post_title, get_permalink( $post->ID ) ) . "\n\n";
		$message .= __('Any changes you have made will be lost.', 'wp-document-revisions' ) . "\n\n";
		$message .= sprintf( __('- The %s Team', 'wp-document-revisions' ), get_bloginfo( 'name' ) );
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

		global $wp_roles;
		if ( ! isset( $wp_roles ) )
			$wp_roles = new WP_Roles;

		//default role => capability mapping; based off of _post options
		//can be overridden by 3d party plugins
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
			'editor' =>
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
			'author' =>
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
			'contributor' =>
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
			'subscriber' =>
			array(
				'edit_documents'             => false,
				'edit_others_documents'      => false,
				'edit_private_documents'     => false,
				'edit_published_documents'   => false,
				'read_documents'             => true,
				'read_document_revisions'    => true,
				'read_private_documents'     => false,
				'delete_documents'           => false,
				'delete_others_documents'    => false,
				'delete_private_documents'   => false,
				'delete_published_documents' => false,
				'publish_documents'          => false,
				'override_document_lock'     => false,
			),
		);

		foreach (  $wp_roles->role_names as $role=>$label ) {

			//if the role is a standard role, map the default caps, otherwise, map as a subscriber
			$caps = ( array_key_exists( $role, $defaults ) ) ? $defaults[$role] : $defaults['subscriber'];

			$caps = apply_filters( 'document_caps', $caps, $role );

			//loop and assign
			foreach ( $caps as $cap=>$grant ) {

				//check to see if the user already has this capability, if so, don't re-add as that would override grant
				if ( !isset( $wp_roles->roles[$role]['capabilities'][$cap] ) )
					$wp_roles->add_cap( $role, $cap, $grant );
			}
		}

	}


	/**
	 * Removes Private or Protected from document titles in RSS feeds
	 * @since 1.0
	 * @param string $prepend the sprintf formatted string to prepend to the title
	 * @return string just the string
	 */
	function no_title_prepend( $prepend ) {

		if ( !$this->verify_post_type() )
			return $prepend;

		return '%s';

	}


	/**
	 * Adds revision number to document tiles
	 * @since 1.0
	 * @param string $title the title
	 * @return string the title possibly with the revision number
	 */
	function add_revision_num_to_title( $title ) {
		global $post;

		//verify post type
		if ( !$this->verify_post_type() )
			return $title;

		//if this is a document, and not a revision, just filter and return the title
		if ( $post->post_type != 'revision' ) {

			if ( is_feed() )
				$title = sprintf( __( '%s - Latest Revision', 'wp-document-revisions'), $title );

			return apply_filters( 'document_title',  $title );

		}

		//get revision num
		$revision_num = $this->get_revision_number( $post->ID );

		//if for some reason there's no revision num
		if ( !$revision_num )
			return apply_filters( 'document_title', $title );

		//add title, apply filters, and return
		return apply_filters( 'document_title', sprintf( __('%s - Revision %d', 'wp-document-revisions' ), $title, $revision_num ) );
	}


	/**
	 * Prevents Attachment ID from being displayed on front end
	 * @since 1.0.3
	 * @param string $content the post content
	 * @return string either the original content or none
	 */
	function content_filter( $content ) {

		if ( !$this->verify_post_type( ) )
			return $content;

		//allow password prompt to display
		if ( post_password_required() )
			return $content;

		return '';

	}


	/**
	 * Provides support for edit flow and disables the default workflow state taxonomy
	 * @return unknown
	 */
	function edit_flow_support() {

		if ( !class_exists( 'edit_flow' ) || !apply_filters( 'document_revisions_use_edit_flow', true ) )
			return false;

		//post caps
		add_post_type_support( 'document',  array(
				'ef_custom_statuses',
				'ef_editorial_comments',
				'ef_notifications',
				'ef_editorial_metadata',
				'ef_calendar',
			)
		);

		//remove workflow state CT
		remove_action( 'admin_init', array( &$this, 'initialize_workflow_states' ) );
		remove_action( 'init', array( &$this, 'register_ct' ) );

	}


	/**
	 * Returns array of document objects matching supplied criteria.
	 *
	 * See http://codex.wordpress.org/Class_Reference/WP_Query#Parameters for more information on potential parameters
	 * @param array $args (optional) an array of WP_Query arguments
	 * @param unknown $return_attachments (optional)
	 * @return array an array of post objects
	 */
	function get_documents( $args = array(), $return_attachments = false ) {

		$args = (array) $args;
		$args['post_type'] = 'document';
		$documents = get_posts( $args );
		$output = array();

		if ( $return_attachments ) {

			//loop through each document and build an array of attachment objects
			//this would be the same output as a query for post_type = attachment
			//but allows querying of document metadata and returns only latest revision
			foreach ( $documents as $document ) {
				$docObj = $this->get_latest_revision( $document->ID );
				$output[] = get_post( $docObj->post_content );
			}

		} else {

			//used internal get_revision function so that filter work and revision bug is offset
			foreach ( $documents as $document )
				$output[] = $this->get_latest_revision( $document->ID );

		}

		//remove empty rows, e.g., created by autodraft, etc.
		$output = array_filter( $output );

		return $output;

	}


	/**
	 * Filter's calls for attachment URLs for files attached to documents
	 * Returns the document or revision URL instead of the file's true location
	 * Prevents direct access to files and ensures authentication
	 * @since 1.2
	 * @param string $url the original URL
	 * @param int $postID the attachment ID
	 * @return string the modified URL
	 */
	function attachment_url_filter( $url, $postID ) {

		//not an attached attachment
		if ( !$this->verify_post_type( $postID ) )
			return $url;

		$post = get_post( $postID );

		//user can't read revisions anyways, so just give them the URL of the latest revision
		if ( !current_user_can( 'read_document_revisions' ) )
			return get_permalink( $post->post_parent );

		//we know there's a revision out there that has the document as its parent and the attachment ID as its body, find it
		global $wpdb;
		$revisionID = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_content = %d LIMIT 1", $post->post_parent, $postID ) );

		//couldn't find it, just return the true URL
		if ( !$revisionID )
			return $url;

		//run through standard permalink filters and return
		return get_permalink( $revisionID );

	}


	/**
	 * Prevents internal calls to files from breaking when apache is running on windows systems (Xampp, etc.)
	 * Code inspired by includes/class.wp.filesystem.php
	 * See generally http://wordpress.org/support/topic/plugin-wp-document-revisions-404-error-and-permalinks-are-set-correctly
	 * @since 1.2.1
	 * @param string $url the permalink
	 * @return string the modified permalink
	 */
	function wamp_document_path_filter( $url ) {
		$url = preg_replace('|^([a-z]{1}):|i', '', $url); //Strip out windows drive letter if it's there.
		return str_replace('\\', '/', $url); //Windows path sanitization
	}


	/**
	 * Term Count Callback that applies custom filter
	 * Allows Workflow State counts to include non-published posts
	 * @since 1.2.1
	 * @param unknown $terms
	 * @param unknown $taxonomy
	 */
	function term_count_cb( $terms, $taxonomy ) {
		add_filter( 'query', array( &$this, 'term_count_query_filter' ) );
		_update_post_term_count( $terms, $taxonomy );
		remove_filter( 'query', array( &$this, 'term_count_query_filter' ) );
	}


	/**
	 * Alters term count query to include all non-trashed posts.
	 * See generally, #17548
	 * @since 1.2.1
	 * @param unknown $query
	 * @return unknown
	 */
	function term_count_query_filter( $query ) {
		return str_replace( "post_status = 'publish'", "post_status != 'trash'", $query );
	}


	/**
	 * Extends the modified term_count_cb to all custom taxonomies associated with documents
	 * Unless taxonomy already has a custom callback
	 * @since 1.2.1
	 */
	function register_term_count_cb() {

		$taxs = get_taxonomies( array( 'object_type' => 'document', 'update_count_callback' => '' ), 'objects' );

		foreach ( $taxs as $tax )
			$tax->update_count_callback = array( &$this, 'term_count_cb' );

	}


	/**
	 * Removes auto-appended trailing slash from document requests prior to serving
	 * WordPress SEO rules properly dictate that all post requests should be 301 redirected with a trailing slash
	 * Because documents end with a phaux file extension, we don't want that
	 * Removes trailing slash from documents, while allowing all other SEO goodies to continue working
	 * @param unknown $redirect
	 * @param unknown $request
	 * @return unknown
	 */
	function redirect_canonical_filter( $redirect, $request ) {

		if ( !$this->verify_post_type() )
			return $redirect;

		return untrailingslashit( $redirect );

	}


	/**
	 * Provides a workaround for the attachment url filter breaking wp_get_attachment_image_src
	 * Removes the wp_get_attachment_url filter and runs image_downsize normally
	 * Will also check to make sure the returned image doesn't leak the file's true path
	 * @since 1.2.2
	 * @param string size the size requested
	 * @param bool $false will always be false
	 * @param int $id the ID of the attachment
	 * @param unknown $size
	 * @return array the image array returned from image_downsize()
	 */
	function image_downsize( $false, $id, $size ) {

		if ( !$this->verify_post_type( $id ) )
			return false;

		remove_filter( 'image_downsize', array( &$this, 'image_downsize') );
		remove_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ) );

		$direct = wp_get_attachment_url( $id );
		$image = image_downsize( $id, $size );

		add_filter( 'image_downsize', array( &$this, 'image_downsize'), 10, 3 );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );

		//if WordPress is going to return the direct url to the real file,
		//serve the document permalink (or revision permalink) instead
		if ( $image[0] == $direct )
			$image[0] = wp_get_attachment_url( $id );

		return $image;

	}

	/**
	 * Remove nocache headers from document downloads on IE < 8
	 * Hooked into parse_request so we can fire after request is parsed, but before headers are sent
	 * See http://support.microsoft.com/kb/323308
	 */	
	function ie_cache_fix( $wp )  {

		//SSL check
		if ( !is_ssl() )
			return $wp;
	
		//IE check
		if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) === false )
			return $wp;
		
		//verify that they are requesting a document
		if ( !isset( $wp->query_vars['post_type'] ) || $wp->query_vars['post_type'] != 'document' )
			return $wp;
		
		add_filter( 'nocache_headers', '__return_empty_array' );
	
		return $wp;
	
	}


}


// $wpdr is a global reference to the class
$wpdr = new Document_Revisions;

//declare global functions
include_once dirname( __FILE__ ) . '/includes/template-functions.php';