<?php
/**
 * WP Document Revisions Rewrite Rules and Permalinks Trait
 *
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite rules and permalink functionality for WP_Document_Revisions.
 */
trait WP_Document_Revisions_Rewrites {

	/**
	 * Adds document CPT rewrite rules.
	 *
	 * @since 0.5
	 */
	public function inject_rules(): void {
		global $wp_rewrite;
		$wp_rewrite->add_rewrite_tag( '%document%', '([^.]+)\.[A-Za-z0-9]{1,7}?', 'document=' );
	}


	/**
	 * Adds document rewrite rules to the rewrite array.
	 *
	 * @since 0.5
	 * @param string[] $rules rewrite rules.
	 * @return string[] rewrite rules
	 */
	public function revision_rewrite( array $rules ): array {
		$slug = $this->document_slug();

		// remove any previous versions of file matches (will be added back if same).
		$rules = array_filter( $rules, array( $this, 'remove_old_rules' ), ARRAY_FILTER_USE_KEY );

		$my_rules = array();

		// These rules will define the trailing / as optional, as will be the extension (since it not used in the search).

		// document revisions in the form of [doc_slug]/yyyy/mm/[slug]-revision-##.[extension], [doc_slug]/yyyy/mm/[slug]-revision-##.[extension]/, [doc_slug]/yyyy/mm/[slug]-revision-##/ and [doc_slug]/yyyy/mm/[slug]-revision-##.
		$my_rules[ $slug . '/(\d{4})/(\d{1,2})/([^./]+)-' . __( 'revision', 'wp-document-revisions' ) . '-(\d+)(\.[A-Za-z0-9]{1,7})?/?$' ] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]&revision=$matches[4]';

