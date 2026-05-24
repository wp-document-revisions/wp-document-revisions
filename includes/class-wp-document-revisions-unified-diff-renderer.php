<?php
/**
 * Unified-diff renderer for WordPress core's `Text_Diff`.
 *
 * WordPress ships `Text_Diff_Renderer` (the abstract base) and a
 * `WP_Text_Diff_Renderer_Table` for the post-revisions UI, but does NOT
 * include PEAR's `Text_Diff_Renderer_unified`. This file defines a small
 * subclass of `Text_Diff_Renderer` that produces standard unified-diff
 * output (`@@ -x,y +a,b @@` block headers; lines prefixed ` `, `+`, `-`)
 * for use by {@see WP_Document_Revisions_Text_Diff}.
 *
 * The file is intentionally loaded lazily — it depends on
 * `Text_Diff_Renderer` being defined, which only happens after
 * `ABSPATH . 'wp-includes/wp-diff.php'` has been required. The helper class
 * loads both before instantiating the renderer; do not require this file
 * directly at plugin bootstrap.
 *
 * Phase 10 of issue #514.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Sanity guard — this file MUST be required after wp-diff.php has loaded.
if ( ! class_exists( 'Text_Diff_Renderer' ) ) {
	return;
}

/**
 * Render a `Text_Diff` as standard unified-diff text.
 */
class WP_Document_Revisions_Unified_Diff_Renderer extends Text_Diff_Renderer {

	/**
	 * Number of leading context lines per hunk.
	 *
	 * The base class defaults to 0 (WP overrides PEAR's 4). The issue's
	 * design calls for ~3 lines of context so AI summaries can resolve
	 * references like "Section 4.2" from surrounding text.
	 *
	 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
	 *
	 * @var int
	 */
	public $_leading_context_lines = 3;

	/**
	 * Number of trailing context lines per hunk.
	 *
	 * @var int
	 */
	public $_trailing_context_lines = 3;

	// phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Render the unified-format hunk header.
	 *
	 * Mirrors the GNU diff `@@ -<xbeg>,<xlen> +<ybeg>,<ylen> @@` form.
	 * When a side contributes a single line, GNU diff sometimes omits the
	 * length suffix; we always emit the explicit `,N` form so downstream
	 * parsers do not have to handle both shapes.
	 *
	 * @param int $xbeg first line of the original-side hunk (1-indexed).
	 * @param int $xlen number of lines from the original side.
	 * @param int $ybeg first line of the new-side hunk (1-indexed).
	 * @param int $ylen number of lines from the new side.
	 * @return string the `@@ ... @@` header.
	 */
	public function _blockHeader( $xbeg, $xlen, $ybeg, $ylen ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return sprintf( '@@ -%d,%d +%d,%d @@', $xbeg, $xlen, $ybeg, $ylen );
	}

	/**
	 * Emit the header line plus a newline so subsequent line groups in
	 * the same hunk start on their own row.
	 *
	 * @param string $header the header text returned by _blockHeader().
	 * @return string header followed by a single newline.
	 */
	public function _startBlock( $header ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $header . "\n";
	}

	/**
	 * Emit nothing between hunks — line groups already terminate with
	 * their own trailing newlines.
	 *
	 * @return string empty string.
	 */
	public function _endBlock() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return '';
	}

	/**
	 * Prefix every line in a group and terminate the group with a
	 * newline. Used by _added(), _deleted(), and _context() below.
	 *
	 * @param string[] $lines  individual diff lines (no trailing newlines).
	 * @param string   $prefix one-character prefix to add to each line.
	 * @return string the prefixed lines joined with newlines, plus a
	 *                trailing newline so the next group starts on its
	 *                own row.
	 */
	public function _lines( $lines, $prefix = ' ' ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		if ( empty( $lines ) ) {
			return '';
		}
		return $prefix . implode( "\n" . $prefix, $lines ) . "\n";
	}

	/**
	 * Render added lines (lines present only in the new side).
	 *
	 * @param string[] $lines lines added.
	 * @return string lines prefixed with `+`.
	 */
	public function _added( $lines ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->_lines( $lines, '+' );
	}

	/**
	 * Render deleted lines (lines present only in the original side).
	 *
	 * @param string[] $lines lines deleted.
	 * @return string lines prefixed with `-`.
	 */
	public function _deleted( $lines ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->_lines( $lines, '-' );
	}

	/**
	 * Render context lines (lines unchanged between the two sides).
	 *
	 * @param string[] $lines unchanged lines.
	 * @return string lines prefixed with a single space.
	 */
	public function _context( $lines ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->_lines( $lines, ' ' );
	}

	/**
	 * Render a changed hunk (some lines deleted, others added in their place).
	 *
	 * Unified diff represents this as the deletions first, then the
	 * additions — no inline "change" form like the table renderer uses.
	 *
	 * The parent's signature uses `$final` for the new-side lines; we
	 * rename to `$replacement` here because phpcs treats `$final` as a
	 * reserved-keyword parameter name (PHP allows it, but it shadows
	 * the `final` class/method keyword and is harder to read). PHP
	 * does not require parameter names to match across overrides.
	 *
	 * @param string[] $orig        original-side lines being replaced.
	 * @param string[] $replacement new-side lines that replace them.
	 * @return string deletions followed by additions.
	 */
	public function _changed( $orig, $replacement ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->_deleted( $orig ) . $this->_added( $replacement );
	}
}
