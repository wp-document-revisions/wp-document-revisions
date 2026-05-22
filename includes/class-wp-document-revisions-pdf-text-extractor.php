<?php
/**
 * PDF text extractor backed by smalot/pdfparser.
 *
 * Handles `application/pdf`. Encrypted, malformed, or otherwise unreadable
 * PDFs return an empty string rather than throwing, so a single bad file
 * cannot interrupt a check-in. Scanned PDFs (no embedded text layer) also
 * return empty — see the OCR follow-up in issue #514 for that path.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDF text extractor.
 */
class WP_Document_Revisions_PDF_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * Whether this extractor can handle the given MIME type.
	 *
	 * @param string $mime_type the MIME type to check.
	 * @return bool true for application/pdf, false otherwise.
	 */
	public function supports( string $mime_type ): bool {
		return 'application/pdf' === $mime_type;
	}

	/**
	 * Extract plain text from a PDF file.
	 *
	 * @param string $file_path absolute path to the PDF on disk.
	 * @param string $mime_type the MIME type (unused; supports() gates this).
	 * @return string extracted text, or empty string if parsing fails or the
	 *                PDF contains no embedded text layer.
	 */
	public function extract( string $file_path, string $mime_type ): string {
		unset( $mime_type );

		try {
			$parser = new \Smalot\PdfParser\Parser();
			$pdf    = $parser->parseFile( $file_path );

			return (string) $pdf->getText();
		} catch ( \Throwable $e ) {
			// Encrypted, malformed, or unsupported PDF (or smalot itself
			// hit an internal error). Returning empty matches the interface
			// contract for "nothing could be extracted" and avoids logging
			// noise on sites that upload many such files. Per-file failure
			// tracking lands with the caching/opt-out phases.
			return '';
		}
	}
}
