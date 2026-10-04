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
	it( 'reads variant, fields, numbers and order', () => {
		expect(
			shortcodeToAttributes(
				'[document_library variant="table" fields="title,modified" numberposts="5" columns="4" orderby="title" order="asc"]'
			)
		).toEqual( {
			variant: 'table',
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

	it( 'keeps slugs as strings, like the PHP shortcode', () => {
		expect(
			shortcodeToAttributes(
				'[document_library workflow_state="final" category="design, 4,2024-reports,"]'
			)
		).toEqual( {
			taxonomies: { workflow_state: [ 'final' ], category: [ 'design', 4, '2024-reports' ] },
		} );
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
		variant: 'list',
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
			'[document_library variant="list" fields="title,file_type,author,modified,download" orderby="modified" order="DESC" numberposts="10"]'
		);
	} );

	it( 'includes columns only for the grid layout, plus taxonomies and new_tab', () => {
		expect(
			attributesToShortcode( {
				...defaults,
				variant: 'grid',
				columns: 4,
				taxonomies: { category: [ 4, 9 ], empty: [] },
				new_tab: true,
			} )
		).toBe(
			'[document_library variant="grid" columns="4" fields="title,file_type,author,modified,download" category="4,9" orderby="modified" order="DESC" numberposts="10" new_tab="true"]'
		);
	} );

	it( 'round-trips through the shortcode', () => {
		const attributes = {
			...defaults,
			variant: 'table',
			taxonomies: { category: [ 4 ], workflow_state: [ 'final', '2024-reports' ] },
			numberposts: 25,
		};
		const parsed = shortcodeToAttributes( attributesToShortcode( attributes ) );
		expect( { ...defaults, ...parsed } ).toEqual( attributes );
	} );
} );