		// document revision feeds in the form of yyyy/mm/[slug]-revision-##.[extension]/feed/, yyyy/mm/[slug]-revision-##/feed/, etc.
		$my_rules[ $slug . '/(\d{4})/(\d{1,2})/([^./]+)(\.[A-Za-z0-9]{1,7})?/feed/?$' ] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]&feed=feed';

		// documents in the form of [doc_slug]/yyyy/mm/[slug].[extension], [doc_slug]/yyyy/mm/[slug].[extension]/.
		$my_rules[ $slug . '/(\d{4})/(\d{1,2})/([^./]+)(\.[A-Za-z0-9]{1,7})?/?$' ] = 'index.php?year=$matches[1]&monthnum=$matches[2]&document=$matches[3]';

		// documents in the form of [doc_slug]/yyyy/mm/.
		$my_rules[ $slug . '/(\d{4})/(\d{1,2})?/?$' ] = 'index.php?post_type=document&year=$matches[1]&monthnum=$matches[2]';

		// and their pages.
		$my_rules[ $slug . '/(\d{4})/(\d{1,2})/page/?(\d{1,})/?$' ] = 'index.php?post_type=document&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]';

		// document revisions in the form of [doc_slug]/[slug]-revision-##.[extension], [doc_slug]/yyyy/mm/[slug]-revision-##.[extension]/, [doc_slug]/yyyy/mm/[slug]-revision-##/ and [doc_slug]/yyyy/mm/[slug]-revision-##.
		$my_rules[ $slug . '/([^./]+)-' . __( 'revision', 'wp-document-revisions' ) . '-(\d+)(\.[A-Za-z0-9]{1,7})?/?$' ] = 'index.php?document=$matches[1]&revision=$matches[2]';

		// document revision feeds in the form of [doc_slug]/[slug].[extension]/feed/, [doc_slug]/[slug]/feed/, etc.
		$my_rules[ $slug . '/([^./]+)(\.[A-Za-z0-9]{1,7})?/feed/?$' ] = 'index.php?document=$matches[1]&feed=feed';

		// documents in the form of [doc_slug]/[slug]##.[extension], [doc_slug]/[slug]##.[extension]/.
		$my_rules[ $slug . '/([^./]+)(\.[A-Za-z0-9]{1,7})?/?$' ] = 'index.php?document=$matches[1]';

		// site.com/documents/ should list all documents that user has access to (private, public).
		$my_rules[ $slug . '/?$' ]                = 'index.php?post_type=document';
		$my_rules[ $slug . '/page/?(\d{1,})/?$' ] = 'index.php?post_type=document&paged=$matches[1]';

		/**
		 * Filters the Document rewrite rules.
		 *
		 * @param array $my_rules Array of rewrite rules to add for for Documents.
		 * @param array $rules    Array of rewrite rules being built.
		 */
		$my_rules = apply_filters( 'document_rewrite_rules', $my_rules, $rules );

		return $my_rules + $rules;
	}


	/**
	 * Builds document post type permalink.
	 *
	 * @since 0.5
	 * @param string  $link      original permalink.
	 * @param WP_Post $document  post object.
	 * @param bool    $leavename whether to leave the %document% placeholder.
	 * @return string the real permalink
	 */
	public function permalink( string $link, WP_Post $document, bool $leavename ): string {
		global $wp_rewrite;
		$revision_num = false;

		// if this isn't our post type, kick.
		if ( ! $this->verify_post_type( $document ) ) {
			return $link;
		}

		// check if it's a revision.
		if ( 'revision' === $document->post_type ) {
			$parent            = clone get_post( $document->post_parent );
			$revision_num      = $this->get_revision_number( $document->ID );
			$parent->post_name = $parent->post_name . __( '-revision-', 'wp-document-revisions' ) . $revision_num;
			$document          = $parent;
		}

		// if no permastruct.
		if ( '' === $wp_rewrite->permalink_structure || empty( $document->post_name ) || in_array( $document->post_status, array( 'pending', 'draft' ), true ) ) {
			$link = site_url( '?post_type=document&p=' . $document->ID );
			if ( $revision_num ) {
				$link = add_query_arg( 'revision', $revision_num, $link );
			}
		} else {
			/**
			 * Filters the home_url() for WPML and translated documents.
			 *
			 * @param string  $home_url generated permalink.
			 * @param WP_Post $document document object.
			 */
			$home_url = apply_filters( 'document_home_url', home_url(), $document );

			// build documents(/yyyy/mm)/slug.
			$extension  = $this->get_file_type( $document );
			$year_month = ( get_option( 'document_link_date' ) ? '' : '/' . str_replace( '-', '/', substr( $document->post_date, 0, 7 ) ) );

			$link  = trailingslashit( $home_url ) . $this->document_slug() . $year_month . '/';
			$link .= ( $leavename ) ? '%document%' : $document->post_name;
			$link .= $extension;
			// add trailing slash if user has set it as their permalink.
			$link = user_trailingslashit( $link );
		}

		/**
		 * Filters the Document permalink.
		 *
		 * @param string  $link     generated permalink.
		 * @param WP_Post $document Post object.
		 */
		$link = apply_filters( 'document_permalink', $link, $document );

		return $link;
	}


	/**
	 * Tells WP to recognize document query vars.
	 *
	 * @since 0.5
	 * @param array $vars the query vars.
	 * @return array the modified query vars
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'revision';
		$vars[] = 'document';
		return $vars;
	}


	/**
	 * Callback called when the plugin is rewrite rules are flushed (including activation).
	 *
	 * @since 3.6
	 *
	 * @return void
	 */
	public function generate_rewrite_rules(): void {
		global $wp_rewrite;
		// forbid access to documents directly. Use a placeholder.
		$wp_rewrite->add_external_rule( 'WPDR', '-' );
	}

	/**
	 * Called when the htaccess rules have been created to stop document access (403 error).
	 *
	 * @since 3.6
	 *
	 * @param string $rules Mod_rewrite rewrite rules formatted for .htaccess.
	 */
	public function mod_rewrite_rules( string $rules ): string {
		// forbid access to documents directly.
		// Find the path.
		$home_root = wp_parse_url( home_url() );
		if ( isset( $home_root['path'] ) ) {
			$home_root = trailingslashit( $home_root['path'] );
		} else {
			$home_root = '/';
		}

		/**
		 * Filter to stop direct file access to documents (specify the URL element (or trailing part) to traverse to the document directory).
		 *
		 * See above for definition.
		 */
		$path_to = trailingslashit( $home_root . apply_filters( 'document_stop_file_access_pattern', '' ) );
		// check that the URL points to a file with an MD5 format name. If so, return Forbidden.
		$rules = preg_replace( '|RewriteRule \^WPDR ' . $home_root . '- \[QSA,L\]|', "RewriteCond %{REQUEST_FILENAME} -f\nRewriteRule $path_to(\d{4}/\d{2}/)?[a-f0-9]{32}(\.\w{1,7})?/?$ /- [F]", $rules );
		return $rules;
	}

	/**
	 * Filters permalink displayed on edit screen in the event that there is no attachment yet uploaded.
	 *
	 * @rerurns string modified HTML
	 * @since 0.5
	 * @param string      $html      original HTML.
	 * @param int         $id        Post ID.
	 * @param string|null $new_title New sample permalink title.
	 * @param string|null $new_slug  New sample permalink slug.
	 * @param WP_Post     $document  Post object.
	 * @return string
	 */
	public function sample_permalink_html_filter( string $html, int $id, ?string $new_title, ?string $new_slug, WP_Post $document ): string {
		// verify post type.
		if ( ! $this->verify_post_type( $document ) ) {
			return $html;
		}

		// grab attachments.
		$attachments = $this->get_attachments( $document );

		// if no attachments, return nothing.
		if ( empty( $attachments ) ) {
			return '';
		}

		// otherwise return html unfiltered.
		return $html;
	}

	/**
	 * Hides file's true location from users in the Gallery.
	 *
	 * @since 0.5
	 * @param string $link URL to file's tru location.
	 * @param int    $id attachment ID.
	 * @return string empty string
	 */
	public function attachment_link_filter( string $link, int $id ): string {

		if ( ! $this->verify_post_type( $id ) ) {
			return $link;
		}

		return '';
	}

	/**
	 * Rewrites a file URL to its public URL.
	 *
	 * @since 0.5
	 * @param array $file file object from WP.
	 * @return array modified file array
	 */
	public function rewrite_file_url( array $file ): array {
		// verify this is a document load as self::$doc_image has been set false if a WPDR document.
		check_admin_referer( 'upload-attachment' );
		if ( self::$doc_image ) {
			// not a WPDR Document.
			return $file;
		}

		global $post;
		$file['url'] = get_permalink( $post->ID );
		write_log( 'Document upload' . $file['url'] );

		return $file;
	}

	/**
	 * Tells WP to recognize workflow_state as a query vars.
	 *
	 * @since 3.3.0
	 * @param array $vars the query vars.
	 * @return array the modified query vars
	 */
	public function add_qv_workflow_state( array $vars ): array {
		$vars[] = 'workflow_state';
		return $vars;
	}

	/**
	 * Removes auto-appended trailing slash from document requests prior to serving.
	 *
	 * WordPress SEO rules properly dictate that all post requests should be 301 redirected with a trailing slash
	 * Because documents end with a phaux file extension, we don't want that unless there is a named extension
	 * Removes trailing slash from documents, while allowing all other SEO goodies to continue working.
	 *
	 * @param String $redirect    the redirect URL.
	 * @param bool   $do_redirect whether to redirect.
	 * @return String the redirect URL without the trailing slash
	 */
	public function redirect_canonical_filter( string $redirect, $do_redirect ): string {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->verify_post_type() ) {
			return $redirect;
		}

		// if the URL already has an extension, then no need to remove.
		$path = wp_parse_url( $redirect, PHP_URL_PATH );

		if ( preg_match( '#(^.+)\.[A-Za-z0-9]{1,7}/?$#', $path ) ) {
			return $redirect;
		}

		return untrailingslashit( $redirect );
	}

	/**
	 * Allows the post slug to be updated.
	 *
	 * Replaces the WordPress supplied one.
	 *
	 * @since 3.5.0
	 */
	public function update_post_slug_field(): void {
		check_ajax_referer( 'samplepermalink', 'samplepermalinknonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : 0;
		$title   = isset( $_POST['new_title'] ) ? sanitize_text_field( wp_unslash( $_POST['new_title'] ) ) : '';
		$slug    = isset( $_POST['new_slug'] ) ? sanitize_title( wp_unslash( $_POST['new_slug'] ) ) : null;

		if ( ! $this->verify_post_type( $post_id ) ) {
			// not a document so do nothing. If another function linked, then exit otherwise do as sample.
			return;
		}

		// Verify user can edit this document.
		if ( ! current_user_can( 'edit_document', $post_id ) ) {
			wp_die( esc_html__( 'Not authorized', 'wp-document-revisions' ) );
		}

		// update the post name with the slug and then the guid - direct in the database.
		$doc            = get_post( $post_id );
		$slug           = wp_unique_post_slug( $slug, $post_id, $doc->post_status, 'document', 0 );
		$doc->post_name = $slug;
		$guid           = $this->permalink( $doc->guid, $doc, false );

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$post_table = "{$wpdb->prefix}posts";
		$sql        = $wpdb->prepare(
			"UPDATE `$post_table` SET `post_name` = %s, guid = %s WHERE `id` = %d ",
			$slug,
			$guid,
			$post_id
		);
		$wpdb->query( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$this->clear_cache( $post_id, $doc, true );

		// phpcs:ignore WordPress.Security.EscapeOutput
		wp_die( wp_kses_post( get_sample_permalink_html( $post_id, $title, $slug ) ) );
	}

	/**
	 * Filter to remove previous rewrite rules.
	 *
	 * @since 3.3.0
	 * @param string $key key of rewrite rule.
	 */
	private function remove_old_rules( string $key ): bool {
		$slug = $this->document_slug();
		if ( 0 === strpos( $key, $slug . '/' ) ) {
			return false;
		}
		return true;
	}
}
