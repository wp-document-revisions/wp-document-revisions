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
					return /^\[?documents\b\s*/.test( text );
				},
				transform: ( { text } ) => {
					// defaults.
					let sheader = '';
					let staxonomy_0 = '';
					let sterm_0 = 0;
					let staxonomy_1 = '';
					let sterm_1 = 0;
					let staxonomy_2 = '';
					let sterm_2 = 0;
					let snumberposts = 5;
					let sorderby = '';
					let sorder = '';
					let sshow_edit = '';
					let sshow_thumb = false;
					let sshow_descr = true;
					let sshow_pdf = false;
					let snew_tab = true;
					let sfreeform = '';

					// prepare text string.
					let iput = text.toLowerCase();
					if ( iput.indexOf( '[' ) === 0 ) {
						iput = iput.slice( 1, iput.length - 1 );
					}
					const args = iput.split( ' ' );
					args.shift();

					const taxo = wpdr_data.taxos;
					const wf_efpp = wpdr_data.wf_efpp;

					function slug_to_id( n, val ) {
						const terms = taxo[ n ].terms;
						const alt_val = val.replace( /_/g, '-' );
						for ( let j = 0; j < terms.length; j++ ) {
							if (
								val === terms[ j ][ 2 ] ||
								alt_val === terms[ j ][ 2 ]
							) {
								return terms[ j ][ 0 ];
							}
						}
						return 0;
					}

					for ( const i of args ) {
						if ( i.length === 0 ) {
							continue;
						}
						let used = false;
						const parm = i.split( '=' );
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
						// existing parameter may be wf_state - convert to post_status.
						if (
							wf_efpp === '1' &&
							parm[ 0 ] === 'workflow_state'
						) {
							parm[ 0 ] = 'post_status';
						}
						if (
							wpdr_data.stmax > 0 &&
							parm[ 0 ] === taxo[ 0 ].query
						) {
							staxonomy_0 = parm[ 0 ];
							sterm_0 = slug_to_id( 0, parm[ 1 ] );
							used = true;
						}
						if (
							wpdr_data.stmax > 1 &&
							parm[ 0 ] === taxo[ 1 ].query
						) {
							staxonomy_1 = parm[ 0 ];
							sterm_1 = slug_to_id( 1, parm[ 1 ] );
							used = true;
						}
						if (
							wpdr_data.stmax > 2 &&
							parm[ 0 ] === taxo[ 2 ].query
						) {
							staxonomy_2 = parm[ 0 ];
							sterm_2 = slug_to_id( 2, parm[ 1 ] );
							used = true;
						}
						if ( parm[ 0 ] === 'number' ) {
							snumberposts = Number( parm[ 1 ] );
							used = true;
						}
						if ( parm[ 0 ] === 'numberposts' ) {
							snumberposts = Number( parm[ 1 ] );
							used = true;
						}
						if ( parm[ 0 ] === 'orderby' ) {
							sorderby = parm[ 1 ];
							used = true;
						}
						if ( parm[ 0 ] === 'order' ) {
							sorder = parm[ 1 ].toUpperCase(); // Upper case needed.
							used = true;
						}
						if ( parm[ 0 ] === 'show_edit' ) {
							sshow_edit = parm[ 1 ];
							used = true;
						}
						if ( parm[ 0 ] === 'show_thumb' ) {
							if (
								parm.length === 1 ||
								parm[ 1 ] === 'true'
							) {
								sshow_thumb = true;
							}
							used = true;
						}
						if ( parm[ 0 ] === 'show_descr' ) {
							if (
								parm.length === 2 &&
								parm[ 1 ] === 'false'
							) {
								sshow_descr = false;
							}
							used = true;
						}
						if ( parm[ 0 ] === 'show_pdf' ) {
							if (
								parm.length === 1 ||
								parm[ 1 ] === 'true'
							) {
								sshow_pdf = true;
							}
							used = true;
						}
						if ( parm[ 0 ] === 'new_tab' ) {
							if (
								parm.length === 2 &&
								parm[ 1 ] === 'false'
							) {
								snew_tab = false;
							}
							used = true;
						}
						if ( ! used ) {
							// other parameter, add to freeform one.
							sfreeform += ` ${ i }`;
						}
					}

					return createBlock(
						'wp-document-revisions/documents-shortcode',
						{
							header: sheader,
							taxonomy_0: staxonomy_0,
							term_0: sterm_0,
							taxonomy_1: staxonomy_1,
							term_1: sterm_1,
							taxonomy_2: staxonomy_2,
							term_2: sterm_2,
							numberposts: snumberposts,
							orderby: sorderby,
							order: sorder,
							show_edit: sshow_edit,
							show_thumb: sshow_thumb,
							show_descr: sshow_descr,
							show_pdf: sshow_pdf,
							new_tab: snew_tab,
							freeform: sfreeform.trim(),
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
					const taxo = wpdr_data.taxos;

					function id_to_slug( n, val ) {
						const terms = taxo[ n ].terms;
						for ( let j = 0; j < terms.length; j++ ) {
							if ( val === terms[ j ][ 0 ] ) {
								return `"${ terms[ j ][ 2 ] }"`;
							}
						}
						return '??';
					}

					function decode_taxo( tax, val ) {
						if ( '' !== tax && 0 !== val ) {
							for ( const i of [ 0, 1, 2 ] ) {
								if ( tax === taxo[ i ].query ) {
									content += ` ${ tax }=${ id_to_slug(
										i,
										val
									) }`;
									return;
								}
							}
							content += ` ${ tax }=${ val }`;
						}
						return;
					}

					let content = '[documents ';
					decode_taxo(
						attributes.taxonomy_0,
						attributes.term_0
					);
					decode_taxo(
						attributes.taxonomy_1,
						attributes.term_1
					);
					decode_taxo(
						attributes.taxonomy_2,
						attributes.term_2
					);
					if ( '' !== attributes.numberposts ) {
						content += ` numberposts="${ attributes.numberposts }"`;
					}
					if (
						undefined !== attributes.orderby &&
						'' !== attributes.orderby
					) {
						content += ` orderby="${ attributes.orderby }"`;
					}
					if (
						'' !== attributes.order &&
						undefined !== attributes.orderby &&
						'' !== attributes.orderby
					) {
						content += ` order="${ attributes.order }"`;
					}
					if ( '' !== attributes.show_edit ) {
						content += ` show_edit="${ attributes.show_edit }"`;
					}
					if ( attributes.show_thumb ) {
						content += ' show_thumb';
					}
					if ( attributes.show_descr ) {
						content += ' show_descr';
					}
					if ( attributes.show_pdf ) {
						content += ' show_pdf';
					}
					if ( attributes.new_tab ) {
						content += ' new_tab';
					}
					if (
						'' !== attributes.freeform &&
						undefined !== attributes.freeform
					) {
						content += ` ${ attributes.freeform }`;
					}
					content += ' ]';
					return createBlock( 'core/shortcode', {
						text: content,
					} );
				},
			},
		],
	},
} );
