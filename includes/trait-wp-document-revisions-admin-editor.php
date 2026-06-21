<?php
/**
 * WP Document Revisions Admin Editor Trait
 *
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin editor functionality for WP_Document_Revisions_Admin.
 */
trait WP_Document_Revisions_Admin_Editor {

	/**
	 * The last_but_one revision
	 *
	 * @var int | null
	 */
	private static $last_but_one_revn = null;

	/**
	 * The last_but_one revision excerpt
	 *
	 * @var string | null
	 */
	private static $last_revn_excerpt = null;

	/**
	 * Registers update messages
	 *
	 * @since 0.5
	 * @param array $messages messages array.
	 * @return array messages array with doc. messages
	 */
	public function update_messages( array $messages ): array {
		global $post, $post_id;

		// Cache date/time format options to avoid multiple get_option calls.
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$messages['document'] = array(
			// translators: %s is the download link.
			1  => sprintf( __( 'Document updated. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink( $post_id ) ) ),
			2  => __( 'Custom field updated.', 'wp-document-revisions' ),
			3  => __( 'Custom field deleted.', 'wp-document-revisions' ),
			4  => __( 'Document updated.', 'wp-document-revisions' ),
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// translators: %s is the revision ID.
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Document restored to revision from %s', 'wp-document-revisions' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			// translators: %s is the download link.
			6  => sprintf( __( 'Document published. <a href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( get_permalink( $post_id ) ) ),
			7  => __( 'Document saved.', 'wp-document-revisions' ),
			// translators: %s is the download link.
			8  => sprintf( __( 'Document submitted. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
			// translators: %1$s is the date, %2$s is the preview link.
			9  => sprintf( __( 'Document scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview document</a>', 'wp-document-revisions' ), date_i18n( sprintf( _x( '%1$s @ %2$s', '%1$s: date; %2$s: time', 'wp-document-revisions' ), $date_format, $time_format ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ) ),
			// translators: %s is the link to download the document.
			10 => sprintf( __( 'Document draft updated. <a target="_blank" href="%s">Download document</a>', 'wp-document-revisions' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
		);

		return $messages;
	}


	/**
	 * Adds help tabs to 3.3+ help tab API.
	 *
	 * @since 1.1
	 * @uses get_help_text()
	 * @return void
	 */
	public function add_help_tab(): void {
		$screen = get_current_screen();

		// only interested in document post_types.
		if ( 'document' !== $screen->post_type ) {
			return;
		}
		// loop through each tab in the help array and add.
		foreach ( $this->get_help_text( $screen ) as $title => $content ) {
			$screen->add_help_tab(
				array(
					'title'   => $title,
					'id'      => str_replace( ' ', '_', $title ),
					'content' => $content,
				)
			);
		}
	}


	/**
	 * Callback to manage metaboxes on edit page.
	 * @ since 0.5
	 */
	public function meta_cb(): void {
		global $post;

		// remove unused meta boxes.
		remove_meta_box( 'revisionsdiv', 'document', 'normal' );
		remove_meta_box( 'postexcerpt', 'document', 'normal' );
		remove_meta_box( 'slugdiv', 'document', 'normal' );
		remove_meta_box( 'tagsdiv-workflow_state', 'document', 'side' );

		// add our meta boxes.
		add_meta_box( 'revision-summary', __( 'Revision Summary', 'wp-document-revisions' ), array( $this, 'revision_summary_cb' ), 'document', 'normal', 'default' );
		add_meta_box( 'document', __( 'Document', 'wp-document-revisions' ), array( $this, 'document_metabox' ), 'document', 'normal', 'high' );

		// $post object has the document id stripped out for editing, so check meta data.
		if ( absint( get_post_meta( $post->ID, '_document_attachment_id', true ) ) > 0 ) {
			add_meta_box( 'revision-log', __( 'Revision Log', 'wp-document-revisions' ), array( $this, 'revision_metabox' ), 'document', 'normal', 'low' );
		}

		if ( taxonomy_exists( 'workflow_state' ) && ! $this->disable_workflow_states() ) {
			add_meta_box( 'workflow-state', __( 'Workflow State', 'wp-document-revisions' ), array( $this, 'workflow_state_metabox_cb' ), 'document', 'side', 'default' );
		}

		// move author div to make room for ours.
		remove_meta_box( 'authordiv', 'document', 'normal' );

		// only add author div if user can give someone else ownership.
		if ( current_user_can( 'edit_others_documents' ) ) {
			add_meta_box( 'authordiv', __( 'Owner', 'wp-document-revisions' ), array( $this, 'post_author_meta_box' ), 'document', 'side', 'low' );
		}

		// By default revisions are unlimited, but user filter may have limited number. Check if impact.
		add_action( 'admin_notices', array( $this, 'check_document_revisions_limit' ) );

		// lock notice.
		add_action( 'admin_notices', array( $this, 'lock_notice' ) );

		do_action( 'document_edit' );
	}


	/**
	 * Forces postcustom metabox to be hidden by default, despite the fact that the CPT creates it.
	 *
	 * @since 1.0
	 * @param array      $hidden the default hidden metaboxes.
	 * @param ?WP_Screen $screen the current screen.
	 * @return array defaults with postcustom
	 */
	public function hide_postcustom_metabox( array $hidden, ?WP_Screen $screen ): array {
		if ( $screen && 'document' === $screen->id ) {
			$hidden[] = 'postcustom';
		}

		return $hidden;
	}


	/**
	 * Metabox to provide common document functions.
	 *
	 * @since 0.5
	 * @param WP_Post $post the post object.
	 */
	public function document_metabox( WP_Post $post ): void {
		$wpdr = self::$parent;
		// convert old format to new.
		if ( is_numeric( $post->post_content ) ) {
			$post->post_content = $wpdr->format_doc_id( $post->post_content );
		}
		// put the document id in metadata.
		$attach = $wpdr->populate_attachment_meta( $post->ID, $post->post_content );

		// set the description field.
		$descr = preg_replace( '/<!-- WPDR \s*\d+ -->/', '', $post->post_content );
		?>
		<input type="hidden" id="post_content" name="post_content" value="<?php echo esc_attr( $descr ); ?>" />
		<input type="hidden" id="curr_attach" name="curr_attach" value="<?php echo esc_attr( $attach ); ?>" />
		<input type="hidden" id="attach_ext" name="attach_ext" value="" />
		<?php
		$lock_holder = $wpdr->get_document_lock( $post );
		if ( $lock_holder ) {
			?>
			<div id="lock_override" class="hide-if-no-js">
			<?php
			// translators: %s is the user that holds the document lock.
			printf( esc_html__( '%s has prevented other users from making changes.', 'wp-document-revisions' ), esc_html( $lock_holder ) );
			if ( current_user_can( 'override_document_lock' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
				_e( '<br />If you believe this is in error you can <a href="#" id="override_link">override the lock</a>, but their changes will be lost.', 'wp-document-revisions' );
			}
			?>
			</div>
		<?php } ?>
		<div id="lock_override">
			<button id="add-document-file" class="button"><?php esc_html_e( 'Upload New Version', 'wp-document-revisions' ); ?></button>
		</div>
		<?php
		$latest_version = $wpdr->get_latest_revision( $post->ID );
		if ( is_object( $latest_version ) ) {
			?>
			<p>
			<strong><?php esc_html_e( 'Latest Version of the Document', 'wp-document-revisions' ); ?></strong>
			<strong><a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_BLANK"><?php esc_html_e( 'Download', 'wp-document-revisions' ); ?></a></strong><br />
			<em>
			<?php
			$mod_date = $latest_version->post_modified;
			// translators: %1$s is the post modified date in words, %2$s is the post modified date in time format, %3$s is how long ago the post was modified, %4$s is the author's name.
			$checked_in = sprintf( __( 'Checked in <abbr class="timestamp" title="%1$s" id="A%2$s">%3$s</abbr> ago by %4$s', 'wp-document-revisions' ), esc_attr( $mod_date ), esc_attr( (string) strtotime( $mod_date ) ), esc_html( human_time_diff( (int) get_post_modified_time( 'U', true, $post->ID ), time() ) ), esc_html( get_the_author_meta( 'display_name', $latest_version->post_author ) ) );
			echo wp_kses(
				$checked_in,
				array(
					'abbr' => array(
						'class' => array(),
						'title' => array(),
						'id'    => array(),
					),
				)
			);
			?>
			</em>
			</p>
		<?php } ?>
		<div class="clear"></div>
		<?php
	}


	/**
	 * Callback to generate metabox for workflow state.
	 *
	 * @since 0.5
	 * @param WP_Post $post the post object.
	 */
	public function workflow_state_metabox_cb( WP_Post $post ): void {
		wp_nonce_field( 'wp-document-revisions', 'workflow_state_nonce' );

		$current_state = wp_get_post_terms(
			$post->ID,
			'workflow_state'
		);
		$states        = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
		?>
		<label for="workflow_state"><?php esc_html_e( 'Current State', 'wp-document-revisions' ); ?>:</label>
		<select name="workflow_state" id="workflow_state">
			<option></option>
			<?php foreach ( $states as $state ) { ?>
			<option value="<?php echo esc_attr( $state->slug ); ?>"
				<?php
				if ( $current_state ) {
					selected( $current_state[0]->slug, $state->slug );
				}
				?>
				><?php echo esc_html( $state->name ); ?></option>
			<?php } ?>
		</select>
		<?php
	}


	/**
	 * Callback to identify change in workflow_state to provide an action point.
	 *
	 * @since 0.5
	 *
	 * @param int    $doc_id     the document ID.
	 * @param array  $terms      the new terms.
	 * @param array  $tt_ids     the new term IDs.
	 * @param string $taxonomy   the taxonomy being changed.
	 * @param bool   $append     whether it is being appended or replaced.
	 * @param array  $old_tt_ids term taxonomy ID array before the change.
	 */
	public function workflow_state_save( int $doc_id, array $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
		// Only interested in replacement to this taxonomy, so if not, bail early.
		if ( 'workflow_state' !== $taxonomy || $append ) {
			return;
		}
		// autosave check.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// only main item.
		if ( wp_is_post_revision( $doc_id ) ) {
			return;
		}

		// verify CPT.
		$wpdr = self::$parent;
		if ( ! $wpdr->verify_post_type( $doc_id ) ) {
			return;
		}

		// check permissions.
		if ( ! current_user_can( 'edit_document', $doc_id ) ) {
			return;
		}

		if ( empty( $tt_ids ) ) {
			$new_id = '';
		} else {
			$term_obj = get_term_by( 'term_taxonomy_id', $tt_ids[0], $taxonomy );
			$new_id   = $term_obj->term_id;
		}
		if ( empty( $old_tt_ids ) ) {
			$old_id = '';
		} else {
			$term_obj = get_term_by( 'term_taxonomy_id', $old_tt_ids[0], $taxonomy );
			$old_id   = $term_obj->term_id;
		}

		if ( $new_id === $old_id ) {
			return;
		}

		// Old action hook.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'change_document_workflow_state', $doc_id, $new_id );

		// Replacement action hook.
		do_action( 'document_change_workflow_state', $doc_id, $new_id, $old_id );
	}


	/**
	 *
	 * Restores the WPDR attachment ID comment to post_content when it has been stripped
	 * by wp_kses_post (applied via content_save_pre for users without unfiltered_html).
	 *
	 * This filter runs after content_save_pre but before the DB write, so it can patch
	 * the data in-place without a secondary wp_update_post call.
	 *
	 * @since 4.0.8
	 * @param array $data    Sanitized post data about to be inserted.
	 * @param array $postarr Raw post data passed to wp_insert_post.
	 * @return array Post data, with post_content restored if needed.
	 */
	public function restore_document_attachment_id( array $data, array $postarr ): array {
		if ( 'document' !== $data['post_type'] ) {
			return $data;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}

		$wpdr = self::$parent;
		// Get the document id.
		$doc_id = $postarr['ID'];

		// Find the meta data value.
		// For revision restores need to get attachment id from the post_content, normally from post_meta.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'restore' === $_GET['action'] ) {
			$attach_id = absint( $wpdr->extract_document_id( $postarr['post_content'] ) );
			// The attachment id comes from (forgeable) post_content here, so if one is present
			// confirm it actually belongs to this document before trusting it.
			if ( $attach_id > 0 && ! $this->attachment_belongs_to_document( $attach_id, $doc_id ) ) {
				return $data;
			}
			update_post_meta( $doc_id, '_document_attachment_id', $attach_id );
		} else {
			$attach_id = absint( get_post_meta( $doc_id, '_document_attachment_id', true ) );
		}

		// Already has an attachment ID, see if it is the stored one so nothing to fix.
		if ( $attach_id && $attach_id === $wpdr->extract_document_id( $data['post_content'] ) ) {
			return $data;
		}

		// Believe the meta value if it's there.
		if ( ! $attach_id ) {
			// The WPDR comment may have been stripped by wp_kses_post.  The raw form value is
			// still in $_POST['post_content'] (unfiltered superglobal).
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! isset( $_POST['post_content'] ) ) {
				return $data;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_posted = wp_unslash( $_POST['post_content'] );
			$attach_id  = absint( $wpdr->extract_document_id( $raw_posted ) );

			// The id is taken from forgeable POST data, so confirm the attachment actually
			// belongs to this document before storing it as the document's marker. Without this
			// an editor could point their document at another document's attachment (IDOR).
			if ( ! $this->attachment_belongs_to_document( $attach_id, $doc_id ) ) {
				return $data;
			}
		}

		// Clean the text from odd spurious tinymce pieces.
		$text = $data['post_content'];
		$text = str_replace( '<br data-mce-bogus="1">', '', $text );
		$text = preg_replace( '/<br>\s*<\/p>/', '', $text );
		$text = preg_replace( '/<p>\s*<\/p>/', '', $text );

		// Rebuild: attachment ID comment + any description that survived kses.
		$data['post_content'] = $wpdr->format_doc_id( $attach_id ) . $text;

		return $data;
	}


	/**
	 * Confirms an attachment id (taken from untrusted input) belongs to a document.
	 *
	 * Used to prevent a document editor from forging the WPDR attachment marker so that
	 * their document points at, and can therefore serve, another document's attachment.
	 * Mirrors the ownership check on the REST save path (sync_meta_to_content).
	 *
	 * @since 5.1.1
	 * @param int $attach_id the attachment id extracted from forgeable input.
	 * @param int $doc_id    the id of the document being saved.
	 * @return bool true if the attachment exists, is an attachment, and is parented to the document.
	 */
	private function attachment_belongs_to_document( int $attach_id, int $doc_id ): bool {
		if ( $attach_id <= 0 || $doc_id <= 0 ) {
			return false;
		}

		$attachment = get_post( $attach_id );

		return ( $attachment instanceof WP_Post
			&& 'attachment' === $attachment->post_type
			&& (int) $attachment->post_parent === $doc_id );
	}


	/**
	 * Callback to unlink loaded featured image.
	 *
	 * @since 3.3
	 * @param int $doc_id the ID of the post being edited.
	 */
	public function save_document( int $doc_id ): void {
		// autosave check.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// check permissions.
		if ( ! current_user_can( 'edit_document', $doc_id ) ) {
			return;
		}

		$parent = wp_is_post_revision( $doc_id );
		if ( false !== $parent ) {
			$doc_id = $parent;
		}
		// if document has featured image loaded with document, then make sure it has no parent
		// done after we make sure that we have doc_id being parent.
		$thumb = get_post_meta( $doc_id, '_thumbnail_id', true );
		if ( $thumb > 0 ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_parent` = 0 WHERE `id` = %d AND `post_parent` = %d ",
				$thumb,
				$doc_id
			);
			$wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( $thumb, 'posts' );
			clean_post_cache( $thumb );
		}

		$wpdr = self::$parent;
		// find the attachment id now (as content might be cached) and we might delete the cache.
		$attach_id = absint( get_post_meta( $doc_id, '_document_attachment_id', true ) );

		// Fallback: if no attachment ID reached the DB (JS failed to set the hidden field, or
		// wp_kses_post stripped the WPDR comment for low-privilege users), try to recover from
		// the most recently uploaded attachment for this document.
		if ( ! $attach_id ) {
			$latest_attach = $this->get_latest_attachment( $doc_id );
			if ( $latest_attach ) {
				// Preserve any existing description text, but restore the attachment ID comment.
				$content     = get_post_field( 'post_content', $doc_id );
				$description = ( is_numeric( $content ) ) ? '' : preg_replace( '/<!-- WPDR\s*\d+\s*-->/i', '', $content );
				$new_content = $wpdr->format_doc_id( $latest_attach->ID ) . $description;
				update_post_meta( $doc_id, '_document_attachment_id', $latest_attach->ID );
				global $wpdb;
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
				$post_table = "{$wpdb->prefix}posts";
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE `$post_table` SET `post_content` = %s WHERE `ID` = %d",
						$new_content,
						$doc_id
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
				wp_cache_delete( $doc_id, 'posts' );
				clean_post_cache( $doc_id );
				$attach_id = $latest_attach->ID;
			}
		}

		// Let's work on Workflow state, Verify nonce.
		if ( ! isset( $_POST['workflow_state_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['workflow_state_nonce'] ) ), 'wp-document-revisions' ) ) {
			return;
		}
		$ws = ( isset( $_POST['workflow_state'] ) ? sanitize_text_field( wp_unslash( $_POST['workflow_state'] ) ) : '' );

		// Save it.
		wp_set_post_terms( $doc_id, array( $ws ), 'workflow_state' );

		// is the permalink useful.
		$doc_post = get_post( $doc_id );
		$new_guid = $wpdr->permalink( $doc_post->guid, $doc_post, false, '' );
		if ( $new_guid !== $doc_post->guid ) {
			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `guid` = %s WHERE `id` = %d AND `post_parent` = 0 ",
				$new_guid,
				$doc_id
			);
			$wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $doc_id );
		}

		// can we merge the revisions.
		if ( is_null( self::$last_revn ) || is_null( self::$last_but_one_revn ) ) {
			null;
		} else {
			// Yes. Need to delete the last_but one revision and update the excerpt on the last revision and the post to keep timestamps.
			// Remove our filter so that we can delete the revision.
			global $wpdr;
			remove_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999 );
			wp_delete_post_revision( self::$last_but_one_revn );
			add_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999, 3 );

			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_excerpt` = %s WHERE `id` IN ( %d, %d ) AND `post_excerpt` <> %s ",
				self::$last_revn_excerpt,
				self::$last_revn,
				$doc_id,
				self::$last_revn_excerpt
			);
			$wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery	
			wp_cache_delete( self::$last_revn, 'posts' );
			wp_cache_delete( $doc_id, 'posts' );
			clean_post_cache( self::$last_revn_excerpt );
			clean_post_cache( self::$last_revn );
		}

		/**
		 * Fires once a document has been saved and all plugin processing done.
		 *
		 * @since 3.4.0
		 *
		 * @param int $doc_id    id of Document post.
		 * @param int $attach_id id of Attachment post.
		 */
		do_action( 'document_saved', $doc_id, $attach_id );
	}


	/**
	 * Enqueue admin scripts, JS and CSS files.
	 */
	public function enqueue_edit_scripts(): void {
		$wpdr = self::$parent;
		// only include JS on document pages.
		if ( ! $wpdr->verify_post_type() ) {
			return;
		}

		// Forces autosave to load. By default, if there's a lock on the post, auto save isn't loaded; we want it in case lock is overridden.
		wp_enqueue_script( 'autosave' );

		// ThickBox and media-upload are only needed for the classic editor.
		$post_id = get_the_ID();
		if ( ! $post_id || ! function_exists( 'use_block_editor_for_post' ) || ! use_block_editor_for_post( $post_id ) ) {
			// For the document upload. Only pass a 'post' when we actually have one:
			// on the documents list screen get_the_ID() is false, and passing that to
			// wp_enqueue_media() makes core read ->ID on a null post (PHP warning).
			wp_enqueue_media( $post_id ? array( 'post' => $post_id ) : array() );
		}

		// Check if block editor is active for this document.
		$use_block_editor = (bool) $post_id
			&& function_exists( 'use_block_editor_for_post' )
			&& use_block_editor_for_post( $post_id );

		if ( $use_block_editor ) {
			// Block editor: enqueue the sidebar upload plugin.
			$asset_file = dirname( __DIR__ ) . '/build/editor-document-upload/index.asset.php';
			if ( file_exists( $asset_file ) ) {
				$asset = require $asset_file;
				wp_enqueue_script(
					'wp-document-revisions-editor',
					plugins_url( '/build/editor-document-upload/index.js', __DIR__ ),
					$asset['dependencies'],
					$asset['version'],
					true
				);
				wp_set_script_translations( 'wp-document-revisions-editor', 'wp-document-revisions' );
				wp_add_inline_script(
					'wp-document-revisions-editor',
					'window.wpDocumentRevisions = ' . wp_json_encode(
						array(
							'restBase' => $wpdr->document_slug(),
						)
					) . ';',
					'before'
				);

				// Hide the main editor canvas — documents are managed via the sidebar panels.
				wp_add_inline_style(
					'wp-edit-post',
					'.post-type-document .editor-visual-editor,
					.post-type-document .edit-post-visual-editor,
					.post-type-document .editor-text-editor,
					.post-type-document .edit-post-text-editor {
						display: none !important;
					}'
				);
			}
		} else {
			// Classic editor: enqueue the existing admin JS with localized strings.
			$data = array(
				'restoreConfirmation' => __( 'Are you sure you want to restore this revision? If you do, no history will be lost. This revision will be copied and become the most recent revision.', 'wp-document-revisions' ),
				'lockNeedle'          => __( 'is currently editing this', 'wp-document-revisions' ),
				'postUploadNotice'    => '<div id="message" class="updated"><p>' . __( 'File uploaded successfully. Add a revision summary below (optional) and press <strong>Update</strong> to save your changes.', 'wp-document-revisions' ) . '</p></div>',
				'postDesktopNotice'   => '<div id="message" class="update-nag"><p>' . __( 'After you have saved your document in your office software, <a href="#" onClick="location.reload();">reload this page</a> to see your changes.', 'wp-document-revisions' ) . '</p></div>',
				'uploadConfirmation'  => __( 'New version uploaded. Press Update to save.', 'wp-document-revisions' ),
				'uploadErrorNotice'   => '<div id="wpdr-upload-error" class="error"><p>' . __( 'Upload failed.', 'wp-document-revisions' ) . '</p></div>',
				'saveFirstNotice'     => '<div id="wpdr-save-first-notice" class="error"><p>' . __( 'Please save the current version before uploading another.', 'wp-document-revisions' ) . '</p></div>',
				'uploadProgress'      => __( 'Uploading…', 'wp-document-revisions' ),
				// translators: %s is the title of the document.
				'lostLockNotice'      => __( 'Your lock on the document %s has been overridden. Any changes will be lost.', 'wp-document-revisions' ),
				'lockError'           => __( 'An error has occurred, please try reloading the page.', 'wp-document-revisions' ),
				'lostLockNoticeTitle' => __( 'Lost Document Lock', 'wp-document-revisions' ),
				'lostLockNoticeLogo'  => admin_url( 'images/logo.gif' ),
				// translators: %d is the numeric minutes, when singular.
				'minute'              => __( '%d mins', 'wp-document-revisions' ),
				// translators: %d is the numeric minutes, when plural.
				'minutes'             => __( '%d mins', 'wp-document-revisions' ),
				// translators: %d is the numeric hour, when singular.
				'hour'                => __( '%d hour', 'wp-document-revisions' ),
				// translators: %d is the numeric hour, when plural.
				'hours'               => __( '%d hours', 'wp-document-revisions' ),
				// translators: %d is the numeric day, when singular.
				'day'                 => __( '%d day', 'wp-document-revisions' ),
				// translators: %d is the numeric days, when plural.
				'days'                => __( '%d days', 'wp-document-revisions' ),
				'offset'              => get_option( 'gmt_offset' ) * 3600,
				'nonce'               => wp_create_nonce( 'wp-document-revisions' ),
			);

			$suffix = ( WP_DEBUG ) ? '.dev' : '';
			$path   = '/js/wp-document-revisions' . $suffix . '.js';
			$vers   = ( WP_DEBUG ) ? filemtime( dirname( __DIR__ ) . $path ) : $wpdr->version;
			wp_enqueue_script(
				'wp_document_revisions',
				plugins_url( $path, __DIR__ ),
				array( 'wp-api-fetch' ),
				$vers,
				false
			);
			wp_add_inline_script(
				'wp_document_revisions',
				'var wp_document_revisions = ' . wp_json_encode( $data ) . ';',
				'before'
			);
		}

		// enqueue CSS.
		wp_enqueue_style( 'wp-document-revisions', plugins_url( '/css/style.css', __DIR__ ), null, $wpdr->version );
	}


	/**
	 * Defaults document visibility to private.
	 *
	 * @since 0.5
	 */
	public function make_private(): void {
		global $post;
		$wpdr = self::$parent;

		// verify that this is a new document.
		if ( ! isset( $post ) || ! $wpdr->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) || strlen( $post->post_content ) > 0 ) {
			return;
		}

		$post_pre = clone $post;

		if ( 'draft' === $post->post_status || 'auto-draft' === $post->post_status ) {
			$post->post_status = 'private';
		}

		/**
		 * Filters setting the new document status to private.
		 *
		 * @since 0.5
		 *
		 * @param WP_Post $post     link to (new) global post.
		 * @param WP_Post $post_pre link to clone of global post.
		 */
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = apply_filters( 'document_to_private', $post, $post_pre );
	}

	/**
	 * Helper function to provide help text as an array.
	 *
	 * @since 1.1
	 * @param WP_Screen $screen (optional) the current screen.
	 * @return array the help text
	 */
	public function get_help_text( ?WP_Screen $screen = null ): array {
		if ( is_null( $screen ) ) {
			$screen = get_current_screen();
		}

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			'document'      => array(
				__( 'Basic Usage', 'wp-document-revisions' ) =>
				'<p>' . __( 'This screen allows users to collaboratively edit documents and track their revision history. To begin, enter a title for the document, optionally enter a description, and click <code>Upload New Version</code> and select the file from your computer.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'Once successfully uploaded, you can enter a revision log message, assign the document an author, and describe its current workflow state.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'When done, simply click <code>Update</code> to save your changes', 'wp-document-revisions' ) . '</p>',
				__( 'Document Description', 'wp-document-revisions' ) =>
				'<p>' . __( 'The document description provides a short user-oriented summary of the document.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'This can be used with the Document List or Latest Documents blocks or their shortcode or widget equivalents.', 'wp-document-revisions' ) . '</p>',
				__( 'Revision Log', 'wp-document-revisions' ) =>
				'<p>' . __( 'The revision log provides a short summary of the changes reflected in a particular revision. Used widely in the open-source community, it provides a comprehensive history of the document at a glance.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'You can download and view previous versions of the document by clicking the timestamp in the revision log. You can also restore revisions by clicking the <code>restore</code> button beside the revision.', 'wp-document-revisions' ) . '</p>',
				__( 'Workflow State', 'wp-document-revisions' ) =>
				'<p>' . __( 'The workflow state field can be used to help team members understand at what stage a document sits within a particular organization&quot;s workflow. The field is optional, and can be customized or queried by clicking <code>Workflow States</code> on the left-hand side.', 'wp-document-revisions' ) . '</p>',
				__( 'Publishing Documents', 'wp-document-revisions' ) =>
				'<p>' . __( 'By default, uploaded documents are only accessible to logged in users. Documents can be published, thus making them accessible to the world, by toggling their visibility in the "Publish" box in the top right corner. Any document marked as published will be accessible to anyone with the proper URL.', 'wp-document-revisions' ) . '</p>',
			),
			'edit-document' => array(
				__( 'Documents', 'wp-document-revisions' ) =>
				'<p>' . __( 'Below is a list of all documents to which you have access. Click the document title to edit the document or download the latest version.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'To add a new document, click <strong>Add Document</strong> on the left-hand side.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'To view all documents at a particular workflow state, click <strong>Workflow States</strong> in the menu on the left.', 'wp-document-revisions' ) . '</p>',
			),
		);

		// if we don't have any help text for this screen, just kick.
		if ( ! $screen instanceof WP_Screen || ! isset( $help[ $screen->id ] ) ) {
			return array();
		}

		/**
		 * Filters the default help text for current screen.
		 *
		 * @param string[]  $help   default help text for current screen.
		 * @param WP_Screen $screen current screen name.
		 */
		return apply_filters( 'document_help_array', $help[ $screen->id ], $screen );
	}

	/**
	 * Use Classic Editor for Documents (as need to constrain options).
	 *
	 * @since 3.4.0
	 *
	 * @param bool    $use_block_editor Whether the post can be edited or not.
	 * @param WP_Post $post             The post being checked.
	 */
	public function no_use_block_editor( bool $use_block_editor, WP_Post $post ) {
		$wpdr = self::$parent;
		// switch off for documents unless document_use_block_editor filter is true.
		if ( $wpdr->verify_post_type( $post ) ) {
			/**
			 * Filters whether documents should use the block editor.
			 *
			 * @since 3.6.0
			 *
			 * @param bool $use_block_editor Whether to use the block editor for documents.
			 */
			return apply_filters( 'document_use_block_editor', false );
		}
		return $use_block_editor;
	}

	/**
	 * Invoke the editor for a description to be entered.
	 *
	 * @ since 3.4.0
	 *
	 * @param WP_Post $post Post object.
	 */
	public function prepare_editor( WP_Post $post ): void {
		if ( 'document' !== $post->post_type ) {
			return;
		}

		echo '<h2>' . esc_html__( 'Document Description', 'wp-document-revisions' ) . '</h2>';

		$wpdr = self::$parent;
		// convert old format to new.
		if ( is_numeric( $post->post_content ) ) {
			$post->post_content = $wpdr->format_doc_id( $post->post_content );
		}
	}

	/**
	 * Modify the standard Classic editor settings for a simple description to be entered.
	 *
	 * @since 3.4.0
	 *
	 * @param array  $settings  Array of editor arguments.
	 * @param string $editor_id Unique editor identifier, e.g. 'content'. Accepts 'classic-block'
	 *                          when called from block editor's Classic block.
	 */
	public function document_editor_setting( array $settings, string $editor_id ) {
		// only interested in content.
		if ( 'content' !== $editor_id ) {
			return $settings;
		}

		global $post;
		$wpdr = self::$parent;
		if ( 'document' === $post->post_type || $wpdr->verify_post_type( $post->ID ) ) {
			// restricted capacity for document content.
			return array(
				'wpautop'       => false,
				'media_buttons' => false,
				'textarea_name' => 'doc_descr',
				'textarea_rows' => 8,
				'teeny'         => false,
				'quicktags'     => false,
			);
		}
		return $settings;
	}

	/**
	 * Modify the 'content' class for documents otherwise it indents everything in the editor.
	 *
	 * @since 3.4.0
	 *
	 * @param array $settings  Array of tiny_mce arguments.
	 */
	public function modify_content_class( array $settings ) {
		// check on document only affects these.
		if ( array_key_exists( 'body_class', $settings ) && 0 === strpos( $settings['body_class'], 'content post-type-document' ) ) {
			$settings['content_css'] = $settings['content_css'] . ',' . plugins_url( '/css/wpdr-content.css', __DIR__ );
		}
		return $settings;
	}

	/**
	 * Filter the post_content field to remove the attachment_id before editing.
	 *
	 * @param String $post_content the post content field.
	 * @return string
	 */
	public function remove_attachment_id( string $post_content ) {
		global $post;
		$wpdr = self::$parent;

		if ( $wpdr->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
			$post_content = ( is_numeric( $post_content ) ? '' : preg_replace( '/<!-- WPDR \d+\s* -->/', '', $post_content ) );
		}
		return $post_content;
	}

	/**
	 * Filter the admin body class to add additional classes which we can use conditionally
	 * to style the page (e.g., when the document is locked).
	 *
	 * @param String $body_class the existing body class(es).
	 */
	public function admin_body_class_filter( string $body_class ) {
		global $post;
		$wpdr = self::$parent;

		if ( ! $wpdr->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
			return $body_class;
		}

		$body_class .= ' document';

		if ( $wpdr->get_document_lock( $post ) ) {
			$body_class .= ' document-locked';
		}

		return $body_class;
	}

	/**
	 * Hide header (gallery, URL, library, etc.) links from media-upload
	 * No real use case for a revision being an already uploaded file,
	 * and javascript not compatible for that usecase as written.
	 *
	 * @since 1.2.4
	 */
	public function hide_upload_header(): void {
		global $pagenow;
		$wpdr = self::$parent;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'media-upload.php' === $pagenow && ( isset( $_GET['post_id'] ) ? $wpdr->verify_post_type( (int) sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) ) : false ) ) {
			?>
			<style>
				#media-upload-header {display:none;}
			</style>
			<?php
		}
	}

	/**
	 * Check that those having edit_document can upload documents.
	 *
	 * @since 3.3
	 */
	public function check_upload_files(): void {
		global $typenow, $pagenow;

		if ( ! current_user_can( 'edit_documents' ) || 'document' !== $typenow || current_user_can( 'upload_files' ) ) {
			return;
		}

		if ( 'post.php' === $pagenow ) {
			?>
			<div class="notice notice-warning is-dismissible"><p>
			<?php esc_html_e( 'You do not have the upload_files capability!', 'wp-document-revisions' ); ?>
			</p><p>
			<?php esc_html_e( 'You will not be able to upload documents though you may be able change some attributes.', 'wp-document-revisions' ); ?>
			</p></div>
			<?php
		} elseif ( 'post-new.php' === $pagenow ) {
			?>
			<div class="notice notice-warning is-dismissible"><p>
			<?php esc_html_e( 'You do not have the upload_files capability!', 'wp-document-revisions' ); ?>
			</p><p>
			<?php esc_html_e( 'You will not be able to upload any documents.', 'wp-document-revisions' ); ?>
			</p></div>
			<?php
			// Need to switch off save capability.
		}
	}

	/**
	 * Custom excerpt metabox CB.
	 *
	 * @since 0.5
	 */
	public function revision_summary_cb(): void {
		?>
		<label class="screen-reader-text" for="excerpt"><?php esc_html_e( 'Revision Summary', 'wp-document-revisions' ); ?></label>
		<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"></textarea>
		<p><?php esc_html_e( 'Revision summaries are optional notes to store along with this revision that allow other users to quickly and easily see what changes you made without needing to open the actual file.', 'wp-document-revisions' ); ?></p>
		<?php
	}

	/**
	 * Creates revision log metabox.
	 *
	 * @since 0.5
	 * @param WP_Post $post the post object.
	 */
	public function revision_metabox( WP_Post $post ): void {
		// post_content has had the document attachment number removed so we need to get it from post meta.
		global $wpdr;
		$can_edit_doc = current_user_can( 'edit_document', $post->ID );
		$attach_id    = absint( get_post_meta( $post->ID, '_document_attachment_id', true ) );
		$revisions    = $wpdr->get_revisions( $post->ID );
		$key          = $this->get_feed_key();
		?>
		<table id="document-revisions">
			<thead>
			<tr class="header">
				<th><?php esc_html_e( 'Modified', 'wp-document-revisions' ); ?></th>
				<th><?php esc_html_e( 'User', 'wp-document-revisions' ); ?></th>
				<th style="width:50%"><?php esc_html_e( 'Summary', 'wp-document-revisions' ); ?></th>
				<?php
				if ( $can_edit_doc ) {
					?>
					<th><?php esc_html_e( 'Actions', 'wp-document-revisions' ); ?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
		<?php

		foreach ( $revisions as $revision ) {
			if ( ! current_user_can( 'read_document', $revision->ID ) ) {
				continue;
			}
			// preserve original file extension on revision links.
			// this will prevent mime/ext security conflicts in IE when downloading.
			$attach = $wpdr->get_document( $revision->ID );

			if ( $attach ) {
				$fn   = get_post_meta( $attach->ID, '_wp_attached_file', true );
				$fno  = pathinfo( $fn, PATHINFO_EXTENSION );
				$info = pathinfo( get_permalink( $revision->ID ) );
				$fn   = $info['dirname'] . '/' . $info['filename'];
				// Only add extension if permalink doesnt contain post id as it becomes invalid.
				if ( ! strpos( $info['filename'], '&p=' ) ) {
					$fn .= '.' . $fno;
				}
			} else {
				$fn = get_permalink( $revision->ID );
			}
			// cast the modified date into js format to simplify updating.
			$mod_date = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $revision->post_modified ) );
			?>
			<tr>
				<td><a href="<?php echo esc_url( $fn ); ?>" title="<?php echo esc_attr( $mod_date ); ?>" class="timestamp"><?php echo esc_html( human_time_diff( strtotime( $revision->post_modified_gmt ), time() ) ); ?></a></td>
				<td><?php echo esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ); ?></td>
				<td><?php echo esc_html( $revision->post_excerpt ); ?></td>
				<?php if ( $can_edit_doc && $post->ID !== $revision->ID && $attach && $attach_id !== $attach->ID ) { ?>
					<td><a href="
					<?php
					echo esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'revision' => $revision->ID,
									'action'   => 'restore',
								),
								'revision.php'
							),
							"restore-post_$revision->ID"
						)
					);
					?>
				" class="revision"><?php esc_html_e( 'Restore', 'wp-document-revisions' ); ?></a></td>
				<?php } ?>
			</tr>
			<?php
		}
		?>
		</tbody>
		</table>
		<div class="footer_cols">
		<div class="footer_left">
		<p><a href="<?php echo esc_url( add_query_arg( 'key', $key, get_post_comments_feed_link( $post->ID ) ) ); ?>"><?php esc_html_e( 'RSS Feed', 'wp-document-revisions' ); ?></a></p>
		</div>
		<div class="footer_right">
		<p><?php echo wp_kses_post( __( 'Restoring earlier revisions will also restore that description.<br/>If the current one is wanted, copy it first.', 'wp-document-revisions' ) ); ?></p>
		</div>
		</div>
		<?php
	}

