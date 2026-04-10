/**
 * Tests for WP Document Revisions - Revisions Shortcode Block.
 *
 * Tests the Gutenberg block registration, edit component rendering,
 * save function, shortcode transforms, and attribute handling.
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

jest.mock( '@wordpress/server-side-render', () => 'ServerSideRender', {
	virtual: true,
} );

jest.mock(
	'@wordpress/i18n',
	() => ( { __: jest.fn( ( text ) => text ) } ),
	{ virtual: true }
);

// --- Imports and helpers ---

import { jsx, jsxs } from 'react/jsx-runtime';
import { registerBlockType, createBlock } from '@wordpress/blocks';

function getAllJsxCalls() {
	return [ ...jsx.mock.calls, ...jsxs.mock.calls ];
}

// --- Test suite ---

describe( 'WP Document Revisions - Revisions Shortcode Block', () => {
	let metadata;
	let blockConfig;

	beforeAll( () => {
		require( '../../src/blocks/revisions-shortcode/index.js' );
		metadata = registerBlockType.mock.calls[ 0 ][ 0 ];
		blockConfig = registerBlockType.mock.calls[ 0 ][ 1 ];
	} );

	// -------------------------------------------------------
	// 1. Block Registration
	// -------------------------------------------------------
	describe( 'Block Registration', () => {
		test( 'calls registerBlockType exactly once', () => {
			expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'registers with correct block name', () => {
			expect( metadata.name ).toBe(
				'wp-document-revisions/revisions-shortcode'
			);
		} );

		test( 'registers with correct title', () => {
			expect( metadata.title ).toBe( 'Document Revisions' );
		} );

		test( 'registers with correct description', () => {
			expect( metadata.description ).toBe(
				'Display a list of revisions for your document.'
			);
		} );

		test( 'registers with correct category', () => {
			expect( metadata.category ).toBe( 'wpdr-category' );
		} );

		test( 'registers with correct icon', () => {
			expect( metadata.icon ).toBe( 'list-view' );
		} );
	} );

	// -------------------------------------------------------
	// 2. Block Attributes
	// -------------------------------------------------------
	describe( 'Block Attributes', () => {
		test( 'defines id attribute as number with default 1', () => {
			expect( metadata.attributes.id ).toEqual( {
				type: 'number',
				default: 1,
			} );
		} );

		test( 'defines numberposts attribute as number with default 5', () => {
			expect( metadata.attributes.numberposts ).toEqual( {
				type: 'number',
				default: 5,
			} );
		} );

		test( 'defines summary attribute as boolean with default false', () => {
			expect( metadata.attributes.summary ).toEqual( {
				type: 'boolean',
				default: false,
			} );
		} );

		test( 'defines show_pdf attribute as boolean with default false', () => {
			expect( metadata.attributes.show_pdf ).toEqual( {
				type: 'boolean',
				default: false,
			} );
		} );

		test( 'defines new_tab attribute as boolean with default true', () => {
			expect( metadata.attributes.new_tab ).toEqual( {
				type: 'boolean',
				default: true,
			} );
		} );

		test( 'defines align attribute as string', () => {
			expect( metadata.attributes.align ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'defines backgroundColor attribute as string', () => {
			expect( metadata.attributes.backgroundColor ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'defines linkColor attribute as string', () => {
			expect( metadata.attributes.linkColor ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'defines textColor attribute as string', () => {
			expect( metadata.attributes.textColor ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'defines gradient attribute as string', () => {
			expect( metadata.attributes.gradient ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'defines fontSize attribute as string', () => {
			expect( metadata.attributes.fontSize ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'defines style attribute as object', () => {
			expect( metadata.attributes.style ).toEqual( {
				type: 'object',
			} );
		} );
	} );

	// -------------------------------------------------------
	// 3. Block Supports
	// -------------------------------------------------------
	describe( 'Block Supports', () => {
		test( 'supports align', () => {
			expect( metadata.supports.align ).toBe( true );
		} );

		test( 'supports color with gradients and link', () => {
			expect( metadata.supports.color ).toEqual( {
				gradients: true,
				link: true,
			} );
		} );

		test( 'supports spacing with margin and padding', () => {
			expect( metadata.supports.spacing ).toEqual( {
				margin: true,
				padding: true,
			} );
		} );

		test( 'supports typography with fontSize and lineHeight', () => {
			expect( metadata.supports.typography ).toEqual( {
				fontSize: true,
				lineHeight: true,
			} );
		} );
	} );

	// -------------------------------------------------------
	// 4. Block Edit Function
	// -------------------------------------------------------
	describe( 'Block Edit Function', () => {
		test( 'provides an edit function', () => {
			expect( typeof blockConfig.edit ).toBe( 'function' );
		} );

		test( 'edit function does not throw', () => {
			expect( () => {
				blockConfig.edit( {
					attributes: {
						id: 1,
						numberposts: 5,
						summary: false,
						show_pdf: false,
						new_tab: true,
					},
					setAttributes: jest.fn(),
				} );
			} ).not.toThrow();
		} );
	} );

	// -------------------------------------------------------
	// 5. Block Save Function
	// -------------------------------------------------------
	describe( 'Block Save Function', () => {
		test( 'provides a save function', () => {
			expect( typeof blockConfig.save ).toBe( 'function' );
		} );

		test( 'save function returns null', () => {
			expect( blockConfig.save() ).toBeNull();
		} );
	} );

	// -------------------------------------------------------
	// 6. Block Transforms - From Shortcode
	// -------------------------------------------------------
	describe( 'Block Transforms - From Shortcode', () => {
		let fromTransform;

		beforeAll( () => {
			fromTransform = blockConfig.transforms.from[ 0 ];
		} );

		beforeEach( () => {
			createBlock.mockClear();
			createBlock.mockImplementation(
				( blockName, attrs ) => attrs
			);
		} );

		test( 'from transform is type block', () => {
			expect( fromTransform.type ).toBe( 'block' );
		} );

		test( 'from transform targets core/shortcode', () => {
			expect( fromTransform.blocks ).toEqual( [
				'core/shortcode',
			] );
		} );

		test( 'isMatch matches document_revisions shortcode', () => {
			expect(
				fromTransform.isMatch( {
					text: '[document_revisions id=5]',
				} )
			).toBe( true );
		} );

		test( 'isMatch matches without brackets', () => {
			expect(
				fromTransform.isMatch( {
					text: 'document_revisions id=5',
				} )
			).toBe( true );
		} );

		test( 'isMatch rejects documents shortcode', () => {
			expect(
				fromTransform.isMatch( { text: '[documents id=5]' } )
			).toBe( false );
		} );

		test( 'isMatch rejects unrelated text', () => {
			expect(
				fromTransform.isMatch( {
					text: 'some other content',
				} )
			).toBe( false );
		} );

		test( 'transform parses all parameters', () => {
			const result = fromTransform.transform( {
				text: '[document_revisions id=10 numberposts=3 summary=true show_pdf=true new_tab=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				{
					id: 10,
					numberposts: 3,
					summary: true,
					show_pdf: true,
					new_tab: false,
				}
			);
			expect( result ).toEqual( {
				id: 10,
				numberposts: 3,
				summary: true,
				show_pdf: true,
				new_tab: false,
			} );
		} );

		test( 'transform uses defaults for missing parameters', () => {
			fromTransform.transform( {
				text: '[document_revisions]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				{
					id: 1,
					numberposts: 5,
					summary: false,
					show_pdf: false,
					new_tab: true,
				}
			);
		} );

		test( 'transform accepts number parameter as alias for numberposts', () => {
			fromTransform.transform( {
				text: '[document_revisions number=8]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { numberposts: 8 } )
			);
		} );

		test( 'transform handles quoted values', () => {
			fromTransform.transform( {
				text: "[document_revisions id='7' numberposts=\"3\"]",
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { id: 7, numberposts: 3 } )
			);
		} );

		test( 'transform works without surrounding brackets', () => {
			fromTransform.transform( {
				text: 'document_revisions id=15 numberposts=2',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( {
					id: 15,
					numberposts: 2,
				} )
			);
		} );
	} );

	// -------------------------------------------------------
	// 7. Block Transforms - To Shortcode
	// -------------------------------------------------------
	describe( 'Block Transforms - To Shortcode', () => {
		let toTransform;

		beforeAll( () => {
			toTransform = blockConfig.transforms.to[ 0 ];
		} );

		beforeEach( () => {
			createBlock.mockClear();
			createBlock.mockImplementation(
				( blockName, attrs ) => attrs
			);
		} );

		test( 'to transform is type block', () => {
			expect( toTransform.type ).toBe( 'block' );
		} );

		test( 'to transform targets core/shortcode', () => {
			expect( toTransform.blocks ).toEqual( [
				'core/shortcode',
			] );
		} );

		test( 'generates shortcode with all defaults', () => {
			toTransform.transform( {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: true,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				{
					text: '[document_revisions id=1 numberposts=5 summary=false new_tab=true ]',
				}
			);
		} );

		test( 'includes summary=true when summary is true', () => {
			toTransform.transform( {
				id: 1,
				numberposts: 5,
				summary: true,
				show_pdf: false,
				new_tab: true,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				{
					text: '[document_revisions id=1 numberposts=5 summary=true new_tab=true ]',
				}
			);
		} );

		test( 'includes show_pdf bare flag when show_pdf is true', () => {
			toTransform.transform( {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: true,
				new_tab: true,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				{
					text: '[document_revisions id=1 numberposts=5 summary=false show_pdf new_tab=true ]',
				}
			);
		} );

		test( 'omits show_pdf when show_pdf is false', () => {
			toTransform.transform( {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: true,
			} );

			const callText =
				createBlock.mock.calls[ 0 ][ 1 ].text;
			expect( callText ).not.toContain( 'show_pdf' );
		} );

		test( 'includes new_tab=false when new_tab is false', () => {
			toTransform.transform( {
				id: 2,
				numberposts: 10,
				summary: true,
				show_pdf: true,
				new_tab: false,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				{
					text: '[document_revisions id=2 numberposts=10 summary=true show_pdf new_tab=false ]',
				}
			);
		} );

		test( 'generates correct shortcode with custom values', () => {
			toTransform.transform( {
				id: 42,
				numberposts: 15,
				summary: true,
				show_pdf: false,
				new_tab: false,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				{
					text: '[document_revisions id=42 numberposts=15 summary=true new_tab=false ]',
				}
			);
		} );
	} );

	// -------------------------------------------------------
	// 8. Attribute Defaults
	// -------------------------------------------------------
	describe( 'Attribute Defaults', () => {
		test( 'id defaults to 1', () => {
			expect( metadata.attributes.id.default ).toBe( 1 );
		} );

		test( 'numberposts defaults to 5', () => {
			expect( metadata.attributes.numberposts.default ).toBe(
				5
			);
		} );

		test( 'summary defaults to false', () => {
			expect( metadata.attributes.summary.default ).toBe(
				false
			);
		} );

		test( 'show_pdf defaults to false', () => {
			expect( metadata.attributes.show_pdf.default ).toBe(
				false
			);
		} );

		test( 'new_tab defaults to true', () => {
			expect( metadata.attributes.new_tab.default ).toBe(
				true
			);
		} );
	} );

	// -------------------------------------------------------
	// 9. Edit Function - JSX Rendering
	// -------------------------------------------------------
	describe( 'Edit Function - JSX Rendering', () => {
		let calls;
		const testAttributes = {
			id: 7,
			numberposts: 3,
			summary: true,
			show_pdf: false,
			new_tab: true,
		};

		beforeAll( () => {
			jsx.mockClear();
			jsxs.mockClear();
			blockConfig.edit( {
				attributes: testAttributes,
				setAttributes: jest.fn(),
			} );
			calls = getAllJsxCalls();
		} );

		test( 'renders ServerSideRender with correct block name', () => {
			const ssrCall = calls.find(
				( call ) => call[ 0 ] === 'ServerSideRender'
			);
			expect( ssrCall ).toBeDefined();
			expect( ssrCall[ 1 ].block ).toBe(
				'wp-document-revisions/revisions-shortcode'
			);
		} );

		test( 'passes attributes to ServerSideRender', () => {
			const ssrCall = calls.find(
				( call ) => call[ 0 ] === 'ServerSideRender'
			);
			expect( ssrCall[ 1 ].attributes ).toBe(
				testAttributes
			);
		} );

		test( 'renders InspectorControls', () => {
			const inspectorCall = calls.find(
				( call ) => call[ 0 ] === 'InspectorControls'
			);
			expect( inspectorCall ).toBeDefined();
		} );

		test( 'renders PanelBody with Selection Criteria title and initialOpen true', () => {
			const panelCall = calls.find(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					call[ 1 ].title === 'Selection Criteria'
			);
			expect( panelCall ).toBeDefined();
			expect( panelCall[ 1 ].initialOpen ).toBe( true );
		} );

		test( 'renders TextControl for Document Id with type number', () => {
			const textCall = calls.find(
				( call ) =>
					call[ 0 ] === 'TextControl' &&
					call[ 1 ].label === 'Document Id'
			);
			expect( textCall ).toBeDefined();
			expect( textCall[ 1 ].type ).toBe( 'number' );
			expect( textCall[ 1 ].value ).toBe( testAttributes.id );
		} );

		test( 'renders RangeControl for Revisions to Display with min 1 and max 20', () => {
			const rangeCall = calls.find(
				( call ) =>
					call[ 0 ] === 'RangeControl' &&
					call[ 1 ].label === 'Revisions to Display'
			);
			expect( rangeCall ).toBeDefined();
			expect( rangeCall[ 1 ].value ).toBe(
				testAttributes.numberposts
			);
			expect( rangeCall[ 1 ].min ).toBe( 1 );
			expect( rangeCall[ 1 ].max ).toBe( 20 );
		} );

		test( 'renders ToggleControl for summary', () => {
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Show Revision Summaries?'
			);
			expect( toggleCall ).toBeDefined();
			expect( toggleCall[ 1 ].checked ).toBe(
				testAttributes.summary
			);
		} );

		test( 'renders ToggleControl for show_pdf', () => {
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Show PDF File indication?'
			);
			expect( toggleCall ).toBeDefined();
			expect( toggleCall[ 1 ].checked ).toBe(
				testAttributes.show_pdf
			);
		} );

		test( 'renders ToggleControl for new_tab', () => {
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Open in New Tab?'
			);
			expect( toggleCall ).toBeDefined();
			expect( toggleCall[ 1 ].checked ).toBe(
				testAttributes.new_tab
			);
		} );

		test( 'new_tab ToggleControl has help text', () => {
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Open in New Tab?'
			);
			expect( toggleCall[ 1 ].help ).toBeDefined();
			expect( typeof toggleCall[ 1 ].help ).toBe( 'string' );
			expect( toggleCall[ 1 ].help.length ).toBeGreaterThan(
				0
			);
		} );

		test( 'renders div wrapper with blockProps', () => {
			const divCall = calls.find(
				( call ) =>
					call[ 0 ] === 'div' &&
					call[ 1 ].className === 'wp-block'
			);
			expect( divCall ).toBeDefined();
		} );
	} );

	// -------------------------------------------------------
	// 10. Edit Function - onChange Callbacks
	// -------------------------------------------------------
	describe( 'Edit Function - onChange Callbacks', () => {
		let setAttributes;

		beforeEach( () => {
			jsx.mockClear();
			jsxs.mockClear();
			setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					id: 1,
					numberposts: 5,
					summary: false,
					show_pdf: false,
					new_tab: true,
				},
				setAttributes,
			} );
		} );

		test( 'id onChange calls setAttributes with parsed integer', () => {
			const calls = getAllJsxCalls();
			const textCall = calls.find(
				( call ) =>
					call[ 0 ] === 'TextControl' &&
					call[ 1 ].label === 'Document Id'
			);
			textCall[ 1 ].onChange( '42' );
			expect( setAttributes ).toHaveBeenCalledWith( {
				id: 42,
			} );
		} );

		test( 'numberposts onChange calls setAttributes with parsed integer', () => {
			const calls = getAllJsxCalls();
			const rangeCall = calls.find(
				( call ) =>
					call[ 0 ] === 'RangeControl' &&
					call[ 1 ].label === 'Revisions to Display'
			);
			rangeCall[ 1 ].onChange( 10 );
			expect( setAttributes ).toHaveBeenCalledWith( {
				numberposts: 10,
			} );
		} );

		test( 'summary onChange calls setAttributes with boolean', () => {
			const calls = getAllJsxCalls();
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Show Revision Summaries?'
			);
			toggleCall[ 1 ].onChange( true );
			expect( setAttributes ).toHaveBeenCalledWith( {
				summary: true,
			} );
		} );

		test( 'show_pdf onChange calls setAttributes with boolean', () => {
			const calls = getAllJsxCalls();
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Show PDF File indication?'
			);
			toggleCall[ 1 ].onChange( true );
			expect( setAttributes ).toHaveBeenCalledWith( {
				show_pdf: true,
			} );
		} );

		test( 'new_tab onChange calls setAttributes with boolean', () => {
			const calls = getAllJsxCalls();
			const toggleCall = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Open in New Tab?'
			);
			toggleCall[ 1 ].onChange( false );
			expect( setAttributes ).toHaveBeenCalledWith( {
				new_tab: false,
			} );
		} );
	} );

	// -------------------------------------------------------
	// 11. From Shortcode Edge Cases
	// -------------------------------------------------------
	describe( 'From Shortcode Edge Cases', () => {
		let fromTransform;

		beforeAll( () => {
			fromTransform = blockConfig.transforms.from[ 0 ];
		} );

		beforeEach( () => {
			createBlock.mockClear();
			createBlock.mockImplementation(
				( blockName, attrs ) => attrs
			);
		} );

		test( 'show_pdf bare flag parses as true', () => {
			fromTransform.transform( {
				text: '[document_revisions show_pdf]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { show_pdf: true } )
			);
		} );

		test( 'new_tab=false parses as false', () => {
			fromTransform.transform( {
				text: '[document_revisions new_tab=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { new_tab: false } )
			);
		} );

		test( 'new_tab bare flag parses as false (special case)', () => {
			fromTransform.transform( {
				text: '[document_revisions new_tab]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { new_tab: false } )
			);
		} );

		test( 'handles consecutive spaces between parameters', () => {
			fromTransform.transform( {
				text: '[document_revisions  id=3   numberposts=7]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( {
					id: 3,
					numberposts: 7,
				} )
			);
		} );

		test( 'show_pdf=true parses as true', () => {
			fromTransform.transform( {
				text: '[document_revisions show_pdf=true]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { show_pdf: true } )
			);
		} );

		test( 'show_pdf=false parses as false', () => {
			fromTransform.transform( {
				text: '[document_revisions show_pdf=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { show_pdf: false } )
			);
		} );

		test( 'summary=false parses as false', () => {
			fromTransform.transform( {
				text: '[document_revisions summary=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { summary: false } )
			);
		} );

		test( 'summary bare flag parses as true', () => {
			fromTransform.transform( {
				text: '[document_revisions summary]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { summary: true } )
			);
		} );

		test( 'new_tab=true parses as true', () => {
			fromTransform.transform( {
				text: '[document_revisions new_tab=true]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { new_tab: true } )
			);
		} );
	} );

	// -------------------------------------------------------
	// 12. Edge Cases
	// -------------------------------------------------------
	describe( 'Edge Cases', () => {
		let fromTransform;
		let toTransform;

		beforeAll( () => {
			fromTransform = blockConfig.transforms.from[ 0 ];
			toTransform = blockConfig.transforms.to[ 0 ];
		} );

		beforeEach( () => {
			createBlock.mockClear();
			createBlock.mockImplementation(
				( blockName, attrs ) => attrs
			);
		} );

		test( 'from transform handles zero id', () => {
			fromTransform.transform( {
				text: '[document_revisions id=0]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { id: 0 } )
			);
		} );

		test( 'from transform handles negative id', () => {
			fromTransform.transform( {
				text: '[document_revisions id=-1]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { id: -1 } )
			);
		} );

		test( 'from transform handles very large id', () => {
			fromTransform.transform( {
				text: '[document_revisions id=999999]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { id: 999999 } )
			);
		} );

		test( 'from transform handles extreme numberposts value', () => {
			fromTransform.transform( {
				text: '[document_revisions numberposts=100]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( { numberposts: 100 } )
			);
		} );

		test( 'from transform handles all booleans toggled', () => {
			fromTransform.transform( {
				text: '[document_revisions summary=true show_pdf=true new_tab=false]',
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.objectContaining( {
					summary: true,
					show_pdf: true,
					new_tab: false,
				} )
			);
		} );

		test( 'to transform handles zero id', () => {
			toTransform.transform( {
				id: 0,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: true,
			} );

			const callText =
				createBlock.mock.calls[ 0 ][ 1 ].text;
			expect( callText ).toContain( 'id=0' );
		} );

		test( 'to transform handles all booleans toggled', () => {
			toTransform.transform( {
				id: 1,
				numberposts: 5,
				summary: true,
				show_pdf: true,
				new_tab: false,
			} );

			expect( createBlock ).toHaveBeenCalledWith(
				'core/shortcode',
				{
					text: '[document_revisions id=1 numberposts=5 summary=true show_pdf new_tab=false ]',
				}
			);
		} );

		test( 'edit renders correctly with all booleans toggled', () => {
			jsx.mockClear();
			jsxs.mockClear();

			const attrs = {
				id: 99,
				numberposts: 20,
				summary: true,
				show_pdf: true,
				new_tab: false,
			};

			blockConfig.edit( {
				attributes: attrs,
				setAttributes: jest.fn(),
			} );

			const calls = getAllJsxCalls();

			const ssrCall = calls.find(
				( call ) => call[ 0 ] === 'ServerSideRender'
			);
			expect( ssrCall[ 1 ].attributes ).toBe( attrs );

			const summaryToggle = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Show Revision Summaries?'
			);
			expect( summaryToggle[ 1 ].checked ).toBe( true );

			const pdfToggle = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Show PDF File indication?'
			);
			expect( pdfToggle[ 1 ].checked ).toBe( true );

			const tabToggle = calls.find(
				( call ) =>
					call[ 0 ] === 'ToggleControl' &&
					call[ 1 ].label === 'Open in New Tab?'
			);
			expect( tabToggle[ 1 ].checked ).toBe( false );
		} );
	} );
} );