/**
 * Tests for src/blocks/shared/parse-shortcode.js
 *
 * The tokenizer feeds the [documents], [document_revisions] and
 * [document_preview] block "from shortcode" transforms, so its contract (bracket/tag stripping, quote
 * removal, bare-flag pairs) is covered here directly.
 */

const {
	parseShortcodeParams,
} = require( '../../src/blocks/shared/parse-shortcode.js' );

describe( 'parseShortcodeParams', () => {
	test( 'strips the brackets and the leading tag name', () => {
		expect( parseShortcodeParams( '[documents numberposts=3]' ) ).toEqual( [
			[ 'numberposts', '3' ],
		] );
	} );

	test( 'parses without brackets too (raw inner text)', () => {
		expect( parseShortcodeParams( 'documents numberposts=3' ) ).toEqual( [
			[ 'numberposts', '3' ],
		] );
	} );

	test( 'removes matching double and single quotes from values', () => {
		expect( parseShortcodeParams( '[documents a="x" b=\'y\']' ) ).toEqual( [
			[ 'a', 'x' ],
			[ 'b', 'y' ],
		] );
	} );

	test( 'returns a single-element pair for a bare flag', () => {
		const params = parseShortcodeParams( '[documents show_pdf]' );
		expect( params ).toEqual( [ [ 'show_pdf' ] ] );
		expect( params[ 0 ] ).toHaveLength( 1 );
	} );

	test( 'lowercases the whole shortcode', () => {
		expect( parseShortcodeParams( '[Documents OrderBy=Title]' ) ).toEqual( [
			[ 'orderby', 'title' ],
		] );
	} );

	test( 'skips empty tokens from doubled spaces', () => {
		expect( parseShortcodeParams( '[documents  a=1   b=2]' ) ).toEqual( [
			[ 'a', '1' ],
			[ 'b', '2' ],
		] );
	} );

	test( 'returns an empty array for a bare shortcode', () => {
		expect( parseShortcodeParams( '[documents]' ) ).toEqual( [] );
	} );

	test( 'returns named params in order, then bare flags', () => {
		expect(
			parseShortcodeParams( '[document_revisions id=7 summary show_pdf=true]' )
		).toEqual( [ [ 'id', '7' ], [ 'show_pdf', 'true' ], [ 'summary' ] ] );
	} );

	test( 'keeps quoted values containing spaces together', () => {
		expect(
			parseShortcodeParams( '[documents meta_value="two words" b=\'x y\']' )
		).toEqual( [
			[ 'meta_value', 'two words' ],
			[ 'b', 'x y' ],
		] );
	} );
} );
