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
	 * The default data
	 *
	 * @var $defaults
	 */
	public $defaults = array(
		'title'       => '',
		'numberposts' => 5,
		'post_status' => array(
			'publish' => true,
			'private' => false,
			'draft'   => false,
		),
		'show_thumb'  => false,
		'show_descr'  => true,
		'show_author' => true,
		'show_pdf'    => true,
		'new_tab'     => false,
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
	 * Generate widget contents.
	 *
	 * @param Array  $args the widget arguments.
	 * @param Object $instance the WP Document Revisions instance.
	 */
	public function widget_gen( $args, $instance ) {
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
			'perm'        => 'readable',
		);

		$documents = $wpdr->get_documents( $query );

		// no documents, don't bother.
		if ( ! $documents ) {
			return '';
		}
		// when getting the images, we may get generated images - getting them will use a cached version of std directory.

		$h_n = ( empty( $instance['title'] ) ? 2 : 3 );
		// buffer output to return rather than echo directly.
		ob_start();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'] . $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'] ) ) . $args['after_title'] . '<ul>';

		if ( (bool) $instance['show_thumb'] ) {
			// PDF files may have a generated image, and the access call uses a cached version of the (std) upload directory
			// so cannot change within call and may be wrong, so possibly replace it in the output.
			$std_dir = str_replace( ABSPATH, '', $wpdr::$wp_default_dir['basedir'] );
			$doc_dir = str_replace( ABSPATH, '', $wpdr->document_upload_dir() );
		}

		foreach ( $documents as $document ) {
			$link   = ( current_user_can( 'edit_document', $document->ID ) ) ? add_query_arg(
				array(
					'post'   => $document->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			) : get_permalink( $document->ID );
			$target = ( $instance['new_tab'] ? ' target="_blank"' : '' );
			// translators: %1$s is the time ago in words, %2$s is the author.
			$format_string = ( $instance['show_author'] ) ? __( '%1$s ago by %2$s', 'wp-document-revisions' ) : __( '%1$s ago', 'wp-document-revisions' );
			// do we need to highlight PDFs.
			$pdf = '';
			if ( $instance['show_pdf'] ) {
				// find mimetype.
				$doc_attach = $wpdr->get_document( $document->ID );
				$mimetype   = $wpdr->get_doc_mimetype( get_attached_file( $doc_attach->ID ) );
				if ( 'application/pdf' === strtolower( $mimetype ) ) {
					$pdf = ' <small>' . __( '(PDF)', 'wp-document-revisions' ) . '</small>';
				}
			}
			?>
			<li>
				<h<?php echo esc_attr( $h_n ); ?> class="wp-block-post-title"><a href="<?php echo esc_attr( $link ) . '"' . esc_attr( $target ) . '>' . esc_html( get_the_title( $document->ID ) ) . wp_kses_post( $pdf ); ?></a></h<?php echo esc_attr( $h_n ); ?>>
				<?php
				if ( (bool) $instance['show_thumb'] ) {
					$thumb = get_post_thumbnail_id( $document->ID );
					if ( $thumb ) {
						$thumb_image     = wp_get_attachment_image_src( $thumb, 'post-thumbnail' );
						$thumb_image_alt = get_post_meta( $thumb, '_wp_attachment_image_alt', true );
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<img class="attachment-post-thumbnail size-post-thumbnail wp-post-image" src="' . esc_url( $thumb_image[0] ) . '" alt="' . esc_html( $thumb_image_alt ) . '"><br />';
					} else {
						$attach = $wpdr->get_document( $document->ID );
						if ( $attach instanceof WP_Post ) {
							// ensure document slug hidden from attachment.
							$wpdr->hide_exist_doc_attach_slug( $attach->ID );
							$image = wp_get_attachment_image( $attach->ID, 'post-thumbnail' ) . '<br />';
							if ( $std_dir !== $doc_dir ) {
								$image = str_replace( $std_dir, $doc_dir, $image );
							}
						} else {
							$image = '<p>' . __( 'No attachment available.', 'wp-document-revisions' ) . '</p>';
						}
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $image;
					}
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo ( (bool) $instance['show_descr'] && ! is_numeric( $document->post_content ) ) ? '<div class="wp-block-paragraph">' . $document->post_content . '</div>' : '';
				printf( esc_html( $format_string ), esc_html( human_time_diff( strtotime( $document->post_modified_gmt ) ) ), esc_html( get_the_author_meta( 'display_name', $document->post_author ) ) );
				?>
			</li>
			<?php
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</ul>' . $args['after_widget'];

		// return buffer contents and remove it.
		return ob_get_clean();
	}

	/**
	 * Callback to display widget contents in classic widget.
	 *
	 * @param Array  $args the widget arguments.
	 * @param Object $instance the WP Document Revisions instance.
	 */
	public function widget( $args, $instance ) {

		$instance = wp_parse_args( $instance, $this->defaults );
		$output   = $this->widget_gen( $args, $instance );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output;
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
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php esc_html_e( 'Display Featured Image:', 'wp-document-revisions' ); ?></label><br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>" <?php checked( $instance['show_thumb'] ); ?> /> <?php esc_html_e( 'Yes', 'wp-document-revisions' ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_descr' ) ); ?>"><?php esc_html_e( 'Display Document Description:', 'wp-document-revisions' ); ?></label><br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_descr' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_descr' ) ); ?>" <?php checked( $instance['show_descr'] ); ?> /> <?php esc_html_e( 'Yes', 'wp-document-revisions' ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_author' ) ); ?>"><?php esc_html_e( 'Display Document Author:', 'wp-document-revisions' ); ?></label><br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_author' ) ); ?>" <?php checked( $instance['show_author'] ); ?> /> <?php esc_html_e( 'Yes', 'wp-document-revisions' ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_pdf' ) ); ?>"><?php esc_html_e( 'Display PDF File Indication:', 'wp-document-revisions' ); ?></label><br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_pdf' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_pdf' ) ); ?>" <?php checked( $instance['show_pdf'] ); ?> /> <?php esc_html_e( 'Yes', 'wp-document-revisions' ); ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'new_tab' ) ); ?>"><?php esc_html_e( 'Open documents in new tab:', 'wp-document-revisions' ); ?></label><br />
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'new_tab' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'new_tab' ) ); ?>" <?php checked( $instance['new_tab'] ); ?> /> <?php esc_html_e( 'Yes', 'wp-document-revisions' ); ?>
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
		$instance['show_thumb']  = (bool) $new_instance['show_thumb'];
		$instance['show_descr']  = (bool) $new_instance['show_descr'];
		$instance['show_author'] = (bool) $new_instance['show_author'];
		$instance['show_pdf']    = (bool) $new_instance['show_pdf'];
		$instance['new_tab']     = (bool) $new_instance['new_tab'];

		// merge post statuses into an array.
		foreach ( $this->defaults['post_status'] as $status => $value ) {
			$instance['post_status'][ $status ] = (bool) isset( $new_instance[ 'post_status_' . $status ] );
		}

		return $instance;
	}


	/**
	 * Register widget block.
	 *
	 * @since 3.3.0
	 */
	public function documents_widget_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active, e.g. Old WP version installed.
			return;
		}

		$dir      = dirname( __DIR__ );
		$suffix   = ( WP_DEBUG ) ? '.dev' : '';
		$index_js = 'js/wpdr-documents-widget' . $suffix . '.js';
		wp_register_script(
			'wpdr-documents-widget-editor',
			plugins_url( $index_js, __DIR__ ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
				'wp-i18n',
			),
			filemtime( "$dir/$index_js" ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$index_css = 'css/wpdr-widget-editor-style.css';
		wp_register_style(
			'wpdr-documents-widget-editor-style',
			plugins_url( $index_css, __DIR__ ),
			array( 'wp-edit-blocks' ),
			filemtime( plugin_dir_path( "$dir/$index_css" ) )
		);

		register_block_type(
			'wp-document-revisions/documents-widget',
			array(
				'description'     => __( 'This block provides a block of the most recently changed documentsand is functionally equivalent to the recently revised widget.', 'wp-document-revisions' ),
				'editor_script'   => 'wpdr-documents-widget-editor',
				'editor_style'    => 'wpdr-documents-widget-editor-style',
				'render_callback' => array( $this, 'wpdr_documents_widget_display' ),
				'attributes'      => array(
					'header'            => array(
						'type' => 'string',
					),
					'numberposts'       => array(
						'type'    => 'number',
						'default' => 5,
					),
					'post_stat_publish' => array(
						'type' => 'boolean',
					),
					'post_stat_private' => array(
						'type' => 'boolean',
					),
					'post_stat_draft'   => array(
						'type' => 'boolean',
					),
					'show_thumb'        => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'show_descr'        => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'show_author'       => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'show_pdf'          => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'new_tab'           => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'align'             => array(
						'type' => 'string',
					),
					'backgroundColor'   => array(
						'type' => 'string',
					),
					'linkColor'         => array(
						'type' => 'string',
					),
					'textColor'         => array(
						'type' => 'string',
					),
					'gradient'          => array(
						'type' => 'string',
					),
					'fontSize'          => array(
						'type' => 'string',
					),
					'style'             => array(
						'type' => 'object',
					),
				),
			)
		);

		// set translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wpdr-documents-widget-editor', 'wp-document-revisions' );
		}

		// Find sizes for images for PDFs. (Logic based on /wp-admin/includes/image.php).
		$merged_sizes = array(
			'thumbnail',
			'medium',
			'large',
		);

		/**
		 * Filters the image sizes generated for non-image mime types.
		 *
		 * @since 4.7.0
		 *
		 * @param string[] $merged_sizes An array of image size names.
		 * @param array    $metadata     Current attachment metadata.
		 */
		$merged_sizes = apply_filters( 'fallback_intermediate_image_sizes', $merged_sizes, array() );

		if ( function_exists( 'get_intermediate_image_sizes' ) ) {
			$registered_sizes = get_intermediate_image_sizes();
			$merged_sizes     = array_merge( $registered_sizes, $merged_sizes );
		}
	}


	/**
	 * Render widget block server side.
	 *
	 * @param array  $atts     block attributes coming from block.
	 * @param string $content  Optional. Block content. Default empty string.
	 * @since 3.3.0
	 */
	public function wpdr_documents_widget_display( $atts, $content = '' ) {
		// Create the two parameter sets.
		$args                    = array(
			'before_widget' => '',
			'before_title'  => '',
			'after_title'   => '',
			'after_widget'  => '',
		);
		$instance                = array();
		$instance['title']       = ( isset( $atts['header'] ) ? $atts['header'] : '' );
		$instance['numberposts'] = ( isset( $atts['numberposts'] ) ? (int) $atts['numberposts'] : 5 );
		$instance['show_thumb']  = ( isset( $atts['show_thumb'] ) ? (bool) $atts['show_thumb'] : false );
		$instance['show_descr']  = ( isset( $atts['show_descr'] ) ? (bool) $atts['show_descr'] : true );
		$instance['show_author'] = ( isset( $atts['show_author'] ) ? (bool) $atts['show_author'] : true );
		$instance['show_pdf']    = ( isset( $atts['show_pdf'] ) ? (bool) $atts['show_pdf'] : false );
		$instance['new_tab']     = ( isset( $atts['new_tab'] ) ? (bool) $atts['new_tab'] : true );
		$instance['post_status'] = array(  // temp.
			'publish' => ( isset( $atts['post_stat_publish'] ) ? (bool) $atts['post_stat_publish'] : true ),
			'private' => ( isset( $atts['post_stat_private'] ) ? (bool) $atts['post_stat_private'] : false ),
			'draft'   => ( isset( $atts['post_stat_draft'] ) ? (bool) $atts['post_stat_draft'] : false ),
		);

		// if header is set, then title at level h2.
		if ( isset( $atts['header'] ) ) {
			$args['before_title'] = '<h2>';
			$args['after_title']  = '</h2>';
		}

		$output = $this->widget_gen( $args, $instance );
		return $output;
	}

	/**
	 * Callback to register the recently revised widget.
	 */
	public function wpdr_widgets_init() {
		global $wpdr_widget;

		register_widget( $wpdr_widget );
	}

	/**
	 * Callback to register the recently revised widget block.
	 *
	 * Call with low priority to let taxonomies be registered.
	 */
	public function wpdr_widgets_block_init() {
		global $wpdr_widget;

		$wpdr_widget->documents_widget_block();
	}
}
