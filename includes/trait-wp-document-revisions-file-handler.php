<?php
/**
 * WP Document Revisions File Handling Trait
 *
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File serving, uploads, and attachment handling functionality for WP_Document_Revisions.
 */
trait WP_Document_Revisions_File_Handler {

	/**
	 * Length of feed key.
	 *
	 * @var int
	 */
	private static $key_length = 32;

	/**
	 * User meta key used auth feeds.
	 *
	 * @var string
	 */
	private static $meta_key = 'document_revisions_feed_key';

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
					'',
					array( 'response' => absint( $this->no_document_response_code( $post, 0 ) ) )
				);
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
				'',
				array( 'response' => absint( $this->no_document_response_code( $post, (int) $rev_id ) ) )
			);
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
		 * @param bool    $serve_file default action to serve file.
		 * @param WP_Post $post    WP Post to be served.
		 * @param string  $version Document revision.
		 */
		$serve_file = apply_filters( 'serve_document_auth', true, $post, $version ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		if ( ! $serve_file ) {
			if ( false === $serve_file ) {
				wp_die(
					esc_html__( 'You are not authorized to access that file.', 'wp-document-revisions' ),
					'',
					array( 'response' => 403 )
				);
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
		do_action( 'serve_document', $post->ID, $file ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		/**
		 * Filters file name of document to be served. (Useful if file is encrypted at rest).
		 *
		 * @param string  $file       File name to be served.
		 * @param integer $post->ID   Post id of the document.
		 * @param integer $attach->ID Post id of the attachment.
		 */
		$file = apply_filters( 'document_serve', $file, $post->ID, $attach->ID );

		/**
		 * Filters a URL to send the (already authorized) request to instead of streaming the file
		 * through PHP, e.g. a signed CDN or S3 URL for large files. Return '' to serve normally.
		 *
		 * The URL isn't restricted to this site, so only return URLs you trust, and prefer
		 * short-lived signed URLs for private documents: anyone with the URL can use it until it
		 * expires. get_raw_attachment_url() returns the attachment's storage URL.
		 *
		 * @since 5.6.0
		 *
		 * @param string  $url    URL to redirect to. Default ''.
		 * @param WP_Post $post   the document.
		 * @param WP_Post $attach the attachment being served.
		 * @param string  $file   path of the file to be served.
		 */
		$redirect = apply_filters( 'document_serve_redirect_url', '', $post, $attach, $file );
		if ( is_string( $redirect ) && '' !== $redirect ) {
			// The target may only be valid for this user, so don't let the redirect be cached.
			nocache_headers();
			wp_redirect( $redirect, 302, 'WP Document Revisions' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- off-site storage URLs are the point.
			if ( class_exists( 'WP_UnitTestCase' ) ) {
				return $template;
			}
			exit;
		}

		// We may override this later.
		status_header( 200 );

		// fake the filename.
		// Coerce the revision query var to a non-negative integer to prevent header-parameter tampering via the Content-Disposition filename.
		$version_label = absint( $version );
		$filename      = $post->post_name;
		$filename     .= ( 0 === $version_label ) ? '' : __( '-revision-', 'wp-document-revisions' ) . $version_label;

		// we want the true attachment URL, not the permalink.
		$filename .= $this->get_extension( (string) $this->get_raw_attachment_url( $attach->ID ) );

		// Sanitize the filename for use in the Content-Disposition header to prevent header injection
		// or quote-escape attacks via filterable extension/post slug values: strip control characters
		// (including DEL, 0x7F) and characters that are unsafe inside an HTTP header value or quoted
		// filename param.
		$filename = preg_replace( '/[\x00-\x1f\x7f"\\\\]/', '', (string) $filename );

		$headers = array();

		// Set content-disposition header. Two options here:
		// "attachment" -- force save-as dialog to pop up when file is downloaded (pre 1.3.1 default)
		// "inline" -- attempt to open in browser (e.g., PDFs), if not possible, prompt with save as (1.3.1+ default).
		$disposition = ( apply_filters( 'document_content_disposition_inline', true ) ) ? 'inline' : 'attachment';

		$headers['Content-Disposition'] = $disposition . '; filename="' . $filename . '"';

		// get the mime type.
		$mimetype = $this->get_doc_mimetype( $file, $attach->ID );

		// Set the Content-Type header if a mimetype has been detected or provided.
		if ( is_string( $mimetype ) ) {
			$headers['Content-Type'] = $mimetype;
		}

		// uncompressed file length.
		$filesize = filesize( $file );

		// Will we use gzip or deflate output? Do this early as can impact headers and these need outputting before any output.
		// Does the user accept gzip or deflate?
		$gzip_dflt = false;
		// Default so $comp_type is always defined: the document_serve_use_gzip
		// filter can force compression even when the client advertised no
		// encoding (in which case the gzip/deflate branches below never run).
		$comp_type = 'deflate';
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

		// Only compress text-like types by default. PDFs, office files, images and archives are
		// already compressed, so deflating them in PHP costs CPU and memory for little or no gain
		// and forces the whole response to be buffered.
		if ( $gzip_dflt && ! $this->is_compressible_mimetype( $mimetype ) ) {
			$gzip_dflt = false;
		}

		/**
		 * Filter to determine if gzip should be used to serve file (subject to browser negotiation).
		 *
		 * Defaults to true only when the client accepts gzip/deflate and the MIME type is
		 * compressible (see document_compressible_mimetypes).
		 *
		 * Note: Use `add_filter( 'document_serve_use_gzip', '__return_true' )` to shortcircuit.
		 *       This is always subject to browser negociation.
		 *
		 * @param bool    $gzip_dflt Whether gzip will be used by default (client support and a compressible MIME type).
		 * @param string  $mimetype  Mime type to be served.
		 * @param integer $filesize  File size.
		 */
		$compress = apply_filters( 'document_serve_use_gzip', $gzip_dflt, $mimetype, $filesize );

		$headers['Content-Length'] = (string) $filesize;
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

		if ( $this->is_public_document( $post, $version ) ) {
			$headers['Cache-Control'] = 'no-cache';
		} else {
			// Only the requesting user may read this, so keep it out of shared caches and proxies.
			$headers['Cache-Control'] = 'private, no-cache, no-store, max-age=0';
			$headers['Pragma']        = 'no-cache';
			$headers['Expires']       = 'Wed, 11 Jan 1984 05:00:00 GMT';
		}

		// Don't let browsers second-guess the served Content-Type.
		$headers['X-Content-Type-Options'] = 'nosniff';

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

		// Hand the file to the web server if the site has set that up.
		$sendfile = $this->sendfile_header( $file, $attach );
		if ( $sendfile ) {
			// The server sends the body and works out its length and encoding.
			unset( $headers['Content-Length'] );
			$headers[ $sendfile[0] ] = $sendfile[1];
			$this->serve_headers( $headers, $file );
			if ( class_exists( 'WP_UnitTestCase' ) ) {
				return $template;
			}
			exit;
		}

		// in case this is a large file, remove PHP time limits.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,Squiz.PHP.DiscouragedFunctions.Discouraged
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

		// Check file readability before committing to response headers (covers both branches:
		// compressed streaming via fopen/deflate and uncompressed via readfile/WP_Filesystem).
		if ( ! is_readable( $file ) ) {
			status_header( 500 );
			if ( $under_test ) {
				return $template;
			}
			exit;
		}

		// If we made it this far, just serve the file.
		if ( $compress ) {
			// Stream the file through an incremental deflate context so the entire raw file
			// is never resident in memory at once. The compressed bytes still accumulate in
			// the output buffer (so we can populate Content-Length), but compressed size is
			// typically << raw size for compressible payloads. Replaces the previous
			// gzencode( file_get_contents( $file ) ) approach which loaded the entire file
			// into PHP memory before compressing - a real DoS vector for multi-MB documents.
			$encoding = ( 'gzip' === $comp_type ) ? ZLIB_ENCODING_GZIP : ZLIB_ENCODING_RAW;
			$ctx      = deflate_init( $encoding, array( 'level' => 9 ) );
			$fp       = @fopen( $file, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( false === $ctx || false === $fp ) {
				// Fallback path - avoid breaking download if streaming primitives fail.
				if ( $fp ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
					fclose( $fp );
				}
				if ( 'gzip' === $comp_type ) {
					// phpcs:ignore WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions
					echo gzencode( file_get_contents( $file ), 9 );
				} else {
					// phpcs:ignore WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions
					echo gzdeflate( file_get_contents( $file ), 9 );
				}
			} else {
				while ( ! feof( $fp ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
					$chunk = fread( $fp, 8192 );
					if ( false === $chunk || '' === $chunk ) {
						break;
					}
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo deflate_add( $ctx, $chunk, ZLIB_NO_FLUSH );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo deflate_add( $ctx, '', ZLIB_FINISH );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $fp );
			}
			$headers['Content-Encoding'] = ( 'gzip' === $comp_type ) ? 'gzip' : 'deflate';
			if ( array_key_exists( 'Content-Length', $headers ) ) {
				// only update to the correct value.
				$headers['Content-Length'] = (string) ob_get_length();
			}
			// only know the length after writing to buffer, so only output headers now.
			$this->serve_headers( $headers, $file );
		} else {
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
				$wp_filesystem = $this->direct_filesystem( dirname( $file ) );
				if ( $wp_filesystem ) {
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
	 * @param WP_Post  $post    the post object.
	 * @param bool|int $version version of the document being served, if any.
	 * @return bool
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
	 * Whether a document file can be read by anyone, i.e. without logging on or a password.
	 *
	 * Mirrors the anonymous-access branch of serve_document_auth().
	 *
	 * @since 5.7.0
	 * @param WP_Post $post    the document being served.
	 * @param mixed   $version revision requested, if any.
	 * @return bool
	 */
	private function is_public_document( WP_Post $post, $version ): bool {
		return ! $version
			&& 'publish' === $post->post_status
			&& '' === $post->post_password
			&& apply_filters( 'document_read_uses_read', true );
	}

	/**
	 * Whether a document of this MIME type is compressed on download by default.
	 *
	 * @since 5.6.0
	 * @param mixed $mimetype the MIME type being served.
	 * @return bool
	 */
	public function is_compressible_mimetype( $mimetype ): bool {
		if ( ! is_string( $mimetype ) || '' === $mimetype ) {
			return false;
		}

		/**
		 * Filters the MIME types compressed on download by default (when the client accepts it).
		 *
		 * An entry ending in "/" matches every type with that prefix, e.g. "text/".
		 * The document_serve_use_gzip filter still has the final say.
		 *
		 * @since 5.6.0
		 *
		 * @param string[] $mimetypes MIME types or "type/" prefixes.
		 */
		$compressible = (array) apply_filters(
			'document_compressible_mimetypes',
			array( 'text/', 'application/json', 'application/ld+json', 'application/xml', 'image/svg+xml' )
		);

		$mimetype = strtolower( trim( explode( ';', $mimetype )[0] ) );
		foreach ( $compressible as $type ) {
			$type = strtolower( (string) $type );
			if ( '/' === substr( $type, -1 ) ? 0 === strpos( $mimetype, $type ) : $mimetype === $type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a document file is being uploaded in this request.
	 *
	 * True from document_upload_start until document_upload_end.
	 *
	 * @since 5.6.0
	 * @return bool
	 */
	public function is_document_upload(): bool {
		return self::$document_upload;
	}

	/**
	 * Filters the file types allowed while a document is being uploaded.
	 *
	 * @since 5.6.0
	 * @param mixed $mimes allowed MIME types keyed by extension pattern.
	 * @return mixed
	 */
	public function document_upload_mimes( $mimes ) {
		if ( ! is_array( $mimes ) || ! $this->is_document_upload() ) {
			return $mimes;
		}

		/**
		 * Filters the file types allowed for document uploads.
		 *
		 * Applied only while a document file is being uploaded, after WordPress's own
		 * restrictions (on multisite, the network's "Upload file types"). For example,
		 * return `array_merge( $mimes, $all )` to allow every type WordPress knows about
		 * for documents without allowing them in the Media Library.
		 *
		 * @since 5.6.0
		 *
		 * @param array<string, string> $mimes Allowed MIME types keyed by extension pattern.
		 * @param array<string, string> $all   Every MIME type WordPress knows (wp_get_mime_types()).
		 */
		return (array) apply_filters( 'document_allowed_mimes', $mimes, wp_get_mime_types() );
	}

	/**
	 * Filters the maximum upload size on document screens and during document uploads.
	 *
	 * @since 5.6.0
	 * @param mixed $bytes the maximum upload size in bytes.
	 * @return mixed
	 */
	public function document_upload_size_limit( $bytes ) {
		if ( ! is_numeric( $bytes ) || ! ( $this->is_document_upload() || ( is_admin() && $this->verify_post_type() ) ) ) {
			return $bytes;
		}

		/**
		 * Filters the maximum size, in bytes, of a document upload.
		 *
		 * Applied on document screens (for the uploader's limit) and while a document is
		 * uploaded, including multisite's "Max upload file size" check. It can't raise
		 * PHP's own upload_max_filesize / post_max_size.
		 *
		 * @since 5.6.0
		 *
		 * @param int $bytes Maximum upload size in bytes.
		 */
		return (int) apply_filters( 'document_upload_size_limit', (int) $bytes );
	}

	/**
	 * Applies document_upload_size_limit to multisite's per-file limit during a document upload.
	 *
	 * Core's check_upload_size() reads the fileupload_maxk site option directly rather than
	 * using upload_size_limit.
	 *
	 * @since 5.6.0
	 * @param mixed $kilobytes the fileupload_maxk site option.
	 * @return mixed
	 */
	public function document_fileupload_maxk( $kilobytes ) {
		if ( ! is_numeric( $kilobytes ) || ! $this->is_document_upload() ) {
			return $kilobytes;
		}

		/** This filter is documented in includes/trait-wp-document-revisions-file-handler.php */
		$bytes = (int) apply_filters( 'document_upload_size_limit', (int) $kilobytes * KB_IN_BYTES );

		return (int) ceil( $bytes / KB_IN_BYTES );
	}

	/**
	 * Marks the end of a document upload once WordPress has generated the attachment metadata.
	 *
	 * @since 5.6.0
	 * @param mixed $metadata      the attachment metadata.
	 * @param mixed $attachment_id the attachment ID.
	 * @return mixed the metadata, unchanged.
	 */
	public function end_document_upload( $metadata, $attachment_id = 0 ) {
		if ( ! self::$document_upload ) {
			return $metadata;
		}
		self::$document_upload = false;

		$attachment_id = absint( $attachment_id );

		/**
		 * Fires when a document file upload has finished and its attachment metadata is generated.
		 *
		 * @since 5.6.0
		 *
		 * @param int $attachment_id the new attachment.
		 * @param int $document_id   the document it belongs to.
		 */
		do_action( 'document_upload_end', $attachment_id, (int) wp_get_post_parent_id( $attachment_id ) );

		return $metadata;
	}

	/**
	 * Returns an attachment's real storage URL, bypassing the filter that replaces document
	 * attachment URLs with the (authenticated) document permalink.
	 *
	 * Don't expose it for private documents unless the storage location itself is protected.
	 *
	 * @since 5.6.0
	 * @param int $attach_id the attachment ID.
	 * @return string|false the URL, or false if there is none.
	 */
	public function get_raw_attachment_url( int $attach_id ) {
		$priority = has_filter( 'wp_get_attachment_url', array( $this, 'attachment_url_filter' ) );
		if ( false !== $priority ) {
			remove_filter( 'wp_get_attachment_url', array( $this, 'attachment_url_filter' ), $priority );
		}

		$url = wp_get_attachment_url( $attach_id );

		if ( false !== $priority ) {
			add_filter( 'wp_get_attachment_url', array( $this, 'attachment_url_filter' ), $priority, 2 );
		}

		return $url;
	}

	/**
	 * HTTP status for a document request that has no file to serve.
	 *
	 * @since 5.6.0
	 * @param WP_Post $post   the requested document.
	 * @param int     $rev_id the document or revision selected, or 0 if none was found.
	 * @return int
	 */
	private function no_document_response_code( WP_Post $post, int $rev_id ): int {
		/**
		 * Filters the HTTP response code when a document or revision has no file to serve.
		 *
		 * @since 5.6.0 Defaults to 404 (previously 403, which suggests an authorization failure)
		 *              and receives the document and revision.
		 *
		 * @param int     $code   Response code. Default 404.
		 * @param WP_Post $post   The requested document.
		 * @param int     $rev_id The document or revision selected, or 0 if none was found.
		 */
		return absint( apply_filters( 'document_no_document_response_code', 404, $post, $rev_id ) );
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
			$dir = $this->default_upload_dir()['basedir'];
		} elseif ( is_multisite() && ! is_network_admin() ) {
			// make site specific on multisite.
			if ( is_main_site() && get_current_network_id() === get_main_network_id() ) {
				$dir = str_replace( '/sites/%site_id%', '', $dir );
			}

			global $wpdb;
			$dir = str_replace( '%site_id%', $wpdb->blogid, $dir );
		}

		/**
		 * Filters the directory documents are stored in.
		 *
		 * Runs when the directory is first needed on each site, after other plugins
		 * have loaded, rather than when WP Document Revisions loads.
		 *
		 * @since 5.6.0
		 *
		 * @param string $dir absolute path, or stream wrapper URL such as s3://bucket/path.
		 */
		self::$wpdr_document_dir = (string) apply_filters( 'document_upload_directory', (string) $dir );

		return self::$wpdr_document_dir;
	}

	/**
	 * Forgets the resolved document directory, e.g. after switch_blog().
	 *
	 * @since 5.6.0
	 */
	public function reset_document_upload_dir(): void {
		self::$wpdr_document_dir = null;
	}

	/**
	 * Returns WordPress's uploads directory for the current site, without the document override.
	 *
	 * Resolved on each call so it reflects upload_dir filters registered after this plugin
	 * loaded and the current site on multisite.
	 *
	 * @since 5.6.0
	 * @return array<string, mixed> the wp_upload_dir() result.
	 */
	public function default_upload_dir(): array {
		self::$resolving_upload_dir = true;
		try {
			$dir = wp_upload_dir( null, false );
		} finally {
			self::$resolving_upload_dir = false;
		}
		self::$wp_default_dir = $dir;

		return $dir;
	}


	/**
	 * Whether the current user may upload a file to the given document.
	 *
	 * @since 5.7.0
	 * @param int $document_id the post the file is being uploaded to.
	 * @return bool
	 */
	private function can_upload_to_document( int $document_id ): bool {
		// Check the ID first: get_post( 0 ) would fall back to the global post.
		return $document_id > 0
			&& 'document' === get_post_type( $document_id )
			&& current_user_can( 'edit_document', $document_id );
	}

	/**
	 * Generates a random name for a stored document file.
	 *
	 * Private documents rely on stored file names being unguessable, so the name comes from a
	 * cryptographically secure source. It keeps the 32 lowercase hex character (MD5) format that
	 * the hashed-name checks and the .htaccess rules look for.
	 *
	 * @since 5.7.0
	 * @return string 32 lowercase hex characters.
	 */
	public static function random_file_name(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Rewrites uploaded revisions filename with secure hash to mask true location.
	 *
	 * @since 0.5
	 * @param array<string, mixed> $file file data from WP.
	 * @return array<string, mixed> file with new filename
	 */
	public function filename_rewrite( array $file ): array {
		// verify if this is a document load as they have an additional parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only lookup of the upload target; core verifies the upload nonce.
		$document_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['upload_source'] ) || 'wp-document-revisions' !== $_POST['upload_source']
			// The flag only counts for a document the current user can edit.
			|| ! $this->can_upload_to_document( $document_id ) ) {
			// default - not a WPDR Document.
			self::$doc_image       = true;
			self::$document_upload = false;
			return $file;
		}

		// Parameter found, so is a document load.
		self::$doc_image       = false;
		self::$document_upload = true;

		// we are going to load the attachment into the upload directory, so invoke filter.
		add_filter( 'upload_dir', array( $this, 'document_upload_dir_filter' ) );
		// it will be removed in "generate_metadata" processing - at end of media_handle_upload.

		// store original file name.
		$orig_filename = $file['name'];

		// hash and replace filename, appending extension.
		$file['name'] = self::random_file_name() . $this->get_extension( $file['name'] );

		/**
		 * Filters the encoded file name for the attached document (on save).
		 *
		 * @param array  $file          file structure with encoded file name.
		 * @param string $orig_filename original file name.
		 */
		$file = apply_filters( 'document_internal_filename', $file, $orig_filename );

		/**
		 * Fires when a document file upload starts, before the file is moved into place.
		 *
		 * From here until document_upload_end, is_document_upload() returns true, e.g. for
		 * offload plugins to set storage options (such as S3 ContentDisposition) for documents.
		 *
		 * @since 5.6.0
		 *
		 * @param array  $file          the upload, with the hashed file name.
		 * @param int    $document_id   the document the file is being uploaded to.
		 * @param string $orig_filename the original file name.
		 */
		do_action( 'document_upload_start', $file, $document_id, $orig_filename );

		return $file;
	}


	/**
	 * Populates the document_attachment_id meta from post_content if empty.
	 *
	 * @since 3.9.1
	 * @param int    $post_id      Post id.
	 * @param string $post_content Post_content.
	 * @return int The attachment ID, or 0 if none found.
	 */
	public function populate_attachment_meta( $post_id, $post_content ) {
		// if there is a value, return it.
		$meta = absint( get_post_meta( $post_id, '_document_attachment_id', true ) );
		if ( $meta ) {
			return $meta;
		}

		// Migrate the legacy public meta key (registered as 'document_attachment_id'
		// in 5.0.0) to the protected key. Carry the value over and drop the old row.
		$legacy = absint( get_post_meta( $post_id, 'document_attachment_id', true ) );
		if ( $legacy ) {
			update_post_meta( $post_id, '_document_attachment_id', $legacy );
			delete_post_meta( $post_id, 'document_attachment_id' );
			return $legacy;
		}

		// get the value from post_content, and if different update the meta.
		$attach_id = absint( $this->extract_document_id( $post_content ) );
		if ( $attach_id !== $meta ) {
			update_post_meta( $post_id, '_document_attachment_id', $attach_id );
		}

		return $attach_id;
	}


	/**
	 * Renames the generated attachment meta data file names to hide the attachment slug.
	 *
	 * If the generated images are used as images, their name would display the slug.
	 *
	 * @since 3.4.0.
	 *
	 * @param mixed $metadata      An array of attachment meta data.
	 * @param mixed $attachment_id Current attachment ID.
	 * @param mixed $context       Additional context. Can be 'create' when metadata was initially created for new attachment
	 *                             or 'update' when the metadata was updated.
	 * @return mixed the (possibly modified) attachment metadata.
	 */
	public function hide_doc_attach_slug( $metadata, $attachment_id = 0, $context = 'create' ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// No attachment ID: don't fall back to the global post (get_post( 0 )).
		if ( ! is_array( $metadata ) || ! is_numeric( $attachment_id ) || (int) $attachment_id <= 0 ) {
			return $metadata;
		}
		$attachment_id = (int) $attachment_id;

		// check that for a document.
		$attach = get_post( $attachment_id );
		if ( ! $attach || ! self::check_doc_attach( $attach ) ) {
			return $metadata;
		}

		// ensure we use the document upload directory (belt and braces - was set earlier).
		self::$doc_image = false;

		if ( array_key_exists( 'sizes', $metadata ) ) {
			// get file directory of attachment.
			$file     = get_attached_file( $attach->ID );
			$file_dir = trailingslashit( dirname( $file ) );

			$metadata['sizes'] = $this->hide_size_file_names( $metadata['sizes'], $file_dir, $attach->post_title );
		}
		// add indicator to note it has been changed (so no need to reprocess).
		$metadata['wpdr_hidden'] = 1;

		// have finished loading the attachment into the upload directory, so remove it.
		remove_filter( 'upload_dir', array( $this, 'document_upload_dir_filter' ) );

		// store the attachment in postmeta for fallback on initial file load.
		update_post_meta( $attach->post_parent, '_document_attachment_id', $attachment_id );

		// revert to default..
		self::$doc_image = true;

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
			return hash_equals( (string) $key_user, $key );
		}

		// lookup key and, if found, set user_id (so current_user_can will work).
		global $wpdb;
		$feed_users = get_users(
			array(
				'blog_id'    => 0,
				'meta_key'   => $wpdb->get_blog_prefix() . self::$meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ID',
				'number'     => 1,
			)
		);
		if ( empty( $feed_users ) ) {
			return false;
		}

		// Re-check with a constant-time, case-sensitive comparison (the SQL match uses the column collation).
		$feed_user = (int) $feed_users[0];
		if ( hash_equals( (string) get_user_option( self::$meta_key, $feed_user ), $key ) ) {
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
	 * @return void
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
	 * Returns the header, if any, that tells the web server to send the file itself.
	 *
	 * Off unless a site opts in, because it only works when the server is configured
	 * for it (mod_xsendfile, an nginx internal location, LiteSpeed).
	 *
	 * @since 5.6.0
	 * @param string  $file   path of the file to be served.
	 * @param WP_Post $attach the attachment being served.
	 * @return array{0: string, 1: string}|null header name and value, or null to serve through PHP.
	 */
	private function sendfile_header( string $file, WP_Post $attach ): ?array {
		/**
		 * Filters which header hands document downloads to the web server instead of PHP.
		 *
		 * Return 'X-Sendfile' (Apache mod_xsendfile, lighttpd), 'X-Accel-Redirect'
		 * (nginx) or 'X-LiteSpeed-Location' (LiteSpeed). Only enable it when the server
		 * is configured for it and the document directory isn't otherwise web-accessible:
		 * the plugin has already checked permissions when the header is sent.
		 *
		 * @since 5.6.0
		 *
		 * @param string  $header Header name. Default '' (serve through PHP).
		 * @param string  $file   Path of the file to be served.
		 * @param WP_Post $attach The attachment being served.
		 */
		$header = (string) apply_filters( 'document_serve_sendfile_header', '', $file, $attach );
		if ( ! in_array( $header, array( 'X-Sendfile', 'X-Accel-Redirect', 'X-LiteSpeed-Location' ), true ) ) {
			return null;
		}

		/**
		 * Filters the value sent in the sendfile header.
		 *
		 * X-Sendfile takes the file path, which is the default. X-Accel-Redirect and
		 * X-LiteSpeed-Location take a URI in an internal location that maps to the
		 * document directory, so they have no default: return one, or the file is served
		 * through PHP as usual.
		 *
		 * @since 5.6.0
		 *
		 * @param string  $value  Header value. Default the file path for X-Sendfile, '' otherwise.
		 * @param string  $file   Path of the file to be served.
		 * @param string  $header Header name.
		 * @param WP_Post $attach The attachment being served.
		 */
		$value = (string) apply_filters( 'document_serve_sendfile_path', 'X-Sendfile' === $header ? $file : '', $file, $header, $attach );

		// Header values can't contain line breaks.
		$value = str_replace( array( "\r", "\n" ), '', $value );
		if ( '' === $value ) {
			return null;
		}

		return array( $header, $value );
	}

	/**
	 * Find the mimetype.
	 *
	 * Resolution order: the `document_revisions_mimetype` filter, the attachment's
	 * stored MIME type (when an attachment ID is given), the file extension, content
	 * sniffing, and finally `application/octet-stream`.
	 *
	 * @param string $file      file name.
	 * @param int    $attach_id optional attachment ID whose stored MIME type is preferred.
	 * @return string|false
	 */
	public function get_doc_mimetype( string $file, int $attach_id = 0 ) {
		/**
		 * Filters the MIME type for a file before it is processed by WP Document Revisions.
		 *
		 * If filtered to `false`, no `Content-Type` header will be set by the plugin.
		 *
		 * If filtered to a string, that value will be set for the `Content-Type` header.
		 *
		 * @param null|bool|string $mimetype  The MIME type for a given file.
		 * @param string           $file      The file being served.
		 * @param int              $attach_id The attachment ID, or 0 if not known.
		 */
		$mimetype = apply_filters( 'document_revisions_mimetype', null, $file, $attach_id );

		if ( is_null( $mimetype ) ) {
			$mimetype = $attach_id ? get_post_mime_type( $attach_id ) : false;

			if ( ! $mimetype ) {
				$mime     = wp_check_filetype( $file );
				$mimetype = $mime['type'];
			}

			if ( ! $mimetype && function_exists( 'mime_content_type' ) && is_readable( $file ) ) {
				$mimetype = mime_content_type( $file );
			}

			if ( ! $mimetype ) {
				$mimetype = 'application/octet-stream';
			}
		}
		return $mimetype;
	}

	/**
	 * Deprecated for consistency of terms.
	 *
	 * @param Int $id the post ID.
	 * @return string|bool
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

		$attach = $latest ? $this->get_document( $latest->ID ) : false;
		if ( ! $attach ) {
			return false;
		}

		// temporarily remove our filter to get the true URL, not the permalink.
		$url = $this->get_raw_attachment_url( $attach->ID );

		return $url;
	}

	/**
	 * Filter the attached file for documents to obviate use of cached upload directory.
	 *
	 * @since 3.5.0
	 * @param string|false $file          The file path to where the attached file should be.
	 * @param int|string   $attachment_id Attachment Id. WordPress core may pass a numeric string.
	 * @return string path to document
	 */
	public function get_attached_file_filter( $file, $attachment_id ) {
		$attachment_id = (int) $attachment_id;

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
		return trailingslashit( $this->document_upload_dir() ) . $file;
	}

	/**
	 * Modifies location of uploaded document revisions.
	 *
	 * @since 0.5
	 * @param array<string, mixed> $dir defaults passed from WP.
	 * @return array<string, mixed> modified directory
	 */
	public function document_upload_dir_filter( array $dir ): array {
		if ( self::$resolving_upload_dir ) {
			return $dir;
		}

		if ( ! $this->verify_post_type() ) {
			// Ensure cookie variable is set correctly - if needed elsewhere.
			self::$doc_image = true;
			return $dir;
		}

		// Ignore if not loading a document. [self::$doc_image set false while loading a document].
		if ( self::$doc_image ) {
			return $dir;
		}

		// set the document directory.
		return $this->document_upload_dir_set( $dir );
	}

	/**
	 * Return the document upload file information.
	 *
	 * @since 3.5.0
	 *
	 * @param array<string, mixed> $dir defaults passed from WP.
	 * @return array<string, mixed> document directory
	 */
	public function document_upload_dir_set( array $dir ): array {

		self::$doc_image = false;
		$doc_dir         = untrailingslashit( $this->document_upload_dir() );

		// Core's subdir is either empty or starts with a slash ("/2026/09"). Joining it with
		// another slash produced "uploads//2026/09", which breaks stream wrappers such as s3://.
		$subdir = isset( $dir['subdir'] ) && '' !== $dir['subdir'] ? '/' . ltrim( (string) $dir['subdir'], '/' ) : '';

		$new_dir = array(
			'path'    => $doc_dir . $subdir,
			'url'     => home_url( '/' . $this->document_slug() ) . $subdir,
			'subdir'  => $subdir,
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

		$meta_sizes = $this->hide_size_file_names( $meta_sizes, $file_dir, $attach->post_title );

		// update the metadata.
		$meta['sizes']       = $meta_sizes;
		$meta['wpdr_hidden'] = 1;

		update_post_meta( $attachment_id, '_wp_attachment_metadata', $meta );

		// revert to media upload directory.
		self::$doc_image = true;
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
		 * @param bool $enable_feed Allows an RSS feed for documents.
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
	 * @param mixed $url the original URL.
	 * @param mixed $post_id the attachment ID.
	 * @return mixed the modified URL
	 */
	public function attachment_url_filter( $url, $post_id = 0 ) {
		// not an attached attachment.
		if ( ! is_string( $url ) || ! is_numeric( $post_id ) || (int) $post_id <= 0 || ! $this->verify_post_type( (int) $post_id ) ) {
			return $url;
		}
		$post_id = (int) $post_id;

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
		return get_permalink( absint( $revision_id ) );
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
	 * @param bool|array<int, mixed> $downsize Whether to short-circuit the image downsize.
	 * @param int                    $id       the ID of the attachment.
	 * @param string                 $size     the size requested.
	 * @return bool|array<int, mixed> false or the image array to be returned from image_downsize()
	 */
	public function image_downsize( $downsize, $id, $size ) {
		$id = (int) $id;
		// previous filter code wants to short-cut the process.
		if ( is_array( $downsize ) ) {
			return $downsize;
		}

		// not a document.
		if ( ! $this->verify_post_type( $id ) ) {
			return $downsize;
		}

		remove_filter( 'image_downsize', array( $this, 'image_downsize' ), 10 );
		remove_filter( 'wp_get_attachment_url', array( $this, 'attachment_url_filter' ) );

		$direct = wp_get_attachment_url( $id );
		$image  = image_downsize( $id, $size );

		add_filter( 'image_downsize', array( $this, 'image_downsize' ), 10, 3 );
		add_filter( 'wp_get_attachment_url', array( $this, 'attachment_url_filter' ), 10, 2 );

		// if WordPress is going to return the direct url to the real file,
		// serve the document permalink (or revision permalink) instead.
		if ( $image && $image[0] === $direct ) {
			$image[0] = wp_get_attachment_url( $id );
		}

		return $image;
	}

	/**
	 * Returns the WP_Filesystem instance when it can be used directly (no credentials) for a directory.
	 *
	 * @since 5.5.0
	 * @param string $dir directory the caller will work in.
	 * @return WP_Filesystem_Base|null the filesystem, or null when direct access is not available.
	 */
	private function direct_filesystem( string $dir ) {
		// file code may not be already loaded.
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( 'direct' !== get_filesystem_method( array(), $dir, false ) ) {
			return null;
		}

		// can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL.
		$creds = request_filesystem_credentials( site_url() . '/wp-admin/', 'direct', false, $dir, array(), false );
		if ( ! WP_Filesystem( $creds ) ) {
			return null;
		}

		global $wp_filesystem;
		return $wp_filesystem;
	}

	/**
	 * Renames generated image-size files that start with the attachment title to a random hashed name.
	 *
	 * A size entry is only updated when its file was actually moved, so the metadata
	 * never points at a file that is not there.
	 *
	 * @since 5.5.0
	 * @param array<string, mixed> $sizes    the 'sizes' element of the attachment metadata.
	 * @param string               $file_dir directory holding the files (with trailing slash).
	 * @param string               $title    attachment title the file names start with.
	 * @return array<string, mixed> the updated sizes.
	 */
	private function hide_size_file_names( array $sizes, string $file_dir, string $title ): array {
		$wp_filesystem = $this->direct_filesystem( $file_dir );
		$new_name      = self::random_file_name();
		foreach ( $sizes as $size => $sizeinfo ) {
			if ( 0 !== strpos( $sizeinfo['file'], $title ) || ! file_exists( $file_dir . $sizeinfo['file'] ) ) {
				continue;
			}
			$old_path = $file_dir . $sizeinfo['file'];
			$new_file = str_replace( $title, $new_name, $sizeinfo['file'] );
			$new_path = $file_dir . $new_file;
			if ( $wp_filesystem ) {
				if ( ! $wp_filesystem->move( $old_path, $new_path ) ) {
					continue;
				}
				$wp_filesystem->chmod( $new_path, FS_CHMOD_FILE );
			} else {
				// Use copy and unlink because rename breaks streams.
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( ! @copy( $old_path, $new_path ) ) {
					continue;
				}
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_chmod
				@chmod( $new_path, defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644 );
				wp_delete_file( $old_path );
			}
			$sizes[ $size ]['file'] = $new_file;
		}
		return $sizes;
	}
}
