<?php
/**
 * WP Document Revisions Document Library block and shortcode.
 *
 * @package WP_Document_Revisions
 */

/**
 * Lists documents as a list, table or grid with a choice of fields.
 *
 * The query goes through WP_Document_Revisions::get_documents(), so the
 * library shows the same documents as the [documents] shortcode for a viewer.
 *
 * @since 5.8.0
 */
class WP_Document_Revisions_Document_Library {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	const BLOCK = 'wp-document-revisions/document-library';

	/**
	 * Available variants.
	 *
	 * @var string[]
	 */
	const VARIANTS = array( 'list', 'table', 'grid' );

	/**
	 * Available fields, in display order.
	 *
	 * @var string[]
	 */
	const FIELDS = array( 'thumbnail', 'title', 'description', 'file_type', 'workflow_state', 'author', 'modified', 'revisions', 'download' );

	/**
	 * Fields shown when none are chosen.
	 *
	 * @var string[]
	 */
	const DEFAULT_FIELDS = array( 'title', 'file_type', 'author', 'modified', 'download' );

	/**
	 * Fields grouped on one line in the list and grid layouts.
	 *
	 * @var string[]
	 */
	const META_FIELDS = array( 'file_type', 'workflow_state', 'author', 'modified', 'revisions' );

	/**
	 * Allowed orderby values.
	 *
	 * @var string[]
	 */
	const ORDERBY = array( 'modified', 'date', 'title', 'menu_order' );

	/**
	 * Most documents one library shows.
	 *
	 * @var int
	 */
	const MAX_DOCUMENTS = 100;

	/**
	 * Whether the hooks have been added.
	 *
	 * @var bool
	 */
	private static $hooked = false;

