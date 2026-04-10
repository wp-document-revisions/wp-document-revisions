<?php
/**
 * WP Document Revisions Revisions Trait
 *
 * @package WP_Document_Revisions
 */

/**
 * Revision data, caching, and lock management functionality for WP_Document_Revisions.
 */
trait WP_Document_Revisions_Revisions {

	/**
	 * Retrieves all revisions for a given post (including the current post)
	 * Workaround for #16215 to ensure revision author is accurate
	 * http://core.trac.wordpress.org/ticket/16215.
	 * Workaround removed as a) 16215 fixed 6 years ago and b) gives erroneous results
	 *
	 * @since 1.0
	 * @param ?int $post_id the post ID.
	 * @return array array of post objects
	 */
	public function get_revisions( ?int $post_id ) {
		$document = get_post( $post_id );

		if ( ! $document || 'document' !== $document->post_type ) {
			return false;
		}

		$cache = wp_cache_get( $post_id, 'document_revisions' );
		if ( $cache ) {
			return $cache;
		}

		// Before post is saved GMT fields are zero.
		if ( '0000-00-00 00:00:00' === $document->post_modified_gmt ) {
			$document->post_modified_gmt = current_time( 'mysql', 1 );
		}
		// correct the modified date.
		$document->post_date = gmdate( 'Y-m-d H:i:s', (int) get_post_modified_time( 'U', null, $post_id ) );

		// fix for Quotes in the most recent post because it comes from get_post.
		$document->post_excerpt = html_entity_decode( $document->post_excerpt );

		// get revisions, remove autosaves, and prepend the post.
		$get_revs = wp_get_post_revisions(
			$post_id,
			array(
				'order'            => 'DESC',
				'suppress_filters' => true,   // try to avoid 'perm' overrides.
			)
		);

		$revs     = array();
		$post_rev = $post_id . '-autosave-v1';
		foreach ( $get_revs as $id => &$get_rev ) {
			if ( $get_rev->post_name !== $post_rev ) {
				// not an autosave.
				$revs[ $id ] = $get_rev;
			}
		}
		array_unshift( $revs, $document );

		wp_cache_set( $post_id, $revs, 'document_revisions' );

		return $revs;
	}


	/**
	 * Returns a modified WP Query object of a document and its revisions
	 * Corrects the authors bug.
	 *
	 * @since 1.0.4
	 * @param int  $post_id the ID of the document.
	 * @param bool $feed (optional) whether this is a feed.
	 * @return obj|bool the WP_Query object, false on failure
	 */
	public function get_revision_query( int $post_id, bool $feed = false ) {
		$posts = $this->get_revisions( $post_id );

		if ( ! $posts ) {
			return false;
		}

		// suppress revisions if user cannot read them to keep just the document.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			$posts = array_slice( $posts, 0, 1, true );
		}

		$rev_query              = new WP_Query();
		$rev_query->posts       = $posts;
		$rev_query->post_count  = count( $posts );
		$rev_query->found_posts = $rev_query->post_count;
		$rev_query->is_404      = (bool) ( 0 === $rev_query->post_count );
		$rev_query->post        = ( $rev_query->is_404 ? null : $posts[0] );
		$rev_query->is_feed     = $feed;
		$rev_query->query_vars  = array(
			'cache_results' => false,
			'fields'        => 'all',
		);
		$rev_query->rewind_posts();

