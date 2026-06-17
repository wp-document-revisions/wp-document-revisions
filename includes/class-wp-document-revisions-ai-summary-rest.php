<?php
/**
 * REST endpoints for AI revision summaries.
 *
 * Exposes two routes under the `wpdr/v1` namespace:
 *
 *   GET  /wpdr/v1/documents/<doc_id>/revisions/<rev_id>/summary
 *       Reads the cached AI summary. Returns a `status` field with one of
 *       'ready', 'pending' (cron has not produced a summary yet), or
 *       'unavailable' (AI was unreachable or the new revision has no
 *       extractable text). Capability: `read_document` on the document.
 *
 *   POST /wpdr/v1/documents/<doc_id>/revisions/<rev_id>/summary/review
 *       Marks (or un-marks) the cached summary as human-reviewed by the
 *       current user. Capability: `edit_document` on the document.
 *
 *   GET  /wpdr/v1/documents/<doc_id>/revisions/<rev_id>/diff
 *       Returns the unified diff between the revision and the one that
 *       precedes it — the same change signal that drives the summary.
 *       Capability: `read_document` on the document AND
 *       `read_document_revisions` (revisions are more sensitive than the
 *       current file, so this mirrors the revision-listing gate).
 *
 * Generation itself is NOT exposed over REST — it runs on cron after
 * extraction completes (see {@see WP_Document_Revisions_AI_Summary}).
 * The capability mapping mirrors @NeilWJames's input on issue #514:
 * reading a summary is gated like reading the document file, marking
 * reviewed is gated like editing, and the per-revision diff record is
 * gated like listing revisions (`read_document_revisions`).
 *
 * Phase 11 of issue #514 (diff route added as a #531 follow-up).
 *
 * @since 5.0.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller for the AI summary endpoints.
 */
class WP_Document_Revisions_AI_Summary_REST {

	/**
	 * REST namespace for the AI summary endpoints.
	 *
	 * @var string
	 */
	const ROUTE_NAMESPACE = 'wpdr/v1';

