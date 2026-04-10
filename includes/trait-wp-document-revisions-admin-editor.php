<?php
/**
 * WP Document Revisions Admin Editor Trait
 *
 * @package WP_Document_Revisions
 */

/**
 * Admin editor functionality for WP_Document_Revisions_Admin.
 */
trait WP_Document_Revisions_Admin_Editor {

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
		add_meta_box( 'revision-summary', __( 'Revision Summary', 'wp-document-revisions' ), array( &$this, 'revision_summary_cb' ), 'document', 'normal', 'default' );
		add_meta_box( 'document', __( 'Document', 'wp-document-revisions' ), array( &$this, 'document_metabox' ), 'document', 'normal', 'high' );

		if ( ! empty( $post->post_content ) ) {
			add_meta_box( 'revision-log', __( 'Revision Log', 'wp-document-revisions' ), array( &$this, 'revision_metabox' ), 'document', 'normal', 'low' );
		}

		if ( taxonomy_exists( 'workflow_state' ) && ! $this->disable_workflow_states() ) {
			add_meta_box( 'workflow-state', __( 'Workflow State', 'wp-document-revisions' ), array( &$this, 'workflow_state_metabox_cb' ), 'document', 'side', 'default' );
		}

		// move author div to make room for ours.
		remove_meta_box( 'authordiv', 'document', 'normal' );

		// only add author div if user can give someone else ownership.
		if ( current_user_can( 'edit_others_documents' ) ) {
			add_meta_box( 'authordiv', __( 'Owner', 'wp-document-revisions' ), array( &$this, 'post_author_meta_box' ), 'document', 'side', 'low' );
		}

		// By default revisions are unlimited, but user filter may have limited number. Check if impact.
		add_action( 'admin_notices', array( &$this, 'check_document_revisions_limit' ) );

		// lock notice.
		add_action( 'admin_notices', array( &$this, 'lock_notice' ) );

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
	 * @param object $post the post object.
	 */
	public function document_metabox( object $post ): void {
		// convert old format to new.
		if ( is_numeric( $post->post_content ) ) {
			$post->post_content = $this->format_doc_id( $post->post_content );
		}
		?>
		<input type="hidden" id="post_content" name="post_content" value="<?php echo esc_attr( $post->post_content ); ?>" />
		<input type="hidden" id="curr_content" name="curr_content" value="Unset" />
		<?php
		$lock_holder = $this->get_document_lock( $post );
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
			<a href="media-upload.php?post_id=<?php echo intval( $post->ID ); ?>&TB_iframe=1" id="content-add_media" class="thickbox add_media button" title="<?php esc_attr_e( 'Upload Document', 'wp-document-revisions' ); ?>" onclick="return false;" >
				<?php esc_html_e( 'Upload New Version', 'wp-document-revisions' ); ?>
			</a>
		</div>
		<?php
		$latest_version = $this->get_latest_revision( $post->ID );
		if ( is_object( $latest_version ) ) {
			?>
			<p>
			<strong><?php esc_html_e( 'Latest Version of the Document', 'wp-document-revisions' ); ?></strong>
			<strong><a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_BLANK"><?php esc_html_e( 'Download', 'wp-document-revisions' ); ?></a></strong><br />
			<em>
			<?php
			$mod_date = $latest_version->post_modified;
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			// translators: %1$s is the post modified date in words, %2$s is the post modified date in time format, %3$s is how long ago the post was modified, %4$s is the author's name.
			printf( __( 'Checked in <abbr class="timestamp" title="%1$s" id="A%2$s">%3$s</abbr> ago by %4$s', 'wp-document-revisions' ), $mod_date, strtotime( $mod_date ), human_time_diff( (int) get_post_modified_time( 'U', true, $post->ID ), time() ), get_the_author_meta( 'display_name', $latest_version->post_author ) );
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</em>
			</p>
		<?php } ?>
		<div class="clear"></div>
		<?php
	}


	/**
	 * Forces autosave to load
	 * By default, if there's a lock on the post, auto save isn't loaded; we want it in case lock is overridden.
	 *
	 * @since 0.5
	 */
	public function enqueue_edit_scripts(): void {
		if ( ! $this->verify_post_type() ) {
			return;
		}

		wp_enqueue_script( 'autosave' );
		add_thickbox();
		wp_enqueue_script( 'media-upload' );
	}


