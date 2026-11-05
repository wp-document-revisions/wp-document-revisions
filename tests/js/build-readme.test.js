/**
 * Tests for script/build-readme, which builds readme.txt (the WordPress.org
 * plugin readme) from docs/*.md.
 */

const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const { buildReadme } = require( '../../script/build-readme' );

const root = path.resolve( __dirname, '../..' );
const DOCS_URL = 'https://wp-document-revisions.github.io/wp-document-revisions/';

/**
 * Writes a minimal repository with the given docs/ files to a temp directory.
 *
 * @param {Object<string, string>} files Map of docs/ file name to contents.
 * @return {string} The temp repository root.
 */
function fixture( files ) {
	const dir = fs.mkdtempSync( path.join( os.tmpdir(), 'wpdr-readme-' ) );
	const docs = path.join( dir, 'docs' );
	fs.mkdirSync( docs );
	const defaults = {
		'header.md': '# Plugin Name\nStable tag: 1.0.0\n\nShort description.\n',
		'README.md': '# Title\n\nBadges and tagline.\n\n## About\n\nAbout text.\n',
		'features.md': '## Features\n\nFeature list.\n',
		'changelog.md': '## Changelog\n\n### 1.0.0\n\n* First.\n',
	};
	for ( const [ name, contents ] of Object.entries( { ...defaults, ...files } ) ) {
		fs.writeFileSync( path.join( docs, name ), contents );
	}
	return dir;
}

describe( 'build-readme', () => {
	const dirs = [];
	const build = ( files ) => {
		const dir = fixture( files );
		dirs.push( dir );
		return buildReadme( dir );
	};

	afterAll( () => {
		for ( const dir of dirs ) {
			fs.rmSync( dir, { recursive: true, force: true } );
		}
	} );

	test( 'reproduces the committed readme.txt from docs/', () => {
		expect( buildReadme( root ) ).toBe(
			fs.readFileSync( path.join( root, 'readme.txt' ), 'utf8' )
		);
	} );

	test( 'starts with the header, then a Description section', () => {
		const output = build();
		expect( output.startsWith( '=== Plugin Name ===\nStable tag: 1.0.0' ) ).toBe( true );
		expect( output ).toContain( 'Short description.\n\n== Description ==\n' );
	} );

	test( 'drops the README title and tagline but keeps its later sections', () => {
		const output = build();
		expect( output ).not.toContain( 'Badges and tagline.' );
		expect( output ).not.toContain( '=== Title ===' );
		expect( output ).toContain( '== About ==\n\nAbout text.' );
	} );

	test( 'puts features.md first and the other docs in byte order', () => {
		// Directory listing order depends on the file system, so hand the script
		// a reversed listing to prove it sorts rather than relying on luck.
		const readdirSync = fs.readdirSync;
		const spy = jest
			.spyOn( fs, 'readdirSync' )
			.mockImplementation( ( ...args ) => readdirSync( ...args ).reverse() );
		let output;
		try {
			output = build( {
				'zebra.md': 'Zebra doc.\n',
				'apple.md': 'Apple doc.\n',
				'Upper.md': 'Upper doc.\n',
			} );
		} finally {
			spy.mockRestore();
		}
		const order = [ 'Feature list.', 'Upper doc.', 'Apple doc.', 'Zebra doc.' ].map(
			( text ) => output.indexOf( text )
		);
		expect( order.every( ( index ) => index !== -1 ) ).toBe( true );
		expect( [ ...order ].sort( ( a, b ) => a - b ) ).toEqual( order );
	} );

	test( 'skips excluded docs and subdirectories', () => {
		const dir = fixture( {
			'filters.md': 'Filter reference.\n',
			'CONTRIBUTING.md': 'How to contribute.\n',
			'installation.md': 'Install steps.\n',
		} );
		dirs.push( dir );
		fs.mkdirSync( path.join( dir, 'docs', 'cookbook' ) );
		fs.writeFileSync( path.join( dir, 'docs', 'cookbook', 'recipe.md' ), 'Recipe.\n' );

		const output = buildReadme( dir );
		expect( output ).toContain( 'Install steps.' );
		expect( output ).not.toContain( 'Filter reference.' );
		expect( output ).not.toContain( 'How to contribute.' );
		expect( output ).not.toContain( 'Recipe.' );
	} );

	test( 'keeps only the three most recent changelog versions', () => {
		const output = build( {
			'changelog.md': [
				'## Changelog',
				'',
				'### 4.0.0',
				'* Four.',
				'### 3.0.0',
				'* Three.',
				'### 2.0.0',
				'* Two.',
				'### 1.0.0',
				'* One.',
				'',
			].join( '\n' ),
		} );
		expect( output ).toContain( '= 4.0.0 =' );
		expect( output ).toContain( '= 3.0.0 =' );
		expect( output ).toContain( '= 2.0.0 =' );
		expect( output ).not.toContain( '* Two.' );
		expect( output ).not.toContain( '1.0.0 =' );
		expect( output ).toContain(
			`For complete changelog, see [GitHub](${ DOCS_URL }changelog/)`
		);
	} );

	test( 'converts Markdown headings to readme.txt headings', () => {
		const output = build( { 'installation.md': '# One\n## Two\n### Three\n' } );
		expect( output ).toContain( '=== One ===\n== Two ==\n= Three =\n' );
	} );

	test( 'rewrites links to docs/*.md as links to the docs site', () => {
		const output = build( {
			'installation.md': 'See [filters](./filters.md) and [FAQ](frequently-asked-questions.md).\n',
		} );
		expect( output ).toContain( `[filters](${ DOCS_URL }filters/)` );
		expect( output ).toContain( `[FAQ](${ DOCS_URL }frequently-asked-questions/)` );
	} );

	// Ruby's String#split, which the script was ported from, drops trailing
	// empty pieces, so a README ending in "#" loses those characters.
	test( 'drops trailing # characters from the README like the Ruby original', () => {
		const output = build( { 'README.md': '# Title\n\nTagline.\n\n## About\n\nText.\n##' } );
		expect( output ).toContain( '== About ==\n\nText.\n\n\n' );
		expect( output ).not.toMatch( /Text\.\n#/ );
	} );
} );