	/**
	 * Retrieves the most recent file attached to a post.
	 *
	 * @since 0.5
	 * @param int $post_id the parent post.
	 * @return object the attachment object
	 */
	public function get_latest_attachment( int $post_id ) {
		$wpdr        = self::$parent;
		$attachments = $wpdr->get_attachments( $post_id );

		return reset( $attachments );
	}

	/**
	 * Callback to display lock notice on top of edit page.
	 *
	 * @since 0.5
	 */
	public function lock_notice(): void {
		global $post;

		do_action( 'document_lock_notice', $post );

		// if there is no page var, this is a new document, no need to warn.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post'] ) ) :
			?>
		<div class="error" id="lock-notice"><p><?php esc_html_e( 'You currently have this file checked out. No other user can edit this document so long as you remain on this page.', 'wp-document-revisions' ); ?></p></div>
			<?php
			endif;
	}

	/**
	 * Back Compat.
	 */
	public function enqueue_js(): void {
		_deprecated_function( __FUNCTION__, '1.3.2 of WP Document Revisions', 'enqueue' );
		$this->enqueue_edit_scripts();
	}

	/**
	 * Identify the 'last but one' revision in case we will merge them.
	 *
	 * @param bool    $post_has_changed Whether the post has changed.
	 * @param WP_Post $last_revision    The last revision post object.
	 * @param WP_Post $post             The post object.
	 * @return bool
	 */
	public function identify_last_but_one( bool $post_has_changed, WP_Post $last_revision, WP_Post $post ): bool {
		// only interested if post changed.
		if ( ! $post_has_changed ) {
			return $post_has_changed;
		}

		$wpdr = self::$parent;
		// verify post type.
		if ( ! $wpdr->verify_post_type( $post->ID ) ) {
			return $post_has_changed;
		}

		// misuse of filter, but can use to determine whether the revisions can be merged.
		// keep revision if title or content (document linked only) changed. Also if author changed.
		if ( $post->post_title !== $last_revision->post_title || $post->post_author !== $last_revision->post_author ) {
			return true;
		}

		// Cache extract_document_id results to avoid duplicate regex operations.
		$post_doc_id          = $wpdr->extract_document_id( $post->post_content );
		$last_revision_doc_id = $wpdr->extract_document_id( $last_revision->post_content );
		if ( $post_doc_id !== $last_revision_doc_id ) {
			return true;
		}

		// normally only title, content and excerpt name for a revision to be created.
		$time_diff = time() - strtotime( $last_revision->post_modified_gmt );

		/**
		 * Filters whether to merge two revisions for a change in excerpt (generally where taxonomy change made late).
		 *
		 * Changes to the title or content (document) will always create an additional revision.
		 * But if there are two revisions within a user-defined time of each other and only one has excerpt text, they can be merged.
		 *
		 * @since 3.4
		 *
		 * @param int $merge_window number of seconds between revisions to NOT create an extra revision.
		 */
		if ( $time_diff < apply_filters( 'document_revisions_merge_revisions', 0 ) ) {
			// only here as excerpt changed.
			if ( '' === $post->post_excerpt || '' === $last_revision->post_excerpt ) {
				// possible merge, set last_but_one_revision (used in routine save_document).
				self::$last_but_one_revn = $last_revision->ID;
				self::$last_revn_excerpt = ( '' === $post->post_excerpt ? $last_revision->post_excerpt : $post->post_excerpt );
				return true;
			}
		}

		return $post_has_changed;
	}

	/**
	 * Slightly modified document author metabox because the current one is ugly.
	 *
	 * @since 0.5
	 * @param WP_Post $post the post object.
	 */
	public function post_author_meta_box( WP_Post $post ): void {
		global $user_id;
		?>
		<label class="screen-reader-text" for="post_author_override"><?php esc_html_e( 'Owner', 'wp-document-revisions' ); ?></label>
		<?php esc_html_e( 'Document Owner', 'wp-document-revisions' ); ?>:
		<?php
		wp_dropdown_users(
			array(
				'name'             => 'post_author_override',
				'selected'         => empty( $post->ID ) ? $user_id : $post->post_author,
				'include_selected' => true,
				'capability'       => 'edit_documents',
			)
		);
	}
}
