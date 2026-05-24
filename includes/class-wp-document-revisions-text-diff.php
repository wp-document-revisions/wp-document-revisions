<?php
/**
 * Unified-diff helper between two cached extracted texts.
 *
 * Wraps WordPress core's `Text_Diff` engine with a unified-format
 * renderer (see {@see WP_Document_Revisions_Unified_Diff_Renderer}) and
 * exposes two entry points:
 *
 * - {@see self::diff_text()}: diff two plain strings directly.
 * - {@see self::diff_revisions()}: diff the cached extracted text of
 *   two revision attachments (loaded via {@see wpdr_extract_text()}).
 *
 * Both methods return a structured array — `['status' => ..., 'diff' => ...]`
 * — rather than a bare string, so callers (notably the upcoming phase
 * 11 summary REST endpoint) can distinguish between an empty diff
 * (text unchanged), a too-large diff (fall back to document-summary),
 * and a missing extracted text on either side (also fall back to
 * document-summary). The status vocabulary is:
 *
 *   'identical'         — old and new texts compare equal; diff is ''.
 *   'ok'                — diff is non-empty and within the size cap.
 *   'too_large'         — diff would exceed `max_chars`; diff is ''.
 *   'old_text_missing'  — diff_revisions: wpdr_extract_text() returned
 *                         empty for the old revision (no cached text or
 *                         legitimately empty extraction). diff is ''.
 *   'new_text_missing'  — same, for the new revision. diff is ''.
 *
 * Configurable via filters and per-call options:
 *
 *   'context_lines'  default 3, filter `wpdr_text_diff_context_lines`.
 *   'max_chars'      default 50000, filter `wpdr_text_diff_max_chars`.
 *
 * The underlying `Text_Diff_Renderer` class is loaded lazily from
 * `wp-diff.php` inside the public methods so plugin bootstrap pays
 * nothing on requests that never call into the diff utility.
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

/**
 * Unified-diff helper for cached extracted text.
 */
class WP_Document_Revisions_Text_Diff {

	/**
	 * Default leading and trailing context lines per hunk.
	 *
	 * Aligned with the issue's design ("~3 lines of context per hunk").
	 * Overridable per-call via the `context_lines` option and globally
	 * via the `wpdr_text_diff_context_lines` filter.
	 *
	 * @var int
	 */
	const DEFAULT_CONTEXT_LINES = 3;

	/**
	 * Default size cap on the rendered diff, measured in characters.
	 *
	 * The phase-11 summary endpoint uses this to decide when a diff
	 * is too large to be useful as model input — in those cases the
	 * caller falls back to summarising the new document directly.
	 * Filterable via `wpdr_text_diff_max_chars`; per-call override
	 * via the `max_chars` option.
	 *
	 * @var int
	 */
	const DEFAULT_MAX_CHARS = 50000;

	/**
	 * Compute a unified diff between two plain-text strings.
	 *
	 * Treats both inputs as line-oriented text (split on "\n") and
	 * returns a result array as described in the class docblock.
	 *
	 * @param string                                      $old_text the original-side text.
	 * @param string                                      $new_text the new-side text.
	 * @param array{context_lines?: int, max_chars?: int} $opts     optional per-call overrides.
	 * @return array{status: string, diff: string} result with `status` in
	 *                                             {'identical','ok','too_large'} and
	 *                                             `diff` populated only when status is 'ok'.
	 */
	public static function diff_text( string $old_text, string $new_text, array $opts = array() ): array {
		if ( $old_text === $new_text ) {
			return array(
				'status' => 'identical',
				'diff'   => '',
			);
		}

		$context_lines = self::resolve_context_lines( $opts );
		$max_chars     = self::resolve_max_chars( $opts );

		self::load_diff_engine();

		$diff     = new Text_Diff(
			'auto',
			array(
				explode( "\n", $old_text ),
				explode( "\n", $new_text ),
			)
		);
		$renderer = new WP_Document_Revisions_Unified_Diff_Renderer();
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$renderer->_leading_context_lines  = $context_lines;
		$renderer->_trailing_context_lines = $context_lines;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$output = (string) $renderer->render( $diff );

		// Text_Diff with two distinct inputs that happen to produce no
		// hunks (shouldn't happen given the equality short-circuit
		// above, but defensive) is treated as identical.
		if ( '' === $output ) {
			return array(
				'status' => 'identical',
				'diff'   => '',
			);
		}

		if ( strlen( $output ) > $max_chars ) {
			return array(
				'status' => 'too_large',
				'diff'   => '',
			);
		}

		return array(
			'status' => 'ok',
			'diff'   => $output,
		);
	}

