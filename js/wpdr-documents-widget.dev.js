( function( blocks, element, blockEditor, components, serverSideRender, i18n ) {
const { registerBlockType, createBlock } = wp.blocks; //Blocks API
const { createElement } = wp.element; //React.createElement
const { InspectorControls } = wp.blockEditor; //Block inspector wrapper
const { PanelBody, RangeControl, TextControl, CheckboxControl, ToggleControl } = wp.components; //WordPress form inputs
const { __ } = wp.i18n; //translation functions

registerBlockType( 'wp-document-revisions/documents-widget', {
	title: __( 'Latest Documents', 'wp-document-revisions' ), // Block title.
	description: __( 'Display a list of your most recent documents.', 'wp-document-revisions' ),
	category: 'wpdr-category',
	icon: 'admin-page',
	attributes:  {
		header : {
			type : 'string'
		},
		numberposts : {
			type : 'number',
			default : 5
		},
		post_stat_publish : {
			type : 'boolean',
			default: true
		},
		post_stat_private : {
			type : 'boolean',
			default: true
		},
		post_stat_draft : {
			type : 'boolean',
			default: false
		},
		show_thumb : {
			type : 'boolean',
			default: false
		},
		show_descr : {
			type : 'boolean',
			default: true
		},
		show_author : {
			type : 'boolean',
			default: true
		},
		show_pdf : {
			type : 'boolean',
			default: false
		},
		new_tab : {
			type : 'boolean',
			default: false
			},
		align: {
			type: 'string'
		},
		backgroundColor: {
			type: 'string'
		},
		linkColor: {
			type: 'string'
		},
		textColor: {
			type: 'string'
		},
		gradient: {
			type: 'string'
		},
		fontSize: {
			type: 'string'
		},
		style: {
				type: 'object'
		}
	},
	supports: {
		align: true,
		color: {
			gradients: true,
			link: true
		},
		spacing: {
			margin: true,
			padding: true
    },
		typography: {
			fontSize: true,
			lineHeight: true
    }
	},
	//display the settings
	edit( props ){
		const attributes =  props.attributes;
		const setAttributes =  props.setAttributes;

		//Display block preview and UI
		return createElement('div', {},
			[
				//Preview a block with a PHP render callback
				createElement( serverSideRender, {
					block: 'wp-document-revisions/documents-widget',
					attributes: attributes
					}
				),
				//Block inspector
				createElement( InspectorControls, {},
					[
						createElement( PanelBody, { title: __( 'Latest Documents Settings', 'wp-document-revisions' ), initialOpen: true },
							[
								//A simple text control for post id
								createElement( TextControl, {
									type: 'string',
									value: attributes.header,
									label: __( 'Latest Documents List Heading', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { header: val } );
									}
								}),
								//Select number of documents
								createElement( RangeControl, {
									value: attributes.numberposts,
									label: __( 'Documents to Display', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { numberposts: parseInt( val ) } );
									},
									min: 1,
									max: 25
								}),
								//Select Document Statuses
								createElement( 'div',
	                { className: props.className },
									createElement( 'p',
		                {},
	                		__( 'Document Statuses to Display', 'wp-document-revisions' )
									),
									//Show Publish status?
									createElement( CheckboxControl , {
										checked: attributes.post_stat_publish,
										label: __( 'Publish', 'wp-document-revisions' ),
										onChange: function( val ) {
											setAttributes( { post_stat_publish: val } );
										}
									}),
									//Show Private status?
									createElement( CheckboxControl , {
										checked: attributes.post_stat_private,
										label: __( 'Private', 'wp-document-revisions' ),
										onChange: function( val ) {
											setAttributes( { post_stat_private: val } );
										}
									}),
									//Show Draft status?
									createElement( CheckboxControl , {
										checked: attributes.post_stat_draft,
										label: __( 'Draft', 'wp-document-revisions' ),
										onChange: function( val ) {
											setAttributes( { post_stat_draft: val } );
										}
									}),
								),
								//Show featured image
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.show_thumb,
									label: __( 'Show featured image?', 'wp-document-revisions' ),
								  help: __( 'Under certain conditons WordPress can generate an image for Page 1 of PDF documents. If created this will be used as the Featured Image.', 'wp-document-revisions' ),
									onChange: function( val ) {
											setAttributes( { show_thumb: val } );
										}
								}),
								//Show post description
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.show_descr,
									label: __( 'Show document description?', 'wp-document-revisions' ),
									onChange: function( val ) {
											setAttributes( { show_descr: val } );
										}
								}),
								//Show author name
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.show_author,
									label: __( 'Show author name?', 'wp-document-revisions' ),
									onChange: function( val ) {
											setAttributes( { show_author: val } );
										}
								}),
								//Show PDF Indication
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.show_pdf,
									label: __( 'Show PDF File indication?', 'wp-document-revisions' ),
									onChange: function( val ) {
											setAttributes( { show_pdf: val } );
										}
								}),
								//Open in new tab
								createElement(ToggleControl, {
									type: 'boolean',
									checked: attributes.new_tab,
									label: __( 'Open documents in new tab?', 'wp-document-revisions' ),
									help: __( 'Setting this on will open the document in a new tab. This should be set on whilst editing the page using this block as clicking on a link whilst editing will force the current page to be left.', 'wp-document-revisions' ),
								  onChange: function( val ) {
										setAttributes( { new_tab: val } );
									}
								})
							]
						)
					]
				)
			]
		);
	},
	save(){
		return null; //save has to exist. This all we need.
	},
} );
}
(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
) );

