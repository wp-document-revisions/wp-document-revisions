// @ts-check
/**
 * Shared shortcode helpers for the block "from/to shortcode" transforms.
 *
 * Tokenizing and serializing are delegated to `@wordpress/shortcode`, which
 * follows the same attribute grammar as PHP's shortcode_parse_atts(), so
 * quoted values containing spaces survive the round trip.
 */
import { attrs, string } from '@wordpress/shortcode';

/**
 * Parse a raw `[shortcode ...]` string into its lowercased parameter pairs.
 *
 * Strips the enclosing brackets and the leading tag name and hands the rest
 * to `@wordpress/shortcode`'s attrs(). Named attributes come back as
 * `[key, value]` pairs (in source order), followed by bare flags as
 * single-element `[key]` pairs, so callers can distinguish `show_pdf` from
 * `show_pdf=true` via `pair.length`.
 *
 * @param {string} text Raw shortcode text, e.g. `[documents numberposts="3"]`.
 * @return {string[][]} Array of `[key]` or `[key, value]` pairs.
 */
export function parseShortcodeParams( text ) {
	const inner = text
		.toLowerCase()
		.trim()
		.replace( /^\[/, '' )
		.replace( /\]$/, '' )
		// Drop the tag name (first token).
		.replace( /^\s*[^\s\]/]+/, '' );

	const { named, numeric } = attrs( inner );
	return [
		...Object.entries( named ).map( ( [ key, value ] ) => [ key, value ] ),
		...numeric.map( ( flag ) => [ flag ] ),
	];
}

/**
 * Serialize a self-closing shortcode.
 *
 * @param {string}                 tag     Shortcode tag.
 * @param {Record<string, string>} named   Named attributes (values are quoted on output).
 * @param {string[]}               numeric Bare flags.
 * @return {string} The shortcode text, e.g. `[documents show_pdf numberposts="5"]`.
 */
export function buildShortcode( tag, named = {}, numeric = [] ) {
	return string( { tag, type: 'single', attrs: { named, numeric } } );
}

/**
 * Parse a free-form attribute string (as typed into the block's "additional
 * parameters" field) into named attributes and flags.
 *
 * @param {string} text Attribute text, e.g. `author="3" suppress_filters`.
 * @return {{ named: Record<string, string>, numeric: string[] }} Parsed attributes.
 */
export function parseAttrs( text ) {
	return attrs( text || '' );
}