	/**
	 * Compute a unified diff between the cached extracted text of two
	 * revision attachments.
	 *
	 * Loads text via {@see wpdr_extract_text()} for each side — this
	 * runs the cache through the registered extractor on a miss, so
	 * callers do not need to pre-warm the cache. When extraction for
	 * either side returns the empty string the result reports
	 * `old_text_missing` / `new_text_missing` rather than collapsing
	 * to `identical`, so phase 11 can distinguish "scanned PDF on one
	 * side" from "both sides have the same text."
	 *
	 * @param int                                         $old_revision_id attachment post ID of the original-side revision.
	 * @param int                                         $new_revision_id attachment post ID of the new-side revision.
	 * @param array{context_lines?: int, max_chars?: int} $opts            optional per-call overrides.
	 * @return array{status: string, diff: string} result with `status` in
	 *                                             {'identical','ok','too_large','old_text_missing','new_text_missing'}.
	 */
	public static function diff_revisions( int $old_revision_id, int $new_revision_id, array $opts = array() ): array {
		$old_text = wpdr_extract_text( $old_revision_id );
		if ( '' === $old_text ) {
			return array(
				'status' => 'old_text_missing',
				'diff'   => '',
			);
		}

		$new_text = wpdr_extract_text( $new_revision_id );
		if ( '' === $new_text ) {
			return array(
				'status' => 'new_text_missing',
				'diff'   => '',
			);
		}

		return self::diff_text( $old_text, $new_text, $opts );
	}

	/**
	 * Resolve the leading/trailing context-line count for a call.
	 *
	 * Precedence: per-call `context_lines` option ➜ filter ➜ class default.
	 *
	 * @param array<string, mixed> $opts per-call options.
	 * @return int non-negative context-line count.
	 */
	private static function resolve_context_lines( array $opts ): int {
		if ( isset( $opts['context_lines'] ) ) {
			$value = (int) $opts['context_lines'];
		} else {
			/**
			 * Filter the default number of context lines per hunk used
			 * by the unified-diff helper.
			 *
			 * @since 4.1.0
			 *
			 * @param int $context_lines Default context-line count.
			 */
			$value = (int) apply_filters( 'wpdr_text_diff_context_lines', self::DEFAULT_CONTEXT_LINES );
		}
		return max( 0, $value );
	}

	/**
	 * Resolve the maximum rendered-diff size for a call.
	 *
	 * Precedence: per-call `max_chars` option ➜ filter ➜ class default.
	 *
	 * @param array<string, mixed> $opts per-call options.
	 * @return int non-negative maximum size in characters.
	 */
	private static function resolve_max_chars( array $opts ): int {
		if ( isset( $opts['max_chars'] ) ) {
			$value = (int) $opts['max_chars'];
		} else {
			/**
			 * Filter the maximum rendered-diff size, in characters,
			 * before the helper reports 'too_large'.
			 *
			 * @since 4.1.0
			 *
			 * @param int $max_chars Default size cap in characters.
			 */
			$value = (int) apply_filters( 'wpdr_text_diff_max_chars', self::DEFAULT_MAX_CHARS );
		}
		return max( 0, $value );
	}

	/**
	 * Ensure the WP core diff engine and the unified renderer subclass
	 * are loaded. Called from every public entry point and idempotent
	 * via class_exists().
	 *
	 * @return void
	 */
	private static function load_diff_engine(): void {
		if ( ! class_exists( 'Text_Diff_Renderer' ) ) {
			// Hard-coded 'wp-includes' rather than the WPINC constant
			// because phpstan-wordpress does not stub WPINC and this
			// directory name is part of WordPress's stable public
			// layout — it has not changed since 2.5 and is unlikely to.
			require_once ABSPATH . 'wp-includes/wp-diff.php';
		}
		if ( ! class_exists( 'WP_Document_Revisions_Unified_Diff_Renderer' ) ) {
			require_once __DIR__ . '/class-wp-document-revisions-unified-diff-renderer.php';
		}
	}
}
