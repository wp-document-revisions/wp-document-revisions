<?php
/**
 * Helper class for WP_Document_Revisions that registers shortcodes, widgets, etc. for use on the front-end
 *
 * @since 1.2
 * @package WP_Document_Revisions
 */

/**
 * WP Document Revisions Front End
 */
class WP_Document_Revisions_Front_End {

	/**
	 * The Parent WP_Document_Revisions instance
	 *
	 * @var $parent
	 */
	public static $parent;

	/**
	 * The Singleton instance
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Array of accepted shortcode keys and default values
	 *
	 * @var $shortcode_defaults
	 */
	public $shortcode_defaults = array(
		'id' => null,
		'number' => null,
	);

	/**
	 *  Registers front end hooks
	 *
	 * @param Object $instance The WP Document Revisions instance
	 */
	public function __construct( &$instance = null ) {

		self::$instance = &$this;

		// create or store parent instance
		if ( is_null( $instance ) ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = &$instance;
		}

		add_shortcode( 'document_revisions', array( &$this, 'revisions_shortcode' ) );
		add_shortcode( 'documents', array( &$this, 'documents_shortcode' ) );
		add_filter( 'document_shortcode_atts', array( &$this, 'shortcode_atts_hyphen_filter' ) );
	}


	/**
	 * Provides support to call functions of the parent class natively
	 *
	 * @since 1.2
	 * @param function $function the function to call
	 * @param array    $args the arguments to pass to the function
	 * @returns mixed the result of the function
	 */
	public function __call( $function, $args ) {
		return call_user_func_array( array( &self::$parent, $function ), $args );
	}


	/**
	 * Provides support to call properties of the parent class natively
	 *
	 * @since 1.2
	 * @param string $name the property to fetch
	 * @returns mixed the property's value
	 */
	public function __get( $name ) {
		return Document_Revisions::$$name;
	}


	/**
	 * Callback to display revisions
	 *
	 * @param array $atts attributes passed via short code
	 * @returns string a UL with the revisions
	 * @since 1.2
	 */
	public function revisions_shortcode( $atts ) {

		// normalize args
		$atts = shortcode_atts( $this->shortcode_defaults, $atts );
		foreach ( array_keys( $this->shortcode_defaults ) as $key ) {
			$$key = isset( $atts[ $key ] ) ? (int) $atts[ $key ] : null;
		}

		// do not show output to users that do not have the read_document_revisions capability
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return;
		}

		// get revisions
		$revisions = $this->get_revisions( $id );

		// show a limited number of revisions
		if ( null !== $number ) {
			$revisions = array_slice( $revisions, 0, (int) $number );
		}

		// buffer output to return rather than echo directly
		ob_start();
?>
		<ul class="revisions document-<?php echo esc_attr( $id ); ?>">
		<?php
		// loop through each revision
		// @codingStandardsIgnoreStart WordPress.XSS.EscapeOutput.OutputNotEscaped
		foreach ( $revisions as $revision ) { ?>
			<li class="revision revision-<?php echo esc_attr( $revision->ID ); ?>" >
				<?php printf( __( '<a href="%1$s" title="%2$s" id="%3$s" class="timestamp">%4$s</a> <span class="agoby">ago by</a> <span class="author">%5$s</a>', 'wp-document-revisions' ), esc_url( get_permalink( $revision->ID ) ), esc_attr( $revision->post_date ), esc_html( strtotime( $revision->post_date ) ), esc_html( human_time_diff( strtotime( $revision->post_date ) ), current_time( 'timestamp' ) ), esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ) ); ?>
			</li>
		<?php
		}
		// @codingStandardsIgnoreEnd WordPress.XSS.EscapeOutput.OutputNotEscaped
		?>
		</ul>
		<?php
		// grab buffer contents and clear
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}


	/**
	 * Shortcode to query for documents
	 * Takes most standard WP_Query parameters (must be int or string, no arrays)
	 * See get_documents in wp-document-revisions.php for more information
	 *
	 * @since 1.2
	 * @param array $atts shortcode attributes
	 * @return string the shortcode output
	 */
	public function documents_shortcode( $atts ) {

		$defaults = array(
			'orderby' => 'modified',
			'order' => 'DESC',
		);

		// list of all string or int based query vars (because we are going through shortcode)
		// via http://codex.wordpress.org/Class_Reference/WP_Query#Parameters
		$keys = array(
			'author',
			'author_name',
			'author__in',
			'author__not_in',
			'cat',
			'category_name',
			'category__and',
			'category__in',
			'category__not_in',
			'tag',
			'tag_id',
			'tag__and',
			'tag__in',
			'tag__not_in',
			'tag_slug__and',
			'tag_slug__in',
			'tax_query',
			's',
			'p',
			'name',
			'title',
			'page_id',
			'pagename',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'post__in',
			'post__not_in',
			'post_name__in',
			'has_password',
			'post_password',
			'post_status',
			'numberposts',
			'year',
			'monthnum',
			'w',
			'day',
			'hour',
			'minute',
			'second',
			'm',
			'date_query',
			'meta_key',
			'meta_value',
			'meta_value_num',
			'meta_compare',
			'meta_query',
		);

		foreach ( $keys as $key ) {
			$defaults[ $key ] = null;
		}

		$taxs = get_taxonomies(
			array(
				'object_type' => array( 'document' ),
			), 'objects'
		);

		// allow querying by custom taxonomy
		foreach ( $taxs as $tax ) {
			$defaults[ $tax->query_var ] = null;
		}

		$atts = apply_filters( 'document_shortcode_atts', $atts );

		// default arguments, can be overriden by shortcode attributes
		$atts = shortcode_atts( $defaults, $atts );
		$atts = array_filter( $atts );

		$documents = $this->get_documents( $atts );

		// buffer output to return rather than echo directly
		ob_start();
?>
		<ul class="documents">
		<?php
		// loop through found documents
		foreach ( $documents as $document ) {
		?>
			<li class="document document-<?php echo esc_attr( $document->ID ); ?>">
				<a href="<?php echo esc_url( get_permalink( $document->ID ) ); ?>">
					<?php echo esc_html( get_the_title( $document->ID ) ); ?>
				</a>
			</li>
		<?php } ?>
		</ul>
		<?php
		// grab buffer contents and clear
		$output = ob_get_contents();
		ob_end_clean();
		return $output;

	}

	/**
	 * Provides workaround for taxonomies with hyphens in their name
	 * User should replace hyphen with underscope and plugin will compensate
	 *
	 * @param Array $atts shortcode attributes
	 * @return Array modified shortcode attributes
	 */
	public function shortcode_atts_hyphen_filter( $atts ) {

		foreach ( (array) $atts as $k => $v ) {

			if ( strpos( $k, '_' ) === false ) {
				continue;
			}

			$alt = str_replace( '_', '-', $k );

			if ( ! taxonomy_exists( $alt ) ) {
				continue;
			}

			$atts[ $alt ] = $v;
			unset( $atts[ $k ] );
		}

		return $atts;
	}

}

