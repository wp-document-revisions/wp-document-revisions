/**
 * Tests for WP Document Revisions - Document Preview Block.
 *
 * Tests the shortcode transforms and their boolean flag handling.
 *
 * @package WP_Document_Revisions
 */

// --- Mock setup ---

jest.mock( 'react/jsx-runtime', () => ( {
	jsx: jest.fn( ( ...args ) => args ),
	jsxs: jest.fn( ( ...args ) => args ),
	Fragment: Symbol( 'Fragment' ),
} ) );

jest.mock(
	'@wordpress/blocks',
	() => ( {
		registerBlockType: jest.fn(),
		createBlock: jest.fn(),
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/block-editor',
	() => ( {
		useBlockProps: jest.fn( () => ( { className: 'wp-block' } ) ),
		InspectorControls: 'InspectorControls',
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/components',
	() => ( {
		PanelBody: 'PanelBody',
		RangeControl: 'RangeControl',
		TextControl: 'TextControl',
		ToggleControl: 'ToggleControl',
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/server-side-render',
	() => ( {
		__esModule: true,
		default: 'ServerSideRender',
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/i18n',
	() => ( { __: jest.fn( ( text ) => text ) } ),
	{ virtual: true }
);

// --- Imports ---

import { registerBlockType, createBlock } from '@wordpress/blocks';

// --- Test suite ---

describe( 'WP Document Revisions - Document Preview Block', () => {
	let metadata;
	let blockConfig;

	beforeAll( () => {
		require( '../../src/blocks/document-preview/index.js' );
		metadata = registerBlockType.mock.calls[ 0 ][ 0 ];
		blockConfig = registerBlockType.mock.calls[ 0 ][ 1 ];
	} );

	beforeEach( () => {
		createBlock.mockClear();
		createBlock.mockImplementation( ( blockName, attrs ) => attrs );
	} );

	test( 'registers the document-preview block', () => {
		expect( metadata.name ).toBe( 'wp-document-revisions/document-preview' );
	} );

	// -------------------------------------------------------
	// Block Transforms - From Shortcode
	// -------------------------------------------------------
	describe( 'Block Transforms - From Shortcode', () => {
		let fromTransform;

		beforeAll( () => {
			fromTransform = blockConfig.transforms.from[ 0 ];
		} );

		test( 'isMatch matches document_preview shortcode', () => {
			expect(
				fromTransform.isMatch( { text: '[document_preview id=5]' } )
			).toBe( true );
		} );

		test( 'isMatch rejects document_revisions shortcode', () => {
			expect(
				fromTransform.isMatch( { text: '[document_revisions id=5]' } )
			).toBe( false );
		} );

		test( 'transform uses defaults for missing parameters', () => {
			fromTransform.transform( { text: '[document_preview]' } );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				{
					id: 0,
					height: 600,
					show_title: false,
					show_download: true,
				}
			);
		} );

		test( 'transform parses all parameters', () => {
			fromTransform.transform( {
				text: '[document_preview id=12 height=400 show_title=true show_download=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				{
					id: 12,
					height: 400,
					show_title: true,
					show_download: false,
				}
			);
		} );

		test( 'show_title bare flag parses as true', () => {
			fromTransform.transform( {
				text: '[document_preview id=1 show_title]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				expect.objectContaining( { show_title: true } )
			);
		} );

		test( 'show_download bare flag parses as true', () => {
			fromTransform.transform( {
				text: '[document_preview id=1 show_download]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				expect.objectContaining( { show_download: true } )
			);
		} );

		test( 'show_download=false parses as false', () => {
			fromTransform.transform( {
				text: '[document_preview id=1 show_download=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				expect.objectContaining( { show_download: false } )
			);
		} );

		test( 'show_download="false" with quotes parses as false', () => {
			fromTransform.transform( {
				text: '[document_preview id=1 show_download="false"]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				expect.objectContaining( { show_download: false } )
			);
		} );

		test( 'show_download=true parses as true', () => {
			fromTransform.transform( {
				text: '[document_preview id=1 show_download=true]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				expect.objectContaining( { show_download: true } )
			);
		} );

		test( 'omitted show_download keeps the default of true', () => {
			fromTransform.transform( {
				text: '[document_preview id=1 show_title]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/document-preview',
				expect.objectContaining( { show_download: true } )
			);
		} );
	} );

	// -------------------------------------------------------
	// Block Transforms - To Shortcode
	// -------------------------------------------------------
	describe( 'Block Transforms - To Shortcode', () => {
		let toTransform;

		beforeAll( () => {
			toTransform = blockConfig.transforms.to[ 0 ];
		} );

		test( 'writes every attribute as an explicit value', () => {
			const result = toTransform.transform( {
				id: 3,
				height: 500,
				show_title: true,
				show_download: false,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				expect.any( Object )
			);
			expect( result.text ).toContain( 'id="3"' );
			expect( result.text ).toContain( 'height="500"' );
			expect( result.text ).toContain( 'show_title="true"' );
			expect( result.text ).toContain( 'show_download="false"' );
		} );

		test( 'round trip keeps a bare show_download flag as true', () => {
			const fromTransform = blockConfig.transforms.from[ 0 ];
			const attrs = fromTransform.transform( {
				text: '[document_preview id=1 show_download]',
			} );
			const result = toTransform.transform( attrs );

			expect( result.text ).toContain( 'show_download="true"' );
		} );
	} );
} );
