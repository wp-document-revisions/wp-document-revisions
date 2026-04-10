/**
 * Tests for the Documents List Gutenberg block (documents-shortcode).
 *
 * Tests block registration, attributes, supports, transforms,
 * edit function, taxonomy consistency checks, and edge cases.
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
		RadioControl: 'RadioControl',
		RangeControl: 'RangeControl',
		SelectControl: 'SelectControl',
		TextControl: 'TextControl',
		TextareaControl: 'TextareaControl',
		ToggleControl: 'ToggleControl',
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/server-side-render', () => 'ServerSideRender', {
	virtual: true,
} );
jest.mock( '@wordpress/i18n', () => ( { __: jest.fn( ( text ) => text ) } ), {
	virtual: true,
} );

// --- Imports ---

import { jsx, jsxs } from 'react/jsx-runtime';
import { registerBlockType, createBlock } from '@wordpress/blocks';

function getAllJsxCalls() {
	return [ ...jsx.mock.calls, ...jsxs.mock.calls ];
}

describe( 'wpdr-documents-shortcode block', () => {
	let metadata, blockConfig;

	const defaultWpdrData = {
		stmax: 2,
		taxos: [
			{
				query: 'workflow_state',
				label: 'Workflow State',
				terms: [
					[ 0, 'All', 'all' ],
					[ 1, 'Draft', 'draft' ],
					[ 2, 'In Review', 'in-review' ],
				],
			},
			{
				query: 'document_category',
				label: 'Category',
				terms: [
					[ 0, 'All', 'all' ],
					[ 3, 'Reports', 'reports' ],
					[ 4, 'Policies', 'policies' ],
				],
			},
		],
		wf_efpp: '0',
	};

	const defaultAttributes = {
		header: '',
		taxonomy_0: '',
		term_0: 0,
		taxonomy_1: '',
		term_1: 0,
		taxonomy_2: '',
		term_2: 0,
		numberposts: 5,
		orderby: '',
		order: 'ASC',
		show_edit: '',
		show_thumb: false,
		show_descr: true,
		show_pdf: false,
		new_tab: true,
		freeform: '',
	};

	function restoreWpdrData() {
		global.wpdr_data = JSON.parse( JSON.stringify( defaultWpdrData ) );
	}

	beforeAll( () => {
		restoreWpdrData();
		require( '../../src/blocks/documents-shortcode/index.js' );
		metadata = registerBlockType.mock.calls[ 0 ][ 0 ];
		blockConfig = registerBlockType.mock.calls[ 0 ][ 1 ];
	} );

	afterEach( () => {
		jsx.mockClear();
		jsxs.mockClear();
		createBlock.mockClear();
		restoreWpdrData();
	} );

	// ── Block Registration ──

	describe( 'Block Registration', () => {
		test( 'should call registerBlockType exactly once', () => {
			expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
		} );

		test( 'should register with correct block name', () => {
			expect( metadata.name ).toBe(
				'wp-document-revisions/documents-shortcode'
			);
		} );

		test( 'should register with correct title', () => {
			expect( metadata.title ).toBe( 'Documents List' );
		} );

		test( 'should register with correct description', () => {
			expect( metadata.description ).toBe(
				'Display a list of documents.'
			);
		} );

		test( 'should register with correct category', () => {
			expect( metadata.category ).toBe( 'wpdr-category' );
		} );

		test( 'should register with correct icon', () => {
			expect( metadata.icon ).toBe( 'editor-ul' );
		} );

		test( 'should register with edit, save, and transforms', () => {
			expect( typeof blockConfig.edit ).toBe( 'function' );
			expect( typeof blockConfig.save ).toBe( 'function' );
			expect( blockConfig.transforms ).toBeDefined();
			expect( blockConfig.transforms.from ).toBeDefined();
			expect( blockConfig.transforms.to ).toBeDefined();
		} );
	} );

	// ── Block Attributes ──

	describe( 'Block Attributes', () => {
		test( 'should define header as string with empty default', () => {
			expect( metadata.attributes.header ).toEqual( {
				type: 'string',
				default: '',
			} );
		} );

		test( 'should define taxonomy_0 as string with empty default', () => {
			expect( metadata.attributes.taxonomy_0 ).toEqual( {
				type: 'string',
				default: '',
			} );
		} );

		test( 'should define term_0 as number with default 0', () => {
			expect( metadata.attributes.term_0 ).toEqual( {
				type: 'number',
				default: 0,
			} );
		} );

		test( 'should define taxonomy_1 as string with empty default', () => {
			expect( metadata.attributes.taxonomy_1 ).toEqual( {
				type: 'string',
				default: '',
			} );
		} );

		test( 'should define term_1 as number with default 0', () => {
			expect( metadata.attributes.term_1 ).toEqual( {
				type: 'number',
				default: 0,
			} );
		} );

		test( 'should define taxonomy_2 as string with empty default', () => {
			expect( metadata.attributes.taxonomy_2 ).toEqual( {
				type: 'string',
				default: '',
			} );
		} );

		test( 'should define term_2 as number with default 0', () => {
			expect( metadata.attributes.term_2 ).toEqual( {
				type: 'number',
				default: 0,
			} );
		} );

		test( 'should define numberposts as number with default 5', () => {
			expect( metadata.attributes.numberposts ).toEqual( {
				type: 'number',
				default: 5,
			} );
		} );

		test( 'should define orderby as string without default', () => {
			expect( metadata.attributes.orderby ).toEqual( {
				type: 'string',
			} );
		} );

		test( 'should define order as string with default ASC', () => {
			expect( metadata.attributes.order ).toEqual( {
				type: 'string',
				default: 'ASC',
			} );
		} );

		test( 'should define show_edit as string with empty default', () => {
			expect( metadata.attributes.show_edit ).toEqual( {
				type: 'string',
				default: '',
			} );
		} );

		test( 'should define show_thumb as boolean with default false', () => {
			expect( metadata.attributes.show_thumb ).toEqual( {
				type: 'boolean',
				default: false,
			} );
		} );

		test( 'should define show_descr as boolean with default true', () => {
			expect( metadata.attributes.show_descr ).toEqual( {
				type: 'boolean',
				default: true,
			} );
		} );

		test( 'should define show_pdf as boolean with default false', () => {
			expect( metadata.attributes.show_pdf ).toEqual( {
				type: 'boolean',
				default: false,
			} );
		} );

		test( 'should define new_tab as boolean with default true', () => {
			expect( metadata.attributes.new_tab ).toEqual( {
				type: 'boolean',
				default: true,
			} );
		} );

		test( 'should define freeform as string with empty default', () => {
			expect( metadata.attributes.freeform ).toEqual( {
				type: 'string',
				default: '',
			} );
		} );

		test( 'should define style attributes for block supports', () => {
			expect( metadata.attributes.align ).toEqual( {
				type: 'string',
			} );
			expect( metadata.attributes.backgroundColor ).toEqual( {
				type: 'string',
			} );
			expect( metadata.attributes.textColor ).toEqual( {
				type: 'string',
			} );
			expect( metadata.attributes.linkColor ).toEqual( {
				type: 'string',
			} );
			expect( metadata.attributes.gradient ).toEqual( {
				type: 'string',
			} );
			expect( metadata.attributes.fontSize ).toEqual( {
				type: 'string',
			} );
			expect( metadata.attributes.style ).toEqual( {
				type: 'object',
			} );
		} );
	} );

	// ── Block Supports ──

	describe( 'Block Supports', () => {
		test( 'should support align', () => {
			expect( metadata.supports.align ).toBe( true );
		} );

		test( 'should support color with gradients and link', () => {
			expect( metadata.supports.color ).toEqual( {
				gradients: true,
				link: true,
			} );
		} );

		test( 'should support spacing with margin and padding', () => {
			expect( metadata.supports.spacing ).toEqual( {
				margin: true,
				padding: true,
			} );
		} );

		test( 'should support typography with fontSize and lineHeight', () => {
			expect( metadata.supports.typography ).toEqual( {
				fontSize: true,
				lineHeight: true,
			} );
		} );
	} );

	// ── Block Save Function ──

	describe( 'Block Save Function', () => {
		test( 'should return null', () => {
			expect( blockConfig.save() ).toBeNull();
		} );
	} );

	// ── Block Edit Function ──

	describe( 'Block Edit Function', () => {
		test( 'should be a function', () => {
			expect( typeof blockConfig.edit ).toBe( 'function' );
		} );

		test( 'should not throw with default attributes', () => {
			const setAttributes = jest.fn();
			expect( () => {
				blockConfig.edit( {
					attributes: { ...defaultAttributes },
					setAttributes,
				} );
			} ).not.toThrow();
		} );

		test( 'should render ServerSideRender component', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const ssrCall = calls.find(
				( call ) => call[ 0 ] === 'ServerSideRender'
			);
			expect( ssrCall ).toBeDefined();
			expect( ssrCall[ 1 ].block ).toBe(
				'wp-document-revisions/documents-shortcode'
			);
		} );

		test( 'should render InspectorControls', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const inspectorCall = calls.find(
				( call ) => call[ 0 ] === 'InspectorControls'
			);
			expect( inspectorCall ).toBeDefined();
		} );

		test( 'should render taxonomy PanelBody for each taxonomy', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const panelCalls = calls.filter(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					typeof call[ 1 ].title === 'string' &&
					call[ 1 ].title.startsWith( 'Taxonomy: ' )
			);
			expect( panelCalls.length ).toBe( 2 );
		} );

		test( 'should set taxonomy_0 attribute via setAttributes in tax_n', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const taxCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].taxonomy_0 === 'workflow_state'
			);
			expect( taxCalls.length ).toBeGreaterThan( 0 );
		} );

		test( 'should set taxonomy_1 attribute via setAttributes in tax_n', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const taxCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].taxonomy_1 === 'document_category'
			);
			expect( taxCalls.length ).toBeGreaterThan( 0 );
		} );

		test( 'should render RadioControl for each taxonomy with correct options', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const taxRadio0 = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Workflow State'
			);
			expect( taxRadio0 ).toBeDefined();
			expect( taxRadio0[ 1 ].options ).toEqual( [
				{ label: 'All', value: 0 },
				{ label: 'Draft', value: 1 },
				{ label: 'In Review', value: 2 },
			] );

			const taxRadio1 = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Category'
			);
			expect( taxRadio1 ).toBeDefined();
			expect( taxRadio1[ 1 ].options ).toEqual( [
				{ label: 'All', value: 0 },
				{ label: 'Reports', value: 3 },
				{ label: 'Policies', value: 4 },
			] );
		} );

		test( 'should render Display Settings PanelBody', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const displayPanel = calls.find(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					call[ 1 ].title === 'Display Settings'
			);
			expect( displayPanel ).toBeDefined();
		} );

		test( 'should render Free Form Settings PanelBody', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const freeformPanel = calls.find(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					call[ 1 ].title === 'Free Form Settings'
			);
			expect( freeformPanel ).toBeDefined();
		} );

		test( 'should render RadioControl with correct selected value', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'workflow_state',
					term_0: 2,
					taxonomy_1: 'document_category',
					term_1: 4,
				},
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const radioCall0 = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Workflow State'
			);
			expect( radioCall0[ 1 ].selected ).toBe( 2 );

			const radioCall1 = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Category'
			);
			expect( radioCall1[ 1 ].selected ).toBe( 4 );
		} );

		test( 'RadioControl onChange for term_0 should call setAttributes', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const radioCall = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Workflow State'
			);
			expect( radioCall ).toBeDefined();

			setAttributes.mockClear();
			radioCall[ 1 ].onChange( '1' );
			expect( setAttributes ).toHaveBeenCalledWith( {
				term_0: 1,
			} );
		} );

		test( 'RadioControl onChange for term_1 should call setAttributes', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const radioCall = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Category'
			);
			expect( radioCall ).toBeDefined();

			setAttributes.mockClear();
			radioCall[ 1 ].onChange( '3' );
			expect( setAttributes ).toHaveBeenCalledWith( {
				term_1: 3,
			} );
		} );
	} );

	// ── No Taxonomies Path ──

	describe( 'No Taxonomies Path', () => {
		test( 'should render no taxonomies message when stmax is 0', () => {
			global.wpdr_data = { stmax: 0, taxos: [], wf_efpp: '0' };
			const setAttributes = jest.fn();

			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const pCall = calls.find( ( call ) => call[ 0 ] === 'p' );
			expect( pCall ).toBeDefined();

			// Should not render any taxonomy PanelBody
			const taxPanels = calls.filter(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					typeof call[ 1 ].title === 'string' &&
					call[ 1 ].title.startsWith( 'Taxonomy: ' )
			);
			expect( taxPanels.length ).toBe( 0 );
		} );
	} );

	// ── Taxonomy Consistency Checks ──

	describe( 'Taxonomy Consistency Checks', () => {
		test( 'should not reorder when taxonomies match expected order', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'workflow_state',
					term_0: 1,
					taxonomy_1: 'document_category',
					term_1: 3,
				},
				setAttributes,
			} );

			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBe( 0 );
		} );

		test( 'should reorder taxonomy_0 when it does not match expected', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'document_category',
					term_0: 3,
				},
				setAttributes,
			} );

			// Should move document_category to freeform with slug lookup
			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBeGreaterThan( 0 );
			expect( freeformCalls[ 0 ][ 0 ].freeform ).toContain(
				'document_category="reports"'
			);

			// Should reset taxonomy_0 to expected value
			const tax0Calls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].taxonomy_0 !== undefined
			);
			expect(
				tax0Calls.some(
					( c ) => c[ 0 ].taxonomy_0 === 'workflow_state'
				)
			).toBe( true );

			// Should reset term_0 to 0
			const term0Calls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].term_0 === 0
			);
			expect( term0Calls.length ).toBeGreaterThan( 0 );
		} );

		test( 'should reorder taxonomy_1 when it does not match expected', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_1: 'workflow_state',
					term_1: 2,
				},
				setAttributes,
			} );

			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBeGreaterThan( 0 );
			expect( freeformCalls[ 0 ][ 0 ].freeform ).toContain(
				'workflow_state="in-review"'
			);

			const tax1Calls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].taxonomy_1 !== undefined
			);
			expect(
				tax1Calls.some(
					( c ) => c[ 0 ].taxonomy_1 === 'document_category'
				)
			).toBe( true );
		} );

		test( 'should use ?? when term id not found in other taxonomy', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'document_category',
					term_0: 999,
				},
				setAttributes,
			} );

			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBeGreaterThan( 0 );
			expect( freeformCalls[ 0 ][ 0 ].freeform ).toContain(
				'document_category="??"'
			);
		} );

		test( 'should use ??? when taxonomy not found in any other position', () => {
			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'unknown_taxonomy',
					term_0: 1,
				},
				setAttributes,
			} );

			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBeGreaterThan( 0 );
			expect( freeformCalls[ 0 ][ 0 ].freeform ).toContain(
				'unknown_taxonomy="???"'
			);
		} );
	} );

	// ── Block Transforms - From Shortcode ──

	describe( 'Block Transforms - From Shortcode', () => {
		beforeEach( () => {
			createBlock.mockImplementation(
				( blockName, attrs ) => attrs
			);
		} );

		describe( 'isMatch', () => {
			test( 'should match [documents] shortcode', () => {
				expect(
					blockConfig.transforms.from[ 0 ].isMatch( {
						text: '[documents]',
					} )
				).toBe( true );
			} );

			test( 'should match [documents ...] with parameters', () => {
				expect(
					blockConfig.transforms.from[ 0 ].isMatch( {
						text: '[documents numberposts=10]',
					} )
				).toBe( true );
			} );

			test( 'should match documents without brackets', () => {
				expect(
					blockConfig.transforms.from[ 0 ].isMatch( {
						text: 'documents numberposts=10',
					} )
				).toBe( true );
			} );

			test( 'should not match [document_revisions] shortcode', () => {
				expect(
					blockConfig.transforms.from[ 0 ].isMatch( {
						text: '[document_revisions]',
					} )
				).toBe( false );
			} );

			test( 'should not match unrelated text', () => {
				expect(
					blockConfig.transforms.from[ 0 ].isMatch( {
						text: '[gallery]',
					} )
				).toBe( false );
			} );

			test( 'should not match empty text', () => {
				expect(
					blockConfig.transforms.from[ 0 ].isMatch( {
						text: '',
					} )
				).toBe( false );
			} );
		} );

		describe( 'transform structure', () => {
			test( 'should have type block', () => {
				expect( blockConfig.transforms.from[ 0 ].type ).toBe(
					'block'
				);
			} );

			test( 'should transform from core/shortcode', () => {
				expect(
					blockConfig.transforms.from[ 0 ].blocks
				).toEqual( [ 'core/shortcode' ] );
			} );
		} );

		describe( 'transform with default values', () => {
			test( 'should parse [documents] with default attributes', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents]',
					} );

				expect( result.header ).toBe( '' );
				expect( result.taxonomy_0 ).toBe( '' );
				expect( result.term_0 ).toBe( 0 );
				expect( result.taxonomy_1 ).toBe( '' );
				expect( result.term_1 ).toBe( 0 );
				expect( result.numberposts ).toBe( 5 );
				expect( result.orderby ).toBe( '' );
				expect( result.order ).toBe( '' );
				expect( result.show_edit ).toBe( '' );
				expect( result.show_thumb ).toBe( false );
				expect( result.show_descr ).toBe( true );
				expect( result.show_pdf ).toBe( false );
				expect( result.new_tab ).toBe( true );
				expect( result.freeform ).toBe( '' );
			} );

			test( 'should call createBlock with correct block name', () => {
				blockConfig.transforms.from[ 0 ].transform( {
					text: '[documents]',
				} );
				expect( createBlock ).toHaveBeenCalledWith(
					'wp-document-revisions/documents-shortcode',
					expect.any( Object )
				);
			} );
		} );

		describe( 'transform with taxonomy parameters', () => {
			test( 'should parse workflow_state taxonomy', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=draft]',
					} );

				expect( result.taxonomy_0 ).toBe( 'workflow_state' );
				expect( result.term_0 ).toBe( 1 );
			} );

			test( 'should parse document_category taxonomy', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents document_category=reports]',
					} );

				expect( result.taxonomy_1 ).toBe(
					'document_category'
				);
				expect( result.term_1 ).toBe( 3 );
			} );

			test( 'should parse both taxonomies', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=draft document_category=policies]',
					} );

				expect( result.taxonomy_0 ).toBe( 'workflow_state' );
				expect( result.term_0 ).toBe( 1 );
				expect( result.taxonomy_1 ).toBe(
					'document_category'
				);
				expect( result.term_1 ).toBe( 4 );
			} );

			test( 'should handle underscore-to-hyphen slug conversion', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=in_review]',
					} );

				expect( result.taxonomy_0 ).toBe( 'workflow_state' );
				expect( result.term_0 ).toBe( 2 );
			} );

			test( 'should return 0 for unknown taxonomy term', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=unknown]',
					} );

				expect( result.taxonomy_0 ).toBe( 'workflow_state' );
				expect( result.term_0 ).toBe( 0 );
			} );
		} );

		describe( 'transform with numberposts', () => {
			test( 'should parse numberposts parameter', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents numberposts=10]',
					} );
				expect( result.numberposts ).toBe( 10 );
			} );

			test( 'should parse number as alias for numberposts', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents number=15]',
					} );
				expect( result.numberposts ).toBe( 15 );
			} );
		} );

		describe( 'transform with order/orderby', () => {
			test( 'should parse orderby parameter', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents orderby=post_title]',
					} );
				expect( result.orderby ).toBe( 'post_title' );
			} );

			test( 'should parse order parameter and uppercase it', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents order=desc]',
					} );
				expect( result.order ).toBe( 'DESC' );
			} );

			test( 'should parse both orderby and order', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents orderby=post_date order=asc]',
					} );
				expect( result.orderby ).toBe( 'post_date' );
				expect( result.order ).toBe( 'ASC' );
			} );
		} );

		describe( 'transform with show_edit', () => {
			test( 'should parse show_edit=1', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_edit=1]',
					} );
				expect( result.show_edit ).toBe( '1' );
			} );

			test( 'should parse show_edit=0', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_edit=0]',
					} );
				expect( result.show_edit ).toBe( '0' );
			} );
		} );

		describe( 'transform with boolean flags', () => {
			test( 'should parse show_thumb=true', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_thumb=true]',
					} );
				expect( result.show_thumb ).toBe( true );
			} );

			test( 'should parse bare show_thumb flag as true', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_thumb]',
					} );
				expect( result.show_thumb ).toBe( true );
			} );

			test( 'should keep show_thumb false for show_thumb=false', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_thumb=false]',
					} );
				expect( result.show_thumb ).toBe( false );
			} );

			test( 'should parse show_descr=false', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_descr=false]',
					} );
				expect( result.show_descr ).toBe( false );
			} );

			test( 'should keep show_descr true for bare flag', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_descr]',
					} );
				expect( result.show_descr ).toBe( true );
			} );

			test( 'should parse show_pdf=true', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_pdf=true]',
					} );
				expect( result.show_pdf ).toBe( true );
			} );

			test( 'should parse bare show_pdf flag as true', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_pdf]',
					} );
				expect( result.show_pdf ).toBe( true );
			} );

			test( 'should parse new_tab=false', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents new_tab=false]',
					} );
				expect( result.new_tab ).toBe( false );
			} );

			test( 'should keep new_tab true for bare flag', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents new_tab]',
					} );
				expect( result.new_tab ).toBe( true );
			} );
		} );

		describe( 'transform with quoted values', () => {
			test( 'should strip single quotes from values', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: "[documents orderby='post_title']",
					} );
				expect( result.orderby ).toBe( 'post_title' );
			} );

			test( 'should strip double quotes from values', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents orderby="post_date"]',
					} );
				expect( result.orderby ).toBe( 'post_date' );
			} );
		} );

		describe( 'transform with unknown parameters', () => {
			test( 'should add unknown parameters to freeform', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents custom_param=value]',
					} );
				expect( result.freeform ).toBe( 'custom_param=value' );
			} );

			test( 'should accumulate multiple unknown parameters in freeform', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents foo=bar baz=qux]',
					} );
				expect( result.freeform ).toBe( 'foo=bar baz=qux' );
			} );

			test( 'should mix known and unknown parameters', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents numberposts=10 custom=value]',
					} );
				expect( result.numberposts ).toBe( 10 );
				expect( result.freeform ).toBe( 'custom=value' );
			} );
		} );

		describe( 'transform without brackets', () => {
			test( 'should parse shortcode without brackets', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: 'documents numberposts=10 order=desc',
					} );
				expect( result.numberposts ).toBe( 10 );
				expect( result.order ).toBe( 'DESC' );
			} );
		} );

		describe( 'wf_efpp workflow_state to post_status conversion', () => {
			test( 'should convert workflow_state to post_status when wf_efpp is 1', () => {
				global.wpdr_data = {
					stmax: 2,
					taxos: [
						{
							query: 'post_status',
							label: 'Post Status',
							terms: [
								[ 0, 'All', 'all' ],
								[ 1, 'Draft', 'draft' ],
								[ 2, 'In Review', 'in-review' ],
							],
						},
						{
							query: 'document_category',
							label: 'Category',
							terms: [
								[ 0, 'All', 'all' ],
								[ 3, 'Reports', 'reports' ],
								[ 4, 'Policies', 'policies' ],
							],
						},
					],
					wf_efpp: '1',
				};

				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=draft]',
					} );

				expect( result.taxonomy_0 ).toBe( 'post_status' );
				expect( result.term_0 ).toBe( 1 );
			} );

			test( 'should not convert workflow_state when wf_efpp is 0', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=draft]',
					} );

				expect( result.taxonomy_0 ).toBe( 'workflow_state' );
				expect( result.term_0 ).toBe( 1 );
			} );
		} );

		describe( 'transform with multiple parameters', () => {
			test( 'should parse a complex shortcode with many parameters', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents workflow_state=draft document_category=reports numberposts=20 orderby=post_date order=desc show_edit=1 show_thumb show_pdf new_tab=false]',
					} );

				expect( result.taxonomy_0 ).toBe( 'workflow_state' );
				expect( result.term_0 ).toBe( 1 );
				expect( result.taxonomy_1 ).toBe(
					'document_category'
				);
				expect( result.term_1 ).toBe( 3 );
				expect( result.numberposts ).toBe( 20 );
				expect( result.orderby ).toBe( 'post_date' );
				expect( result.order ).toBe( 'DESC' );
				expect( result.show_edit ).toBe( '1' );
				expect( result.show_thumb ).toBe( true );
				expect( result.show_pdf ).toBe( true );
				expect( result.new_tab ).toBe( false );
				expect( result.freeform ).toBe( '' );
			} );
		} );

		describe( 'transform edge cases', () => {
			test( 'should handle extra spaces', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents  numberposts=10  order=desc]',
					} );

				expect( result.numberposts ).toBe( 10 );
				expect( result.order ).toBe( 'DESC' );
			} );

			test( 'should lowercase input before parsing', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents NUMBERPOSTS=10 ORDER=DESC]',
					} );

				expect( result.numberposts ).toBe( 10 );
				expect( result.order ).toBe( 'DESC' );
			} );

			test( 'should handle show_descr=true as still true', () => {
				const result =
					blockConfig.transforms.from[ 0 ].transform( {
						text: '[documents show_descr=true]',
					} );
				expect( result.show_descr ).toBe( true );
			} );
		} );
	} );

	// ── Block Transforms - To Shortcode ──

	describe( 'Block Transforms - To Shortcode', () => {
		const baseToAttrs = {
			taxonomy_0: '',
			term_0: 0,
			taxonomy_1: '',
			term_1: 0,
			taxonomy_2: '',
			term_2: 0,
			numberposts: 5,
			orderby: undefined,
			order: 'ASC',
			show_edit: '',
			show_thumb: false,
			show_descr: true,
			show_pdf: false,
			new_tab: true,
			freeform: '',
		};

		beforeEach( () => {
			createBlock.mockImplementation(
				( blockName, attrs ) => attrs
			);
		} );

		describe( 'transform structure', () => {
			test( 'should have type block', () => {
				expect( blockConfig.transforms.to[ 0 ].type ).toBe(
					'block'
				);
			} );

			test( 'should transform to core/shortcode', () => {
				expect(
					blockConfig.transforms.to[ 0 ].blocks
				).toEqual( [ 'core/shortcode' ] );
			} );
		} );

		describe( 'basic transform', () => {
			test( 'should create shortcode with defaults', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform(
						baseToAttrs
					);

				expect( createBlock ).toHaveBeenCalledWith(
					'core/shortcode',
					expect.objectContaining( {
						text: expect.any( String ),
					} )
				);
				const { text } = result;
				expect( text ).toMatch( /^\[documents/ );
				expect( text ).toMatch( /\]$/ );
				expect( text ).toContain( 'numberposts="5"' );
				expect( text ).toContain( 'show_descr' );
				expect( text ).toContain( 'new_tab' );
			} );
		} );

		describe( 'transform with taxonomy terms', () => {
			test( 'should include taxonomy_0 with slug', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						taxonomy_0: 'workflow_state',
						term_0: 1,
					} );
				expect( result.text ).toContain(
					'workflow_state="draft"'
				);
			} );

			test( 'should include taxonomy_1 with slug', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						taxonomy_1: 'document_category',
						term_1: 3,
					} );
				expect( result.text ).toContain(
					'document_category="reports"'
				);
			} );

			test( 'should include both taxonomies', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						taxonomy_0: 'workflow_state',
						term_0: 2,
						taxonomy_1: 'document_category',
						term_1: 4,
					} );
				expect( result.text ).toContain(
					'workflow_state="in-review"'
				);
				expect( result.text ).toContain(
					'document_category="policies"'
				);
			} );

			test( 'should not include taxonomy when term is 0', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						taxonomy_0: 'workflow_state',
						term_0: 0,
					} );
				expect( result.text ).not.toContain(
					'workflow_state'
				);
			} );

			test( 'should return ?? for unknown term id', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						taxonomy_0: 'workflow_state',
						term_0: 999,
					} );
				expect( result.text ).toContain(
					'workflow_state=??'
				);
			} );
		} );

		describe( 'transform with display settings', () => {
			test( 'should include orderby and order when set', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						numberposts: 10,
						orderby: 'post_title',
						order: 'DESC',
					} );

				expect( result.text ).toContain(
					'numberposts="10"'
				);
				expect( result.text ).toContain(
					'orderby="post_title"'
				);
				expect( result.text ).toContain( 'order="DESC"' );
			} );

			test( 'should not include order when orderby is not set', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						orderby: undefined,
						order: 'DESC',
					} );

				expect( result.text ).not.toContain( 'order=' );
				expect( result.text ).not.toContain( 'orderby' );
			} );

			test( 'should not include order when orderby is empty', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						orderby: '',
						order: 'DESC',
					} );

				expect( result.text ).not.toContain( 'order=' );
			} );

			test( 'should include show_edit when not empty', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_edit: '1',
					} );
				expect( result.text ).toContain( 'show_edit="1"' );
			} );

			test( 'should include show_edit="0" in shortcode', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_edit: '0',
					} );
				expect( result.text ).toContain( 'show_edit="0"' );
			} );

			test( 'should not include show_edit when empty', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform(
						baseToAttrs
					);
				expect( result.text ).not.toContain( 'show_edit' );
			} );

			test( 'should include show_thumb when true', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_thumb: true,
					} );
				expect( result.text ).toContain( 'show_thumb' );
			} );

			test( 'should not include show_thumb when false', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_thumb: false,
					} );
				expect( result.text ).not.toContain( 'show_thumb' );
			} );

			test( 'should include show_pdf when true', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_pdf: true,
					} );
				expect( result.text ).toContain( 'show_pdf' );
			} );

			test( 'should not include new_tab when false', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_descr: false,
						new_tab: false,
					} );

				expect( result.text ).not.toContain( 'new_tab' );
				expect( result.text ).not.toContain( 'show_descr' );
			} );
		} );

		describe( 'transform with freeform', () => {
			test( 'should include freeform parameters at end', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_descr: false,
						new_tab: false,
						freeform: 'custom_param=value',
					} );

				expect( result.text ).toContain(
					'custom_param=value'
				);
				expect( result.text ).toMatch(
					/custom_param=value\s*\]$/
				);
			} );

			test( 'should not include freeform when empty', () => {
				const result =
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						show_descr: false,
						new_tab: false,
						freeform: '',
					} );

				expect( result.text ).toMatch(
					/^\[documents\s+numberposts="5"\s+\]$/
				);
			} );

			test( 'should handle freeform as undefined', () => {
				expect( () =>
					blockConfig.transforms.to[ 0 ].transform( {
						...baseToAttrs,
						freeform: undefined,
					} )
				).not.toThrow();
			} );
		} );
	} );

	// ── tax_n Function - All Indices ──

	describe( 'tax_n Function - All Indices', () => {
		test( 'should render taxonomy panel for index 2 when 3 taxonomies exist', () => {
			global.wpdr_data = {
				stmax: 3,
				taxos: [
					{
						query: 'workflow_state',
						label: 'Workflow State',
						terms: [
							[ 0, 'All', 'all' ],
							[ 1, 'Draft', 'draft' ],
						],
					},
					{
						query: 'document_category',
						label: 'Category',
						terms: [
							[ 0, 'All', 'all' ],
							[ 3, 'Reports', 'reports' ],
						],
					},
					{
						query: 'document_tag',
						label: 'Tag',
						terms: [
							[ 0, 'All', 'all' ],
							[ 5, 'Important', 'important' ],
							[ 6, 'Archive', 'archive' ],
						],
					},
				],
				wf_efpp: '0',
			};

			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();

			// Should have 3 taxonomy panels
			const taxPanels = calls.filter(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					typeof call[ 1 ].title === 'string' &&
					call[ 1 ].title.startsWith( 'Taxonomy: ' )
			);
			expect( taxPanels.length ).toBe( 3 );

			// Check the third taxonomy panel
			const panel2 = calls.find(
				( call ) =>
					call[ 0 ] === 'PanelBody' &&
					typeof call[ 1 ].title === 'string' &&
					call[ 1 ].title.includes( 'Tag' )
			);
			expect( panel2 ).toBeDefined();

			// Check RadioControl for Tag taxonomy
			const radioCall = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Tag'
			);
			expect( radioCall ).toBeDefined();
			expect( radioCall[ 1 ].options ).toEqual( [
				{ label: 'All', value: 0 },
				{ label: 'Important', value: 5 },
				{ label: 'Archive', value: 6 },
			] );

			// taxonomy_2 should be set
			const tax2Calls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].taxonomy_2 === 'document_tag'
			);
			expect( tax2Calls.length ).toBeGreaterThan( 0 );
		} );

		test( 'RadioControl onChange for term_2 should call setAttributes', () => {
			global.wpdr_data = {
				stmax: 3,
				taxos: [
					{
						query: 'workflow_state',
						label: 'Workflow State',
						terms: [
							[ 0, 'All', 'all' ],
							[ 1, 'Draft', 'draft' ],
						],
					},
					{
						query: 'document_category',
						label: 'Category',
						terms: [
							[ 0, 'All', 'all' ],
							[ 3, 'Reports', 'reports' ],
						],
					},
					{
						query: 'document_tag',
						label: 'Tag',
						terms: [
							[ 0, 'All', 'all' ],
							[ 5, 'Important', 'important' ],
						],
					},
				],
				wf_efpp: '0',
			};

			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: { ...defaultAttributes },
				setAttributes,
			} );

			const calls = getAllJsxCalls();
			const radioCall = calls.find(
				( call ) =>
					call[ 0 ] === 'RadioControl' &&
					call[ 1 ].label === 'Tag'
			);
			expect( radioCall ).toBeDefined();

			setAttributes.mockClear();
			radioCall[ 1 ].onChange( '5' );
			expect( setAttributes ).toHaveBeenCalledWith( {
				term_2: 5,
			} );
		} );
	} );

	// ── Taxonomy consistency with 3 taxonomies ──

	describe( 'Taxonomy Consistency with 3 taxonomies', () => {
		const threeWpdrData = {
			stmax: 3,
			taxos: [
				{
					query: 'workflow_state',
					label: 'Workflow State',
					terms: [
						[ 0, 'All', 'all' ],
						[ 1, 'Draft', 'draft' ],
						[ 2, 'Final', 'final' ],
					],
				},
				{
					query: 'document_type',
					label: 'Document Type',
					terms: [
						[ 0, 'All', 'all' ],
						[ 3, 'Policy', 'policy' ],
						[ 4, 'Report', 'report' ],
					],
				},
				{
					query: 'department',
					label: 'Department',
					terms: [
						[ 0, 'All', 'all' ],
						[ 5, 'HR', 'hr' ],
						[ 6, 'IT', 'it' ],
					],
				},
			],
			wf_efpp: '0',
		};

		test( 'should reorder taxonomy_2 when it does not match expected', () => {
			global.wpdr_data = JSON.parse(
				JSON.stringify( threeWpdrData )
			);

			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'workflow_state',
					taxonomy_1: 'document_type',
					taxonomy_2: 'workflow_state',
					term_2: 1,
				},
				setAttributes,
			} );

			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBeGreaterThan( 0 );
			expect( freeformCalls[ 0 ][ 0 ].freeform ).toContain(
				'workflow_state='
			);

			const tax2Calls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].taxonomy_2 !== undefined
			);
			expect(
				tax2Calls.some(
					( c ) => c[ 0 ].taxonomy_2 === 'department'
				)
			).toBe( true );
		} );

		test( 'should not reorder when all 3 taxonomies match', () => {
			global.wpdr_data = JSON.parse(
				JSON.stringify( threeWpdrData )
			);

			const setAttributes = jest.fn();
			blockConfig.edit( {
				attributes: {
					...defaultAttributes,
					taxonomy_0: 'workflow_state',
					term_0: 1,
					taxonomy_1: 'document_type',
					term_1: 3,
					taxonomy_2: 'department',
					term_2: 5,
				},
				setAttributes,
			} );

			const freeformCalls = setAttributes.mock.calls.filter(
				( call ) => call[ 0 ].freeform !== undefined
			);
			expect( freeformCalls.length ).toBe( 0 );
		} );
	} );
} );
