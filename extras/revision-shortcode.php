<?php
if ( !function_exists( 'wpdr_shotcode' ) ) {
	/**
	 * Callback to display revisions
	 * 
	 * Usage: (in a post or page) [revisions="{DocumentID}"] where {DocumentID} is the ID of a document
	 *
	 * @param $atts array attributes passed via short code
	 * @returns string a UL with the revisions
	 */
	function wpdr_shotcode( $atts ) {
	
		//extract args
		extract( shortcode_atts( array(
			'id' => null,
		), $atts ) );
		
		global $wpdr;
		if ( !$wpdr )
			$wpdr = &Document_Revisions::$instance;		
		
		$revisions = $wpdr->get_revisions( $id );
		
		if ( !$revisions )
			return false;
		
		//buffer output to return rather than echo directly
		ob_start();
		?>
		<ul class="revisions">
		<?php 
		//loop through each revision
		foreach ( $revisions as $revision ) { ?>
			<li>
				<a href="<?php echo get_permalink( $revision->ID ); ?>" title="<?php echo $revision->post_date; ?>" class="timestamp" id="<?php echo strtotime( $revision->post_date ); ?>">
				<?php echo human_time_diff( strtotime( $revision->post_date ), current_time('timestamp') ); ?>
				</a> by <?php echo get_the_author_meta( 'display_name', $revision->post_author ); ?>
			</li>
		<?php } ?>
		</ul>
		<?php
		//grab buffer contents and clear
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	add_shortcode( 'revisions', 'wpdr_shortcode' );

}