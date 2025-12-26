/**
 * Tests for wpdr-documents-widget.dev.js
 * 
 * This file tests the Latest Documents Gutenberg block that displays
 * a list of the most recent documents.
 */

describe('wpdr-documents-widget block', () => {
	beforeEach(() => {
		// Load the documents widget script
		const path = require('path');
		const modulePath = path.resolve(__dirname, '../../js/wpdr-documents-widget.dev.js');
		
		// Clear the module from cache to ensure fresh execution
		delete require.cache[require.resolve(modulePath)];

		// Execute the code in the test environment by requiring the module
		require(modulePath);
	});

	afterEach(() => {
		// Ensure the widget script is re-executed for each test by clearing it from the require cache
		const path = require('path');
		const modulePath = path.resolve(__dirname, '../../js/wpdr-documents-widget.dev.js');
		delete require.cache[require.resolve(modulePath)];
	});

	describe('Block Registration', () => {
		test('should register block with correct name', () => {
			expect(wp.blocks.registerBlockType).toHaveBeenCalledWith(
				'wp-document-revisions/documents-widget',
				expect.any(Object)
			);
		});

		test('should have correct block title', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.title).toBe('Latest Documents');
		});

		test('should have correct block description', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.description).toBe('Display a list of your most recent documents.');
		});

		test('should be in wpdr-category', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.category).toBe('wpdr-category');
		});

		test('should have admin-page icon', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.icon).toBe('admin-page');
		});
	});

	describe('Block Attributes', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should have header attribute', () => {
			expect(blockConfig.attributes.header).toEqual({
				type: 'string',
			});
		});

		test('should have numberposts attribute with default 5', () => {
			expect(blockConfig.attributes.numberposts).toEqual({
				type: 'number',
				default: 5,
			});
		});

		test('should have post_stat_publish attribute with default true', () => {
			expect(blockConfig.attributes.post_stat_publish).toEqual({
				type: 'boolean',
				default: true,
			});
		});

		test('should have post_stat_private attribute with default true', () => {
			expect(blockConfig.attributes.post_stat_private).toEqual({
				type: 'boolean',
				default: true,
			});
		});

		test('should have post_stat_draft attribute with default false', () => {
			expect(blockConfig.attributes.post_stat_draft).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have show_thumb attribute with default false', () => {
			expect(blockConfig.attributes.show_thumb).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have show_descr attribute with default true', () => {
			expect(blockConfig.attributes.show_descr).toEqual({
				type: 'boolean',
				default: true,
			});
		});

		test('should have show_author attribute with default true', () => {
			expect(blockConfig.attributes.show_author).toEqual({
				type: 'boolean',
				default: true,
			});
		});

		test('should have show_pdf attribute with default false', () => {
			expect(blockConfig.attributes.show_pdf).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have new_tab attribute with default false', () => {
			expect(blockConfig.attributes.new_tab).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have styling attributes', () => {
			expect(blockConfig.attributes.align).toEqual({ type: 'string' });
			expect(blockConfig.attributes.backgroundColor).toEqual({ type: 'string' });
			expect(blockConfig.attributes.linkColor).toEqual({ type: 'string' });
			expect(blockConfig.attributes.textColor).toEqual({ type: 'string' });
			expect(blockConfig.attributes.gradient).toEqual({ type: 'string' });
			expect(blockConfig.attributes.fontSize).toEqual({ type: 'string' });
			expect(blockConfig.attributes.style).toEqual({ type: 'object' });
		});
	});

	describe('Block Supports', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should support alignment', () => {
			expect(blockConfig.supports.align).toBe(true);
		});

		test('should support color with gradients and link', () => {
			expect(blockConfig.supports.color).toEqual({
				gradients: true,
				link: true,
			});
		});

		test('should support spacing with margin and padding', () => {
			expect(blockConfig.supports.spacing).toEqual({
				margin: true,
				padding: true,
			});
		});

		test('should support typography with fontSize and lineHeight', () => {
			expect(blockConfig.supports.typography).toEqual({
				fontSize: true,
				lineHeight: true,
			});
		});
	});

	describe('Block Edit Function', () => {
		let blockConfig;
		let editFunction;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			editFunction = blockConfig.edit;
		});

		test('should be defined', () => {
			expect(editFunction).toBeDefined();
			expect(typeof editFunction).toBe('function');
		});

		test('should accept props and not throw', () => {
			const props = {
				attributes: {
					header: 'Latest Documents',
					numberposts: 5,
					post_stat_publish: true,
					post_stat_private: true,
					post_stat_draft: false,
					show_thumb: false,
					show_descr: true,
					show_author: true,
					show_pdf: false,
					new_tab: false,
				},
				setAttributes: jest.fn(),
				className: 'wp-block',
			};

			expect(() => {
				editFunction(props);
			}).not.toThrow();
		});
	});

	describe('Block Save Function', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should return null (dynamic block)', () => {
			expect(blockConfig.save()).toBeNull();
		});
	});

	describe('Attribute Defaults', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should default to showing 5 documents', () => {
			expect(blockConfig.attributes.numberposts.default).toBe(5);
		});

		test('should default to showing publish and private documents', () => {
			expect(blockConfig.attributes.post_stat_publish.default).toBe(true);
			expect(blockConfig.attributes.post_stat_private.default).toBe(true);
		});

		test('should default to not showing draft documents', () => {
			expect(blockConfig.attributes.post_stat_draft.default).toBe(false);
		});

		test('should default to not showing thumbnails', () => {
			expect(blockConfig.attributes.show_thumb.default).toBe(false);
		});

		test('should default to showing descriptions', () => {
			expect(blockConfig.attributes.show_descr.default).toBe(true);
		});

		test('should default to showing author', () => {
			expect(blockConfig.attributes.show_author.default).toBe(true);
		});

		test('should default to not showing PDF indicator', () => {
			expect(blockConfig.attributes.show_pdf.default).toBe(false);
		});

		test('should default to not opening in new tab', () => {
			expect(blockConfig.attributes.new_tab.default).toBe(false);
		});
	});

	describe('Inspector Controls', () => {
		let blockConfig;
		let editFunction;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			editFunction = blockConfig.edit;
		});

		test('should accept setAttributes callback', () => {
			const mockSetAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					numberposts: 5,
					post_stat_publish: true,
					post_stat_private: true,
					post_stat_draft: false,
					show_thumb: false,
					show_descr: true,
					show_author: true,
					show_pdf: false,
					new_tab: false,
				},
				setAttributes: mockSetAttributes,
				className: 'wp-block',
			};

			expect(() => {
				editFunction(props);
			}).not.toThrow();
		});
	});

	describe('Component Structure', () => {
		let blockConfig;
		let editFunction;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			editFunction = blockConfig.edit;
		});

		test('should accept all required props', () => {
			const testAttributes = {
				header: 'Test Header',
				numberposts: 10,
				post_stat_publish: true,
				post_stat_private: false,
				post_stat_draft: true,
				show_thumb: true,
				show_descr: false,
				show_author: false,
				show_pdf: true,
				new_tab: true,
			};

			const props = {
				attributes: testAttributes,
				setAttributes: jest.fn(),
				className: 'wp-block',
			};

			expect(() => {
				editFunction(props);
			}).not.toThrow();
		});
	});

	describe('Edge Cases', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should handle undefined header attribute', () => {
			const props = {
				attributes: {
					header: undefined,
					numberposts: 5,
					post_stat_publish: true,
					post_stat_private: true,
					post_stat_draft: false,
					show_thumb: false,
					show_descr: true,
					show_author: true,
					show_pdf: false,
					new_tab: false,
				},
				setAttributes: jest.fn(),
				className: 'wp-block',
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle extreme numberposts values', () => {
			const props = {
				attributes: {
					header: '',
					numberposts: 1000, // Very high value
					post_stat_publish: true,
					post_stat_private: true,
					post_stat_draft: false,
					show_thumb: false,
					show_descr: true,
					show_author: true,
					show_pdf: false,
					new_tab: false,
				},
				setAttributes: jest.fn(),
				className: 'wp-block',
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle all boolean attributes as false', () => {
			const props = {
				attributes: {
					header: '',
					numberposts: 5,
					post_stat_publish: false,
					post_stat_private: false,
					post_stat_draft: false,
					show_thumb: false,
					show_descr: false,
					show_author: false,
					show_pdf: false,
					new_tab: false,
				},
				setAttributes: jest.fn(),
				className: 'wp-block',
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle all boolean attributes as true', () => {
			const props = {
				attributes: {
					header: '',
					numberposts: 5,
					post_stat_publish: true,
					post_stat_private: true,
					post_stat_draft: true,
					show_thumb: true,
					show_descr: true,
					show_author: true,
					show_pdf: true,
					new_tab: true,
				},
				setAttributes: jest.fn(),
				className: 'wp-block',
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});
	});
});