		return $rev_query;
	}


	/**
	 * Given a post ID, returns the latest revision attachment.
	 *
	 * @param Int $post_id the post id.
	 * @return object latest revision object
	 */
	public function get_latest_revision( $post_id ) {
		if ( is_object( $post_id ) ) {
			$post_id = $post_id->ID;
		}

		$revisions = $this->get_revisions( $post_id );

		if ( ! $revisions ) {
			return false;
		}

		// verify that there's an upload ID in the content field
		// if there's no upload ID for some reason, default to latest attached upload.
		if ( ! $this->get_document( $revisions[0]->ID ) ) {
			$attachments = $this->get_attachments( $post_id );

			if ( empty( $attachments ) ) {
				return false;
			}

			$latest_attachment = reset( $attachments );
			if ( is_numeric( $revisions[0]->post_content ) ) {
				$revisions[0]->post_content = $this->format_doc_id( $revisions[0]->post_content );
			} elseif ( empty( $revisions[0]->post_content ) ) {
				$revisions[0]->post_content = $this->format_doc_id( $latest_attachment->ID );
			}
		}

		return $revisions[0];
	}


	/**
	 * Given a revision id (post->ID) returns the revisions spot in the sequence.
	 *
	 * @since 0.5
	 * @param int $revision_id the revision ID.
	 * @return int revision number
	 */
	public function get_revision_number( int $revision_id ) {
		$revision = get_post( $revision_id );

		if ( ! isset( $revision->post_parent ) ) {
			return false;
		}

		$index = $this->get_revision_indices( $revision->post_parent );

		return array_search( $revision_id, $index, true );
	}


	/**
	 * Ajax Callback to change filelock on lock override.
	 *
	 * @since 0.5
	 * @param bool $send_notice (optional) whether or not to send an e-mail to the former lock owner.
	 */
	public function override_lock( bool $send_notice = true ): void {
		check_ajax_referer( 'wp-document-revisions', 'nonce' );

		$post_id = ( isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : false );

		// verify current user can edit
		// consider a specific permission check here.
		if ( ! $post_id || ! current_user_can( 'edit_document', $post_id ) || ! current_user_can( 'override_document_lock' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify that there is a lock.
		$current_owner = wp_check_post_lock( $post_id );
		if ( ! ( $current_owner ) ) {
			die( '-1' );
		}

		// update the lock.
		wp_set_post_lock( $post_id );

		// get the current user ID.
		$current_user = wp_get_current_user();

		/**
		 * Filters the option to send a locked document override email.
		 *
		 * @param boolean $send_notice selector whether to send the locked document.
		 */
		if ( apply_filters( 'send_document_override_notice', $send_notice ) ) {
			$this->send_override_notice( $post_id, $current_owner, $current_user->ID );
		}

		do_action( 'document_lock_override', $post_id, $current_user->ID, $current_owner );

		die( '1' );
	}


	/**
	 * Clears cache on post_save_document.
	 *
	 * @param int     $post_id the post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function clear_cache( int $post_id, WP_Post $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		wp_cache_delete( $post_id, 'document_revision_indices' );
		wp_cache_delete( $post_id, 'document_revisions' );
		clean_post_cache( $post_id );
	}


	/**
	 * Given a post object, returns all attached uploads.
	 *
	 * @since 0.5
	 * @param object $document (optional) post object.
	 * @return object all attached uploads
	 */
	public function get_attachments( $document = '' ) {
		if ( '' === $document ) {
			global $post;
			$document = $post;
		}

		// verify that it's an object.
		if ( ! is_object( $document ) ) {
			$document = get_post( $document );
		}

		// check for revisions.
		$parent = wp_is_post_revision( $document );
		if ( $parent ) {
			$document = get_post( $parent );
		}

		// check for attachments.
		if ( 'attachment' === $document->post_type ) {
			$document = get_post( $document->post_parent );
		}

		if ( ! isset( $document->ID ) ) {
			return array();
		}

		$args = array(
			'post_parent' => $document->ID,
			'post_status' => 'inherit',
			'post_type'   => 'attachment',
			'order'       => 'DESC',
			'orderby'     => 'post_date',
		);

		/**
		 * Filters the plugin query to fetch all the attachments of a parent post.
		 *
		 * @param array $args Delivered WP Query to fetch all attachments for a document.
		 */
		$args = apply_filters( 'document_revision_query', $args );

		return get_children( $args );
	}

	/**
	 * Checks if document is locked, if so, returns the lock holder's name.
	 *
	 * @since 0.5
	 * @param object|int $document the post object or postID.
	 * @return bool|string false if no lock, user's display name if locked
	 */
	public function get_document_lock( $document ) {
		if ( ! is_object( $document ) ) {
			$document = get_post( $document );
		}

		if ( ! $document ) {
			return false;
		}

		// get the post lock.
		$user = wp_check_post_lock( $document->ID );
		if ( ! ( $user ) ) {
			$user = false;
		}

		// allow others to shortcircuit.
		/**
		 * Filters the user locking the document file.
		 *
		 * @param string $user     user locking the document.
		 * @param object $document Post object.
		 */
		$user = apply_filters( 'document_lock_check', $user, $document );

		if ( ! $user ) {
			return false;
		}

		// get displayname from userID.
		$last_user = get_userdata( $user );
		return ( $last_user ) ? $last_user->display_name : __( 'Somebody', 'wp-document-revisions' );
	}

	/**
	 * For a given post, builds a 1-indexed array of revision post ID's.
	 *
	 * @since 0.5
	 * @param int $post_id the parent post id.
	 * @return array array of revisions
	 */
	public function get_revision_indices( int $post_id ): array {
		$cache = wp_cache_get( $post_id, 'document_revision_indices' );
		if ( $cache ) {
			return $cache;
		}

		$revs = wp_get_post_revisions(
			$post_id,
			array(
				'order' => 'ASC',
			)
		);

		$i = 1;

		// ignore autosaves keeping only real revisions.
		$output   = array();
		$post_rev = $post_id . '-autosave-v1';
		foreach ( $revs as $rev ) {
			if ( $rev->post_name !== $post_rev ) {
				// not an autosave.
				$output[ $i++ ] = $rev->ID;
			}
		}

		if ( ! empty( $output ) ) {
			wp_cache_set( $post_id, $output, 'document_revision_indices' );
		}

		return $output;
	}

	/**
	 * Given a revision number (e.g., 4 from foo-revision-4) returns the revision ID.
	 *
	 * @since 0.5
	 * @param int $revision_num the 1-indexed revision #.
	 * @param int $post_id the ID of the parent post.
	 * @return int the ID of the revision
	 */
	public function get_revision_id( int $revision_num, int $post_id ) {
		$index = $this->get_revision_indices( $post_id );

		return $index[ $revision_num ] ?? false;
	}

	/**
	 * E-mails current lock owner to notify them that they lost their file lock.
	 *
	 * @since 0.5
	 * @param int $post_id id of document lock being overridden.
	 * @param int $owner_id id of current document owner.
	 * @param int $current_user_id id of user overriding lock.
	 * @return bool true on success, false on fail
	 */
	public function send_override_notice( int $post_id, int $owner_id, int $current_user_id ): bool {
		// get lock owner's details.
		$lock_owner = get_userdata( $owner_id );

		// get the current user's details.
		$current_user = wp_get_current_user( $current_user_id );

		// get the post.
		$document = get_post( $post_id );

		// build the subject.
		// translators: %1$s is the blog name, %2$s is the overriding user, %3$s is the document title.
		$subject = sprintf( __( '%1$s: %2$s has overridden your lock on %3$s', 'wp-document-revisions' ), get_bloginfo( 'name' ), $current_user->display_name, $document->post_title );
		/**
		 * Filters the locked document email subject text.
		 *
		 * @param string $subject delivered email subject text.
		 */
		$subject = apply_filters( 'lock_override_notice_subject', $subject );

		// build the message.
		// translators: %s is the user's name.
		$message = sprintf( __( 'Dear %s:', 'wp-document-revisions' ), $lock_owner->display_name ) . "\n\n";
		// translators: %1$s is the overriding user, %2$s is the user's email, %3$s is the document title, %4$s is the document URL.
		$message .= sprintf( __( '%1$s (%2$s), has overridden your lock on the document %3$s (%4$s).', 'wp-document-revisions' ), $current_user->display_name, $current_user->user_email, $document->post_title, get_permalink( $document->ID ) ) . "\n\n";
		$message .= __( 'Any changes you have made will be lost.', 'wp-document-revisions' ) . "\n\n";
		// translators: %s is the blog name.
		$message .= sprintf( __( '- The %s Team', 'wp-document-revisions' ), get_bloginfo( 'name' ) );
		/**
		 * Filters the locked document email message text.
		 *
		 * @param string $message delivered email message text.
		 */
		$message = apply_filters( 'lock_override_notice_message', $message );

		/**
		 * Filters the lost lock document email text.
		 *
		 * @param string $message         lost lock email message text.
		 * @param int    $post_id         document id.
		 * @param int    $current_user_id current user id (who has lost lock).
		 * @param object $lock_owner      locking user details.
		 */
		$message = apply_filters( 'document_lock_override_email', $message, $post_id, $current_user_id, $lock_owner );

		// send mail.
		return wp_mail( $lock_owner->user_email, $subject, $message );
	}

	/**
	 * Check whether we can delete a document revision (to block external functionality).
	 *
	 * @since 3.7
	 * @param WP_Post|false|null $delete       Whether to go forward with deletion.
	 * @param WP_Post            $post         Post object.
	 * @param bool               $force_delete Whether to bypass the Trash.
	 * @return WP_Post | null  Null - No opinion (will run deletion); $post - Bypass delete.
	 */
	public function possibly_delete_revision( $delete, WP_Post $post, bool $force_delete ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// bail if not a revision, an autosave or already decided not to delete.
		if ( 'revision' !== $post->post_type || str_contains( $post->post_name, '-autosave-v1' ) || ! is_null( $delete ) ) {
			// only process revisions.
			return $delete;
		}

		// is it for a document.
		$doc = $post->post_parent;
		if ( 0 === $doc || ! $this->verify_post_type( $doc ) ) {
			// not a document revision.
			return $delete;
		}

		// do we want to allow deletion by known processes (eg PublishPress Revisions).
		/**
		 * Filter to allow revision deletion. Set to true to bypass these tests and allow delete.
		 *
		 * @since 3.7
		 *
		 * @param boolean false  default to not allow deletion.
		 * @param WP_Post $post  Post object.
		 */
		if ( apply_filters( 'document_allow_revision_deletion', false, $post ) ) {
			return null;
		}

		// Have we loaded our admin.
		// If not, we need to continue as it is external functionality trying to delete our revision.
		if ( is_null( $this->admin ) ) {
			$this->admin_init( true );
		}
		// are we in the scope of deleting a document so OK to delete.
		// we can delete a revision if we started with a document or is beyond limit.
		if ( $this->admin->is_deleting() ) {
			return $delete;
		}

		// have we processed the document (so have the keep list).
		if ( array_key_exists( $doc, self::$revns ) ) {
			return ( in_array( $post->ID, self::$revns[ $doc ], true ) ? false : $delete );
		}

		// do we keep all document revisions.
		$keep = wp_revisions_to_keep( get_post( $doc ) );
		if ( -1 === $keep ) {
			// keep all.
			return false;
		}

		// or none. (But we won't respect that for documents).
		if ( 0 === $keep ) {
			return false;
		}

		// have we created the keep list of revisions.
		if ( ! array_key_exists( $doc, self::$revns ) ) {
			global $wpdr;
			$all_revns           = $wpdr->get_revisions( $doc );
			self::$revns[ $doc ] = array_slice( $all_revns, 0, $keep );
		}

		if ( in_array( $post->ID, self::$revns[ $doc ], true ) ) {
			// in the keep list, do not delete.
			return false;
		}

		return $delete;
	}
}
