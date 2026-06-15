<?php
/**
 * WP Document Revisions Manage REST Functionality
 *
 * @author  Neil W. James <neil@familyjames.com>
 * @package WP Document Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WP_Document_Revisions Manage REST class.
 */
class WP_Document_Revisions_Manage_Rest {

	/**
	 * The parent WP Document Revisions instance
	 *
	 * @var object
	 */
	public static $parent;

	/**
	 * The singelton instance
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Constructor
	 *
	 * @since 3.4
	 * @param object $instance class instance.
	 * @return void
	 */
	public function __construct( ?object $instance = null ) {
		self::$instance = &$this;

		// create or store parent instance.
		if ( null === $instance ) {
			self::$parent = new WP_Document_Revisions();
		} else {
			self::$parent = $instance;
		}

		// additional validation.
		add_filter( 'rest_request_before_callbacks', array( $this, 'document_validation' ), 10, 3 );

		// hide data.
		add_filter( 'rest_prepare_document', array( $this, 'doc_clean_document' ), 10, 3 );
		add_filter( 'rest_prepare_revision', array( $this, 'doc_clean_revision' ), 10, 3 );
		add_filter( 'rest_prepare_attachment', array( $this, 'doc_clean_attachment' ), 10, 3 );

		// Block editor content/meta sync.
		if ( apply_filters( 'document_use_block_editor', false ) ) {
			add_filter( 'rest_pre_insert_document', array( $this, 'sync_meta_to_content' ), 10, 2 );
		}
	}

	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 3.4.0
	 *
	 * @param string $funct the function to call.
	 * @param array  $args  the arguments to pass to the function.
	 * @return mixed the result of the function.
	 */
	public function __call( string $funct, array $args ) {
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
	public static function document_validation( $response, array $handler, WP_REST_Request $request ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$post_type = get_post_type_object( 'document' );
		$route     = $request->get_route();
		$params    = $request->get_params();
		$target    = 'wp/v2/' . $post_type->rest_base . '/';
		if ( false === strpos( $route . '/', $target ) ) {
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
		if ( ! apply_filters( 'document_read_uses_read', true ) && ! current_user_can( 'read_documents' ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to read documents.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Check methods.
		$method = $request->get_method();
		if ( 'GET' !== $method ) {
			if ( in_array( $method, array( 'POST', 'PUT', 'DELETE' ), true ) && apply_filters( 'document_use_block_editor', false ) ) {
				// Block editor needs POST/PUT/DELETE. Verify nonce.
				$nonce = $request->get_header( 'x-wp-nonce' );
				if ( ! isset( $nonce ) || false === wp_verify_nonce( $nonce, 'wp_rest' ) ) {
					return new WP_Error(
						'rest_cannot_modify',
						__( 'Sorry, invalid REST call', 'wp-document-revisions' ),
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
	public function doc_clean_document( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
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

			// Hide attachment ID meta from non-editors to prevent attachment enumeration.
			$data = $response->get_data();
			if ( isset( $data['meta']['document_attachment_id'] ) ) {
				$data['meta']['document_attachment_id'] = 0;
				$response->set_data( $data );
			}
		}

		// Block editor: sync meta from content and strip WPDR comment from raw content.
		if ( apply_filters( 'document_use_block_editor', false ) && 'edit' === $request['context'] ) {
			$attach_id = $this->populate_attachment_meta( $post );
			$data      = $response->get_data();

			// Update meta in response if we populated it from content.
			if ( $attach_id && isset( $data['meta'] ) ) {
				$data['meta']['document_attachment_id'] = $attach_id;
			}

			// Strip WPDR comment so block editor only sees description content.
			if ( isset( $data['content']['raw'] ) ) {
				$data['content']['raw'] = preg_replace( '/<!-- WPDR \s*\d+ -->/', '', $data['content']['raw'] );
			}

			$response->set_data( $data );
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
	public function doc_clean_revision( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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

		// Strip internal WPDR attachment comment from revision content.
		$data = $response->get_data();
		if ( isset( $data['content']['raw'] ) ) {
			$data['content']['raw'] = preg_replace( '/<!-- WPDR \s*\d+ -->/', '', $data['content']['raw'] );
			$response->set_data( $data );
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
	public function doc_clean_attachment( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
		// protect various fields — prevent leaking MD5-hashed filenames, file paths, and URLs.
		$protected                                 = __( '<!-- protected -->', 'wp-document-revisions' );
		$response->data['slug']                    = $protected;
		$response->data['source_url']              = '';
		$response->data['title']['rendered']       = $protected;
		$response->data['title']['raw']            = $protected;
		$response->data['description']['rendered'] = $protected;
		$response->data['description']['raw']      = $protected;
		if ( isset( $response->data['guid']['rendered'] ) ) {
			$response->data['guid']['rendered'] = '';
		}
		if ( isset( $response->data['guid']['raw'] ) ) {
			$response->data['guid']['raw'] = '';
		}
		$response->data['link'] = '';

		// For non-editors, strip all media details to prevent file path leakage.
		$response->data['media_details'] = new stdClass();

		// Also protect the file name, size and mime type, which can leak the
		// (hashed) filename, reveal file size, or fingerprint the document type.
		$response->data['mime_type'] = $protected;
		if ( isset( $response->data['filename'] ) ) {
			$response->data['filename'] = $protected;
		}
		if ( isset( $response->data['filesize'] ) ) {
			$response->data['filesize'] = $protected;
		}

		// If the user cannot read the document at all, also drop the link back to
		// the parent document so the relationship can't be enumerated.
		if ( ! apply_filters( 'document_read_uses_read', true ) && ! current_user_can( 'read_document', $parent ) ) {
			$response->remove_link( 'https://api.w.org/attached-to' );
		}

		return $response;
	}


	/**
	 * Populates the document_attachment_id meta from post_content if empty.
	 *
	 * @since 3.9.1
	 * @param WP_Post $post Post object.
	 * @return int|false The attachment ID, or false if none found.
	 */
	private function populate_attachment_meta( WP_Post $post ) {
		$meta = absint( get_post_meta( $post->ID, 'document_attachment_id', true ) );
		if ( $meta ) {
			return $meta;
		}

		$wpdr      = self::$parent;
		$attach_id = $wpdr->extract_document_id( $post->post_content );
		if ( $attach_id ) {
			update_post_meta( $post->ID, 'document_attachment_id', $attach_id );
			return $attach_id;
		}

		return false;
	}


	/**
	 * Prepends the WPDR comment to post_content on REST save using the attachment meta.
	 *
	 * @since 3.9.1
	 * @param stdClass        $prepared_post An object representing a single post prepared for inserting or updating the database.
	 * @param WP_REST_Request $request       Request object.
	 * @return stdClass Modified post object.
	 */
	public function sync_meta_to_content( $prepared_post, WP_REST_Request $request ) {
		// Get attachment ID: prefer request meta, fall back to DB.
		$attach_id = 0;
		$meta      = $request->get_param( 'meta' );
		if ( isset( $meta['document_attachment_id'] ) ) {
			$attach_id = absint( $meta['document_attachment_id'] );
		} elseif ( ! empty( $prepared_post->ID ) ) {
			$attach_id = absint( get_post_meta( $prepared_post->ID, 'document_attachment_id', true ) );
		}

		if ( $attach_id ) {
			// Validate the attachment exists and is actually an attachment.
			$attachment = get_post( $attach_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return $prepared_post;
			}

			// Verify the attachment belongs to this document.
			if ( ! empty( $prepared_post->ID ) && (int) $attachment->post_parent !== (int) $prepared_post->ID ) {
				return $prepared_post;
			}

			$wpdr    = self::$parent;
			$content = isset( $prepared_post->post_content ) ? $prepared_post->post_content : '';
			// Strip any existing WPDR comment to avoid duplicates.
			$content = preg_replace( '/<!-- WPDR \s*\d+ -->/', '', $content );
			// Prepend the WPDR comment.
			$prepared_post->post_content = $wpdr->format_doc_id( $attach_id ) . $content;
		}

		return $prepared_post;
	}
}
