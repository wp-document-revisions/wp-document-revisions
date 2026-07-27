import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="wp-document-revisions/document-preview"
				attributes={ attributes }
			/>
			<InspectorControls>
				<PanelBody
					title={ __( 'Preview Settings', 'wp-document-revisions' ) }
					initialOpen={ true }
				>
					<TextControl
						type="number"
						value={ attributes.id }
						label={ __( 'Document Id', 'wp-document-revisions' ) }
						onChange={ ( val ) => {
							setAttributes( { id: parseInt( val ) } );
						} }
					/>
					<RangeControl
						value={ attributes.height }
						label={ __( 'Preview Height (px)', 'wp-document-revisions' ) }
						onChange={ ( val ) => {
							setAttributes( { height: parseInt( val ) } );
						} }
						min={ 100 }
						max={ 2000 }
						step={ 50 }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.show_title }
						label={ __( 'Show Document Title?', 'wp-document-revisions' ) }
						onChange={ ( val ) => {
							setAttributes( { show_title: val } );
						} }
					/>
					<ToggleControl
						type="boolean"
						checked={ attributes.show_download }
						label={ __( 'Show Download Link?', 'wp-document-revisions' ) }
						help={ __(
							'Displays a download link below the preview, and is shown as a fallback when the file type cannot be previewed inline.',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { show_download: val } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
