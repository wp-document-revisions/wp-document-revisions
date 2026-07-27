/**
 * Ambient type declarations for the block sources in `src/blocks`.
 *
 * Like `src/admin/globals.d.ts`, this is a *script* (no import/export) so its
 * declarations are global. It types the PHP-injected `wpdr_data` object that
 * the "from/to shortcode" transforms and edit panels read, and provides a
 * minimal shape for the webpack-external `@wordpress/blocks` package the
 * `// @ts-check`'d transform files import. Not compiled or shipped.
 */

/** A single custom taxonomy exposed to the blocks, e.g. workflow_state. */
interface WpdrTaxonomy {
	/** The query-var / shortcode key, e.g. `workflow_state`. */
	query: string;
	/** Human-readable taxonomy label. */
	label: string;
	/** Terms as `[ id, label, slug ]` tuples. */
	terms: Array< [ number, string, string ] >;
}

/** The `wpdr_data` object localized for the block editor. */
interface WpdrData {
	/** Number of custom taxonomies exposed (0–3). */
	stmax: number;
	/** The taxonomies themselves. */
	taxos: WpdrTaxonomy[];
	/** '1' when the workflow-state / extended file-permissions feature is on. */
	wf_efpp: string;
}

// eslint-disable-next-line no-var
declare var wpdr_data: WpdrData;

declare module '@wordpress/blocks' {
	export function createBlock(
		name: string,
		attributes?: Record< string, unknown >
	): unknown;
	export function registerBlockType(
		metadata: unknown,
		settings: Record< string, unknown >
	): unknown;
}
