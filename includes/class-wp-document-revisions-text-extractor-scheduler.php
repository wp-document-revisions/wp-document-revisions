<?php
/**
 * Async scheduler for text extraction.
 *
 * Hooks into the WordPress attachment-insert action so that whenever a new
 * revision attachment lands under a document, a single wp-cron event is
 * scheduled to extract its text out-of-band. Doing the work in cron keeps
 * a check-in request fast even for large files and consolidates the
 * extraction path so the synchronous helper can serve cached results.
 *
 * Failure handling: if the registered extractor throws a hard failure, the
 * cache class records the file's SHA-256 in `_wpdr_extraction_failed` and
 * the cron handler refuses to re-run for the same content. Replacing the
 * file (which changes the hash) cleanly retries.
 *
 * Phase 7 of issue #514. Phase 8 layered the per-document opt-out on
 * top: this class delegates the "should we run extraction at all" check
 * to {@see WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document()},
 * which honours both the `WPDR_TEXT_EXTRACTION` sitewide constant and
 * the per-document `_wpdr_no_text_extraction` flag.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedules and runs out-of-band text extraction for revision attachments.
 */
class WP_Document_Revisions_Text_Extractor_Scheduler {

	/**
	 * Cron action name fired to run extraction for a single attachment.
	 *
	 * @var string
	 */
	const CRON_ACTION = 'wpdr_extract_text_async';

	/**
	 * Default delay, in seconds, between attachment insert and cron pickup.
	 *
	 * The issue's design calls for ~10 seconds so the request that inserted
	 * the attachment can finish before extraction starts. Filterable via
	 * `wpdr_text_extraction_delay`.
	 *
	 * @var int
	 */
	const DEFAULT_DELAY = 10;

	/**
	 * Default per-file extraction timeout, in seconds.
	 *
	 * Applied via set_time_limit() inside the cron handler so a malformed
	 * file cannot lock up the worker indefinitely. Note that
	 * set_time_limit() is advisory — it's a no-op when safe_mode is on or
	 * when the function is disabled via disable_functions. Filterable via
	 * `wpdr_text_extraction_timeout`.
	 *
	 * @var int
	 */
	const DEFAULT_TIMEOUT = 30;

	/**
	 * Register the scheduler's hooks. Called once from the plugin bootstrap.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_attachment', array( __CLASS__, 'maybe_schedule' ) );
		add_action( self::CRON_ACTION, array( __CLASS__, 'run' ) );
	}

	/**
	 * Schedule async extraction if this attachment is a document revision.
	 *
	 * Silently skips non-document attachments, attachments without a parent,
	 * and any case where extraction is disabled via the
	 * `WPDR_TEXT_EXTRACTION` constant. Also skips if an event is already
	 * scheduled for this attachment, so a re-save during cron pickup does
	 * not enqueue duplicates.
	 *
	 * @param int $attachment_id ID of the newly-inserted attachment.
	 * @return void
	 */
	public static function maybe_schedule( int $attachment_id ): void {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return;
		}

		$parent_id = wp_get_post_parent_id( $attachment_id );
		if ( ! $parent_id || 'document' !== get_post_type( $parent_id ) ) {
			return;
		}

		// Phase 8: honour the sitewide kill switch and the per-document
		// opt-out via the single opt-out predicate.
		if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $parent_id ) ) {
			return;
		}

		$args = array( $attachment_id );
		if ( false !== wp_next_scheduled( self::CRON_ACTION, $args ) ) {
			return;
		}

		/**
		 * Filter the delay between attachment insert and async extraction.
		 *
		 * @since 4.1.0
		 *
		 * @param int $delay         Delay in seconds.
		 * @param int $attachment_id Attachment ID about to be scheduled.
		 */
		$delay = (int) apply_filters( 'wpdr_text_extraction_delay', self::DEFAULT_DELAY, $attachment_id );
		if ( $delay < 0 ) {
			$delay = 0;
		}

		wp_schedule_single_event( time() + $delay, self::CRON_ACTION, $args );
	}

	/**
	 * Cron callback: extract text for a single attachment and cache it.
	 *
	 * @param int $attachment_id ID of the revision attachment to process.
	 * @return void
	 */
	public static function run( int $attachment_id ): void {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return;
		}

		// Phase 8: re-check opt-out at cron time in case the document was
		// opted out between scheduling and pickup.
		$parent_id = (int) wp_get_post_parent_id( $attachment_id );
		if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_disabled_for_document( $parent_id ) ) {
			return;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! is_string( $file_path ) || '' === $file_path || ! is_readable( $file_path ) ) {
			return;
		}

		// Already cached for this exact file content — nothing to do.
		if ( null !== WP_Document_Revisions_Text_Extractor_Cache::get( $attachment_id, $file_path ) ) {
			return;
		}

		// Previously threw on this exact content — don't retry in a tight loop.
		if ( WP_Document_Revisions_Text_Extractor_Cache::is_failed( $attachment_id, $file_path ) ) {
			return;
		}

		$mime_type = (string) get_post_mime_type( $attachment_id );
		if ( '' === $mime_type ) {
			return;
		}

		$extractor = WP_Document_Revisions_Text_Extractor_Registry::find_for( $mime_type );
		if ( null === $extractor ) {
			return;
		}

		/**
		 * Filter the per-file extraction timeout, in seconds.
		 *
		 * @since 4.1.0
		 *
		 * @param int $timeout       Timeout in seconds. Zero disables the cap.
		 * @param int $attachment_id Attachment being extracted.
		 */
		$timeout = (int) apply_filters( 'wpdr_text_extraction_timeout', self::DEFAULT_TIMEOUT, $attachment_id );
		if ( $timeout > 0 ) {
			// Advisory only; safe_mode / disable_functions can no-op this.
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.IniSet.Risky
			@set_time_limit( $timeout );
		}

		try {
			$text = $extractor->extract( $file_path, $mime_type );
		} catch ( Throwable $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'WP Document Revisions: async text extraction failed for ' . $file_path . ': ' . $e->getMessage() );
			WP_Document_Revisions_Text_Extractor_Cache::mark_failed( $attachment_id, $file_path );
			return;
		}

		$identity = WP_Document_Revisions_Text_Extractor_Cache::identity_for( $extractor );
		WP_Document_Revisions_Text_Extractor_Cache::set( $attachment_id, $file_path, $text, $identity );
	}
}