/**
 * Recently revised documents widget
 */
class Document_Revisions_Recently_Revised_Widget extends WP_Widget {

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
			'draft' => false,
		),
		'show_author' => true,
	);

	/**
	 * Init widget and register
	 */
	public function __construct() {
		parent::__construct( 'Document_Revisions_Recently_Revised_Widget', __( 'Recently Revised Documents', 'wp-document-revisions' ) );

		// can't i18n outside of a function
		$this->defaults['title'] = __( 'Recently Revised Documents', 'wp-document-revisions' );
	}


	/**
	 * Callback to display widget contents
	 *
	 * @param Array  $args the widget arguments
	 * @param Object $instance the WP Document Revisions instance
	 */
	public function widget( $args, $instance ) {

		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = Document_Revisions::$instance;
		}

		// enabled statuses are stored as status => bool, but we want an array of only activated statuses
		$statuses = array_filter( (array) $instance['post_status'] );
		$statuses = array_keys( $statuses );

		$query = array(
			'orderby'     => 'modified',
			'order'       => 'DESC',
			'numberposts' => (int) $instance['numberposts'],
			'post_status' => $statuses,
		);

		$documents = $wpdr->get_documents( $query );

		// no documents, don't bother
		if ( ! $documents ) {
			return;
		}

		// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'] . $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'] . '<ul>';

		foreach ( $documents as $document ) :
			$link = ( current_user_can( 'edit_document', $document->ID ) ) ? add_query_arg(
				array(
					'post' => $document->ID,
					'action' => 'edit',
				), admin_url( 'post.php' )
			) : get_permalink( $document->ID );
			// translators: %1$s is the time ago in words, %2$s is the author
			$format_string = ( $instance['show_author'] ) ? __( '%1$s ago by %2$s', 'wp-document-revisions' ) : __( '%1$s ago', 'wp-document-revisions' );
?>
			<li>
				<a href="<?php echo esc_attr( $link ); ?>"><?php echo get_the_title( $document->ID ); ?></a><br />
				<?php printf( esc_html( $format_string ), esc_html( human_time_diff( strtotime( $document->post_modified_gmt ) ) ), esc_html( get_the_author_meta( 'display_name', $document->post_author ) ) ); ?>
			</li>
		<?php
		endforeach;

		// @codingStandardsIgnoreLine WordPress.XSS.EscapeOutput.OutputNotEscaped
		echo '</ul>' . $args['after_widget'];
	}


	/**
	 * Callback to display widget options form
	 *
	 * @param Object $instance the WP Document Revisions instance
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
	 * Sanitizes options and saves
	 *
	 * @param Object $new_instance the new instance
	 * @param Object $old_instance the old instance
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['numberposts'] = (int) $new_instance['numberposts'];
		$instance['show_author'] = (bool) $new_instance['show_author'];

		// merge post statuses into an array
		foreach ( $this->defaults['post_status'] as $status => $value ) {
			$instance['post_status'][ $status ] = (bool) isset( $new_instance[ 'post_status_' . $status ] );
		}

		return $instance;
	}


}

/**
 * Callback to register the recently revised widget
 */
function drrrw_widgets_init() {
	register_widget( 'Document_Revisions_Recently_Revised_Widget' );
}


add_action( 'widgets_init', 'drrrw_widgets_init' );
