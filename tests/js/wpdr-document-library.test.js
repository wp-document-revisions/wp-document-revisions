/**
 * Tests for the Document Library block's shortcode transforms.
 *
 * @package
 */

import {
	attributesToShortcode,
	shortcodeToAttributes,
} from '../../src/blocks/document-library/transforms';

describe( 'document-library shortcodeToAttributes', () => {
	it( 'reads layout, fields, numbers and order', () => {
		expect(
			shortcodeToAttributes(
				'[document_library layout="table" fields="title,modified" numberposts="5" columns="4" orderby="title" order="asc"]'
			)
		).toEqual( {
			layout: 'table',
			fields: [ 'title', 'modified' ],
			numberposts: 5,
			columns: 4,
			orderby: 'title',
			order: 'ASC',
		} );
	} );

	it( 'treats other keys as taxonomy term IDs', () => {
		expect(
			shortcodeToAttributes( '[document_library category="4,9" workflow_state="2"]' )
		).toEqual( {
			taxonomies: { category: [ 4, 9 ], workflow_state: [ 2 ] },
		} );
	} );

	it( 'drops non-numeric terms', () => {
		expect( shortcodeToAttributes( '[document_library category="final"]' ) ).toEqual( {} );
	} );

	it( 'accepts new_tab as a bare flag or a value', () => {
		expect( shortcodeToAttributes( '[document_library new_tab]' ) ).toEqual( {
			new_tab: true,
		} );
		expect( shortcodeToAttributes( '[document_library new_tab="false"]' ) ).toEqual( {
			new_tab: false,
		} );
	} );
} );

describe( 'document-library attributesToShortcode', () => {
	const defaults = {
		layout: 'list',
		columns: 3,
		fields: [ 'title', 'file_type', 'author', 'modified', 'download' ],
		taxonomies: {},
		orderby: 'modified',
		order: 'DESC',
		numberposts: 10,
		new_tab: false,
	};

	it( 'serializes the defaults', () => {
		expect( attributesToShortcode( defaults ) ).toBe(
			'[document_library layout="list" fields="title,file_type,author,modified,download" orderby="modified" order="DESC" numberposts="10"]'
		);
	} );

	it( 'includes columns only for the grid layout, plus taxonomies and new_tab', () => {
		expect(
			attributesToShortcode( {
				...defaults,
				layout: 'grid',
				columns: 4,
				taxonomies: { category: [ 4, 9 ], empty: [] },
				new_tab: true,
			} )
		).toBe(
			'[document_library layout="grid" columns="4" fields="title,file_type,author,modified,download" category="4,9" orderby="modified" order="DESC" numberposts="10" new_tab="true"]'
		);
	} );

	it( 'round-trips through the shortcode', () => {
		const attributes = {
			...defaults,
			layout: 'table',
			taxonomies: { category: [ 4 ] },
			numberposts: 25,
		};
		const parsed = shortcodeToAttributes( attributesToShortcode( attributes ) );
		expect( { ...defaults, ...parsed } ).toEqual( attributes );
	} );
} );
