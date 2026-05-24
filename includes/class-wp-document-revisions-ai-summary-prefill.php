<?php
/**
 * Admin-editor JS enqueue for the AI revision-log pre-fill.
 *
 * On the document edit screen, this class enqueues a small JS module
 * that fetches the cached AI summary for the document's current
 * revision via the phase-11 REST endpoint and writes it into the
 * revision-log textarea — but only when:
 *
 *   - The pre-fill is not disabled globally
 *     (`WPDR_AI_SUMMARY_PREFILL = false`) or per-document
 *     (the "Do not pre-fill" checkbox is checked).
 *   - The textarea is currently empty (we never clobber a value the
 *     editor has already typed).
 *   - The summary endpoint returns `status: 'ready'`.
 *
 * Generation is cron-driven from phase 11; this JS only reads. On a
 * `pending` response it shows a small note rather than polling, so the
 * editor doesn't see the textarea mutate while they're typing (advisor
 * call from the phase-12 design review: simpler beats polished here).
 *
 * Phase 12 of issue #514.
 *
 * @since 5.0.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the revision-log pre-fill JS on the document edit screen.
 */
class WP_Document_Revisions_AI_Summary_Prefill {

	/**
	 * Script handle for the pre-fill JS module.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'wpdr-ai-summary-prefill';

	/**
	 * Name of the JS global containing localized config + i18n strings.
	 *
	 * @var string
	 */
	const JS_OBJECT = 'wpdrAISummaryPrefill';

	/**
	 * Register the admin enqueue hook. Idempotent / safe to call from
	 * the plugin bootstrap.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
	}

	/**
	 * Enqueue + localize the pre-fill JS when we're on the document
	 * edit screen, the user can read the document, and the pre-fill
	 * is not opted out for this document or the site.
	 *
	 * @param string $hook current admin page hook (e.g. 'post.php').
	 * @return void
	 */
	public static function maybe_enqueue( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'document' !== $screen->post_type ) {
			return;
		}

		global $post;
		if ( ! $post instanceof WP_Post || 'document' !== $post->post_type ) {
			return;
		}

		$document_id = (int) $post->ID;
		if ( $document_id <= 0 ) {
			return;
		}

		if ( WP_Document_Revisions_Text_Extraction_Opt_Out::is_prefill_disabled_for_document( $document_id ) ) {
			return;
		}

		if ( ! current_user_can( 'read_document', $document_id ) ) {
			return;
		}

		$revision_id = self::current_revision_attachment_id( $document_id );
		if ( $revision_id <= 0 ) {
			// No revision attachment yet — the JS would have nothing
			// to fetch a summary for. Skip.
			return;
		}

		$suffix = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.dev' : '';
		$path   = '/js/wp-document-revisions-ai-prefill' . $suffix . '.js';
		$abs    = dirname( __DIR__ ) . $path;
		$vers   = file_exists( $abs ) ? (string) filemtime( $abs ) : '5.0.0';

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			plugins_url( $path, __DIR__ ),
			array( 'wp-api-fetch' ),
			$vers,
			true
		);

		$data = array(
			'restPath'       => sprintf(
				'%s/documents/%d/revisions/%d/summary',
				WP_Document_Revisions_AI_Summary_REST::ROUTE_NAMESPACE,
				$document_id,
				$revision_id
			),
			'fieldId'        => 'excerpt',
			'initialDelayMs' => 10000,
			'i18n'           => array(
				'hint'    => __( '✨ AI suggestion — edit before saving.', 'wp-document-revisions' ),
				'dismiss' => __( 'Dismiss', 'wp-document-revisions' ),
				'pending' => __( '✨ AI summary will be available shortly — refresh this page to see it.', 'wp-document-revisions' ),
			),
		);

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'var ' . self::JS_OBJECT . ' = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}

	/**
	 * Resolve the attachment ID for the document's current (latest)
	 * revision, used as the target of the REST summary fetch.
	 *
	 * @param int $document_id ID of the document post.
	 * @return int attachment ID, or 0 when no revision exists yet.
	 */
	public static function current_revision_attachment_id( int $document_id ): int {
		global $wpdr;
		if ( ! $wpdr || ! method_exists( $wpdr, 'get_latest_revision' ) ) {
			return 0;
		}
		$revision = $wpdr->get_latest_revision( $document_id );
		if ( ! $revision || ! isset( $revision->post_content ) ) {
			return 0;
		}
		// $wpdr->get_latest_revision() stores the attachment ID in the
		// revision's post_content. Older revisions used a formatted
		// URL; tolerate both shapes by extracting the trailing integer.
		$content = (string) $revision->post_content;
		if ( ctype_digit( $content ) ) {
			return (int) $content;
		}
		if ( preg_match( '/(\d+)\D*$/', $content, $matches ) ) {
			return (int) $matches[1];
		}
		return 0;
	}
}
