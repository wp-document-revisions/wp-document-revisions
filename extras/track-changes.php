<?php
/**
 * Description: Auto-generates and appends revision summaries for changes to taxonomies, title, and visibility 
 */

class wpdr_track_meta_changes {

	public $document_change_list = array();
	public $wpdr;
	
	function __construct() {
		
		//set up class	
		add_action( 'plugins_loaded', array( &$this, 'setup_wpdr' ) );
		
		//taxs
		add_action( 'set_object_terms', array( &$this, 'build_taxonomy_change_list' ), 10, 6 );
		
		//status
		add_action( 'transition_post_status', array( &$this, 'track_status_changes' ), 10, 3);

		//title
		add_action( 'save_post', array( &$this, 'track_title_changes' ), 10, 1 );

		//appending
		add_action( 'save_post', array( &$this, 'append_changes_to_revision_summary' ), 20, 1 );
	
	}
	/**
	 * Makes all WPDR functions accessible as $this->wpdr->{function}	
	 * Call here so that Doc Revs is loaded
	 */
	function setup_wpdr() {
		global $wpdr;
		if ( !$wpdr )
			$wpdr = &Document_Revisions::$instance;
		$this->wpdr = &$wpdr;
	}
	
	/**
	 * Compares post title to previous revisions post title and adds to internal array if changed
	 * @param int $postID the id of the post to check
	 */
	function track_title_changes( $postID ) {
		
		if ( $this->dont_track( $postID ) )
			return false;
		
		$new = get_post( $postID );
		$revisions = $this->wpdr->get_revisions( $postID );
		
		//because we've already saved, [0] = this one, [1] = the previous one
		$old = $revisions[1];
		
		if ( $new->post_title == $old->post_title )
			return;
		
		do_action( 'document_title_changed', $postID, $old, $new );
		
		$this->document_change_list[] = sprintf( __( 'Title changed from "%1$s" to "%2$s"', 'wp_document_revisions'), $old->post_title, $new->post_title );
		
	}
	
	/**
	 * Tracks when a post status changes
	 * @param string $new the new status
	 * @param string $old the old status
	 * @param object $post the post object
	 */
	function track_status_changes( $new, $old, $post ) {
		
		if ( $this->dont_track( $post->ID ) )
			return false;	
	
		if ( $old == 'new' || $old == 'auto_draft' )
			return false;
			
		if ( $new == $old )
			return false;
		
		do_action( 'document_visibility_changed', $post->ID, $old, $new );
		
		$this->document_change_list[] = sprintf( __( 'Visibility changed from "%1$s" to "%2$s"', 'wp_document_revisions'), $old, $new );
		
	}

	/**
	 * Tracks changes to taxonomies
	 * @param int $object_id the document ID
	 * @param array $terms the new terms
	 * @param array $tt_ids the new term IDs
	 * @param string $taxonomy the taxonomy being changed
	 * @param bool $append whether it is being appended or replaced
	 * @param array $old_tt_ids term taxonomy ID array before the change
	 */
	function build_taxonomy_change_list( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
		
		if ( $this->dont_track( $object_id ) )
			return false;
		
		//no changes were made
		if ( $append == false && !$this->taxonomy_changed( $tt_ids, $old_tt_ids ) )
			return false;
		
		$removed = false;
		
		//something was removed
		if ( sizeof( $old_tt_ids ) > sizeof( $tt_ids ) ) {
			$removed = true;
			$tt_ids = array_diff( $old_tt_ids, $tt_ids );
		}
				
		$taxonomy = get_taxonomy( $taxonomy );
		$terms_formatted = array();
		
		//format terms into comma separated list of terms with quotes
		foreach ( $tt_ids as $term ) {
			$term_obj = get_term_by('id', $term, $taxonomy->name);
			$terms_formatted[] = '"' . $term_obj->name . '"';
		}

		//human format the string by adding an "and" before the last term
    	$last = array_pop ( $terms_formatted ); 
    	if ( !count( $terms_formatted) ) 
        	$terms_formatted = $last;
		else 
	    	$terms_formatted = implode (', ', $terms_formatted ) . ' and ' . $last; 

		//grab the proper taxonomy label
		$taxonomy_formatted = ( sizeof( $tt_ids )  == 1) ? $taxonomy->labels->singular_name : $taxonomy->labels->name;
		
		if ( $append ) {
			$message = sprintf( __( '%1$s %2$s added', 'wp_document_revisions' ), $taxonomy_formatted, $terms_formatted );
		} else if ( $removed ) {
			$message = sprintf( __( '%1$s %2$s removed', 'wp_document_revisions' ), $taxonomy_formatted, $terms_formatted );
		} else {
			$message = sprintf( __( '%1$s changed to %2$s', 'wp_document_revisions' ), $taxonomy_formatted, $terms_formatted );
		}
		
		do_action( 'document_taxonomy_changed', $object_id, $taxonomy->name, $tt_ids, $old_tt_ids );
		
		$this->document_change_list[] = $message;		
		
	}
	
	/**
	 * Loops through document change list and appends to latest revisions's log message
	 * @param int $postID the ID of the document being changed
	 */
	function append_changes_to_revision_summary( $postID ) {
		global $wpdb;
		
		if ( $this->dont_track( $postID ) )
			return false;
			
		if ( empty( $this->document_change_list ) )
			return false;
			
		$post = get_post( $postID );
		$message = trim( $post->post_excerpt );
		
		if ( !empty( $message ) && substr( $message, -1, 1 ) != ' ' ) 	
			$message .= ' ';
		
		//escape HTML and implode list on semi-colons
		$this->document_change_list = esc_html( stripslashes( implode( '; ', $this->document_change_list ) ) );
		$message .= '(' . $this->document_change_list . ')';
		$message = apply_filters( 'document_revision_log_auto_append_message', $message, $postID );
		
		//manually update the DB here so that we don't create another revision
		$wpdb->update( $wpdb->posts, array( 'post_excerpt' => $message ), array( 'ID' => $postID ), '%s', '%d' );
		
		do_action( 'document_meta_change', $postID, $message );
		
		//reset in case another post is also being saved for some reason
		$this->document_change_list = array();
		
	}
	
	/**
	 * Helper function to compare to taxonomy arrays and determine if they are the same regardless of order
	 * @param array $a 1st array
	 * @param array $b 2nd array
	 * @returns bool true if changed, false if unchanged
	 * from: php.net
	 */
	function taxonomy_changed($a, $b) {
    	return !(is_array($a) && is_array($b) && array_diff($a, $b) === array_diff($b, $a) );
	}
	
	/** 
	 * Determines whether changes should be tracked for a given post
	 * @param int $postID the ID of the post
	 * @returns bool true if shouldn't track, otherwise false
	 */
	function dont_track( $postID ) {
	
		if ( !apply_filters( 'track_document_meta_changes', true ) )
			return true;
			
		if ( wp_is_post_revision( $postID ) )
			return true;
		
		if ( !$this->wpdr->verify_post_type( $postID ) ) 
			return true;

		$revisions = $this->wpdr->get_revisions( $postID );
		if ( sizeof( $revisions ) <= 1 )
			return true;
			
		return false;
	}

}

new wpdr_track_meta_changes;