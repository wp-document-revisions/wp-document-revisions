( function( blocks, element, blockEditor, components, compose, serverSideRender, i18n ) {
const { registerBlockType, createBlock } = wp.blocks; //Blocks API
const { createElement } = wp.element;
const { InspectorControls } = wp.blockEditor; //Block inspector wrapper
const { PanelBody, RadioControl, RangeControl, TextControl, TextareaControl, ToggleControl } = wp.components; //WordPress form inputs
const { __ } = wp.i18n; //translation functions

registerBlockType( 'wp-document-revisions/documents-shortcode', {
	title: __( 'Documents List', 'wp-document-revisions' ), // Block title.
	description: __( 'Display a list of documents.', 'wp-document-revisions' ),
	category: 'wpdr-category',
	icon: 'editor-ul',
	attributes:  {
		header : {
			type: 'string',
			default: ''
		},
		taxonomy_0 : {
			type: 'string',
			default: ''
		},
		term_0 : {
			type: 'number',
			default: 0
		},
		taxonomy_1 : {
			type: 'string',
			default: ''
		},
		term_1 : {
			type: 'number',
			default: 0
		},
		taxonomy_2 : {
			type: 'string',
			default: ''
		},
		term_2 : {
			type: 'number',
			default: 0
		},
		numberposts : {
			type: 'number',
			default: 5
		},
		orderby : {
			type: 'string'
		},
		order : {
			type: 'string',
			default: 'ASC'
		},
		show_edit : {
			type: 'string',
			default: ''
		},
		show_thumb : {
			type : 'boolean',
			default: false
		},
		show_descr : {
			type : 'boolean',
			default: true
		},
		show_pdf : {
			type : 'boolean',
			default: false
		},
		new_tab : {
			type: 'boolean',
			default: true
		},
		freeform : {
			type: 'string',
			default: ''
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

		var taxo  = wpdr_data.taxos;

		function id_to_slug( n, tax, val ) {
			for( i = 0; i < wpdr_data.stmax; i++ ) {
				if ( i != n && tax === taxo[i].query ) {
					var terms = taxo[i].terms;
					for ( j = 0, lenj = terms.length; j < lenj; j++) {
						if ( val === terms[j][0] ) {
							return tax + '="' + terms[j][2] + '"';
						}
					}
					return tax + '="??"';
				}
			}
			return tax + '="???"';
		}

		// consistency check (possibly reordered). If same order, this does nothing.
		if ( wpdr_data.stmax > 0 && "" !== attributes.taxonomy_0 && attributes.taxonomy_0 !== taxo[0].query ) {
			setAttributes( { freeform: attributes.freeform + " " + id_to_slug( 0, attributes.taxonomy_0, attributes.term_0 ) } ); 
			setAttributes( { taxonomy_0: taxo[0].query } );
			setAttributes( { term_0: 0 } );
		}
		if ( wpdr_data.stmax > 1 && "" !== attributes.taxonomy_1 && attributes.taxonomy_1 !== taxo[1].query ) {
			setAttributes( { freeform: attributes.freeform + " " + id_to_slug( 1, attributes.taxonomy_1, attributes.term_1 ) } ); 
			setAttributes( { taxonomy_1: taxo[1].query } );
			setAttributes( { term_1: 0 } );
		}
		if ( wpdr_data.stmax > 2 && "" !== attributes.taxonomy_2 && attributes.taxonomy_2 !== taxo[2].query ) {
			setAttributes( { freeform: attributes.freeform + " " + id_to_slug( 2, attributes.taxonomy_2, attributes.term_2 ) } ); 
			setAttributes( { taxonomy_2: taxo[2].query } );
			setAttributes( { term_2: 0 } );
		}

		//Function to create the select grouping
		function tax_n( i ) {
			var terms = taxo[i].terms;
			var opts = [];
			for ( j = 0, lenj = terms.length; j < lenj; j++) {
				opts.push( { label: terms[j][1], value: terms[j][0] } );
			}
			// Set taxonomy slug
			if ( i == 0 ) {
				setAttributes( { taxonomy_0: taxo[0].query } )
				return createElement( PanelBody, { title: __( 'Taxonomy: ', 'wp-document-revisions' ) + taxo[0].label, initialOpen: false },
					[
						createElement(RadioControl, {
							label: taxo[0].label,
							selected: attributes.term_0,
							options: opts,
							onChange: function( val ) {
								setAttributes( { term_0: parseInt( val ) } );
							}
						})
					]
				);
			}
			if ( i == 1 ) {
				setAttributes( { taxonomy_1: taxo[1].query } )
				return createElement( PanelBody, { title: __( 'Taxonomy: ', 'wp-document-revisions' ) + taxo[1].label, initialOpen: false },
					[
						createElement(RadioControl, {
							label: taxo[1].label,
							selected: attributes.term_1,
							options: opts,
							onChange: function( val ) {
								setAttributes( { term_1: parseInt( val ) } );
							}
						})
					]
				);
			}
			if ( i == 2 ) {
				setAttributes( { taxonomy_2: taxo[2].query } )
				return createElement( PanelBody, { title: __( 'Taxonomy: ', 'wp-document-revisions' ) + taxo[2].label, initialOpen: false },
					[
						createElement(RadioControl, {
							label: taxo[2].label,
							selected: attributes.term_2,
							options: opts,
							onChange: function( val ) {
								setAttributes( { term_2: parseInt( val ) } );
							}
						})
					]
				);
			}
		}

		function taxonomies() {
			if ( wpdr_data.stmax == 0 ) {
				return createElement('p', {},
					__( 'There are no taxonomies defined.', 'wp-document-revisions' )
				);
			}

			var taxos = [];
			for (var i = 0; i < wpdr_data.stmax; i++) {
				taxos.push( tax_n( i ) );
			}
			return taxos;
		}

		//Display block preview and UI
		return createElement('div', {},
			[
				//Preview a block with a PHP render callback
				createElement( serverSideRender, {
					block: 'wp-document-revisions/documents-shortcode',
					attributes: attributes
					}
				),
				//Block inspector
				createElement( InspectorControls, {},
					[
						// Simple Taxonomy Selectors
						createElement(TextControl, {
							type: 'string',
							value: attributes.header,
							label: __( 'Block Heading', 'wp-document-revisions' ),
							onChange: function( val ) {
								setAttributes( { header: val } );
							}
						}),
						taxonomies(),
						createElement( PanelBody, { title: __( 'Display Settings', 'wp-document-revisions' ), initialOpen: false },
							[
								//Select number of posts
								createElement(RangeControl, {
									value: attributes.numberposts,
									label: __( 'Number of documents to display', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { numberposts: parseInt( val ) } );
									},
									min: 1,
									step: 1
								}),
								//A simple text control for document ordering
								createElement(TextControl, {
									type: 'string',
									value: attributes.orderby,
									label: __( 'List ordering Field', 'wp-document-revisions' ),
								  help: __( 'Example fields are post_title, post_date and post_modified.', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { orderby: val } );
									}
								}),
								createElement(RadioControl, {
									label: __( 'Order sequence' ),
								  selected: attributes.order,
								  options: [
								  	{ label: __( 'Ascending', 'wp-document-revisions' ), value: 'ASC' },
								    { label: __( 'Descending', 'wp-document-revisions' ), value: 'DESC' },
								  ],
									onChange: function( val ) {
										setAttributes( { order: val } );
									},
								}),
								createElement(RadioControl, {
									label: __( 'Show Edit link', 'wp-document-revisions' ),
								  help: __( 'Show Edit link allows the list to have a link to the Edit function. A choice made here will over-ride the system-configured settings. Links will only appear if the user can edit the document.', 'wp-document-revisions' ),
								  selected: attributes.show_edit,
								  options: [
								  	{ label: __( 'Default', 'wp-document-revisions' ), value: '' },
								    { label: __( 'No Edit link', 'wp-document-revisions' ), value: '0' },
										{ label: __( 'Edit link', 'wp-document-revisions' ), value: '1' },
								  ],
									onChange: function( val ) {
										setAttributes( { show_edit: val } );
									},
								}),
								//Show featured image
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.show_thumb,
									label: __( 'Show featured image?', 'wp-document-revisions' ),
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
						),
						createElement( PanelBody, { title: __( 'Free Form Settings', 'wp-document-revisions' ), initialOpen: false },
							[
								//A text area control for anything else
								createElement(TextareaControl, {
									type: 'string',
									rows: 8,
									value: attributes.freeform,
									label: __( 'Free-form parameters', 'wp-document-revisions' ),
									help: __( 'The query parameters can be very extensive. enter any other parameters required here.', 'wp-document-revisions' ),
									onChange: function( val ) {
										setAttributes( { freeform: val } );
									}
								})
							]
						),
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
					return /^\[?documents\b\s*/.test(text);
				},
				transform: ({text}) => {

					// defaults.
					var sheader = "";
					var staxonomy_0 = "";
					var sterm_0 = 0;
					var staxonomy_1 = "";
					var sterm_1 = 0;
					var staxonomy_2 = "";
					var sterm_2 = 0;
					var snumberposts = 5;
					var sorderby = "";
					var sorder = "";
					var sshow_edit = "";
					var sshow_thumb = false;
					var sshow_descr = true;
					var sshow_pdf = false;
					var snew_tab = true;
					var sfreeform  = "";

					// prepare text string.
					var iput = text.toLowerCase();
					if ( iput.indexOf("[") == 0 ) {
						iput = iput.slice(1, iput.length-1);
					}
					var args = iput.split(" ");
					args.shift();

					var taxo    = wpdr_data.taxos;
					var wf_efpp = wpdr_data.wf_efpp;

					function slug_to_id( n, val ) {
						var terms = taxo[n].terms;
						var alt_val = val.replace(/_/g, "-");
						for ( j = 0, lenj = terms.length; j < lenj; j++) {
							if ( val === terms[j][2] || alt_val === terms[j][2] ) {
								return terms[j][0];
							}
						}
						return 0;
					}
					
					var i;
					for (i of args) {
						if (i.length === 0 ) {
							continue;
						}
						var used = false;
						var parm = i.split("=");
						if ( parm.length > 1 && ( parm[1].indexOf("'") === 0 || parm[1].indexOf('"') === 0 ) ) {
							parm[1] = parm[1].slice(1, parm[1].length-1);
						}
						// existing parameter may be wf_state - convert to post_status.
						if ( wf_efpp === '1' && parm[0] === 'workflow_state') {
							parm[0] = 'post_status';
						}
						if ( wpdr_data.stmax > 0 && parm[0] === taxo[0].query ) {
							staxonomy_0 = parm[0];
							sterm_0 = slug_to_id( 0, parm[1] );
							used = true;
						}
						if ( wpdr_data.stmax > 1 && parm[0] === taxo[1].query ) {
							staxonomy_1 = parm[0];
							sterm_1 = slug_to_id( 1, parm[1] );
							used = true;
						}
						if ( wpdr_data.stmax > 2 && parm[0] === taxo[2].query ) {
							staxonomy_2 = parm[0];
							sterm_2 = slug_to_id( 2, parm[1] );
							used = true;
						}
						if ( parm[0] === 'number' ) {
							snumberposts = Number(parm[1]);
							used = true;
						}
						if ( parm[0] === 'numberposts' ) {
							snumberposts = Number(parm[1]);
							used = true;
						}
						if ( parm[0] === 'orderby' ) {
							sorderby = parm[1];
							used = true;
						}
						if ( parm[0] === 'order' ) {
							sorder = parm[1].toUpperCase();  // Upper case needed.
							used = true;
						}
						if ( parm[0] === 'show_edit' ) {
							sshow_edit = parm[1];
							used = true;
						}
						if ( parm[0] === 'show_thumb' ) {
							if ( parm.length === 1 || parm[1] === 'true' ) {
								sshow_thumb = true;
							}
							used = true;
						}
						if ( parm[0] === 'show_descr' ) {
							if ( parm.length === 2 && parm[1] === 'false' ) {
								sshow_descr = false;
							}
							used = true;
						}
						if ( parm[0] === 'show_pdf' ) {
							if ( parm.length === 1 || parm[1] === 'true' ) {
								sshow_pdf = true;
							}
							used = true;
						}
						if ( parm[0] === 'new_tab' ) {
							if ( parm.length === 2 && parm[1] === 'false' ) {
								snew_tab = false;
							}
							used = true;
						}
						if ( false == used ) {
							// other parameter, add to freeform one.
							sfreeform += ' ' + i;
						}
					}

					return createBlock('wp-document-revisions/documents-shortcode', {
						header : sheader,
						taxonomy_0 : staxonomy_0,
						term_0 : sterm_0,
						taxonomy_1 : staxonomy_1,
						term_1 : sterm_1,
						taxonomy_2 : staxonomy_2,
						term_2 : sterm_2,
						numberposts : snumberposts,
						orderby : sorderby,
						order : sorder,
						show_edit : sshow_edit,
						show_thumb : sshow_thumb,
						show_descr : sshow_descr,
						show_pdf : sshow_pdf,
						new_tab : snew_tab,
						freeform : sfreeform.trim()
					} );
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				transform: ( attributes ) => {
					var taxo  = wpdr_data.taxos;

					function id_to_slug( n, val ) {
						var terms = taxo[n].terms;
						for ( j = 0, lenj = terms.length; j < lenj; j++) {
							if ( val === terms[j][0] ) {
								return '"' + terms[j][2] + '"';
							}
						}
						return "??";
					}

					function decode_taxo( tax, val ) {
						if ( "" !== tax && 0 != val ) {
							var i;
							for ( i in [0, 1, 2] ) {
								if ( tax === taxo[i].query ) {
									content += " " + tax + "=" + id_to_slug( i, val );
									return;
								}
							}
							content += " " + tax + "=" + val;
						}
						return;
					}

					var content = '[documents ';
					decode_taxo( attributes.taxonomy_0, attributes.term_0 );
					decode_taxo( attributes.taxonomy_1, attributes.term_1 );
					decode_taxo( attributes.taxonomy_2, attributes.term_2 );
					if ( "" !== attributes.numberposts ) {
						content += ' numberposts="' + attributes.numberposts + '"';
					}
					if ( undefined !== attributes.orderby && "" !== attributes.orderby ) {
						content += ' orderby="' + attributes.orderby + '"';
					}
					if ( "" !== attributes.order && undefined !== attributes.orderby && "" !== attributes.orderby ) {
						content += ' order="' + attributes.order + '"';
					}
					if ( "" !== attributes.show_edit ) {
						content += ' show_edit="' + attributes.show_edit + '"';
					}
					if ( attributes.show_thumb ) {
						content += " show_thumb";
					}
					if ( attributes.show_descr ) {
						content += " show_descr";
					}
					if ( attributes.show_pdf ) {
						content += " show_pdf";
					}
					if ( attributes.new_tab ) {
						content += " new_tab";
					}
					if ( "" !== attributes.freeform && undefined !== attributes.freeform ) {
						content += ' ' + attributes.freeform;
					}
					content += " ]";
					return createBlock( 'core/shortcode', {
						text : content,
					} );
				},
			},
		],
	},
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
