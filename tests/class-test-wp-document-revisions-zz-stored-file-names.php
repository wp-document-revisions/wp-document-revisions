<?php
/**
 * Tests that stored document file names are random, 32-hex-character names.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Stored file name tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Stored_File_Names extends WP_UnitTestCase {

	/**
	 * Earlier test classes start document uploads that never generate metadata, so reset the
	 * per-request flag.
	 */
	public function set_up() {
		parent::set_up();
		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
	}

	/**
	 * Clean up request state.
	 */
	public function tear_down() {
		global $wpdr;
		unset( $_POST['upload_source'], $_POST['post_id'] );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		WP_Document_Revisions::$doc_image = true;

		$flag = new ReflectionProperty( 'WP_Document_Revisions', 'document_upload' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
		parent::tear_down();
	}

	/**
	 * Mark the request as a document upload.
	 *
	 * @return void
	 */
	private function start_document_upload() {
		$doc = self::factory()->post->create( array( 'post_type' => 'document' ) );
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$_POST['upload_source'] = 'wp-document-revisions';
		$_POST['post_id']       = $doc;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * The helper returns 32 lowercase hex characters, different on every call.
	 */
	public function test_random_file_name_format_and_uniqueness() {
		$names = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$name = WP_Document_Revisions::random_file_name();
			self::assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $name, 'Name not 32 lowercase hex characters' );
			$names[] = $name;
		}
		self::assertCount( 20, array_unique( $names ), 'Generated names repeat' );
	}

	/**
	 * Uploading the same file twice in one request gives two different hashed names.
	 */
	public function test_same_file_uploaded_twice_gets_different_names() {
		global $wpdr;
		$this->start_document_upload();

		$first  = $wpdr->filename_rewrite( array( 'name' => 'Quarterly Report.pdf' ) );
		$second = $wpdr->filename_rewrite( array( 'name' => 'Quarterly Report.pdf' ) );

		self::assertMatchesRegularExpression( '/^[a-f0-9]{32}\.pdf$/', $first['name'] );
		self::assertMatchesRegularExpression( '/^[a-f0-9]{32}\.pdf$/', $second['name'] );
		self::assertNotSame( $first['name'], $second['name'], 'Same file got the same stored name' );
		self::assertStringNotContainsString( 'Quarterly', $first['name'] );
	}

	/**
	 * Names without an extension are just the hash, which the hash-detection code recognises.
	 */
	public function test_name_is_recognised_as_hashed() {
		global $wpdr;
		$this->start_document_upload();

		$file = $wpdr->filename_rewrite( array( 'name' => 'no-extension' ) );

		self::assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $file['name'] );
		// Same patterns Validate Structure uses to detect hashed names.
		self::assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', pathinfo( $file['name'], PATHINFO_FILENAME ) );
		self::assertMatchesRegularExpression( '/^[0-9a-f]{32}\./', wp_basename( $file['name'] . '.txt' ) );
	}

	/**
	 * The document_internal_filename filter still receives the original name and can override the result.
	 */
	public function test_internal_filename_filter_still_applies() {
		global $wpdr;
		$this->start_document_upload();

		$seen   = array();
		$filter = function ( $file, $orig ) use ( &$seen ) {
			$seen[]       = array( $file['name'], $orig );
			$file['name'] = 'custom-name.pdf';
			return $file;
		};
		add_filter( 'document_internal_filename', $filter, 10, 2 );
		$file = $wpdr->filename_rewrite( array( 'name' => 'report.pdf' ) );
		remove_filter( 'document_internal_filename', $filter, 10 );

		self::assertSame( 'custom-name.pdf', $file['name'], 'Filter result not used' );
		self::assertCount( 1, $seen );
		self::assertMatchesRegularExpression( '/^[a-f0-9]{32}\.pdf$/', $seen[0][0], 'Filter not given the hashed name' );
		self::assertSame( 'report.pdf', $seen[0][1], 'Filter not given the original name' );
	}
}
