<?php
/**
 * Test double: a fake text extractor that returns a fixed string for a single MIME type.
 *
 * @package WP_Document_Revisions
 */

/**
 * Fake extractor that returns a configured string for one configured MIME type.
 */
class WPDR_Test_Fake_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * MIME type this fake claims to support.
	 *
	 * @var string
	 */
	private $supported_mime;

	/**
	 * String to return from extract().
	 *
	 * @var string
	 */
	private $return_text;

	/**
	 * Constructor.
	 *
	 * @param string $supported_mime MIME type this fake claims.
	 * @param string $return_text    Text to return from extract().
	 */
	public function __construct( string $supported_mime, string $return_text = 'fake text' ) {
		$this->supported_mime = $supported_mime;
		$this->return_text    = $return_text;
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
	 * Extract — returns the configured fixed string.
	 *
	 * @param string $file_path File path (ignored by this fake).
	 * @param string $mime_type MIME type (ignored by this fake).
	 * @return string
	 */
	public function extract( string $file_path, string $mime_type ): string {
		unset( $file_path, $mime_type );
		return $this->return_text;
	}
}
