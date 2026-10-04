// @ts-check
/**
 * Conversion between the Document Library block's attributes and the
 * [document_library] shortcode.
 */
import { buildShortcode, parseShortcodeParams } from '../shared/parse-shortcode';

/** Shortcode keys that are block attributes rather than taxonomy filters. */
const ATTRIBUTE_KEYS = [
	'variant',
	'columns',
	'fields',
	'orderby',
	'order',
	'numberposts',
	'new_tab',
];

/**
 * Block attributes from shortcode text.
 *
 * Any key that isn't a block attribute is treated as a taxonomy filter with a
 * comma-separated list of term IDs or slugs, matching what the PHP shortcode
 * accepts. Only all-digit values become IDs, so a slug such as `2024-reports`
 * stays a slug rather than turning into term 2024.
 *
 * @param {string} text Raw shortcode text, e.g. `[document_library variant="table"]`.
 * @return {Record<string, unknown>} Block attributes.
 */
export function shortcodeToAttributes( text ) {
	/** @type {Record<string, unknown>} */
	const attributes = {};
	/** @type {Record<string, Array<number|string>>} */
	const taxonomies = {};

	for ( const [ key, value ] of parseShortcodeParams( text ) ) {
		if ( key === 'new_tab' ) {
			attributes.new_tab = value === undefined || [ '1', 'true', 'yes' ].includes( value );
		} else if ( value === undefined ) {
			continue;
		} else if ( key === 'fields' ) {
			attributes.fields = value
				.split( ',' )
				.map( ( field ) => field.trim() )
				.filter( Boolean );
		} else if ( key === 'columns' || key === 'numberposts' ) {
			const number = parseInt( value, 10 );
			if ( ! Number.isNaN( number ) ) {
				attributes[ key ] = number;
			}
		} else if ( key === 'order' ) {
			attributes.order = value.toUpperCase() === 'ASC' ? 'ASC' : 'DESC';
		} else if ( ATTRIBUTE_KEYS.includes( key ) ) {
			attributes[ key ] = value;
		} else {
			const terms = value
				.split( ',' )
				.map( ( term ) => term.trim() )
				.filter( Boolean )
				.map( ( term ) => ( /^\d+$/.test( term ) ? Number( term ) : term ) );
			if ( terms.length ) {
				taxonomies[ key ] = terms;
			}
		}
	}

	if ( Object.keys( taxonomies ).length ) {
		attributes.taxonomies = taxonomies;
	}
	return attributes;
}

/**
 * Shortcode text from block attributes.
 *
 * @param {{variant?: string, columns?: number, fields?: string[], taxonomies?: Record<string, Array<number|string>>, orderby?: string, order?: string, numberposts?: number, new_tab?: boolean}} attributes Block attributes.
 * @return {string} The shortcode.
 */
export function attributesToShortcode( attributes ) {
	/** @type {Record<string, string>} */
	const named = {
		variant: String( attributes.variant || 'list' ),
	};
	if ( attributes.variant === 'grid' ) {
		named.columns = String( attributes.columns );
	}
	if ( Array.isArray( attributes.fields ) && attributes.fields.length ) {
		named.fields = attributes.fields.join( ',' );
	}
	for ( const [ taxonomy, terms ] of Object.entries( attributes.taxonomies || {} ) ) {
		if ( Array.isArray( terms ) && terms.length ) {
			named[ taxonomy ] = terms.join( ',' );
		}
	}
	named.orderby = String( attributes.orderby || 'modified' );
	named.order = String( attributes.order || 'DESC' );
	named.numberposts = String( attributes.numberposts );
	if ( attributes.new_tab ) {
		named.new_tab = 'true';
	}
	return buildShortcode( 'document_library', named );
}
