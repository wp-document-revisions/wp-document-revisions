/**
 * Tests for wpdr-documents-shortcode.dev.js
 * 
 * This file tests the Documents List Gutenberg block that displays
 * a list of documents based on taxonomy filters and display settings.
 */

const path = require('path');

const MODULE_PATH = path.resolve(__dirname, '../../js/wpdr-documents-shortcode.dev.js');

describe('wpdr-documents-shortcode block', () => {
	beforeEach(() => {
		// Reset wpdr_data
		global.wpdr_data = {
			stmax: 2,
			taxos: [
				{
					query: 'workflow_state',
					label: 'Workflow State',
					terms: [
						[0, 'All', 'all'],
						[1, 'Draft', 'draft'],
						[2, 'In Review', 'in-review'],
					],
				},
				{
					query: 'document_category',
					label: 'Category',
					terms: [
						[0, 'All', 'all'],
						[3, 'Reports', 'reports'],
						[4, 'Policies', 'policies'],
					],
				},
			],
			wf_efpp: '0',
		};

		// Load the documents shortcode script
		// Clear the module from cache to ensure fresh execution
		delete require.cache[require.resolve(MODULE_PATH)];

		// Execute the code in the test environment by requiring the module
		require(MODULE_PATH);
	});

	afterEach(() => {
		// Clean up the shortcode module from the require cache after each test to prevent side effects
		delete require.cache[require.resolve(MODULE_PATH)];
	});

	describe('Block Registration', () => {
		test('should register block with correct name', () => {
			expect(wp.blocks.registerBlockType).toHaveBeenCalledWith(
				'wp-document-revisions/documents-shortcode',
				expect.any(Object)
			);
		});

		test('should have correct block title', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.title).toBe('Documents List');
		});

		test('should have correct block description', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.description).toBe('Display a list of documents.');
		});

		test('should be in wpdr-category', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.category).toBe('wpdr-category');
		});

		test('should have editor-ul icon', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.icon).toBe('editor-ul');
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
				default: '',
			});
		});

		test('should have taxonomy_0 attribute', () => {
			expect(blockConfig.attributes.taxonomy_0).toEqual({
				type: 'string',
				default: '',
			});
		});

		test('should have term_0 attribute', () => {
			expect(blockConfig.attributes.term_0).toEqual({
				type: 'number',
				default: 0,
			});
		});

		test('should have numberposts attribute with default 5', () => {
			expect(blockConfig.attributes.numberposts).toEqual({
				type: 'number',
				default: 5,
			});
		});

		test('should have orderby attribute', () => {
			expect(blockConfig.attributes.orderby).toEqual({
				type: 'string',
			});
		});

		test('should have order attribute with default ASC', () => {
			expect(blockConfig.attributes.order).toEqual({
				type: 'string',
				default: 'ASC',
			});
		});

		test('should have show_edit attribute', () => {
			expect(blockConfig.attributes.show_edit).toEqual({
				type: 'string',
				default: '',
			});
		});

		test('should have show_thumb boolean attribute', () => {
			expect(blockConfig.attributes.show_thumb).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have show_descr boolean attribute with default true', () => {
			expect(blockConfig.attributes.show_descr).toEqual({
				type: 'boolean',
				default: true,
			});
		});

		test('should have show_pdf boolean attribute', () => {
			expect(blockConfig.attributes.show_pdf).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have new_tab boolean attribute with default true', () => {
			expect(blockConfig.attributes.new_tab).toEqual({
				type: 'boolean',
				default: true,
			});
		});

		test('should have freeform attribute', () => {
			expect(blockConfig.attributes.freeform).toEqual({
				type: 'string',
				default: '',
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
					header: 'Test Header',
					taxonomy_0: '',
					term_0: 0,
					numberposts: 5,
					orderby: '',
					order: 'ASC',
					show_edit: '',
					show_thumb: false,
					show_descr: true,
					show_pdf: false,
					new_tab: true,
					freeform: '',
				},
				setAttributes: jest.fn(),
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

	describe('Block Transforms - From Shortcode', () => {
		let blockConfig;
		let fromTransform;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			fromTransform = blockConfig.transforms.from[0];
			// Clear mock and set up fresh for each test
			wp.blocks.createBlock.mockClear();
		});

		test('should have transform from core/shortcode', () => {
			expect(fromTransform.type).toBe('block');
			expect(fromTransform.blocks).toEqual(['core/shortcode']);
		});

		test('should match documents shortcode', () => {
			expect(fromTransform.isMatch({ text: '[documents]' })).toBe(true);
			expect(fromTransform.isMatch({ text: '[documents number=10]' })).toBe(true);
			expect(fromTransform.isMatch({ text: 'documents' })).toBe(true);
		});

		test('should not match non-documents shortcodes', () => {
			expect(fromTransform.isMatch({ text: '[gallery]' })).toBe(false);
			expect(fromTransform.isMatch({ text: '[other]' })).toBe(false);
		});

		test('should call createBlock when transforming', () => {
			wp.blocks.createBlock.mockReturnValue({});

			fromTransform.transform({ text: '[documents number=10]' });

			expect(wp.blocks.createBlock).toHaveBeenCalled();
		});

		test('should handle taxonomy parameters', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[documents workflow_state=draft]' });

			expect(result.taxonomy_0).toBe('workflow_state');
			expect(result.term_0).toBe(1);
		});

		test('should handle multiple parameters', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({
				text: '[documents number=10 orderby=post_title order=asc show_thumb]',
			});

			expect(result.numberposts).toBe(10);
			expect(result.orderby).toBe('post_title');
			expect(result.order).toBe('ASC');
			expect(result.show_thumb).toBe(true);
		});

		test('should handle unknown parameters in freeform', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({
				text: '[documents custom_param=value]',
			});

			expect(result.freeform).toContain('custom_param=value');
		});
	});

	describe('Block Transforms - To Shortcode', () => {
		let blockConfig;
		let toTransform;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			toTransform = blockConfig.transforms.to[0];
			wp.blocks.createBlock.mockClear();
		});

		test('should have transform to core/shortcode', () => {
			expect(toTransform.type).toBe('block');
			expect(toTransform.blocks).toEqual(['core/shortcode']);
		});

		test('should create shortcode with numberposts', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				taxonomy_0: '',
				term_0: 0,
				taxonomy_1: '',
				term_1: 0,
				taxonomy_2: '',
				term_2: 0,
				numberposts: 10,
				orderby: '',
				order: '',
				show_edit: '',
				show_thumb: false,
				show_descr: false,
				show_pdf: false,
				new_tab: false,
				freeform: '',
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('numberposts="10"');
		});

		test('should create shortcode with orderby and order', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				taxonomy_0: '',
				term_0: 0,
				taxonomy_1: '',
				term_1: 0,
				taxonomy_2: '',
				term_2: 0,
				numberposts: 5,
				orderby: 'post_date',
				order: 'DESC',
				show_edit: '',
				show_thumb: false,
				show_descr: false,
				show_pdf: false,
				new_tab: false,
				freeform: '',
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('orderby="post_date"');
			expect(result.text).toContain('order="DESC"');
		});

		test('should include show_thumb in shortcode when true', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				taxonomy_0: '',
				term_0: 0,
				taxonomy_1: '',
				term_1: 0,
				taxonomy_2: '',
				term_2: 0,
				numberposts: 5,
				orderby: '',
				order: '',
				show_edit: '',
				show_thumb: true,
				show_descr: false,
				show_pdf: false,
				new_tab: false,
				freeform: '',
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('show_thumb');
		});

		test('should include taxonomy in shortcode when set', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				taxonomy_0: 'workflow_state',
				term_0: 1,
				taxonomy_1: '',
				term_1: 0,
				taxonomy_2: '',
				term_2: 0,
				numberposts: 5,
				orderby: '',
				order: '',
				show_edit: '',
				show_thumb: false,
				show_descr: false,
				show_pdf: false,
				new_tab: false,
				freeform: '',
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('workflow_state');
		});

		test('should include freeform parameters', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				taxonomy_0: '',
				term_0: 0,
				taxonomy_1: '',
				term_1: 0,
				taxonomy_2: '',
				term_2: 0,
				numberposts: 5,
				orderby: '',
				order: '',
				show_edit: '',
				show_thumb: false,
				show_descr: false,
				show_pdf: false,
				new_tab: false,
				freeform: 'custom_param=value',
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('custom_param=value');
		});
	});

	describe('Edge Cases', () => {
		test('should handle empty wpdr_data.taxos', () => {
			global.wpdr_data = {
				stmax: 0,
				taxos: [],
				wf_efpp: '0',
			};

			// Reload the script using Node.js VM module for safer execution
			const fs = require('fs');
			const path = require('path');
			const vm = require('vm');
			const jsFile = fs.readFileSync(
				path.resolve(__dirname, '../../js/wpdr-documents-shortcode.dev.js'),
				'utf8'
			);

			// Create a context with access to required globals
			const context = vm.createContext({
				wp: global.wp,
				wpdr_data: global.wpdr_data,
				window: global.window,
			});

			expect(() => {
				vm.runInContext(jsFile, context);
			}).not.toThrow();
		});
	});
});
