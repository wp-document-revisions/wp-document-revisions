/**
 * Tests for documents-widget block (modern JSX/ESM version).
 *
 * This file tests the Latest Documents Gutenberg block that displays
 * a list of the most recent documents.
 *
 * @package WP_Document_Revisions
 */

// Mock react/jsx-runtime (babel automatic runtime target).
jest.mock( 'react/jsx-runtime', () => ( {
	jsx: jest.fn( ( ...args ) => args ),
	jsxs: jest.fn( ( ...args ) => args ),
	Fragment: Symbol( 'Fragment' ),
} ) );

// Mock WordPress packages (virtual — not installed locally).
jest.mock(
	'@wordpress/blocks',
	() => ( {
		registerBlockType: jest.fn(),
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
		TextControl: 'TextControl',
		TextareaControl: 'TextareaControl',
		ToggleControl: 'ToggleControl',
		CheckboxControl: 'CheckboxControl',
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/server-side-render', () => 'ServerSideRender', {
	virtual: true,
} );

jest.mock(
	'@wordpress/i18n',
	() => ( {
		__: jest.fn( ( text ) => text ),
	} ),
	{ virtual: true }
);

import { jsx, jsxs } from 'react/jsx-runtime';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Return all JSX calls from both jsx() and jsxs() mocks.
 *
 * @return {Array} Combined array of mock call arguments.
 */
function getAllJsxCalls() {
	return [ ...jsx.mock.calls, ...jsxs.mock.calls ];
}

/**
 * Find the first JSX call whose component type matches the given value.
 *
 * @param {Array}  calls    Combined JSX mock calls.
 * @param {string} typeName Component type to search for.
 * @return {Array|undefined} The matching call arguments, or undefined.
 */
function findCall( calls, typeName ) {
	return calls.find( ( call ) => call[ 0 ] === typeName );
}

/**
 * Find every JSX call whose component type matches the given value.
 *
 * @param {Array}  calls    Combined JSX mock calls.
 * @param {string} typeName Component type to search for.
 * @return {Array} Matching call arguments.
 */
function findAllCalls( calls, typeName ) {
	return calls.filter( ( call ) => call[ 0 ] === typeName );
}

// ─── Shared state ────────────────────────────────────────────────────────────────────

let metadata;
let blockConfig;

beforeAll( () => {
	require( '../../src/blocks/documents-widget/index.js' );
	metadata = registerBlockType.mock.calls[ 0 ][ 0 ];
	blockConfig = registerBlockType.mock.calls[ 0 ][ 1 ];
} );

// ─── Block Registration ─────────────────────────────────────────────────────────────

describe( 'Block Registration', () => {
	test( 'registers block with correct name', () => {
		expect( metadata.name ).toBe(
			'wp-document-revisions/documents-widget'
		);
	} );

	test( 'has correct title', () => {
		expect( metadata.title ).toBe( 'Latest Documents' );
	} );

	test( 'has correct description', () => {
		expect( metadata.description ).toBe(
			'Display a list of your most recent documents.'
		);
	} );

	test( 'has correct category', () => {
		expect( metadata.category ).toBe( 'wpdr-category' );
	} );

	test( 'has correct icon', () => {
		expect( metadata.icon ).toBe( 'admin-page' );
	} );

	test( 'provides an edit function', () => {
		expect( typeof blockConfig.edit ).toBe( 'function' );
	} );

	test( 'provides a save function', () => {
		expect( typeof blockConfig.save ).toBe( 'function' );
	} );
} );

// ─── Block Attributes ───────────────────────────────────────────────────────────────

describe( 'Block Attributes', () => {
	test( 'defines header attribute as string', () => {
		expect( metadata.attributes.header ).toEqual( { type: 'string' } );
	} );

	test( 'defines numberposts attribute as number', () => {
		expect( metadata.attributes.numberposts.type ).toBe( 'number' );
	} );

	test( 'defines boolean status attributes', () => {
		expect( metadata.attributes.post_stat_publish.type ).toBe(
			'boolean'
		);
		expect( metadata.attributes.post_stat_private.type ).toBe(
			'boolean'
		);
		expect( metadata.attributes.post_stat_draft.type ).toBe( 'boolean' );
	} );

	test( 'defines boolean display attributes', () => {
		expect( metadata.attributes.show_thumb.type ).toBe( 'boolean' );
		expect( metadata.attributes.show_descr.type ).toBe( 'boolean' );
		expect( metadata.attributes.show_author.type ).toBe( 'boolean' );
		expect( metadata.attributes.show_pdf.type ).toBe( 'boolean' );
		expect( metadata.attributes.new_tab.type ).toBe( 'boolean' );
	} );

	test( 'defines styling attributes', () => {
		expect( metadata.attributes.align.type ).toBe( 'string' );
		expect( metadata.attributes.backgroundColor.type ).toBe( 'string' );
		expect( metadata.attributes.linkColor.type ).toBe( 'string' );
		expect( metadata.attributes.textColor.type ).toBe( 'string' );
		expect( metadata.attributes.gradient.type ).toBe( 'string' );
		expect( metadata.attributes.fontSize.type ).toBe( 'string' );
		expect( metadata.attributes.style.type ).toBe( 'object' );
	} );
} );

// ─── Block Supports ─────────────────────────────────────────────────────────────────

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

	test( 'supports spacing (margin and padding)', () => {
		expect( metadata.supports.spacing ).toEqual( {
			margin: true,
			padding: true,
		} );
	} );

	test( 'supports typography (fontSize and lineHeight)', () => {
		expect( metadata.supports.typography ).toEqual( {
			fontSize: true,
			lineHeight: true,
		} );
	} );
} );

