// @ts-check
/**
 * Shared shortcode tokenizer for the block "from shortcode" transforms.
 *
 * The `[documents]` and `[document_revisions]` blocks both need to turn a raw
 * shortcode string into its parameter pairs before mapping them onto block
 * attributes. The tokenizing rules are identical between them, so they live
 * here rather than being duplicated in each block's transform.
 */

/**
 * Parse a raw `[shortcode ...]` string into its lowercased parameter pairs.
 *
 * Strips the enclosing brackets and the leading tag name, splits the remaining
 * space-separated tokens on `=`, and removes a matching pair of surrounding
 * single or double quotes from each value. A bare flag (no `=`) yields a
 * single-element `[key]` pair, so callers can distinguish `show_pdf` from
 * `show_pdf=true` via `pair.length`.
 *
 * Empty tokens (from doubled spaces) are skipped.
 *
 * @param {string} text Raw shortcode text, e.g. `[documents numberposts="3"]`.
 * @return {string[][]} Array of `[key]` or `[key, value]` pairs.
 */
export function parseShortcodeParams( text ) {
	let iput = text.toLowerCase();
	if ( iput.indexOf( '[' ) === 0 ) {
		iput = iput.slice( 1, iput.length - 1 );
	}
	const args = iput.split( ' ' );
	// Drop the tag name (first token).
	args.shift();

	const params = [];
	for ( const arg of args ) {
		if ( arg.length === 0 ) {
			continue;
		}
		const parm = arg.split( '=' );
		if (
			parm.length > 1 &&
			( parm[ 1 ].indexOf( "'" ) === 0 || parm[ 1 ].indexOf( '"' ) === 0 )
		) {
			parm[ 1 ] = parm[ 1 ].slice( 1, parm[ 1 ].length - 1 );
		}
		params.push( parm );
	}
	return params;
}
