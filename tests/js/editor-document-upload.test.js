/**
 * Tests for src/editor-document-upload/index.js
 *
 * Covers the module's pure helpers — file-extension resolution and the
 * relative-time formatter — which hold the non-trivial branching. The React
 * sidebar components (hooks, media frame, REST fetch) are exercised by the
 * Playwright E2E suite; here we mock the @wordpress/* runtime packages
 * (webpack externals, not installed in node_modules) purely so the module
 * loads and its named exports can be imported.
 */

// The block-editor runtime packages aren't installed under Jest; virtual-mock
// each import the module pulls in so requiring it does not throw.
jest.mock( 'react/jsx-runtime', () => ( {
	jsx: jest.fn(),
	jsxs: jest.fn(),
	Fragment: Symbol( 'Fragment' ),
} ) );
jest.mock( '@wordpress/plugins', () => ( { registerPlugin: jest.fn() } ), { virtual: true } );
jest.mock(
	'@wordpress/editor',
	() => ( { PluginDocumentSettingPanel: 'PluginDocumentSettingPanel' } ),
	{ virtual: true }
);
jest.mock( '@wordpress/block-editor', () => ( { MediaUploadCheck: 'MediaUploadCheck' } ), {
	virtual: true,
} );
jest.mock(
	'@wordpress/components',
	() => ( { Button: 'Button', Spinner: 'Spinner', TextareaControl: 'TextareaControl' } ),
	{ virtual: true }
);
jest.mock( '@wordpress/core-data', () => ( { useEntityProp: jest.fn( () => [ null, jest.fn() ] ) } ), {
	virtual: true,
} );
jest.mock(
	'@wordpress/data',
	() => ( { useSelect: jest.fn(), useDispatch: jest.fn( () => ( {} ) ) } ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/element',
	() => ( {
		useEffect: jest.fn(),
		useState: jest.fn( () => [ null, jest.fn() ] ),
		useCallback: jest.fn( ( fn ) => fn ),
		useRef: jest.fn( () => ( { current: null } ) ),
	} ),
	{ virtual: true }
);
jest.mock( '@wordpress/i18n', () => ( { __: jest.fn( ( text ) => text ) } ), { virtual: true } );
jest.mock( '@wordpress/notices', () => ( { store: 'notices' } ), { virtual: true } );

const { getFileExtension, formatDate } = require( '../../src/editor-document-upload/index.js' );

describe( 'getFileExtension', () => {
	test( 'prefers the filename extension, uppercased', () => {
		expect( getFileExtension( { filename: 'contract.pdf' } ) ).toBe( 'PDF' );
		expect( getFileExtension( { filename: 'Report.DocX' } ) ).toBe( 'DOCX' );
	} );

	test( 'uses the last dot segment for multi-dot filenames', () => {
		expect( getFileExtension( { filename: 'archive.tar.gz' } ) ).toBe( 'GZ' );
	} );

	test( 'falls back to source_url when no filename', () => {
		expect( getFileExtension( { source_url: 'https://x.test/wp-content/a.xlsx' } ) ).toBe(
			'XLSX'
		);
	} );

	test( 'strips a query string before parsing source_url', () => {
		expect( getFileExtension( { source_url: 'https://x.test/a.csv?ver=2' } ) ).toBe( 'CSV' );
	} );

	test( 'ignores a source_url "extension" longer than 5 chars', () => {
		// No real extension → the long trailing segment is rejected, empty result.
		expect( getFileExtension( { source_url: 'https://x.test/document' } ) ).toBe( '' );
	} );

	test( 'maps known MIME subtypes to friendly labels', () => {
		expect( getFileExtension( { mime_type: 'application/pdf' } ) ).toBe( 'PDF' );
		expect( getFileExtension( { mime_type: 'application/msword' } ) ).toBe( 'DOC' );
		expect(
			getFileExtension( {
				mime_type:
					'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			} )
		).toBe( 'DOCX' );
		expect( getFileExtension( { mime_type: 'text/plain' } ) ).toBe( 'TXT' );
	} );

	test( 'uppercases an unknown MIME subtype', () => {
		expect( getFileExtension( { mime_type: 'application/x-custom' } ) ).toBe( 'X-CUSTOM' );
	} );

	test( 'returns empty string for missing / empty media', () => {
		expect( getFileExtension( null ) ).toBe( '' );
		expect( getFileExtension( undefined ) ).toBe( '' );
		expect( getFileExtension( {} ) ).toBe( '' );
	} );

	test( 'filename takes precedence over mime_type', () => {
		expect( getFileExtension( { filename: 'a.pdf', mime_type: 'application/msword' } ) ).toBe(
			'PDF'
		);
	} );
} );

describe( 'formatDate', () => {
	const now = new Date( '2021-01-01T12:00:00Z' );
	const ago = ( ms ) => new Date( now.getTime() - ms ).toISOString();
	const MIN = 60000;
	const HOUR = 60 * MIN;
	const DAY = 24 * HOUR;

	test( 'returns "just now" under a minute', () => {
		expect( formatDate( ago( 30 * 1000 ), now ) ).toBe( 'just now' );
	} );

	test( 'returns minutes for under an hour', () => {
		expect( formatDate( ago( 5 * MIN ), now ) ).toBe( '5 min ago' );
	} );

	test( 'uses singular hour and plural hours', () => {
		expect( formatDate( ago( 1 * HOUR ), now ) ).toBe( '1 hour ago' );
		expect( formatDate( ago( 5 * HOUR ), now ) ).toBe( '5 hours ago' );
	} );

	test( 'uses singular day and plural days', () => {
		expect( formatDate( ago( 1 * DAY ), now ) ).toBe( '1 day ago' );
		expect( formatDate( ago( 10 * DAY ), now ) ).toBe( '10 days ago' );
	} );

	test( 'falls back to a locale date string at 30+ days', () => {
		const old = new Date( '2020-01-01T12:00:00Z' );
		expect( formatDate( old.toISOString(), now ) ).toBe( old.toLocaleDateString() );
	} );
} );
