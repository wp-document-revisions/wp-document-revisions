<?php
/**
 * WP Document Revisions Admin Settings Trait
 *
 * @package WP_Document_Revisions
 */

/**
 * Admin settings functionality for WP_Document_Revisions_Admin.
 */
trait WP_Document_Revisions_Admin_Settings {

	/**
	 * Sanitize link_date option prior to saving.
	 *
	 * @since 3.5.0
	 *
	 * @param ?string $link_date value to represent whether to add the year/month into the permalink.
	 * @return string sanitized value
	 */
	public function sanitize_link_date( ?string $link_date ) {
		return (bool) $link_date;
	}


	/**
	 * Adds upload directory and document slug options to network admin page.
	 *
	 * @since 1.0
	 */
	public function network_settings_cb(): void {
		?>
		<h3><?php esc_html_e( 'Document Settings', 'wp-document-revisions' ); ?></h3>
		<table id="document_settings" class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Document Upload Directory', 'wp-document-revisions' ); ?></th>
				<td>
					<?php $this->upload_location_cb(); ?>
					<?php wp_nonce_field( 'network_document_upload_location', 'document_upload_location_nonce' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Document Slug', 'wp-document-revisions' ); ?></th>
				<td>
					<?php $this->document_slug_cb(); ?>
					<?php wp_nonce_field( 'network_document_slug', 'document_slug_nonce' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Document Link Date', 'wp-document-revisions' ); ?></th>
				<td>
					<?php $this->document_link_date_cb(); ?>
					<?php wp_nonce_field( 'network_document_link_date', 'document_link_date_nonce' ); ?>
				</td>
			</tr>
		</table>
		<?php
	}


	/**
	 * Callback to add RSS key field to profile page.
	 *
	 * @since 0.5
	 */
	public function rss_key_display(): void {
		$key = $this->get_feed_key();
		?>
		<div class="tool-box">
		<h2><?php esc_html_e( 'Feed Privacy', 'wp-document-revisions' ); ?></h2>
		<table class="form-table">
			<tr id="document_revisions_feed_key">
				<th><label for="feed_key"><?php esc_html_e( 'Secret Feed Key', 'wp-document-revisions' ); ?></label></th>
				<td>
					<input type="text" value="<?php echo esc_attr( $key ); ?>" class="regular-text" readonly="readonly" /><br />
					<span class="description"><?php esc_html_e( 'To protect your privacy, you need to append a key to feeds for use in feed readers.', 'wp-document-revisions' ); ?></span><br />
					<?php wp_nonce_field( 'generate-new-feed-key', '_document_revisions_nonce' ); ?>
					<?php submit_button( __( 'Generate New Key', 'wp-document-revisions' ), 'secondary', 'generate-new-feed-key', false ); ?>

				</td>
			</tr>
		</table>
		</div>
		<?php
	}


	/**
	 * Retrieves feed key user meta; generates if necessary.
	 *
	 * @since 0.5
	 * @param int $user (optional) UserID.
	 * @return string the feed key
	 */
	public function get_feed_key( ?int $user = null ): string {
		$key = get_user_option( $this->meta_key, $user );

		if ( ! $key ) {
			$key = $this->generate_new_feed_key();
		}

		return $key;
	}


	/**
	 * Filters documents from media galleries.
	 *
	 * @uses filter_media_where()
	 * @uses filter_media_join()
	 */
	public function filter_from_media(): void {
		global $pagenow;

		// verify the page.
		if ( 'upload.php' !== $pagenow && 'media-upload.php' !== $pagenow ) {
			return;
		}

		// note: hook late so that unattached filter can hook in, if necessary.
		add_filter( 'posts_join_paged', array( &$this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( &$this, 'filter_media_where' ), 20 );
	}


	/**
	 * Requires all document revisions to have attachments
	 * Prevents initial autosave drafts from appearing as a revision after document upload.
	 *
	 * @since 1.0
	 * @param int $revision_id the revision post id.
	 */
	public function revision_filter( int $revision_id ): void {
		// verify post type.
		if ( ! $this->verify_post_type( $revision_id ) ) {
			return;
		}

		$revision = get_post( $revision_id );
		// delete revision if there is no content.
		if ( 0 === strlen( $revision->post_content ) ) {
			global $wpdr;
			remove_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999, 3 );
			wp_delete_post_revision( $revision_id );
			add_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999, 3 );
			return;
		}

		// set last_revision (used in routine save_document to possibly merge revisions).
		self::$last_revn = $revision_id;
	}


	/**
	 * Deletes all attachments associated with a document or revision.
	 *
	 * This is called (for documents) after any remaining attachments have had their parent removed.
	 *
	 * @since 1.0
	 * @param int $post_id the id of the deleted post.
	 */
	public function delete_attachments_with_document( int $post_id ): void {
		if ( ! $this->verify_post_type( $post_id ) ) {
			return;
		}

		// relevant record could be document, revision or attachment.
		$record = get_post( $post_id );

		// not for an attachment or an autosave revision.
		if ( 'attachment' === $record->post_type || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// We will delete the attachment record related to this record.
		// Since all revisions are deleted with the document, all attachments will get deleted.
		$attach = $this->get_document( $post_id );
		if ( ! $attach ) {
			// no attachment.
			return;
		}

		// make sure that the attachment is not refered to by another document or revision post (ignore autosave).
		$doc_id = ( 'document' === $record->post_type ? $record->ID : $record->post_parent );
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$doc_link = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM $wpdb->posts WHERE %d IN (post_parent, id) AND (post_content = %d OR post_content LIKE %s ) AND post_name != %s ",
				$doc_id,
				$attach->ID,
				$this->format_doc_id( $attach->ID ) . '%',
				strval( $doc_id ) . '-autosave-v1'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		if ( '1' === $doc_link ) {
			// have to access the document upload directory, so add it. Also on delete file.
			add_filter( 'upload_dir', array( self::$parent, 'document_upload_dir_filter' ) );
			add_filter( 'wp_delete_file', array( $this, 'wp_delete_file' ) );

			// delete_attachment does not delete the attachment if document is outside uploads directory.
			$file = get_attached_file( $attach->ID );
			wp_delete_attachment( $attach->ID, true );

			// ensure attachment deleted.
			wp_delete_file( $file );

			// have looked for the upload directory, so remove it.
			remove_filter( 'upload_dir', array( self::$parent, 'document_upload_dir_filter' ) );
			remove_filter( 'wp_delete_file', array( $this, 'wp_delete_file' ) );

			// remove attachment ID from list.
			unset( self::$attachmts[ $attach->ID ] );
		}

		// If multiple files have been uploaded between saves then there will be attached files left [Edge case].
		if ( 'document' === $record->post_type ) {
			if ( ! empty( self::$attachmts ) ) {
				add_filter( 'upload_dir', array( self::$parent, 'document_upload_dir_filter' ) );
				add_filter( 'wp_delete_file', array( $this, 'wp_delete_file' ) );
				foreach ( self::$attachmts as $id => $value ) {
					// delete_attachment does not delete the attachment if document is outside uploads directory.
					$file = get_attached_file( $id );

					wp_delete_attachment( $id, true );

					// ensure attachment deleted.
					wp_delete_file( $file );
				}
				remove_filter( 'upload_dir', array( self::$parent, 'document_upload_dir_filter' ) );
				remove_filter( 'wp_delete_file', array( $this, 'wp_delete_file' ) );
			}
			// set the attachmts to null, so that being null means we are not deleting a document.
			self::$attachmts = null;
		}
	}


	/**
	 * Set up revisions on admin dashboard.
	 * @ since 3.0.1
	 */
	public function setup_dashboard(): void {
		wp_add_dashboard_widget(
			'wpdr_dashboard',
			__( 'Recently Revised Documents', 'wp-document-revisions' ),
			array(
				&$this,
				'dashboard_display',
			)
		);
	}


	/**
	 * Callback to display documents on admin dashboard.
	 * @ since 3.0.1
	 */
	public function dashboard_display(): void {
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}

		$query = array(
			'orderby'     => 'modified',
			'order'       => 'DESC',
			'numberposts' => 5,
		);

		$documents = $this->get_documents( $query );

		// no documents, don't bother.
		if ( ! $documents ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<ul>';

		foreach ( $documents as $document ) {
			$link = ( current_user_can( 'edit_document', $document->ID ) ) ? add_query_arg(
				array(
					'post'   => $document->ID,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			) : get_permalink( $document->ID );
			// translators: %1$s is the time ago in words, %2$s is the author, %3$s is the post status.
			$format_string = __( '%1$s ago by %2$s [%3$s]', 'wp-document-revisions' );
			?>
			<li>
				<a href="<?php echo esc_attr( $link ); ?>"><?php echo esc_html( get_the_title( $document->ID ) ); ?></a><br />
				<?php
				printf(
					esc_html( $format_string ),
					esc_html( human_time_diff( strtotime( $document->post_modified_gmt ), time() ) ),
					esc_html( get_the_author_meta( 'display_name', $document->post_author ) ),
					esc_html( ucwords( $document->post_status ) )
				);
				?>
			</li>
			<?php
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</ul>';
	}

	/**
	 * Registers the document settings.
	 *
	 * @since 0.5
	 */
	public function settings_fields(): void {
		register_setting( 'media', 'document_upload_directory', array( &$this, 'sanitize_upload_dir' ) );
		register_setting( 'media', 'document_slug', array( &$this, 'sanitize_document_slug' ) );
		add_settings_field( 'document_upload_directory', __( 'Document Upload Directory', 'wp-document-revisions' ), array( &$this, 'upload_location_cb' ), 'media', 'uploads' );
		add_settings_field( 'document_slug', __( 'Document Slug', 'wp-document-revisions' ), array( &$this, 'document_slug_cb' ), 'media', 'uploads' );
		register_setting(
			'media',
			'document_link_date',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( &$this, 'sanitize_link_date' ),
			)
		);
		add_settings_field(
			'document_link_date',
			__( 'Document Date in Permalink', 'wp-document-revisions' ),
			array( &$this, 'document_link_date_cb' ),
			'media',
			'uploads'
		);
	}

	/**
	 * Verifies that upload directory is a valid directory before updating the setting
	 * Attempts to create the directory if it does not exist.
	 *
	 * @since 1.0
	 * @param string $dir path to the new directory.
	 * @return bool|string false on fail, path to new dir on success
	 */
	public function sanitize_upload_dir( string $dir ) {
		// empty string passed.
		if ( '' === $dir ) {
			return $this->document_upload_dir();
		}

		// if the path is not absolute (Linux and Windows tests), assume it's relative to ABSPATH.
		if ( 0 !== strpos( $dir, '/' ) && ! preg_match( '|^.:\\\|', $dir ) ) {
			$dir = ABSPATH . $dir;
		}

		// dir didn't change.
		if ( $this->document_upload_dir() === $dir ) {
			return $dir;
		}

		// don't fire more than once.
		if ( ! get_settings_errors( 'document_upload_directory' ) ) {
			if ( ! is_multisite() ) {
				// does directory exist.
				if ( ! is_dir( $dir ) ) {
					add_settings_error( 'document_upload_directory', 'document-upload-dir-exists', __( 'Document directory does not appear to exist. Please review value.', 'wp-document-revisions' ), 'updated' );
				} elseif ( ! wp_is_writable( $dir ) ) {
					add_settings_error( 'document_upload_directory', 'document-upload-dir-write', __( 'Document directory is not writable. Please check permissions.', 'wp-document-revisions' ), 'updated' );
				}
			}
			// dir changed, throw warning.
			add_settings_error( 'document_upload_directory', 'document-upload-dir-change', __( 'Document upload directory changed, but existing uploads may need to be moved to the new folder to ensure they remain accessible.', 'wp-document-revisions' ), 'updated' );
		}

		// update plugin cache with new value.
		global $wpdr;
		$wpdr::$wpdr_document_dir = trailingslashit( trim( $dir ) );

		// trim and return.
		return $wpdr::$wpdr_document_dir;
	}

	/**
	 * Sanitize slug prior to saving.
	 *
	 * @param string $slug new slug.
	 * @return string sanitized slug
	 */
	public function sanitize_document_slug( string $slug ): string {
		$slug = sanitize_title( $slug, 'documents' );

		// unchanged.
		if ( $slug === $this->document_slug() ) {
			return $slug;
		}

		// new slug isn't yet stored
		// but queue up a rewrite rule flush to ensure slug takes effect on next request.
		add_action( 'shutdown', 'flush_rewrite_rules' );

		add_settings_error( 'document_slug', 'document-slug-change', __( 'Document slug changed, but some previously published URLs may now be broken.', 'wp-document-revisions' ), 'updated' );

		return $slug;
	}

	/**
	 * Callback to validate and save the network upload directory.
	 *
	 * @since 1.0
	 */
	public function network_upload_location_save(): void {
		if ( ! isset( $_POST['document_upload_location_nonce'] ) ) {
			return;
		}

		// verify nonce, auth.
		// phpcs:ignore  WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['document_upload_location_nonce'] ) ), 'network_document_upload_location' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify upload dir.
		$upload_dir = ( isset( $_POST['document_upload_directory'] ) ? sanitize_text_field( wp_unslash( $_POST['document_upload_directory'] ) ) : '' );
		$dir        = $this->sanitize_upload_dir( $upload_dir );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient.
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the dir is valid, save it.
		if ( $dir ) {
			update_site_option( 'document_upload_directory', $dir );
			// update plugin cache with new value.
			global $wpdr;
			$wpdr::$wpdr_document_dir = trailingslashit( trim( $dir ) );
		}
	}

	/**
	 * Callback to validate and save slug on network settings page.
	 */
	public function network_slug_save(): void {
		if ( ! isset( $_POST['document_slug_nonce'] ) ) {
			return;
		}

		// verify nonce, auth.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['document_slug_nonce'] ) ), 'network_document_slug' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// verify upload dir.
		$slug = ( isset( $_POST['document_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['document_slug'] ) ) : '' );
		$slug = $this->sanitize_document_slug( $slug );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient.
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the slug is valid, save it.
		if ( ! empty( $slug ) ) {
			update_site_option( 'document_slug', $slug );
		}
	}

	/**
	 * Callback to validate and save link date on network settings page.
	 */
	public function network_link_date_save(): void {
		if ( ! isset( $_POST['document_link_date_nonce'] ) ) {
			return;
		}

		// verify nonce, auth.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['document_link_date_nonce'] ) ), 'network_document_link_date' ) || ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// get link date value.
		$link_date = ( isset( $_POST['document_link_date'] ) ? sanitize_text_field( wp_unslash( $_POST['document_link_date'] ) ) : '' );
		$link_date = $this->sanitize_document_link_date( $link_date );

		// because there's a redirect, and there's no Settings API, force settings errors into a transient.
		global $wp_settings_errors;
		set_transient( 'settings_errors', $wp_settings_errors );

		// if the value has changed, save it.
		if ( get_site_option( 'document_link_date' ) !== $link_date ) {
			update_site_option( 'document_link_date', $link_date );
		}
	}

	/**
	 * Adds settings errors to network settings page for document upload directory CB.
	 *
	 * @since 1.0
	 */
	public function network_settings_errors(): void {
		settings_errors( 'document_upload_directory' );
		settings_errors( 'document_slug' );
		settings_errors( 'document_link_date' );
	}

	/**
	 * Appends the settings-updated query arg to the network admin settings redirect so that the settings API can work.
	 *
	 * @since 1.0
	 * @param string $location the URL being redirected to.
	 * @return string the modified location
	 */
	public function network_settings_redirect( string $location ): string {
		// Verify redirect string from /wp-admin/network/edit.php line 164.
		if ( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php' ) ) === $location ) {
			// append the settings-updated query arg and return.
			$location = add_query_arg( 'settings-updated', 'true', $location );
		}

		return $location;
	}

	/**
	 * Callback to create the upload location settings field.
	 *
	 * @since 0.5
	 */
	public function upload_location_cb(): void {
		?>
		<input name="document_upload_directory" type="text" id="document_upload_directory" value="<?php echo esc_attr( $this->document_upload_dir() ); ?>" class="large-text code" /><br />
		<span class="description">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
		_e( 'Directory in which to store uploaded documents. The default is in your <code>wp-content/uploads</code> folder (or another default uploads folder defined elsewhere), but it may be moved to a folder outside of the <code>htdocs</code> or <code>public_html</code> folder for added security.', 'wp-document-revisions' );
		?>
		</span>
		<?php if ( is_multisite() ) : ?>
		<span class="description">
			<?php
			// phpcs:disable WordPress.Security.EscapeOutput.UnsafePrintingFunction
			// translators: %site_id% is not interpolated and should not be translated.
			_e( 'You may optionally include the string <code>%site_id%</code> within the path to separate files by site.', 'wp-document-revisions' );
			// phpcs:enable WordPress.Security.EscapeOutput.UnsafePrintingFunction
			?>
		</span>
			<?php
		endif;
	}

	/**
	 * Callback to create the document slug settings field
	 */
	public function document_slug_cb(): void {
		// phpcs:ignore
		$year_month = ( get_site_option( 'document_link_date' ) ? '' : '/' . date( 'Y/m' ) );
		?>
		<code><?php echo esc_html( trailingslashit( home_url() ) ); ?><input name="document_slug" type="text" id="document_slug" value="<?php echo esc_attr( $this->document_slug() ); ?>" class="medium-text" /><?php echo esc_html( $year_month ); ?>/<?php esc_html_e( 'example-document-title', 'wp-document-revisions' ); ?>.txt</code><br />
		<span class="description">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.UnsafePrintingFunction
		_e( '"Slug" with which to prefix all URLs for documents (and the document archive). Default is <code>documents</code>.', 'wp-document-revisions' );
		echo '<br />';
		echo '</span>';
	}

	/**
	 * Adds link_date option to permalink page.
	 *
	 * @since 3.5.0
	 */
	public function document_link_date_cb(): void {
		?>
		<label for="document_link_date">
		<input name="document_link_date" type="checkbox" id="document_link_date" value="1" <?php checked( '1', get_site_option( 'document_link_date' ) ); ?> />
		<?php esc_html_e( 'Remove the year and month element /yyyy/mm from the document permalink.', 'wp-document-revisions' ); ?></label><br />
		<span class="description">
		<?php esc_html_e( 'By default the document permalink will contain the post year and month.', 'wp-document-revisions' ); ?><br />
		<?php esc_html_e( 'The delivered rewrite rules support both formats.', 'wp-document-revisions' ); ?>
		</span>
		<?php
	}

	/**
	 * Binds our post-upload javascript callback to the plupload event
	 *
	 * Note: in footer because it has to be called after handler.js is loaded and initialized.
	 *
	 * @since 1.2.1
	 */
	public function bind_upload_cb(): void {
		global $pagenow;

		if ( 'media-upload.php' === $pagenow ) {
			// Change event to load to let all js get loaded/initialised.
			?>
			<script type="text/javascript">
				window.addEventListener('load', function() {
					if ( typeof window.WPDocumentRevisions === "undefined" ) {
						window.WPDocumentRevisions = new WPDocumentRevisions();
					}
				});
			</script>
			<?php
		}
	}

	/**
	 * Generates, saves, and returns new feed key.
	 *
	 * @since 0.5
	 * @param int $user (optional) UserID.
	 * @return string feed key
	 */
	public function generate_new_feed_key( ?int $user = null ): string {
		if ( ! $user ) {
			$user = get_current_user_id();
		}

		$key = wp_generate_password( $this->key_length, false, false );
		update_user_option( $user, $this->meta_key, $key );

		return $key;
	}

	/**
	 * Callback to handle profile updates.
	 *
	 * @since 0.5
	 */
	public function profile_update_cb(): void {
		if ( isset( $_POST['generate-new-feed-key'] ) && isset( $_POST['_document_revisions_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_document_revisions_nonce'] ) ), 'generate-new-feed-key' ) ) {
			$this->generate_new_feed_key();
		}
	}

	/**
	 * Ensures that an error box appears if the revisions for a post has reached a system limit.
	 *
	 * @since 3.2.2
	 */
	public function check_document_revisions_limit(): void {
		global $post;

		if ( ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) ) {
			return;
		}

		$num = wp_revisions_to_keep( $post );

		if ( 0 === $num ) {
			// setting revisions to 0 makes no sense for this plugin.
			?>
			<div class="notice notice-error"><p>
			<?php
			esc_html_e( 'Maximum number of document revisions set to zero using a filter. Check your configuration.', 'wp-document-revisions' );
			?>
			</p></div>
			<?php

		} elseif ( 0 < $num ) {
			// need to check that we're not at the limit.
			$revisions = count( wp_get_post_revisions( $post->ID ) );

			if ( $num < $revisions ) {
				?>
				<div class="notice notice-error"><p>
				<?php
				esc_html_e( 'More revisions exist for this document than is permitted. Making changes will delete data.', 'wp-document-revisions' );
				?>
				</p></div>
				<?php

			} elseif ( $num <= $revisions ) {
				?>
				<div class="notice notice-error"><p>
				<?php
				esc_html_e( 'Maximum number of revisions reached for this document. Making changes will delete data.', 'wp-document-revisions' );
				?>
				</p></div>
				<?php

			}
		}
	}

	/**
	 * Joins wp_posts on itself so posts can be filter by post_parent's type.
	 *
	 * @param string $join the original join statement.
	 * @return string the modified join statement
	 */
	public function filter_media_join( string $join ): string {
		global $wpdb;

		$join .= " LEFT OUTER JOIN {$wpdb->posts} wpdr_post_parent ON wpdr_post_parent.ID = {$wpdb->posts}.post_parent";

		return $join;
	}

	/**
	 * Exclude children of documents from query.
	 *
	 * @param string $where the original where statement.
	 * @return string the modified where statement
	 */
	public function filter_media_where( string $where ): string {
		global $wpdb;

		$where .= " AND ( wpdr_post_parent.post_type IS NULL OR wpdr_post_parent.post_type != 'document' )";

		return $where;
	}

	/**
	 * Filters documents from the media grid view when queried via Ajax. This uses
	 * the same filters from the list view applied in `filter_from_media()`.
	 *
	 * @param Object $query the WP_Query object.
	 * @return mixed
	 */
	public function filter_from_media_grid( $query ) {
		// note: hook late so that unattached filter can hook in, if necessary.
		add_filter( 'posts_join_paged', array( $this, 'filter_media_join' ) );
		add_filter( 'posts_where_paged', array( $this, 'filter_media_where' ), 20 );

		return $query;
	}

	/**
	 * Creates a list of all document attachments. Some may not be attached to a document or revision.
	 *
	 * @since 3.7
	 * @param int $post_id the id of the deleted post.
	 */
	public function list_attachments_with_document( int $post_id ): void {
		$record = get_post( $post_id );
		if ( 'document' !== $record->post_type ) {
			return;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$attachmts       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent = %d AND post_type = 'attachment'",
				$post_id
			),
			ARRAY_A
		);
		self::$attachmts = wp_list_pluck( $attachmts, 'ID', 'ID' );
	}

	/**
	 * Are we in the process of deleting documents or their revisions.
	 *
	 * @since 3.7
	 * @return bool
	 */
	public function is_deleting(): bool {
		return ( ! is_null( self::$attachmts ) );
	}

	/**
	 * Ensure file delete uses document directory...
	 *
	 * @since 0.5
	 * @param string $file Path to the file to delete.
	 */
	public function wp_delete_file( string $file ) {
		global $wpdr;
		$std_dir = $wpdr::$wp_default_dir['basedir'];
		$doc_dir = $wpdr::$wpdr_document_dir;
		if ( $doc_dir !== $std_dir ) {
			$file = str_ireplace( $std_dir, $doc_dir, $file );
		}
		return $file;
	}

	/**
	 * Remove all hooks that activate workflow state support
	 * use filter `document_use_workflow_states` to disable.
	 */
	public function disable_workflow_states() {
		if ( self::$parent->use_workflow_states() ) {
			return false;
		}

		remove_action( 'set_object_terms', array( &$this, 'workflow_state_save' ) );

		// Have changed taxonomy key for EF/PP support, so switch off make private.
		if ( ! empty( self::$parent->taxonomy_key() ) && 'workflow_state' !== self::$parent->taxonomy_key() ) {
			remove_action( 'admin_head', array( &$this, 'make_private' ) );
		}
		return true;
	}
}
