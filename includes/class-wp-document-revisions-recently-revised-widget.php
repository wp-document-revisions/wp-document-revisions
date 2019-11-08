<?php
/**
 * Helper class for WP_Document_Revisions that registers the recently revised widget.
 *
 * @since 3.2.2
 * @package WP_Document_Revisions
 */

/**
 * Recently revised documents widget.
 */
class WP_Document_Revisions_Recently_Revised_Widget extends WP_Widget {

	/**
	 * Default widget settings
	 *
	 * @var $defaults
	 */
	private $defaults = array(
		'numberposts' => 5,
		'post_status' => array(
			'publish' => true,
			'private' => false,
			'draft'   => false,
		),
		'show_author' => true,
	);

	/**
	 * Init widget and register.
	 */
	public function __construct() {
		parent::__construct( 'WP_Document_Revisions_Recently_Revised_Widget', __( 'Recently Revised Documents', 'wp-document-revisions' ) );

		// can't i18n outside of a function.
		$this->defaults['title'] = __( 'Recently Revised Documents', 'wp-document-revisions' );
	}


	/**
	 * Callback to display widget contents.
	 *
	 * @param Array  $args the widget arguments.
	 * @param Object $instance the WP Document Revisions instance.
	 */
	public function widget( $args, $instance ) {

		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}

		// enabled statuses are stored as status => bool, but we want an array of only activated statuses.
		$statuses = array_filter( (array) $instance['post_status'] );
		$statuses = array_keys( $statuses );

		$query = array(
			'orderby'     => 'modified',
			'order'       => 'DESC',
			'numberposts' => (int) $instance['numberposts'],
			'post_status' => $statuses,
		);

		$documents = $wpdr->get_documents( $query );

		// no documents, don't bother.
		if ( ! $documents ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'] . $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'] . '<ul>';

		foreach ( $documents as $document ) :
			$link = ( current_user_can( 'edit_document', $document->ID ) ) ? add_query_arg(
				array(
					'post'   => $document->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			) : get_permalink( $document->ID );
			// translators: %1$s is the time ago in words, %2$s is the author.
			$format_string = ( $instance['show_author'] ) ? __( '%1$s ago by %2$s', 'wp-document-revisions' ) : __( '%1$s ago', 'wp-document-revisions' );
			?>
			<li>
				<a href="<?php echo esc_attr( $link ); ?>"><?php echo esc_html( get_the_title( $document->ID ) ); ?></a><br />
				<?php printf( esc_html( $format_string ), esc_html( human_time_diff( strtotime( $document->post_modified_gmt ) ) ), esc_html( get_the_author_meta( 'display_name', $document->post_author ) ) ); ?>
			</li>
			<?php
		endforeach;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</ul>' . $args['after_widget'];
	}


	/**
	 * Callback to display widget options form.
	 *
	 * @param Object $instance the WP Document Revisions instance.
	 */
	public function form( $instance ) {

		foreach ( $this->defaults as $key => $value ) {
			if ( ! isset( $instance[ $key ] ) ) {
				$instance[ $key ] = $value;
			}
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wp-document-revisions' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'numberposts' ) ); ?>"><?php esc_html_e( 'Number of Posts:', 'wp-document-revisions' ); ?></label><br />
			<input class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'numberposts' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'numberposts' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['numberposts'] ); ?>" />
		</p>
		<p>
			<?php esc_html_e( 'Posts to Show:', 'wp-document-revisions' ); ?><br />
			<?php foreach ( $instance['post_status'] as $status => $value ) : ?>
				<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'post_status_' . $status ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_status_' . $status ) ); ?>" type="text" <?php checked( $value ); ?> />
				<label for="<?php echo esc_attr( $this->get_field_name( 'post_status_' . $status ) ); ?>"><?php echo esc_html( ucwords( $status ) ); ?></label><br />
			<?php endforeach; ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_author' ) ); ?>"><?php esc_html_e( 'Display Document Author:', 'wp-document-revisions' ); ?></label><br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_author' ) ); ?>" <?php checked( $instance['show_author'] ); ?> /> <?php esc_html_e( 'Yes', 'wp-document-revisions' ); ?>
		</p>
		<?php
	}


	/**
	 * Sanitizes options and saves.
	 *
	 * @param Object $new_instance the new instance.
	 * @param Object $old_instance the old instance.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                = $old_instance;
		$instance['title']       = wp_strip_all_tags( $new_instance['title'] );
		$instance['numberposts'] = (int) $new_instance['numberposts'];
		$instance['show_author'] = (bool) $new_instance['show_author'];

		// merge post statuses into an array.
		foreach ( $this->defaults['post_status'] as $status => $value ) {
			$instance['post_status'][ $status ] = (bool) isset( $new_instance[ 'post_status_' . $status ] );
		}

		return $instance;
	}


}

/**
 * Callback to register the recently revised widget.
 */
function drrrw_widgets_init() {
	register_widget( 'WP_Document_Revisions_Recently_Revised_Widget' );
}


add_action( 'widgets_init', 'drrrw_widgets_init' );
