import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	CheckboxControl,
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes, className } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="wp-document-revisions/documents-widget"
				attributes={ attributes }
			/>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Latest Documents Settings',
						'wp-document-revisions'
					) }
					initialOpen={ true }
				>
					<TextControl
						type="string"
						value={ attributes.header }
						label={ __(
							'Latest Documents List Heading',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { header: val } );
						} }
					/>
					<RangeControl
						value={ attributes.numberposts }
						label={ __(
							'Documents to Display',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( {
								numberposts: parseInt( val ),
							} );
						} }
						min={ 1 }
						max={ 25 }
					/>
					<div className={ className }>
						<p>
							{ __(
								'Document Statuses to Display',
								'wp-document-revisions'
							) }
						</p>
						<CheckboxControl
							checked={ attributes.post_stat_publish }
							label={ __(
								'Publish',
								'wp-document-revisions'
							) }
							onChange={ ( val ) => {
								setAttributes( {
									post_stat_publish: val,
								} );
							} }
						/>
						<CheckboxControl
							checked={ attributes.post_stat_private }
							label={ __(
								'Private',
								'wp-document-revisions'
							) }
							onChange={ ( val ) => {
								setAttributes( {
									post_stat_private: val,
								} );
							} }
						/>
						<CheckboxControl
							checked={ attributes.post_stat_draft }
							label={ __(
								'Draft',
								'wp-document-revisions'
							) }
							onChange={ ( val ) => {
								setAttributes( {
									post_stat_draft: val,
								} );
							} }
						/>
					</div>
					<ToggleControl
						type="boolean"
						checked={ attributes.show_thumb }
						label={ __(
							'Show featured image?',
							'wp-document-revisions'
						) }
						help={ __(
							'Under certain conditons WordPress can generate an image for Page 1 of PDF documents. If created this will be used as the Featured Image.',
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
						checked={ attributes.show_author }
						label={ __(
							'Show author name?',
							'wp-document-revisions'
						) }
						onChange={ ( val ) => {
							setAttributes( { show_author: val } );
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
			</InspectorControls>
		</div>
	);
}
