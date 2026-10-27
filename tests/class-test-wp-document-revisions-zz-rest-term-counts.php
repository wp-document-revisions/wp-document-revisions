<?php
/**
 * Tests that REST term counts don't reveal non-public documents.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * REST term count tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_REST_Term_Counts extends WP_UnitTestCase {

	/**
	 * Workflow state with only non-public documents (three of them).
	 *
	 * @var int
	 */
	private static $hidden_term;

	/**
	 * Workflow state with one published and one private document.
	 *
	 * @var int
	 */
	private static $mixed_term;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private static $subscriber;

	/**
	 * Author user ID (can edit documents, but not others' drafts or private ones).
	 *
	 * @var int
	 */
	private static $author;

	/**
	 * Create the terms and documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		// Earlier test classes unregister the taxonomy.
		$wpdr->register_ct();

		self::$editor      = $factory->user->create( array( 'role' => 'editor' ) );
		self::$subscriber  = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$author      = $factory->user->create( array( 'role' => 'author' ) );
		self::$hidden_term = $factory->term->create(
			array(
				'taxonomy' => 'workflow_state',
				'name'     => 'Hidden Review',
			)
		);
		self::$mixed_term  = $factory->term->create(
			array(
				'taxonomy' => 'workflow_state',
				'name'     => 'Mixed Review',
			)
		);

		$docs = array(
			array( 'private', self::$hidden_term ),
			array( 'draft', self::$hidden_term ),
			array( 'pending', self::$hidden_term ),
			array( 'publish', self::$mixed_term ),
			array( 'private', self::$mixed_term ),
		);
		foreach ( $docs as $doc ) {
			$id = $factory->post->create(
				array(
					'post_type'   => 'document',
					'post_status' => $doc[0],
					'post_author' => self::$editor,
				)
			);
			wp_set_object_terms( $id, array( $doc[1] ), 'workflow_state' );
		}
	}

	/**
	 * Start each test with a fresh REST server, so rest_api_init (which adds the count filter) runs again
	 * after the previous test's hooks were reset.
	 */
	public function set_up() {
		parent::set_up();
		$GLOBALS['wp_rest_server'] = null;
	}

	/**
	 * Fetch the workflow state counts over REST as the given user.
	 *
	 * @param int $user user to request as.
	 * @return array<int, int> counts keyed by term ID, from the collection and single-term routes.
	 */
	private function rest_counts( $user ) {
		wp_set_current_user( $user );

		$request = new WP_REST_Request( 'GET', '/wp/v2/workflow_state' );
		$request->set_param( 'hide_empty', false );
		$response = rest_do_request( $request );
		self::assertSame( 200, $response->get_status() );

		$counts = array();
		foreach ( $response->get_data() as $term ) {
			$counts[ $term['id'] ] = $term['count'];
		}

		$single = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/workflow_state/' . self::$hidden_term ) );
		self::assertSame( $counts[ self::$hidden_term ], $single->get_data()['count'], 'Single-term route matches the collection.' );

		return $counts;
	}

	/**
	 * Anonymous visitors see only published documents in the counts.
	 */
	public function test_anonymous_counts_published_only() {
		$counts = $this->rest_counts( 0 );

		self::assertSame( 0, $counts[ self::$hidden_term ] );
		self::assertSame( 1, $counts[ self::$mixed_term ] );
	}

	/**
	 * Logged-in users who can't see others' non-public documents get the published counts too.
	 */
	public function test_subscriber_and_author_counts_published_only() {
		foreach ( array( self::$subscriber, self::$author ) as $user ) {
			$counts = $this->rest_counts( $user );

			self::assertSame( 0, $counts[ self::$hidden_term ] );
			self::assertSame( 1, $counts[ self::$mixed_term ] );
		}
	}

	/**
	 * Fetch the IDs of the workflow states a REST collection request returns.
	 *
	 * @param int                  $user   user to request as.
	 * @param array<string, mixed> $params request parameters.
	 * @return int[]
	 */
	private function rest_term_ids( $user, $params ) {
		wp_set_current_user( $user );

		$request = new WP_REST_Request( 'GET', '/wp/v2/workflow_state' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = rest_do_request( $request );
		self::assertSame( 200, $response->get_status() );

		return array_values( array_intersect( wp_list_pluck( $response->get_data(), 'id' ), array( self::$hidden_term, self::$mixed_term ) ) );
	}

	/**
	 * Hiding empty terms doesn't list a term that only non-public documents carry.
	 */
	public function test_hide_empty_uses_published_counts() {
		self::assertSame( array( self::$mixed_term ), $this->rest_term_ids( 0, array( 'hide_empty' => true ) ) );
		self::assertEqualSets( array( self::$hidden_term, self::$mixed_term ), $this->rest_term_ids( self::$editor, array( 'hide_empty' => true ) ) );
	}

	/**
	 * Ordering by count doesn't rank terms by their non-public documents.
	 */
	public function test_orderby_count_ignores_hidden_documents() {
		// The stored counts (3 vs 2) would put Hidden Review first; published counts and names don't.
		$params = array(
			'orderby' => 'count',
			'order'   => 'desc',
			'include' => array( self::$hidden_term, self::$mixed_term ),
		);
		self::assertSame( array( self::$mixed_term, self::$hidden_term ), $this->rest_term_ids( 0, $params ) );
		self::assertSame( array( self::$hidden_term, self::$mixed_term ), $this->rest_term_ids( self::$editor, $params ) );
	}

	/**
	 * Editors still get the all-status counts the Documents screens use.
	 */
	public function test_editor_counts_all_statuses() {
		$counts = $this->rest_counts( self::$editor );

		self::assertSame( 3, $counts[ self::$hidden_term ] );
		self::assertSame( 2, $counts[ self::$mixed_term ] );
	}
}
