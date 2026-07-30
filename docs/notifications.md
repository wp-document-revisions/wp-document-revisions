# Document Notifications

WP Document Revisions can email interested people when a document moves through your workflow — when it **changes workflow state** (for example, moved to *Under Review* or *Approved*) or when a **new revision** is saved. This turns workflow states from passive labels into an active review loop: the reviewer actually hears about it.

Notifications are **opt-in and disabled by default**, so enabling the plugin (or upgrading) never starts sending mail on its own.

## Enabling notifications

Notifications are configured under **Settings → Media**, in the **Document Notifications** section:

- **Email notifications when documents change** — the master switch. Off by default; nothing is sent until you turn it on.
- **Notify these email addresses** — a site-wide recipient list (one address per line, or comma-separated).
- **Notify when a document changes workflow state** — on by default (only takes effect once the master switch is on).
- **Notify when a new revision of a document is saved** — off by default.

## Who gets notified

For each event, the recipients are:

- everyone on the site-wide recipient list, **plus**
- the document's author,

with two rules always applied:

- **the person who made the change is never notified of their own change**, and
- duplicate and invalid addresses are removed.

Each recipient receives their own copy — the recipient list is never exposed to the others, and no stray copy is sent to the site administrator.

> **Note on bulk edits:** because a notification is sent per document, changing the workflow state of many documents at once (for example, a bulk edit) sends one notification per document. Use the `document_notification_recipients` filter to suppress or reroute mail in that situation if needed.

## Customizing with filters

Every part of the behavior is filterable. See [Filters](filters.md) for full signatures. In brief:

- `document_notify_enabled`, `document_notify_on_state_change`, `document_notify_on_new_revision` — force-enable or force-disable the master switch and each event.
- `document_notification_recipients` — receives `( array $emails, int $doc_id, string $event, int $actor_id )`. This is the seam for advanced routing: notify prior editors, or route by workflow state (e.g. send *Under Review* transitions to a review team).
- `document_notification_subject`, `document_notification_message` — customize the email subject and body.
- `document_notification_headers` — add email headers (e.g. a `From:` or `Reply-To:`).

### Example: route "Under Review" to a review team

```php
add_filter(
	'document_notification_recipients',
	function ( $emails, $doc_id, $event, $actor_id ) {
		if ( 'state_change' !== $event ) {
			return $emails;
		}
		$states = wp_get_object_terms( $doc_id, 'workflow_state', array( 'fields' => 'names' ) );
		if ( in_array( 'Under Review', $states, true ) ) {
			$emails[] = 'review-team@example.com';
		}
		return $emails;
	},
	10,
	4
);
```

## Delivery

Notifications are sent through WordPress's standard `wp_mail()`. On sites with high mail volume or a slow mail server, install a transactional-email/SMTP plugin so delivery does not add latency to saving a document.
