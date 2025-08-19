/**
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock WordPress dependencies
jest.mock('@wordpress/i18n', () => ({
	__: jest.fn((text) => text),
}));

jest.mock('@wordpress/blocks', () => ({
	registerBlockType: jest.fn(),
}));

jest.mock('@wordpress/element', () => ({
	createElement: React.createElement,
}));

jest.mock('@wordpress/block-editor', () => ({
	InspectorControls: ({ children }: { children: React.ReactNode }) => (
		<div data-testid="inspector-controls">{children}</div>
	),
}));

jest.mock('@wordpress/components', () => ({
	PanelBody: ({ title, children }: { title: string; children: React.ReactNode }) => (
		<div data-testid="panel-body" data-title={title}>
			{children}
		</div>
	),
	RangeControl: jest.fn(({ label, value }) => (
		<div data-testid="range-control">
			{label}: {value}
		</div>
	)),
	TextControl: jest.fn(({ label, value }) => (
		<div data-testid="text-control">
			{label}: {value}
		</div>
	)),
	SelectControl: jest.fn(({ label, value, options }) => (
		<div data-testid="select-control">
			{label}: {value}
		</div>
	)),
	ToggleControl: jest.fn(({ label, checked }) => (
		<div data-testid="toggle-control">
			{label}: {checked ? 'Yes' : 'No'}
		</div>
	)),
	TextareaControl: jest.fn(({ label, value }) => (
		<div data-testid="textarea-control">
			{label}: {value}
		</div>
	)),
	RadioControl: jest.fn(({ label, selected }) => (
		<div data-testid="radio-control">
			{label}: {selected}
		</div>
	)),
}));

describe('Documents Shortcode Block', () => {
	let mockSetAttributes: jest.Mock;
	let mockAttributes: any;

	beforeEach(() => {
		jest.resetModules();
		jest.clearAllMocks();
	});

	describe('Block Registration', () => {
		it('should register the documents shortcode block', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const call = (registerBlockType as any).mock.calls[0];
			expect(call[0]).toBe('wp-document-revisions/documents-shortcode');
			expect(call[1]).toEqual(
				expect.objectContaining({
					title: 'Documents List',
					description: 'Display a list of documents.',
					category: 'wpdr-category',
					icon: 'editor-ul',
				})
			);
		});

		it('should have comprehensive block attributes', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			expect(blockConfig.attributes).toEqual(
				expect.objectContaining({
					header: { type: 'string', default: '' },
					taxonomy_0: { type: 'string', default: '' },
					term_0: { type: 'number', default: 0 },
					numberposts: { type: 'number', default: 5 },
					orderby: { type: 'string', default: 'date' },
					order: { type: 'string', default: 'ASC' },
					show_edit: { type: 'string', default: '' },
					show_thumb: { type: 'boolean', default: false },
					show_descr: { type: 'boolean', default: true },
					show_pdf: { type: 'boolean', default: false },
					new_tab: { type: 'boolean', default: true },
					freeform: { type: 'string', default: '' },
				})
			);
		});

		it('should support color and typography customization', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			expect(blockConfig.supports).toEqual(
				expect.objectContaining({
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
				})
			);
		});

		it('should include relevant keywords', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			expect(blockConfig.keywords).toContain('documents');
			expect(blockConfig.keywords).toContain('list');
			expect(blockConfig.keywords).toContain('files');
		});
	});

	describe('Default Values', () => {
		it('should have appropriate default values for all attributes', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			// Test key defaults
			expect(blockConfig.attributes.numberposts.default).toBe(5);
			expect(blockConfig.attributes.order.default).toBe('ASC');
			expect(blockConfig.attributes.orderby.default).toBe('date');
			expect(blockConfig.attributes.show_edit.default).toBe('');
			expect(blockConfig.attributes.show_thumb.default).toBe(false);
			expect(blockConfig.attributes.show_descr.default).toBe(true);
			expect(blockConfig.attributes.show_pdf.default).toBe(false);
			expect(blockConfig.attributes.new_tab.default).toBe(true);
		});
	});

	describe('Taxonomy Support', () => {
		it('should support multiple taxonomy filters', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			// Should have 3 taxonomy/term pairs
			expect(blockConfig.attributes.taxonomy_0).toBeDefined();
			expect(blockConfig.attributes.term_0).toBeDefined();
			expect(blockConfig.attributes.taxonomy_1).toBeDefined();
			expect(blockConfig.attributes.term_1).toBeDefined();
			expect(blockConfig.attributes.taxonomy_2).toBeDefined();
			expect(blockConfig.attributes.term_2).toBeDefined();
		});
	});

	describe('Display Options', () => {
		it('should have toggle options for display elements', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			// Check boolean display options
			expect(blockConfig.attributes.show_thumb.type).toBe('boolean');
			expect(blockConfig.attributes.show_descr.type).toBe('boolean');
			expect(blockConfig.attributes.show_pdf.type).toBe('boolean');
			expect(blockConfig.attributes.new_tab.type).toBe('boolean');
		});
	});

	describe('Server Side Rendering', () => {
		it('should use server-side rendering', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			expect(blockConfig.save()).toBeNull();
		});
	});

	describe('Sorting Options', () => {
		it('should support different sorting methods', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			expect(blockConfig.attributes.orderby).toEqual({
				type: 'string',
				default: 'date',
			});

			expect(blockConfig.attributes.order).toEqual({
				type: 'string',
				default: 'ASC',
			});
		});
	});

	describe('Freeform Support', () => {
		it('should support freeform shortcode parameters', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-documents-shortcode');

			const blockConfig = registerBlockType.mock.calls[0][1];

			expect(blockConfig.attributes.freeform).toEqual({
				type: 'string',
				default: '',
			});
		});
	});
});
