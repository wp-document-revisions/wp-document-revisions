<?php
/**
 * WP Document Revisions Manage REST Functionality
 *
 * @author  Neil W. James <neil@familyjames.com>
 * @package WP Document Revisions
 */

/**
 * Main WP_Document_Revisions Manage REST class.
 */
class WP_Document_Revisions_Manage_Rest {

	/**
	 * The parent WP Document Revisions instance
	 *
	 * @var $parent
	 */
	public static $parent;

	/**
	 * The singelton instance
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Constructor
	 *
	 * @since 3.4
	 * @param object $instance class instance.
	 * @return void
	 */
	public function __construct( &$instance = null ) {
		self::$instance = &$this;

		// create or store parent instance.
		if ( null === $instance ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = &$instance;
		}

		// additional validation.
		add_filter( 'rest_request_before_callbacks', array( &$this, 'document_validation' ), 10, 3 );

		// hide data.
		add_filter( 'rest_prepare_document', array( &$this, 'doc_clean_document' ), 10, 3 );
		add_filter( 'rest_prepare_revision', array( &$this, 'doc_clean_revision' ), 10, 3 );
		add_filter( 'rest_prepare_attachment', array( &$this, 'doc_clean_attachment' ), 10, 3 );
	}

	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 3.4.0
	 *
	 * @param function $funct the function to call.
	 * @param array    $args  the arguments to pass to the function.
	 * @returns mixed the result of the function.
	 */
	public function __call( $funct, $args ) {
		return call_user_func_array( array( &self::$parent, $funct ), $args );
	}

	/**
	 * Provide plugin specific addition validation.
	 *
	 * @since 3.4.0
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
	 *                                                                   Usually a WP_REST_Response or WP_Error.
	 * @param array                                            $handler  Route handler used for the request.
	 * @param WP_REST_Request                                  $request  Request used to generate the response.
	 **/
	public static function document_validation( $response, $handler, $request ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$post_type = get_post_type_object( 'document' );
		$route     = $request->get_route();
		$params    = $request->get_params();
		$target    = 'wp/v2/' . $post_type->rest_base . '/';
		if ( ! strpos( $route . '/', $target ) ) {
			return $response;
		}

		// Check for valid document editing.
		if ( 'edit' === $request['context'] ) {
			if ( isset( $params['id'] ) && current_user_can( 'edit_post', $params['id'] ) ) {
				return $response;
			}
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit documents.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Additional validation for documents.

		// No revisions unless allowed.
		if ( strpos( $route, '/revisions/' ) && ! current_user_can( 'read_document_revisions' ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to view revisions.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// does user require read_documents and not just read to read document.
		if ( apply_filters( 'document_read_uses_read', true ) || current_user_can( 'read_documents' ) ) {
			null;
		} else {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to read documents.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Check methods.
		$method = $request->get_method();
		if ( 'GET' === $method ) {
			// Only GET method always supported.
			null;
		} elseif ( 'PUT' === $method && apply_filters( 'document_use_block_editor', false ) ) {
			// Editor usage needs review.
			// Check nonce.
			$nonce = $request->get_header( 'x-wp-nonce' );
			if ( isset( $nonce ) && false !== wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				// nonce OK.
				null;
			} else {
				return new WP_Error(
					'rest_cannot_modify',
					__( 'Sorry, invalid PUT call', 'wp-document-revisions' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
		} else {
			return new WP_Error(
				'rest_cannot_modify',
				__( 'Sorry, you are only allowed to use GET method', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return $response;
	}

	/**
	 * Filters the document post data for a REST API response.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 */
	public function doc_clean_document( $response, $post, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// is it a document.
		if ( 'document' !== get_post_type( $post->ID ) ) {
			return $response;
		}

		// Possibly remove revisions.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			$response->remove_link( 'version-history' );
			$response->remove_link( 'predecessor-version' );
		}

		// Possibly remove attachment.
		if ( ! current_user_can( 'edit_document', $post->ID ) ) {
			$response->remove_link( 'https://api.w.org/attachment' );
		}

		return $response;
	}

	/**
	 * Filters the document revision post data for a REST API response.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 */
	public function doc_clean_revision( $response, $post, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// is it a document revision.
		$parent = $post->post_parent;
		if ( 0 === $parent || 'document' !== get_post_type( $parent ) ) {
			return $response;
		}

		// Possibly remove revisions.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to view revisions.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return $response;
	}

	/**
	 * Filters the document attachment post data for a REST API response.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 */
	public function doc_clean_attachment( $response, $post, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// is it a document attachment. (featured images have parent set to 0).
		$parent = $post->post_parent;
		if ( 0 < $parent && 'document' === get_post_type( $parent ) ) {
			// a document.
			if ( current_user_can( 'edit_document', $parent ) ) {
				// can edit, so dont hide details.
				return $response;
			}
		} else {
			// not for us.
			return $response;
		}

		// media always thinks that the attachments are in media directory. We may need to change it.
		$wpdr    = self::$parent;
		$std_dir = $wpdr::$wp_default_dir['basedir'];
		$doc_dir = $wpdr->document_upload_dir();

		// protect various fields.
		$response->data['slug']              = __( '<!-- protected -->', 'wp-document-revisions' );
		$response->data['title']['rendered'] = __( '<!-- protected -->', 'wp-document-revisions' );
		// description may leak the slug as generated images would be built using the slug name.
		$response->data['description']['rendered'] = __( '<!-- protected -->', 'wp-document-revisions' );

		// deal with meta_data - media_details).
		if ( isset( $response->data['media_details'] ) ) {
			if ( $response->data['media_details'] instanceof stdClass ) {
				// attachment meta data not present so cannot expose anything.
				null;
			} elseif ( false === get_post_meta( $parent, '_wpdr_meta_hidden', true ) ) {
				// cannot trust the metadate, treat as not present.
				$response->data['media_details'] = new stdClass();
			} elseif ( $doc_dir !== $std_dir ) {
				// need to correct link.
				if ( isset( $response->data['media_details']['sizes'] ) ) {
					$block = $response->data['media_details']['sizes'];
					require_once ABSPATH . '/wp-admin/includes/file.php';
					$home    = get_home_path();
					$std_dir = trailingslashit( site_url() ) . str_replace( $home, '', $std_dir );
					$doc_dir = trailingslashit( site_url() ) . str_replace( $home, '', $doc_dir );
					foreach ( $block as $size => $sizeinfo ) {
						if ( isset( $sizeinfo['source_url'] ) ) {
							$block[ $size ]['source_url'] = str_replace( $std_dir, $doc_dir, $sizeinfo['source_url'] );
						}
					}
					$response->data['media_details']['sizes'] = $block;
				}
			}
		}

		return $response;
	}
}
