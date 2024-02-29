<?php
/**
 * Helper class for WP_Document_Revisions that registers shortcodes, etc. for use on the front-end.
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
		'id'          => null,
		'numberposts' => null,
		'show_thumb'  => false,
		'show_descr'  => true,
		'new_tab'     => true,
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

		// Add blocks. Done after wp_loaded so that the taxonomies have been defined.
		add_action( 'wp_loaded', array( &$this, 'documents_shortcode_blocks' ), 100 );

		// Queue up JS (low priority to be at end).
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_front' ), 50 );
	}


	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 1.2
	 * @param function $funct the function to call.
	 * @param array    $args  the arguments to pass to the function.
	 * @returns mixed the result of the function
	 */
	public function __call( $funct, $args ) {
		return call_user_func_array( array( &self::$parent, $funct ), $args );
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

		// change attribute number into numberposts (for backward compatibility).
		if ( array_key_exists( 'number', $atts ) && ! array_key_exists( 'numberposts', $atts ) ) {
			$atts['numberposts'] = $atts['number'];
			unset( $atts['number'] );
		}

		// normalize args.
		$atts = shortcode_atts( $this->shortcode_defaults, $atts, 'document' );
		foreach ( array_keys( (array) $this->shortcode_defaults ) as $key ) {
			$$key = isset( $atts[ $key ] ) ? (int) $atts[ $key ] : null;
		}

		// do not show output to users that do not have the read_document_revisions capability.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// Check it is a document.
		global $wpdr;
		if ( ! $wpdr->verify_post_type( $id ) ) {
			return '<p>' . esc_html__( 'This is not a valid document.', 'wp-document-revisions' ) . '</p>';
		}

		// get revisions.
		$revisions = $this->get_revisions( $id );

		// show a limited number of revisions.
		if ( null !== $numberposts ) {
			$revisions = array_slice( $revisions, 0, (int) $numberposts );
		}

		if ( isset( $atts['summary'] ) ) {
			$atts_summary = filter_var( $atts['summary'], FILTER_VALIDATE_BOOLEAN );
		} else {
			$atts_summary = false;
		}

		if ( isset( $atts['show_pdf'] ) ) {
			$attach   = $wpdr->get_document( $document->ID );
			$file     = get_attached_file( $attach->ID );
			$mimetype = $wpdr->get_doc_mimetype( $file );
			$show_pdf = ( 'application/pdf' === strtolower( $mimetype ) ? ' <small>' . __( '(PDF)', 'wp-document-revisions' ) . '</small>' : '' );
		} else {
			$atts_show_pdf = '';
		}

		if ( isset( $atts['new_tab'] ) ) {
			$atts_new_tab = filter_var( $atts['new_tab'], FILTER_VALIDATE_BOOLEAN );
		} else {
			$atts_new_tab = false;
		}

		// buffer output to return rather than echo directly.
		ob_start();
		?>
		<ul class="revisions document-<?php echo esc_attr( $id ); ?>">
		<?php
		// loop through each revision.
		foreach ( $revisions as $revision ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<li class="revision revision-<?php echo esc_attr( $revision->ID ); ?>" >
				<?php
				// html - string not to be translated.
				printf( '<a href="%1$s" title="%2$s" id="%3$s" class="timestamp"', esc_url( get_permalink( $revision->ID ) ), esc_attr( $revision->post_modified ), esc_html( strtotime( $revision->post_modified ) ) );
				echo ( $atts_new_tab ? ' target="_blank"' : '' );
				printf( '>%s</a> <span class="agoby">', esc_html( human_time_diff( strtotime( $revision->post_modified_gmt ), time() ) ) . wp_kses_post( $atts_show_pdf ) );
				esc_html_e( 'ago by', 'wp-document-revisions' );
				printf( '</span> <span class="author">%s</span>', esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ) );
				echo ( $atts_summary ? '<br/>' . esc_html( $revision->post_excerpt ) : '' );
				?>
			</li>
			<?php
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		</ul>
		<?php
		// grab buffer contents and remove.
		return ob_get_clean();
	}


	/**
	 * Shortcode to query for documents.
	 * Called from shortcode sirectly.
	 *
	 * @since 3.3
	 * @param array $atts shortcode attributes.
	 * @return string the shortcode output
	 */
	public function documents_shortcode( $atts ) {

		// Only need to do something if workflow_state points to post_status.
		if ( 'workflow_state' !== self::$parent->taxonomy_key() ) {
			if ( in_array( 'workflow_state', $atts, true ) ) {
				$atts['post_status'] = $atts['workflow_state'];
				unset( $atts['workflow_state'] );
			}
		}

		return $this->documents_shortcode_int( $atts );
	}


	/**
	 * Shortcode to query for documents.
	 * Takes most standard WP_Query parameters (must be int or string, no arrays)
	 * See get_documents in wp-document-revisions.php for more information.
	 *
	 * This is the original documents_shortcode function but an added layer for sorting
	 * reuse of workflow_state when EditLlow or PublishPressi is used.
	 *
	 * @since 1.2
	 * @param array $atts shortcode attributes.
	 * @return string the shortcode output
	 */
	private function documents_shortcode_int( $atts ) {

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
			// Presentation attributes (will be dealt with before getting documents).
		);

		foreach ( $keys as $key ) {
			$defaults[ $key ] = null;
		}

		// allow querying by custom taxonomy.
		$taxs = $this->get_taxonomy_details();
		foreach ( $taxs['taxos'] as $tax ) {
			$defaults[ $tax['query'] ] = null;
		}

		// show_edit, show_thumb, show_descr, show_pdf and new_tab may be entered without name (implies value true)
		// convert to name value pair.
		for ( $i = 0; $i < 5; $i++ ) {
			if ( isset( $atts[ $i ] ) ) {
				$atts[ $atts[ $i ] ] = true;
				unset( $atts[ $i ] );
			}
		}

		// Presentation attributes may be set as false, so process before array_filter and remove.
		if ( isset( $atts['show_edit'] ) ) {
			$atts_show_edit = filter_var( $atts['show_edit'], FILTER_VALIDATE_BOOLEAN );
			unset( $atts['show_edit'] );
		} else {
			// Want to know if there was a shortcode as it will override.
			$atts_show_edit = null;
		}

		if ( isset( $atts['show_thumb'] ) ) {
			$atts_show_thumb = filter_var( $atts['show_thumb'], FILTER_VALIDATE_BOOLEAN );
			unset( $atts['show_thumb'] );
		} else {
			$atts_show_thumb = false;
		}

		if ( isset( $atts['show_descr'] ) ) {
			$atts_show_descr = filter_var( $atts['show_descr'], FILTER_VALIDATE_BOOLEAN );
			unset( $atts['show_descr'] );
		} else {
			$atts_show_descr = false;
		}

		if ( isset( $atts['show_pdf'] ) ) {
			$atts_show_pdf = ' <small>' . __( '(PDF)', 'wp-document-revisions' ) . '</small>';
			unset( $atts['show_pdf'] );
		} else {
			$atts_show_pdf = '';
		}

		if ( isset( $atts['new_tab'] ) ) {
			$atts_new_tab = filter_var( $atts['new_tab'], FILTER_VALIDATE_BOOLEAN );
			unset( $atts['new_tab'] );
		} else {
			$atts_new_tab = false;
		}

		/**
		 * Filters the Document shortcode attributes.
		 *
		 * @param array $atts attributes set on the shortcode.
		 */
		$atts = apply_filters( 'document_shortcode_atts', $atts );

		// default arguments, can be overriden by shortcode attributes.
		// note that the filter shortcode_atts_document is also available to filter the attributes.
		$atts = shortcode_atts( $defaults, $atts, 'document' );

		$atts = array_filter( $atts );

		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}

		if ( $atts_show_thumb ) {
			// PDF files may have a generated image, and the access call uses a cached version of the (std) upload directory
			// so cannot change within call and may be wrong, so possibly replace it in the output.
			$std_dir = str_replace( ABSPATH, '', $wpdr::$wp_default_dir['basedir'] );
			$doc_dir = str_replace( ABSPATH, '', $wpdr->document_upload_dir() );
		}

		$documents = $wpdr->get_documents( $atts );

		// Determine whether to output edit option - shortcode value will override.
		if ( is_null( $atts_show_edit ) ) {
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
		} else {
			$show_edit = $atts_show_edit;
		}

		// buffer output to return rather than echo directly.
		ob_start();
		?>
		<ul class="documents">
		<?php
		// loop through found documents.
		foreach ( $documents as $document ) {
			if ( empty( $atts_show_pdf ) ) {
				$show_pdf = '';
			} else {
				$attach   = $wpdr->get_document( $document->ID );
				$file     = get_attached_file( $attach->ID );
				$mimetype = $wpdr->get_doc_mimetype( $file );
				$show_pdf = ( 'application/pdf' === strtolower( $mimetype ) ? $atts_show_pdf : '' );
			}
			?>
			<li class="document document-<?php echo esc_attr( $document->ID ); ?>">
			<a href="<?php echo esc_url( get_permalink( $document->ID ) ); ?>"
				<?php echo ( $atts_new_tab ? ' target="_blank"' : '' ); ?>>
				<?php echo esc_html( get_the_title( $document->ID ) ) . wp_kses_post( $show_pdf ); ?>
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
				echo '&nbsp;&nbsp;<small><a class="document-mod" href="' . esc_attr( $link ) . '">[' . esc_html__( 'Edit', 'wp-document-revisions' ) . ']</a></small><br />';
			}
			if ( $atts_show_thumb ) {
				$thumb = get_post_thumbnail_id( $document->ID );
				if ( $thumb ) {
					$thumb_image     = wp_get_attachment_image_src( $thumb, 'post-thumbnail' );
					$thumb_image_alt = get_post_meta( $thumb, '_wp_attachment_image_alt', true );
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<br /><img class="attachment-post-thumbnail size-post-thumbnail wp-post-image" src="' . esc_url( $thumb_image[0] ) . '" alt="' . esc_html( $thumb_image_alt ) . '"><br />';
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
			// is_numeric is old format.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ( $atts_show_descr && ! is_numeric( $document->post_content ) ) ? '<div class="wp-block-paragraph">' . $document->post_content . '</div>' : '';
			?>
			</li>
		<?php } ?>
		</ul>
		<?php
		// grab buffer contents and remove.
		return ob_get_clean();
	}

	/**
	 * Shortcode can have CSS on any page.
	 *
	 * @since 3.2.0
	 */
	public function enqueue_front() {

		$wpdr = self::$parent;

		// enqueue CSS for shortcode.
		wp_enqueue_style( 'wp-document-revisions-front', plugins_url( '/css/style-front.css', __DIR__ ), null, $wpdr->version );
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

	/**
	 * Register WP Document Revisions block category.
	 *
	 * @since 3.3.0
	 * @param Array                   $categories           Block categories available.
	 * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
	 */
	public function wpdr_block_categories( $categories, $block_editor_context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'wpdr-category',
					'title' => __( 'WP Document Revisions', 'wp-document-revisions' ),
				),
			)
		);
	}


	/**
	 * Register revisions-shortcode block
	 *
	 * @since 3.3.0
	 */
	public function documents_shortcode_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active, e.g. Old WP version installed.
			return;
		}

		// add the plugin category.
		add_filter( 'block_categories_all', array( $this, 'wpdr_block_categories' ), 10, 2 );

		register_block_type(
			'wp-document-revisions/documents-shortcode',
			array(
				'description'     => __( 'This block provides a list of all documents meeting the selection criteria and is functionally equivalent to the [documents] shortcode.', 'wp-document-revisions' ),
				'editor_script'   => 'wpdr-documents-shortcode-editor',
				'render_callback' => array( $this, 'wpdr_documents_shortcode_display' ),
				'attributes'      => array(
					'header'          => array(
						'type'    => 'string',
						'default' => '',
					),
					'taxonomy_0'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'term_0'          => array(
						'type'    => 'number',
						'default' => 0,
					),
					'taxonomy_1'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'term_1'          => array(
						'type'    => 'number',
						'default' => 0,
					),
					'taxonomy_2'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'term_2'          => array(
						'type'    => 'number',
						'default' => 0,
					),
					'numberposts'     => array(
						'type'    => 'number',
						'default' => 5,
					),
					'orderby'         => array(
						'type' => 'string',
					),
					'order'           => array(
						'type' => 'string',
					),
					'show_edit'       => array(
						'type' => 'string',
					),
					'show_thumb'      => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'show_descr'      => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'show_pdf'        => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'new_tab'         => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'freeform'        => array(
						'type' => 'string',
					),
					'align'           => array(
						'type' => 'string',
					),
					'backgroundColor' => array(
						'type' => 'string',
					),
					'linkColor'       => array(
						'type' => 'string',
					),
					'textColor'       => array(
						'type' => 'string',
					),
					'gradient'        => array(
						'type' => 'string',
					),
					'fontSize'        => array(
						'type' => 'string',
					),
					'style'           => array(
						'type' => 'object',
					),
				),
			)
		);

		register_block_type(
			'wp-document-revisions/revisions-shortcode',
			array(
				'description'     => __( 'This block provides a list of all the revisions of a selected document and is functionally equivalent to the [documents_revisions] shortcode.', 'wp-document-revisions' ),
				'editor_script'   => 'wpdr-revisions-shortcode-editor',
				'render_callback' => array( $this, 'wpdr_revisions_shortcode_display' ),
				'attributes'      => array(
					'id'              => array(
						'type'    => 'number',
						'default' => 0,
					),
					'numberposts'     => array(
						'type'    => 'number',
						'default' => 5,
					),
					'summary'         => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'show_pdf'        => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'new_tab'         => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'align'           => array(
						'type' => 'string',
					),
					'backgroundColor' => array(
						'type' => 'string',
					),
					'linkColor'       => array(
						'type' => 'string',
					),
					'textColor'       => array(
						'type' => 'string',
					),
					'gradient'        => array(
						'type' => 'string',
					),
					'fontSize'        => array(
						'type' => 'string',
					),
					'style'           => array(
						'type' => 'object',
					),
				),
			)
		);

		// register scripts.
		$dir      = dirname( __DIR__ );
		$suffix   = ( WP_DEBUG ) ? '.dev' : '';
		$index_js = 'js/wpdr-documents-shortcode' . $suffix . '.js';
		wp_register_script(
			'wpdr-documents-shortcode-editor',
			plugins_url( $index_js, __DIR__ ),
			array(
				'wp-blocks',
				'wp-data',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-server-side-render',
				'wp-i18n',
			),
			filemtime( "$dir/$index_js" ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Add supplementary script for additional information.
		// document CPT has no default taxonomies, need to look up in wp_taxonomies.
		// Ensure taxonomies are set.
		$taxonomies = $this->get_taxonomy_details();
		wp_add_inline_script( 'wpdr-documents-shortcode-editor', 'const wpdr_data = ' . wp_json_encode( $taxonomies ), 'before' );

		$index_js = 'js/wpdr-revisions-shortcode' . $suffix . '.js';
		wp_register_script(
			'wpdr-revisions-shortcode-editor',
			plugins_url( $index_js, __DIR__ ),
			array(
				'wp-blocks',
				'wp-data',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-compose',
				'wp-server-side-render',
				'wp-i18n',
			),
			filemtime( "$dir/$index_js" ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// set translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wpdr-documents-shortcode-editor', 'wp-document-revisions' );
			wp_set_script_translations( 'wpdr-revisions-shortcode-editor', 'wp-document-revisions' );
		}
	}

	/**
	 * Flattened taxonomy term list.
	 *
	 * @var array $tax_terms array of terms.
	 */
	private static $tax_terms = array();


	/**
	 * Get taxonomy structure.
	 *
	 * @param String  $taxonomy Taxonomy name.
	 * @param Integer $par_term parent term.
	 * @param Integer $level    level in hierarchy.
	 * @since 3.3.0
	 */
	private function get_taxonomy_hierarchy( $taxonomy, $par_term = 0, $level = 0 ) {
		// get all direct descendants of the $parent.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'parent'     => $par_term,
			)
		);
		// go through all the direct descendants of $parent, and recurse their children.
		// this creates a treewalk in simple array format.
		foreach ( $terms as $term ) {
			// Mis-use term_group to hold level.
			$term->term_group  = $level;
			self::$tax_terms[] = $term;
			// recurse to get the direct descendants of "this" term.
			$this->get_taxonomy_hierarchy( $taxonomy, $term->term_id, $level + 1 );
		}
	}

	/**
	 * Get taxonomy names for documents (use cache).
	 *
	 * @return Array Taxonomy names for documents
	 * @since 3.3.0
	 */
	public function get_taxonomy_details() {
		$taxonomy_details = wp_cache_get( 'wpdr_document_taxonomies' );

		if ( false === $taxonomy_details ) {
			// build and create cache entry. Get name only to allow easier filtering.
			$taxos = get_object_taxonomies( 'document' );
			// Make sure 'workflow_state' is in the list if not disabled. With EF/PP it uses the post_status taxonomy.
			if ( ! empty( self::$parent->taxonomy_key() ) && ! in_array( 'workflow_state', (array) $taxos, true ) ) {
				$taxos[] = 'workflow_state';
			}

			sort( $taxos );

			/**
			 * Filters the Document taxonomies (allowing users to select the first three for the block widget.
			 *
			 * @param array $taxonomies taxonomies available for selection in the list block.
			 */
			$taxos = apply_filters( 'document_block_taxonomies', $taxos );

			$taxonomy_elements = array();
			// Has workflow_state been mangled? Note. set here as it could be filtered out.
			$wf_efpp = 0;
			$tax_key = self::$parent->taxonomy_key();
			foreach ( $taxos as $taxonomy ) {
				// Find the terms.
				$terms    = array();
				$terms[0] = array(
					0,  // value.
					__( 'No selection', 'wp-document-revisions' ),  // label.
					'',  // underscore-separated slug.
				);
				// Look up taxonomy.
				if ( 'workflow_state' === $taxonomy && ! empty( $tax_key ) && 'workflow_state' !== $tax_key ) {
					// EF/PP - Mis-use of 'post_status' taxonomy.
					$tax_arr                 = (array) get_taxonomy( $tax_key );
					$tax_arr['hierarchical'] = false;
					$tax_arr['label']        = 'Post Status';
					$object_type             = $tax_arr['object_type'];
					unset( $tax_arr['name'] );
					unset( $tax_arr['object_type'] );
					$tax     = new WP_Taxonomy( $tax_key, $object_type, $tax_arr );
					$wf_efpp = 1;
				} else {
					$tax = get_taxonomy( $taxonomy );
				}

				// Hierarchical or flat taxonomy ?
				if ( $tax->hierarchical ) {
					self::$tax_terms = array();
					// Get hierarchical list.
					$this->get_taxonomy_hierarchy( $taxonomy );
				} else {
					self::$tax_terms = get_terms(
						array(
							'taxonomy'     => $tax->name,
							'hide_empty'   => false,
							'hierarchical' => false,
						)
					);
				}
				foreach ( self::$tax_terms as $terms_obj ) {
					$indent  = ( $tax->hierarchical ? str_repeat( ' ', $terms_obj->term_group ) : '' );
					$terms[] = array(
						$terms_obj->term_id,
						$indent . $terms_obj->name,
						str_replace( '-', '_', $terms_obj->slug ), // Used for block<-> shortcode conversion.
					);
				}

				// Will use Query_var not (necessarily) the slug.
				$taxonomy_elements[] = array(
					'slug'  => $tax->name,
					'query' => ( empty( $tax->query_var ) ? $tax->name : $tax->query_var ),
					'label' => $tax->label,
					'terms' => $terms,
				);
			}
			$taxonomy_details = array(
				'stmax'   => count( $taxonomy_elements ),
				'wf_efpp' => $wf_efpp,
				'taxos'   => $taxonomy_elements,
			);

			wp_cache_set( 'wpdr_document_taxonomies', $taxonomy_details, '', ( WP_DEBUG ? 10 : 120 ) );
		}

		return $taxonomy_details;
	}

	/**
	 * Server side block to render the documents list.
	 *
	 * @param array $atts shortcode attributes.
	 * @returns string a UL with the revisions
	 * @since 3.3.0
	 */
	public function wpdr_documents_shortcode_display( $atts ) {
		// get instance of global class.
		global $wpdr;

		// sanity check.
		// do not show output to users that do not have the read_documents capability and don't get it via read.
		if ( ( ! current_user_can( 'read_documents' ) ) && ! apply_filters( 'document_read_uses_read', true ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// if header set, then output as <h2>.
		$output = '';
		if ( isset( $atts['header'] ) ) {
			$output = '<h2>' . esc_html( $atts['header'] ) . '</h2>';
		}

		$atts = shortcode_atts(
			array(
				'taxonomy_0'  => '',
				'term_0'      => 0,
				'taxonomy_1'  => '',
				'term_1'      => 0,
				'taxonomy_2'  => '',
				'term_2'      => 0,
				'numberposts' => 5,
				'orderby'     => '',
				'order'       => 'ASC',
				'show_edit'   => '',
				'show_thumb'  => false,
				'show_descr'  => true,
				'show_pdf'    => false,
				'new_tab'     => true,
				'freeform'    => '',
			),
			$atts,
			'document'
		);
		// Check taxonomy grouping is same as current taxonomy.
		$taxonomy_details = $this->get_taxonomy_details();
		$curr_tax_max     = $taxonomy_details['stmax'];
		$curr_taxos       = $taxonomy_details['taxos'];
		$errs             = '';
		// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent, Universal.WhiteSpace.PrecisionAlignment
		if ( ( $curr_tax_max >= 1 && ( ! empty( $atts['taxonomy_0'] ) ) && $atts['taxonomy_0'] !== $curr_taxos[0]['query'] ) ||
		     ( $curr_tax_max >= 2 && ( ! empty( $atts['taxonomy_1'] ) ) && $atts['taxonomy_1'] !== $curr_taxos[1]['query'] ) ||
		     ( $curr_tax_max >= 3 && ( ! empty( $atts['taxonomy_2'] ) ) && $atts['taxonomy_2'] !== $curr_taxos[2]['query'] ) ) {
			$errs .= '<p>' . esc_html__( ' Taxonomy details in this block have changed.', 'wp-document-revisions' ) . '</p>';
		}
		// phpcs:enable Generic.WhiteSpace.DisallowSpaceIndent, Universal.WhiteSpace.PrecisionAlignment

		// Remove attribute if not an over-ride.
		if ( 0 === strlen( $atts['show_edit'] ) ) {
			unset( $atts['show_edit'] );
		}
		if ( 0 === strlen( $atts['show_thumb'] ) ) {
			unset( $atts['show_thumb'] );
		}
		if ( 0 === strlen( $atts['show_descr'] ) ) {
			unset( $atts['show_descr'] );
		}

		// Remove show_pdf if false.
		if ( ! $atts['show_pdf'] ) {
			unset( $atts['show_pdf'] );
		}

		// Remove new_tab if false.
		if ( empty( $atts['new_tab'] ) ) {
			unset( $atts['new_tab'] );
		}

		// Deal with explicit taxonomomies. Note taxonomy_i is query_var, not slug.
		if ( empty( $atts['taxonomy_0'] ) || empty( $atts['term_0'] ) ) {
			null;
		} else {
			// get likely taxonomy.
			$taxo = ( $atts['taxonomy_0'] === $curr_taxos[0]['query'] ? $curr_taxos[0]['slug'] : '' );
			// create atts in the appropriate form tax->query_var = term slug.
			$term = get_term( $atts['term_0'], $taxo );
			if ( $term instanceof WP_Term ) {
				$atts[ $atts['taxonomy_0'] ] = $term->slug;
			} else {
				$errs .= '<p>' . esc_html__( ' Taxonomy term does not belong to this taxonomy.', 'wp-document-revisions' ) . ' (1)</p>';
			}
		}
		unset( $atts['taxonomy_0'] );
		unset( $atts['term_0'] );

		if ( empty( $atts['taxonomy_1'] ) || empty( $atts['term_1'] ) ) {
			null;
		} else {
			// get likely taxonomy.
			$taxo = ( isset( $curr_taxos[1]['query'] ) && $atts['taxonomy_1'] === $curr_taxos[1]['query'] ? $curr_taxos[1]['slug'] : '' );
			// create atts in the appropriate form tax->query_var = term slug.
			$term = get_term( $atts['term_1'], $taxo );
			if ( $term instanceof WP_Term ) {
				$atts[ $atts['taxonomy_1'] ] = $term->slug;
			} else {
				$errs .= '<p>' . esc_html__( ' Taxonomy term does not belong to this taxonomy.', 'wp-document-revisions' ) . ' (2)</p>';
			}
		}
		unset( $atts['taxonomy_1'] );
		unset( $atts['term_1'] );

		if ( empty( $atts['taxonomy_2'] ) || empty( $atts['term_2'] ) ) {
			null;
		} else {
			// get likely taxonomy.
			$taxo = ( isset( $curr_taxos[2]['query'] ) && $atts['taxonomy_2'] === $curr_taxos[2]['query'] ? $curr_taxos[2]['slug'] : '' );
			// create atts in the appropriate form tax->query_var = term slug).
			$term = get_term( $atts['term_2'], $taxo );
			if ( $term instanceof WP_Term ) {
				$atts[ $atts['taxonomy_2'] ] = $term->slug;
			} else {
				$errs .= '<p>' . esc_html__( ' Taxonomy term does not belong to this taxonomy.', 'wp-document-revisions' ) . ' (3)</p>';
			}
		}
		unset( $atts['taxonomy_2'] );
		unset( $atts['term_2'] );

		// deal with freeform attributes.
		if ( ! empty( $atts['freeform'] ) ) {
			$freeform = shortcode_parse_atts( $atts['freeform'] );
			$atts     = array_merge( $freeform, $atts );
		}
		unset( $atts['freeform'] );

		// if empty orderby attribute, then order is not relevant.
		if ( empty( $atts['orderby'] ) ) {
			unset( $atts['orderby'] );
			unset( $atts['order'] );
		}

		if ( ! empty( $errs ) ) {
			$errs = '<div class="notice notice-error">' . $errs . '</div>';
		}

		$output .= $errs . $this->documents_shortcode_int( $atts );
		return $output;
	}

	/**
	 * Server side block to render the revisions list.
	 *
	 * @param array $atts shortcode attributes.
	 * @returns string a UL with the revisions
	 * @since 3.3.0
	 */
	public function wpdr_revisions_shortcode_display( $atts ) {
		// get instance of global class.
		global $wpdr_fe;

		$atts = shortcode_atts(
			array(
				'id'          => 0,
				'numberposts' => 5,
				'summary'     => false,
				'show_pdf'    => false,
				'new_tab'     => true,
			),
			$atts,
			'document'
		);

		// sanity check.
		// do not show output to users that do not have the read_document_revisions capability.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// Check it is a document (and not its revision or attached document) so don't use verify_post_type.
		if ( 'document' !== get_post_type( $atts['id'] ) ) {
			return '<p>' . esc_html__( 'This is not a valid document.', 'wp-document-revisions' ) . '</p>';
		}

		// Remove show_pdf if false.
		if ( ! $atts['show_pdf'] ) {
			unset( $atts['show_pdf'] );
		}

		$output  = '<h2 class="document-title document-' . esc_attr( $atts['id'] ) . '">' . get_the_title( $atts['id'] ) . '</h2>';
		$output .= $wpdr_fe->revisions_shortcode( $atts );
		return $output;
	}
}