	/**
	 * Register the controller's routes with the REST API.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register the read and review routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/documents/(?P<doc_id>\d+)/revisions/(?P<rev_id>\d+)/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_summary' ),
				'permission_callback' => array( __CLASS__, 'can_read_summary' ),
				'args'                => self::id_args(),
			)
		);
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/documents/(?P<doc_id>\d+)/revisions/(?P<rev_id>\d+)/summary/review',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'mark_reviewed' ),
				'permission_callback' => array( __CLASS__, 'can_review_summary' ),
				'args'                => array_merge(
					self::id_args(),
					array(
						'reviewed' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => 'Pass false to un-mark a previously-reviewed summary.',
						),
					)
				),
			)
		);
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/documents/(?P<doc_id>\d+)/revisions/(?P<rev_id>\d+)/diff',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_diff' ),
				'permission_callback' => array( __CLASS__, 'can_read_diff' ),
				'args'                => self::id_args(),
			)
		);
	}

	/**
	 * Permission callback: caller must have `read_document` on the
	 * claimed document, AND the claimed revision must actually be an
	 * attachment of that document.
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return true|WP_Error
	 */
	public static function can_read_summary( WP_REST_Request $request ) {
		$ids = self::resolve_ids( $request );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		if ( ! current_user_can( 'read_document', $ids['doc_id'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Insufficient permission to read this document.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Permission callback: caller must have `edit_document` on the
	 * claimed document, AND the claimed revision must actually be an
	 * attachment of that document.
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return true|WP_Error
	 */
	public static function can_review_summary( WP_REST_Request $request ) {
		$ids = self::resolve_ids( $request );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		if ( ! current_user_can( 'edit_document', $ids['doc_id'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Insufficient permission to mark this summary reviewed.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Permission callback for the diff route: caller must be able to read
	 * the document AND have the `read_document_revisions` capability, AND
	 * the claimed revision must be an attachment of that document.
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return true|WP_Error
	 */
	public static function can_read_diff( WP_REST_Request $request ) {
		$ids = self::resolve_ids( $request );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		if ( ! current_user_can( 'read_document', $ids['doc_id'] ) || ! current_user_can( 'read_document_revisions' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Insufficient permission to read this document revision diff.', 'wp-document-revisions' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * GET handler — return the cached summary as a JSON envelope.
	 *
	 * Response shape (always includes `status`):
	 *
	 *   { "status": "pending" }
	 *   { "status": "unavailable", "kind": "unavailable" }
	 *   {
	 *     "status": "ready",
	 *     "kind": "change"|"document"|"no_change",
	 *     "summary": "...",
	 *     "generated_at": 1234567890,
	 *     "reviewed_by": 0,
	 *     "reviewed_at": 0
	 *   }
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return WP_REST_Response
	 */
	public static function get_summary( WP_REST_Request $request ): WP_REST_Response {
		$ids = self::resolve_ids( $request );
		// resolve_ids() already validated for the permission callback;
		// we know it's an array here, not a WP_Error.

		$stored = WP_Document_Revisions_AI_Summary::get( $ids['rev_id'] );
		if ( null === $stored ) {
			return new WP_REST_Response( array( 'status' => 'pending' ), 200 );
		}
		if ( 'unavailable' === $stored['kind'] ) {
			return new WP_REST_Response(
				array(
					'status' => 'unavailable',
					'kind'   => 'unavailable',
				),
				200
			);
		}
		return new WP_REST_Response(
			array(
				'status'       => 'ready',
				'kind'         => $stored['kind'],
				'summary'      => $stored['text'],
				'generated_at' => $stored['generated_at'],
				'reviewed_by'  => $stored['reviewed_by'],
				'reviewed_at'  => $stored['reviewed_at'],
			),
			200
		);
	}

	/**
	 * GET handler — return the unified diff between the revision and the
	 * one that precedes it within the same document.
	 *
	 * Response shape (always includes `status`):
	 *
	 *   { "status": "no_prior", "diff": "", "prior_revision": 0 }
	 *   { "status": "identical"|"too_large"|"old_text_missing"|"new_text_missing",
	 *     "diff": "", "prior_revision": 123 }
	 *   { "status": "ok", "diff": "@@ ...", "prior_revision": 123 }
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return WP_REST_Response
	 */
	public static function get_diff( WP_REST_Request $request ): WP_REST_Response {
		$ids = self::resolve_ids( $request );
		// resolve_ids() already validated for the permission callback.

		$prior_id = WP_Document_Revisions_AI_Summary::prior_revision_id( $ids['rev_id'], $ids['doc_id'] );
		if ( 0 === $prior_id ) {
			// Initial upload — nothing precedes it to diff against.
			return new WP_REST_Response(
				array(
					'status'         => 'no_prior',
					'diff'           => '',
					'prior_revision' => 0,
				),
				200
			);
		}

		$result = WP_Document_Revisions_Text_Diff::diff_revisions( $prior_id, $ids['rev_id'] );

		return new WP_REST_Response(
			array(
				'status'         => $result['status'],
				'diff'           => $result['diff'],
				'prior_revision' => $prior_id,
			),
			200
		);
	}

	/**
	 * POST review handler — mark (or un-mark) the cached summary as
	 * reviewed by the current user.
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function mark_reviewed( WP_REST_Request $request ) {
		$ids = self::resolve_ids( $request );

		$reviewed = (bool) $request->get_param( 'reviewed' );
		$user_id  = $reviewed ? get_current_user_id() : 0;

		$ok = WP_Document_Revisions_AI_Summary::set_reviewed( $ids['rev_id'], $user_id );
		if ( ! $ok ) {
			return new WP_Error(
				'wpdr_summary_not_found',
				__( 'No summary has been generated for this revision yet.', 'wp-document-revisions' ),
				array( 'status' => 404 )
			);
		}

		$stored = WP_Document_Revisions_AI_Summary::get( $ids['rev_id'] );
		return new WP_REST_Response(
			array(
				'reviewed_by' => null === $stored ? 0 : (int) $stored['reviewed_by'],
				'reviewed_at' => null === $stored ? 0 : (int) $stored['reviewed_at'],
			),
			200
		);
	}

	/**
	 * Validate that the claimed revision is actually an attachment
	 * whose parent is the claimed document. Returns an associative
	 * array of `{doc_id, rev_id}` on success or a WP_Error on
	 * mismatch — surfaced from permission callbacks as a 404 so the
	 * caller cannot probe for the existence of arbitrary documents
	 * or attachments via the response shape.
	 *
	 * @param WP_REST_Request $request incoming request.
	 * @return array{doc_id: int, rev_id: int}|WP_Error
	 */
	private static function resolve_ids( WP_REST_Request $request ) {
		$doc_id = (int) $request->get_param( 'doc_id' );
		$rev_id = (int) $request->get_param( 'rev_id' );
		if ( $doc_id <= 0 || $rev_id <= 0 ) {
			return new WP_Error( 'rest_not_found', '', array( 'status' => 404 ) );
		}
		if ( 'document' !== get_post_type( $doc_id ) ) {
			return new WP_Error( 'rest_not_found', '', array( 'status' => 404 ) );
		}
		if ( 'attachment' !== get_post_type( $rev_id ) ) {
			return new WP_Error( 'rest_not_found', '', array( 'status' => 404 ) );
		}
		if ( (int) wp_get_post_parent_id( $rev_id ) !== $doc_id ) {
			return new WP_Error( 'rest_not_found', '', array( 'status' => 404 ) );
		}
		return array(
			'doc_id' => $doc_id,
			'rev_id' => $rev_id,
		);
	}

	/**
	 * Shared `args` schema fragment for the `doc_id` / `rev_id` path
	 * parameters used by both routes.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function id_args(): array {
		return array(
			'doc_id' => array(
				'type'        => 'integer',
				'required'    => true,
				'description' => 'Document post ID.',
			),
			'rev_id' => array(
				'type'        => 'integer',
				'required'    => true,
				'description' => 'Revision attachment post ID.',
			),
		);
	}
}
