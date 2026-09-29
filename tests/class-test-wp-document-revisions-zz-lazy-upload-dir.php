<?php
/**
 * Tests resolving the document upload directory on use (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Lazy document upload directory tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Lazy_Upload_Dir extends WP_UnitTestCase {

	/**
	 * Replaces the uploads basedir, as S3-Uploads and similar plugins do.
	 *
	 * @param array<string, mixed> $dir wp_upload_dir() result.
	 * @return array<string, mixed>
	 */
	public function fake_offload( $dir ) {
		$dir['basedir'] = '/tmp/offloaded-uploads';
		$dir['path']    = $dir['basedir'] . $dir['subdir'];
		return $dir;
	}

	/**
	 * Start each test with the document directory unresolved and no option set.
	 */
	public function set_up() {
		parent::set_up();
		global $wpdr;
		delete_site_option( 'document_upload_directory' );
		$wpdr->reset_document_upload_dir();
	}

	/**
	 * Restore filters and cached state.
	 */
	public function tear_down() {
		global $wpdr;
		remove_filter( 'upload_dir', array( $this, 'fake_offload' ) );
		remove_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		WP_Document_Revisions::$doc_image = true;
		unset( $_GET['post_type'] );
		$wpdr->reset_document_upload_dir();
		parent::tear_down();
	}

	/**
	 * An upload_dir filter added after the plugin loaded is respected.
	 */
	public function test_late_upload_dir_filter_is_used() {
		global $wpdr;

		add_filter( 'upload_dir', array( $this, 'fake_offload' ) );

		self::assertSame( '/tmp/offloaded-uploads', $wpdr->document_upload_dir() );
	}

	/**
	 * The document_upload_directory filter overrides the resolved directory.
	 */
	public function test_document_upload_directory_filter() {
		global $wpdr;

		$filter = function () {
			return 's3://bucket/documents';
		};
		add_filter( 'document_upload_directory', $filter );
		$dir = $wpdr->document_upload_dir();
		remove_filter( 'document_upload_directory', $filter );

		self::assertSame( 's3://bucket/documents', $dir );
	}

	/**
	 * While a document is being uploaded, resolving the default directory doesn't recurse
	 * into the document upload_dir filter, and uploads go to the document directory.
	 */
	public function test_document_upload_does_not_recurse() {
		global $wpdr;

		add_filter( 'upload_dir', array( $this, 'fake_offload' ) );
		add_filter( 'upload_dir', array( $wpdr, 'document_upload_dir_filter' ) );
		$_GET['post_type']                = 'document';
		WP_Document_Revisions::$doc_image = false;

		$dir = wp_upload_dir( null, false );

		self::assertSame( '/tmp/offloaded-uploads', $dir['basedir'] );
		self::assertStringNotContainsString( '//', $dir['path'] );
	}

	/**
	 * Switching sites resolves the directory again for the new site.
	 */
	public function test_switch_blog_resets_directory() {
		global $wpdr;

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		update_site_option( 'document_upload_directory', '/srv/documents/sites/%site_id%' );
		$site = self::factory()->blog->create();

		$main = $wpdr->document_upload_dir();
		switch_to_blog( $site );
		$other = $wpdr->document_upload_dir();
		restore_current_blog();

		self::assertSame( '/srv/documents', $main );
		self::assertSame( '/srv/documents/sites/' . $site, $other );
		self::assertSame( '/srv/documents', $wpdr->document_upload_dir(), 'Restored after switching back.' );
	}
}
