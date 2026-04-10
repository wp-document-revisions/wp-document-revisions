<?php
/**
 * WP Document Revisions File Handling Trait
 *
 * @package WP_Document_Revisions
 */

/**
 * File serving, uploads, and attachment handling functionality for WP_Document_Revisions.
 */
trait WP_Document_Revisions_File_Handler {

	/**
	 * Serves document files.
	 *
	 * @since 0.5
	 * @param String $template the requested template.
	 * @return String the resolved template
	 */
	public function serve_file( string $template ) {
		global $post;
		global $wp_query;
		global $wp;

		if ( ! is_single() ) {
			return $template;
		}

		if ( ! $this->verify_post_type( $post ) ) {
			return $template;
		}

		// if this is a passworded document and no password is sent
		// use the normal template which should prompt for password.
		if ( post_password_required( $post ) ) {
			return $template;
		}

		// grab the post revision if any.
		$version = get_query_var( 'revision' );

		// if there's not a post revision given, default to the latest.
		if ( ! $version ) {
			$revn = $this->get_latest_revision( $post->ID );
			if ( false === $revn ) {
				// no revision.
				wp_die(
					esc_html__( 'No document file is attached.', 'wp-document-revisions' ),
					null,
					array( 'response' => 403 )
				);
				// for unit testing.
				$wp_query->is_404 = true;
				return false;
			}
			$rev_id = $revn->ID;
		} else {
			$rev_id = $this->get_revision_id( $version, $post->ID );
		}

		// ensure we use the document upload directory.
		self::$doc_image = false;

		// get the attachment (id in post_content of rev_id).
		$attach = $this->get_document( $rev_id );
		$exists = ( $attach instanceof WP_Post );

		/*
		 * Filter the attachment post to serve (Return false to stop display).
		 *
		 * @param WP_Post $attach Attachment Post corresponding to document / revisions selected.
		 * @param int     $rev_id Id of document / revision selected.
		 */
		$attach = apply_filters( 'document_serve_attachment', $attach, $rev_id );

		if ( $attach instanceof WP_Post ) {
			$file = get_attached_file( $attach->ID );
		} else {
			// create message on failure to find attachment. (More banal if one filters to false).
			$msg = ( $exists ? __( 'Document is not available.', 'wp-document-revisions' ) : __( 'No document file is attached.', 'wp-document-revisions' ) );
			wp_die(
				esc_html( $msg ),
				null,
				array( 'response' => 403 )
			);
			// for unit testing.
			$wp_query->is_404 = true;
			return false;
		}

		// flip slashes for WAMP settups to prevent 404ing on the next line.
		/**
		 * Filters the file name for WAMP settings (filter routine provided by plugin).
		 *
		 * @param string $file attached file name.
		 */
		$file = apply_filters( 'document_path', $file );

		// return 404 if the file is a dud or malformed.
		if ( ! is_file( $file ) ) {

			// this will send 404 and no cache headers
			// and tell wp_query that this is a 404 so that is_404() works as expected
			// and theme formats appropriately.
			$wp_query->posts          = array();
			$wp_query->queried_object = null;
			$wp_query->is_404         = true;
			$wp->handle_404();

			// tell WP to serve the theme's standard 404 template, this is a filter after all...
			return get_404_template();

		}

		// note: authentication is happening via a hook here to allow shortcircuiting.
		/**
		 * Filters the decision to serve the document through WP Document Revisions.
		 *
		 * I.e. return null if user not logged on and want to deny existence.
		 * (only if filter 'document_read_uses_read' returns false)
		 *
		 * @param boolean true     default action to serve file.
		 * @param object  $post    WP Post to be served.
		 * @param string  $version Document revision.
		 */
		$serve_file = apply_filters( 'serve_document_auth', true, $post, $version );
		if ( ! $serve_file ) {
			if ( false === $serve_file ) {
				wp_die(
					esc_html__( 'You are not authorized to access that file.', 'wp-document-revisions' ),
					null,
					array( 'response' => 403 )
				);
				// for unit testing.
				$wp_query->is_404 = true;
				return false;
			} else {
				// not logged on, deny file existence (as above).
				$wp_query->posts          = array();
				$wp_query->queried_object = null;
				$wp_query->is_404         = true;
				$wp->handle_404();

				// tell WP to serve the theme's standard 404 template, this is a filter after all...
				return get_404_template();
			}
		}

		/**
		 * Action hook when the document is served.
		 *
		 * @param integer $post->ID     Post id of the document.
		 * @param string  $file         File name to be served.
		 */
		do_action( 'serve_document', $post->ID, $file );

		/**
		 * Filters file name of document to be served. (Useful if file is encrypted at rest).
		 *
		 * @param string  $file       File name to be served.
		 * @param integer $post->ID   Post id of the document.
		 * @param integer $attach->ID Post id of the attachment.
		 */
		$file = apply_filters( 'document_serve', $file, $post->ID, $attach->ID );

		// We may override this later.
		status_header( 200 );

		// fake the filename.
		$filename  = $post->post_name;
		$filename .= ( '' === $version ) ? '' : __( '-revision-', 'wp-document-revisions' ) . $version;

		// we want the true attachment URL, not the permalink, so temporarily remove our filter.
		remove_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ) );
		$filename .= $this->get_extension( wp_get_attachment_url( $attach->ID ) );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );

		$headers = array();

		// Set content-disposition header. Two options here:
		// "attachment" -- force save-as dialog to pop up when file is downloaded (pre 1.3.1 default)
		// "inline" -- attempt to open in browser (e.g., PDFs), if not possible, prompt with save as (1.3.1+ default).
		$disposition = ( apply_filters( 'document_content_disposition_inline', true ) ) ? 'inline' : 'attachment';

		$headers['Content-Disposition'] = $disposition . '; filename="' . $filename . '"';

		// get the mime type.
		$mimetype = $this->get_doc_mimetype( $file );

		// Set the Content-Type header if a mimetype has been detected or provided.
		if ( is_string( $mimetype ) ) {
			$headers['Content-Type'] = $mimetype;
		}

		// uncompressed file length.
		$filesize = filesize( $file );

		// Will we use gzip or deflate output? Do this early as can impact headers and these need outputting before any output.
		// Does the user accept gzip or deflate?
		$gzip_dflt = false;
		if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
			// phpcs:ignore
			$encoding = strtolower( $_SERVER['HTTP_ACCEPT_ENCODING'] );
			if ( substr_count( $encoding, 'gzip' ) || substr_count( $encoding, 'x-gzip' ) ) {
				$gzip_dflt = true;
				$comp_type = 'gzip';
			} elseif ( substr_count( $encoding, 'deflate' ) ) {
				$gzip_dflt = true;
				$comp_type = 'deflate';
			}
		}

		/**
		 * Filter to determine if gzip should be used to serve file (subject to browser negotiation).
		 *
		 * Note: Use `add_filter( 'document_serve_use_gzip', '__return_true' )` to shortcircuit.
		 *       This is always subject to browser negociation.
		 *
		 * @param bool    $gzip_dflt Whether gzip is supported by the client.
		 * @param string  $mimetype  Mime type to be served.
		 * @param integer $filesize  File size.
		 */
		$compress = apply_filters( 'document_serve_use_gzip', $gzip_dflt, $mimetype, $filesize );

		$headers['Content-Length'] = $filesize;
		if ( $compress ) {
			// request compression. Remove Length as possibly wrong and HTTP/2 fails if length wrong.
			// phpcs:ignore
			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) && '1' < substr( $_SERVER['SERVER_PROTOCOL'], 5, 1 ) ) {
						unset( $headers['Content-Length'] );
			}
		}

		// modified time - use to determine if already loaded.
		$last_modified            = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
		$etag                     = '"' . md5( $last_modified ) . '"';
		$headers['Last-Modified'] = $last_modified . ' GMT';
		$headers['ETag']          = $etag;
		$headers['Cache-Control'] = 'no-cache';

		// could be compressed or not depending on browser capability.
		$headers['Vary'] = 'Accept-Encoding';

		// Support for Conditional GET.
		$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) ) : false;

		// if DEFLATE was used to compress output, etag is modified by adding '-gzip' to our etag.
		if ( '-gzip"' === substr( $client_etag, -6 ) ) {
			$client_etag = substr( $client_etag, 0, -6 ) . '"';
		}

		if ( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
		}

		$client_last_modified = trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) );

		// If string is empty, return 0. If not, attempt to parse into a timestamp.
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

		// Make a timestamp for our most recent modification...
		$modified_timestamp = strtotime( $last_modified );

		if ( ( $client_last_modified && $client_etag )
			? ( ( $client_modified_timestamp >= $modified_timestamp ) && ( $client_etag === $etag ) )
			: ( ( $client_modified_timestamp >= $modified_timestamp ) || ( $client_etag === $etag ) )
		) {
			// no content with a 304, other header needed.
			unset( $headers['Content-Length'] );
			$this->serve_headers( $headers, $file );
			status_header( 304 );
			return $template;
		}

		// in case this is a large file, remove PHP time limits.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@set_time_limit( 0 );

		// In normal operation, corruption can occur if ouput is written by any other process.
		// However, when doing PHPUnit testing, this will occur, so we need to check whether we are in a test harness.
		$under_test = class_exists( 'WP_UnitTestCase' );

		if ( $under_test ) {
			// Under test. We know that we have done an ob_start, so remove buffer prior to open another.
			ob_end_clean();
		} else {
			// clear any existing output buffer(s) to prevent other plugins from corrupting the file.
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				ob_end_clean();
			}

			// If any output has been generated (by another plugin), it could cause corruption.
			/**
			 * Filter to serve file even if output already written.
			 *
			 * Note: Use `add_filter( 'document_output_sent_is_ok', '__return_true' )` to shortcircuit.
			 *
			 * @param bool $debug Set to false.
			 */
			if ( ! apply_filters( 'document_output_sent_is_ok', false ) ) {
				// oops, at least one still there,  deleted and contains data.
				if ( ob_get_level() > 0 && ob_get_length() > 0 ) {
					wp_die( esc_html__( 'Sorry, Output buffer exists with data. Filewriting suppressed.', 'wp-document-revisions' ) );
				}

				// data may already have been flushed so should error.
				if ( headers_sent() ) {
					// normal case is to fail as can cause corrupted output.
					wp_die( esc_html__( 'Sorry, Output has already been written, so your file cannot be downloaded.', 'wp-document-revisions' ) );
				}
			}
		}

		$buffsize = 0;

		if ( ! $compress ) {
			/**
			 * Filter to define uncompressed file writing buffer size (Default 0 = No buffering).
			 *
			 * Note: This is always subject to browser negotiation.
			 *
			 * @param integer $buffsize  0 (no intermediate flushing).
			 * @param integer $filesize  File size.
			 */
			$buffsize = apply_filters( 'document_buffer_size', $buffsize, $filesize );
		}

		// Make sure that there is a buffer to be written on close.
		ob_start( null, $buffsize );

		// If we made it this far, just serve the file
		// Note: We use readfile, and not WP_Filesystem for memory/performance reasons.
		if ( $compress ) {
			if ( 'gzip' === $comp_type ) {
				// phpcs:ignore WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions
				echo gzencode( file_get_contents( $file ), 9 );
				$headers['Content-Encoding'] = 'gzip';
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions
				echo gzdeflate( file_get_contents( $file ), 9 );
				$headers['Content-Encoding'] = 'deflate';
			}
			if ( array_key_exists( 'Content-Length', $headers ) ) {
				// only update to the correct value.
				$headers['Content-Length'] = ob_get_length();
			}
			// only know the length after writing to buffer, so only output headers now.
			$this->serve_headers( $headers, $file );
		} else {
			// Check file readability before committing to response headers.
			if ( ! is_readable( $file ) ) {
				status_header( 500 );
				if ( $under_test ) {
					return $template;
				}
				exit;
			}

			// know the headers and buffering may cause writing, so output headers first.
			$this->serve_headers( $headers, $file );
			// see if PHP readfile could be used.
			/**
			 * Filter whether WP_FileSystem used to serve document (or PHP readfile). Irrelevant of compressed on output.
			 *
			 * Note: Use `add_filter( 'document_use_wp_filesystem', '__return_true' )` to shortcircuit.
			 *
			 * @param bool    $default    false unless overridden by prior filter.
			 * @param string  $file       File name to be served.
			 * @param integer $post->ID   Post id of the document.
			 * @param integer $attach->ID Post id of the attachment.
			 */
			$file_served = false;
			if ( apply_filters( 'document_use_wp_filesystem', false, $file, $post->ID, $attach->ID ) ) {
				// try WP_filesystem for $doc_dir.
				// file code may not be already loaded.
				if ( ! function_exists( 'get_filesystem_method' ) ) {
					include ABSPATH . 'wp-admin/includes/file.php';
				}
				$method = get_filesystem_method( array(), dirname( $file ), false );

				if ( 'direct' === $method ) {
					// can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL.
					$creds = request_filesystem_credentials( site_url() . '/wp-admin/', $method, false, dirname( $file ), array(), false );

					// initialize the API.
					if ( WP_Filesystem( $creds ) ) {
						// all good so far.
						global $wp_filesystem;

						// downloading a file, not normally WP text so don't sanitize.
						$contents = $wp_filesystem->get_contents( $file );
						if ( false !== $contents ) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $contents;
							$file_served = true;
						}
						// Fall through to readfile if get_contents fails.
					}
				}
			}

			if ( ! $file_served ) {
				// Serve the file via readfile.
				// Note: We use default readfile, and not WP_Filesystem for memory/performance reasons.

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				readfile( $file );
			}
		}

		/**
		 * Action hook after the document is served.
		 *
		 * Useful to delete temporary file.
		 *
		 * Strictly output is not yet written, but file no longer needed.
		 *
		 * @param string  $file        File name that was served.
		 * @param integer $attach->ID  Post id of the attachment.
		 */
		do_action( 'document_serve_done', $file, $attach->ID );

		// successful call, exit to avoid anything adding to output unless in PHPUnit test mode.
		if ( $under_test ) {
			return $template;
		}

		// opened buffer, so flush output.
		ob_end_flush();

		exit;
	}


	/**
	 * Filter to authenticate document delivery.
	 *
	 * @param bool     $deflt   true unless overridden by prior filter.
	 * @param obj      $post    the post object.
	 * @param bool|int $version version of the document being served, if any.
	 * @return unknown
	 */
	public function serve_document_auth( bool $deflt, $post, $version ) {
		$user     = wp_get_current_user();
		$ret_null = ( 0 === $user->ID && ! apply_filters( 'document_read_uses_read', true ) );
		// public file, not a revision, no need to go any further
		// note: non-authenticated users only have the "read" cap, so can't auth via read_document.
		if ( ! $version && 'publish' === $post->post_status ) {
			if ( 0 === $user->ID && apply_filters( 'document_read_uses_read', true ) ) {
				// Not logged on. But only default read capability.
				return $deflt;
			}
		}

		// need to check access.
		// attempting to access a revision.
		if ( $version && ! current_user_can( 'read_document_revisions' ) ) {
			return ( $ret_null ? null : false );
		}

		// specific document cap check.
		if ( ! current_user_can( 'read_document', $post->ID ) ) {
			return ( $ret_null ? null : false );
		}

		return $deflt;
	}


	/**
	 * Calculated path to upload documents.
	 *
	 * @since 0.5
	 * @return string path to document
	 */
	public function document_upload_dir(): string {
		if ( ! is_null( self::$wpdr_document_dir ) ) {
			return self::$wpdr_document_dir;
		}

		// If no options set, default to normal upload dir.
		$dir = get_site_option( 'document_upload_directory' );
		if ( ! ( $dir ) ) {
			self::$wpdr_document_dir = self::$wp_default_dir['basedir'];
			return self::$wpdr_document_dir;
		}

		self::$wpdr_document_dir = $dir;
		if ( ! is_multisite() ) {
			return $dir;
		}

		// make site specific on multisite.
		if ( is_multisite() && ! is_network_admin() ) {
			if ( is_main_site() && get_current_network_id() === get_main_network_id() ) {
				$dir = str_replace( '/sites/%site_id%', '', $dir );
			}

			global $wpdb;
			$dir                     = str_replace( '%site_id%', $wpdb->blogid, $dir );
			self::$wpdr_document_dir = $dir;
		}

		return $dir;
	}


	/**
	 * Rewrites uploaded revisions filename with secure hash to mask true location.
	 *
	 * @since 0.5
	 * @param array $file file data from WP.
	 * @return array $file file with new filename
	 */
	public function filename_rewrite( array $file ): array {
		// verify this is a document.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['post_id'] ) || ! $this->verify_post_type( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) ) ) {
			self::$doc_image = true;
			return $file;
		}

		// Ignore if dealing with thumbnail on document page (File upload has type set as 'file').
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['type'] ) || 'file' !== $_POST['type'] ) {
			self::$doc_image = true;
			return $file;
		}

		global $pagenow;

		if ( 'async-upload.php' === $pagenow ) {
			// got past cookie, but may be in thumbnail code.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			$trace     = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$functions = array(
				'wp_ajax_get_post_thumbnail_html',
				'_wp_post_thumbnail_html',
				'post_thumbnail_meta_box',
			);
			foreach ( $trace as $traceline ) {
				if ( in_array( $traceline['function'], $functions, true ) ) {
					self::$doc_image = true;
					return $file;
				}
			}
		}

		self::$doc_image = false;
		// we are going to load the attachment into the upload directory, so invoke filter.
		add_filter( 'upload_dir', array( &$this, 'document_upload_dir_filter' ) );
		// it will be removed in "generate_metadata" processing - at end of media_handle_upload.

		// store original file name.
		$orig_filename = $file['name'];

		// hash and replace filename, appending extension.
		$file['name'] = md5( $file['name'] . microtime() ) . $this->get_extension( $file['name'] );

		/**
		 * Filters the encoded file name for the attached document (on save).
		 *
		 * @param array  $file          file structure with encoded file name.
		 * @param string $orig_filename original file name.
		 */
		$file = apply_filters( 'document_internal_filename', $file, $orig_filename );

		return $file;
	}


	/**
	 * Renames the generated attachment meta data file names to hide the attachment slug.
	 *
	 * If the generated images are used as images, their name would display the slug.
	 *
	 * @since 3.4.0.
	 *
	 * @param array  $metadata      An array of attachment meta data.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $context       Additional context. Can be 'create' when metadata was initially created for new attachment
	 *                              or 'update' when the metadata was updated.
	 */
	public function hide_doc_attach_slug( array $metadata, int $attachment_id, string $context ): array {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// check that for a document.
		$attach = get_post( $attachment_id );
		if ( ! self::check_doc_attach( $attach ) ) {
			return $metadata;
		}

		// ensure we use the document upload directory.
		self::$doc_image = false;

		if ( array_key_exists( 'sizes', $metadata ) ) {
			// get file directory of attachment.
			$file     = get_attached_file( $attach->ID );
			$file_dir = trailingslashit( dirname( $file ) );

			// prepare to use WP_Filesystem if we can.
			$use_wp_filesystem = false;
			// file code may not be already loaded.
			if ( ! function_exists( 'get_filesystem_method' ) ) {
				include ABSPATH . 'wp-admin/includes/file.php';
			}
			$method = get_filesystem_method( array(), $file_dir, false );

			if ( 'direct' === $method ) {
				// can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL.
				$creds = request_filesystem_credentials( site_url() . '/wp-admin/', $method, false, $file_dir, array(), false );

				// initialize the API.
				if ( WP_Filesystem( $creds ) ) {
					// all good so far.
					global $wp_filesystem;
					$use_wp_filesystem = true;
				}
			}

			$title    = $attach->post_title;
			$new_name = md5( $title . microtime() );
			// move file and update.
			foreach ( $metadata['sizes'] as $size => $sizeinfo ) {
				if ( 0 === strpos( $sizeinfo['file'], $title ) ) {
					if ( file_exists( $file_dir . $sizeinfo['file'] ) ) {
						$new_file = str_replace( $title, $new_name, $sizeinfo['file'] );
						if ( $use_wp_filesystem ) {
							$wp_filesystem->move( $file_dir . $sizeinfo['file'], $file_dir . $new_file );
							$wp_filesystem->chmod( $file_dir . $new_file, 0664 );
							$metadata['sizes'][ $size ]['file'] = $new_file;
						} else {
							$dummy = null;
							// Use copy and unlink because rename breaks streams.
							// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
							if ( @copy( $file_dir . $sizeinfo['file'], $file_dir . $new_file ) ) {
								@chmod( $file_dir . $new_file, 0664 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
								wp_delete_file( $file_dir . $sizeinfo['file'] );
								$metadata['sizes'][ $size ]['file'] = $new_file;
							}
							// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged
						}
					}
				}
			}
		}
		// add indicator to note it has been changed (so no need to reprocess).
		$metadata['wpdr_hidden'] = 1;

		// have finished loading the attachment into the upload directory, so remove it.
		remove_filter( 'upload_dir', array( &$this, 'document_upload_dir_filter' ) );

		return $metadata;
	}


	/**
	 * Directory with which to namespace document URLs
	 * Defaults to "documents".
	 *
	 * @return string
	 */
	public function document_slug(): string {
		$slug = get_site_option( 'document_slug' );

		if ( ! $slug ) {
			$slug = 'documents';
		}

		/**
		 * Filters the document slug.
		 *
		 * @param string $slug The slug (default or parameter).
		 */
		return apply_filters( 'document_slug', $slug );
	}


	/**
	 * Checks feed key before serving revision RSS feed.
	 *
	 * @since 0.5
	 * @return bool
	 */
	public function validate_feed_key(): bool {
		global $wpdb;

		// verify key exists.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['key'] ) ) {
			return false;
		}

		// make alphanumeric.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = preg_replace( '/[^a-z0-9]/i', '', sanitize_text_field( wp_unslash( $_GET['key'] ) ) );

		// verify length.
		if ( strlen( $key ) !== self::$key_length ) {
			return false;
		}

		// is a user logged on?
		$user = wp_get_current_user();
		if ( $user->exists() ) {
			// yes, validate against their key, i.e. act somewhat like nonce.
			$key_user = get_user_option( self::$meta_key );
			if ( $key === $key_user ) {
				return true;
			} else {
				return false;
			}
		}

		// lookup key and, if found, set user_id (so current_user_can will work).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$feed_user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s", $wpdb->prefix . self::$meta_key, $key ) );
		if ( '' !== $feed_user ) {
			wp_set_current_user( $feed_user );
			return true;
		}

		return false;
	}


	/**
	 * Given a file, returns the file's extension.
	 *
	 * @since 0.5
	 * @param string $file URL, path, or filename to file.
	 * @return string extension
	 */
	public function get_extension( string $file ): string {
		$extension = '.' . pathinfo( $file, PATHINFO_EXTENSION );

		// don't return a . extension.
		if ( '.' === $extension ) {
			return '';
		}

		/**
		 * Filters the file extension.
		 *
		 * @since 0.5
		 *
		 * @param string $extension attachment file name extension.
		 * @param string $file      attachment file name.
		 */
		return apply_filters( 'document_extension', $extension, $file );
	}

	/**
	 * Serves response headers.
	 *
	 * @since 3.3.1
	 * @param String[] $headers Headers to outout.
	 * @param string   $file    The file being served.
	 * @return void.
	 */
	private function serve_headers( array $headers, string $file ): void {
		/**
		 * Filters the HTTP headers sent when a file is served through WP Document Revisions.
		 *
		 * @param string[] $headers The HTTP headers to be sent.
		 * @param string   $file    The file being served.
		 */
		$headers = apply_filters( 'document_revisions_serve_file_headers', $headers, $file );

		foreach ( $headers as $header => $value ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@header( $header . ': ' . $value );
		}
	}

	/**
	 * Find the mimetype.
	 *
	 * @param string $file  file name..
	 * @return string
	 */
	public function get_doc_mimetype( string $file ) {
		/**
		 * Filters the MIME type for a file before it is processed by WP Document Revisions.
		 *
		 * If filtered to `false`, no `Content-Type` header will be set by the plugin.
		 *
		 * If filtered to a string, that value will be set for the `Content-Type` header.
		 *
		 * @param null|bool|string $mimetype The MIME type for a given file.
		 * @param string           $file     The file being served.
		 */
		$mimetype = apply_filters( 'document_revisions_mimetype', null, $file );

		if ( is_null( $mimetype ) ) {
			// inspired by wp-includes/ms-files.php.
			$mime = wp_check_filetype( $file );
			if ( false === $mime['type'] && function_exists( 'mime_content_type' ) ) {
				$mime['type'] = mime_content_type( $file );
			}

			if ( $mime['type'] ) {
				$mimetype = $mime['type'];
			} else {
				$mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
			}
		}
		return $mimetype;
	}

	/**
	 * Deprecated for consistency of terms.
	 *
	 * @param Int $id the post ID.
	 * @return unknown
	 */
	public function get_latest_version( $id ) {
		_deprecated_function( __FUNCTION__, '1.0.3 of WP Document Revisions', 'get_latest_version' );
		return $this->get_latest_revision( $id );
	}

	/**
	 * Deprecated for consistency sake.
	 *
	 * @param Int $id the post ID.
	 * @return String the revision URL
	 */
	public function get_latest_version_url( int $id ) {
		_deprecated_function( __FUNCTION__, '1.0.3 of WP Document Revisions', 'get_latest_revision_url' );
		return $this->get_latest_revision_url( $id );
	}

	/**
	 * Returns the URL to a post's latest revision.
	 *
	 * @since 0.5
	 * @param int $id post ID.
	 * @return string|bool URL to revision or false if no attachment
	 */
	public function get_latest_revision_url( int $id ) {

		$latest = $this->get_latest_revision( $id );

		if ( ! $latest ) {
			return false;
		}

		// temporarily remove our filter to get the true URL, not the permalink.
		remove_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ) );
		$url = wp_get_attachment_url( $this->get_document( $latest->ID )->ID );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );

		return $url;
	}

	/**
	 * Filter the attached file for documents to obviate use of cached upload directory.
	 *
	 * @since 3.5.0
	 * @param string/false $file          The file path to where the attached file should be.
	 * @param int          $attachment_id Attachment Id.
	 * @return string path to document
	 */
	public function get_attached_file_filter( $file, int $attachment_id ) {
		// returned false.
		if ( ! $file ) {
			return $file;
		}

		// only for a document.
		if ( ! $this->verify_post_type( $attachment_id ) ) {
			return $file;
		}

		// need to rebuild file name.
		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		return trailingslashit( self::$wpdr_document_dir ) . $file;
	}

	/**
	 * Modifies location of uploaded document revisions.
	 *
	 * @since 0.5
	 * @param array $dir defaults passed from WP.
	 * @return array $dir modified directory
	 */
	public function document_upload_dir_filter( array $dir ): array {
		if ( ! $this->verify_post_type() ) {
			// Ensure cookie variable is set correctly - if needed elsewhere.
			self::$doc_image = true;
			return $dir;
		}

		// Ignore if dealing with thumbnail on document page (Document upload has type set as 'file').
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['type'] ) || 'file' !== $_POST['type'] ) {
			self::$doc_image = true;
			return $dir;
		}

		global $pagenow;

		// got past cookie check (could be initial display), but may be in thumbnail code
		// Set image directory if dealing with thumbnail on document page.
		$pages = array(
			'admin-ajax.php',
			'async-upload.php',
			'edit.php',
			'media-upload.php',
			'post.php',
			'post-new.php',
		);
		if ( in_array( $pagenow, $pages, true ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			$trace     = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$functions = array(
				'wp_ajax_get_post_thumbnail_html',
				'_wp_post_thumbnail_html',
				'post_thumbnail_meta_box',
			);
			foreach ( $trace as $traceline ) {
				if ( in_array( $traceline['function'], $functions, true ) ) {
					self::$doc_image = true;
					return $dir;
				}
			}
		}

		// set the document directory.
		return $this->document_upload_dir_set( $dir );
	}

	/**
	 * Return the document upload file information.
	 *
	 * @since 3.5.0
	 *
	 * @param array $dir defaults passed from WP.
	 * @return array $dir document directory
	 */
	public function document_upload_dir_set( array $dir ): array {

		self::$doc_image = false;
		$doc_dir         = untrailingslashit( self::$wpdr_document_dir );
		$new_dir         = array(
			'path'    => $doc_dir . '/' . $dir['subdir'],
			'url'     => home_url( '/' . $this->document_slug() ) . $dir['subdir'],
			'subdir'  => $dir['subdir'],
			'basedir' => $doc_dir,
			'baseurl' => home_url( '/' . $this->document_slug() ),
			'error'   => false,
		);

		return $new_dir;
	}

	/**
	 * Hides the generated attachment meta data file names to hide the attachment slug.
	 *
	 * If the generated images are used as images, their name would display the slug.
	 *
	 * For existing images.
	 *
	 * @since 3.4.0.
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public function hide_exist_doc_attach_slug( int $attachment_id ): void {
		$attach = get_post( $attachment_id );
		if ( ! self::check_doc_attach( $attach ) ) {
			return;
		}

		// get attachment metadata.
		$meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		if ( ! is_array( $meta ) || ! isset( $meta['sizes'] ) || isset( $meta['wpdr_hidden'] ) ) {
			return;
		}

		$meta_sizes = $meta['sizes'];
		// WPML can create duplicate attachment records (updating array will ensure this copied too).
		if ( ! isset( $meta_sizes[0]['file'] ) || false === strpos( $meta_sizes[0]['file'], substr( $attach->post_title, 0, 32 ) ) ) {
			// image files have a different name, nothing to do.
			$meta['wpdr_hidden'] = 1;
			update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );
			return;
		}

		// The metadata contains the same name as the document.

		// ensure we use the document upload directory.
		self::$doc_image = false;

		// get file for attachment (to know the directory stored).
		$file     = get_attached_file( $attachment_id );
		$file_dir = trailingslashit( dirname( $file ) );

		// prepare to use WP_Filesystem if we can.
		$use_wp_filesystem = false;
		// file code may not be already loaded.
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			include ABSPATH . 'wp-admin/includes/file.php';
		}

		$method = get_filesystem_method( array(), $file_dir, false );
		if ( 'direct' === $method ) {
			// can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL.
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', $method, false, $file_dir, array(), false );

			// initialize the API.
			if ( WP_Filesystem( $creds ) ) {
				// all good so far.
				global $wp_filesystem;
				$use_wp_filesystem = true;
			}
		}

		$title    = $attach->post_title;
		$new_name = md5( $title . microtime() );
		// move file and update.
		foreach ( $meta_sizes as $size => $sizeinfo ) {
			if ( 0 === strpos( $sizeinfo['file'], $title ) ) {
				if ( file_exists( $file_dir . $sizeinfo['file'] ) ) {
					$new_file = str_replace( $title, $new_name, $sizeinfo['file'] );
					if ( $use_wp_filesystem ) {
						if ( $wp_filesystem->move( $file_dir . $sizeinfo['file'], $file_dir . $new_file ) ) {
							$wp_filesystem->chmod( $file_dir . $new_file, 0664 );
							$meta_sizes[ $size ]['file'] = $new_file;
						}
					} else {
						$dummy = null;
						// Use copy and unlink because rename breaks streams.
						// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						if ( @copy( $file_dir . $sizeinfo['file'], $file_dir . $new_file ) ) {
							wp_delete_file( $file_dir . $sizeinfo['file'] );
							$meta_sizes[ $size ]['file'] = $new_file;
						}
					}
				}
			}
		}
		// update the metadata.
		$meta['sizes']       = $meta_sizes;
		$meta['wpdr_hidden'] = 1;

		update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );
	}

	/**
	 * Callback to handle revision RSS feed.
	 *
	 * @since 0.5
	 */
	public function do_feed_revision_log(): void {
		// because we're in function scope, pass $post as a global.
		global $post;

		// remove this filter to A) prevent trimming and B) to prevent WP from using the attachID if there's no revision log.
		remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );
		remove_filter( 'get_the_excerpt', 'twentyeleven_custom_excerpt_more' );

		// include the feed and then die.
		load_template( __DIR__ . '/revision-feed.php' );
	}

	/**
	 * Intercepts RSS feed redirect and forces our custom feed.
	 *
	 * Note: Use `add_filter( 'document_custom_feed', '__return_false' )` to shortcircuit.
	 *
	 * @since 0.5
	 * @param string $deflt the original feed.
	 * @return string the slug for our feed
	 */
	public function hijack_feed( string $deflt ): string {
		global $post;

		if ( ! $this->verify_post_type( ( isset( $post->ID ) ? $post : false ) ) || ! apply_filters( 'document_custom_feed', true ) ) {
			return $deflt;
		}

		return 'revision_log';
	}

	/**
	 * Verifies that users are auth'd to view a revision feed.
	 *
	 * Note: Use `add_filter( 'document_verify_feed_key', '__return_false' )` to shortcircuit.
	 *
	 * @since 0.5
	 */
	public function revision_feed_auth(): void {
		/**
		 * Allows the RSS feed to be switched off.
		 *
		 * @param boolean true Allows an RSS feed for documents.
		 */
		if ( ! $this->verify_post_type() || ! apply_filters( 'document_verify_feed_key', true ) ) {
			return;
		}

		if ( is_feed() && ! $this->validate_feed_key() ) {
				wp_die( esc_html__( 'Sorry, this is a private feed.', 'wp-document-revisions' ) );
		}
	}

	/**
	 * Filter's calls for attachment URLs for files attached to documents
	 * Returns the document or revision URL instead of the file's true location
	 * Prevents direct access to files and ensures authentication.
	 *
	 * @since 1.2
	 * @param string $url the original URL.
	 * @param int    $post_id the attachment ID.
	 * @return string the modified URL
	 */
	public function attachment_url_filter( string $url, int $post_id ): string {
		// not an attached attachment.
		if ( ! $this->verify_post_type( $post_id ) ) {
			return $url;
		}

		$document = get_post( $post_id );

		if ( ! $document ) {
			return $url;
		}

		// user can't read revisions anyways, so just give them the URL of the latest document.
		if ( $document->post_parent > 0 && ! current_user_can( 'read_document_revisions' ) ) {
			return get_permalink( $document->post_parent );
		}

		// we know there's a revision out there that has the document as its parent and the attachment ID as its body, find it.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$revision_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_parent = %d " .
				'AND post_name <> %s ' .
				'AND (post_content = %d OR post_content LIKE %s ) LIMIT 1',
				$document->post_parent,
				$document->post_parent . '-autosave-v1',
				$post_id,
				$this->format_doc_id( $post_id ) . '%'
			)
		);

		// couldn't find it, just return the true URL.
		if ( ! $revision_id ) {
			return $url;
		}

		// run through standard permalink filters and return.
		return get_permalink( $revision_id );
	}

	/**
	 * Prevents internal calls to files from breaking when apache is running on windows systems (Xampp, etc.)
	 * Code inspired by includes/class.wp.filesystem.php
	 * See generally http://wordpress.org/support/topic/plugin-wp-document-revisions-404-error-and-permalinks-are-set-correctly.
	 *
	 * @since 1.2.1
	 * @param string $url the permalink.
	 * @return string the modified permalink
	 */
	public function wamp_document_path_filter( string $url ): string {
		$url = preg_replace( '|^([a-z]{1}):|i', '', $url ); // Strip out windows drive letter if it's there.
		return str_replace( '\\', '/', $url ); // Windows path sanitization.
	}

	/**
	 * Provides a workaround for the attachment url filter breaking wp_get_attachment_image_src
	 * Removes the wp_get_attachment_url filter and runs image_downsize normally
	 * Will also check to make sure the returned image doesn't leak the file's true path.
	 *
	 * @since 1.2.2
	 * @param bool|array $downsize Whether to short-circuit the image downsize.
	 * @param int        $id       the ID of the attachment.
	 * @param string     $size     the size requested.
	 * @return bool|array false or the image array to be returned from image_downsize()
	 */
	public function image_downsize( $downsize, int $id, $size ) {
		// previous filter code wants to short-cut the process.
		if ( is_array( $downsize ) ) {
			return $downsize;
		}

		// not a document.
		if ( ! $this->verify_post_type( $id ) ) {
			return $downsize;
		}

		remove_filter( 'image_downsize', array( &$this, 'image_downsize' ) );
		remove_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ) );

		$direct = wp_get_attachment_url( $id );
		$image  = image_downsize( $id, $size );

		add_filter( 'image_downsize', array( &$this, 'image_downsize' ), 10, 3 );
		add_filter( 'wp_get_attachment_url', array( &$this, 'attachment_url_filter' ), 10, 2 );

		// if WordPress is going to return the direct url to the real file,
		// serve the document permalink (or revision permalink) instead.
		if ( $image && $image[0] === $direct ) {
			$image[0] = wp_get_attachment_url( $id );
		}

		return $image;
	}
}
