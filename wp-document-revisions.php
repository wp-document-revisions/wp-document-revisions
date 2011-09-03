<?php
/*
 * Plugin Name: WP Document Revisions
 * Plugin URI:
 * Description: Document Managment System (DMS) for WordPress
 * Version: 0.1
 * Author: Benjamin J. Balter
 * Author URI: http://ben.balter.com/
 */

class document_revisions {
	
	private $caps = array('edit','read','delete','edit_others','read_private','publish','unlock');

	/**
	 * Add hooks & filters
	 * @since 0.1
	 */
	function document_revisions() {
		register_activation_hook(__FILE__, array( &$this, 'flush_rules' ) );
		add_action( 'init', array( &$this, 'register_cpt' ) );
		add_action( 'init', array( &$this, 'register_taxonomies' ) );
		add_action( 'init', array( &$this, 'map_caps' ) );
		add_filter( 'init', array( &$this, 'inject_rules' ) );
		add_action( 'admin_menu', array( &$this, 'add_options_menu' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_init', array( &$this, 'disable_richeditor' ), 1 );
		add_filter( 'get_sample_permalink_html', array(&$this, 'sample_permalink_filter'), 10, 4);
		add_action( 'post_type_link', array(&$this,'permalink'), 10, 4 );
		add_filter( 'single_template', array(&$this, 'serve_file') );
		add_filter( 'wp_handle_upload_prefilter', array(&$this, 'filename_rewrite' ) );
		add_filter( 'media_upload_form_url', array(&$this, 'post_upload_handler' ) );
		add_filter( 'post_updated_messages', array(&$this, 'update_messages') );
		add_action( 'contextual_help',  array(&$this, 'add_help_text'), 10, 3 );
		add_filter( '_wp_post_revision_field_post_content', array(&$this, 'revision_filter'), 100, 1 );
	 	add_filter( 'query_vars', array(&$this, 'add_query_var'), 10, 4 );
	 	add_action( 'admin_head', array( &$this, 'make_private' ) );
	 	add_filter( 'media_meta', array( &$this, 'media_meta_hack'), 10, 1  );
	 	do_action( 'document_revisions_init');
	}
	
	/**
	 * Returns plugin options
	 * @since 0.1
	 */
	function get_options() {
		return apply_filters( 'document_revision_options', get_option('document_revisions') );
	}

	/**
	 * Let WP know about or custom post type
	 * @since 0.1
	 */
	function register_cpt()  {
	
	  $labels = array(
	    'name' => _x('Documents', 'post type general name'),
	    'singular_name' => _x('Document', 'post type singular name'),
	    'add_new' => _x('Add Document', 'book'),
	    'add_new_item' => __('Add New Document'),
	    'edit_item' => __('Edit Document'),
	    'new_item' => __('New Document'),
	    'view_item' => __('View Document'),
	    'search_items' => __('Search Documents'),
	    'not_found' =>  __('No documents found'),
	    'not_found_in_trash' => __('No documents found in Trash'), 
	    'parent_item_colon' => '',
	    'menu_name' => 'Documents'
	
	  );
	  
	  $args = array(
	    'labels' => $labels,
	    'publicly_queryable' => true,
	    'show_ui' => true, 
	    'show_in_menu' => true, 
	    'query_var' => true,
	    'rewrite' => false,
	    'capability_type' => 'post',
	    'map_meta_cap' => true,
	    'has_archive' => false, 
	    'hierarchical' => false,
	    'menu_position' => null,
	    'register_meta_box_cb' => array(&$this, 'meta_cb'),
	    'supports' => array('title','author','custom-fields','revisions', 'editor','excerpt')
	  ); 
	  
	  $args = apply_filters( 'document_revisions_cpt', $args );
	  register_post_type('document',$args);
	
	}
	
	function register_taxonomies() {
		$options = $this->get_options();
		
		$cts = array('radio', 'checkbox', 'tags');
		
		foreach ($options['fields'] as $field) {
		
			if (!in_array($field['type'], $cts) )
				continue;
		
			 $labels = array(
 			   'name' => $field['name'],
 			   'singular_name' => $field['name_singular'],
 			   'search_items' =>  'Search ' . $field['name'],
 			   'all_items' => 'All ' . $field['name'],
 			   'parent_item' => 'Parent ' . $field['name_singular'],
 			   'parent_item_colon' => 'Parent ' . $field['name_singule'] . ':',
 			   'edit_item' => 'Edit ' . $field['name_singular'],
 			   'update_item' => 'Update ' . $field['name_singular'],
 			   'add_new_item' => 'Add New ' . $field['name_singular'],
 			   'new_item_name' => 'New ' . $field['name_singular'] . ' Name',
 			   'menu_name' => $field['name'],
 			 ); 	
 			
  			register_taxonomy('document_' . $this->get_field_slug($field['name']),'document', array(
  			  'hierarchical' => ($field['type'] == 'checkbox') ? true : false,
  			  'labels' => $labels,
  			  'show_ui' => true,
  			  'query_var' => true,
  			  'rewrite' => false,
  			));
		}
	}
	
	/**
	 * CPT update message strings
	 * @since 0.1
	 */
	function update_messages( $messages ) {
	 	global $post, $post_ID;
	
	  	$messages['document'] = array(
	  		  0 => '', // Unused. Messages start at index 1.
	  		  1 => sprintf( __('Document updated. <a href="%s">Download document</a>'), esc_url( get_permalink($post_ID) ) ),
	  		  2 => __('Custom field updated.'),
	  		  3 => __('Custom field deleted.'),
	  		  4 => __('Document updated.'),
	  		  /* translators: %s: date and time of the revision */
	  		  5 => isset($_GET['revision']) ? sprintf( __('Document restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	  		  6 => sprintf( __('Document published. <a href="%s">Download document</a>'), esc_url( get_permalink($post_ID) ) ),
	  		  7 => __('Document saved.'),
	  		  8 => sprintf( __('Document submitted. <a target="_blank" href="%s">Download document</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	  		  9 => sprintf( __('Document scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview document</a>'),
	  		    // translators: Publish box date format, see http://php.net/date
	  		    date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
	  		  10 => sprintf( __('Document draft updated. <a target="_blank" href="%s">Download document</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	  		);
	
	  	return $messages;
	}
	
	/**
	 * CPT help text
	 * @todo help text
	 * @since 0.1
	 */
	function add_help_text($contextual_help, $screen_id, $screen) { 
		$contextual_help = __( '<div style="float: left; width: 50%">
								<p><strong>To add a document</strong></p>
								<ol>
								<li style="list-style-type:decimal">Enter a document title</li>
								<li style="list-style-type:decimal">Click <code>Upload New Version</code> and select your document</li>
								<li style="list-style-type:decimal">Optionally, add a revision message describing your change</li>
								<li style="list-style-type:decimal">Click <code>Update</code></li>
								</ol></div>
								<div style="float: left; width: 50%">
								<p><strong>To revise a document</strong></p>
								<ol>
								<li style="list-style-type:decimal">Click <code>Download</code> to download the latest version</li>
								<li style="list-style-type:decimal">Make changes to your document as you would normally. So long as you remain on the page, no other user can make changes</li>
								<li style="list-style-type:decimal">Click <code>Upload New Version</code> and select your revised document</li>
								<li style="list-style-type:decimal">Optionally, add a revision message describing your change</li>
								<li style="list-style-type:decimal">Click <code>Update</code></li>
								</ol></div>
								<div style="clear:both;">&nbsp;</div>
								
								
								<div style="float: left; width: 50%">
								<p><strong>To Download a Previous Version</strong></p>
								<p>Click the date of the revision you would like to download from the revision log below.</p>
								</div>

								<div style="float: left; width: 50%">
								<p><strong>To Restore a Previous Version</strong></p>
								<p>Find the version you would like to restore to and click the <code>restore</code> link.</p>
								</div>
							
								<div style="clear:both;">&nbsp;</div>

								');

	  	return $contextual_help;
	}
	
	
	/**
	 * Grabs privledges from plugin options and maps them to roles
	 * @since 0.1
	 */
	function map_caps() {	
		$options = $this->get_options();
		
		if ( !isset($options['roles'] ) )
			return;
		
		foreach ( $options['roles'] as $role_id => $role ) {
			$r =& get_role($role_id);
					
			foreach ($this->caps as $cap) {
				if ( isset( $role[$cap] ) && $role[$cap] ) 
					$r->add_cap($cap . '_documents');
					$r->add_cap($cap . '_document');
			}
		
		}
	
	}
	
	/**
	 * CPT callback to init metaboxes on document edit / add
	 * @since 0.1
	 */
	function meta_cb() {
		
		//add our meta boxes
		add_meta_box('revision-summary', 'Revision Summary', array(&$this, 'revision_summary_cb'), 'document', 'normal', 'default');
		add_meta_box('document', 'Document', array(&$this, 'document_metabox'), 'document', 'normal', 'high');
		add_meta_box('revision-log', 'Revision Log', array(&$this, 'revision_metabox'), 'document', 'normal', 'low' );
		
		//add custom metaboxes
		$options = $this->get_options();
		foreach ($options['fields'] as $slug=>$field) {
		print_r($field);
			switch($field['type']) {
				case 'text':
					add_meta_box('document_' . $slug, $field['name_singular'], array(&$this, 'text_meta_box'), 'document', $field['position'], $field['priority'], array('slug'=> $slug ) );
				break;
				case 'paragraph':
				
				break;
				
				case 'radio':
				
				break;
			}	
		}
		
		//kill unused betaboxes
		remove_meta_box('postexcerpt', 'document', 'normal' );
		remove_meta_box('postcustom', 'document', 'normal' );
		
		//move core metaboxes
		remove_meta_box('authordiv', 'document', 'normal' );
		add_meta_box('authordiv', 'Author', 'post_author_meta_box', 'document', 'side', 'low' );
		remove_meta_box('revisionsdiv', 'document', 'normal' );
		
		add_action('admin_head', array(&$this, 'admin_css') );
		
		global $post;
		add_action('admin_notices', array(&$this,'lock_notice') );
		
		do_action('document_edit_metabox_cb');
		
	}
	
	/**
	 * Forces default post status to private
	 * @since 0.1
	 */
	function make_private() {
		global $post;
		$post_pre = $post;
		
		if ( $post->post_status == 'draft' || $post->post_status == 'auto-draft')
			$post->post_status = 'private';
	
		//allow others to oveerride
		$post = apply_filters( 'document_private', $post, $post_pre);
	
	}
	
	/**
	 * Add custom css to admin head for edit screen
	 * @since 0.1
	 */
	function admin_css(){ 
		global $post; ?>
		<style>
			#postdiv {display:none;}
			#edit-slug-box {padding-bottom: 20px;}
			#lock-notice {background-color: #D4E8BA; border-color: #92D43B; }
			#document-revisions {width: 100%; text-align: left;}
			#document-revisions td {padding: 5px 0 0 0;}
			<?php if ( wp_check_post_lock( $post->ID ) ) { ?>
			#publish, #add_media, #lock-notice {display: none;}
			<?php } ?>
		</style>
	<?php }
	
	/**
	 * Callback to add our document upload / latest metabox
	 * @param object $post post object
	 * @since 0.1
	 */
	function document_metabox($post) {

		$latest_version = $this->get_latest_version( $post->ID ); 
			if ( $latest_version  ) {			
	?>
	<p><strong>Latest Version of the Document:</strong>
	<strong><a href="<?php echo get_permalink($post->ID); ?>">Download</a></strong><br />
		<em>Checked in <?php echo $this->relative_time( strtotime( $latest_version->post_date_gmt ) ); ?> by <?php echo get_author_name( $latest_version->post_author ); ?></em>
	</p>
	<?php } ?>
	<?php if ( wp_check_post_lock( $post->ID ) ) { 
	$lock = explode( ':', get_post_meta( $post->ID, '_edit_lock', true ) );
	$user = isset( $lock[1] ) ? $lock[1] : get_post_meta( $post->ID, '_edit_last', true );
	$last_user = get_userdata( $user );
	$last_user_name = $last_user ? $last_user->display_name : __('Somebody');	
	?>
	<p id="lock_override" style="text-align: right;"> <?php echo esc_html($last_user_name); ?> has prevented other users from making changes.<br />If you believe this is in error you can <a href="#" id="override_link">override the lock</a>, but their changes will be lost.</p>
	<?php } ?>
	<p style="text-align: right;"><a href='media-upload.php?post_id=<?php echo $post->ID; ?>&TB_iframe=1&document=1' id='add_media' class='thickbox' title='Upload Document' onclick='return false;' ><input type="button" value="Upload New Version" class='button'/></a></p>
	<script>
		jQuery(document).ready(function($) {	
			//var blockSave;
			
			//lock override toggle	
			$('#override_link').click(function() {
				$('#lock_override').hide();
				$('.error').not('#lock-notice').hide();
				$('#publish, #add_media, #lock-notice').fadeIn();	
			});
			
			//init autosave and watch for lock changes
			setTimeout( function(){
				if ( blockSave )
					return;
				autosave();
				if ($('#last-edit').html().indexOf('is currently editing this post.') != -1)
					alert( wp_document_revisions.lockOverrideNotice );
			}, 200);

		});
	</script>
	
	<?Php
	}
	
	/**
	 * Registers option menu
	 * @since 0.1
	 */
	function add_options_menu() {
		add_submenu_page( 'edit.php?post_type=document', 'Document Options', 'Options', 'manage_options', 'document_options', array( &$this, 'options_panel' ) );
	}
	
	/**
	 * Admin init hook, registers our settings
	 * @since 0.1
	 */
	function admin_init() {
		register_setting( 'document_revisions', 'document_revisions', array(&$this, 'options_validate' ) );
	}
	
	/**
	 * Callback for options panel 
	 * @since 0.1
	 * @todo CTs
	 */
	function options_panel() {
?>
<div class="wrap">
	<h2><?php _e('Document Options', 'document_revisions'); ?></h2>
	<form method="post" action='options.php' id="document_revisions_form">
<?php 

	//provide feedback
	settings_errors();
	
	//Tell WP that we are on the wp_resume_options page
	settings_fields( 'document_revisions' ); 
	
	//Pull the existing options from the DB
	$options = $this->get_options();

?>
<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Permissions', 'document_revisions'); ?></label></th>
			<td>
<?php 
		global $wp_roles;
		foreach ( $wp_roles->roles as $role_id => $role ) { ?>
		<div style="float: left; width: 200px; padding-bottom: 20px;">
		<strong><?php echo $role['name']; ?></strong>
			<ul>
		<?php
			foreach ($this->caps as $cap) { ?>
				<li>
					<input type="checkbox" name="document_revisions[roles][<?php echo $role_id; ?>][<?php echo $cap ?>]" id="<?php echo $role_id . '_' . $cap ?>" <?php if ( isset( $role['capabilities'][$cap . '_document'] ) ) checked( $role['capabilities'][$cap . '_document'] ); ?> value="1"> 
					<label for="<?php echo $role_id . '_' . $cap ?>"><?php echo str_replace('_', ' ', ucfirst($cap) ); ?> documents</label></li>
			<?php 
			} 
			?>
			</ul>
		</div>
		<?php
		}
?>
	</td>
	</tr>
	<tr valign="top">
		<th scrope="row">Fields and Taxonomies</th>
		<td>
		<?php
		$options['fields'][] = array('name'=>'', 'name_singular' => '', 'type'=>'');
		foreach ($options['fields'] as $id=>$field ) { ?>
			<?php echo ($field['type'] == '') ? '<strong>Add New</strong>' : ''; ?>
			<p><div style="width: 100px; float: left;">	<label for="tax-name">Name:</label></div><input type="text" name="document_revisions[fields][<?php echo $id; ?>][name]" id="tax-name" value="<?php echo $field['name']; ?>" /> <span class="description">e.g., Issue</span></p>
			<p><div style="width: 100px; float: left;"><label for="tax-name-singular">Singular Name:</label></div><input type="text" name="document_revisions[fields][<?php echo $id; ?>][name_singular]" id="tax-name-singular" value="<?php echo $field['name_singular']; ?>" /> <span class="description">e.g., Issues</span></p>
			<p><div style="width: 100px; float: left;"><label for="tax-type">Type:</label></div>
			<select id="tax-type" name="document_revisions[fields][<?php echo $id; ?>][type]">
			<?php echo ($field['type'] == '') ? '<option value=""></option>' : '<option value="remove">(Remove)</option>'; ?>" 
				<option value="radio" <?php selected($field['type'], 'radio'); ?>>Radio Buttons</option>
				<option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>>Checkbox</option>
				<option value="tags" <?php selected($field['type'], 'tags'); ?>>Tags</option>
				<option value="author" <?php selected($field['type'], 'author'); ?>>Author Dropdown</option>
				<option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
				<option value="paragraph" <?php selected($field['type'], 'paragraph'); ?>>Paragraph Text</option>
			</select></p>
			<p><div style="width: 100px; float: left;"><label for="position">Default Position:</div>
				<select name="document_revisions[fields][<?php echo $id; ?>][position]" id="position">
					<option <?php selected($field['position'], 'normal'); ?> value="normal">Normal</option>
					<option <?php selected($field['position'], 'side'); ?> value="side">Side</option>
					<option <?php selected($field['position'], 'advanced'); ?> value="advanced">Advanced</option>
				</select>

			</p>
			<p><div style="width: 100px; float: left;"><label for="priority">Priority:</div>
				<select name="document_revisions[fields][<?php echo $id; ?>][priority]" id="priority">
					<option <?php selected($field['priority'], 'high'); ?>>High</option>
					<option value="default" <?php selected($field['priority'], 'default'); ?>  <?php selected($field['priority'], ''); ?>>Normal</option>
					<option <?php selected($field['priority'], 'low'); ?>>Low</option>
				</select>
			</p>
			<p>&nbsp;</p>
			<?php } ?>		
		</td>
	</tr>
	</table>
	<p class="submit">
         <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	</form>
	<?php
	}
	
	/**
	 * Function to validate option page submission
	 * @param array $data post data
	 * @return array validated post data
	 * @since 0.1
	 */
	function options_validate($data) {
		
		//convert roles from 1/off checkboxes to true false
		if ( isset($data['roles'] ) ) {
			foreach ($data['roles'] as $role) {
				foreach ($role as &$cap) {
					if ($cap == '1')
						$cap = true;
					else 
						$cap = false;	
				}
			}
		}
		
		foreach ($data['fields'] as $id=>$field) {
		
			//rekey by field slug
			if ($field['type'] != '' && $field['type'] != 'remove') { 
				$data['fields'][ $this->get_field_slug( $field['name'] ) ] = $field;				
			}
			
			//kill original
			unset($data['fields'][$id]);
			
		}
		return $data;
	
	}
	
	/**
	 * Retrieves all attachments for a given post
	 * @param object $post post object
	 * @returns array array of attachment objects
	 * @since 0.1
	 */
	function get_attachments( $post = '') {
		
		if ($post == '')
			global $post;
		
		if ( !isset($post->ID) )
			return false;
		
		$args = array(	
				'post_parent' => $post->ID, 
				'post_status' => 'inherit', 
				'post_type' => 'attachment', 
				'order' => 'DESC', 
				'orderby' => 'post_date',
				);

		$args = apply_filters('document_revision_query', $args);
		
		return get_children( $args );
	
	}
	
	/**
	 * Relative time function for revisions
	 * @param int $timestamp unix timestamp
	 * @returns string relative timestamp
	 * @h/thttp://terriswallow.com/weblog/2008/relative-dates-in-wordpress-templates/
	 * @since 0.1
	 */
	function relative_time( $timestamp ) {
	
	        $difference = time() - $timestamp;

            if($difference >= 60*60*24*365){        // if more than a year ago
                $int = intval($difference / (60*60*24*365));
                $s = ($int > 1) ? 's' : '';
                $r = $int . ' year' . $s . ' ago';
            } elseif($difference >= 60*60*24*7*5){  // if more than five weeks ago
                $int = intval($difference / (60*60*24*30));
                $s = ($int > 1) ? 's' : '';
                $r = $int . ' month' . $s . ' ago';
            } elseif($difference >= 60*60*24*7){        // if more than a week ago
                $int = intval($difference / (60*60*24*7));
                $s = ($int > 1) ? 's' : '';
                $r = $int . ' week' . $s . ' ago';
            } elseif($difference >= 60*60*24){      // if more than a day ago
                $int = intval($difference / (60*60*24));
                $s = ($int > 1) ? 's' : '';
                $r = $int . ' day' . $s . ' ago';
            } elseif($difference >= 60*60){         // if more than an hour ago
                $int = intval($difference / (60*60));
                $s = ($int > 1) ? 's' : '';
                $r = $int . ' hour' . $s . ' ago';
            } elseif($difference >= 60){            // if more than a minute ago
                $int = intval($difference / (60));
                $s = ($int > 1) ? 's' : '';
                $r = $int . ' minute' . $s . ' ago';
            } else {                                // if less than a minute ago
                $r = 'moments ago';
            }

            return $r;
     }
     
     /**
      * Flush Rewrite rules to allow for our permastruct
      * @since 0.1
      */
     function flush_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
     
     /**
      * Gets a file's extension
      * @param string $file url or path to file
      * @returns string extension w/ leading .
      * @since 0.1
      */
     function get_extension( $file ) {
     	return substr( $file, strrpos( $file,'.' ) );
     }
     
     
     /**
      * Determines filetype of given post
      * @param object $post post object
      * @returns string extension of post
      * @since 0.1
      */
     function get_file_type( $post = '') {
     	if ($post == '')
     		global $post;
     		     
		return $this->get_extension( $this->get_latest_version_url( $post->ID ) );  
		
     }
     
     /**
      * Callback to display lock notice on top of edit page
      * @since 0.1
      */
     function lock_notice() { 
     	global $post;
     	
     	do_action('document_lock_notice', $post);
     	
     	//if there is no page var, this is a new document, no need to warn
     	if ( !isset( $_GET['post'] ) )
     		return; 
     		
     ?>
     	<div class="error" id="lock-notice"><p>You currently have this file checked out. No other user can edit this document so long as you remain on this page.</p></div>
     <?php }
     
     /**
      * Adds rewrite rules
      * @since 0.1
      */
     function inject_rules(){
	
		global $wp_rewrite;
		
    	$rw_structure = '/documents/%year%/%monthnum%/%document%';
 	    $wp_rewrite->add_rewrite_tag("%document%", '([^.]+)(\.[A-Za-z0-9]{3,4})?', 'document=');
    	$wp_rewrite->add_permastruct('document', $rw_structure, false);  
    	  	      	    
  	    $this->flush_rules();
		
	}
		
	function add_query_var( $vars ) {
		$vars[] = "revision";
		return $vars;
	}

	
	/**
	 * Permalink rewrite filter for custom permalinks
	 * @param string $link current link
	 * @param object $post post object
	 * @param bool $leavename whether to leave the post type placeholder
	 * @since 0.1
	 * @returns string pretty url
	 */
	function permalink( $link, $post, $leavename, $sample ) {

		//if this isn't our post type, kick
		if( $post->post_type != 'document')
		   return $link;
		
		//what are we replacing
		$rewritecode = array(
		  '#%year%#',
		  '#%monthnum%#',
		  '#/$#',
		);
		
		//get attachments
		$extension = $this->get_file_type( $post );
		$timestamp = strtotime($post->post_date);

		//assign to replacements
		$replace_array = array(
			date('Y',$timestamp),
			date('m',$timestamp),
			$extension,
		);
		
		//replace and return
		$link = preg_replace($rewritecode, $replace_array, $link);

		$link = apply_filters('document_permalink', $link, $post);
		
		return $link;
	}
	
	/**
	 * Intercepts template_redirect function and serves file to user
	 * @since 0.1
	 */
	function serve_file( $version = '' ) {
		global $post;
		
		$version = get_query_var('revision');
		
		if ( !$version )
			$revision = $this->get_latest_version( $post->ID );
		else 
			$revision = get_post ( $version );
			
		$headers[] = 'Content-Type: ' . $revision->post_mime_type;
		$headers[] = 'Content-Disposition: attachment; filename="' . $post->post_name . $this->get_extension( wp_get_attachment_url( $revision->ID ) ) . '"';
		$headers[] = "Content-Transfer-Encoding: binary";
		$headers[] = 'Pragma: no-cache';
		$headers[] = "Expires: 0";
		
		$headers = apply_filters('document_serve_header', $headers, $post );
		
		foreach ($headers as $header)
			header( $header );
				
		set_time_limit(0);
		
		$file = get_attached_file( $revision->ID ); 
		do_action('serve_document', $post, $file);
		readfile($file);
		
		exit();	
	}
	
	/**
	 * Modified excerpt CB with our help text and label
	 * @since 0.1
	 */
	function revision_summary_cb() { ?>
		<label class="screen-reader-text" for="excerpt">Revision Summary</label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"></textarea> 
		<p>Revision summaries are optional notes to store along with this revision that allow other users to quickly and easily see what changes you made without needing to open the actual file.</a></p> 
	<?php }
	
	/**
	 * Filters files on upload to md5 filenames
	 * Prevents unathorized user from guessing location in wp-uploads
	 * @param array $file file upload
	 * @returns array $file file upload with modified name 
	 * @since 0.1
	 */
	function filename_rewrite( $file ) {
	
		//verify post type, otherwise kick
		$post = get_post ($_POST['post_id'] );
		if ( $post->post_type != 'document' )
			return $file;
		
		//build new filename from filename + time md5'd + extension and return
		$file['name'] = md5( $file['name'] .  time() ) .$this->get_extension( $file['name'] );
		
		$file = apply_filters( 'document_internal_filename', $file );
		
		return $file;
		
	}
	
	/**
	 * Hook to follow file uploads to automate attaching the document to the post
	 * Technically using a filter as a hook, so don't tell anyone
	 * @param string $filter whatever we really should be filtering
	 * @returns string the same stuff they gave us, like we were never here
	 * @since 0.1
	 */
	function post_upload_handler( $filter ) {
	
		//if we're not posting this is the initial form load, kick
		if (!$_POST)
			return $filter;
		
		//verify post type
		$post = get_post( $_POST['post_id'] );
		if ($post->post_type != 'document')
			return;		
		
		//get the object that is our new attachment
		$attachments = $this->get_attachments( $post );
		$latest = reset($attachments);
		
		//get URL and extension to past to lock_notice
		$url = wp_get_attachment_url( $latest->ID );
		$extension = $this->get_extension( $url );
		
		do_action('document_upload', $latest, $_POST['post_id'] );
		
		$this->post_upload_js();
		
		//should probably give this back...
		return $filter; 

     }
     
     /**
      * Force rich editor into poor editor for simplicity since we're not really using it
      * @since 0.1
      */
     function disable_richeditor() {
     
     	//if this is a new document we can get the CPT form the URL
     	//if this is a return document, we get the CPT from the post object
     	if ( 
     		( isset( $_GET['post_type']) && $_GET['post_type'] == 'document' ) ||
     		( isset( $_GET['post'] ) && ( $post = get_post( $_GET['post'] ) ) && $post->post_type == 'document' ) ) 
   		 		add_filter('get_user_option_rich_editing', '__return_false');
 	}
     
    /**
     * Prevents permalink from being generated on exit screen without any attachments
     * @since 0.1
     * @param string $html original html WP generated
     * @param int $id ID post post
     * @returns string filtered HTML
     */
   	function sample_permalink_filter($html, $id ) {
		$post = get_post($id);
		
		if ($post->post_type != 'document')
			return $html;
			
		$attachments = $this->get_attachments( $post );
		if ( sizeof($attachments) == 0)
			return '';
		
		return $html;
   	}		
	
	/**
	 * Given a post ID, returns the object that is the latest revision's attached document
	 * @param int $id post ID
	 * @returns object post object of attachment
	 * @since 0.1
	 */
	function get_latest_version( $id ) {
		$post = get_post( $id );
		
		//verify post type
		if ( $post->post_type != 'document' )
			return false;
			
		//check the content field for a URL, if so, that's our current version
		if ( strpos( $post->post_content, get_bloginfo('url') ) === 0 ) {
			  $latestID = $this->get_attachment_id_from_url( $post->post_content );
				return get_post( $latestID );
		}
		
		$attachments = $this->get_attachments( $post );
		
		//if there are no attachments, kick
		if ( sizeof( $attachments ) == 0 )
			return false;	
	
		return reset( $attachments );
		
	}
	
	/**
	 * Given ID gets URL of latest revision's attachment
	 * @param int $id post id
	 * @returns string url to attachment
	 * @since 0.1
	 */
	function get_latest_version_url( $id ) {
		$latest = $this->get_latest_version( $id );
		if (!$latest)
			return false;
		return wp_get_attachment_url( $latest->ID );				

				
	}
	
	/**
	 * Reverse attachment lookup
	 * @param string $url URL to lookup
	 * @returns int ID of coresponding attachment, if any
	 * @since 0.1
	 */
	function get_attachment_id_from_url( $url ) {
	
		global $wpdb;
		
		//strip upload_dir base from URL
		$uploads = wp_upload_dir();
		$url = str_ireplace($uploads['baseurl'].'/', '', $url);
		
		//escape and query DB
		$postID = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = '%s'", $url ) );
		return $postID;
	
	}
	
	/**
	 * Modifies permalink in revision review to point to revision permalink
	 * @param string $url original url
	 * @returns string permalink
	 * @since 0.1
	 */
	function revision_permalink( $url ) {

		$attachID = $this->get_attachment_id_from_url( $url );
		$attach = get_post ($attachID);
		$parent_post = get_post( $attach->post_parent );
		
		//verify CPT
		if ($parent_post->post_type != 'document')
			return $url;
								
		//match extension to this file, not latest in case it has changed
		$extension = $this->get_extension( $url );	
		
		//build off of current permalink
		$latest_url = get_permalink( $parent_post->ID );	

		$url = substr($latest_url, 0, strrpos($latest_url, '.' ) );
		$url .= '-revision-' . $attach->ID . $extension;
		
		return '<a href="'.$url.'">'.$url.'</a>';
	
	}
	
	/**
	 * Filter hook for revision to parse URL into permalink
	 * @param string $content original content
	 * @returns string permalink
	 * @since 0.1
	 */
	function revision_filter($content) {

		$revision = get_post($_GET['revision']);
		
		$post = get_post($revision->post_parent);
		
		if ($post->post_type != 'document')
			return $content;
			
		
		$content = $this->revision_permalink( $content );
		
		return $content;	
	}
   	
   	function revision_metabox( $post ) {
   	
   		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		$revisions = wp_get_post_revisions( $post->ID );
		
		if (sizeof($revisions) > 0) { ?>
		
		<table id="document-revisions">
			<tr class="header">
				<th>Modified</th>
				<th>User</th>
				<th style="width:50%">Summary</th>
				<?php if ($can_edit_post) { ?><th>Actions</th><?php } ?>
			</tr>
		<?php
		
		foreach ( $revisions as $revision ) {
			if ( !current_user_can( 'read_post', $revision->ID ) || wp_is_post_autosave( $revision ) )
				continue;
		?>
		<tr>
			<td><a href="<?php echo $revision->post_content; ?>" title="<?php echo $revision->post_date; ?>"><?php echo $this->relative_time( strtotime( $revision->post_date_gmt ) ); ?></a></td>
			<td><?php echo get_the_author_meta( 'display_name', $revision->post_author ); ?></td>
			<td><?php echo $revision->post_excerpt; ?></td>
			<?php if ($can_edit_post) { ?><td><?php if ( $post->ID != $revision->ID )
				echo '<a href="' . wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'action' => 'restore' ), 'revision.php' ), "restore-post_$post->ID|$revision->ID" ) . '" class="revision">' . __( 'Restore' ) . '</a>'; ?></td><?php } ?>
		</tr>
		<?php		
		}
		?>
		</table>
		<script>
			jQuery(document).ready(function($) {
				$('.revision').click(function($){
					event.preventDefault();
					var url = jQuery(this).attr('href');
					if (confirm( '<?php _e('Are you sure you want to restore this revision?\n\nIf you do, no history will be lost. This revision will be copied and become the most recent revision.\n')?>' ) )
						window.location.href = url;
				});
			});
		</script>
		<?php
		
   		}
   	
   	}
   	
   	function post_upload_js() {
   		ob_start();
   	 ?>
   	<script>
		jQuery(document).ready(function($){	
		
			//Because we're in an iFrame, we need to traverse to parrent
			var win = window.dialogArguments || opener || parent || top;

			//stuff most recent version URL into hidden content field
			win.jQuery('#content').val("<?php echo $url; ?>");
			
			//kill any "document updated" messages to prevent confusion
			win.jQuery('#message').hide();
			
			//close TB and notify user of success
			win.tb_remove();
			win.jQuery('#post').before('<?php _e('<div id="message" class="updated" style="display:none"><p>File uploaded successfully. Add a revision summary below (optional) or press <em>Update</em> to save your changes</p></div>'); ?>').prev().fadeIn().fadeOut().fadeIn();
						
			//If they already have a permalink, update it with the current extension in case it changed
			//otherwise, tell WP that we're ready for it to generate a permalink for the first time
			if ( win.jQuery('#sample-permalink').length == 0 ) {
				win.autosave_update_slug('<?php echo $_POST['post_id']; ?>');
			} else {
				win.jQuery('#sample-permalink').html( win.jQuery('#sample-permalink').html().replace(/\<\/span>(\.[a-z0-9]{3,4})?$/i,'<?php echo $extension; ?>') );
			}
		});
	</script>
   	<?php 
  		$js = ob_get_contents();
   		ob_end_clean();
   		return $js;
   	}
   	
   	/**
   	 * Ugly, Ugly hack to sneak post-upload JS into the iframe
   	 * If there was a hook there, I wouldn't have to do this
   	 * @param string $meta dimensions / post meta
   	 * @returns string meta + js to process post
   	 * @since 0.1
   	 */
   	function media_meta_hack( $meta ) {
   		
   		global $post;
   		$parent = get_post ($post->post_parent);
 
  		if ( $parent->post_type != 'document' )
  			return $meta;
  			
  		$meta .= $this->post_upload_js( $post->ID );
  		
  		return $meta;
   	
   	}
   	
   	function get_field_slug( $field ) {
   		return strtolower( preg_replace('/[^A-Z0-9]+/i', '_', $field) );
   	}
   	
   	function text_meta_box( $post, $args ) { 
   		$options = $this->get_options();
   		$field = $options['fields'][$args['slug']];
   		?>
   		<label for="<?php echo $slug; ?>"><?php echo $field['name']; ?></label>
   		<input type="text" name="<?php echo $slug; ?>" id="<?php echo $slug; ?>" value="<?php get_post_meta($post->ID, 'document_' . $slug, true); ?>" />
   		<?php
   	}

   	
}

new document_revisions();

