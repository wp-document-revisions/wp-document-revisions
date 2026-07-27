<?php
/**
 * Tests the document email notifications (workflow-state changes and new revisions).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document notification tests.
 */
class Test_WP_Document_Revisions_Notifications extends Test_Common_WPDR {

	/**
	 * Author user id (email author@example.com).
	 *
	 * @var integer
	 */
	private static $author_id;

	/**
	 * Another editor's user id (email other@example.com), used as a non-author actor.
	 *
	 * @var integer
	 */
	private static $other_id;

	/**
	 * Workflow state term id.
	 *
	 * @var integer
	 */
	private static $ws_term_id;

	/**
	 * Captured outgoing mail for the current test.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $mails = array();

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
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();

		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		self::$author_id = $factory->user->create(
			array(
				'role'       => 'editor',
				'user_email' => 'author@example.com',
			)
		);
		self::$other_id  = $factory->user->create(
			array(
				'role'       => 'editor',
				'user_email' => 'other@example.com',
			)
		);

		$wpdr->add_caps();
		$wpdr->register_ct();
		$wpdr->initialize_workflow_states();

		$term             = wp_insert_term( 'Notify Review', 'workflow_state' );
		self::$ws_term_id = is_array( $term ) ? (int) $term['term_id'] : 0;

		wp_cache_flush();
	}

	/**
	 * Captures mail instead of sending it.
	 */
	public function set_up() {
		parent::set_up();
		$this->mails = array();
		add_filter( 'pre_wp_mail', array( $this, 'capture_mail' ), 10, 2 );
	}

	/**
	 * Removes the mail capture and resets notification options.
	 */
	public function tear_down() {
		remove_filter( 'pre_wp_mail', array( $this, 'capture_mail' ), 10 );
		delete_site_option( 'document_notify_enabled' );
		delete_site_option( 'document_notify_recipients' );
		delete_site_option( 'document_notify_on_state_change' );
		delete_site_option( 'document_notify_on_new_revision' );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Short-circuits wp_mail and records the arguments.
	 *
	 * @param null|bool            $short short-circuit value.
	 * @param array<string, mixed> $atts  wp_mail arguments.
	 * @return bool true to signal the mail was handled.
	 */
	public function capture_mail( $short, $atts ): bool {
		$this->mails[] = $atts;
		return true;
	}

	/**
	 * Configures the four notification options and flushes the cache.
	 *
	 * @param string $enabled   master toggle value.
	 * @param string $recipients recipient list.
	 * @param string $on_state  state-change toggle value.
	 * @param string $on_rev    new-revision toggle value.
	 */
	private function configure( string $enabled, string $recipients, string $on_state, string $on_rev ): void {
		update_site_option( 'document_notify_enabled', $enabled );
		update_site_option( 'document_notify_recipients', $recipients );
		update_site_option( 'document_notify_on_state_change', $on_state );
		update_site_option( 'document_notify_on_new_revision', $on_rev );
		wp_cache_flush();
	}

	/**
	 * Creates a bare document authored by the given user.
	 *
	 * @param int $author_id the author user id.
	 * @return int the document id.
	 */
	private function make_doc( int $author_id ): int {
		return (int) wp_insert_post(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_title'   => 'Notify Doc',
				'post_author'  => $author_id,
				'post_content' => '<!-- WPDR 0 -->',
			)
		);
	}

	/**
	 * The flattened list of "to" addresses across captured mail.
	 *
	 * @return string[] the recipient addresses.
	 */
	private function recipients(): array {
		$to = array();
		foreach ( $this->mails as $mail ) {
			$to[] = is_array( $mail['to'] ) ? implode( ',', $mail['to'] ) : (string) $mail['to'];
		}
		return $to;
	}

	/**
	 * When disabled, no mail is sent.
	 */
	public function test_disabled_sends_nothing() {
		$this->configure( '', 'team@example.com', '1', '1' );
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$other_id );

		do_action( 'document_change_workflow_state', $doc_id, self::$ws_term_id, '' );

		self::assertCount( 0, $this->mails, 'no mail when disabled' );
	}

	/**
	 * A state change notifies both the site list and the document author.
	 */
	public function test_state_change_notifies_list_and_author() {
		$this->configure( '1', 'team@example.com', '1', '' );
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$other_id );

		do_action( 'document_change_workflow_state', $doc_id, self::$ws_term_id, '' );

		$to = $this->recipients();
		self::assertCount( 2, $to, 'two recipients' );
		self::assertContains( 'team@example.com', $to, 'site list notified' );
		self::assertContains( 'author@example.com', $to, 'author notified' );
	}

	/**
	 * The actor is never notified of their own change (author acting on own doc).
	 */
	public function test_actor_author_excluded() {
		$this->configure( '1', 'team@example.com', '1', '' );
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$author_id );

		do_action( 'document_change_workflow_state', $doc_id, self::$ws_term_id, '' );

		$to = $this->recipients();
		self::assertCount( 1, $to, 'author-as-actor excluded' );
		self::assertContains( 'team@example.com', $to, 'site list still notified' );
		self::assertNotContains( 'author@example.com', $to, 'author excluded' );
	}

	/**
	 * With the state-change toggle off, a state change sends nothing.
	 */
	public function test_state_change_toggle_off() {
		$this->configure( '1', 'team@example.com', '', '1' );
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$other_id );

		do_action( 'document_change_workflow_state', $doc_id, self::$ws_term_id, '' );

		self::assertCount( 0, $this->mails, 'no mail when state-change toggle off' );
	}

	/**
	 * A new revision notifies when its toggle is on, and not when off (the default).
	 */
	public function test_new_revision_toggle() {
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$other_id );

		// default: new-revision toggle off.
		$this->configure( '1', 'team@example.com', '1', '' );
		do_action( 'document_saved', $doc_id, 0 );
		self::assertCount( 0, $this->mails, 'no mail when new-revision toggle off' );

		// toggle on.
		$this->configure( '1', 'team@example.com', '1', '1' );
		do_action( 'document_saved', $doc_id, 0 );
		self::assertContains( 'team@example.com', $this->recipients(), 'notified on new revision' );
	}

	/**
	 * The recipients filter can fully override the list.
	 */
	public function test_recipients_filter_overrides() {
		$this->configure( '1', 'team@example.com', '1', '' );
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$other_id );

		$filter = static function () {
			return array( 'override@example.com' );
		};
		add_filter( 'document_notification_recipients', $filter );
		do_action( 'document_change_workflow_state', $doc_id, self::$ws_term_id, '' );
		remove_filter( 'document_notification_recipients', $filter );

		$to = $this->recipients();
		self::assertCount( 1, $to, 'only the filtered recipient' );
		self::assertContains( 'override@example.com', $to, 'filter honored' );
	}

	/**
	 * Duplicate and invalid addresses are dropped.
	 */
	public function test_dedupe_and_invalid_dropped() {
		$this->configure( '1', 'team@example.com, team@example.com, not-an-email', '1', '' );
		$doc_id = $this->make_doc( self::$author_id );
		wp_set_current_user( self::$other_id );

		do_action( 'document_change_workflow_state', $doc_id, self::$ws_term_id, '' );

		$to = $this->recipients();
		self::assertCount( 2, $to, 'duplicates collapsed and invalid dropped (team + author)' );
		self::assertContains( 'team@example.com', $to, 'valid address kept' );
		self::assertNotContains( 'not-an-email', $to, 'invalid address dropped' );
	}
}
