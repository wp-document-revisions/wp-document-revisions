import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="wp-document-revisions/revisions-shortcode"
				attributes={ attributes }
			/>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Selection Criteria',
						'wp-document-revisions'
					) }
					initialOpen={ true }
				>
					<TextControl
						type="number"
						value={ attributes.id }
						label={ __(
							'Document Id',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { id: parseInt( val ) } );
						} }
					/>
					<RangeControl
						value={ attributes.numberposts }
						label={ __(
							'Revisions to Display',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( {
								numberposts: parseInt( val ),
							} );
						} }
						min={ 1 }
						max={ 20 }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.summary }
						label={ __(
							'Show Revision Summaries?',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { summary: val } );
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
							'Open in New Tab?',
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
			</InspectorControls>
		</div>
	);
}
