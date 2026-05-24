# Text Extraction & AI Summaries

## Overview

WP Document Revisions extracts the plain text of each uploaded file (PDF, DOCX, ODT, etc.) into a per-attachment cache, then optionally generates a 1–3 sentence AI summary of what changed in each new revision. When a revision is uploaded, the summary is pre-filled into the revision log on the document edit screen for the editor to review before saving.

This recipe covers:

- How the pipeline fits together (extraction → diff → summary → pre-fill)
- How to register a custom extractor for a new file format
- How to customize the AI summary prompt
- How to disable extraction or AI globally / per-document
- How to use the WP-CLI backfill command for an existing library
- How to plug in an alternative AI provider (or mock the AI in tests)
- How to read the cached summary from REST

> Shipped in phases 1–12 of [issue #514](https://github.com/wp-document-revisions/wp-document-revisions/issues/514). All the hooks and constants below are public API and stable across point releases.

## Prerequisites

- WP Document Revisions 5.0.0 or later
- PHP 7.4 or later (PHP 8.3+ recommended)
- WordPress 5.0 or later for extraction; **WordPress 7.0 or later** for AI summary generation (uses the core AI Client). The pre-fill UI is harmless on older WordPress — it simply finds no summaries to show.
- One of the [WordPress AI provider connectors](https://wordpress.org/plugins/search/ai+provider/) (Anthropic, OpenAI, or Google) configured under **Settings → Connectors**, for summary generation. Without a provider, extraction still runs and the cache populates.

## How the pipeline fits together

```
┌────────────────────┐    add_attachment    ┌─────────────────────────┐
│ Editor uploads     │ ───────────────────▶ │ Extractor scheduler     │
│ a new revision     │                      │ (wp-cron, ~10s delay)   │
└────────────────────┘                      └────────────┬────────────┘
                                                         │
                                                         ▼
                                            ┌─────────────────────────┐
                                            │ Registered extractor    │
                                            │ (PDF / DOCX / custom)   │
                                            └────────────┬────────────┘
                                                         │ extracted text
                                                         ▼
                                            ┌─────────────────────────┐
                  fires wpdr_text_extracted │ Hash-keyed text cache   │
                ◀───────────────────────────│ on the attachment post  │
                │                           └─────────────────────────┘
                ▼
   ┌─────────────────────────┐
   │ AI summary scheduler    │     diff prior vs new
   │ (wp-cron, ~10s delay)   │ ─────────────────────▶ ┌────────────────────┐
   └────────────┬────────────┘                        │ wp_ai_client_prompt│
                │                                     │ ()->generate_text()│
                │  cached summary on attachment       └────────────────────┘
                ▼
   ┌─────────────────────────┐    GET /wpdr/v1/...    ┌────────────────────┐
   │ REST endpoint           │ ◀───────────────────── │ Admin-editor JS    │
   │ (read_document gated)   │                        │ pre-fills excerpt  │
   └─────────────────────────┘                        └────────────────────┘
```

Each stage stores its output in post meta on the **attachment post** (not on the document or post-revision record), so multiple post-revisions that point at the same attachment share the same extracted text and summary without re-running the work.

The same meta keys are removed whenever the per-document opt-out is toggled on, or whenever the WP-CLI `--force` flag is used to re-extract.

---

## Recipe 1: Register a custom extractor

The plugin walks an ordered list of `WP_Document_Revisions_Text_Extractor` implementations and uses the first one whose `supports()` returns true for the file's MIME type. To add support for a new format, ship a class that implements the interface and register it via the `wpdr_text_extractors` filter.

### Example: CSV extractor

```php
<?php
/**
 * CSV extractor for WP Document Revisions.
 *
 * Plugin Name: WPDR CSV Text Extractor
 */

defined( 'ABSPATH' ) || exit;

class My_CSV_Text_Extractor implements WP_Document_Revisions_Text_Extractor {

	/**
	 * Bump when the extraction logic changes — used as the cache
	 * "identity" key so a WP-CLI re-run can target old output.
	 */
	const VERSION = '1.0.0';

	public function supports( string $mime_type ): bool {
		return in_array( $mime_type, array( 'text/csv', 'application/csv' ), true );
	}

	public function extract( string $file_path, string $mime_type ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$body = file_get_contents( $file_path );
		if ( false === $body ) {
			return '';
		}

		// Tab-separate columns so downstream tools can distinguish them.
		$rows = array();
		foreach ( str_getcsv( $body, "\n" ) as $line ) {
			$cells  = str_getcsv( $line );
			$rows[] = implode( "\t", $cells );
		}
		return implode( "\n", $rows );
	}
}

add_filter(
	'wpdr_text_extractors',
	static function ( array $extractors ): array {
		// `array_unshift` to take precedence over any built-in or
		// later-registered extractors that also claim text/csv.
		array_unshift( $extractors, new My_CSV_Text_Extractor() );
		return $extractors;
	}
);
```

### What the contract guarantees

- `extract()` runs **out of band** (wp-cron, default ~10s after upload). Long-running work won't block the editor's check-in request.
- `extract()` should return `''` for files it can't read (malformed, encrypted, unsupported) rather than throwing.
- If `extract()` **does** throw, the scheduler records the file's SHA-256 on a "failure list" and refuses to retry against the same content until the file is replaced. The `--force` flag on the WP-CLI command bypasses this.
- The `VERSION` constant is optional. If present, it's appended to the extractor identity (`My_CSV_Text_Extractor@1.0.0`) and stored alongside the cached text — so a future WP-CLI reprocess can target everything produced by an outdated tool with `--extractor=My_CSV_Text_Extractor@1.0.0`.

### Hard timeout, in seconds

Cap how long any single extraction can run via `wpdr_text_extraction_timeout` (default 30s). Useful when your extractor calls a slow external library:

```php
add_filter( 'wpdr_text_extraction_timeout', static function (): int {
	return 120;
} );
```

---

## Recipe 2: Customize the AI summary prompt

The default system prompt asks for "a 1–3 sentence summary describing what changed." Override it via the `wpdr_ai_summary_prompt` filter:

```php
add_filter(
	'wpdr_ai_summary_prompt',
	static function ( string $default, string $kind, int $attachment_id ): string {
		// Two prompt shapes: 'change' (we have a diff) and 'document'
		// (initial upload, or diff was too large / empty).
		if ( 'change' === $kind ) {
			return <<<PROMPT
You are summarising a change to a legal contract.
Given the following unified diff between two revisions of the
document, write a 1–2 sentence summary describing the substantive
legal change in plain language for a non-lawyer.

Focus on:
- monetary amounts that changed
- liability or indemnity terms that changed
- dates or deadlines that changed

Do NOT narrate the diff format itself.
PROMPT;
		}

		// 'document' kind — initial upload or fallback path.
		return <<<PROMPT
You are summarising a legal contract. Write a 1–2 sentence summary
describing the document's purpose, the parties, and any
unusual terms a reviewer should be aware of.
PROMPT;
	},
	10,
	3
);
```

### Why the filter doesn't pass the input text

The filter signature is `(string $default, string $kind, int $attachment_id)` — deliberately **without** the input. Your filter returns the *template* (system prompt); the plugin concatenates the input text after it. This makes it impossible to accidentally prepend rather than replace the template (a common bug class with text-prefixing filters).

To see the input, fetch the extracted text yourself via `wpdr_extract_text( $attachment_id )`.

### Other AI-related knobs

- `wpdr_ai_summary_delay` — seconds between text extraction completing and the summary cron firing. Default 10. Filter to delay longer on busy sites.
- `wpdr_ai_summary_timeout` — per-call timeout in seconds (advisory). Default 60.

---

## Recipe 3: Disable extraction or AI

There are four orthogonal switches. Pick the smallest one that fits your need.

| Concern | Mechanism | Scope |
| --- | --- | --- |
| Stop extraction altogether | `define( 'WPDR_TEXT_EXTRACTION', false );` in `wp-config.php` | Sitewide |
| Stop extraction for one document | "Skip text extraction" checkbox on the document edit screen | One document |
| Stop AI summary pre-fill (keep extraction for search) | `define( 'WPDR_AI_SUMMARY_PREFILL', false );` | Sitewide |
| Stop pre-fill for one document | "Do not pre-fill the revision log" checkbox on the document edit screen | One document |
| Stop AI summary generation entirely | `define( 'WP_AI_SUPPORT', false );` (the WordPress core kill switch) | Sitewide |

When the extraction opt-out is flipped on for a document, the plugin clears every cache-managed meta key on the document's revision attachments (`_wpdr_extracted_text`, `_wpdr_extracted_text_hash`, `_wpdr_extracted_text_extractor`, `_wpdr_extraction_failed`) and un-schedules any pending extraction cron events.

The pre-fill opt-out is UI-only — it doesn't touch any cached data, just stops the editor JS from writing into the revision log field.

---

## Recipe 4: Backfill an existing library with WP-CLI

Newly uploaded revisions extract automatically. For documents that pre-date the extraction feature (or that you opted out and later opted back in), use the bundled WP-CLI command:

```bash
# Process every document in the library.
wp document-revisions extract-text --all

# Only revisions that have no cached text and are not on the failure list.
wp document-revisions extract-text --missing

# A single document by ID.
wp document-revisions extract-text --id=42

# Re-process everything produced by an outdated extractor version.
wp document-revisions extract-text --all --extractor=My_CSV_Text_Extractor@1.0.0 --force

# See what would happen, without writing anything.
wp document-revisions extract-text --all --dry-run
```

Flag semantics:

- One of `--all`, `--missing`, or `--id=<id>` is **required**. `--id` wins when combined with the others.
- `--missing` deliberately excludes attachments on the failure list. `--force` is the documented escape hatch to retry them.
- `--extractor=<substring>` filters to attachments whose recorded extractor identity contains the substring. Useful patterns: `--extractor=PDF_Text_Extractor` (any version), `--extractor=@1.0.0` (any extractor at v1.0.0).
- `--force` re-extracts even when cached text is already present, and bypasses the failure-list filter.
- `--dry-run` logs the intended action for each attachment without invoking any extractor or writing meta.

The command paginates documents in batches of 100. Per-document opt-outs are honoured — opted-out documents log as `skipped` without enumerating their attachments.

---

## Recipe 5: Plug in an alternative AI provider (or mock the AI)

By default, the summary generator calls WordPress 7.0's `wp_ai_client_prompt( $prompt )->generate_text()`. To route to a different SDK — or to short-circuit for testing — intercept via the `wpdr_ai_summary_generator` filter:

```php
add_filter(
	'wpdr_ai_summary_generator',
	static function ( $ignored_default, string $prompt ) {
		// Return a string to short-circuit; return null to defer to the
		// real WP AI Client (the default path).
		$response = my_custom_provider_call( $prompt );
		if ( is_wp_error( $response ) ) {
			return null; // fall back to wp_ai_client_prompt() if available
		}
		return (string) $response;
	},
	10,
	2
);
```

Pair this with `wpdr_ai_summary_available` to force the pipeline on even when `wp_ai_client_prompt()` is missing:

```php
add_filter( 'wpdr_ai_summary_available', '__return_true' );
```

This is the same mechanism the plugin's own unit tests use to exercise the generation pipeline without a live provider.

---

## Recipe 6: Read the cached summary from REST

The summary is exposed read-only at:

```
GET /wp-json/wpdr/v1/documents/<doc_id>/revisions/<revision_id>/summary
```

Capability required: `read_document` on the document. The response envelope:

```json
{
  "status": "ready" | "pending" | "unavailable",
  "kind":   "change" | "document" | "no_change" | "unavailable",
  "summary": "Section 4.2 payment terms changed from net-30 to net-60.",
  "generated_at": 1715534400,
  "reviewed_by":  3,
  "reviewed_at":  1715537000
}
```

- `pending` — the cron hasn't yet produced a summary. The UI should retry after a refresh.
- `ready` — `summary`, `kind`, and the review metadata are populated.
- `unavailable` — generation was attempted but the AI Client was unreachable, or the revision has no extractable text.

To mark a summary as human-reviewed:

```
POST /wp-json/wpdr/v1/documents/<doc_id>/revisions/<revision_id>/summary/review
Body: { "reviewed": true }
```

Capability required: `edit_document`. Pass `"reviewed": false` to un-mark. The response returns the current `reviewed_by` / `reviewed_at` values.

### Capability mapping at a glance (h/t [@NeilWJames](https://github.com/NeilWJames))

| Operation | Capability | Reasoning |
| --- | --- | --- |
| Extraction itself | (none — server-side cron) | Same content is reachable via WP-CLI backfill; no per-request gate |
| Read the current summary | `read_document` | Same gate as reading the document file |
| Read per-revision diff records | `read_document_revisions` | Diffs reveal history across revisions, a stronger disclosure than current state. *(Not yet exposed over REST as of phase 12.)* |
| Mark a summary as human-reviewed | `edit_document` | Writes meta on the revision attachment |

---

## Troubleshooting

**The summary never appears.** Check `wp cron event list` — there should be a `wpdr_extract_text_async` event and (after extraction completes) a `wpdr_generate_ai_summary` event for the attachment ID. If they're missing, the document may be opted out of extraction. If they're present but never running, your site's wp-cron may be disabled (`DISABLE_WP_CRON`).

**The summary endpoint returns `unavailable`.** Either the AI Client is not loaded (WordPress < 7.0), `WP_AI_SUPPORT` is false, no provider connector is configured, or the new revision's extractor returned empty (scanned PDF without OCR, for example). Check the attachment's `_wpdr_extracted_text` meta to distinguish.

**The pre-fill replaces text I'm typing.** It shouldn't — the JS bails before fetching if the textarea already has content. If you see this, the page may have loaded with an empty textarea, the AI fetch fired, and then you typed before the response arrived. Per-document opt-out (the "Do not pre-fill" checkbox) suppresses the fetch entirely.

**A specific extractor's output is wrong.** Bump its `VERSION` constant and run `wp document-revisions extract-text --all --extractor=<class>@<old_version> --force`.

---

## Related

- [Filters reference](../filters.md) — full list of hooks, including the ten introduced by this feature.
- [Actions reference](../actions.md) — including the new `wpdr_text_extracted` action.
- WordPress 7.0 AI Client: [Introducing the AI Client in WordPress 7.0](https://make.wordpress.org/core/2026/03/24/introducing-the-ai-client-in-wordpress-7-0/)
- Original design discussion: [issue #514](https://github.com/wp-document-revisions/wp-document-revisions/issues/514)
