import { createBlock, registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import Edit from './edit';

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
					// prepare text string.
					let iput = text.toLowerCase();
					if ( iput.indexOf( '[' ) === 0 ) {
						iput = iput.slice( 1, iput.length - 1 );
					}
					const args = iput.split( ' ' );
					args.shift();

					// defaults.
					let sid = 1;
					let snumberposts = 5;
					let ssummary = false;
					let sshow_pdf = false;
					let snew_tab = true;
					for ( const arg of args ) {
						if ( arg.length === 0 ) {
							continue;
						}
						const parm = arg.split( '=' );
						if (
							parm.length > 1 &&
							( parm[ 1 ].indexOf( "'" ) === 0 ||
								parm[ 1 ].indexOf( '"' ) === 0 )
						) {
							parm[ 1 ] = parm[ 1 ].slice(
								1,
								parm[ 1 ].length - 1
							);
						}
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
							if (
								parm.length === 1 ||
								parm[ 1 ] === 'true'
							) {
								ssummary = true;
							}
						}
						if ( parm[ 0 ] === 'show_pdf' ) {
							if (
								parm.length === 1 ||
								parm[ 1 ] === 'true'
							) {
								sshow_pdf = true;
							}
						}
						if ( parm[ 0 ] === 'new_tab' ) {
							if (
								parm.length === 1 ||
								parm[ 1 ] === 'false'
							) {
								snew_tab = false;
							}
						}
					}

					return createBlock(
						'wp-document-revisions/revisions-shortcode',
						{
							id: sid,
							numberposts: snumberposts,
							summary: ssummary,
							show_pdf: sshow_pdf,
							new_tab: snew_tab,
						}
					);
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
