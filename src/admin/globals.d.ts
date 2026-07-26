/**
 * Ambient type declarations for the classic-admin scripts in `src/admin`.
 *
 * These are NOT compiled or shipped — they exist purely so the `// @ts-check`
 * pragma in the admin sources can type-check the PHP-injected config objects
 * (the actual goal) with editor autocomplete and typo detection, while the
 * broader WordPress/browser globals are declared loosely to avoid noise.
 * Consumed by the editor's TS language server and by `jsconfig.json`
 * (`tsc --noEmit`) if run manually; never bundled into the plugin.
 *
 * This file is intentionally a *script* (no top-level import/export) so its
 * declarations are global: `interface Window` merges with lib.dom, and the
 * `declare module` blocks below create ambient shapes for the webpack-external
 * `@wordpress/*` packages that are not installed in node_modules.
 */

// The @wordpress/* runtime packages are webpack externals; declare minimal
// module shapes for the two the admin scripts import (default export only).
declare module '@wordpress/api-fetch' {
	const apiFetch: ( options: Record< string, unknown > ) => Promise< any >;
	export default apiFetch;
}

declare module '@wordpress/dom-ready' {
	const domReady: ( callback: () => void ) => void;
	export default domReady;
}

/** The `wp_document_revisions` object localized by the classic-editor enqueue. */
interface WpdrClassicConfig {
	restoreConfirmation: string;
	lockNeedle: string;
	postUploadNotice: string;
	postDesktopNotice: string;
	uploadConfirmation: string;
	uploadErrorNotice: string;
	saveFirstNotice: string;
	lostLockNotice: string;
	lockError: string;
	lostLockNoticeTitle: string;
	lostLockNoticeLogo: string;
	uploadTitle: string;
	uploadSelect: string;
	minute: string;
	minutes: string;
	hour: string;
	hours: string;
	day: string;
	days: string;
	offset: number;
	nonce: string;
}

/** i18n strings for the AI revision-log pre-fill hint. */
interface WpdrAiPrefillI18n {
	hint?: string;
	pending?: string;
	dismiss?: string;
	review?: string;
	reviewed?: string;
}

/** The `wpdrAISummaryPrefill` object localized by the AI pre-fill enqueue. */
interface WpdrAiPrefillConfig {
	restPath: string;
	fieldId: string;
	initialDelayMs?: number;
	i18n?: WpdrAiPrefillI18n;
}

// PHP-injected config object the classic-admin script is built around.
// Declared non-optional: the script only loads when the enqueue provides it.
declare var wp_document_revisions: WpdrClassicConfig;

interface Window {
	wpDocumentRevisions?: { restBase: string };
	wpdrAISummaryPrefill?: WpdrAiPrefillConfig;
	wpdrAISummaryPrefillRun?: () => void;
	WPDocumentRevisions?: unknown;
	WPDocumentRevisionsClass?: unknown;
	autosave_enable_buttons?: () => void;
	webkitNotifications?: any;
	// Validate-structure functions exposed on window for inline onclick=.
	wpdr_valid_fix?: ( id: number, code: string, parm: number ) => Promise< void >;
	clear_line?: ( id: number, code: string ) => void;
	hide_show?: ( id: string ) => void;
}

// Standard WordPress admin globals injected by core / inline scripts.
declare var ajaxurl: string;
declare function autosave(): void;
declare function lock_override_notice( notice?: string ): void;

// Validate-structure inline-script globals.
declare var user: number;
declare var processed: string;

// The global WP JS namespace. `apiFetch`/`domReady` are imported now; only
// the broad `wp.media` API is still reached through it, so leave it `any`.
declare var wp: any;
