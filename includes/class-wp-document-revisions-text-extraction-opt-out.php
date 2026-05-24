<?php
/**
 * Per-document opt-out for text extraction.
 *
 * Adds a meta box on the document edit screen with a single checkbox:
 * "Skip text extraction for this document." When checked, the
 * {@see WP_Document_Revisions_Text_Extractor_Scheduler} and
 * {@see wpdr_extract_text()} sync helper both bail before running an
 * extractor, and the document's revision attachments have their cached
 * text, hash, identity, and failure-flag meta cleared on save.
 *
 * The `WPDR_TEXT_EXTRACTION` constant remains the sitewide kill switch —
 * setting it to false disables extraction across every document
 * regardless of the per-document checkbox. The opt-out class is the
 * single source of truth for both checks (constant + per-document meta)
 * so scheduler and sync helper share one predicate.
 *
 * Phase 8 of issue #514.
 *
 * @since 4.1.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-document opt-out controller and predicate.
 */
class WP_Document_Revisions_Text_Extraction_Opt_Out {

	/**
	 * Post meta key on the document post holding the opt-out flag.
	 *
	 * Present and equal to '1' means the document is opted out. Absent or
	 * any other value means extraction proceeds normally.
	 *
	 * @var string
	 */
	const META_KEY = '_wpdr_no_text_extraction';

	/**
	 * Nonce field name posted by the meta-box form.
	 *
	 * @var string
	 */
	const NONCE_FIELD = 'wpdr_text_extraction_opt_out_nonce';

	/**
	 * Nonce action paired with {@see self::NONCE_FIELD}.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'wpdr_text_extraction_opt_out';

	/**
	 * Name of the checkbox input rendered into the meta box.
	 *
	 * @var string
	 */
	const FORM_FIELD = 'wpdr_text_extraction_opt_out';

	/**
	 * ID of the meta box added to the document edit screen.
	 *
	 * @var string
	 */
	const META_BOX_ID = 'wpdr-text-extraction-opt-out';

	/**
	 * Register the admin meta box and save handler.
	 *
	 * Only the admin-facing hooks are registered here; the
	 * {@see self::is_disabled_for_document()} predicate is callable from any
	 * runtime (cron, REST, frontend) without going through init().
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'add_meta_boxes_document', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post_document', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Whether extraction is currently disabled globally (via the
	 * `WPDR_TEXT_EXTRACTION` constant).
	 *
	 * @return bool true when the constant is defined and equal to false.
	 */
	public static function is_globally_disabled(): bool {
		return defined( 'WPDR_TEXT_EXTRACTION' ) && false === WPDR_TEXT_EXTRACTION;
	}

	/**
	 * Whether extraction is disabled for a specific document, accounting for
	 * both the global kill switch and the per-document opt-out flag.
	 *
	 * Pass 0 (or any non-positive ID) to ask "is extraction disabled in any
	 * way that does not depend on a document?" — useful for callers handling
	 * an attachment with no document parent.
	 *
	 * @param int $document_id ID of the document post to check.
	 * @return bool true when extraction should be skipped for this document.
	 */
	public static function is_disabled_for_document( int $document_id ): bool {
		if ( self::is_globally_disabled() ) {
			return true;
		}
		if ( $document_id <= 0 ) {
			return false;
		}
		return '1' === (string) get_post_meta( $document_id, self::META_KEY, true );
	}

	/**
	 * Add the opt-out meta box on the document edit screen.
	 *
	 * @return void
	 */
	public static function register_meta_box(): void {
		add_meta_box(
			self::META_BOX_ID,
			__( 'Text Extraction', 'wp-document-revisions' ),
			array( __CLASS__, 'render_meta_box' ),
			'document',
			'side',
			'low'
		);
	}

	/**
	 * Render the opt-out checkbox plus a short description.
	 *
	 * @param WP_Post $post current document post being edited.
	 * @return void
	 */
	public static function render_meta_box( WP_Post $post ): void {
		$opted_out = '1' === (string) get_post_meta( $post->ID, self::META_KEY, true );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<p>
			<label for="<?php echo esc_attr( self::FORM_FIELD ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( self::FORM_FIELD ); ?>"
					name="<?php echo esc_attr( self::FORM_FIELD ); ?>"
					value="1"
					<?php checked( $opted_out, true ); ?>
				/>
				<?php esc_html_e( 'Skip text extraction for this document', 'wp-document-revisions' ); ?>
			</label>
		</p>
		<p class="description">
			<?php
			esc_html_e(
				'When checked, plain text will not be extracted from uploaded files for this document, and any previously extracted text will be cleared. Useful for sensitive documents whose contents should not be cached for search, AI summaries, or other text-based features.',
				'wp-document-revisions'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Persist the checkbox state on document save, clearing any cached text
	 * on the document's revision attachments when the flag is newly enabled.
	 *
	 * @param int     $document_id ID of the saved document post.
	 * @param WP_Post $post        Saved post object.
	 * @return void
	 */
	public static function save( int $document_id, WP_Post $post ): void {
		if ( 'document' !== $post->post_type ) {
			return;
		}
		if ( wp_is_post_autosave( $document_id ) || wp_is_post_revision( $document_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified below.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_document', $document_id ) ) {
			return;
		}

		$was_disabled = '1' === (string) get_post_meta( $document_id, self::META_KEY, true );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
		$is_disabled = isset( $_POST[ self::FORM_FIELD ] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above.
			&& '1' === sanitize_text_field( wp_unslash( $_POST[ self::FORM_FIELD ] ) );

		if ( $is_disabled ) {
			update_post_meta( $document_id, self::META_KEY, '1' );
			if ( ! $was_disabled ) {
				self::clear_cached_text_for_document( $document_id );
			}
		} else {
			delete_post_meta( $document_id, self::META_KEY );
		}
	}

	/**
	 * Clear cached extracted text and unschedule pending async events for
	 * every revision attachment of a document.
	 *
	 * Called on the transition from "extraction allowed" to "extraction
	 * disabled" so previously-cached output cannot satisfy a future read,
	 * and a cron event already on the queue does not burn CPU running an
	 * extractor whose result will be discarded.
	 *
	 * @param int $document_id ID of the document whose attachments to clear.
	 * @return void
	 */
	public static function clear_cached_text_for_document( int $document_id ): void {
		global $wpdr;
		if ( ! $wpdr || ! method_exists( $wpdr, 'get_attachments' ) ) {
			return;
		}
		$attachments = $wpdr->get_attachments( $document_id );
		if ( ! is_array( $attachments ) ) {
			return;
		}
		foreach ( $attachments as $attachment ) {
			$attachment_id = isset( $attachment->ID ) ? (int) $attachment->ID : 0;
			if ( $attachment_id <= 0 ) {
				continue;
			}
			WP_Document_Revisions_Text_Extractor_Cache::clear( $attachment_id );
			wp_clear_scheduled_hook(
				WP_Document_Revisions_Text_Extractor_Scheduler::CRON_ACTION,
				array( $attachment_id )
			);
		}
	}
}
