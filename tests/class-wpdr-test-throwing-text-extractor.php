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
	 * Extract — delegates to fail() which always throws. The trailing return
	 * is unreachable in practice but lets the interface's string return type
	 * stay intact without tripping the "no return statement" sniff.
	 *
	 * @param string $file_path File path (ignored).
	 * @param string $mime_type MIME type (ignored).
	 * @return string
	 * @throws WP_Document_Revisions_Text_Extraction_Exception Always thrown by fail().
	 */
	public function extract( string $file_path, string $mime_type ): string {
		unset( $file_path, $mime_type );
		$this->fail_with_extraction_exception();
		return '';
	}

	/**
	 * Throw a hard-failure exception. Extracted into a helper so the static
	 * analyser does not realise extract() never returns.
	 *
	 * @return void
	 * @throws WP_Document_Revisions_Text_Extraction_Exception Always.
	 */
	private function fail_with_extraction_exception(): void {
		throw new WP_Document_Revisions_Text_Extraction_Exception( 'boom' );
	}
}
