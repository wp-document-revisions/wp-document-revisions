<?php
/**
 * Test double: a fake text extractor that always throws a hard-failure exception.
 *
 * @package WP_Document_Revisions
 */

/**
 * Fake extractor that claims to support every MIME type and always throws on extract().
 */
class WPDR_Test_Throwing_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * Supports any MIME type.
	 *
	 * @param string $mime_type MIME type (ignored).
	 * @return bool
	 */
	public function supports( string $mime_type ): bool {
		unset( $mime_type );
		return true;
	}

	/**
	 * Always throws — the return type is declared on the interface, so the
	 * signature has to keep it even though this body never returns.
	 *
	 * @param string $file_path File path (ignored).
	 * @param string $mime_type MIME type (ignored).
	 * @return string
	 * @throws WP_Document_Revisions_Text_Extraction_Exception Always thrown by this fake.
	 */
	public function extract( string $file_path, string $mime_type ): string { // phpcs:ignore Squiz.Commenting.FunctionComment.InvalidNoReturn
		unset( $file_path, $mime_type );
		throw new WP_Document_Revisions_Text_Extraction_Exception( 'boom' );
	}
}
