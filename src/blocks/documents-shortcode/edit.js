import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RadioControl,
	RangeControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

/* global wpdr_data */

// Attribute name maps keyed by taxonomy index.
const TAXONOMY_KEYS = [ 'taxonomy_0', 'taxonomy_1', 'taxonomy_2' ];
const TERM_KEYS = [ 'term_0', 'term_1', 'term_2' ];

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	const taxo = wpdr_data.taxos;

	// Synchronise taxonomy slug attributes and handle reordering.
	// Runs as an effect so we never call setAttributes during render.
	useEffect( () => {
		const updates = {};

		for ( let i = 0; i < wpdr_data.stmax && i < 3; i++ ) {
			const currentSlug = attributes[ TAXONOMY_KEYS[ i ] ];

			if ( '' !== currentSlug && currentSlug !== taxo[ i ].query ) {
				// Taxonomy was reordered — move old selection to freeform.
				const slug = idToSlug( i, currentSlug, attributes[ TERM_KEYS[ i ] ] );
				updates.freeform = ( updates.freeform || attributes.freeform ) + ' ' + slug;
				updates[ TAXONOMY_KEYS[ i ] ] = taxo[ i ].query;
				updates[ TERM_KEYS[ i ] ] = 0;
			} else if ( currentSlug !== taxo[ i ].query ) {
				// First render — set the slug.
				updates[ TAXONOMY_KEYS[ i ] ] = taxo[ i ].query;
			}
		}

		if ( Object.keys( updates ).length ) {
			setAttributes( updates );
		}
	}, [
		attributes.taxonomy_0,
		attributes.taxonomy_1,
		attributes.taxonomy_2,
		attributes.term_0,
		attributes.term_1,
		attributes.term_2,
	] ); // eslint-disable-line react-hooks/exhaustive-deps

	function idToSlug( n, tax, val ) {
		for ( let i = 0; i < wpdr_data.stmax; i++ ) {
			if ( i !== n && tax === taxo[ i ].query ) {
				const terms = taxo[ i ].terms;
				for ( let j = 0; j < terms.length; j++ ) {
					if ( val === terms[ j ][ 0 ] ) {
						return `${ tax }="${ terms[ j ][ 2 ] }"`;
					}
				}
				return `${ tax }="??"`;
			}
		}
		return `${ tax }="???"`;
	}

	function taxonomies() {
		if ( wpdr_data.stmax === 0 ) {
			return (
				<p>
					{ __(
						'There are no taxonomies defined.',
						'wp-document-revisions'
					) }
				</p>
			);
		}

		return taxo.slice( 0, wpdr_data.stmax ).map( ( tax, i ) => {
			const opts = tax.terms.map( ( term ) => ( {
				label: term[ 1 ],
				value: term[ 0 ],
			} ) );

			return (
				<PanelBody
					key={ tax.query }
					title={
						__( 'Taxonomy: ', 'wp-document-revisions' ) +
						tax.label
					}
					initialOpen={ false }
				>
					<RadioControl
						label={ tax.label }
						selected={ attributes[ TERM_KEYS[ i ] ] }
						options={ opts }
						onChange={ ( val ) => {
							setAttributes( {
								[ TERM_KEYS[ i ] ]: parseInt( val ),
							} );
						} }
					/>
				</PanelBody>
			);
		} );
	}

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="wp-document-revisions/documents-shortcode"
				attributes={ attributes }
			/>
			<InspectorControls>
				<TextControl
					type="string"
					value={ attributes.header }
					label={ __( 'Block Heading', 'wp-document-revisions' ) }
					onChange={ ( val ) => {
						setAttributes( { header: val } );
					} }
				/>
				{ taxonomies() }
				<PanelBody
					title={ __(
						'Display Settings',
						'wp-document-revisions'
					) }
					initialOpen={ false }
				>
					<RangeControl
						value={ attributes.numberposts }
						label={ __(
							'Number of documents to display',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( {
								numberposts: parseInt( val ),
							} );
						} }
						min={ 1 }
						step={ 1 }
					/>
					<TextControl
						type="string"
						value={ attributes.orderby }
						label={ __(
							'List ordering Field',
							'wp-document-revisions'
						) }
						help={ __(
							'Example fields are post_title, post_date and post_modified.',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { orderby: val } );
						} }
					/>
					<RadioControl
						label={ __( 'Order sequence' ) }
						selected={ attributes.order }
						options={ [
							{
								label: __(
									'Ascending',
									'wp-document-revisions'
								),
								value: 'ASC',
							},
							{
								label: __(
									'Descending',
									'wp-document-revisions'
								),
								value: 'DESC',
							},
						] }
						onChange={ ( val ) => {
							setAttributes( { order: val } );
						} }
					/>
					<RadioControl
						label={ __(
							'Show Edit link',
							'wp-document-revisions'
						) }
						help={ __(
							'Show Edit link allows the list to have a link to the Edit function. A choice made here will over-ride the system-configured settings. Links will only appear if the user can edit the document.',
							'wp-document-revisions'
						) }
						selected={ attributes.show_edit }
						options={ [
							{
								label: __(
									'Default',
									'wp-document-revisions'
								),
								value: '',
							},
							{
								label: __(
									'No Edit link',
									'wp-document-revisions'
								),
								value: '0',
							},
							{
								label: __(
									'Edit link',
									'wp-document-revisions'
								),
								value: '1',
							},
						] }
						onChange={ ( val ) => {
							setAttributes( { show_edit: val } );
						} }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.show_thumb }
						label={ __(
							'Show featured image?',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { show_thumb: val } );
						} }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.show_descr }
						label={ __(
							'Show document description?',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { show_descr: val } );
						} }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.show_pdf }
						label={ __(
							'Show PDF File indication?',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { show_pdf: val } );
						} }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.new_tab }
						label={ __(
							'Open documents in new tab?',
							'wp-document-revisions'
						) }
						help={ __(
							'Setting this on will open the document in a new tab. This should be set on whilst editing the page using this block as clicking on a link whilst editing will force the current page to be left.',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { new_tab: val } );
						} }
					/>
				</PanelBody>
				<PanelBody
					title={ __(
						'Free Form Settings',
						'wp-document-revisions'
					) }
					initialOpen={ false }
				>
					<TextareaControl
						type="string"
						rows={ 8 }
						value={ attributes.freeform }
						label={ __(
							'Free-form parameters',
							'wp-document-revisions'
						) }
						help={ __(
							'The query parameters can be very extensive. enter any other parameters required here.',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { freeform: val } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
