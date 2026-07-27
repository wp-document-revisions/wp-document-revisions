<?php
/**
 * WP Document Revisions Admin List Trait
 *
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
				echo '<label class="screen-reader-text" for="workflow_state">' . esc_html( $so_all ) . '</label>';
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
			// @phpstan-ignore argument.type ($args carry the custom 'wpdr_added' marker consumed by our pre_user_query filter; wp_dropdown_users() ignores keys it does not recognise)
			wp_dropdown_users( $args );
			remove_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
		}
	}

	/**
	 * Displays a first-run call to action on the documents list when no documents exist yet.
	 *
	 * Keeps the empty state actionable instead of showing a blank table, reducing the friction
	 * new users (and WordPress Playground "Live Preview" visitors) hit before their first upload.
	 *
	 * @since 5.3
	 */
	public function empty_state_notice(): void {
		$screen = get_current_screen();
		if ( is_null( $screen ) || 'edit-document' !== $screen->id ) {
			return;
		}

		// Only a genuine first-run state: skip when searching or filtering, where an empty list means "no matches" rather than "no documents".
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['s'] ) || isset( $_GET['post_status'] ) || isset( $_GET['workflow_state'] ) || isset( $_GET['author'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// No point prompting a user who cannot create documents.
		if ( ! current_user_can( 'edit_documents' ) ) {
			return;
		}

		// Bail if any documents already exist (in any status).
		$counts = array_map( 'intval', (array) wp_count_posts( 'document' ) );
		if ( array_sum( $counts ) > 0 ) {
			return;
		}

		$add_link  = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'post-new.php?post_type=document' ) ),
			esc_html__( 'Add your first document', 'wp-document-revisions' )
		);
		$docs_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://wp-document-revisions.github.io/wp-document-revisions/' ),
			esc_html__( 'read the documentation', 'wp-document-revisions' )
		);
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Welcome to WP Document Revisions!', 'wp-document-revisions' ); ?></strong>
				<?php
				printf(
					/* translators: 1: link to add a new document, 2: link to the documentation */
					esc_html__( 'You have not added any documents yet. %1$s to start tracking revisions and workflow, or %2$s.', 'wp-document-revisions' ),
					$add_link, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- anchor assembled from esc_url() and esc_html__() above.
					$docs_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- anchor assembled from esc_url() and esc_html__() above.
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Invites engaged, successful users to leave a WordPress.org review.
	 *
	 * Shown only on the documents list screen (so it never coincides with the upload/lock
	 * error notices that appear on the document edit screen) and only once the user has a
	 * real, working library. Every document save creates a revision in this plugin, so a
	 * healthy document count implies a meaningful revision history. Dismissal is remembered
	 * per user, and the prompt never nags again.
	 *
	 * @since 5.3
	 */
	public function review_prompt(): void {
		$screen = get_current_screen();
		if ( is_null( $screen ) || 'edit-document' !== $screen->id ) {
			return;
		}

		if ( ! current_user_can( 'edit_documents' ) ) {
			return;
		}

		// Never ask again once the user has responded.
		if ( get_user_meta( get_current_user_id(), self::REVIEW_DISMISSED_META, true ) ) {
			return;
		}

		// Engagement gate: only ask users with a real, successful library.
		$counts = array_map( 'intval', (array) wp_count_posts( 'document' ) );
		if ( array_sum( $counts ) < self::REVIEW_MIN_DOCS ) {
			return;
		}

		$review_url  = wp_nonce_url(
			add_query_arg(
				array(
					'wpdr_review_dismiss' => 1,
					'wpdr_review_go'      => 1,
				)
			),
			'wpdr_review_dismiss',
			'wpdr_review_nonce'
		);
		$dismiss_url = wp_nonce_url( add_query_arg( 'wpdr_review_dismiss', 1 ), 'wpdr_review_dismiss', 'wpdr_review_nonce' );
		?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'You have been managing your documents with WP Document Revisions — thank you! If it has been useful, would you consider leaving a quick review? It genuinely helps other teams discover the plugin.', 'wp-document-revisions' ); ?></p>
			<p>
				<a href="<?php echo esc_url( $review_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a review', 'wp-document-revisions' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button-link"><?php esc_html_e( 'No thanks', 'wp-document-revisions' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Records dismissal of the review prompt and, when the user chose to leave a review,
	 * forwards them to the WordPress.org review page.
	 *
	 * @since 5.3
	 */
	public function handle_review_dismissal(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wpdr_review_dismiss'] ) ) {
			return;
		}
		$nonce = isset( $_GET['wpdr_review_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['wpdr_review_nonce'] ) ) : '';
		$go    = isset( $_GET['wpdr_review_go'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! wp_verify_nonce( $nonce, 'wpdr_review_dismiss' ) ) {
			return;
		}

		update_user_meta( get_current_user_id(), self::REVIEW_DISMISSED_META, 1 );

		if ( $go ) {
			// External redirect to the review page is intentional.
			wp_redirect( 'https://wordpress.org/support/plugin/wp-document-revisions/reviews/#new-post' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		wp_safe_redirect( remove_query_arg( array( 'wpdr_review_dismiss', 'wpdr_review_nonce', 'wpdr_review_go' ) ) );
		exit;
	}

	/**
	 * Filter the user dropdown args to add additional arguments that are normally filtered out. .
	 *
	 * @since 3.6
	 * @param array<string, mixed> $query_args  The query arguments for get_users().
	 * @param array<string, mixed> $parsed_args The arguments passed to wp_dropdown_users() combined with the defaults.
	 * @return array<string, mixed> the (possibly modified) query arguments.
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
	 * @param WP_User_Query $query the WP_Query object.
	 */
	public function pre_user_query( WP_User_Query $query ): void {
		if ( current_user_can( 'read_private_documents' ) ) {
			$query->query_where = str_replace( "= 'publish'", "IN ('publish', 'private')", $query->query_where );
		}
	}

	/**
	 * Need to manipulate workflow_state into taxonomy slug for EF/PP.
	 *
	 * Only invoked if taxonomy slug needs to be changed.
	 *
	 * @param WP_Query $query the WP_Query object.
	 */
	public function convert_workflow_state_to_post_status( WP_Query $query ): void {
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
	 * @param array<string, string> $defaults the default column labels.
	 * @return array<string, string> the modified column labels
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
	 * @param array<string, string> $defaults the original columns.
	 * @return array<string, string> our spliced columns
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
