( function( blocks, element, blockEditor, components, compose, serverSideRender, i18n ) {
const { registerBlockType, createBlock } = wp.blocks; //Blocks API
const { createElement } = wp.element; //React.createElement
const { InspectorControls } = wp.blockEditor; //Block inspector wrapper
const { PanelBody, RangeControl, TextControl, ToggleControl } = wp.components; //WordPress form inputs
const { __ } = wp.i18n; //translation functions

registerBlockType( 'wp-document-revisions/revisions-shortcode', {
	title: __( 'Document Revisions', 'wp-document-revisions' ), // Block title.
	description: __( 'Display a list of revisions for your document.', 'wp-document-revisions' ),
	category: 'wpdr-category',
	icon: 'list-view',
	attributes:  {
		id : {
			type: 'number',
			default: 1
		},
		numberposts : {
			type: 'number',
			default: 5
		},
		summary : {
			type: 'boolean',
			default: false
		},
		show_pdf : {
			type : 'boolean',
			default: false
		},
		new_tab : {
			type: 'boolean',
			default: true
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
					block: 'wp-document-revisions/revisions-shortcode',
					attributes: attributes
					}
				),
				//Block inspector
				createElement( InspectorControls, {},
					[
						createElement( PanelBody, { title: __( 'Selection Criteria', 'wp-document-revisions' ), initialOpen: true },
							[
								//A simple text control for post id
								createElement( TextControl, {
									type: 'number',
									value: attributes.id,
									label: __( 'Document Id', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { id: parseInt( val ) } );
									}
								} ),
								//Select number of revisions
								createElement( RangeControl, {
									value: attributes.numberposts,
									label: __( 'Revisions to Display', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { numberposts: parseInt( val ) } );
									},
									min: 1,
									max: 20
								} ),
								//Show summary
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.summary,
									label: __( 'Show Revision Summaries?', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { summary: val } )
									}
								} ),
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
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.new_tab,
									label: __( 'Open in New Tab?', 'wp-document-revisions' ),
									help: __( 'Setting this on will open the document in a new tab. This should be set on whilst editing the page using this block as clicking on a link whilst editing will force the current page to be left.', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { new_tab: val } )
									}
								} )
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
	transforms: {
		from: [
			{
				type: 'block',
				blocks: ['core/shortcode'],
				isMatch: function( {text} ) {
					return /^\[?document_revisions\b\s*/.test(text);
				},
				transform: ({text}) => {

					// prepare text string.
					var iput = text.toLowerCase();
					if ( iput.indexOf("[") == 0 ) {
						iput = iput.slice(1, iput.length-1);
					}
					var args = iput.split(" ");
					args.shift();

					// defaults.
					var sid = 1;
					var snumberposts = 5;
					var ssummary = false;
					var sshow_pdf = false;
					var snew_tab = true;
					var i;
					for (i of args) {
						if (i.length === 0 ) {
							continue;
						}
						var parm = i.split("=");
						if ( parm.length > 1 && ( parm[1].indexOf("'") === 0 || parm[1].indexOf('"') === 0 ) ) {
							parm[1] = parm[1].slice(1, parm[1].length-1);
						}
						if ( parm[0] === 'id' ) {
							sid = Number(parm[1]);
						};
						if ( parm[0] === 'number' ) {
							snumberposts = Number(parm[1]);
						};
						if ( parm[0] === 'numberposts' ) {
							snumberposts = Number(parm[1]);
						};
						if ( parm[0] === 'summary' ) {
							if ( parm.length === 1 || parm[1] === 'true' ) {
								ssummary = true;
							}
						};
						if ( parm[0] === 'show_pdf' ) {
							if ( parm.length === 1 || parm[1] === 'true' ) {
								sshow_pdf = true;
							}
							used = true;
						}
						if ( parm[0] === 'new_tab' ) {
							if ( parm.length === 1 || parm[1] === 'false' ) {
								snew_tab = false;
							}
						};
					};

					return createBlock('wp-document-revisions/revisions-shortcode', {
						id : sid,
						numberposts : snumberposts,
						summary : ssummary,
						show_pdf : sshow_pdf,
						new_tab : snew_tab
					} );
				}
			}
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				transform: ( attributes ) => {
					var content = '[document_revisions ';
					if ( "" != attributes.id ) {
						content += "id=" + attributes.id;
					}
					if ( "" != attributes.numberposts ) {
						content += " numberposts=" + attributes.numberposts;
					}
					if ( ! attributes.summary ) {
						content += " summary=false";
					} else {
						content += " summary=true";
					}
					if ( attributes.show_pdf ) {
						content += " show_pdf";
					}
					if ( ! attributes.new_tab ) {
						content += " new_tab=false ]";
					} else {
						content += " new_tab=true ]";
					}
					return createBlock( 'core/shortcode', {
						text : content,
					} );
				},
			},
		]
	}
} );
}
(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.compose,
	window.wp.serverSideRender,
	window.wp.i18n
) );
