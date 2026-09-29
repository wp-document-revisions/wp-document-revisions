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

		// The attachment ID meta is server-only. Drop it from requests before core writes meta.
		add_filter( 'rest_pre_insert_document', array( $this, 'drop_attachment_meta' ), 5, 2 );

		// Block editor content/meta sync.
		if ( apply_filters( 'document_use_block_editor', false ) ) {
			add_filter( 'rest_pre_insert_document', array( $this, 'sync_meta_to_content' ), 10, 2 );
		}

		// Read-only file details on document responses. This class is created during
		// rest_api_init (priority 10), so hook a later priority to run in the same pass.
		add_action( 'rest_api_init', array( $this, 'register_document_file_field' ), 20 );
	}

	/**
	 * Registers the read-only document_file field on document REST responses.
	 *
	 * @since 5.6.0
	 * @return void
	 */
	public function register_document_file_field(): void {
		$post_type = get_post_type_object( 'document' );
		if ( ! $post_type || ! $post_type->show_in_rest ) {
			return;
		}

		register_rest_field(
			'document',
			'document_file',
			array(
				'get_callback' => array( $this, 'get_document_file_field' ),
				'schema'       => array(
					'description' => __( 'The current file of the document. Only shown to users who can edit the document; null otherwise or when there is no file.', 'wp-document-revisions' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'attachment_id' => array( 'type' => 'integer' ),
						'mime_type'     => array( 'type' => 'string' ),
						'extension'     => array( 'type' => 'string' ),
						'filesize'      => array( 'type' => array( 'integer', 'null' ) ),
						'url'           => array(
							'type'   => 'string',
							'format' => 'uri',
						),
					),
				),
			)
		);
	}

	/**
	 * Returns the document_file REST field for a document.
	 *
	 * Gated on edit_document, like the attachment details doc_clean_attachment() hides
	 * from other users. The URL is the document permalink, which checks permissions,
	 * not the file's storage location.
	 *
	 * @since 5.6.0
	 * @param array<string, mixed> $data the prepared response data.
	 * @return array<string, mixed>|null
	 */
	public function get_document_file_field( array $data ): ?array {
		$document_id = isset( $data['id'] ) ? (int) $data['id'] : 0;
		if ( $document_id <= 0 || ! current_user_can( 'edit_document', $document_id ) ) {
			return null;
		}

		$attach = self::$parent->get_document( $document_id );
		if ( ! $attach instanceof WP_Post ) {
			return null;
		}

		$file = get_attached_file( $attach->ID );

		return array(
			'attachment_id' => (int) $attach->ID,
			'mime_type'     => (string) get_post_mime_type( $attach->ID ),
			'extension'     => ltrim( self::$parent->get_extension( (string) get_post_meta( $attach->ID, '_wp_attached_file', true ) ), '.' ),
			'filesize'      => ( is_string( $file ) && is_file( $file ) ) ? (int) filesize( $file ) : null,
			'url'           => (string) get_permalink( $document_id ),
		);
	}

	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 3.4.0
	 *
	 * @param string  $funct the function to call.
	 * @param mixed[] $args  the arguments to pass to the function.
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
	 * @param array<string, mixed>                             $handler  Route handler used for the request.
	 * @param WP_REST_Request                                  $request  Request used to generate the response.
	 * @return WP_REST_Response|WP_HTTP_Response|WP_Error|mixed the (possibly modified) response.
	 **/
	public static function document_validation( $response, array $handler, WP_REST_Request $request ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$post_type = get_post_type_object( 'document' );
		// The REST server matches routes case-insensitively, so compare them that way too.
		$route  = strtolower( $request->get_route() );
		$params = $request->get_params();
		$target = strtolower( '/' . $post_type->rest_namespace . '/' . $post_type->rest_base . '/' );
		if ( false === strpos( $route . '/', $target ) ) {
			return $response;
		}

		// Check for valid document editing.
		if ( 'edit' === $request['context'] ) {
			$edit_ok = false;
			// standard route for document.
			if ( isset( $params['id'] ) && current_user_can( 'edit_document', $params['id'] ) ) {
				$edit_ok = true;
			}
			// route for revisions and autosaves.
			if ( isset( $params['parent'] ) && current_user_can( 'edit_document', $params['parent'] ) ) {
				$edit_ok = true;
			}
			// route for query loop. Make sure NOT an edit document/revision route.
			if ( trailingslashit( $route ) === $target && ! isset( $params['id'] ) && ! isset( $params['parent'] ) ) {
				// check the user can read documents.
				global $wp_post_types;
				$read_cap = $wp_post_types['document']->cap->read;
				if ( current_user_can( $read_cap ) ) {
					$edit_ok = true;
				}
			}
			if ( ! $edit_ok ) {
				return new WP_Error(
					'rest_forbidden_context',
					__( 'Sorry, you are not allowed to edit documents.', 'wp-document-revisions' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
			// An edit context read is allowed. Writes must still pass the method check below,
			// the edit context must not be a way around it.
			if ( 'GET' === $request->get_method() ) {
				return $response;
			}
		}

		// Additional validation for documents.

		// No revisions unless allowed.
		if ( strpos( $route . '/', '/revisions/' ) && ! current_user_can( 'read_document_revisions' ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to view revisions.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// does user require read_documents and not just read to read document.
		if ( ! apply_filters( 'document_read_uses_read', true ) ) {
			// read_document test depends on whether we have a specific id.
			if ( ! ( isset( $params['id'] ) ? current_user_can( 'read_document', $params['id'] ) : current_user_can( 'read_documents' ) ) ) {
				return new WP_Error(
					'rest_cannot_read',
					__( 'Sorry, you are not allowed to read documents.', 'wp-document-revisions' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
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

		// route is for a document. Make sure the document_attachment meta is set.
		if ( isset( $params['id'] ) ) {
			$wpdr    = self::$parent;
			$post_id = absint( $params['id'] );
			$content = get_post_field( 'post_content', $post_id );
			$attach  = $wpdr->populate_attachment_meta( $post_id, $content );
		}

		return $response;
	}

	/**
	 * Filters the document post data for a REST API response.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Response|WP_Error $response The response object (or error if found).
	 * @param WP_Post                   $post     Post object.
	 * @param WP_REST_Request           $request  Request object.
	 * @return WP_REST_Response|WP_Error the cleaned response.
	 */
	public function doc_clean_document( $response, WP_Post $post, WP_REST_Request $request ) {
		// Already filtered to an error response.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

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
			if ( isset( $data['meta']['_document_attachment_id'] ) ) {
				$data['meta']['_document_attachment_id'] = 0;
				$response->set_data( $data );
			}
		}

		// Block editor: sync meta from content and strip WPDR comment from raw content.
		if ( apply_filters( 'document_use_block_editor', false ) && 'edit' === $request['context'] ) {
			$wpdr      = self::$parent;
			$attach_id = $wpdr->populate_attachment_meta( $post->ID, $post->post_content );
			$data      = $response->get_data();

			// Update meta in response if we populated it from content.
			if ( $attach_id && isset( $data['meta'] ) ) {
				$data['meta']['_document_attachment_id'] = $attach_id;
			}

			// Strip WPDR comment so block editor only sees description content.
			if ( isset( $data['content']['raw'] ) ) {
				$data['content']['raw'] = ( is_numeric( $data['content']['raw'] ) ? '' : preg_replace( '/<!-- WPDR \d+\s*-->/', '', $data['content']['raw'] ) );
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
	 * @param WP_REST_Response|WP_Error $response The response object (or error if found).
	 * @param WP_Post                   $post     Post object.
	 * @param WP_REST_Request           $request  Request object.
	 * @return WP_REST_Response|WP_Error the cleaned response.
	 */
	public function doc_clean_revision( $response, WP_Post $post, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Already filtered to an error response.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// is it a document revision.
		$parent = $post->post_parent;
		if ( 0 === $parent || 'document' !== get_post_type( $parent ) ) {
			return $response;
		}

		// Possibly remove revisions.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to view this revision.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Strip internal WPDR attachment comment from revision content.
		$data = $response->get_data();
		if ( isset( $data['content']['raw'] ) ) {
			$data['content']['raw'] = preg_replace( '/<!-- WPDR \d+\s*-->/', '', $data['content']['raw'] );
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Filters the document attachment post data for a REST API response.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Response|WP_Error $response The response object (or error if found).
	 * @param WP_Post                   $post     Post object.
	 * @param WP_REST_Request           $request  Request object.
	 * @return WP_REST_Response|WP_Error the cleaned response.
	 */
	public function doc_clean_attachment( $response, WP_Post $post, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Already filtered to an error response.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

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
		$data                            = $response->get_data();
		$protected                       = __( '<!-- protected -->', 'wp-document-revisions' );
		$data['slug']                    = $protected;
		$data['source_url']              = '';
		$data['title']['rendered']       = $protected;
		$data['title']['raw']            = $protected;
		$data['description']['rendered'] = $protected;
		$data['description']['raw']      = $protected;
		if ( isset( $data['guid']['rendered'] ) ) {
			$data['guid']['rendered'] = '';
		}
		if ( isset( $data['guid']['raw'] ) ) {
			$data['guid']['raw'] = '';
		}
		$data['link'] = '';

		// For non-editors, strip all media details to prevent file path leakage.
		$data['filename']      = $protected;
		$data['filesize']      = $protected;
		$data['media_details'] = new stdClass();
		$data['mime_type']     = $protected;

		$response->set_data( $data );
		// if can't read the document, then remove the reference to the parent.
		if ( ! apply_filters( 'document_read_uses_read', true ) && ! current_user_can( 'read_document', $parent ) ) {
			$response->remove_link( 'https://api.w.org/attached-to' );
		}

		return $response;
	}


	/**
	 * Prepends the WPDR comment to post_content on REST save using the attachment meta.
	 *
	 * @since 3.9.1
	 * @param stdClass        $prepared_post An object representing a single post prepared for inserting or updating the database.
	 * @param WP_REST_Request $request       Request object.
	 * @return stdClass|WP_Error Modified post object, or an error if the attachment does not belong to the document.
	 */
	public function sync_meta_to_content( $prepared_post, WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$wpdr = self::$parent;
		// Get attachment ID from the server, never the request: the stored meta, else the stored content.
		$attach_id = absint( get_post_meta( $prepared_post->ID, '_document_attachment_id', true ) );
		if ( ! $attach_id ) {
			$content   = get_post_field( 'post_content', $prepared_post->ID );
			$attach_id = absint( $wpdr->extract_document_id( $content ) );
		}

		// Only rewrite content that the request actually supplies.
		if ( ! isset( $prepared_post->post_content ) ) {
			return $prepared_post;
		}

		// The request content is forgeable. Never keep a WPDR marker from it; the marker is
		// rebuilt below from an attachment verified to belong to this document.
		$content = $prepared_post->post_content;
		$content = ( is_numeric( $content ) ? '' : preg_replace( '/<!-- WPDR \s*\d+ -->/i', '', $content ) );

		// check the attachment data.
		if ( $attach_id ) {
			// Validate the attachment exists, is an attachment and belongs to this document.
			$attachment = get_post( $attach_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type || empty( $prepared_post->ID ) || (int) $attachment->post_parent !== (int) $prepared_post->ID ) {
				return new WP_Error(
					'rest_invalid_param',
					__( 'Invalid document attachment.', 'wp-document-revisions' ),
					array( 'status' => 400 )
				);
			}

			// Prepend the WPDR comment.
			$content = $wpdr->format_doc_id( $attach_id ) . $content;
		}

		$prepared_post->post_content = $content;

		return $prepared_post;
	}

	/**
	 * Drops the attachment ID meta from a document REST write.
	 *
	 * The meta is set by the server for the server, so a client may not change it. Dropping it
	 * (rather than failing the save) lets the block editor, which echoes the value back, keep saving.
	 *
	 * @since 5.6.0
	 * @param stdClass        $prepared_post An object representing a single post prepared for inserting or updating the database.
	 * @param WP_REST_Request $request       Request object.
	 * @return stdClass The unchanged post object.
	 */
	public function drop_attachment_meta( $prepared_post, WP_REST_Request $request ) {
		$meta = $request->get_param( 'meta' );
		if ( is_array( $meta ) && array_key_exists( '_document_attachment_id', $meta ) ) {
			unset( $meta['_document_attachment_id'] );
			$request->set_param( 'meta', $meta );
		}

		return $prepared_post;
	}
}
