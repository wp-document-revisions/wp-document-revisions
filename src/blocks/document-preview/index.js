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
					let content = '[document_preview ';
					if ( '' !== attributes.id ) {
						content += `id=${ attributes.id }`;
					}
					content += ` height=${ attributes.height }`;
					if ( attributes.show_title ) {
						content += ' show_title=true';
					} else {
						content += ' show_title=false';
					}
					if ( attributes.show_download ) {
						content += ' show_download=true ]';
					} else {
						content += ' show_download=false ]';
					}
					return createBlock( 'core/shortcode', {
						text: content,
					} );
				},
			},
		],
	},
} );
