<?php
/**
 * Tests the year/month folder of new document files (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Upload date folder tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Upload_Date_Folder extends WP_UnitTestCase {

	/**
	 * What core passes for an upload to a document dated May 2024.
	 *
	 * @var array<string, mixed>
	 */
	private static $backdated = array(
		'path'    => '/tmp/uploads/2024/05',
		'url'     => 'http://example.org/wp-content/uploads/2024/05',
		'subdir'  => '/2024/05',
		'basedir' => '/tmp/uploads',
		'baseurl' => 'http://example.org/wp-content/uploads',
		'error'   => false,
	);

	/**
	 * Editor user id.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Document dated May 2024.
	 *
	 * @var int
	 */
	private static $document;

	/**
	 * Create an editor and a backdated document to upload to.
	 *
	 * Since #782 a document upload must target a document the user can edit.
	 *
	 * @param WP_UnitTest_Factory $factory factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$editor   = $factory->user->create( array( 'role' => 'editor' ) );
		self::$document = $factory->post->create(
			array(
				'post_type'   => 'document',
				'post_author' => self::$editor,
				'post_date'   => '2024-05-15 12:00:00',
			)
		);
	}

	/**
	 * Use year/month folders.
	 */
	public function set_up() {
		parent::set_up();
		update_option( 'uploads_use_yearmonth_folders', 1 );
	}

	/**
	 * Clear the upload state.
	 */
	public function tear_down() {
		global $wpdr;
		$wpdr->end_document_upload( array(), 0 );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		WP_Document_Revisions::$doc_image = true;
		unset( $_POST['upload_source'], $_POST['post_id'] );
		parent::tear_down();
	}

	/**
	 * Start a document upload the way async-upload.php does.
	 */
	private function start_document_upload() {
		global $wpdr;
		wp_set_current_user( self::$editor );
		$_POST['upload_source'] = 'wp-document-revisions';
		$_POST['post_id']       = self::$document;
		$wpdr->filename_rewrite( array( 'name' => 'report.pdf' ) );
		$this->assertTrue( $wpdr->is_document_upload() );
	}

	/**
	 * A new version of a backdated document goes in the document's month folder, so the
	 * file folder matches the permalink's /yyyy/mm (#777 reverted).
	 */
	public function test_document_upload_keeps_document_month() {
		global $wpdr;
		$this->start_document_upload();

		$dir = $wpdr->document_upload_dir_set( self::$backdated );

		$this->assertSame( '/2024/05', $dir['subdir'] );
		$this->assertStringEndsWith( '/2024/05', $dir['path'] );
		$this->assertStringEndsWith( '/2024/05', $dir['url'] );
	}

	/**
	 * Outside a document upload, the folder is left alone.
	 */
	public function test_other_calls_keep_subdir() {
		global $wpdr;
		$dir = $wpdr->document_upload_dir_set( self::$backdated );
		$this->assertSame( '/2024/05', $dir['subdir'] );
	}

	/**
	 * Without year/month folders there is no date to change.
	 */
	public function test_no_yearmonth_folders() {
		global $wpdr;
		update_option( 'uploads_use_yearmonth_folders', 0 );
		$this->start_document_upload();

		$flat           = self::$backdated;
		$flat['subdir'] = '';
		$dir            = $wpdr->document_upload_dir_set( $flat );
		$this->assertSame( '', $dir['subdir'] );
	}
}
