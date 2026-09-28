<?php
/**
 * Tests the document upload directory paths (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document upload directory tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Upload_Dir extends WP_UnitTestCase {

	/**
	 * Restore the document image flag that document_upload_dir_set() clears.
	 */
	public function tear_down() {
		WP_Document_Revisions::$doc_image = true;
		parent::tear_down();
	}

	/**
	 * Data provider: core subdir values and the expected document subdir.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function subdir_provider() {
		return array(
			'year/month folders' => array( '/2026/09', '/2026/09' ),
			'no folders'         => array( '', '' ),
			'missing slash'      => array( '2026/09', '/2026/09' ),
		);
	}

	/**
	 * The path and URL never contain a doubled slash.
	 *
	 * @dataProvider subdir_provider
	 * @param string $subdir   subdir passed by core.
	 * @param string $expected expected subdir.
	 */
	public function test_document_upload_dir_set_joins_subdir( $subdir, $expected ) {
		global $wpdr;

		$dir           = WP_Document_Revisions::$wp_default_dir;
		$dir['subdir'] = $subdir;
		$new_dir       = $wpdr->document_upload_dir_set( $dir );
		$doc_dir       = untrailingslashit( $wpdr->document_upload_dir() );

		self::assertSame( $doc_dir . $expected, $new_dir['path'] );
		self::assertSame( $expected, $new_dir['subdir'] );
		self::assertStringNotContainsString( '//', str_replace( '://', '', $new_dir['path'] ) );
		self::assertStringNotContainsString( '//', str_replace( '://', '', $new_dir['url'] ) );
	}
}
