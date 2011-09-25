<?php
if ( !class_exists( 'WPDR_Recently_Revised_Documents' ) ) {

	/**
 	 * Widget of recently revised files
 	 */
	class WPDR_Recently_Revised_Documents extends WP_Widget {
	
		function __construct() {
			parent::WP_Widget( 'WPDR_Recently_Revised_Documents', $name = 'Recently Revised Documents' );
		}
		
		function widget( $args, $instance ) {
		
			//todo: add option to show/hide private and draft documents
			
			global $wpdr;
			if ( !$wpdr )
				$wpdr = &Document_Revisions::$instance;
			
			extract( $args );
	 		
	 		echo $before_widget; 
	 		
			echo $before_title . 'Recently Revised Documents' . $after_title;	
			
			$query = array( 
					'post_type' => 'document',
					'orderby' => 'modified',
					'order' => 'DESC',
					'numberposts' => '5',
					'post_status' => array( 'private', 'publish', 'draft' ),
			);
			
			$documents = get_posts( $query );
	
			echo "<ul>\n";
			foreach ( $documents as $document ) {
			 
				//use our function to get post data to correct WP's author bug
				$revision = $wpdr->get_latest_revision( $document->ID );
	
			?>
				<li><a href="<?php echo get_edit_post_link( $revision->ID ); ?>"><?php echo $revision->post_title; ?></a><br />
				<?php echo human_time_diff( strtotime( $revision->post_modified_gmt ) ); ?> ago by <?php echo  get_the_author_meta( 'display_name', $revision->post_author ); ?>
				</li>
			<?php }
			
			echo "</ul>\n";
			
			echo $after_widget;
			
		}
	
	}

add_action( 'widgets_init', create_function( '', 'register_widget("WPDR_Recently_Revised_Documents");' ) );

}