// ─── Attribute Defaults ─────────────────────────────────────────────────────────────

describe( 'Attribute Defaults', () => {
	test( 'header has no default', () => {
		expect( metadata.attributes.header.default ).toBeUndefined();
	} );

	test( 'numberposts defaults to 5', () => {
		expect( metadata.attributes.numberposts.default ).toBe( 5 );
	} );

	test( 'post_stat_publish defaults to true', () => {
		expect( metadata.attributes.post_stat_publish.default ).toBe( true );
	} );

	test( 'post_stat_private defaults to true', () => {
		expect( metadata.attributes.post_stat_private.default ).toBe( true );
	} );

	test( 'post_stat_draft defaults to false', () => {
		expect( metadata.attributes.post_stat_draft.default ).toBe( false );
	} );

	test( 'show_thumb defaults to false', () => {
		expect( metadata.attributes.show_thumb.default ).toBe( false );
	} );

	test( 'show_descr defaults to true', () => {
		expect( metadata.attributes.show_descr.default ).toBe( true );
	} );

	test( 'show_author defaults to true', () => {
		expect( metadata.attributes.show_author.default ).toBe( true );
	} );

	test( 'show_pdf defaults to false', () => {
		expect( metadata.attributes.show_pdf.default ).toBe( false );
	} );

	test( 'new_tab defaults to false', () => {
		expect( metadata.attributes.new_tab.default ).toBe( false );
	} );
} );

// ─── Block Save Function ──────────────────────────────────────────────────────────────

describe( 'Block Save Function', () => {
	test( 'returns null (server-side rendered)', () => {
		expect( blockConfig.save() ).toBeNull();
	} );
} );

// ─── Edit Function – JSX Rendering ──────────────────────────────────────────────────────

