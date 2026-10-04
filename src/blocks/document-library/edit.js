/* global wpdr_library_data */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	CheckboxControl,
	FormTokenField,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

/**
 * Fields in display order, with their labels.
 *
 * @return {Array<[string, string]>} [ field, label ] pairs.
 */
function fieldOptions() {
	return [
		[ 'thumbnail', __( 'Thumbnail', 'wp-document-revisions' ) ],
		[ 'title', __( 'Title', 'wp-document-revisions' ) ],
		[ 'description', __( 'Description', 'wp-document-revisions' ) ],
		[ 'file_type', __( 'File type', 'wp-document-revisions' ) ],
		[ 'workflow_state', __( 'Workflow state', 'wp-document-revisions' ) ],
		[ 'author', __( 'Author', 'wp-document-revisions' ) ],
		[ 'modified', __( 'Last modified date', 'wp-document-revisions' ) ],
		[ 'revisions', __( 'Number of revisions', 'wp-document-revisions' ) ],
		[ 'download', __( 'Download button', 'wp-document-revisions' ) ],
	];
}

/**
 * Document taxonomies and their terms, from PHP.
 *
 * @return {Array<{slug: string, label: string, terms: Array<{id: number, name: string, slug: string}>}>} Taxonomies.
 */
function documentTaxonomies() {
	return typeof wpdr_library_data !== 'undefined' ? wpdr_library_data.taxonomies : [];
}

/**
 * The term a filter value refers to: a term ID, or a slug from a converted shortcode.
 *
 * @param {{terms: Array<{id: number, name: string, slug: string}>}} taxonomy Taxonomy.
 * @param {number|string}                                            value    Term ID or slug.
 * @return {{id: number, name: string, slug: string}|undefined} The term.
 */
function findTerm( taxonomy, value ) {
	if ( typeof value === 'number' || /^\d+$/.test( value ) ) {
		return taxonomy.terms.find( ( term ) => term.id === Number( value ) );
	}
	// PHP passes slugs with hyphens written as underscores.
	const slug = value.replace( /-/g, '_' );
	return taxonomy.terms.find( ( term ) => term.slug === slug );
}

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { variant, columns, fields, taxonomies, orderby, order, numberposts, new_tab } =
		attributes;

	const toggleField = ( field, checked ) => {
		const next = checked ? [ ...fields, field ] : fields.filter( ( item ) => item !== field );
		// Keep the display order so the saved attribute stays tidy.
		setAttributes( {
			fields: fieldOptions()
				.map( ( [ key ] ) => key )
				.filter( ( key ) => next.includes( key ) ),
		} );
	};

	const setTerms = ( taxonomy, names ) => {
		const ids = names
			.map( ( name ) => taxonomy.terms.find( ( term ) => term.name === name ) )
			.filter( Boolean )
			.map( ( term ) => term.id );
		const next = { ...taxonomies };
		if ( ids.length ) {
			next[ taxonomy.slug ] = ids;
		} else {
			delete next[ taxonomy.slug ];
		}
		setAttributes( { taxonomies: next } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'wp-document-revisions' ) }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Layout', 'wp-document-revisions' ) }
						value={ variant }
						options={ [
							{ value: 'list', label: __( 'List', 'wp-document-revisions' ) },
							{ value: 'table', label: __( 'Table', 'wp-document-revisions' ) },
							{ value: 'grid', label: __( 'Grid', 'wp-document-revisions' ) },
						] }
						onChange={ ( value ) => setAttributes( { variant: value } ) }
					/>
					{ variant === 'grid' && (
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Columns', 'wp-document-revisions' ) }
							value={ columns }
							min={ 1 }
							max={ 6 }
							onChange={ ( value ) => setAttributes( { columns: value } ) }
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Fields', 'wp-document-revisions' ) }>
					{ fieldOptions().map( ( [ field, label ] ) => (
						<CheckboxControl
							key={ field }
							__nextHasNoMarginBottom
							label={ label }
							checked={ fields.includes( field ) }
							onChange={ ( checked ) => toggleField( field, checked ) }
						/>
					) ) }
				</PanelBody>
				{ documentTaxonomies().length > 0 && (
					<PanelBody
						title={ __( 'Filter', 'wp-document-revisions' ) }
						initialOpen={ false }
					>
						{ documentTaxonomies().map( ( taxonomy ) => (
							<FormTokenField
								key={ taxonomy.slug }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ taxonomy.label }
								value={ ( taxonomies[ taxonomy.slug ] || [] )
									.map( ( value ) => findTerm( taxonomy, value ) )
									.filter( Boolean )
									.map( ( term ) => term.name ) }
								suggestions={ taxonomy.terms.map( ( term ) => term.name ) }
								onChange={ ( names ) => setTerms( taxonomy, names ) }
								__experimentalExpandOnFocus
							/>
						) ) }
					</PanelBody>
				) }
				<PanelBody title={ __( 'Order', 'wp-document-revisions' ) } initialOpen={ false }>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Order by', 'wp-document-revisions' ) }
						value={ orderby }
						options={ [
							{
								value: 'modified',
								label: __( 'Last modified', 'wp-document-revisions' ),
							},
							{ value: 'date', label: __( 'Date created', 'wp-document-revisions' ) },
							{ value: 'title', label: __( 'Title', 'wp-document-revisions' ) },
							{
								value: 'menu_order',
								label: __( 'Menu order', 'wp-document-revisions' ),
							},
						] }
						onChange={ ( value ) => setAttributes( { orderby: value } ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Order', 'wp-document-revisions' ) }
						value={ order }
						options={ [
							{ value: 'DESC', label: __( 'Descending', 'wp-document-revisions' ) },
							{ value: 'ASC', label: __( 'Ascending', 'wp-document-revisions' ) },
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Number of documents', 'wp-document-revisions' ) }
						value={ numberposts }
						min={ 1 }
						max={ 100 }
						onChange={ ( value ) => setAttributes( { numberposts: value } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open documents in a new tab', 'wp-document-revisions' ) }
						checked={ new_tab }
						onChange={ ( value ) => setAttributes( { new_tab: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ServerSideRender
					block="wp-document-revisions/document-library"
					attributes={ attributes }
					skipBlockSupportAttributes
				/>
			</div>
		</>
	);
}
