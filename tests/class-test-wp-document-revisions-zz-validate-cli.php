<?php
/**
 * Tests the `wp document-revisions validate` command's logic (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Validate CLI command tests. The WP_CLI output wrapper isn't exercised; run() holds the logic.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Validate_Cli extends Test_Common_WPDR {

	/**
	 * Load the command class (only loaded under WP-CLI).
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once dirname( __DIR__ ) . '/includes/class-wp-document-revisions-validate-cli-command.php';
	}

	/**
	 * Create a document whose post_content lost its attachment marker (code 4, fixable).
	 *
	 * @return int[] document and attachment IDs.
	 */
	private function broken_document() {
		global $wpdr, $wpdb;

		$doc = self::factory()->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		self::add_document_attachment( self::factory(), $doc, self::$test_file );
		$attach = $wpdr->get_document( $doc )->ID;

		$wpdb->update( $wpdb->posts, array( 'post_content' => 'Description only' ), array( 'ID' => $doc ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( $doc );

		return array( $doc, $attach );
	}

	/**
	 * Reports the problem without a user (as under WP-CLI), and --fix repairs it.
	 */
	public function test_run_reports_and_fixes() {
		global $wpdr;

		list( $doc, $attach ) = $this->broken_document();
		wp_set_current_user( 0 );

		$rows = wp_list_filter( WP_Document_Revisions_Validate_CLI_Command::run(), array( 'id' => $doc ) );
		self::assertNotEmpty( $rows, 'Problem reported without a logged-in user.' );
		$row = reset( $rows );
		self::assertSame( 'yes', $row['fixable'] );
		self::assertArrayNotHasKey( 'result', $row );

		$fixed = wp_list_filter( WP_Document_Revisions_Validate_CLI_Command::run( true ), array( 'id' => $doc ) );
		self::assertSame( 'fixed', reset( $fixed )['result'] );
		self::assertSame( $attach, $wpdr->extract_document_id( get_post_field( 'post_content', $doc, 'db' ) ) );

		self::assertSame( array(), array_values( wp_list_filter( WP_Document_Revisions_Validate_CLI_Command::run(), array( 'id' => $doc ) ) ), 'Clean after fixing.' );
	}

	/**
	 * Unfixable codes are rejected by fix_problem().
	 */
	public function test_fix_problem_rejects_unfixable_code() {
		$result = WP_Document_Revisions_Validate_Structure::fix_problem( 1, 1, 0 );
		self::assertWPError( $result );
		self::assertSame( 'not_fixable', $result->get_error_code() );
	}
}