describe( 'Edit Function - JSX Rendering', () => {
	const defaultAttributes = {
		header: 'Recent Documents',
		numberposts: 5,
		post_stat_publish: true,
		post_stat_private: true,
		post_stat_draft: false,
		show_thumb: false,
		show_descr: true,
		show_author: true,
		show_pdf: false,
		new_tab: false,
	};

	beforeEach( () => {
		jsx.mockClear();
		jsxs.mockClear();
	} );

	test( 'renders without throwing', () => {
		expect( () => {
			blockConfig.edit( {
				attributes: defaultAttributes,
				setAttributes: jest.fn(),
				className: 'test-class',
			} );
		} ).not.toThrow();
	} );

	test( 'renders ServerSideRender with correct block name', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const ssrCall = findCall( calls, 'ServerSideRender' );

		expect( ssrCall ).toBeDefined();
		expect( ssrCall[ 1 ].block ).toBe(
			'wp-document-revisions/documents-widget'
		);
		expect( ssrCall[ 1 ].attributes ).toBe( defaultAttributes );
	} );

	test( 'renders InspectorControls', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const inspectorCall = findCall( calls, 'InspectorControls' );

		expect( inspectorCall ).toBeDefined();
	} );

	test( 'renders PanelBody with correct title', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const panelCall = findCall( calls, 'PanelBody' );

		expect( panelCall ).toBeDefined();
		expect( panelCall[ 1 ].title ).toBe( 'Latest Documents Settings' );
		expect( panelCall[ 1 ].initialOpen ).toBe( true );
	} );

	test( 'renders TextControl for header', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const textCall = findCall( calls, 'TextControl' );

		expect( textCall ).toBeDefined();
		expect( textCall[ 1 ].value ).toBe( 'Recent Documents' );
		expect( textCall[ 1 ].label ).toBe(
			'Latest Documents List Heading'
		);
		expect( textCall[ 1 ].type ).toBe( 'string' );
	} );

	test( 'renders RangeControl for numberposts', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const rangeCall = findCall( calls, 'RangeControl' );

		expect( rangeCall ).toBeDefined();
		expect( rangeCall[ 1 ].value ).toBe( 5 );
		expect( rangeCall[ 1 ].label ).toBe( 'Documents to Display' );
		expect( rangeCall[ 1 ].min ).toBe( 1 );
		expect( rangeCall[ 1 ].max ).toBe( 25 );
	} );

	test( 'renders three CheckboxControls for document statuses', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );

		expect( checkboxCalls ).toHaveLength( 3 );

		const labels = checkboxCalls.map( ( call ) => call[ 1 ].label );
		expect( labels ).toContain( 'Publish' );
		expect( labels ).toContain( 'Private' );
		expect( labels ).toContain( 'Draft' );
	} );

	test( 'renders CheckboxControls with correct checked values', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );

		const publishCb = checkboxCalls.find(
			( c ) => c[ 1 ].label === 'Publish'
		);
		const privateCb = checkboxCalls.find(
			( c ) => c[ 1 ].label === 'Private'
		);
		const draftCb = checkboxCalls.find(
			( c ) => c[ 1 ].label === 'Draft'
		);

		expect( publishCb[ 1 ].checked ).toBe( true );
		expect( privateCb[ 1 ].checked ).toBe( true );
		expect( draftCb[ 1 ].checked ).toBe( false );
	} );

	test( 'renders ToggleControls for display options', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );

		expect( toggleCalls.length ).toBe( 5 );

		const labels = toggleCalls.map( ( call ) => call[ 1 ].label );
		expect( labels ).toContain( 'Show featured image?' );
		expect( labels ).toContain( 'Show document description?' );
		expect( labels ).toContain( 'Show author name?' );
		expect( labels ).toContain( 'Show PDF File indication?' );
		expect( labels ).toContain( 'Open documents in new tab?' );
	} );

	test( 'renders ToggleControls with correct checked values', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );

		const byLabel = ( label ) =>
			toggleCalls.find( ( c ) => c[ 1 ].label === label );

		expect( byLabel( 'Show featured image?' )[ 1 ].checked ).toBe(
			false
		);
		expect(
			byLabel( 'Show document description?' )[ 1 ].checked
		).toBe( true );
		expect( byLabel( 'Show author name?' )[ 1 ].checked ).toBe( true );
		expect(
			byLabel( 'Show PDF File indication?' )[ 1 ].checked
		).toBe( false );
		expect(
			byLabel( 'Open documents in new tab?' )[ 1 ].checked
		).toBe( false );
	} );

	test( 'show_thumb toggle includes help text', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const thumbToggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Show featured image?'
		);

		expect( thumbToggle[ 1 ].help ).toBeDefined();
		expect( thumbToggle[ 1 ].help ).toContain( 'Under certain' );
	} );

	test( 'new_tab toggle includes help text', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const newTabToggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Open documents in new tab?'
		);

		expect( newTabToggle[ 1 ].help ).toBeDefined();
		expect( newTabToggle[ 1 ].help ).toContain( 'Setting this on' );
	} );

	test( 'renders wrapper div with blockProps', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const divCalls = findAllCalls( calls, 'div' );
		const blockDiv = divCalls.find(
			( c ) => c[ 1 ].className === 'wp-block'
		);

		expect( blockDiv ).toBeDefined();
	} );

	test( 'renders status wrapper div with className prop', () => {
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const divCalls = findAllCalls( calls, 'div' );
		const statusDiv = divCalls.find(
			( c ) => c[ 1 ].className === 'test-class'
		);

		expect( statusDiv ).toBeDefined();
	} );
} );

