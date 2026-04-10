<?php
/**
 * WP Document Revisions Query and Document Retrieval Trait
 *
 * @package WP_Document_Revisions
 */

/**
 * Document retrieval, taxonomy, and display functionality for WP_Document_Revisions.
 */
trait WP_Document_Revisions_Query {

	/**
	 * Returns the.document attachment associated with a post.
	 *
	 * @param id $post_id ID of a post object (document or revision).
	 * @return WP_Post||false
	 */
	public function get_document( $post_id ) {
		$content   = get_post_field( 'post_content', $post_id );
		$attach_id = $this->extract_document_id( $content );
		if ( $attach_id ) {
			$attach = get_post( $attach_id );
			if ( (bool) $attach && 'attachment' === $attach->post_type ) {
				return $attach;
			}
		}
		// not a valid attachment.
		return false;
	}


	/**
	 * Returns array of document objects matching supplied criteria.
	 *
	 * See https://developer.wordpress.org/reference/classes/wp_query/ for more information on potential parameters
	 *
	 * @param ?array  $args (optional) an array of WP_Query arguments.
	 * @param boolean $return_attachments (optional).
	 * @return array an array of post objects
	 */
	public function get_documents( ?array $args = array(), bool $return_attachments = false ): array {
		$args              = (array) $args;
		$args['post_type'] = 'document';
		$args['perm']      = 'readable';
		if ( isset( $args['numberposts'] ) ) {
			// get all of them.
			$args['posts_per_page'] = $args['numberposts'];
		}

		$query  = new WP_Query( $args );
		$output = array();

		if ( $return_attachments ) {

			// loop through each document and build an array of attachment objects
			// this would be the same output as a query for post_type = attachment
			// but allows querying of document metadata and returns only latest revision.
			foreach ( $query->posts as $document ) {
				$document_object               = $this->get_latest_revision( $document->ID );
				$attachmt_object               = $this->get_document( $document_object->ID );
				$attachmt_object->post_content = $document_object->post_content;
				$output[]                      = $attachmt_object;
			}
		} else {

			// used internal get_revision function so that filter work and revision bug is offset.
			foreach ( $query->posts as $document ) {
				$output[] = $this->get_latest_revision( $document->ID );
			}
		}

		// remove empty rows, e.g., created by autodraft, etc.
		$output = array_filter( $output );

		return $output;
	}


	/**
	 * Try to retrieve only correct documents.
	 *
	 * Queries by post_status do not do proper permissions check.
	 * See https://developer.wordpress.org/reference/classes/wp_query/
	 *
	 * @since 3.3.0
	 *
	 * @param WP_Query $query  Query object.
	 */
	public function retrieve_documents( WP_Query $query ): void {
		$query_fields = (array) $query->query;
		if ( isset( $query_fields['post_type'] ) && 'document' === $query_fields['post_type'] ) {
			// not for administrator.
			$user = wp_get_current_user();
			if ( in_array( 'administrator', $user->roles, true ) ) {
				return;
			}

			// dropped through initial tests.
			if ( isset( $query_fields['post_status'] ) && ! empty( $query_fields['post_status'] ) ) {
				if ( ! isset( $query_fields['perm'] ) ) {
					// create/modify taxonomy query.
					$query->set( 'perm', 'readable' );
				}
			}
		}
	}


	/**
	 * Returns the.document id associated with a post from the content.
	 *
	 * @param ?string $post_content post_content from a post object (document or revision).
	 * @return int|false
	 */
	public function extract_document_id( ?string $post_content ) {
		if ( empty( $post_content ) ) {
			return false;
		} elseif ( is_numeric( $post_content ) ) {
			return (int) $post_content;
		} else {
			// Early return if content doesn't contain WPDR marker to avoid regex cost.
			if ( false === stripos( $post_content, 'WPDR' ) ) {
				return false;
			}
			// find document id. Might have white space from the screen upload process.
			preg_match( '/<!-- WPDR \s*(\d+) -->/i', $post_content, $id );
			if ( isset( $id[1] ) ) {
				// if a match return the id (Zero will be no document attached - WPML scenario).
				return (int) $id[1];
			}
		}
		// no document found.
		return false;
	}


