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

(function () {
	'use strict';

	const config = window.wpdrAISummaryPrefill;
	if ( ! config || ! config.restPath || ! config.fieldId ) {
		return;
	}

	function run() {
		const textarea = document.getElementById( config.fieldId );
		if ( ! textarea ) {
			return;
		}
		// Respect a value the editor has already typed — never clobber.
		if ( textarea.value && '' !== textarea.value.trim() ) {
			return;
		}
		if ( ! window.wp || ! window.wp.apiFetch ) {
			return;
		}

		window.wp.apiFetch( { path: config.restPath } )
			.then( function ( response ) {
				if ( ! response ) {
					return;
				}
				if ( 'ready' === response.status && response.summary ) {
					applyPrefill( textarea, response.summary );
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

	function applyPrefill( textarea, summary ) {
		textarea.value = summary;
		addHint( textarea, config.i18n && config.i18n.hint, true );
	}

	function showPendingHint( textarea ) {
		addHint( textarea, config.i18n && config.i18n.pending, false );
	}

	function addHint( textarea, message, withDismiss ) {
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

		if ( withDismiss && config.i18n && config.i18n.dismiss ) {
			const dismiss = document.createElement( 'a' );
			dismiss.href = '#';
			dismiss.className = 'wpdr-ai-prefill-dismiss';
			dismiss.style.marginLeft = '8px';
			dismiss.textContent = config.i18n.dismiss;
			dismiss.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				textarea.value = '';
				if ( hint.parentNode ) {
					hint.parentNode.removeChild( hint );
				}
			} );
			hint.appendChild( dismiss );
		}

		textarea.parentNode.insertBefore( hint, textarea );
	}

	// Expose for direct invocation from Jest. Production code always
	// uses the setTimeout path below; tests call window.wpdrAISummary-
	// PrefillRun() to skip the wait.
	window.wpdrAISummaryPrefillRun = run;

	const delay = typeof config.initialDelayMs === 'number'
		? config.initialDelayMs
		: 10000;
	if ( delay <= 0 ) {
		run();
	} else {
		window.setTimeout( run, delay );
	}
} )();