// ─── Edit Function – onChange Callbacks ──────────────────────────────────────────────────

describe( 'Edit Function - onChange Callbacks', () => {
	const defaultAttributes = {
		header: 'Recent Documents',
		numberposts: 5,
		post_stat_publish: true,
		post_stat_private: true,
		post_stat_draft: false,
		show_thumb: false,
		show_descr: true,
		show_author: true,
		show_pdf: false,
		new_tab: false,
	};

	let setAttributes;

	beforeEach( () => {
		jsx.mockClear();
		jsxs.mockClear();
		setAttributes = jest.fn();
		blockConfig.edit( {
			attributes: defaultAttributes,
			setAttributes,
			className: 'test-class',
		} );
	} );

	test( 'header onChange calls setAttributes correctly', () => {
		const calls = getAllJsxCalls();
		const textCall = findCall( calls, 'TextControl' );

		textCall[ 1 ].onChange( 'New Heading' );

		expect( setAttributes ).toHaveBeenCalledWith( {
			header: 'New Heading',
		} );
	} );

	test( 'numberposts onChange calls setAttributes with parseInt', () => {
		const calls = getAllJsxCalls();
		const rangeCall = findCall( calls, 'RangeControl' );

		rangeCall[ 1 ].onChange( 10 );

		expect( setAttributes ).toHaveBeenCalledWith( {
			numberposts: 10,
		} );
	} );

	test( 'numberposts onChange parses string values', () => {
		const calls = getAllJsxCalls();
		const rangeCall = findCall( calls, 'RangeControl' );

		rangeCall[ 1 ].onChange( '7' );

		expect( setAttributes ).toHaveBeenCalledWith( {
			numberposts: 7,
		} );
	} );

	test( 'post_stat_publish onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );
		const publishCb = checkboxCalls.find(
			( c ) => c[ 1 ].label === 'Publish'
		);

		publishCb[ 1 ].onChange( false );

		expect( setAttributes ).toHaveBeenCalledWith( {
			post_stat_publish: false,
		} );
	} );

	test( 'post_stat_private onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );
		const privateCb = checkboxCalls.find(
			( c ) => c[ 1 ].label === 'Private'
		);

		privateCb[ 1 ].onChange( false );

		expect( setAttributes ).toHaveBeenCalledWith( {
			post_stat_private: false,
		} );
	} );

	test( 'post_stat_draft onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );
		const draftCb = checkboxCalls.find(
			( c ) => c[ 1 ].label === 'Draft'
		);

		draftCb[ 1 ].onChange( true );

		expect( setAttributes ).toHaveBeenCalledWith( {
			post_stat_draft: true,
		} );
	} );

	test( 'show_thumb onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const toggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Show featured image?'
		);

		toggle[ 1 ].onChange( true );

		expect( setAttributes ).toHaveBeenCalledWith( {
			show_thumb: true,
		} );
	} );

	test( 'show_descr onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const toggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Show document description?'
		);

		toggle[ 1 ].onChange( false );

		expect( setAttributes ).toHaveBeenCalledWith( {
			show_descr: false,
		} );
	} );

	test( 'show_author onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const toggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Show author name?'
		);

		toggle[ 1 ].onChange( false );

		expect( setAttributes ).toHaveBeenCalledWith( {
			show_author: false,
		} );
	} );

	test( 'show_pdf onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const toggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Show PDF File indication?'
		);

		toggle[ 1 ].onChange( true );

		expect( setAttributes ).toHaveBeenCalledWith( {
			show_pdf: true,
		} );
	} );

	test( 'new_tab onChange calls setAttributes', () => {
		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const toggle = toggleCalls.find(
			( c ) => c[ 1 ].label === 'Open documents in new tab?'
		);

		toggle[ 1 ].onChange( true );

		expect( setAttributes ).toHaveBeenCalledWith( {
			new_tab: true,
		} );
	} );
} );

