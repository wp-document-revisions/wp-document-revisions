/**
 * Admin-editor JS: AI revision-log pre-fill.
 *
 * Phase 12 of issue #514. On the document edit screen, after a short
 * delay (default 10s — gives the phase-11 cron a chance to fire),
 * this module fetches the cached AI summary for the document's
 * current revision via the phase-11 REST endpoint and writes it into
 * the revision-log textarea — but only when the textarea is empty,
 * so it never clobbers a value the editor has already typed.
 *
 * One-shot fetch, not polling: a `pending` response shows a small
 * note telling the editor to refresh once cron has produced the
 * summary, rather than mutating the textarea while they may be
 * typing in it.
 *
 * Localized config comes in via window.wpdrAISummaryPrefill set by
 * the matching PHP enqueue. See
 * includes/class-wp-document-revisions-ai-summary-prefill.php.
 */

// @ts-check
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const config = window.wpdrAISummaryPrefill;

function run() {
	const textarea = /** @type {HTMLTextAreaElement | null} */ (
		document.getElementById( config.fieldId )
	);
	if ( ! textarea ) {
		return;
	}
	// Respect a value the editor has already typed — never clobber.
	if ( textarea.value && '' !== textarea.value.trim() ) {
		return;
	}
	apiFetch( { path: config.restPath } )
		.then( function ( response ) {
			if ( ! response ) {
				return;
			}
			if ( 'ready' === response.status && response.summary ) {
				applyPrefill( textarea, response );
			} else if ( 'pending' === response.status ) {
				showPendingHint( textarea );
			}
			// 'unavailable' or any other status: silently noop.
			// The pre-fill is an enhancement, not a feature the
			// editor depends on being aware of.
		} )
		.catch( function () {
			// Network errors, permission denials, 404s: silent.
		} );
}

function applyPrefill( textarea, response ) {
	textarea.value = response.summary;
	addHint(
		textarea,
		__( '✨ AI suggestion — edit before saving.', 'wp-document-revisions' ),
		true,
		response
	);
}

function showPendingHint( textarea ) {
	addHint(
		textarea,
		__(
			'✨ AI summary will be available shortly — refresh this page to see it.',
			'wp-document-revisions'
		),
		false
	);
}

function addHint( textarea, message, withDismiss, response ) {
	if ( ! message || ! textarea.parentNode ) {
		return;
	}

	const hint = document.createElement( 'div' );
	hint.className = 'wpdr-ai-prefill-hint';
	hint.setAttribute( 'role', 'status' );
	hint.style.fontStyle = 'italic';
	hint.style.fontSize = '12px';
	hint.style.marginBottom = '4px';
	hint.style.color = '#646970';

	const text = document.createElement( 'span' );
	text.textContent = message;
	hint.appendChild( text );

	if ( withDismiss ) {
		const dismiss = document.createElement( 'a' );
		dismiss.href = '#';
		dismiss.className = 'wpdr-ai-prefill-dismiss';
		dismiss.style.marginLeft = '8px';
		dismiss.textContent = __( 'Dismiss', 'wp-document-revisions' );
		dismiss.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			textarea.value = '';
			if ( hint.parentNode ) {
				hint.parentNode.removeChild( hint );
			}
		} );
		hint.appendChild( dismiss );
	}

	if ( withDismiss ) {
		addReviewAction( hint, response );
	}

	textarea.parentNode.insertBefore( hint, textarea );
}

// Append a "Mark reviewed" control to a hint. POSTs to the summary
// review endpoint and, on success, swaps the link for a static
// "reviewed" label. If the summary is already reviewed, shows the
// label directly with no link.
function addReviewAction( hint, response ) {
	const reviewedLabel = __( 'Reviewed ✓', 'wp-document-revisions' );

	function showReviewed() {
		const done = document.createElement( 'span' );
		done.className = 'wpdr-ai-prefill-reviewed';
		done.style.marginLeft = '8px';
		done.textContent = reviewedLabel;
		return done;
	}

	if ( response && response.reviewed_by ) {
		hint.appendChild( showReviewed() );
		return;
	}

	const review = document.createElement( 'a' );
	review.href = '#';
	review.className = 'wpdr-ai-prefill-review';
	review.style.marginLeft = '8px';
	review.textContent = __( 'Mark reviewed', 'wp-document-revisions' );
	review.addEventListener( 'click', function ( event ) {
		event.preventDefault();
		apiFetch( {
			path: config.restPath + '/review',
			method: 'POST',
			data: { reviewed: true },
		} )
			.then( function () {
				if ( review.parentNode ) {
					review.parentNode.replaceChild( showReviewed(), review );
				}
			} )
			.catch( function () {
				// Leave the link in place so the editor can retry.
			} );
	} );
	hint.appendChild( review );
}

// Only wire anything up when the localized config is present and complete;
// otherwise the module loads as an inert no-op. (A bare early `return` here
// is not valid at ES-module top level, so the bootstrap is guarded instead.)
if ( config && config.restPath && config.fieldId ) {
	// Expose for direct invocation from Jest. Production code always
	// uses the setTimeout path below; tests call window.wpdrAISummary-
	// PrefillRun() to skip the wait.
	window.wpdrAISummaryPrefillRun = run;

	const delay = typeof config.initialDelayMs === 'number' ? config.initialDelayMs : 10000;
	if ( delay <= 0 ) {
		run();
	} else {
		window.setTimeout( run, delay );
	}
}