	/**
	 * Registers the shortcode and block.
	 */
	public function __construct() {
		// WP_Document_Revisions can be constructed more than once; only hook once.
		if ( self::$hooked ) {
			return;
		}
		self::$hooked = true;

		add_shortcode( 'document_library', array( $this, 'shortcode' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	/**
	 * Registers the block from its build directory.
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) || ! WP_Document_Revisions_Front_End::blocks_enabled() ) {
			return;
		}

		$build_dir = dirname( __DIR__ ) . '/build/blocks/document-library';
		$args      = array( 'render_callback' => array( $this, 'render_block' ) );

		if ( file_exists( $build_dir . '/block.json' ) ) {
			register_block_type( $build_dir, $args );
		} else {
			// Fallback when build directory is not available (e.g. development/CI).
			register_block_type( self::BLOCK, $args );
		}
	}

	/**
	 * Gives the block editor the document taxonomies and their terms.
	 */
	public function editor_data(): void {
		global $wpdr_fe;
		if ( ! is_admin() || ! $wpdr_fe instanceof WP_Document_Revisions_Front_End ) {
			return;
		}

		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK );
		if ( ! $block || empty( $block->editor_script_handles ) ) {
			return;
		}

		$details = $wpdr_fe->get_taxonomy_details();
		$taxos   = array();
		foreach ( (array) ( $details['taxos'] ?? array() ) as $taxo ) {
			// The EF/PP post_status taxonomy filters by post status, which the library doesn't support yet.
			if ( ! empty( $details['wf_efpp'] ) && 'workflow_state' !== $taxo['slug'] && WP_Document_Revisions::taxonomy_key() === $taxo['slug'] ) {
				continue;
			}
			$terms = array();
			foreach ( $taxo['terms'] as $term ) {
				// Skip the "No selection" entry.
				if ( 0 !== (int) $term[0] ) {
					$terms[] = array(
						'id'   => (int) $term[0],
						'name' => trim( (string) $term[1] ),
						'slug' => (string) ( $term[2] ?? '' ),
					);
				}
			}
			$taxos[] = array(
				'slug'  => $taxo['slug'],
				'label' => $taxo['label'],
				'terms' => $terms,
			);
		}

		wp_add_inline_script( $block->editor_script_handles[0], 'var wpdr_library_data = ' . wp_json_encode( array( 'taxonomies' => $taxos ) ) . ';', 'before' );
	}

	/**
	 * Block render callback.
	 *
	 * @param array<string, mixed> $attributes block attributes.
	 * @return string
	 */
	public function render_block( $attributes ): string {
		return $this->render( $this->normalize( (array) $attributes ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * Taxonomies are filtered with their name (or query var) as the key and a
	 * comma-separated list of term IDs or slugs, e.g. [document_library category="4,9"].
	 *
	 * @param array<string, mixed>|string $atts shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ): string {
		$atts       = (array) $atts;
		$taxonomies = array();
		foreach ( $atts as $key => $value ) {
			$taxonomy = $this->document_taxonomy( (string) $key );
			if ( $taxonomy ) {
				$taxonomies[ $taxonomy ] = $value;
				unset( $atts[ $key ] );
			}
		}
		$atts['taxonomies'] = $taxonomies;

		// The block's style is only enqueued automatically when the block renders.
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK );
		if ( $block && ! empty( $block->style_handles ) ) {
			wp_enqueue_style( $block->style_handles[0] );
		}

		return $this->render( $this->normalize( $atts ) );
	}

	/**
	 * Turns block attributes or shortcode attributes into validated settings.
	 *
	 * @param array<string, mixed> $atts raw attributes.
	 * @return array{variant: string, columns: int, fields: string[], taxonomies: array<string, array<int|string>>, orderby: string, order: string, numberposts: int, new_tab: bool}
	 */
	public function normalize( array $atts ): array {
		$variant = isset( $atts['variant'] ) ? (string) $atts['variant'] : 'list';
		$variant = in_array( $variant, self::VARIANTS, true ) ? $variant : 'list';

		$fields = $atts['fields'] ?? self::DEFAULT_FIELDS;
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		$fields = array_map( 'trim', array_map( 'strval', (array) $fields ) );
		// Keep the display order fixed whatever order the fields were given in.
		$fields = array_values( array_intersect( self::FIELDS, $fields ) );
		if ( ! $fields ) {
			$fields = self::DEFAULT_FIELDS;
		}

		$taxonomies = array();
		foreach ( (array) ( $atts['taxonomies'] ?? array() ) as $taxonomy => $terms ) {
			$taxonomy = $this->document_taxonomy( (string) $taxonomy );
			if ( ! $taxonomy ) {
				continue;
			}
			if ( is_string( $terms ) ) {
				$terms = explode( ',', $terms );
			}
			$terms = array_filter(
				array_map( 'trim', array_map( 'strval', (array) $terms ) ),
				static function ( string $term ): bool {
					return '' !== $term;
				}
			);
			if ( $terms ) {
				$taxonomies[ $taxonomy ] = array_values( $terms );
			}
		}

		$orderby = isset( $atts['orderby'] ) ? (string) $atts['orderby'] : 'modified';
		$order   = isset( $atts['order'] ) ? strtoupper( (string) $atts['order'] ) : 'DESC';

		$new_tab = $atts['new_tab'] ?? false;

		return array(
			'variant'     => $variant,
			'columns'     => min( 6, max( 1, (int) ( $atts['columns'] ?? 3 ) ) ),
			'fields'      => $fields,
			'taxonomies'  => $taxonomies,
			'orderby'     => in_array( $orderby, self::ORDERBY, true ) ? $orderby : 'modified',
			'order'       => 'ASC' === $order ? 'ASC' : 'DESC',
			'numberposts' => min( self::MAX_DOCUMENTS, max( 1, (int) ( $atts['numberposts'] ?? 10 ) ) ),
			'new_tab'     => is_bool( $new_tab ) ? $new_tab : in_array( strtolower( (string) $new_tab ), array( '1', 'true', 'yes' ), true ),
		);
	}

	/**
	 * Resolves a taxonomy name, query var or underscored name to a document taxonomy.
	 *
	 * @param string $key taxonomy name, query var, or name with hyphens written as underscores.
	 * @return string the taxonomy name, or '' when it isn't a document taxonomy.
	 */
	private function document_taxonomy( string $key ): string {
		$taxonomies = get_object_taxonomies( 'document', 'objects' );
		foreach ( $taxonomies as $taxonomy ) {
			if ( in_array( $key, array( $taxonomy->name, str_replace( '-', '_', $taxonomy->name ), (string) $taxonomy->query_var ), true ) ) {
				return $taxonomy->name;
			}
		}
		return '';
	}

	/**
	 * Renders the library.
	 *
	 * @param array{variant: string, columns: int, fields: string[], taxonomies: array<string, array<int|string>>, orderby: string, order: string, numberposts: int, new_tab: bool} $settings normalized settings.
	 * @return string
	 */
	public function render( array $settings ): string {
		// Same gate as the documents list block.
		if ( ! apply_filters( 'document_read_uses_read', true ) && ! current_user_can( 'read_documents' ) ) {
			return '<p>' . esc_html__( 'You are not authorized to read this data', 'wp-document-revisions' ) . '</p>';
		}

		global $wpdr;
		$documents = $wpdr->get_documents( $this->query_args( $settings ) );

		$variant = $settings['variant'];
		$fields  = $settings['fields'];
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			$fields = array_values( array_diff( $fields, array( 'revisions' ) ) );
		}

		$rows = array();
		foreach ( $documents as $document ) {
			$cells = array();
			foreach ( $fields as $field ) {
				$cells[ $field ] = $this->field( $field, $document, $settings );
			}

			/**
			 * Filters the HTML of each field shown for a document in the document library.
			 *
			 * Return an empty string for a field to leave it out of that document's row.
			 *
			 * @since 5.8.0
			 *
			 * @param array<string, string> $cells    field HTML keyed by field name, in display order.
			 * @param WP_Post               $document the document.
			 * @param string                $variant  list, table or grid.
			 */
			$rows[] = (array) apply_filters( 'document_library_row', $cells, $document, $variant );
		}

		$classes = 'wpdr-library wpdr-library--' . $variant;
		$extra   = array( 'class' => $classes );
		if ( 'grid' === $variant ) {
			$extra['style'] = '--wpdr-library-columns:' . $settings['columns'] . ';';
		}

		if ( ! $rows ) {
			$inner = '<p class="wpdr-library__empty">' . esc_html__( 'No documents found.', 'wp-document-revisions' ) . '</p>';
		} elseif ( 'table' === $variant ) {
			$inner = $this->table( $fields, $rows );
		} else {
			$inner = $this->items( $rows );
		}

		return '<div ' . $this->wrapper_attributes( $extra ) . '>' . $inner . '</div>';
	}

	/**
	 * Builds the get_documents() query.
	 *
	 * @param array{variant: string, columns: int, fields: string[], taxonomies: array<string, array<int|string>>, orderby: string, order: string, numberposts: int, new_tab: bool} $settings normalized settings.
	 * @return array<string, mixed>
	 */
	private function query_args( array $settings ): array {
		$args = array(
			'orderby'     => $settings['orderby'],
			'order'       => $settings['order'],
			'numberposts' => $settings['numberposts'],
		);

		$tax_query = array();
		foreach ( $settings['taxonomies'] as $taxonomy => $terms ) {
			$numeric     = count( array_filter( $terms, 'is_numeric' ) ) === count( $terms );
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => $numeric ? 'term_id' : 'slug',
				'terms'    => $numeric ? array_map( 'intval', $terms ) : $terms,
			);
		}
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		/**
		 * Filters the query arguments of the document library.
		 *
		 * The arguments go to WP_Document_Revisions::get_documents(), which still
		 * removes documents the viewer can't read.
		 *
		 * @since 5.8.0
		 *
		 * @param array<string, mixed> $args     query arguments.
		 * @param array<string, mixed> $settings normalized block or shortcode settings.
		 */
		return (array) apply_filters( 'document_library_query_args', $args, $settings );
	}

	/**
	 * HTML for one field of a document.
	 *
	 * @param string                                                                                                                                                                $field    field name.
	 * @param WP_Post                                                                                                                                                               $document the document.
	 * @param array{variant: string, columns: int, fields: string[], taxonomies: array<string, array<int|string>>, orderby: string, order: string, numberposts: int, new_tab: bool} $settings normalized settings.
	 * @return string
	 */
	private function field( string $field, WP_Post $document, array $settings ): string {
		global $wpdr, $wpdr_fe;

		$table     = 'table' === $settings['variant'];
		$id        = $document->ID;
		$title     = get_the_title( $id );
		$permalink = (string) get_permalink( $id );
		// Password-protected documents don't show their thumbnail or description.
		$protected = post_password_required( $id );

		switch ( $field ) {
			case 'title':
				$target = $settings['new_tab'] ? ' target="_blank" rel="noopener"' : '';
				return '<a class="wpdr-library__title-link" href="' . esc_url( $permalink ) . '"' . $target . '>' . esc_html( $title ) . '</a>';

			case 'description':
				// is_numeric is the old format. The attachment marker is an HTML comment, removed with the tags.
				if ( $protected || is_numeric( $document->post_content ) ) {
					return '';
				}
				return esc_html( wp_trim_words( wp_strip_all_tags( $document->post_content ), 30 ) );

			case 'thumbnail':
				if ( $protected || ! $wpdr_fe instanceof WP_Document_Revisions_Front_End ) {
					return '';
				}
				$image = $wpdr_fe->document_thumbnail( $document, $permalink );
				// Leave out the "No thumbnail available" comment.
				return 0 === strpos( $image, '<!--' ) ? '' : $image;

			case 'file_type':
				$type = strtoupper( ltrim( $wpdr->get_file_type( $document ), '.' ) );
				return '' === $type ? '' : '<span class="wpdr-library__badge">' . esc_html( $type ) . '</span>';

			case 'workflow_state':
				$taxonomy = WP_Document_Revisions::taxonomy_key();
				if ( '' === $taxonomy ) {
					return '';
				}
				// EF/PP keep the state in the post's own status, not in post_status terms.
				if ( 'workflow_state' !== $taxonomy ) {
					$status = get_post_status_object( (string) get_post_status( $document ) );
					return $status ? '<span class="wpdr-library__state">' . esc_html( (string) $status->label ) . '</span>' : '';
				}
				if ( ! taxonomy_exists( $taxonomy ) ) {
					return '';
				}
				$terms = get_the_terms( $id, $taxonomy );
				if ( ! is_array( $terms ) || ! $terms ) {
					return '';
				}
				return '<span class="wpdr-library__state">' . esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ) . '</span>';

			case 'author':
				$name = get_the_author_meta( 'display_name', (int) $document->post_author );
				if ( '' === $name ) {
					return '';
				}
				/* translators: %s: document author's name. */
				return esc_html( $table ? $name : sprintf( __( 'By %s', 'wp-document-revisions' ), $name ) );

			case 'modified':
				$time = '<time datetime="' . esc_attr( (string) get_post_modified_time( 'c', true, $id ) ) . '">' . esc_html( (string) get_the_modified_date( '', $id ) ) . '</time>';
				/* translators: %s: date the document was last modified. */
				return $table ? $time : sprintf( esc_html__( 'Updated %s', 'wp-document-revisions' ), $time );

			case 'revisions':
				// get_revisions() includes the document itself, which duplicates its latest revision.
				$count = max( 1, count( $wpdr->get_revisions( $id ) ) - 1 );
				if ( $table ) {
					return esc_html( number_format_i18n( $count ) );
				}
				/* translators: %s: number of revisions. */
				return esc_html( sprintf( _n( '%s revision', '%s revisions', $count, 'wp-document-revisions' ), number_format_i18n( $count ) ) );

			case 'download':
				// The same check serve_file() makes before sending the file.
				if ( ! apply_filters( 'serve_document_auth', true, $document, false ) ) {
					return '';
				}
				/* translators: %s: document title. */
				$label = sprintf( __( 'Download %s', 'wp-document-revisions' ), $title );
				return '<a class="wpdr-library__download wp-element-button" href="' . esc_url( $permalink ) . '" download aria-label="' . esc_attr( $label ) . '">' . esc_html__( 'Download', 'wp-document-revisions' ) . '</a>';
		}

		return '';
	}

