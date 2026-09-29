<?php
/**
 * Tests which MIME types are compressed on download by default (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Compressible MIME type tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Compressible_Mimetypes extends WP_UnitTestCase {

	/**
	 * Defaults: text and structured text types, not already-compressed formats.
	 */
	public function test_defaults() {
		global $wpdr;

		foreach ( array( 'text/plain', 'text/csv', 'application/json', 'application/xml', 'image/svg+xml', 'text/html; charset=UTF-8' ) as $type ) {
			self::assertTrue( $wpdr->is_compressible_mimetype( $type ), $type );
		}
		foreach ( array( 'application/pdf', 'application/zip', 'image/png', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '', null ) as $type ) {
			self::assertFalse( $wpdr->is_compressible_mimetype( $type ), (string) $type );
		}
	}

	/**
	 * The filter can add exact types and prefixes, or remove defaults.
	 */
	public function test_filter() {
		global $wpdr;

		$filter = function () {
			return array( 'application/pdf', 'font/' );
		};
		add_filter( 'document_compressible_mimetypes', $filter );
		$pdf  = $wpdr->is_compressible_mimetype( 'application/pdf' );
		$font = $wpdr->is_compressible_mimetype( 'font/ttf' );
		$text = $wpdr->is_compressible_mimetype( 'text/plain' );
		remove_filter( 'document_compressible_mimetypes', $filter );

		self::assertTrue( $pdf );
		self::assertTrue( $font );
		self::assertFalse( $text, 'Removed from the list.' );
	}
}
