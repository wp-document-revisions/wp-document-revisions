<?php
/**
 * Email notifications for document workflow-state changes and new revisions.
 *
 * Notifications are opt-in (disabled by default) so existing installs never begin
 * sending mail on upgrade. Recipients are the admin-configured site-wide list plus
 * the document's author, excluding whoever made the change, deduplicated. The event
 * triggers (`document_change_workflow_state`, `document_saved`) and the mail-building
 * approach mirror the plugin's existing lock-override notice.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Sends email notifications for document events.
 */
class WP_Document_Revisions_Notifications {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'document_change_workflow_state', array( $this, 'notify_workflow_state_change' ), 10, 3 );
		add_action( 'document_saved', array( $this, 'notify_document_saved' ), 10, 2 );
	}

	/**
	 * Whether notifications are enabled at all.
	 *
	 * @return bool true if the master toggle is on.
	 */
	private function enabled(): bool {
		/**
		 * Filters whether document email notifications are enabled.
		 *
		 * @param bool $enabled whether notifications are enabled.
		 */
		return (bool) apply_filters( 'document_notify_enabled', (bool) get_site_option( 'document_notify_enabled', false ) );
	}

	/**
	 * Builds the recipient list for a document notification.
	 *
	 * Combines the site-wide recipient list with the document's author, drops the
	 * user who triggered the event, removes invalid and duplicate addresses, and
	 * exposes the result via a filter (the seam for per-document or state-based routing).
	 *
	 * @param int    $doc_id   the document ID.
	 * @param int    $actor_id the user ID who triggered the event.
	 * @param string $event    the event slug ('state_change' or 'new_revision').
	 * @return string[] validated, deduplicated email addresses.
	 */
	private function get_recipients( int $doc_id, int $actor_id, string $event ): array {
		$emails = array();

		// site-wide list.
		$raw = (string) get_site_option( 'document_notify_recipients', '' );
		foreach ( preg_split( '/[\s,]+/', $raw ) as $addr ) {
			$addr = sanitize_email( trim( (string) $addr ) );
			if ( is_email( $addr ) ) {
				$emails[] = $addr;
			}
		}

		// document author.
		$author = get_userdata( (int) get_post_field( 'post_author', $doc_id ) );
		if ( $author && is_email( $author->user_email ) ) {
			$emails[] = $author->user_email;
		}

		// exclude the person who made the change.
		$actor       = get_userdata( $actor_id );
		$actor_email = ( $actor ? strtolower( $actor->user_email ) : '' );
		$emails      = array_filter(
			$emails,
			static function ( $email ) use ( $actor_email ) {
				return strtolower( $email ) !== $actor_email;
			}
		);

		// dedupe (case-insensitively).
		$seen   = array();
		$unique = array();
		foreach ( $emails as $email ) {
			$key = strtolower( $email );
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ] = true;
				$unique[]     = $email;
			}
		}

		/**
		 * Filters the recipients of a document notification.
		 *
		 * @param string[] $unique   validated, deduplicated email addresses.
		 * @param int      $doc_id   the document ID.
		 * @param string   $event    the event slug ('state_change' or 'new_revision').
		 * @param int      $actor_id the user ID who triggered the event.
		 */
		$unique = apply_filters( 'document_notification_recipients', $unique, $doc_id, $event, $actor_id );

		// re-validate in case a filter added addresses.
		return array_values( array_filter( array_map( 'sanitize_email', (array) $unique ), 'is_email' ) );
	}

	/**
	 * Notifies recipients when a document's workflow state changes.
	 *
	 * @param int        $doc_id the document ID.
	 * @param int|string $new_id the new workflow_state term ID (empty string if none).
	 * @param int|string $old_id the previous workflow_state term ID (empty string if none).
	 */
	public function notify_workflow_state_change( int $doc_id, $new_id, $old_id ): void {
		if ( ! $this->enabled() ) {
			return;
		}

		/**
		 * Filters whether to notify on workflow-state changes.
		 *
		 * @param bool $enabled whether to notify on state changes.
		 */
		if ( ! apply_filters( 'document_notify_on_state_change', (bool) get_site_option( 'document_notify_on_state_change', true ) ) ) {
			return;
		}

		if ( 'document' !== get_post_type( $doc_id ) ) {
			return;
		}

		$actor_id   = get_current_user_id();
		$recipients = $this->get_recipients( $doc_id, $actor_id, 'state_change' );
		if ( empty( $recipients ) ) {
			return;
		}

		$document   = get_post( $doc_id );
		$new_state  = $this->state_name( $new_id );
		$old_state  = $this->state_name( $old_id );
		$actor      = get_userdata( $actor_id );
		$actor_name = ( $actor ? $actor->display_name : __( 'Someone', 'wp-document-revisions' ) );

		// translators: %1$s is the blog name, %2$s is the document title, %3$s is the new workflow state.
		$subject = sprintf( __( '%1$s: %2$s moved to %3$s', 'wp-document-revisions' ), get_bloginfo( 'name' ), $document->post_title, $new_state );

		// translators: %1$s is the user who made the change, %2$s is the document title.
		$message = sprintf( __( '%1$s changed the workflow state of the document %2$s.', 'wp-document-revisions' ), $actor_name, $document->post_title ) . "\n\n";
		if ( '' !== $old_state ) {
			// translators: %1$s is the previous workflow state, %2$s is the new workflow state.
			$message .= sprintf( __( 'State: %1$s to %2$s', 'wp-document-revisions' ), $old_state, $new_state ) . "\n\n";
		} else {
			// translators: %s is the new workflow state.
			$message .= sprintf( __( 'State: %s', 'wp-document-revisions' ), $new_state ) . "\n\n";
		}
		// translators: %s is the document URL.
		$message .= sprintf( __( 'View the document: %s', 'wp-document-revisions' ), get_permalink( $doc_id ) ) . "\n\n";
		// translators: %s is the blog name.
		$message .= sprintf( __( '- The %s Team', 'wp-document-revisions' ), get_bloginfo( 'name' ) );

		$this->send( $recipients, $subject, $message, $doc_id, 'state_change' );
	}

	/**
	 * Notifies recipients when a new revision of a document is saved.
	 *
	 * @param int $doc_id    the document ID.
	 * @param int $attach_id the attachment ID of the new revision.
	 */
	public function notify_document_saved( int $doc_id, int $attach_id ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->enabled() ) {
			return;
		}

		/**
		 * Filters whether to notify on new revisions.
		 *
		 * @param bool $enabled whether to notify on new revisions.
		 */
		if ( ! apply_filters( 'document_notify_on_new_revision', (bool) get_site_option( 'document_notify_on_new_revision', false ) ) ) {
			return;
		}

		if ( 'document' !== get_post_type( $doc_id ) ) {
			return;
		}

		$actor_id   = get_current_user_id();
		$recipients = $this->get_recipients( $doc_id, $actor_id, 'new_revision' );
		if ( empty( $recipients ) ) {
			return;
		}

		$document   = get_post( $doc_id );
		$actor      = get_userdata( $actor_id );
		$actor_name = ( $actor ? $actor->display_name : __( 'Someone', 'wp-document-revisions' ) );

		// translators: %1$s is the blog name, %2$s is the document title.
		$subject = sprintf( __( '%1$s: New revision of %2$s', 'wp-document-revisions' ), get_bloginfo( 'name' ), $document->post_title );

		// translators: %1$s is the user who saved the revision, %2$s is the document title.
		$message = sprintf( __( '%1$s saved a new revision of the document %2$s.', 'wp-document-revisions' ), $actor_name, $document->post_title ) . "\n\n";
		$summary = (string) get_post_field( 'post_excerpt', $doc_id );
		if ( '' !== $summary ) {
			// translators: %s is the revision summary.
			$message .= sprintf( __( 'Summary: %s', 'wp-document-revisions' ), $summary ) . "\n\n";
		}
		// translators: %s is the document URL.
		$message .= sprintf( __( 'View the document: %s', 'wp-document-revisions' ), get_permalink( $doc_id ) ) . "\n\n";
		// translators: %s is the blog name.
		$message .= sprintf( __( '- The %s Team', 'wp-document-revisions' ), get_bloginfo( 'name' ) );

		$this->send( $recipients, $subject, $message, $doc_id, 'new_revision' );
	}

	/**
	 * Resolves a workflow_state term ID to its name.
	 *
	 * @param int|string $term_id the term ID (empty string or 0 if none).
	 * @return string the term name, or empty string.
	 */
	private function state_name( $term_id ): string {
		if ( '' === $term_id || 0 === (int) $term_id ) {
			return '';
		}
		$term = get_term( (int) $term_id, 'workflow_state' );
		return ( $term instanceof WP_Term ) ? $term->name : '';
	}

	/**
	 * Sends the notification to each recipient.
	 *
	 * Sends one message per recipient (rather than a shared To/Cc) so the recipient
	 * list is never exposed and no spurious copy is sent to the site admin.
	 *
	 * @param string[] $recipients validated email addresses.
	 * @param string   $subject    the email subject.
	 * @param string   $message    the email body.
	 * @param int      $doc_id     the document ID.
	 * @param string   $event      the event slug.
	 * @return bool true if every message was accepted for delivery.
	 */
	private function send( array $recipients, string $subject, string $message, int $doc_id, string $event ): bool {
		/**
		 * Filters the document notification email subject.
		 *
		 * @param string $subject the email subject.
		 * @param int    $doc_id  the document ID.
		 * @param string $event   the event slug.
		 */
		$subject = apply_filters( 'document_notification_subject', $subject, $doc_id, $event );

		/**
		 * Filters the document notification email body.
		 *
		 * @param string $message the email body.
		 * @param int    $doc_id  the document ID.
		 * @param string $event   the event slug.
		 */
		$message = apply_filters( 'document_notification_message', $message, $doc_id, $event );

		/**
		 * Filters the document notification email headers.
		 *
		 * @param string[] $headers the email headers.
		 * @param int      $doc_id  the document ID.
		 * @param string   $event   the event slug.
		 */
		$headers = apply_filters( 'document_notification_headers', array(), $doc_id, $event );

		$sent = true;
		foreach ( $recipients as $recipient ) {
			$sent = wp_mail( $recipient, $subject, $message, $headers ) && $sent;
		}
		return $sent;
	}
}
