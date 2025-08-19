/**
 * Revisions Shortcode Block
 * Modern TypeScript conversion with proper typing
 */

import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import { RevisionsShortcodeAttributes, WPDRBlockProps } from '../types/blocks';

// Using official ServerSideRender component provided by WP core

interface RevisionsShortcodeBlockProps extends WPDRBlockProps<RevisionsShortcodeAttributes> {}

const RevisionsShortcodeEdit = ({ attributes, setAttributes }: RevisionsShortcodeBlockProps) => {
	const { id, numberposts, summary, show_pdf, new_tab } = attributes;

	const inspectorControls = (
		<InspectorControls>
			<PanelBody title={__('Revisions Settings', 'wp-document-revisions')} initialOpen={true}>
				<TextControl
					label={__('Document ID', 'wp-document-revisions')}
					type="number"
					value={id.toString()}
					onChange={(value: string) => {
						const numValue = parseInt(value, 10);
						if (!isNaN(numValue) && numValue > 0) {
							setAttributes({ id: numValue });
						}
					}}
					help={__(
						'The ID of the document to show revisions for',
						'wp-document-revisions'
					)}
				/>
				<RangeControl
					label={__('Number of Revisions', 'wp-document-revisions')}
					value={numberposts}
					onChange={(value?: number) => setAttributes({ numberposts: value ?? 1 })}
					min={1}
					max={50}
					help={__('How many revisions to display', 'wp-document-revisions')}
				/>
			</PanelBody>
			<PanelBody title={__('Display Options', 'wp-document-revisions')} initialOpen={false}>
				<ToggleControl
					label={__('Show Summary', 'wp-document-revisions')}
					checked={summary}
					onChange={(value: boolean) => setAttributes({ summary: value })}
					help={__('Display revision summaries/comments', 'wp-document-revisions')}
				/>
				<ToggleControl
					label={__('Show PDF Link', 'wp-document-revisions')}
					checked={show_pdf}
					onChange={(value: boolean) => setAttributes({ show_pdf: value })}
					help={__('Show direct PDF download link if available', 'wp-document-revisions')}
				/>
				<ToggleControl
					label={__('Open in New Tab', 'wp-document-revisions')}
					checked={new_tab}
					onChange={(value: boolean) => setAttributes({ new_tab: value })}
					help={__('Open revision links in a new tab/window', 'wp-document-revisions')}
				/>
			</PanelBody>
		</InspectorControls>
	);

	const blockContent = (
		<div
			style={{
				padding: '20px',
				border: '1px dashed #ccc',
				borderRadius: '4px',
				backgroundColor: '#f9f9f9',
			}}
		>
			<h4 style={{ margin: '0 0 10px 0' }}>
				{__('Document Revisions', 'wp-document-revisions')}
			</h4>
			<p style={{ margin: '0', color: '#666' }}>
				{id > 0
					? sprintf(
							/* translators: 1: number of revisions, 2: document id */
							__(
								'Showing %1$s revisions for document #%2$s',
								'wp-document-revisions'
							),
							numberposts,
							id
						)
					: __('Please enter a valid document ID', 'wp-document-revisions')}
			</p>
			{id > 0 && (
				<ServerSideRender
					block="wp-document-revisions/revisions-shortcode"
					attributes={attributes}
				/>
			)}
		</div>
	);

	return (
		<div>
			{inspectorControls}
			{blockContent}
		</div>
	);
};

registerBlockType('wp-document-revisions/revisions-shortcode', {
	title: __('Document Revisions', 'wp-document-revisions'),
	description: __('Display a list of revisions for your document.', 'wp-document-revisions'),
	category: 'wpdr-category',
	icon: 'list-view',
	keywords: [
		__('revisions', 'wp-document-revisions'),
		__('history', 'wp-document-revisions'),
		__('versions', 'wp-document-revisions'),
		__('document', 'wp-document-revisions'),
	],
	attributes: {
		id: {
			type: 'number',
			default: 1,
		},
		numberposts: {
			type: 'number',
			default: 5,
		},
		summary: {
			type: 'boolean',
			default: false,
		},
		show_pdf: {
			type: 'boolean',
			default: false,
		},
		new_tab: {
			type: 'boolean',
			default: true,
		},
		align: {
			type: 'string',
		},
		backgroundColor: {
			type: 'string',
		},
		linkColor: {
			type: 'string',
		},
		textColor: {
			type: 'string',
		},
		gradient: {
			type: 'string',
		},
		fontSize: {
			type: 'string',
		},
		style: {
			type: 'object',
		},
	},
	supports: {
		align: true,
		color: {
			background: true,
			text: true,
			link: true,
			gradients: true,
		},
		typography: {
			fontSize: true,
		},
		spacing: {
			padding: true,
			margin: true,
		},
	},
	edit: RevisionsShortcodeEdit,
	save: () => null, // Server-side rendered
});
