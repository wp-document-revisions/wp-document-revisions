import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RadioControl,
	RangeControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

/* global wpdr_data */

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	const taxo = wpdr_data.taxos;

	function id_to_slug( n, tax, val ) {
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

	// consistency check (possibly reordered). If same order, this does nothing.
	if (
		wpdr_data.stmax > 0 &&
		'' !== attributes.taxonomy_0 &&
		attributes.taxonomy_0 !== taxo[ 0 ].query
	) {
		setAttributes( {
			freeform:
				attributes.freeform +
				' ' +
				id_to_slug( 0, attributes.taxonomy_0, attributes.term_0 ),
		} );
		setAttributes( { taxonomy_0: taxo[ 0 ].query } );
		setAttributes( { term_0: 0 } );
	}
	if (
		wpdr_data.stmax > 1 &&
		'' !== attributes.taxonomy_1 &&
		attributes.taxonomy_1 !== taxo[ 1 ].query
	) {
		setAttributes( {
			freeform:
				attributes.freeform +
				' ' +
				id_to_slug( 1, attributes.taxonomy_1, attributes.term_1 ),
		} );
		setAttributes( { taxonomy_1: taxo[ 1 ].query } );
		setAttributes( { term_1: 0 } );
	}
	if (
		wpdr_data.stmax > 2 &&
		'' !== attributes.taxonomy_2 &&
		attributes.taxonomy_2 !== taxo[ 2 ].query
	) {
		setAttributes( {
			freeform:
				attributes.freeform +
				' ' +
				id_to_slug( 2, attributes.taxonomy_2, attributes.term_2 ),
		} );
		setAttributes( { taxonomy_2: taxo[ 2 ].query } );
		setAttributes( { term_2: 0 } );
	}

	// Function to create the select grouping
	function tax_n( i ) {
		const terms = taxo[ i ].terms;
		const opts = [];
		for ( let j = 0; j < terms.length; j++ ) {
			opts.push( { label: terms[ j ][ 1 ], value: terms[ j ][ 0 ] } );
		}
		// Set taxonomy slug
		if ( i === 0 ) {
			setAttributes( { taxonomy_0: taxo[ 0 ].query } );
			return (
				<PanelBody
					title={
						__( 'Taxonomy: ', 'wp-document-revisions' ) +
						taxo[ 0 ].label
					}
					initialOpen={ false }
				>
					<RadioControl
						label={ taxo[ 0 ].label }
						selected={ attributes.term_0 }
						options={ opts }
						onChange={ ( val ) => {
							setAttributes( { term_0: parseInt( val ) } );
						} }
					/>
				</PanelBody>
			);
		}
		if ( i === 1 ) {
			setAttributes( { taxonomy_1: taxo[ 1 ].query } );
			return (
				<PanelBody
					title={
						__( 'Taxonomy: ', 'wp-document-revisions' ) +
						taxo[ 1 ].label
					}
					initialOpen={ false }
				>
					<RadioControl
						label={ taxo[ 1 ].label }
						selected={ attributes.term_1 }
						options={ opts }
						onChange={ ( val ) => {
							setAttributes( { term_1: parseInt( val ) } );
						} }
					/>
				</PanelBody>
			);
		}
		if ( i === 2 ) {
			setAttributes( { taxonomy_2: taxo[ 2 ].query } );
			return (
				<PanelBody
					title={
						__( 'Taxonomy: ', 'wp-document-revisions' ) +
						taxo[ 2 ].label
					}
					initialOpen={ false }
				>
					<RadioControl
						label={ taxo[ 2 ].label }
						selected={ attributes.term_2 }
						options={ opts }
						onChange={ ( val ) => {
							setAttributes( { term_2: parseInt( val ) } );
						} }
					/>
				</PanelBody>
			);
		}
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

		const taxos = [];
		for ( let i = 0; i < wpdr_data.stmax; i++ ) {
			taxos.push( tax_n( i ) );
		}
		return taxos;
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
