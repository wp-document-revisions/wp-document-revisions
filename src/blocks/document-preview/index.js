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
					return /^\[?document_preview\b\s*/.test( text );
				},
				transform: ( { text } ) => {
					// Tokenize the raw shortcode into parameter pairs.
					const params = parseShortcodeParams( text );

					// defaults.
					let sid = 0;
					let sheight = 600;
					let sshow_title = false;
					let sshow_download = true;
					for ( const parm of params ) {
						if ( parm[ 0 ] === 'id' ) {
							sid = Number( parm[ 1 ] );
						}
						if ( parm[ 0 ] === 'height' ) {
							sheight = Number( parm[ 1 ] );
						}
						if ( parm[ 0 ] === 'show_title' ) {
							if ( parm.length === 1 || parm[ 1 ] === 'true' ) {
								sshow_title = true;
							}
						}
						if ( parm[ 0 ] === 'show_download' ) {
							if ( parm.length === 1 || parm[ 1 ] === 'false' ) {
								sshow_download = false;
							}
						}
					}

					return createBlock( 'wp-document-revisions/document-preview', {
						id: sid,
						height: sheight,
						show_title: sshow_title,
						show_download: sshow_download,
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
					if ( '' !== attributes.id ) {
						named.id = String( attributes.id );
					}
					named.height = String( attributes.height );
					named.show_title = attributes.show_title ? 'true' : 'false';
					named.show_download = attributes.show_download ? 'true' : 'false';
					const content = buildShortcode( 'document_preview', named );
					return createBlock( 'core/shortcode', {
						text: content,
					} );
				},
			},
		],
	},
} );
