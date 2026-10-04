<?php
/**
 * Helper class for WP_Document_Revisions that registers shortcodes, etc. for use on the front-end.
 *
 * @since 1.2
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Document Revisions Front End.
 *
 * Methods of the parent {@see WP_Document_Revisions} instance are callable on
 * this class natively via {@see WP_Document_Revisions_Front_End::__call()}, which
 * forwards to the parent. The `@method` tag below documents that forwarding so
 * static analysis can resolve the call; it adds no runtime behavior.
 *
 * @method WP_Post[] get_revisions( ?int $post_id )
 */
class WP_Document_Revisions_Front_End {

	/**
	 * The Parent WP_Document_Revisions instance.
	 *
	 * @var object
	 */
	public static $parent;

	/**
	 * The Singleton instance.
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Array of accepted shortcode keys and default values.
	 *
	 * @var array<string, mixed>
	 */
	public $shortcode_defaults = array(
		'id'          => null,
		'numberposts' => null,
		'show_thumb'  => false,
		'show_descr'  => true,
		'new_tab'     => true,
		'summary'     => false,
		'show_pdf'    => false,
	);

	/**
	 *  Registers front end hooks.
	 *
	 * @param Object $instance The WP Document Revisions instance.
	 */
	public function __construct( ?object $instance = null ) {

		self::$instance = &$this;

		// create or store parent instance.
		if ( is_null( $instance ) ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = $instance;
		}

		add_shortcode( 'document_revisions', array( $this, 'revisions_shortcode' ) );
		add_shortcode( 'documents', array( $this, 'documents_shortcode' ) );
		add_shortcode( 'document_preview', array( $this, 'wpdr_document_preview_display' ) );
		add_filter( 'document_shortcode_atts', array( $this, 'shortcode_atts_hyphen_filter' ) );

		// Add blocks. Done on standard init so that the block supports will be taken into account.
		add_action( 'init', array( $this, 'documents_shortcode_blocks' ) );
		// Add taxonomy data. Done on enqueue_block_editor_assets so that the taxonomies have been defined.
		add_action( 'enqueue_block_editor_assets', array( $this, 'documents_block_editor_data' ) );

		// Queue up JS (low priority to be at end).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front' ), 50 );
	}


	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 1.2
	 * @param string  $funct the function to call.
	 * @param mixed[] $args  the arguments to pass to the function.
	 * @return mixed the result of the function
	 */
	public function __call( string $funct, array $args ) {
		return call_user_func_array( array( &self::$parent, $funct ), $args );
	}


	/**
	 * Provides support to call properties of the parent class natively.
	 *
	 * @since 1.2
	 * @param string $name the property to fetch.
	 * @return mixed the property's value
	 */
	public function __get( string $name ) {
		return WP_Document_Revisions::$$name;
	}


