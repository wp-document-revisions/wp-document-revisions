// @ts-check
import { createBlock, registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import { buildShortcode, parseShortcodeParams } from '../shared/parse-shortcode';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				isMatch: ( { text } ) => {
					return /^\[?document_revisions\b\s*/.test( text );
				},
				transform: ( { text } ) => {
					// Tokenize the raw shortcode into parameter pairs.
					const params = parseShortcodeParams( text );

					// defaults.
					let sid = 1;
					let snumberposts = 5;
					let ssummary = false;
					let sshow_pdf = false;
					let snew_tab = true;
					for ( const parm of params ) {
						if ( parm[ 0 ] === 'id' ) {
							sid = Number( parm[ 1 ] );
						}
						if ( parm[ 0 ] === 'number' ) {
							snumberposts = Number( parm[ 1 ] );
						}
						if ( parm[ 0 ] === 'numberposts' ) {
							snumberposts = Number( parm[ 1 ] );
						}
						if ( parm[ 0 ] === 'summary' ) {
							if ( parm.length === 1 || parm[ 1 ] === 'true' ) {
								ssummary = true;
							}
						}
						if ( parm[ 0 ] === 'show_pdf' ) {
							if ( parm.length === 1 || parm[ 1 ] === 'true' ) {
								sshow_pdf = true;
							}
						}
						if ( parm[ 0 ] === 'new_tab' ) {
							if ( parm.length === 1 || parm[ 1 ] === 'false' ) {
								snew_tab = false;
							}
						}
					}

					return createBlock( 'wp-document-revisions/revisions-shortcode', {
						id: sid,
						numberposts: snumberposts,
						summary: ssummary,
						show_pdf: sshow_pdf,
						new_tab: snew_tab,
					} );
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				transform: ( attributes ) => {
					/** @type {Record<string, string>} */
					const named = {};
					const flags = [];
					if ( '' !== attributes.id ) {
						named.id = String( attributes.id );
					}
					if ( '' !== attributes.numberposts ) {
						named.numberposts = String( attributes.numberposts );
					}
					named.summary = attributes.summary ? 'true' : 'false';
					if ( attributes.show_pdf ) {
						flags.push( 'show_pdf' );
					}
					named.new_tab = attributes.new_tab ? 'true' : 'false';
					const content = buildShortcode( 'document_revisions', named, flags );
					return createBlock( 'core/shortcode', {
						text: content,
					} );
				},
			},
		],
	},
} );
