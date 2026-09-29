<?php
/**
 * Tests that saving a document can only assign existing workflow states.
 *
 * @package WP_Document_Revisions
 */

/**
 * Workflow state term creation tests.
 */
class Test_WP_Document_Revisions_Zz_Workflow_State_Terms extends Test_Common_WPDR {

	/**
	 * Contributor user id.
	 *
	 * @var integer
	 */
	private static $contributor;

	/**
	 * Administrator user id.
	 *
	 * @var integer
	 */
	private static $admin;

	/**
	 * Draft document owned by the contributor.
	 *
	 * @var integer
	 */
	private static $contrib_doc;

	/**
	 * Document owned by the administrator.
	 *
	 * @var integer
	 */
	private static $admin_doc;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// phpcs:enable
		global $wpdr;
		$wpdr->register_ct();
		$wpdr->admin_init();
		$wpdr->add_caps();

		self::$contributor = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$admin       = $factory->user->create( array( 'role' => 'administrator' ) );
		$wpdr->add_caps();

		self::$contrib_doc = $factory->post->create(
			array(
				'post_title'  => 'Contributor draft',
				'post_status' => 'draft',
				'post_author' => self::$contributor,
				'post_type'   => 'document',
			)
		);

		self::$admin_doc = $factory->post->create(
			array(
				'post_title'  => 'Admin document',
				'post_status' => 'publish',
				'post_author' => self::$admin,
				'post_type'   => 'document',
			)
		);
	}

	/**
	 * Make sure the states used exist and reset the documents' state.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( 0 );
		foreach ( array( 'In Progress', 'Final' ) as $state ) {
			if ( ! term_exists( $state, 'workflow_state' ) ) {
				wp_insert_term( $state, 'workflow_state' );
			}
		}

		$in_progress = term_exists( 'In Progress', 'workflow_state' );
		wp_set_post_terms( self::$contrib_doc, array( (int) $in_progress['term_id'] ), 'workflow_state' );
		wp_set_post_terms( self::$admin_doc, array( (int) $in_progress['term_id'] ), 'workflow_state' );
	}

	/**
	 * Clean up the request.
	 */
	public function tear_down() {
		unset( $_POST['workflow_state_nonce'], $_POST['workflow_state'] );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Save a document through the workflow state metabox as a user.
	 *
	 * @param int    $user   user id.
	 * @param int    $doc_id document id.
	 * @param string $state  posted workflow state value.
	 */
	private function metabox_save( int $user, int $doc_id, string $state ): void {
		global $wpdr;

		wp_set_current_user( $user );
		$_POST['workflow_state_nonce'] = wp_create_nonce( 'wp-document-revisions' );
		$_POST['workflow_state']       = $state;

		$wpdr->admin->save_document( $doc_id );
	}

	/**
	 * The slug of a document's workflow state.
	 *
	 * @param int $doc_id document id.
	 * @return string
	 */
	private function state_slug( int $doc_id ): string {
		$terms = wp_get_post_terms( $doc_id, 'workflow_state' );
		return empty( $terms ) ? '' : $terms[0]->slug;
	}

	/**
	 * The number of workflow states.
	 *
	 * @return int
	 */
	private function count_states(): int {
		return (int) wp_count_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
			)
		);
	}

	/**
	 * A contributor can assign an existing state by slug.
	 */
	public function test_contributor_assigns_existing_state() {
		self::assertTrue( user_can( self::$contributor, 'edit_document', self::$contrib_doc ), 'contributor cannot edit' );

		$this->metabox_save( self::$contributor, self::$contrib_doc, 'final' );

		self::assertSame( 'final', $this->state_slug( self::$contrib_doc ) );
	}

	/**
	 * A contributor can assign an existing state by term id.
	 */
	public function test_contributor_assigns_existing_state_by_id() {
		$final = term_exists( 'Final', 'workflow_state' );

		$this->metabox_save( self::$contributor, self::$contrib_doc, (string) $final['term_id'] );

		self::assertSame( 'final', $this->state_slug( self::$contrib_doc ) );
	}

	/**
	 * An unknown state posted by a contributor is ignored.
	 */
	public function test_contributor_cannot_create_state_via_metabox() {
		$count = $this->count_states();

		$this->metabox_save( self::$contributor, self::$contrib_doc, 'Contributor Invented State' );

		self::assertNull( term_exists( 'Contributor Invented State', 'workflow_state' ), 'term created' );
		self::assertSame( $count, $this->count_states() );
		self::assertSame( 'in-progress', $this->state_slug( self::$contrib_doc ), 'state changed' );
	}

	/**
	 * A numeric value that is not a term id is ignored.
	 */
	public function test_contributor_cannot_create_state_with_unknown_id() {
		$count = $this->count_states();

		$this->metabox_save( self::$contributor, self::$contrib_doc, '987654' );

		self::assertNull( term_exists( '987654', 'workflow_state' ), 'term created' );
		self::assertSame( $count, $this->count_states() );
		self::assertSame( 'in-progress', $this->state_slug( self::$contrib_doc ), 'state changed' );
	}

	/**
	 * Posting the empty option still clears the state.
	 */
	public function test_empty_state_clears() {
		$this->metabox_save( self::$contributor, self::$contrib_doc, '' );

		self::assertSame( '', $this->state_slug( self::$contrib_doc ) );
	}

	/**
	 * A contributor cannot create a state through tax_input (quick/bulk edit, post.php).
	 */
	public function test_contributor_cannot_create_state_via_tax_input() {
		$count = $this->count_states();

		wp_set_current_user( self::$contributor );
		wp_update_post(
			array(
				'ID'        => self::$contrib_doc,
				'tax_input' => array( 'workflow_state' => 'Tax Input Invented State' ),
			)
		);

		self::assertNull( term_exists( 'Tax Input Invented State', 'workflow_state' ), 'term created' );
		self::assertSame( $count, $this->count_states() );
		self::assertSame( 'in-progress', $this->state_slug( self::$contrib_doc ), 'state changed' );
	}

	/**
	 * A contributor can still assign an existing state through tax_input.
	 */
	public function test_contributor_assigns_existing_state_via_tax_input() {
		wp_set_current_user( self::$contributor );
		wp_update_post(
			array(
				'ID'        => self::$contrib_doc,
				'tax_input' => array( 'workflow_state' => 'Final' ),
			)
		);

		self::assertSame( 'final', $this->state_slug( self::$contrib_doc ) );
	}

	/**
	 * An administrator assigns an existing state as before.
	 */
	public function test_admin_assigns_existing_state() {
		$this->metabox_save( self::$admin, self::$admin_doc, 'final' );

		self::assertSame( 'final', $this->state_slug( self::$admin_doc ) );
	}

	/**
	 * An administrator can still create a state through tax_input.
	 */
	public function test_admin_can_create_state_via_tax_input() {
		wp_set_current_user( self::$admin );
		wp_update_post(
			array(
				'ID'        => self::$admin_doc,
				'tax_input' => array( 'workflow_state' => 'Admin Created State' ),
			)
		);

		self::assertNotNull( term_exists( 'Admin Created State', 'workflow_state' ) );
		self::assertSame( 'admin-created-state', $this->state_slug( self::$admin_doc ) );
	}

	/**
	 * Seeding the default states still works when a contributor loads the admin first.
	 */
	public function test_contributor_seeds_default_states() {
		global $wpdr;

		$terms = get_terms(
			array(
				'taxonomy'   => 'workflow_state',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, 'workflow_state' );
		}

		wp_set_current_user( self::$contributor );
		$wpdr->initialize_workflow_states();

		self::assertSame( 4, $this->count_states() );
	}
}
