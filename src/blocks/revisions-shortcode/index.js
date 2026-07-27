// @ts-check
import { createBlock, registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import { parseShortcodeParams } from '../shared/parse-shortcode';

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
					let content = '[document_revisions ';
					if ( '' !== attributes.id ) {
						content += `id=${ attributes.id }`;
					}
					if ( '' !== attributes.numberposts ) {
						content += ` numberposts=${ attributes.numberposts }`;
					}
					if ( ! attributes.summary ) {
						content += ' summary=false';
					} else {
						content += ' summary=true';
					}
					if ( attributes.show_pdf ) {
						content += ' show_pdf';
					}
					if ( ! attributes.new_tab ) {
						content += ' new_tab=false ]';
					} else {
						content += ' new_tab=true ]';
					}
					return createBlock( 'core/shortcode', {
						text: content,
					} );
				},
			},
		],
	},
} );
