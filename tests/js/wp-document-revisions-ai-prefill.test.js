/**
 * Tests for wp-document-revisions-ai-prefill.dev.js
 *
 * Phase 12 of issue #514: verifies the one-shot pre-fill behaviour,
 * the empty-textarea precondition, the pending-status fallback hint,
 * the dismiss control on a successful pre-fill, and the silent no-op
 * paths (unavailable summary, network error, missing config).
 */

const path = require('path');

const MODULE_PATH = path.resolve(
	__dirname,
	'../../js/wp-document-revisions-ai-prefill.dev.js'
);

/**
 * Set up a fresh document body with a single textarea matching the
 * field id the production code expects (`excerpt`), and configure the
 * window.wpdrAISummaryPrefill global with a zero delay so tests do
 * not have to wait on setTimeout.
 *
 * @param {object} [overrides] partial config overriding the default.
 */
function setupDom(overrides = {}) {
	document.body.innerHTML =
		'<form><textarea id="excerpt"></textarea></form>';
	window.wpdrAISummaryPrefill = Object.assign(
		{
			restPath: 'wpdr/v1/documents/1/revisions/2/summary',
			fieldId: 'excerpt',
			initialDelayMs: 0,
			i18n: {
				hint: '✨ AI suggestion — edit before saving.',
				dismiss: 'Dismiss',
				pending:
					'✨ AI summary will be available shortly — refresh this page to see it.',
			},
		},
		overrides
	);
}

/**
 * Load the module fresh after the test has set up DOM + globals.
 * Returns once the in-flight Promise from wp.apiFetch has settled,
 * so assertions see the final DOM state.
 */
async function loadModule() {
	jest.resetModules();
	require(MODULE_PATH);
	// Flush the apiFetch promise chain.
	await Promise.resolve();
	await Promise.resolve();
}

describe('wp-document-revisions-ai-prefill', () => {
	beforeEach(() => {
		jest.clearAllMocks();
		document.body.innerHTML = '';
		delete window.wpdrAISummaryPrefill;
		delete window.wpdrAISummaryPrefillRun;
		global.wp.apiFetch = jest.fn(() => Promise.resolve({}));
	});

	test('does nothing when the localized config is absent', async () => {
		// No setupDom() — config never set.
		document.body.innerHTML = '<textarea id="excerpt"></textarea>';
		await loadModule();

		expect(global.wp.apiFetch).not.toHaveBeenCalled();
		expect(document.getElementById('excerpt').value).toBe('');
	});

	test('does nothing when the textarea is absent from the page', async () => {
		setupDom();
		document.body.innerHTML = ''; // wipe DOM after config is set
		await loadModule();

		expect(global.wp.apiFetch).not.toHaveBeenCalled();
	});

	test('pre-fills the textarea when the summary is ready', async () => {
		setupDom();
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({
				status: 'ready',
				summary: 'Section 4.2 payment terms updated.',
				kind: 'change',
			})
		);

		await loadModule();

		expect(global.wp.apiFetch).toHaveBeenCalledWith({
			path: 'wpdr/v1/documents/1/revisions/2/summary',
		});
		expect(document.getElementById('excerpt').value).toBe(
			'Section 4.2 payment terms updated.'
		);

		const hint = document.querySelector('.wpdr-ai-prefill-hint');
		expect(hint).not.toBeNull();
		expect(hint.textContent).toContain('AI suggestion');
		expect(hint.querySelector('.wpdr-ai-prefill-dismiss')).not.toBeNull();
	});

	test('respects user-typed content and does not pre-fill', async () => {
		setupDom();
		document.getElementById('excerpt').value =
			'Reviewer comments already typed';
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({
				status: 'ready',
				summary: 'AI suggested text',
				kind: 'change',
			})
		);

		await loadModule();

		expect(global.wp.apiFetch).not.toHaveBeenCalled();
		expect(document.getElementById('excerpt').value).toBe(
			'Reviewer comments already typed'
		);
		expect(document.querySelector('.wpdr-ai-prefill-hint')).toBeNull();
	});

	test('shows a pending hint without pre-filling when status is pending', async () => {
		setupDom();
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({ status: 'pending' })
		);

		await loadModule();

		expect(document.getElementById('excerpt').value).toBe('');

		const hint = document.querySelector('.wpdr-ai-prefill-hint');
		expect(hint).not.toBeNull();
		expect(hint.textContent).toContain('refresh');
		// Pending hint should NOT carry a dismiss link.
		expect(hint.querySelector('.wpdr-ai-prefill-dismiss')).toBeNull();
	});

	test('silently noops on unavailable status', async () => {
		setupDom();
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({ status: 'unavailable', kind: 'unavailable' })
		);

		await loadModule();

		expect(document.getElementById('excerpt').value).toBe('');
		expect(document.querySelector('.wpdr-ai-prefill-hint')).toBeNull();
	});

	test('silently noops on a rejected promise (network / permission error)', async () => {
		setupDom();
		global.wp.apiFetch = jest.fn(() =>
			Promise.reject(new Error('boom'))
		);

		await loadModule();

		expect(document.getElementById('excerpt').value).toBe('');
		expect(document.querySelector('.wpdr-ai-prefill-hint')).toBeNull();
	});

	test('dismiss control clears the textarea and removes the hint', async () => {
		setupDom();
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({
				status: 'ready',
				summary: 'AI suggested change',
				kind: 'change',
			})
		);
		await loadModule();

		const dismiss = document.querySelector('.wpdr-ai-prefill-dismiss');
		expect(dismiss).not.toBeNull();
		dismiss.click();

		expect(document.getElementById('excerpt').value).toBe('');
		expect(document.querySelector('.wpdr-ai-prefill-hint')).toBeNull();
	});

	test('honours a positive initialDelayMs via setTimeout', async () => {
		jest.useFakeTimers();
		setupDom({ initialDelayMs: 10000 });
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({
				status: 'ready',
				summary: 'delayed prefill',
				kind: 'change',
			})
		);

		// Loading the module schedules the run on the timer; the fetch
		// must not fire before the timer advances.
		jest.resetModules();
		require(MODULE_PATH);
		expect(global.wp.apiFetch).not.toHaveBeenCalled();

		jest.advanceTimersByTime(10000);
		// Flush microtasks the fetch resolution put on the queue.
		await Promise.resolve();
		await Promise.resolve();

		expect(global.wp.apiFetch).toHaveBeenCalledTimes(1);
		expect(document.getElementById('excerpt').value).toBe(
			'delayed prefill'
		);

		jest.useRealTimers();
	});
});
