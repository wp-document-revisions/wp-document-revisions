<?php
/**
 * Tests the admin engagement notices: first-run empty state and the review prompt.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Empty-state and review-prompt notice tests.
 */
class Test_WP_Document_Revisions_Review_Prompt extends Test_Common_WPDR {

	/**
	 * Editor user id.
	 *
	 * @var integer
	 */
	private static $editor_user_id;

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

		// make sure that we have the admin set up.
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		self::$editor_user_id = $factory->user->create(
			array(
				'user_nicename' => 'Editor',
				'role'          => 'editor',
			)
		);

		// init user roles and workflow states.
		$wpdr->add_caps();
		$wpdr->register_ct();
		$wpdr->initialize_workflow_states();

		wp_cache_flush();
	}

	/**
	 * Deletes every document so each test controls the exact count within its rolled-back transaction.
	 */
	private function delete_all_documents(): void {
		$docs = get_posts(
			array(
				'post_type'   => 'document',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $docs as $doc_id ) {
			wp_delete_post( $doc_id, true );
		}
		wp_cache_flush();
	}

	/**
	 * Creates the given number of published documents owned by the editor.
	 *
	 * @param int $count Number of documents to create.
	 */
	private function create_documents( int $count ): void {
		for ( $i = 0; $i < $count; $i++ ) {
			self::factory()->post->create(
				array(
					'post_type'   => 'document',
					'post_status' => 'publish',
					'post_author' => self::$editor_user_id,
					'post_title'  => 'Doc ' . $i,
				)
			);
		}
		wp_cache_flush();
	}

	/**
	 * Puts the request on the documents list screen as the editor.
	 */
	private function on_documents_list_screen(): void {
		wp_set_current_user( self::$editor_user_id );
		set_current_screen( 'edit-document' );
		get_current_screen()->id = 'edit-document';
	}

	/**
	 * Captures the output of an admin-notice method.
	 *
	 * @param string $method Method name on the admin instance.
	 * @return string
	 */
	private function capture( string $method ): string {
		global $wpdr;
		ob_start();
		$wpdr->admin->$method();
		return (string) ob_get_clean();
	}

	/**
	 * The empty-state notice appears when no documents exist yet.
	 */
	public function test_empty_state_shows_when_no_documents(): void {
		$this->on_documents_list_screen();
		$this->delete_all_documents();

		$output = $this->capture( 'empty_state_notice' );

		self::assertStringContainsString( 'Add your first document', $output, 'empty-state CTA missing' );
	}

	/**
	 * The empty-state notice is suppressed once a document exists.
	 */
	public function test_empty_state_hidden_when_documents_exist(): void {
		$this->on_documents_list_screen();
		$this->delete_all_documents();
		$this->create_documents( 1 );

		self::assertEmpty( $this->capture( 'empty_state_notice' ), 'empty-state shown despite a document existing' );
	}

	/**
	 * The empty-state notice only renders on the documents list screen.
	 */
	public function test_empty_state_hidden_off_screen(): void {
		wp_set_current_user( self::$editor_user_id );
		$this->delete_all_documents();
		set_current_screen( 'dashboard' );

		self::assertEmpty( $this->capture( 'empty_state_notice' ), 'empty-state shown on the wrong screen' );
	}

	/**
	 * The review prompt stays hidden below the engagement threshold.
	 */
	public function test_review_prompt_hidden_below_threshold(): void {
		$this->on_documents_list_screen();
		$this->delete_all_documents();
		delete_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );
		$this->create_documents( WP_Document_Revisions_Admin::REVIEW_MIN_DOCS - 1 );

		self::assertEmpty( $this->capture( 'review_prompt' ), 'review prompt shown below threshold' );
	}

	/**
	 * The review prompt appears once the engagement threshold is met.
	 */
	public function test_review_prompt_shows_at_threshold(): void {
		$this->on_documents_list_screen();
		$this->delete_all_documents();
		delete_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );
		$this->create_documents( WP_Document_Revisions_Admin::REVIEW_MIN_DOCS );

		$output = $this->capture( 'review_prompt' );

		self::assertStringContainsString( 'Leave a review', $output, 'review prompt missing at threshold' );
		self::assertStringContainsString( 'wpdr_review_dismiss', $output, 'review prompt missing dismissal link' );
	}

	/**
	 * The review prompt is suppressed after the user has dismissed it.
	 */
	public function test_review_prompt_hidden_after_dismissal(): void {
		$this->on_documents_list_screen();
		$this->delete_all_documents();
		$this->create_documents( WP_Document_Revisions_Admin::REVIEW_MIN_DOCS );
		update_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META, 1 );

		self::assertEmpty( $this->capture( 'review_prompt' ), 'review prompt shown after dismissal' );

		delete_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );
	}

	/**
	 * A valid dismissal records the user meta and redirects to the review page when requested.
	 */
	public function test_handle_review_dismissal_records_and_redirects(): void {
		global $wpdr;
		wp_set_current_user( self::$editor_user_id );
		delete_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );

		$_GET['wpdr_review_dismiss'] = '1';
		$_GET['wpdr_review_go']      = '1';
		$_GET['wpdr_review_nonce']   = wp_create_nonce( 'wpdr_review_dismiss' );

		$location = '';
		add_filter(
			'wp_redirect',
			static function ( $loc ) {
				throw new Exception( esc_html( (string) $loc ) );
			}
		);

		try {
			$wpdr->admin->handle_review_dismissal();
		} catch ( Exception $e ) {
			$location = $e->getMessage();
		}

		remove_all_filters( 'wp_redirect' );
		unset( $_GET['wpdr_review_dismiss'], $_GET['wpdr_review_go'], $_GET['wpdr_review_nonce'] );

		self::assertNotEmpty( get_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META, true ), 'dismissal not recorded' );
		self::assertStringContainsString( 'wordpress.org/support/plugin/wp-document-revisions/reviews', $location, 'did not redirect to the review page' );

		delete_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );
	}

	/**
	 * A dismissal request with an invalid nonce is ignored.
	 */
	public function test_handle_review_dismissal_requires_valid_nonce(): void {
		global $wpdr;
		wp_set_current_user( self::$editor_user_id );
		delete_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );

		$_GET['wpdr_review_dismiss'] = '1';
		$_GET['wpdr_review_nonce']   = 'not-a-valid-nonce';

		$redirected = false;
		add_filter(
			'wp_redirect',
			static function ( $loc ) use ( &$redirected ) {
				$redirected = true;
				throw new Exception( esc_html( (string) $loc ) );
			}
		);

		try {
			$wpdr->admin->handle_review_dismissal();
		} catch ( Exception $e ) {
			// no-op; asserted via $redirected below.
			unset( $e );
		}

		remove_all_filters( 'wp_redirect' );
		unset( $_GET['wpdr_review_dismiss'], $_GET['wpdr_review_nonce'] );

		self::assertFalse( $redirected, 'handler redirected on an invalid nonce' );
		self::assertEmpty( get_user_meta( self::$editor_user_id, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META, true ), 'dismissal recorded on an invalid nonce' );
	}
}
