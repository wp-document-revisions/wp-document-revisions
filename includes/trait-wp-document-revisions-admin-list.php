<?php
/**
 * WP Document Revisions Admin List Trait
 *
 * @package WP_Document_Revisions
 */

/**
 * Admin list functionality for WP_Document_Revisions_Admin.
 */
trait WP_Document_Revisions_Admin_List {

	/**
	 * Allow some filtering of the All Documents list.
	 */
	public function filter_documents_list(): void {
		global $typenow;
		// Only applies to document post type.
		if ( 'document' === $typenow ) {
			$tax_slug = self::$parent->taxonomy_key();
			if ( ! empty( $tax_slug ) ) {
				// Filter by workflow state/edit flow/publishpress state.
				// Note that the name is always workflow state as using post_status will invoke default status handling.
				// However it may be different on coming back.
				$so_all = __( 'All workflow states', 'wp-document-revisions' );
				if ( 'workflow_state' !== $tax_slug ) {
					$so_all = __( 'All statuses', 'wp-document-revisions' );
				}
				$args = array(
					'name'            => 'workflow_state',
					'show_option_all' => $so_all,
					'taxonomy'        => $tax_slug,
					'hide_empty'      => false,
					'value_field'     => 'slug',
					'selected'        => filter_input( INPUT_GET, 'workflow_state', FILTER_SANITIZE_SPECIAL_CHARS ),
				);
				wp_dropdown_categories( $args );
			}

			// Add (and later remove) the action to get only document authors.
			if ( current_user_can( 'read_private_documents' ) ) {
				add_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
			}
			// author/owner filtering.
			$args = array(
				'name'                => 'author',
				'show_option_all'     => __( 'All owners', 'wp-document-revisions' ),
				'value_field'         => 'slug',
				'selected'            => filter_input( INPUT_GET, 'author', FILTER_SANITIZE_SPECIAL_CHARS ),
				'orderby'             => 'name',
				'order'               => 'ASC',
				'wpdr_added'          => 'list',
				'has_published_posts' => array( 'document' ),
			);
			wp_dropdown_users( $args );
			remove_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
		}
	}

	/**
	 * Filter the user dropdown args to add additional arguments that are normally filtered out. .
	 *
	 * @since 3.6
	 * @param array $query_args  The query arguments for get_users().
	 * @param array $parsed_args The arguments passed to wp_dropdown_users() combined with the defaults.
	 */
	public function filter_user_dropdown( array $query_args, array $parsed_args ) {
		if ( array_key_exists( 'wpdr_added', $parsed_args ) ) {
			if ( 'list' === $parsed_args['wpdr_added'] ) {
				$query_args['has_published_posts'] = $parsed_args['has_published_posts'];
			}
		}
		return $query_args;
	}

	/**
	 * If the user can read Private documents, then include private in the selection.
	 *
	 * @since 3.6
	 * @param Object $query the WP_Query object.
	 */
	public function pre_user_query( object $query ): void {
		if ( current_user_can( 'read_private_documents' ) ) {
			$query->query_where = str_replace( "= 'publish'", "IN ('publish', 'private')", $query->query_where );
		}
	}

	/**
	 * Need to manipulate workflow_state into taxonomy slug for EF/PP.
	 *
	 * Only invoked if taxonomy slug needs to be changed.
	 *
	 * @param Object $query the WP_Query object.
	 */
	public function convert_workflow_state_to_post_status( object $query ): void {
		global $pagenow, $typenow;
		if ( 'edit.php' === $pagenow && 'document' === $typenow ) {
			if ( 'workflow_state' !== self::$parent->taxonomy_key() && array_key_exists( 'workflow_state', $query->query_vars ) ) {
				// parameter sent using 'workflow_state', look up with the appropriate taxonomy key.
				$query->query_vars[ self::$parent->taxonomy_key() ] = $query->query_vars['workflow_state'];
			}
		}
	}

	/**
	 * Renames author column on document list to "owner".
	 *
	 * @since 1.0.4
	 * @param array $defaults the default column labels.
	 * @return array the modified column labels
	 */
	public function rename_author_column( array $defaults ): array {
		if ( isset( $defaults['author'] ) ) {
			$defaults['author'] = __( 'Owner', 'wp-document-revisions' );
		}

		return $defaults;
	}

	/**
	 * Splices in Currently Editing column to document list.
	 *
	 * @since 1.1
	 * @param array $defaults the original columns.
	 * @return array our spliced columns
	 */
	public function add_currently_editing_column( array $defaults ): array {
		// get checkbox and title.
		$output = array_slice( $defaults, 0, 2 );

		// splice in workflow state.
		$output['currently_editing'] = __( 'Currently Editing', 'wp-document-revisions' );

		// get the rest of the columns.
		$output = array_merge( $output, array_slice( $defaults, 2 ) );

		return $output;
	}

	/**
	 * Callback to output data for currently editing column.
	 *
	 * @since 1.1
	 * @param string $column_name the name of the column being propegated.
	 * @param int    $post_id the ID of the post being displayed.
	 */
	public function currently_editing_column_cb( string $column_name, int $post_id ): void {
		// verify column.
		if ( 'currently_editing' === $column_name && $this->verify_post_type( $post_id ) ) {

			// output will be display name, if any.
			$lock = $this->get_document_lock( $post_id );
			if ( $lock ) {
				echo esc_html( $lock );
			}
		}
	}
}
