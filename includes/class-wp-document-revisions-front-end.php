<?php
/**
 * Helper class for WP_Document_Revisions that registers shortcodes, widgets, etc. for use on the front-end.
 *
 * @since 1.2
 * @package WP_Document_Revisions
 */

/**
 * WP Document Revisions Front End.
 */
class WP_Document_Revisions_Front_End {

	/**
	 * The Parent WP_Document_Revisions instance.
	 *
	 * @var $parent
	 */
	public static $parent;

	/**
	 * The Singleton instance.
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Array of accepted shortcode keys and default values.
	 *
	 * @var $shortcode_defaults
	 */
	public $shortcode_defaults = array(
		'id'     => null,
		'number' => null,
	);

	/**
	 *  Registers front end hooks.
	 *
	 * @param Object $instance The WP Document Revisions instance.
	 */
	public function __construct( &$instance = null ) {

		self::$instance = &$this;

		// create or store parent instance.
		if ( is_null( $instance ) ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = &$instance;
		}

		add_shortcode( 'document_revisions', array( &$this, 'revisions_shortcode' ) );
		add_shortcode( 'documents', array( &$this, 'documents_shortcode' ) );
		add_filter( 'document_shortcode_atts', array( &$this, 'shortcode_atts_hyphen_filter' ) );

		// Queue up JS (low priority to be at end).
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_front' ), 50 );

	}


	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 1.2
	 * @param function $function the function to call.
	 * @param array    $args the arguments to pass to the function.
	 * @returns mixed the result of the function
	 */
	public function __call( $function, $args ) {
		return call_user_func_array( array( &self::$parent, $function ), $args );
	}


	/**
	 * Provides support to call properties of the parent class natively.
	 *
	 * @since 1.2
	 * @param string $name the property to fetch.
	 * @returns mixed the property's value
	 */
	public function __get( $name ) {
		return WP_Document_Revisions::$$name;
	}


	/**
	 * Callback to display revisions.
	 *
	 * @param array $atts attributes passed via short code.
	 * @returns string a UL with the revisions
	 * @since 1.2
	 */
	public function revisions_shortcode( $atts ) {

		// normalize args.
		$atts = shortcode_atts( $this->shortcode_defaults, $atts );
		foreach ( array_keys( $this->shortcode_defaults ) as $key ) {
			$$key = isset( $atts[ $key ] ) ? (int) $atts[ $key ] : null;
		}

		// do not show output to users that do not have the read_document_revisions capability.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return;
		}

		// get revisions.
		$revisions = $this->get_revisions( $id );

		// show a limited number of revisions.
		if ( null !== $number ) {
			$revisions = array_slice( $revisions, 0, (int) $number );
		}

		// buffer output to return rather than echo directly.
		ob_start();
		?>
		<ul class="revisions document-<?php echo esc_attr( $id ); ?>">
		<?php
		// loop through each revision.
		// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
		foreach ( $revisions as $revision ) {
			?>
			<li class="revision revision-<?php echo esc_attr( $revision->ID ); ?>" >
				<?php
				// html - string not to be translated.
				printf( '<a href="%1$s" title="%2$s" id="%3$s" class="timestamp">%4$s</a> <span class="agoby">ago by</a> <span class="author">%5$s</a>', esc_url( get_permalink( $revision->ID ) ), esc_attr( $revision->post_date ), esc_html( strtotime( $revision->post_date ) ), esc_html( human_time_diff( strtotime( $revision->post_date ) ) ), esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ) );
				?>
			</li>
			<?php
		// phpcs:enable WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		?>
		</ul>
		<?php
		// grab buffer contents and clear.
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}


	/**
	 * Shortcode to query for documents.
	 * Takes most standard WP_Query parameters (must be int or string, no arrays)
	 * See get_documents in wp-document-revisions.php for more information.
	 *
	 * @since 1.2
	 * @param array $atts shortcode attributes.
	 * @return string the shortcode output
	 */
	public function documents_shortcode( $atts ) {

		$defaults = array(
			'orderby' => 'modified',
			'order'   => 'DESC',
		);

		// list of all string or int based query vars (because we are going through shortcode)
		// via http://codex.wordpress.org/Class_Reference/WP_Query#Parameters.
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
			),
			'objects'
		);

		// allow querying by custom taxonomy.
		foreach ( $taxs as $tax ) {
			$defaults[ $tax->query_var ] = null;
		}

		/**
		 * Filters the Document shortcode attributes.
		 *
		 * @param array $atts attributes set on the shortcode.
		 */
		$atts = apply_filters( 'document_shortcode_atts', $atts );

		// default arguments, can be overriden by shortcode attributes.
		$atts = shortcode_atts( $defaults, $atts );
		$atts = array_filter( $atts );

		$documents = $this->get_documents( $atts );

		// check whether to show update option. Default - only administrator role.
		$show_edit = false;
		$user      = wp_get_current_user();
		if ( $user->ID > 0 ) {
			// logged on user only.
			$roles = (array) $user->roles;
			if ( in_array( 'administrator', $roles, true ) ) {
				$show_edit = true;
			}
		}
		/**
		 * Filters the controlling option to display an edit option against each document.
		 *
		 * By default, only logged-in administrators be able to have an edit option.
		 * The user will also need to be able to edit the individual document before it is displayed.
		 *
		 * @since 3.2.0
		 *
		 * @param boolean $show_edit default value.
		 */
		$show_edit = apply_filters( 'document_shortcode_show_edit', $show_edit );

		// buffer output to return rather than echo directly.
		ob_start();
		?>
		<ul class="documents">
		<?php
		// loop through found documents.
		foreach ( $documents as $document ) {
			?>
			<li class="document document-<?php echo esc_attr( $document->ID ); ?>">
			<a href="<?php echo esc_url( get_permalink( $document->ID ) ); ?>">
				<?php echo esc_html( get_the_title( $document->ID ) ); ?>
			</a>
			<?php
			if ( $show_edit && current_user_can( 'edit_document', $document->ID ) ) {
				$link = add_query_arg(
					array(
						'post'   => $document->ID,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				);
				echo '&nbsp;&nbsp;<a class="document-mod" href="' . esc_attr( $link ) . '">[' . esc_html__( 'Edit', 'wp-document-revisions' ) . ']</a>';
			}
			?>
			</li>
		<?php } ?>
		</ul>
		<?php
		// grab buffer contents and clear.
		$output = ob_get_contents();
		ob_end_clean();
		return $output;

	}

	/**
	 * Shortcode can have CSS on any page.
	 *
	 * @since 3.2.0
	 */
	public function enqueue_front() {

		$wpdr = self::$parent;

		// enqueue CSS for shortcode.
		wp_enqueue_style( 'wp-document-revisions-front', plugins_url( '/css/style-front.css', dirname( __FILE__ ) ), null, $wpdr->version );

	}


	/**
	 * Provides workaround for taxonomies with hyphens in their name
	 * User should replace hyphen with underscope and plugin will compensate.
	 *
	 * @param Array $atts shortcode attributes.
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
