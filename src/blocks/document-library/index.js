// @ts-check
import { createBlock, registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import { attributesToShortcode, shortcodeToAttributes } from './transforms';
import './style.css';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				isMatch: ( { text } ) => /^\[?document_library\b/.test( String( text ).trim() ),
				transform: ( { text } ) =>
					createBlock(
						'wp-document-revisions/document-library',
						shortcodeToAttributes( text )
					),
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				transform: ( attributes ) =>
					createBlock( 'core/shortcode', { text: attributesToShortcode( attributes ) } ),
			},
		],
	},
} );