// ─── Edge Cases ───────────────────────────────────────────────────────────────────

describe( 'Edge Cases', () => {
	beforeEach( () => {
		jsx.mockClear();
		jsxs.mockClear();
	} );

	test( 'renders with undefined header', () => {
		const attrs = {
			header: undefined,
			numberposts: 5,
			post_stat_publish: true,
			post_stat_private: true,
			post_stat_draft: false,
			show_thumb: false,
			show_descr: true,
			show_author: true,
			show_pdf: false,
			new_tab: false,
		};

		expect( () => {
			blockConfig.edit( {
				attributes: attrs,
				setAttributes: jest.fn(),
				className: 'test-class',
			} );
		} ).not.toThrow();

		const calls = getAllJsxCalls();
		const textCall = findCall( calls, 'TextControl' );
		expect( textCall[ 1 ].value ).toBeUndefined();
	} );

	test( 'renders with minimum numberposts value', () => {
		const attrs = {
			header: 'Test',
			numberposts: 1,
			post_stat_publish: true,
			post_stat_private: true,
			post_stat_draft: false,
			show_thumb: false,
			show_descr: true,
			show_author: true,
			show_pdf: false,
			new_tab: false,
		};

		blockConfig.edit( {
			attributes: attrs,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const rangeCall = findCall( calls, 'RangeControl' );
		expect( rangeCall[ 1 ].value ).toBe( 1 );
	} );

	test( 'renders with maximum numberposts value', () => {
		const attrs = {
			header: 'Test',
			numberposts: 25,
			post_stat_publish: true,
			post_stat_private: true,
			post_stat_draft: false,
			show_thumb: false,
			show_descr: true,
			show_author: true,
			show_pdf: false,
			new_tab: false,
		};

		blockConfig.edit( {
			attributes: attrs,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const rangeCall = findCall( calls, 'RangeControl' );
		expect( rangeCall[ 1 ].value ).toBe( 25 );
	} );

	test( 'renders with all boolean attributes true', () => {
		const attrs = {
			header: 'All On',
			numberposts: 10,
			post_stat_publish: true,
			post_stat_private: true,
			post_stat_draft: true,
			show_thumb: true,
			show_descr: true,
			show_author: true,
			show_pdf: true,
			new_tab: true,
		};

		blockConfig.edit( {
			attributes: attrs,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );

		toggleCalls.forEach( ( call ) => {
			expect( call[ 1 ].checked ).toBe( true );
		} );

		checkboxCalls.forEach( ( call ) => {
			expect( call[ 1 ].checked ).toBe( true );
		} );
	} );

	test( 'renders with all boolean attributes false', () => {
		const attrs = {
			header: 'All Off',
			numberposts: 3,
			post_stat_publish: false,
			post_stat_private: false,
			post_stat_draft: false,
			show_thumb: false,
			show_descr: false,
			show_author: false,
			show_pdf: false,
			new_tab: false,
		};

		blockConfig.edit( {
			attributes: attrs,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const toggleCalls = findAllCalls( calls, 'ToggleControl' );
		const checkboxCalls = findAllCalls( calls, 'CheckboxControl' );

		toggleCalls.forEach( ( call ) => {
			expect( call[ 1 ].checked ).toBe( false );
		} );

		checkboxCalls.forEach( ( call ) => {
			expect( call[ 1 ].checked ).toBe( false );
		} );
	} );

	test( 'renders with empty string header', () => {
		const attrs = {
			header: '',
			numberposts: 5,
			post_stat_publish: true,
			post_stat_private: true,
			post_stat_draft: false,
			show_thumb: false,
			show_descr: true,
			show_author: true,
			show_pdf: false,
			new_tab: false,
		};

		blockConfig.edit( {
			attributes: attrs,
			setAttributes: jest.fn(),
			className: 'test-class',
		} );

		const calls = getAllJsxCalls();
		const textCall = findCall( calls, 'TextControl' );
		expect( textCall[ 1 ].value ).toBe( '' );
	} );
} );
