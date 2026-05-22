<?php
/**
 * Test double: text extractor that counts how many times extract() runs.
 *
 * Used by the cache tests to verify a second wpdr_extract_text() call
 * against the same revision and the same file content does NOT re-invoke
 * the extractor.
 *
 * @package WP_Document_Revisions
 */

/**
 * Counting fake extractor for cache tests.
 */
class WPDR_Test_Counting_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * Number of times extract() has been called on this instance.
	 *
	 * @var int
	 */
	public $calls = 0;

	/**
	 * MIME type this fake claims to support.
	 *
	 * @var string
	 */
	private $supported_mime;

	/**
	 * Fixed string to return from extract(), or null to read the file.
	 *
	 * @var string|null
	 */
	private $fixed_return;

	/**
	 * Constructor.
	 *
	 * @param string      $supported_mime MIME type this fake claims.
	 * @param string|null $fixed_return   When set, extract() returns this
	 *                                    instead of reading the file. Lets
	 *                                    callers exercise the "extractor
	 *                                    returned ''" path without depending
	 *                                    on file content.
	 */
	public function __construct( string $supported_mime = 'text/plain', ?string $fixed_return = null ) {
		$this->supported_mime = $supported_mime;
		$this->fixed_return   = $fixed_return;
	}

	/**
	 * Supports check.
	 *
	 * @param string $mime_type MIME type to check.
	 * @return bool
	 */
	public function supports( string $mime_type ): bool {
		return $mime_type === $this->supported_mime;
	}

	/**
	 * Extract — counts the call and returns the file's current contents
	 * so a content change is observable in the cached value.
	 *
	 * @param string $file_path file path.
	 * @param string $mime_type MIME type.
	 * @return string
	 */
	public function extract( string $file_path, string $mime_type ): string {
		unset( $mime_type );
		++$this->calls;
		if ( null !== $this->fixed_return ) {
			return $this->fixed_return;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $file_path );
		return false === $contents ? '' : (string) $contents;
	}
}
