<?php
/**
 * DOCX (and ODT) text extractor backed by phpoffice/phpword.
 *
 * Handles `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
 * (DOCX) and, as a bonus since PHPWord reads it too,
 * `application/vnd.oasis.opendocument.text` (ODT). Legacy `application/msword`
 * (DOC) is intentionally NOT supported here — it belongs to a separate
 * shell-out-to-LibreOffice extractor (phase 5 of issue #514).
 *
 * Encrypted, malformed, or otherwise unreadable files return an empty
 * string rather than throwing, so a single bad file cannot interrupt a
 * check-in.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DOCX/ODT text extractor.
 */
class WP_Document_Revisions_DOCX_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * MIME types this extractor accepts.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_MIME_TYPES = array(
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.oasis.opendocument.text',
	);

	/**
	 * Whether this extractor can handle the given MIME type.
	 *
	 * @param string $mime_type the MIME type to check.
	 * @return bool true for DOCX or ODT, false otherwise.
	 */
	public function supports( string $mime_type ): bool {
		return in_array( $mime_type, self::SUPPORTED_MIME_TYPES, true );
	}

	/**
	 * Extract plain text from a DOCX or ODT file.
	 *
	 * @param string $file_path absolute path to the file on disk.
	 * @param string $mime_type the MIME type, used to pick the PHPWord reader.
	 * @return string extracted text, or empty string on any parse failure.
	 */
	public function extract( string $file_path, string $mime_type ): string {
		try {
			$reader  = $this->reader_for( $mime_type );
			$phpword = \PhpOffice\PhpWord\IOFactory::load( $file_path, $reader );

			return $this->extract_from_phpword( $phpword );
		} catch ( \Throwable $e ) {
			// Malformed, encrypted, or otherwise unreadable. The dispatcher
			// receives empty rather than an exception so one bad file does
			// not break the caller. Per-file failure tracking lands with
			// the caching / opt-out phases.
			return '';
		}
	}

	/**
	 * Map a MIME type to the PHPWord reader name.
	 *
	 * @param string $mime_type the file's MIME type.
	 * @return string PHPWord reader identifier.
	 */
	private function reader_for( string $mime_type ): string {
		if ( 'application/vnd.oasis.opendocument.text' === $mime_type ) {
			return 'ODText';
		}
		return 'Word2007';
	}

	/**
	 * Walk every section in a parsed PhpWord document and join the text.
	 *
	 * @param \PhpOffice\PhpWord\PhpWord $phpword parsed document.
	 * @return string concatenated text from sections, headers, and footers.
	 */
	private function extract_from_phpword( \PhpOffice\PhpWord\PhpWord $phpword ): string {
		$parts = array();
		foreach ( $phpword->getSections() as $section ) {
			$parts[] = $this->extract_from_section( $section );
		}
		return $this->join_parts( $parts );
	}

	/**
	 * Extract text from a single section, including its headers and footers.
	 *
	 * @param \PhpOffice\PhpWord\Element\Section $section section to walk.
	 * @return string concatenated text.
	 */
	private function extract_from_section( \PhpOffice\PhpWord\Element\Section $section ): string {
		$parts = array();

		foreach ( $section->getHeaders() as $header ) {
			$parts[] = $this->extract_from_container( $header );
		}

		foreach ( $section->getElements() as $element ) {
			$parts[] = $this->extract_from_element( $element );
		}

		foreach ( $section->getFooters() as $footer ) {
			$parts[] = $this->extract_from_container( $footer );
		}

		return $this->join_parts( $parts );
	}

	/**
	 * Walk a container (Header, Footer, Cell, or any element exposing
	 * getElements()) and concatenate its child text.
	 *
	 * @param object $container element whose children should be walked.
	 * @return string concatenated text.
	 */
	private function extract_from_container( $container ): string {
		if ( ! method_exists( $container, 'getElements' ) ) {
			return '';
		}

		$parts = array();
		foreach ( $container->getElements() as $element ) {
			$parts[] = $this->extract_from_element( $element );
		}
		return $this->join_parts( $parts );
	}

	/**
	 * Dispatch on element type. Text and TextRun produce strings; tables
	 * walk their rows and cells; list items pull text from their inner text
	 * object; unknown containers fall back to a generic getElements() walk.
	 *
	 * @param object $element PHPWord element.
	 * @return string text contributed by this element.
	 */
	private function extract_from_element( $element ): string {
		if ( $element instanceof \PhpOffice\PhpWord\Element\Text ) {
			return (string) $element->getText();
		}

		if ( $element instanceof \PhpOffice\PhpWord\Element\TextRun ) {
			return $this->extract_from_container( $element );
		}

		if ( $element instanceof \PhpOffice\PhpWord\Element\Table ) {
			return $this->extract_from_table( $element );
		}

		if ( $element instanceof \PhpOffice\PhpWord\Element\ListItem ) {
			$text_object = $element->getTextObject();
			if ( $text_object instanceof \PhpOffice\PhpWord\Element\Text ) {
				return (string) $text_object->getText();
			}
			return '';
		}

		// Unknown container — try a generic walk before giving up.
		if ( method_exists( $element, 'getElements' ) ) {
			return $this->extract_from_container( $element );
		}

		return '';
	}

	/**
	 * Walk a table's rows and cells. Cells in a row are joined with tabs so
	 * downstream consumers can still tell columns apart; rows are joined
	 * with newlines.
	 *
	 * @param \PhpOffice\PhpWord\Element\Table $table table element.
	 * @return string flattened table text.
	 */
	private function extract_from_table( \PhpOffice\PhpWord\Element\Table $table ): string {
		$rows = array();
		foreach ( $table->getRows() as $row ) {
			$cell_texts = array();
			foreach ( $row->getCells() as $cell ) {
				$cell_texts[] = $this->extract_from_container( $cell );
			}
			$cell_texts = array_filter( $cell_texts, 'strlen' );
			if ( ! empty( $cell_texts ) ) {
				$rows[] = implode( "\t", $cell_texts );
			}
		}
		return $this->join_parts( $rows );
	}

	/**
	 * Drop empty strings and join the rest with newlines.
	 *
	 * @param string[] $parts text fragments to concatenate.
	 * @return string non-empty fragments joined by newline.
	 */
	private function join_parts( array $parts ): string {
		return implode( "\n", array_filter( $parts, 'strlen' ) );
	}
}
