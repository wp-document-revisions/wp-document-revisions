<?php
/**
 * Tests that Validate Structure flags and fixes document files stored under unhashed names.
 *
 * @package WP_Document_Revisions
 */

/**
 * Validate Structure unhashed-file tests.
 */
class Test_WP_Document_Revisions_Zz_Validate_Unhashed extends Test_Common_WPDR {

	/**
	 * Editor user id.
	 *
	 * @var integer
	 */
	private static $editor;

	/**
	 * Document id.
	 *
	 * @var integer
	 */
	private static $doc;

	/**
	 * Attachment of the earlier version (made unhashed).
	 *
	 * @var integer
	 */
	private static $old_attach;

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
		$wpdr->register_cpt();
		$wpdr->add_caps();

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		$wpdr->add_caps();

		self::$doc = $factory->post->create(
			array(
				'post_title'   => 'Unhashed files',
				'post_status'  => 'private',
				'post_author'  => self::$editor,
				'post_type'    => 'document',
				'post_content' => '',
			)
		);

		// Two versions: the first becomes an earlier version once the second is uploaded.
		self::add_document_attachment( $factory, self::$doc, self::$test_file );
		self::$old_attach = $wpdr->get_document( self::$doc )->ID;
		self::add_document_attachment( $factory, self::$doc, self::$test_file2 );
	}

	/**
	 * Delete the posts.
	 */
	public static function wpTearDownAfterClass() {
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );
		wp_delete_post( self::$doc, true );
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10 );
	}

	/**
	 * Run the Validate Structure page and return its output.
	 *
	 * @return string
	 */
	private function validate_output() {
		wp_cache_flush();
		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		return ob_get_clean();
	}

	/**
	 * An earlier version's file stored under its original name is flagged, and the fix hashes it.
	 */
	public function test_unhashed_earlier_version_flagged_and_fixed() {
		global $wpdr;
		wp_set_current_user( self::$editor );

		$current = $wpdr->get_document( self::$doc )->ID;
		$this->assertNotSame( self::$old_attach, $current, 'Setup: the old attachment should not be current' );

		// Give the earlier version's file an original (unhashed) name, as block editor panel uploads had.
		$file  = get_attached_file( self::$old_attach );
		$fname = get_post_meta( self::$old_attach, '_wp_attached_file', true );
		$nname = trailingslashit( dirname( $fname ) ) . 'quarterly-report.txt';
		$nfile = trailingslashit( dirname( $file ) ) . 'quarterly-report.txt';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		rename( $file, $nfile );
		update_post_meta( self::$old_attach, '_wp_attached_file', $nname, $fname );

		$output = $this->validate_output();
		$this->assertSame( 1, substr_count( $output, 'stored under their original file names' ), 'Unhashed earlier version not flagged' );
		$this->assertSame( 1, substr_count( $output, 'wpdr_valid_fix(' . self::$doc . ',15,' . self::$doc . ')' ), 'Fix button not offered' );

		// The md5 filter switches the check off.
		add_filter( 'document_validate_md5', '__return_false' );
		$this->assertSame( 0, substr_count( $this->validate_output(), 'stored under their original file names' ), 'Check not switched off by filter' );
		remove_filter( 'document_validate_md5', '__return_false' );

		$request = new WP_REST_Request( 'PUT', '/wpdr/v1/correct/' . self::$doc . '/type/15/attach/' . self::$doc );
		$request->set_param( 'id', self::$doc );
		$request->set_param( 'code', 15 );
		$request->set_param( 'parm', self::$doc );
		$response = WP_Document_Revisions_Validate_Structure::correct_document( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertSame( 'Success.', $response->get_data() );

		$new = get_post_meta( self::$old_attach, '_wp_attached_file', true );
		$this->assertMatchesRegularExpression( '|^\d{4}/\d{2}/[a-f0-9]{32}\.txt$|', $new, 'File not renamed to a hash' );
		$this->assertFileExists( get_attached_file( self::$old_attach ), 'Renamed file missing' );
		$this->assertFileDoesNotExist( $nfile, 'Original unhashed file not removed' );

		$this->assertSame( 0, substr_count( $this->validate_output(), 'stored under their original file names' ), 'Still flagged after fix' );
	}

	/**
	 * The fix refuses a mismatched document id.
	 */
	public function test_fix_rejects_inconsistent_parms() {
		wp_set_current_user( self::$editor );
		$request = new WP_REST_Request( 'PUT', '/wpdr/v1/correct/' . self::$doc . '/type/15/attach/1' );
		$request->set_param( 'id', self::$doc );
		$request->set_param( 'code', 15 );
		$request->set_param( 'parm', 1 );
		$response = WP_Document_Revisions_Validate_Structure::correct_document( $request );
		$this->assertWPError( $response );
	}
}
