<?php
/**
 * AI-generated revision summaries.
 *
 * After text extraction completes on a revision attachment (signalled by
 * the `wpdr_text_extracted` action fired from {@see
 * WP_Document_Revisions_Text_Extractor_Cache::set()}), this class queues
 * an async cron event that:
 *
 *   1. Finds the immediately prior revision of the same document.
 *   2. Computes the unified diff via {@see WP_Document_Revisions_Text_Diff}.
 *   3. Routes the diff status to a summary "kind":
 *        ok                → 'change'      (send the diff to the model)
 *        identical         → 'no_change'   (no AI call, fixed message)
 *        too_large         → 'document'    (summarise the new text directly)
 *        old_text_missing  → 'document'    (no prior text to diff against)
 *        new_text_missing  → 'unavailable' (cannot summarise yet)
 *      The same mapping applies when no prior revision exists at all
 *      (initial upload → 'document').
 *   4. Calls the WordPress 7.0 AI Client via {@see wp_ai_client_prompt()},
 *      with a filterable system prompt, and stores the result.
 *
 * The result lives in post meta on the new revision attachment:
 *
 *   _wpdr_ai_summary_text          Cached summary string.
 *   _wpdr_ai_summary_kind          One of 'change'|'document'|'no_change'|'unavailable'.
 *   _wpdr_ai_summary_input_hash    SHA-256 of the exact input sent to the
 *                                  model (the diff for 'change', the new
 *                                  extracted text for 'document'). Used as
 *                                  the cache key so re-running the cron
 *                                  against unchanged inputs is a no-op.
 *   _wpdr_ai_summary_generated_at  Unix timestamp of generation.
 *   _wpdr_ai_summary_reviewed_by   User ID who marked the summary reviewed
 *                                  via the REST endpoint, or 0.
 *   _wpdr_ai_summary_reviewed_at   Unix timestamp of review, or 0.
 *
 * Sites without WordPress 7.0 (or with `WP_AI_SUPPORT` set to false)
 * skip silently — the cron event runs but stores nothing, the REST
 * endpoint reports status `unavailable`. Tests inject a generator via
 * the `wpdr_ai_summary_generator` filter so the rest of the pipeline
 * is exercised without a live AI provider.
 *
 * Phase 11 of issue #514.
 *
 * @since 5.0.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron-driven AI summary generator + storage layer for revision attachments.
 */
class WP_Document_Revisions_AI_Summary {

	/**
	 * Post-meta key holding the cached summary text.
	 *
	 * @var string
	 */
	const META_KEY_TEXT = '_wpdr_ai_summary_text';

	/**
	 * Post-meta key holding the summary kind.
	 *
	 * @var string
	 */
	const META_KEY_KIND = '_wpdr_ai_summary_kind';

	/**
	 * Post-meta key holding the SHA-256 of the input the model was asked
	 * to summarise. Re-running generation when this hash matches the
	 * current inputs is a no-op.
	 *
	 * @var string
	 */
	const META_KEY_INPUT_HASH = '_wpdr_ai_summary_input_hash';

	/**
	 * Post-meta key holding the Unix timestamp of generation.
	 *
	 * @var string
	 */
	const META_KEY_GENERATED_AT = '_wpdr_ai_summary_generated_at';

	/**
	 * Post-meta key holding the user ID who marked the summary reviewed.
	 *
	 * @var string
	 */
	const META_KEY_REVIEWED_BY = '_wpdr_ai_summary_reviewed_by';

	/**
	 * Post-meta key holding the Unix timestamp of review.
	 *
	 * @var string
	 */
	const META_KEY_REVIEWED_AT = '_wpdr_ai_summary_reviewed_at';

	/**
	 * Cron action name fired to generate a summary for one attachment.
	 *
	 * @var string
	 */
	const CRON_ACTION = 'wpdr_generate_ai_summary';

