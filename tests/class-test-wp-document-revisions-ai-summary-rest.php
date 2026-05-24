<?php
/**
 * Tests for the AI summary REST endpoints.
 *
 * Phase 11 of issue #514: verifies the read endpoint's status taxonomy
 * (pending / ready / unavailable), the review endpoint's write path,
 * and Neil's capability mapping — `read_document` for read,
 * `edit_document` for marking reviewed, 404 (not 401) when the claimed
 * revision does not belong to the claimed document so the response
 * does not let an unauthorised caller probe for which documents own
 * which attachments.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for WP_Document_Revisions_AI_Summary_REST.
 */
class Test_WP_Document_Revisions_AI_Summary_REST extends Test_Common_WPDR {

	/**
	 * REST server, re-initialised per test so route registration is
	 * deterministic.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Stand up a fresh REST server and register routes for each test.
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down the REST server reference so it does not leak into
	 * other tests.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Create a document with one attached revision and return both IDs.
	 *
	 * @return array{0:int,1:int} [ document_id, attachment_id ].
	 */
	private function create_document_with_attachment(): array {
		$doc_id = self::factory()->post->create(
			array(
				'post_title'  => 'AI Summary REST Test',
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
		self::add_document_attachment( self::factory(), $doc_id, self::$test_file );

		global $wpdr;
		$revision = $wpdr->get_latest_revision( $doc_id );
		return array( (int) $doc_id, (int) $revision->post_content );
	}

	/**
	 * Build a relative REST route for the summary endpoint.
	 *
	 * @param int    $doc_id document post ID.
	 * @param int    $rev_id revision attachment ID.
	 * @param string $suffix optional suffix (e.g. '/review').
	 * @return string the full route path.
	 */
	private function summary_route( int $doc_id, int $rev_id, string $suffix = '' ): string {
		return sprintf( '/wpdr/v1/documents/%d/revisions/%d/summary%s', $doc_id, $rev_id, $suffix );
	}

	/**
	 * GET returns `pending` when no summary has been stored yet.
	 */
	public function test_get_returns_pending_when_no_summary_stored() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();

		$response = $this->server->dispatch(
			new WP_REST_Request( 'GET', $this->summary_route( $doc_id, $attach_id ) )
		);

		self::assertSame( 200, $response->get_status() );
		self::assertSame( array( 'status' => 'pending' ), $response->get_data() );
	}

	/**
	 * GET returns the stored summary as `ready` once meta exists.
	 */
	public function test_get_returns_ready_with_stored_summary() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		WP_Document_Revisions_AI_Summary::store( $attach_id, 'change', 'Section 4.2 updated.', 'input-hash' );

		$response = $this->server->dispatch(
			new WP_REST_Request( 'GET', $this->summary_route( $doc_id, $attach_id ) )
		);

		self::assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		self::assertSame( 'ready', $data['status'] );
		self::assertSame( 'change', $data['kind'] );
		self::assertSame( 'Section 4.2 updated.', $data['summary'] );
		self::assertGreaterThan( 0, $data['generated_at'] );
	}

	/**
	 * GET reports `unavailable` when the cron stored an unavailable
	 * record (e.g. AI was unreachable / no extractable text).
	 */
	public function test_get_returns_unavailable_when_kind_is_unavailable() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		WP_Document_Revisions_AI_Summary::store( $attach_id, 'unavailable', '', '' );

		$response = $this->server->dispatch(
			new WP_REST_Request( 'GET', $this->summary_route( $doc_id, $attach_id ) )
		);

		self::assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		self::assertSame( 'unavailable', $data['status'] );
		self::assertArrayNotHasKey( 'summary', $data, 'unavailable response should not expose a summary field' );
	}

	/**
	 * Anonymous callers are rejected on the read endpoint.
	 */
	public function test_get_requires_read_document_capability() {
		wp_set_current_user( 0 );
		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();

		$response = $this->server->dispatch(
			new WP_REST_Request( 'GET', $this->summary_route( $doc_id, $attach_id ) )
		);

		self::assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * GET returns 404 when the revision does not belong to the claimed
	 * document — by design, so the response shape cannot be used to
	 * probe attachment-to-document relationships without authorisation.
	 */
	public function test_get_returns_404_when_revision_does_not_match_document() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		list( $doc_a, ) = $this->create_document_with_attachment();
		list( , $rev_b ) = $this->create_document_with_attachment();

		$response = $this->server->dispatch(
			new WP_REST_Request( 'GET', $this->summary_route( $doc_a, $rev_b ) )
		);

		self::assertSame( 404, $response->get_status() );
	}

	/**
	 * POST review marks the summary reviewed by the current user when
	 * a summary has already been stored.
	 */
	public function test_review_marks_summary_reviewed() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		WP_Document_Revisions_AI_Summary::store( $attach_id, 'change', 'summary', 'hash' );

		$request = new WP_REST_Request( 'POST', $this->summary_route( $doc_id, $attach_id, '/review' ) );
		$request->set_param( 'reviewed', true );
		$response = $this->server->dispatch( $request );

		self::assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		self::assertSame( $editor_id, $data['reviewed_by'] );
		self::assertGreaterThan( 0, $data['reviewed_at'] );
	}

	/**
	 * POST review with reviewed=false un-marks a previously-reviewed
	 * summary.
	 */
	public function test_review_unmarks_when_reviewed_false() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		WP_Document_Revisions_AI_Summary::store( $attach_id, 'change', 'summary', 'hash' );
		WP_Document_Revisions_AI_Summary::set_reviewed( $attach_id, $editor_id );

		$request = new WP_REST_Request( 'POST', $this->summary_route( $doc_id, $attach_id, '/review' ) );
		$request->set_param( 'reviewed', false );
		$response = $this->server->dispatch( $request );

		self::assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		self::assertSame( 0, $data['reviewed_by'] );
		self::assertSame( 0, $data['reviewed_at'] );
	}

	/**
	 * Subscribers (no edit_document cap) are rejected on the review endpoint.
	 */
	public function test_review_requires_edit_document_capability() {
		$sub_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $sub_id );

		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();
		WP_Document_Revisions_AI_Summary::store( $attach_id, 'change', 'summary', 'hash' );

		$request = new WP_REST_Request( 'POST', $this->summary_route( $doc_id, $attach_id, '/review' ) );
		$response = $this->server->dispatch( $request );

		self::assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * POST review returns 404 when there is nothing to review yet
	 * (cron has not produced a summary for this revision).
	 */
	public function test_review_returns_404_when_no_summary_exists() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		list( $doc_id, $attach_id ) = $this->create_document_with_attachment();

		$request = new WP_REST_Request( 'POST', $this->summary_route( $doc_id, $attach_id, '/review' ) );
		$response = $this->server->dispatch( $request );

		self::assertSame( 404, $response->get_status() );
	}
}
