/**
 * @jest-environment jsdom
 */

import React, { createElement } from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

// Generic i18n mock returning the original string
jest.mock('@wordpress/i18n', () => ({
	__: (s: string) => s,
	sprintf: (format: string, ...values: any[]) =>
		format.replace(/%(\d+)\$s/g, (_, n) => String(values[parseInt(n, 10) - 1] ?? '')),
}));

// registerBlockType capture
const registerBlockTypeMock = jest.fn();
jest.mock('@wordpress/blocks', () => ({
	registerBlockType: registerBlockTypeMock,
}));

// Element mock (allow JSX)
jest.mock('@wordpress/element', () => ({
	createElement: (...args: any[]) => createElement.apply(null as any, args as any),
}));

// Minimal InspectorControls passthrough
jest.mock('@wordpress/block-editor', () => ({
	InspectorControls: ({ children }: { children: React.ReactNode }) => (
		<div data-testid="inspector">{children}</div>
	),
}));

// Interactive component mocks that invoke onChange handlers
jest.mock('@wordpress/components', () => ({
	TextControl: ({ label, value, onChange }: any) => (
		<label>
			{label}
			<input
				data-testid={`text-${label.replace(/\s+/g, '-').toLowerCase()}`}
				value={value || ''}
				onChange={(e) => onChange(e.target.value)}
			/>
		</label>
	),
	ToggleControl: ({ label, checked, onChange }: any) => (
		<button
			data-testid={`toggle-${label.replace(/\s+/g, '-').toLowerCase()}`}
			onClick={() => onChange(!checked)}
		>
			{label}:{checked ? 'on' : 'off'}
		</button>
	),
	RangeControl: ({ label, value, onChange, min = 1, max = 50 }: any) => (
		<input
			data-testid={`range-${label.replace(/\s+/g, '-').toLowerCase()}`}
			type="range"
			min={min}
			max={max}
			value={value}
			onChange={(e) => onChange(parseInt(e.target.value, 10))}
		/>
	),
	RadioControl: ({ label, selected, options, onChange }: any) => (
		<div data-testid={`radio-${label.replace(/\s+/g, '-').toLowerCase()}`}>
			{options?.map((opt: any) => (
				<button
					key={opt.value}
					aria-pressed={selected === opt.value}
					onClick={() => onChange(opt.value)}
				>
					{opt.label}
				</button>
			))}
		</div>
	),
	PanelBody: ({ title, children }: any) => (
		<fieldset data-testid="panel" data-title={title}>
			<legend>{title}</legend>
			{children}
		</fieldset>
	),
	TextareaControl: ({ label, value, onChange }: any) => (
		<label>
			{label}
			<textarea
				data-testid={`textarea-${label.replace(/\s+/g, '-').toLowerCase()}`}
				value={value}
				onChange={(e) => onChange(e.target.value)}
			/>
		</label>
	),
}));

// Mock ServerSideRender to avoid network/WordPress server calls
jest.mock('@wordpress/server-side-render', () => ({
	__esModule: true,
	default: ({ block }: { block: string }) => (
		<div data-testid="ssr" data-block={block}>
			SSR:{block}
		</div>
	),
}));

describe('Block Edit Component Interactions', () => {
	beforeEach(() => {
		jest.resetModules();
		registerBlockTypeMock.mockReset();
	});

	function loadBlock(path: string) {
		require(path); // side-effect registers block
		const [, config] = registerBlockTypeMock.mock.calls[0];
		return config.edit;
	}

	test('Documents Shortcode: header change and toggle updates attributes', () => {
		const Edit = loadBlock('../../src/blocks/wpdr-documents-shortcode');
		const setAttributes = jest.fn();
		const attributes = {
			header: 'Initial',
			taxonomy_0: '',
			term_0: 0,
			taxonomy_1: '',
			term_1: 0,
			taxonomy_2: '',
			term_2: 0,
			numberposts: 5,
			orderby: 'date',
			order: 'ASC',
			show_edit: '',
			show_thumb: false,
			show_descr: true,
			show_pdf: false,
			new_tab: true,
			freeform: '',
		};

		render(
			<Edit
				attributes={attributes}
				setAttributes={setAttributes}
				clientId="test"
				className=""
				isSelected={true}
			/>
		);

		// Header change
		const headerInput = screen.getByTestId('text-header');
		fireEvent.change(headerInput, { target: { value: 'Updated Header' } });
		expect(setAttributes).toHaveBeenCalledWith(
			expect.objectContaining({ header: 'Updated Header' })
		);

		// Toggle show thumbnails
		const thumbToggle = screen.getByTestId('toggle-show-thumbnails');
		fireEvent.click(thumbToggle);
		expect(setAttributes).toHaveBeenCalledWith(
			expect.objectContaining({ show_thumb: true })
		);

		// Change order by using a radio option (simulate clicking second button if exists)
		const radioGroup = screen.getByTestId('radio-order-by');
		const optionButtons = radioGroup.querySelectorAll('button');
		if (optionButtons.length > 1) {
			fireEvent.click(optionButtons[1]);
			expect(setAttributes).toHaveBeenCalledWith(
				expect.objectContaining({ orderby: expect.any(String) })
			);
		}
	});

	test('Revisions Shortcode: numberposts and summary toggle update attributes', () => {
		const Edit = loadBlock('../../src/blocks/wpdr-revisions-shortcode');
		const setAttributes = jest.fn();
		const attributes = {
			id: 1,
			numberposts: 5,
			summary: false,
			show_pdf: false,
			new_tab: true,
		};

		render(
			<Edit
				attributes={attributes}
				setAttributes={setAttributes}
				clientId="test"
				className=""
				isSelected={true}
			/>
		);

		// Range change (increase value)
		const range = screen.getByTestId('range-number-of-revisions');
		fireEvent.change(range, { target: { value: '7' } });
		expect(setAttributes).toHaveBeenCalledWith(
			expect.objectContaining({ numberposts: 7 })
		);

		// Toggle summary
		const summaryToggle = screen.getByTestId('toggle-show-summary');
		fireEvent.click(summaryToggle);
		expect(setAttributes).toHaveBeenCalledWith(
			expect.objectContaining({ summary: true })
		);
	});
});
