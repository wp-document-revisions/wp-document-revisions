<?php
/**
 * Test double: a fake text extractor that always throws a hard-failure exception.
 *
 * @package WP_Document_Revisions
 */

/**
 * Fake extractor that always throws on extract().
 *
 * Default-constructed it claims every MIME type (the original Phase 1 usage);
 * with a MIME passed in it restricts itself to that type so Phase 7 scheduler
 * tests can register it alongside the built-ins without intercepting their
 * dispatches.
 */
class WPDR_Test_Throwing_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * Number of times extract() has been called on this instance.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * MIME type this fake claims to support, or null to support everything.
	 *
	 * @var string|null
	 */
	private $supported_mime;

	/**
	 * Constructor.
	 *
	 * @param string|null $supported_mime MIME type this fake claims, or null
	 *                                    to claim every MIME (default).
	 */
	public function __construct( ?string $supported_mime = null ) {
		$this->supported_mime = $supported_mime;
	}

	/**
	 * Supports check.
	 *
	 * @param string $mime_type MIME type to check.
	 * @return bool
	 */
	public function supports( string $mime_type ): bool {
		return null === $this->supported_mime || $mime_type === $this->supported_mime;
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
		++$this->calls;
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