	/**
	 * Callback to generate metabox for workflow state.
	 *
	 * @since 0.5
	 * @param object $post the post object.
	 */
	public function workflow_state_metabox_cb( object $post ): void {
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
		if ( ! $this->verify_post_type( $doc_id ) ) {
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
		do_action( 'change_document_workflow_state', $doc_id, $new_id );

		// Replacement action hook.
		do_action( 'document_change_workflow_state', $doc_id, $new_id, $old_id );
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

		// find the attachment id now (as content might be cached) and we might delete the cache.
		$content   = get_post_field( 'post_content', $doc_id );
		$attach_id = $this->extract_document_id( $content );

		// Let's work on Workflow state, Verify nonce.
		if ( ! isset( $_POST['workflow_state_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['workflow_state_nonce'] ) ), 'wp-document-revisions' ) ) {
			return;
		}
		$ws = ( isset( $_POST['workflow_state'] ) ? sanitize_text_field( wp_unslash( $_POST['workflow_state'] ) ) : '' );

		// Save it.
		wp_set_post_terms( $doc_id, array( $ws ), 'workflow_state' );

		// is the permalink useful.
		$doc_post = get_post( $doc_id );
		$new_guid = self::$parent->permalink( $doc_post->guid, $doc_post, false, '' );
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
			remove_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999, 3 );
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
	 * Enqueue admin JS and CSS files.
	 */
	public function enqueue(): void {
		// only include JS on document pages.
		if ( ! $this->verify_post_type() ) {
			return;
		}

		// translation strings.
		$data = array(
			'restoreConfirmation' => __( 'Are you sure you want to restore this revision? If you do, no history will be lost. This revision will be copied and become the most recent revision.', 'wp-document-revisions' ),
			'lockNeedle'          => __( 'is currently editing this', 'wp-document-revisions' ),
			'postUploadNotice'    => '<div id="message" class="updated" style="display:none"><p>' . __( 'File uploaded successfully. Add a revision summary below (optional) and press <strong>Update</strong> to save your changes.', 'wp-document-revisions' ) . '</p></div>',
			'postDesktopNotice'   => '<div id="message" class="update-nag" style="display:none"><p>' . __( 'After you have saved your document in your office software, <a href="#" onClick="location.reload();">reload this page</a> to see your changes.', 'wp-document-revisions' ) . '</p></div>',
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

		$wpdr = self::$parent;

		// Enqueue JS.
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
		wp_localize_script( 'wp_document_revisions', 'wp_document_revisions', $data );

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

		// verify that this is a new document.
		if ( ! isset( $post ) || ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) || strlen( $post->post_content ) > 0 ) {
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
		// switch off for documents.
		if ( $this->verify_post_type( $post ) ) {
			return false;
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

		// convert old format to new.
		if ( is_numeric( $post->post_content ) ) {
			$post->post_content = $this->format_doc_id( $post->post_content );
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
		if ( 'document' === $post->post_type || $this->verify_post_type( $post->ID ) ) {
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
	 * Filter the admin body class to add additional classes which we can use conditionally
	 * to style the page (e.g., when the document is locked).
	 *
	 * @param String $body_class the existing body class(es).
	 */
	public function admin_body_class_filter( string $body_class ) {
		global $post;

		if ( ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
			return $body_class;
		}

		$body_class .= ' document';

		if ( $this->get_document_lock( $post ) ) {
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'media-upload.php' === $pagenow && ( isset( $_GET['post_id'] ) ? $this->verify_post_type( sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) ) : false ) ) {
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
	 * @param object $post the post object.
	 */
	public function revision_metabox( object $post ): void {
		$can_edit_doc = current_user_can( 'edit_document', $post->ID );
		$revisions    = $this->get_revisions( $post->ID );
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

		$i = 0;
		foreach ( $revisions as $revision ) {
			++$i;
			if ( ! current_user_can( 'read_document', $revision->ID ) ) {
				continue;
			}
			// preserve original file extension on revision links.
			// this will prevent mime/ext security conflicts in IE when downloading.
			$attach = $this->get_document( $revision->ID );
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
			?>
			<tr>
				<td><a href="<?php echo esc_url( $fn ); ?>" title="<?php echo esc_attr( $revision->post_modified ); ?>" class="timestamp"><?php echo esc_html( human_time_diff( strtotime( $revision->post_modified_gmt ), time() ) ); ?></a></td>
				<td><?php echo esc_html( get_the_author_meta( 'display_name', $revision->post_author ) ); ?></td>
				<td><?php echo esc_html( $revision->post_excerpt ); ?></td>
				<?php if ( $can_edit_doc && $post->ID !== $revision->ID && $i > 2 ) { ?>
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
		<p style="padding-top: 10px;"><a href="<?php echo esc_url( add_query_arg( 'key', $key, get_post_comments_feed_link( $post->ID ) ) ); ?>"><?php esc_html_e( 'RSS Feed', 'wp-document-revisions' ); ?></a></p>
		<?php
	}

	/**
	 * Only load documents from Computer.
	 *
	 * @since 3.3
	 *
	 * @param string[] $_default_tabs An array of media tabs.
	 */
	public function media_upload_tabs_computer( array $_default_tabs ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $this->verify_post_type() && isset( $_GET['action'] ) ) {
			// keep just load from computer for the document (but not the thumbnail).
			unset( $_default_tabs['type_url'] );
			unset( $_default_tabs['gallery'] );
			unset( $_default_tabs['library'] );
		}

		return $_default_tabs;
	}

	/**
	 * Retrieves the most recent file attached to a post.
	 *
	 * @since 0.5
	 * @param int $post_id the parent post.
	 * @return object the attachment object
	 */
	public function get_latest_attachment( int $post_id ) {
		$attachments = $this->get_attachments( $post_id );

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
		$this->enqueue();
	}

	/**
	 * Identify the 'last but one' revision in case we will merge them.
	 *
	 * @param bool    $post_has_changed Whether the post has changed.
	 * @param WP_Post $last_revision    The last revision post object.
	 * @param WP_Post $post             The post object.
	 * @return bool.
	 */
	public function identify_last_but_one( bool $post_has_changed, WP_Post $last_revision, WP_Post $post ): bool {
		// only interested if post changed.
		if ( ! $post_has_changed ) {
			return $post_has_changed;
		}

		// verify post type.
		if ( ! $this->verify_post_type( $post->ID ) ) {
			return $post_has_changed;
		}

		// misuse of filter, but can use to determine whether the revisions can be merged.
		// keep revision if title or content (document linked only) changed. Also if author changed.
		if ( $post->post_title !== $last_revision->post_title || $post->post_author !== $last_revision->post_author ) {
			return true;
		}
		global $wpdr;
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
		 * @param int 0 number of seconds between revisions to NOT create an extra revision.
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
	 * @param object $post the post object.
	 */
	public function post_author_meta_box( object $post ): void {
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