	/**
	 * Checks if a given post is a document.
	 *
	 * When called with `false`, will look to other available global data to determine whether
	 * this request is for a document post type. Will *not* look to the global `$post` object.
	 *
	 * Note: We can't use the screen API because A) used on front end, and B) admin_init is too early (enqueue scripts).
	 *
	 * @param object|int|bool $documentish a post object, postID, or false.
	 * @since 0.5
	 * @return bool true if document, false if not
	 */
	public function verify_post_type( $documentish = false ): bool {
		global $wp_query;

		if ( false === $documentish ) {

			// check for post_type query arg (post new).
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['post_type'] ) && 'document' === $_GET['post_type'] ) {
				return true;
			}

			// Assume that a document feed is a document feed, even without a post object.
			if ( isset( $wp_query->query_vars ) && is_array( $wp_query->query_vars ) &&
			( array_key_exists( 'post_type', $wp_query->query_vars ) ) && 'document' === $wp_query->query_vars['post_type'] && is_feed() ) {
				return true;
			}

			// if post isn't set, try get vars (edit post).
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['post'] ) ) {
				$documentish = intval( $_GET['post'] );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			}

			// look for post_id via post or get (media upload).
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['post_id'] ) ) {
				$documentish = intval( $_REQUEST['post_id'] );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		if ( false === $documentish ) {
			return false;
		}

		$post = get_post( $documentish );

		if ( ! $post ) {
			return false;
		}

		$post_type = $post->post_type;

		// if post is really an attachment or revision, look to the post's parent.
		if ( ( 'attachment' === $post_type || 'revision' === $post_type ) && 0 !== $post->post_parent ) {
			$post_type = get_post_type( $post->post_parent );
		}

		return 'document' === $post_type;
	}


	/**
	 * Prevents Attachment ID from being displayed on front end.
	 *
	 * @since 1.0.3
	 * @param string $content the post content.
	 * @return string either the original content or none
	 */
	public function content_filter( string $content ): string {
		if ( ! $this->verify_post_type( get_post() ) ) {
			return $content;
		}

		// allow password prompt to display.
		if ( post_password_required() ) {
			return $content;
		}

		return '';
	}


	/**
	 * Adds revision number to document titles.
	 *
	 * @since 1.0
	 * @param string $title   the title.
	 * @param int    $post_id The ID of the post for which the title is being generated.
	 * @return string the title possibly with the revision number
	 */
	public function add_revision_num_to_title( string $title, ?int $post_id = null ): string {
		// If a post ID is not provided, do not attempt to filter the title.
		if ( is_null( $post_id ) ) {
			return $title;
		}

		$post = get_post( $post_id );

		// verify post type.
		if ( ! $this->verify_post_type( $post ) ) {
			return $title;
		}

		// if this is a document, and not a revision, just filter and return the title.
		if ( 'revision' !== $post->post_type ) {

			if ( is_feed() ) {
				// translators: %s is the document title.
				$title = sprintf( __( '%s - Latest Revision', 'wp-document-revisions' ), $title );
			}

			/**
			 * Filter the document title from the post.
			 *
			 * @param string $title the title retrieved from the post.
			 */
			return apply_filters( 'document_title', $title );
		}

		// get revision num.
		$revision_num = $this->get_revision_number( $post->ID );

		// if for some reason there's no revision num.
		if ( ! $revision_num ) {
			return apply_filters( 'document_title', $title );
		}

		// add title, apply filters, and return.
		// translators: %1$s is the document title, %2$d is the revision ID.
		return apply_filters( 'document_title', sprintf( __( '%1$s - Revision %2$d', 'wp-document-revisions' ), $title, $revision_num ) );
	}


	/**
	 * Extends the modified term_count_cb to all custom taxonomies associated with documents
	 * Unless taxonomy already has a custom callback.
	 *
	 * @since 1.2.1
	 */
	public function register_term_count_cb(): void {
		// This will return only taxonomies for documents only, e.g. ignore those for documents AND another.
		$taxs = get_taxonomies(
			array(
				'object_type'           => array( 'document' ),
				'update_count_callback' => '',
			),
			'names'
		);

		/**
		 * Filter to select which taxonomies with default term count to be modified to count all non-trashed posts.
		 *
		 * @param array $taxs document taxonomies .
		 */
		$taxs = apply_filters( 'document_taxonomy_term_count', $taxs );

		if ( ! empty( $taxs ) ) {
			global $wp_taxonomies;

			foreach ( $taxs as $tax ) {
					$wp_taxonomies[ $tax ]->update_count_callback = array( &$this, 'term_count_cb' );
			}
		}
	}


	/**
	 * Filters the term_count post_statuses for all custom taxonomies associated with documents
	 * Unless taxonomy already has a custom callback.
	 *
	 * @since 3.3.0
	 * @param string[]    $statuses  List of post statuses to include in the count. Default is 'publish'.
	 * @param WP_Taxonomy $taxonomy  Current taxonomy object.
	 */
	public function review_count_statuses( array $statuses, WP_Taxonomy $taxonomy ): array {
		$tax_name   = $taxonomy->name;
		$tax_status = wp_cache_get( 'wpdr_statuses_' . $tax_name );
		if ( false === $tax_status ) {
			// if filtered out, don't need to look at taxonomy. N.B. Odd format for compatibility.
			/**
			 * Filter to select which taxonomies with default term count to be modified to count all non-trashed posts.
			 *
			 * In prior versions input parameter was an array of all affected post types.
			 *
			 * @param array $taxs document taxonomies .
			 */
			if ( ! in_array( $tax_name, apply_filters( 'document_taxonomy_term_count', array( $tax_name ) ), true ) ) {
				$tax_status = $statuses;
			} elseif ( '' !== $taxonomy->update_count_callback || ! in_array( 'document', $taxonomy->object_type, true ) ) {
				// check if taxonomy has a callback defined or is not for documents.
				$tax_status = $statuses;
			} else {
				// get the list of statuses.
				$tax_status = get_post_stati();
				// trash, inherit and auto-draft to be excluded.
				unset( $tax_status['trash'] );
				unset( $tax_status['inherit'] );
				unset( $tax_status['auto-draft'] );
			}
			wp_cache_set( 'wpdr_statuses_' . $tax_name, $tax_status, '', 60 );
		}

		return $tax_status;
	}


	/**
	 * Ensures that any system limit on revisions does not apply to documents.
	 *
	 * @since 3.2.2
	 *
	 * @param int      $num  default value for the number of revisions for the post_type.
	 * @param ?WP_Post $post current post.
	 */
	public function manage_document_revisions_limit( int $num, ?WP_Post $post ): int {
		if ( ! $post || ! $this->verify_post_type( $post ) ) {
			return $num;
		}

		// Set default number as unlimited.
		$num = -1;
		/**
		 * Filters the number of revisions to keep for documents.
		 *
		 * This should normally be unlimited and setting it can make attachments unaccessible.
		 *
		 * Note particularly that Autosaves are revisions, so count towards the total.
		 *
		 * @since 3.2.2
		 *
		 * @param int -1 (unlimited).
		 */
		$num = apply_filters( 'document_revisions_limit', $num );

		return $num;
	}


	/**
	 * Returns the.document id associated with a post in a standard format.
	 *
	 * @param int $post_id ID of a post object that is an attachment.
	 * @return string
	 */
	public function format_doc_id( int $post_id ): string {
		return '<!-- WPDR ' . (string) $post_id . ' -->';
	}


	/**
	 * Gets a file extension from a post.
	 *
	 * @since 0.5
	 * @param object|int $document_or_attachment document or attachment.
	 * @return string the extension to the latest revision
	 */
	public function get_file_type( $document_or_attachment = '' ): string {
		if ( '' === $document_or_attachment ) {
			global $post;
			$document_or_attachment = $post;
		}

		if ( ! is_object( $document_or_attachment ) ) {
			$document_or_attachment = get_post( $document_or_attachment );
		}

		// note, changing $post here would break $post in the global scope
		// rename $post to attachment, or grab the attachment from $post
		// either way, $attachment is now the object we're looking to query.
		if ( 'attachment' === get_post_type( $document_or_attachment ) ) {
			$attachment = $document_or_attachment;
		} elseif ( 'document' === get_post_type( $document_or_attachment ) ) {
			$latest_revision = $this->get_latest_revision( $document_or_attachment->ID );

			// verify a previous revision exists.
			if ( ! $latest_revision ) {
				return '';
			}

			$attachment = $this->get_document( $latest_revision );

			// sanity check in case post_content somehow doesn't represent an attachment,
			// or in case some sort of non-document, non-attachment object/ID was passed.
			if ( ! $attachment ) {
				return '';
			}
		}

		// no need to get the correct directory as we just want the extension.
		return $this->get_extension( get_attached_file( $attachment->ID ) );
	}


	/**
	 * Dynamically sets the Featured Image Size for Documents when not set by theme.
	 *
	 * @since 3.7
	 * @param string|int[] $size    Requested image size. Can be any registered image size name, or
	 *                              an array of width and height values in pixels (in that order).
	 * @param int          $post_id The post ID.
	 */
	public function document_featured_image_size( $size, int $post_id ) {
		if ( 'post-thumbnail' !== $size || ! $this->verify_post_type( $post_id ) ) {
			return $size;
		}

		$size = array(
			get_option( 'thumbnail_size_w' ),
			get_option( 'thumbnail_size_h' ),
		);

		/**
		 * Filters the post-thumbnail size parameters (used only if this image size has not been set).
		 *
		 * @since 3.6
		 *
		 * @param mixed[] $size default values for the image size.
		 */
		return apply_filters( 'document_post_thumbnail', $size );
	}

	/**
	 * Term Count Callback that applies custom filter
	 * Allows Workflow State counts to include non-published posts.
	 *
	 * @since 1.2.1
	 * @param Array  $terms the terms to filter.
	 * @param Object $taxonomy the taxonomy object.
	 */
	public function term_count_cb( array $terms, object $taxonomy ): void {
		add_filter( 'query', array( &$this, 'term_count_query_filter' ) );
		_update_post_term_count( $terms, $taxonomy );
		remove_filter( 'query', array( &$this, 'term_count_query_filter' ) );
	}

	/**
	 * Alters term count query to include all non-trashed posts.
	 * See generally, #17548
	 *
	 * @since 1.2.1
	 * @param string $query the query string.
	 * @return string the modified query
	 */
	public function term_count_query_filter( string $query ): string {
		return str_replace( "= 'publish'", "!= 'trash'", $query );
	}

	/**
	 * Return an empty excerpt for documents on front end views to avoid leaking
	 * revision notes (except if the user could see them by editting the post).
	 *
	 * @since 3.3.0
	 * @param string  $excerpt The original excerpt text associated with a post.
	 * @param WP_Post $post    The post object.
	 *
	 * @return string
	 */
	public function empty_excerpt_return( string $excerpt, WP_Post $post ): string {
		if ( '' === $excerpt || ! $this->verify_post_type( $post ) ) {
			return $excerpt;
		}

		// suppress only if user could not edit the (parent) post and see it.
		$post_test = ( 0 === $post->post_parent ? $post->ID : $post->post_parent );
		if ( ! current_user_can( 'edit_document', $post_test ) ) {
			return '';
		}

		return $excerpt;
	}

	/**
	 * Filters the WHERE clause in the SQL for an adjacent post query.
	 *
	 * Add 1=0 test to WHERE clause for documents for a single page.
	 *
	 * @since 3.3.0
	 *
	 * @param string  $where          The `WHERE` clause in the SQL.
	 * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
	 * @param array   $excluded_terms Array of excluded term IDs.
	 * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
	 * @param WP_Post $post           WP_Post object.
	 *
	 * @return string
	 */
	public function suppress_adjacent_doc( string $where, bool $in_same_term, array $excluded_terms, string $taxonomy, WP_Post $post ): string {
		if ( ! $this->verify_post_type( $post ) ) {
			return $where;
		}

		// Leakage arises on queries on a single page.
		if ( ! is_singular() ) {
			return $where;
		}

		return $where . ' AND 1 = 0 ';
	}

	/**
	 * Review WP_Query SQL results.
	 *
	 * Only invoked when user should NOT access documents via 'read' but does not have 'read_documents'. Remove any documents.
	 *
	 * @param WP_Post[] $results      Array of post objects.
	 * @param WP_Query  $query_object Query object.
	 * @return WP_Post[] Array of post objects.
	 */
	public function posts_results( array $results, WP_Query $query_object ): array {
		$match = false;
		if ( is_array( $results ) ) {
			foreach ( $results as $key => $result ) {
				// confirm a document.
				if ( $this->verify_post_type( $result ) ) {
					// user has no access, remove from result.
					unset( $results[ $key ] );
					$match = true;
				}
			}
		}
		// re-evaluate count.
		if ( $match ) {
			// reindex array.
			$results = array_values( $results );

			if ( is_array( $results ) ) {
				$query_object->post_count  = count( $results );
				$query_object->found_posts = $query_object->post_count;
				$query_object->is_404      = (bool) ( 0 === $query_object->post_count );
			} elseif ( null === $results ) {
				$query_object->post_count  = 0;
				$query_object->found_posts = 0;
				$query_object->is_404      = true;
			} else {
				$query_object->found_posts = 1;
			}
		}

		return $results;
	}

	/**
	 * Removes Private or Protected from document titles in RSS feeds.
	 *
	 * @since 1.0
	 * @param string $prepend the sprintf formatted string to prepend to the title.
	 * @return string just the string
	 */
	public function no_title_prepend( string $prepend ): string {
		global $post;

		if ( ! isset( $post->ID ) || ! $this->verify_post_type( $post ) ) {
			return $prepend;
		}

		return '%s';
	}

	/**
	 * Adds EditFlow / PublishPress Status support for post status to the admin table.
	 *
	 * @since 3.3.0
	 * @param array $defaults the column chosen of the all documents list.
	 * @return array the updated column list.
	 */
	public function add_post_status_column( array $defaults ): array {
		// find place to slice (after author).
		$author_col = 0;
		foreach ( $defaults as $key => $dflt ) {
			++$author_col;
			if ( 'author' === $key ) {
				break;
			}
		}

		// get checkbox and title.
		$output = array_slice( $defaults, 0, $author_col );

		// splice in workflow state.
		$output['status'] = __( 'Status', 'wp-document-revisions' );

		// get the rest of the columns.
		$output = array_merge( $output, array_slice( $defaults, $author_col ) );

		return $output;
	}

	/**
	 * Adds EditFlow / PublishPress Status support for post status to the admin table (when using Custom statuses).
	 *
	 * @since 3.3.0
	 * @param string $column_name the column name of the all documents list to be populated.
	 * @param string $post_id     the post id of the all documents list to be populated.
	 */
	public function post_status_column_cb( string $column_name, $post_id ): void {

		// verify column.
		if ( 'status' !== $column_name || ! $this->verify_post_type( $post_id ) ) {
			return;
		}

		$wp_status = array(
			'publish' => __( 'Published', 'wp-document-revisions' ),
			'draft'   => __( 'Draft', 'wp-document-revisions' ),
			'future'  => __( 'Scheduled', 'wp-document-revisions' ),
			'private' => __( 'Private', 'wp-document-revisions' ),
			'pending' => __( 'Pending Review', 'wp-document-revisions' ),
			'trash'   => __( 'Trash', 'wp-document-revisions' ),
		);

		$post   = get_post( $post_id );
		$status = $post->post_status;
		// Try builtin first.
		if ( array_key_exists( $status, $wp_status ) ) {
			echo esc_html( $wp_status[ $status ] );
			return;
		}

		// Try to see if EF Custom Status.
		$res = get_term_by( 'slug', $status, self::$taxonomy_key_val );
		if ( ! $res ) {
			// not found.
			echo esc_html( $status );
		} else {
			echo esc_html( $res->name );
		}
	}
}