	/**
	 * Callback to display revisions.
	 *
	 * @param array<string, mixed> $atts attributes passed via short code.
	 * @return string a UL with the revisions
	 * @since 1.2
	 */
	public function revisions_shortcode( $atts ): string {

		// WordPress passes empty string when shortcode has no attributes.
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		// change attribute number into numberposts (for backward compatibility).
		if ( array_key_exists( 'number', $atts ) && ! array_key_exists( 'numberposts', $atts ) ) {
			$atts['numberposts'] = $atts['number'];
			unset( $atts['number'] );
		}

		// summary, show_pdf and new_tab may be entered without a value (implies true).
		foreach ( array( 'summary', 'show_pdf', 'new_tab' ) as $flag ) {
			$pos = array_search( $flag, $atts, true );
			if ( is_int( $pos ) ) {
				$atts[ $flag ] = true;
				unset( $atts[ $pos ] );
			}
		}

		// normalize args.
		$atts = shortcode_atts( $this->shortcode_defaults, $atts, 'document' );
		// Extract recognized shortcode attributes into explicit local variables
		// (avoids the dynamic `$$key` pattern, which is opaque to static analysis).
		$id          = isset( $atts['id'] ) ? (int) $atts['id'] : null;
		$numberposts = isset( $atts['numberposts'] ) ? (int) $atts['numberposts'] : null;

		// do not show output to users that do not have the read_document_revisions capability.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// Check it is a document.
		global $wpdr;
		if ( ! $wpdr->verify_post_type( $id ) ) {
			return '<p>' . esc_html__( 'This is not a valid document.', 'wp-document-revisions' ) . '</p>';
		}

		// The user must be able to read this document.
		if ( ! $this->can_read_revisions( (int) $id ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
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

		$atts_show_pdf = '';
		if ( filter_var( $atts['show_pdf'], FILTER_VALIDATE_BOOLEAN ) ) {
			$attach = $wpdr->get_document( $id );
			$file   = $attach ? get_attached_file( $attach->ID ) : false;
			if ( $file ) {
				$mimetype      = $wpdr->get_doc_mimetype( $file, $attach->ID );
				$atts_show_pdf = ( 'application/pdf' === strtolower( $mimetype ) ? ' <small>' . __( '(PDF)', 'wp-document-revisions' ) . '</small>' : '' );
			}
		}

		if ( isset( $atts['new_tab'] ) ) {
			$atts_new_tab = filter_var( $atts['new_tab'], FILTER_VALIDATE_BOOLEAN );
		} else {
			$atts_new_tab = false;
		}

		// buffer output to return rather than echo directly.
		ob_start();
		?>
		<ul class="revisions document-<?php echo esc_attr( (string) $id ); ?>">
		<?php
		// loop through each revision.
		foreach ( $revisions as $revision ) {
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<li class="revision revision-<?php echo esc_attr( (string) $revision->ID ); ?>" >
				<?php
				// html - string not to be translated.
				printf( '<a href="%1$s" title="%2$s" id="%3$s" class="timestamp"', esc_url( get_permalink( $revision->ID ) ), esc_attr( $revision->post_modified ), esc_html( (string) strtotime( $revision->post_modified ) ) );
				echo ( $atts_new_tab ? ' target="_blank"' : '' );
				printf( '>%s</a> <span class="agoby">', esc_html( human_time_diff( strtotime( $revision->post_modified_gmt ), time() ) ) . wp_kses_post( $atts_show_pdf ) );
				esc_html_e( 'ago by', 'wp-document-revisions' );
				printf( '</span> <span class="author">%s</span>', esc_html( get_the_author_meta( 'display_name', $wpdr->get_revision_author( $revision ) ) ) );
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
	 * @param array<string, mixed> $atts shortcode attributes.
	 * @return string the shortcode output
	 */
	public function documents_shortcode( $atts ): string {

		// WordPress passes empty string when shortcode has no attributes.
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		// Only need to do something if workflow_state points to post_status.
		if ( 'workflow_state' !== self::$parent->taxonomy_key() ) {
			if ( array_key_exists( 'workflow_state', $atts ) ) {
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
	 * @param array<string, mixed> $atts shortcode attributes.
	 * @return string the shortcode output
	 */
	private function documents_shortcode_int( array $atts ): string {

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

		$atts_show_pdf = '';
		if ( isset( $atts['show_pdf'] ) ) {
			if ( filter_var( $atts['show_pdf'], FILTER_VALIDATE_BOOLEAN ) ) {
				$atts_show_pdf = ' <small>' . __( '(PDF)', 'wp-document-revisions' ) . '</small>';
			}
			unset( $atts['show_pdf'] );
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
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$wpdr = new WP_Document_Revisions();
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
			$permalink = get_permalink( $document->ID );
			if ( empty( $atts_show_pdf ) ) {
				$show_pdf = '';
			} else {
				$attach = $wpdr->get_document( $document->ID );
				$file   = get_attached_file( $attach->ID );
				if ( $file ) {
					$mimetype = $wpdr->get_doc_mimetype( $file, $attach->ID );
					$show_pdf = ( 'application/pdf' === strtolower( $mimetype ) ? $atts_show_pdf : '' );
				} else {
					// cant find attached file.
					$show_pdf = '';
				}
			}
			?>
			<li class="document document-<?php echo esc_attr( $document->ID ); ?>">
			<a href="<?php echo esc_url( $permalink ); ?>"
				<?php echo ( $atts_new_tab ? ' target="_blank"' : '' ); ?>>
				<?php echo esc_html( get_the_title( $document->ID ) ) . wp_kses_post( $show_pdf ); ?>
			</a>
			<?php
			if ( $show_edit && current_user_can( 'edit_document', $document->ID ) ) {
				// The Edit link is the only front-end output styled by style-front.css.
				$this->enqueue_front_style();
				$link = add_query_arg(
					array(
						'post'   => $document->ID,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				);
				echo '&nbsp;&nbsp;<small><a class="document-mod" href="' . esc_attr( $link ) . '">[' . esc_html__( 'Edit', 'wp-document-revisions' ) . ']</a></small><br />';
			}
			// Password-protected documents don't show their thumbnail or description.
			$protected = post_password_required( $document->ID );
			if ( $atts_show_thumb && ! $protected ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->document_thumbnail( $document, $permalink ) . '<br />';
			}
			// is_numeric is old format. WPDR comment will be stripped by wp_kses_post.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ( $atts_show_descr && ! $protected && ! is_numeric( $document->post_content ) ) ? '<div class="wp-block-paragraph">' . wp_kses_post( $document->post_content ) . '</div>' : '';
			?>
			</li>
		<?php } ?>
		</ul>
		<?php
		// grab buffer contents and remove.
		return ob_get_clean();
	}

	/**
	 * Thumbnail image HTML for a document in a list.
	 *
	 * Uses the featured image, or else the image generated from the first page of a PDF.
	 *
	 * @since 5.8.0
	 * @param WP_Post $document  the document.
	 * @param string  $permalink the document's permalink.
	 * @return string an img tag, or an HTML comment when there is no thumbnail.
	 */
	public function document_thumbnail( WP_Post $document, string $permalink ): string {
		// PDF files may have a generated image, and the access call uses a cached version of the (std) upload directory
		// so cannot change within call and may be wrong, so possibly replace it in the output.
		$doc_dir = str_replace( ABSPATH, '', self::$parent->document_upload_dir() );

		/**
		 * Filters the post thumbnail size on blocks/shortcodes - default thumbnail.
		 *
		 * @since 3.7.0
		 *
		 * @param string $size Requested image size. Can be any registered image size name.
		 */
		$thumb_size = apply_filters( 'document_thumbnail', 'thumbnail' );

		$image = '<!-- ' . __( 'No thumbnail available.', 'wp-document-revisions' ) . ' -->';
		$thumb = get_post_thumbnail_id( $document->ID );
		if ( $thumb ) {
			$image = wp_get_attachment_image( $thumb, $thumb_size );
		} else {
			$attach = self::$parent->get_document( $document->ID );
			if ( $attach instanceof WP_Post ) {
				// ensure document slug hidden from attachment.
				self::$parent->hide_exist_doc_attach_slug( $attach->ID );
				// find the image (if there).
				$meta = get_post_meta( $attach->ID, '_wp_attachment_metadata', true );
				if ( is_array( $meta ) && array_key_exists( 'sizes', $meta ) ) {
					$sizes = $meta['sizes'];
					if ( array_key_exists( $thumb_size, $sizes ) ) {
						$doc_thumb = $sizes[ $thumb_size ];
						// find the location of the attachment image.
						// The document permalink will contain the slug plus the correct sub_dir (if used).
						// Replace 'file name' and then the slug for directory.
						$url   = untrailingslashit( $permalink );
						$url   = substr( $url, 0, strrpos( $url, '/' ) + 1 ) . $doc_thumb['file'];
						$url   = str_replace( '/' . self::$parent->document_slug() . '/', '/' . $doc_dir . '/', $url );
						$image = '<img width="' . esc_attr( $doc_thumb['width'] ) . '" height="' . esc_attr( $doc_thumb['height'] ) . '" src="' . esc_url( $url ) . '" class="attachment-' . esc_attr( $thumb_size ) . ' size-' . esc_attr( $thumb_size ) . '" alt="' . esc_html( get_the_title( $document->ID ) ) . '"  decoding="async" loading="lazy" >';
					}
				}
			}
		}
		return $image;
	}

	/**
	 * Registers the front-end CSS. It's enqueued only when output that uses it renders.
	 *
	 * @since 3.2.0
	 */
	public function enqueue_front(): void {
		$this->register_front_style();
	}

	/**
	 * Registers the front-end stylesheet if it isn't already.
	 *
	 * @since 5.6.0
	 */
	private function register_front_style(): void {
		if ( wp_style_is( 'wp-document-revisions-front', 'registered' ) ) {
			return;
		}
		wp_register_style( 'wp-document-revisions-front', plugins_url( '/css/style-front.css', __DIR__ ), array(), self::$parent->version );
	}

	/**
	 * Enqueues the front-end stylesheet from inside shortcode or block output.
	 *
	 * WordPress prints styles enqueued after wp_head in the footer.
	 *
	 * @since 5.6.0
	 */
	private function enqueue_front_style(): void {
		$this->register_front_style();
		wp_enqueue_style( 'wp-document-revisions-front' );
	}

	/**
	 * Whether to register the plugin's blocks.
	 *
	 * @since 5.6.0
	 * @return bool
	 */
	public static function blocks_enabled(): bool {
		/**
		 * Filters whether to register WP Document Revisions' blocks (documents list,
		 * revisions list, document preview and recently revised documents).
		 *
		 * The [documents], [document_revisions] and [document_preview] shortcodes and
		 * the classic widget are unaffected.
		 *
		 * @since 5.6.0
		 *
		 * @param bool $register Whether to register the blocks. Default true.
		 */
		return (bool) apply_filters( 'document_register_blocks', true );
	}


	/**
	 * Provides workaround for taxonomies with hyphens in their name
	 * User should replace hyphen with underscope and plugin will compensate.
	 *
	 * @param mixed[] $atts shortcode attributes.
	 * @return mixed[] modified shortcode attributes
	 */
	public function shortcode_atts_hyphen_filter( array $atts ): array {

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
	 * @param mixed[]                  $categories           Block categories available.
	 * @param ?WP_Block_Editor_Context $block_editor_context The current block editor context.
	 * @return mixed[] the (possibly extended) block categories.
	 */
	public function wpdr_block_categories( array $categories, ?WP_Block_Editor_Context $block_editor_context = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

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
	public function documents_shortcode_blocks(): void {
		if ( ! function_exists( 'register_block_type' ) || ! self::blocks_enabled() ) {
			// Gutenberg is not active (e.g. old WP version installed), or the site turned the blocks off.
			return;
		}

		// add the plugin category.
		add_filter( 'block_categories_all', array( $this, 'wpdr_block_categories' ), 10, 2 );

		$dir       = dirname( __DIR__ );
		$build_dir = $dir . '/build/blocks/documents-shortcode';

		if ( file_exists( $build_dir . '/block.json' ) ) {
			register_block_type(
				$build_dir,
				array(
					'render_callback' => array( $this, 'wpdr_documents_shortcode_display' ),
				)
			);
		} else {
			// Fallback when build directory is not available (e.g. development/CI).
			register_block_type(
				'wp-document-revisions/documents-shortcode',
				array(
					'render_callback' => array( $this, 'wpdr_documents_shortcode_display' ),
				)
			);
		}

		$rev_build_dir = $dir . '/build/blocks/revisions-shortcode';

		if ( file_exists( $rev_build_dir . '/block.json' ) ) {
			register_block_type(
				$rev_build_dir,
				array(
					'render_callback' => array( $this, 'wpdr_revisions_shortcode_display' ),
				)
			);
		} else {
			// Fallback when build directory is not available (e.g. development/CI).
			register_block_type(
				'wp-document-revisions/revisions-shortcode',
				array(
					'render_callback' => array( $this, 'wpdr_revisions_shortcode_display' ),
				)
			);
		}

		$prev_build_dir = $dir . '/build/blocks/document-preview';

		if ( file_exists( $prev_build_dir . '/block.json' ) ) {
			register_block_type(
				$prev_build_dir,
				array(
					'render_callback' => array( $this, 'wpdr_document_preview_display' ),
				)
			);
		} else {
			// Fallback when build directory is not available (e.g. development/CI).
			register_block_type(
				'wp-document-revisions/document-preview',
				array(
					'render_callback' => array( $this, 'wpdr_document_preview_display' ),
				)
			);
		}
	}

	/**
	 * Register revisions-shortcode block
	 *
	 * @since 5.5.0
	 */
	public function documents_block_editor_data(): void {
		if ( ! is_admin() ) {
			return;
		}

		// Add supplementary script for additional information.
		// document CPT has no default taxonomies, need to look up in wp_taxonomies.
		// Ensure taxonomies are set.
		$taxonomies = $this->get_taxonomy_details();

		// Get the auto-generated script handle from the registered block.
		$registry = \WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'wp-document-revisions/documents-shortcode' );
		if ( $block && ! empty( $block->editor_script_handles ) ) {
			$handle = $block->editor_script_handles[0];
			wp_add_inline_script( $handle, 'var wpdr_data = ' . wp_json_encode( $taxonomies ), 'before' );
		}
	}

	/**
	 * Flattened taxonomy term list.
	 *
	 * @var array<int|string, mixed> $tax_terms array of terms.
	 */
	private static $tax_terms = array();


	/**
	 * Get taxonomy structure.
	 *
	 * Appends the terms to self::$tax_terms depth-first (children by name under
	 * their parent), with each term's depth stored in term_group. Loads all the
	 * taxonomy's terms with a single query.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $par_term parent term.
	 * @param int    $level    level in hierarchy.
	 * @since 3.3.0
	 */
	private function get_taxonomy_hierarchy( string $taxonomy, int $par_term = 0, int $level = 0 ): void {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $terms ) ) {
			return;
		}

		// group by parent, keeping get_terms()' name order within each parent.
		$children = array();
		foreach ( $terms as $term ) {
			$children[ (int) $term->parent ][] = $term;
		}

		$this->append_term_children( $children, $par_term, $level );
	}

	/**
	 * Depth-first walk of grouped terms into self::$tax_terms.
	 *
	 * @param array<int, WP_Term[]> $children terms grouped by parent ID.
	 * @param int                   $par_term parent term.
	 * @param int                   $level    level in hierarchy.
	 */
	private function append_term_children( array $children, int $par_term, int $level ): void {
		if ( empty( $children[ $par_term ] ) ) {
			return;
		}
		foreach ( $children[ $par_term ] as $term ) {
			// Mis-use term_group to hold level.
			$term->term_group  = $level;
			self::$tax_terms[] = $term;
			$this->append_term_children( $children, $term->term_id, $level + 1 );
		}
	}

	/**
	 * Get taxonomy names for documents (use cache).
	 *
	 * @return mixed[] Taxonomy names for documents
	 * @since 3.3.0
	 */
	public function get_taxonomy_details(): array {
		// Salt the key with the terms last_changed value so edits to terms show up at once, and
		// with the locale because the "No selection" label is translated.
		$cache_key        = 'wpdr_document_taxonomies:' . get_user_locale() . ':' . wp_cache_get_last_changed( 'terms' );
		$taxonomy_details = wp_cache_get( $cache_key );

		if ( false === $taxonomy_details ) {
			// build and create cache entry. Get name only to allow easier filtering.
			$taxos = get_object_taxonomies( 'document' );
			// Make sure 'workflow_state' is in the list if not disabled. With EF/PP it uses the post_status taxonomy.
			$tax_key = self::$parent->taxonomy_key();
			if ( ! empty( self::$parent->taxonomy_key() ) && taxonomy_exists( $tax_key ) && ! in_array( 'workflow_state', (array) $taxos, true ) ) {
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
					$tax_obj = get_taxonomy( $tax_key );
					if ( ! $tax_obj instanceof WP_Taxonomy ) {
						continue;
					}
					// EF/PP - Mis-use of 'post_status' taxonomy.
					$tax               = clone $tax_obj;
					$tax->hierarchical = false;
					$tax->label        = 'Post Status';
					$wf_efpp           = 1;
				} else {
					$tax = get_taxonomy( $taxonomy );
				}

				if ( ! $tax instanceof WP_Taxonomy ) {
					continue; // Not registered (e.g. unregistered, or bad name from the filter).
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

			// Keep a TTL: the document_block_taxonomies filter output is not covered by the salt.
			wp_cache_set( $cache_key, $taxonomy_details, '', ( WP_DEBUG ? 10 : 120 ) );
		}

		return $taxonomy_details;
	}

	/**
	 * Server side block to render the documents list.
	 *
	 * @param array<mixed> $atts shortcode attributes.
	 * @return string a UL with the revisions
	 * @since 3.3.0
	 */
	public function wpdr_documents_shortcode_display( array $atts ): string {
		// quick check.
		// do not show output to users that do not have the read_documents capability and don't get it via read.
		if ( ( ! apply_filters( 'document_read_uses_read', true ) && ! current_user_can( 'read_documents' ) ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// find the block styling.
		$wrapper = $this->get_block_attributes();
		$output  = '';

		// if header set, then output as <h2>.
		if ( isset( $atts['header'] ) ) {
			$output .= '<h2>' . esc_html( $atts['header'] ) . '</h2>';
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
		if ( ! empty( $atts['taxonomy_0'] ) && ! empty( $atts['term_0'] ) ) {
			// get likely taxonomy.
			$taxo = ( isset( $curr_taxos[0]['query'] ) && $atts['taxonomy_0'] === $curr_taxos[0]['query'] ? $curr_taxos[0]['slug'] : '' );
			// create atts in the appropriate form tax->query_var = term slug. Ensure parameter is passed as an integer.
			$term = get_term( (int) $atts['term_0'], $taxo );
			if ( $term instanceof WP_Term ) {
				$atts[ $atts['taxonomy_0'] ] = $term->slug;
			} else {
				$errs .= '<p>' . esc_html__( ' Taxonomy term does not belong to this taxonomy.', 'wp-document-revisions' ) . ' (1)</p>';
			}
		}
		unset( $atts['taxonomy_0'] );
		unset( $atts['term_0'] );

		if ( ! empty( $atts['taxonomy_1'] ) && ! empty( $atts['term_1'] ) ) {
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

		if ( ! empty( $atts['taxonomy_2'] ) && ! empty( $atts['term_2'] ) ) {
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
		if ( ! empty( $wrapper ) ) {
			$output = '<div ' . $wrapper . '>' . $output . '</div>';
		}
		return $output;
	}

	/**
	 * Whether the current user can see a document's revision list.
	 *
	 * @since 5.5.1
	 *
	 * @param int $id document ID.
	 * @return bool
	 */
	public function can_read_revisions( int $id ): bool {
		return current_user_can( 'read_document', $id ) && ! post_password_required( $id );
	}

	/**
	 * Server side block to render the revisions list.
	 *
	 * @param array<string, mixed> $atts shortcode attributes.
	 * @return string a UL with the revisions
	 * @since 3.3.0
	 */
	public function wpdr_revisions_shortcode_display( array $atts ): string {

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

		// quick check.
		// do not show output to users that do not have the read_document_revisions capability.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// Check it is a document (and not its revision or attached document) so don't use verify_post_type.
		if ( ( ! is_numeric( $atts['id'] ) ) || 'document' !== get_post_type( $atts['id'] ) ) {
			return '<p>' . esc_html__( 'This is not a valid document.', 'wp-document-revisions' ) . '</p>';
		}

		// The user must be able to read this document.
		if ( ! $this->can_read_revisions( (int) $atts['id'] ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		// Remove show_pdf if false.
		if ( ! $atts['show_pdf'] ) {
			unset( $atts['show_pdf'] );
		}

		// find the block styling.
		$wrapper = $this->get_block_attributes();

		$output  = '<h2 class="document-title document-' . esc_attr( $atts['id'] ) . '">' . esc_html( get_the_title( $atts['id'] ) ) . '</h2>';
		$output .= $this->revisions_shortcode( $atts );
		if ( ! empty( $wrapper ) ) {
			$output = '<div ' . $wrapper . '>' . $output . '</div>';
		}
		return $output;
	}

	/**
	 * Server side block/shortcode to render an inline preview of a document's latest revision.
	 *
	 * The document permalink serves the latest revision inline through the authenticated
	 * file handler (serve_file), so the browser previews it in place and access control is
	 * enforced on the actual file request regardless of this callback.
	 *
	 * @param array<string, mixed> $atts shortcode/block attributes.
	 * @return string the preview markup.
	 * @since 5.5.0
	 */
	public function wpdr_document_preview_display( array $atts ): string {
		global $wpdr;

		$atts = shortcode_atts(
			array(
				'id'            => 0,
				'height'        => 600,
				'show_title'    => false,
				'show_download' => true,
			),
			$atts,
			'document'
		);

		$id = absint( $atts['id'] );

		// Check it is a document (and not its revision or attached document).
		if ( 'document' !== get_post_type( $id ) ) {
			return '<p>' . esc_html__( 'This is not a valid document.', 'wp-document-revisions' ) . '</p>';
		}

		// Mirror serve_file()'s access decision exactly, using the same filter, so the preview
		// renders if and only if the file would actually be served. This keeps the preview in
		// step with WPDR's model (published documents are public by default, others are gated)
		// and honours any third-party auth filters. serve_file re-enforces this on the real
		// file request regardless, so access control never depends on this callback alone.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		if ( ! apply_filters( 'serve_document_auth', true, get_post( $id ), false ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this document.', 'wp-document-revisions' ) . '</p>';
		}

		$url = get_permalink( $id );
		if ( ! $url ) {
			return '<p>' . esc_html__( 'This document has no file to preview.', 'wp-document-revisions' ) . '</p>';
		}

		// get_file_type() returns the extension with a leading dot (e.g. ".pdf"); normalize it.
		$extension     = ltrim( strtolower( $wpdr->get_file_type( $id ) ), '.' );
		$height        = absint( $atts['height'] );
		$height        = ( 0 === $height ) ? 600 : $height;
		$show_title    = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );
		$show_download = filter_var( $atts['show_download'], FILTER_VALIDATE_BOOLEAN );
		$image_types   = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp' );

		$download_link = '<a href="' . esc_url( $url ) . '" class="document-download" download>' . esc_html__( 'Download document', 'wp-document-revisions' ) . '</a>';

		// find the block styling.
		$wrapper = $this->get_block_attributes();

		$output = '<div class="document-preview document-' . esc_attr( (string) $id ) . '">';

		if ( $show_title ) {
			$output .= '<h2 class="document-title">' . esc_html( get_the_title( $id ) ) . '</h2>';
		}

		if ( 'pdf' === $extension ) {
			// <object> renders the PDF inline; the nested link is the graceful fallback
			// when the browser cannot display it.
			$output .= '<object class="document-preview-object" data="' . esc_url( $url ) . '" type="application/pdf" width="100%" height="' . esc_attr( (string) $height ) . '">';
			$output .= $download_link;
			$output .= '</object>';
		} elseif ( in_array( $extension, $image_types, true ) ) {
			$output .= '<img class="document-preview-image" src="' . esc_url( $url ) . '" alt="' . esc_attr( get_the_title( $id ) ) . '" />';
		} else {
			// Non-previewable type: the served file is behind an auth gate, so external
			// viewers cannot reach it. Offer a download instead.
			$output .= '<p class="document-preview-nopreview">' . esc_html__( 'This file type cannot be previewed inline.', 'wp-document-revisions' ) . ' ' . $download_link . '</p>';
			// Avoid rendering the link twice below.
			$show_download = false;
		}

		if ( $show_download ) {
			$output .= '<p class="document-preview-download">' . $download_link . '</p>';
		}

		$output .= '</div>';

		if ( ! empty( $wrapper ) ) {
			$output = '<div ' . $wrapper . '>' . $output . '</div>';
		}

		return $output;
	}

	/**
	 * Block wrapper attributes.
	 *
	 * The rendering code may be called outside the context of a block, i.e. with a shortcode.
	 *
	 * @return string the block attributes if in context (empty for shortcodes).
	 * @since 5.5.0
	 */
	public function get_block_attributes(): string {
		// $block_to_render is set while any dynamic block renders (e.g. core/post-content running shortcodes), so check it is ours.
		$block = WP_Block_Supports::$block_to_render;
		if ( ! is_array( $block ) || 0 !== strpos( (string) ( $block['blockName'] ?? '' ), 'wp-document-revisions/' ) ) {
			return '';
		}
		return get_block_wrapper_attributes();
	}
}

