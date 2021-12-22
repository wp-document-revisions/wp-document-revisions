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
	 * @param function $function the function to call.
	 * @param array    $args the arguments to pass to the function.
	 * @returns mixed the result of the function.
	 */
	public function __call( $function, $args ) {
		return call_user_func_array( array( &self::$parent, $function ), $args );
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
		$route  = $request->get_route();
		$params = $request->get_params();
		$target = 'wp/v2/' . self::$parent->document_slug() . '/';
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
		$post_type = get_post_type_object( 'document' );
		$method    = $request->get_method();

		// Only GET method and POST for document editors are currently supported.
		if ( 'GET' !== $method ) {
			return new WP_Error(
				'rest_cannot_modify',
				__( 'Sorry, you are only allowed to read documents', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// No revisions unless allowed.
		if ( strpos( $route, '/revisions' ) && ! current_user_can( 'read_document_revisions' ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to view revisions.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// does user require read_documents and not just read to read document.
		if ( ! current_user_can( $post_type->cap->read ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to read documents.', 'wp-document-revisions' ),
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
	public function doc_clean_document( $response, $post, $request ) {
		// is it a document.
		if ( 'document' !== get_post_type( $post->ID ) ) {
			return $response;
		}

		// Possibly remove revisions.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			$response->remove_link( 'version-history' );
			$response->remove_link( 'predecessor-version' );
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
	public function doc_clean_revision( $response, $post, $request ) {
		// is it a document revision.
		$parent = $post->post_parent;
		if ( 0 === $parent || 'document' !== get_post_type( $parent ) ) {
			return $response;
		}

		// Possibly remove revisions.
		if ( ! current_user_can( 'read_document_revisions' ) ) {
			$response->remove_link( 'version-history' );
			$response->remove_link( 'predecessor-version' );
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
	public function doc_clean_attachment( $response, $post, $request ) {
		// is it a document attachment. (featured images have parent set to 0).
		$parent = $post->post_parent;
		if ( 0 < $parent && 'document' === get_post_type( $parent ) ) {
		if ( 0 < $parent && 'document' === get_post_type( $parent ) && ! current_user_can( 'read_post', $parent ) ) {
			$response->data['slug']              = __( '<!-- protected -->', 'wp-document-revisions' );
			$response->data['title']['rendered'] = __( '<!-- protected -->', 'wp-document-revisions' );
			if ( false === get_post_meta( $parent, '_wpdr_meta_hidden', true ) ) {
				// description may leak the slug as generated images would be built using the slug name.
				$response->data['description']['rendered'] = __( '<!-- protected -->', 'wp-document-revisions' );
				$response->data['media_details']           = __( '<!-- protected -->', 'wp-document-revisions' );
			}
		}

		return $response;
	}

}