	/**
	 * Table layout.
	 *
	 * @param string[]                 $fields fields shown.
	 * @param array<int, array<mixed>> $rows   field HTML for each document.
	 * @return string
	 */
	private function table( array $fields, array $rows ): string {
		$labels = $this->labels();
		$html   = '<div class="wpdr-library__scroll"><table class="wpdr-library__table"><thead><tr>';
		foreach ( $fields as $field ) {
			$html .= '<th scope="col" class="wpdr-library__col--' . esc_attr( $field ) . '">' . esc_html( $labels[ $field ] ?? $field ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $rows as $cells ) {
			$html .= '<tr>';
			foreach ( $fields as $field ) {
				// The first column labels the row for screen readers.
				$tag   = 'title' === $field ? 'th scope="row"' : 'td';
				$html .= '<' . $tag . ' class="wpdr-library__col--' . esc_attr( $field ) . '">' . ( $cells[ $field ] ?? '' ) . '</' . strtok( $tag, ' ' ) . '>';
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table></div>';
	}

	/**
	 * List and grid layouts.
	 *
	 * @param array<int, array<mixed>> $rows field HTML for each document.
	 * @return string
	 */
	private function items( array $rows ): string {
		$html = '<ul class="wpdr-library__items">';
		foreach ( $rows as $cells ) {
			$meta  = array();
			$html .= '<li class="wpdr-library__item">';
			foreach ( $cells as $field => $cell ) {
				if ( '' === $cell ) {
					continue;
				}
				if ( in_array( $field, self::META_FIELDS, true ) ) {
					$meta[] = '<span class="wpdr-library__' . esc_attr( $field ) . '">' . $cell . '</span>';
					continue;
				}
				// Download goes last, after the meta line.
				if ( 'download' === $field ) {
					continue;
				}
				$tag   = 'description' === $field ? 'p' : 'div';
				$html .= '<' . $tag . ' class="wpdr-library__' . esc_attr( (string) $field ) . '">' . $cell . '</' . $tag . '>';
			}
			if ( $meta ) {
				$html .= '<p class="wpdr-library__meta">' . implode( ' ', $meta ) . '</p>';
			}
			if ( ! empty( $cells['download'] ) ) {
				$html .= '<div class="wpdr-library__actions">' . $cells['download'] . '</div>';
			}
			$html .= '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Table column headings.
	 *
	 * @return array<string, string>
	 */
	private function labels(): array {
		return array(
			'thumbnail'      => __( 'Thumbnail', 'wp-document-revisions' ),
			'title'          => __( 'Title', 'wp-document-revisions' ),
			'description'    => __( 'Description', 'wp-document-revisions' ),
			'file_type'      => __( 'Type', 'wp-document-revisions' ),
			'workflow_state' => __( 'Workflow State', 'wp-document-revisions' ),
			'author'         => __( 'Author', 'wp-document-revisions' ),
			'modified'       => __( 'Last Modified', 'wp-document-revisions' ),
			'revisions'      => __( 'Revisions', 'wp-document-revisions' ),
			'download'       => __( 'Download', 'wp-document-revisions' ),
		);
	}

	/**
	 * Wrapper attributes, with block supports when rendering this block.
	 *
	 * @param array<string, string> $extra extra attributes.
	 * @return string
	 */
	private function wrapper_attributes( array $extra ): string {
		// $block_to_render is also set while core/post-content runs shortcodes, so check it is ours.
		$block = WP_Block_Supports::$block_to_render;
		if ( is_array( $block ) && self::BLOCK === ( $block['blockName'] ?? '' ) ) {
			return get_block_wrapper_attributes( $extra );
		}

		$html = '';
		foreach ( $extra as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		return ltrim( $html );
	}
}
