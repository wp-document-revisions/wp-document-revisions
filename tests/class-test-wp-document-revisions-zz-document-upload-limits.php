<?php
/**
 * Tests document-specific upload types and size limits (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document upload limit tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Document_Upload_Limits extends WP_UnitTestCase {

	/**
	 * Start with no upload in progress.
	 */
	public function set_up() {
		parent::set_up();
		$this->set_uploading( false );
	}

	/**
	 * Clean up.
	 */
	public function tear_down() {
		global $wpdr;
		$this->set_uploading( false );
		remove_all_filters( 'document_allowed_mimes' );
		remove_all_filters( 'document_upload_size_limit' );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		unset( $_POST['upload_source'], $_POST['post_id'] );
		WP_Document_Revisions::$doc_image = true;
		parent::tear_down();
	}

	/**
	 * Set the per-request document upload flag.
	 *
	 * @param bool $uploading whether a document upload is in progress.
	 */
	private function set_uploading( $uploading ) {
		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, $uploading );
	}

	/**
	 * Allow .dwg during document uploads.
	 */
	private function allow_dwg() {
		add_filter(
			'document_allowed_mimes',
			function ( $mimes ) {
				$mimes['dwg'] = 'image/vnd.dwg';
				return $mimes;
			}
		);
	}

	/**
	 * Extra types apply to document uploads only.
	 */
	public function test_allowed_mimes_only_for_documents() {
		$this->allow_dwg();

		self::assertArrayNotHasKey( 'dwg', get_allowed_mime_types(), 'Not for ordinary uploads.' );

		$this->set_uploading( true );
		self::assertArrayHasKey( 'dwg', get_allowed_mime_types() );
	}

	/**
	 * The filter gets every known type as its second argument.
	 */
	public function test_allowed_mimes_receives_all_types() {
		$seen = null;
		add_filter(
			'document_allowed_mimes',
			function ( $mimes, $all ) use ( &$seen ) {
				$seen = $all;
				return $mimes;
			},
			10,
			2
		);
		$this->set_uploading( true );
		get_allowed_mime_types();

		self::assertSame( wp_get_mime_types(), $seen );
	}

	/**
	 * The size limit applies during document uploads, including multisite's per-file option.
	 */
	public function test_upload_size_limit() {
		add_filter(
			'document_upload_size_limit',
			function () {
				return 50 * MB_IN_BYTES;
			}
		);

		self::assertSame( 1500, (int) get_site_option( 'fileupload_maxk', 1500 ), 'Unchanged outside document uploads.' );

		$this->set_uploading( true );
		self::assertSame( 50 * KB_IN_BYTES, (int) get_site_option( 'fileupload_maxk', 1500 ) );
		self::assertSame( 50 * MB_IN_BYTES, (int) apply_filters( 'upload_size_limit', 2 * MB_IN_BYTES, 0, 0 ) );
	}

	/**
	 * Uploads start being document uploads at filename_rewrite(), so the checks after it see the flag.
	 */
	public function test_flag_set_by_document_upload() {
		global $wpdr;

		$this->allow_dwg();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$_POST['upload_source'] = 'wp-document-revisions';
		$_POST['post_id']       = self::factory()->post->create( array( 'post_type' => 'document' ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$wpdr->filename_rewrite( array( 'name' => 'plan.dwg' ) );

		self::assertArrayHasKey( 'dwg', get_allowed_mime_types() );
	}
}