	/**
	 * Default delay between the `wpdr_text_extracted` action and the
	 * summary cron event. Mirrors phase 7's ~10-second pattern so the
	 * request that triggered extraction has time to settle.
	 *
	 * @var int
	 */
	const DEFAULT_DELAY = 10;

	/**
	 * Default per-call timeout, in seconds, applied via set_time_limit()
	 * inside the cron handler. The AI provider may itself enforce a
	 * shorter timeout; this is the local upper bound.
	 *
	 * @var int
	 */
	const DEFAULT_TIMEOUT = 60;

	/**
	 * Fixed string stored as the summary when the diff was empty
	 * (file changed but extracted text did not). No AI call required.
	 *
	 * @var string
	 */
	const NO_CHANGE_MESSAGE = 'No textual change between this revision and the prior one.';

	/**
	 * Register the cache-trigger hook and cron handler.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wpdr_text_extracted', array( __CLASS__, 'maybe_schedule' ) );
		add_action( self::CRON_ACTION, array( __CLASS__, 'run' ) );
	}

	/**
	 * Whether the WordPress 7.0 AI Client is available for the current
	 * site. Honours the `WP_AI_SUPPORT` constant (false disables AI use
	 * even if the client is loaded) and the function-existence check
	 * (the AI Client ships in WP 7.0; older WordPress does not have it).
	 *
	 * @return bool true when summaries can be generated via the AI Client.
	 */
	public static function is_ai_available(): bool {
		if ( defined( 'WP_AI_SUPPORT' ) && false === WP_AI_SUPPORT ) {
			return false;
		}
		/**
		 * Filter the availability check so tests (and sites running an
		 * alternative provider via the `wpdr_ai_summary_generator`
		 * filter) can force-enable the pipeline without WP 7.0.
		 *
		 * @since 5.0.0
		 *
		 * @param bool|null $available Force true/false to override; null
		 *                             defers to the default check.
		 */
		$forced = apply_filters( 'wpdr_ai_summary_available', null );
		if ( null !== $forced ) {
			return (bool) $forced;
		}
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Hook callback for `wpdr_text_extracted`. Queues a single cron
	 * event for the attachment if one is not already scheduled.
	 *
	 * @param int $attachment_id ID of the attachment whose text just landed in the cache.
	 * @return void
	 */
	public static function maybe_schedule( int $attachment_id ): void {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return;
		}

		$parent_id = (int) wp_get_post_parent_id( $attachment_id );
		if ( $parent_id <= 0 || 'document' !== get_post_type( $parent_id ) ) {
			return;
		}

		if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $parent_id ) ) {
			return;
		}

		$args = array( $attachment_id );
		if ( false !== wp_next_scheduled( self::CRON_ACTION, $args ) ) {
			return;
		}

		/**
		 * Filter the delay between text extraction and summary generation.
		 *
		 * @since 5.0.0
		 *
		 * @param int $delay         Seconds to wait before running the summary cron.
		 * @param int $attachment_id Attachment being scheduled.
		 */
		$delay = (int) apply_filters( 'wpdr_ai_summary_delay', self::DEFAULT_DELAY, $attachment_id );
		if ( $delay < 0 ) {
			$delay = 0;
		}

		wp_schedule_single_event( time() + $delay, self::CRON_ACTION, $args );
	}

	/**
	 * Cron handler: generate (or refresh) the AI summary for one attachment.
	 *
	 * Idempotent within a generation: re-running against an attachment
	 * whose stored input_hash already matches the current inputs is a
	 * no-op. Replacing the file (which changes extraction → changes the
	 * diff → changes the input hash) cleanly invalidates the cached
	 * summary on the next cron pass.
	 *
	 * @param int $attachment_id ID of the new-side revision attachment.
	 * @return void
	 */
	public static function run( int $attachment_id ): void {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return;
		}

		$parent_id = (int) wp_get_post_parent_id( $attachment_id );
		if ( $parent_id <= 0 || 'document' !== get_post_type( $parent_id ) ) {
			return;
		}

		if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $parent_id ) ) {
			return;
		}

		$plan = self::plan_for( $attachment_id, $parent_id );
		if ( null === $plan ) {
			return;
		}

		$current_input_hash = '' === $plan['input'] ? '' : hash( 'sha256', $plan['input'] );
		$stored_input_hash  = (string) get_post_meta( $attachment_id, self::META_KEY_INPUT_HASH, true );
		$stored_kind        = (string) get_post_meta( $attachment_id, self::META_KEY_KIND, true );

		// Same inputs + same kind → cached summary already accurate.
		if ( '' !== $stored_kind && $stored_kind === $plan['kind'] && $stored_input_hash === $current_input_hash ) {
			return;
		}

		// No AI call needed for these kinds — record a fixed result.
		if ( 'no_change' === $plan['kind'] ) {
			self::store( $attachment_id, $plan['kind'], self::NO_CHANGE_MESSAGE, $current_input_hash );
			return;
		}
		if ( 'unavailable' === $plan['kind'] ) {
			self::store( $attachment_id, $plan['kind'], '', $current_input_hash );
			return;
		}

		if ( ! self::is_ai_available() ) {
			// Defer: do not store an empty summary so a later run (after
			// the site upgrades to WP 7.0 / enables WP_AI_SUPPORT) will
			// generate properly.
			return;
		}

		$timeout = (int) apply_filters( 'wpdr_ai_summary_timeout', self::DEFAULT_TIMEOUT, $attachment_id );
		if ( $timeout > 0 ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.Risky
			@set_time_limit( $timeout );
		}

		$prompt = self::compose_prompt( $plan['kind'], $plan['input'], $attachment_id );
		$text   = self::call_ai( $prompt );
		if ( null === $text ) {
			return;
		}

		self::store( $attachment_id, $plan['kind'], $text, $current_input_hash );
	}

	/**
	 * Decide what to summarise for an attachment and produce the input
	 * string that should be hashed + (optionally) sent to the model.
	 *
	 * @param int $attachment_id new-side revision attachment.
	 * @param int $parent_id     parent document post ID.
	 * @return array{kind: string, input: string}|null plan, or null on hard failure.
	 */
	private static function plan_for( int $attachment_id, int $parent_id ): ?array {
		$prior_id = self::prior_revision_id( $attachment_id, $parent_id );

		// No prior revision (initial upload) → document summary path.
		if ( 0 === $prior_id ) {
			$new_text = wpdr_extract_text( $attachment_id );
			if ( '' === $new_text ) {
				return array(
					'kind'  => 'unavailable',
					'input' => '',
				);
			}
			return array(
				'kind'  => 'document',
				'input' => $new_text,
			);
		}

		$result = WP_Document_Revisions_Text_Diff::diff_revisions( $prior_id, $attachment_id );

		switch ( $result['status'] ) {
			case 'ok':
				return array(
					'kind'  => 'change',
					'input' => $result['diff'],
				);
			case 'identical':
				return array(
					'kind'  => 'no_change',
					'input' => '',
				);
			case 'too_large':
			case 'old_text_missing':
				// Either side has no useful text-difference signal:
				// fall back to summarising the new side directly.
				$new_text = wpdr_extract_text( $attachment_id );
				if ( '' === $new_text ) {
					return array(
						'kind'  => 'unavailable',
						'input' => '',
					);
				}
				return array(
					'kind'  => 'document',
					'input' => $new_text,
				);
			case 'new_text_missing':
				// New side has no extracted text yet — defer until it does.
				return array(
					'kind'  => 'unavailable',
					'input' => '',
				);
			default:
				return null;
		}
	}

	/**
	 * Find the revision attachment that immediately precedes a given
	 * revision attachment within the same document.
	 *
	 * Returns 0 when the passed attachment is the document's first
	 * revision (initial upload).
	 *
	 * Sorts attachments by ID descending before walking so the result
	 * is independent of whatever order `$wpdr->get_attachments()`
	 * returns — attachment IDs are strictly increasing per WordPress's
	 * post_id sequence, so "older" reliably means "lower ID."
	 *
	 * @param int $attachment_id new-side revision attachment.
	 * @param int $document_id   parent document post ID.
	 * @return int prior attachment ID, or 0 when no prior revision exists.
	 */
	public static function prior_revision_id( int $attachment_id, int $document_id ): int {
		global $wpdr;
		if ( ! $wpdr || ! method_exists( $wpdr, 'get_attachments' ) ) {
			return 0;
		}
		$attachments = $wpdr->get_attachments( $document_id );
		if ( ! is_array( $attachments ) ) {
			return 0;
		}
		$ids = array();
		foreach ( $attachments as $attachment ) {
			$id = isset( $attachment->ID ) ? (int) $attachment->ID : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		rsort( $ids );

		$found = false;
		foreach ( $ids as $id ) {
			if ( $found ) {
				return $id;
			}
			if ( $id === $attachment_id ) {
				$found = true;
			}
		}
		return 0;
	}

	/**
	 * Compose the prompt text to send to the AI Client. The default
	 * templates are filterable via `wpdr_ai_summary_prompt` so sites
	 * can tune tone, length, or language without touching code.
	 *
	 * The filter intentionally does NOT receive the input text — the
	 * input is what the prompt operates on, not part of the prompt
	 * template. Filters that prepend rather than replace are a common
	 * source of subtle bugs, so we pass `kind` and the revision ID and
	 * let the filter decide which sprintf format string to return.
	 *
	 * @param string $kind          one of 'change' or 'document'.
	 * @param string $input         the diff or text to be summarised.
	 * @param int    $attachment_id the revision attachment being summarised.
	 * @return string the assembled prompt.
	 */
	public static function compose_prompt( string $kind, string $input, int $attachment_id ): string {
		if ( 'change' === $kind ) {
			$default = 'You are summarising a change to a document. Given the following unified diff, write a 1–3 sentence summary describing what changed. Focus on substantive content changes, not formatting or whitespace, and do not narrate the diff format itself — describe the change as it would appear to a reader of the document.';
		} else {
			$default = 'You are summarising a document. Given the following text, write a 1–3 sentence summary describing what the document is about and its key topics.';
		}

		/**
		 * Filter the system prompt template used for AI summaries.
		 *
		 * The filter receives the kind and the revision ID but NOT the
		 * input text — the prompt template is the instruction; the
		 * input is concatenated after it. Return a plain string.
		 *
		 * @since 5.0.0
		 *
		 * @param string $template      Default prompt for this kind.
		 * @param string $kind          'change' or 'document'.
		 * @param int    $attachment_id Revision attachment being summarised.
		 */
		$template = (string) apply_filters( 'wpdr_ai_summary_prompt', $default, $kind, $attachment_id );

		return $template . "\n\n" . $input;
	}

	/**
	 * Invoke the AI Client (or a test-injected generator) for the given
	 * prompt. Returns null on any failure so the caller can short-circuit.
	 *
	 * @param string $prompt fully-assembled prompt.
	 * @return string|null generated text, or null on failure.
	 */
	private static function call_ai( string $prompt ): ?string {
		/**
		 * Allow tests and sites running an alternative AI provider to
		 * intercept the call. Return a string to short-circuit (used by
		 * the test suite); return null to defer to the real provider.
		 *
		 * @since 5.0.0
		 *
		 * @param string|null $result Forced result, or null to defer.
		 * @param string      $prompt The composed prompt.
		 */
		$injected = apply_filters( 'wpdr_ai_summary_generator', null, $prompt );
		if ( is_string( $injected ) ) {
			return $injected;
		}

		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return null;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		$response = wp_ai_client_prompt( $prompt )->generate_text();
		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Document Revisions: AI summary generation failed: ' . $response->get_error_message() );
			return null;
		}
		if ( ! is_string( $response ) || '' === trim( $response ) ) {
			return null;
		}
		return trim( $response );
	}

	/**
	 * Persist a summary result. Every call clears the reviewed-by/at
	 * meta on the principle that a freshly-stored summary is by
	 * definition not yet reviewed — and `run()` only invokes this
	 * function when the inputs have actually changed (a same-inputs
	 * regeneration is short-circuited earlier).
	 *
	 * @param int    $attachment_id revision attachment whose summary we are storing.
	 * @param string $kind          one of 'change'|'document'|'no_change'|'unavailable'.
	 * @param string $text          the cached summary string ('' for 'unavailable').
	 * @param string $input_hash    SHA-256 of the input sent to the model ('' when not applicable).
	 * @return void
	 */
	public static function store( int $attachment_id, string $kind, string $text, string $input_hash ): void {
		if ( $attachment_id <= 0 ) {
			return;
		}
		update_post_meta( $attachment_id, self::META_KEY_TEXT, $text );
		update_post_meta( $attachment_id, self::META_KEY_KIND, $kind );
		update_post_meta( $attachment_id, self::META_KEY_INPUT_HASH, $input_hash );
		update_post_meta( $attachment_id, self::META_KEY_GENERATED_AT, time() );
		// New summary supersedes any prior review — the previous review
		// applied to text that no longer reflects the current revision.
		delete_post_meta( $attachment_id, self::META_KEY_REVIEWED_BY );
		delete_post_meta( $attachment_id, self::META_KEY_REVIEWED_AT );
	}

	/**
	 * Mark a summary as human-reviewed by the current user. Returns
	 * false when the attachment has no stored summary to review.
	 *
	 * @param int $attachment_id revision attachment whose summary is being reviewed.
	 * @param int $user_id       user marking the review (0 to unmark).
	 * @return bool true on success.
	 */
	public static function set_reviewed( int $attachment_id, int $user_id ): bool {
		if ( $attachment_id <= 0 ) {
			return false;
		}
		$kind = (string) get_post_meta( $attachment_id, self::META_KEY_KIND, true );
		if ( '' === $kind ) {
			return false;
		}
		if ( $user_id > 0 ) {
			update_post_meta( $attachment_id, self::META_KEY_REVIEWED_BY, $user_id );
			update_post_meta( $attachment_id, self::META_KEY_REVIEWED_AT, time() );
		} else {
			delete_post_meta( $attachment_id, self::META_KEY_REVIEWED_BY );
			delete_post_meta( $attachment_id, self::META_KEY_REVIEWED_AT );
		}
		return true;
	}

	/**
	 * Read the stored summary record for a revision attachment.
	 *
	 * Returns null when nothing has been stored yet (the cron event has
	 * not run, or has not yet completed). Callers use this to drive the
	 * REST `pending` vs `ready` distinction.
	 *
	 * @param int $attachment_id revision attachment ID.
	 * @return array{text: string, kind: string, generated_at: int, reviewed_by: int, reviewed_at: int}|null
	 */
	public static function get( int $attachment_id ): ?array {
		if ( $attachment_id <= 0 ) {
			return null;
		}
		$kind = (string) get_post_meta( $attachment_id, self::META_KEY_KIND, true );
		if ( '' === $kind ) {
			return null;
		}
		return array(
			'text'         => (string) get_post_meta( $attachment_id, self::META_KEY_TEXT, true ),
			'kind'         => $kind,
			'generated_at' => (int) get_post_meta( $attachment_id, self::META_KEY_GENERATED_AT, true ),
			'reviewed_by'  => (int) get_post_meta( $attachment_id, self::META_KEY_REVIEWED_BY, true ),
			'reviewed_at'  => (int) get_post_meta( $attachment_id, self::META_KEY_REVIEWED_AT, true ),
		);
	}
}
