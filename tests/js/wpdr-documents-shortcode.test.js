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

	describe('Taxonomy Consistency Checks', () => {
		let blockConfig;

		beforeEach(() => {
			// Set up 3 taxonomies so all consistency check branches are reachable
			global.wpdr_data = {
				stmax: 3,
				taxos: [
					{ query: 'workflow_state', label: 'Workflow State', terms: [[0, 'All', 'all'], [1, 'Draft', 'draft'], [2, 'Final', 'final']] },
					{ query: 'document_type', label: 'Document Type', terms: [[0, 'All', 'all'], [3, 'Policy', 'policy'], [4, 'Report', 'report']] },
					{ query: 'department', label: 'Department', terms: [[0, 'All', 'all'], [5, 'HR', 'hr'], [6, 'IT', 'it']] },
				],
				wf_efpp: '0',
			};

			delete require.cache[require.resolve(MODULE_PATH)];
			require(MODULE_PATH);
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should not reorder when taxonomy_0 matches taxo[0].query', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 1,
					taxonomy_1: 'document_type',
					term_1: 3,
					taxonomy_2: 'department',
					term_2: 5,
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
				setAttributes,
			};

			blockConfig.edit(props);

			// setAttributes is called by tax_n to set taxonomy slugs, but NOT for freeform reorder
			const freeformCalls = setAttributes.mock.calls.filter(
				(call) => call[0].freeform !== undefined
			);
			expect(freeformCalls).toHaveLength(0);
		});

		test('should reorder taxonomy_0 when it does not match taxo[0].query', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'department',
					term_0: 5,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: 'workflow_state',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			// Should have called setAttributes with freeform containing slug from id_to_slug
			const freeformCalls = setAttributes.mock.calls.filter(
				(call) => call[0].freeform !== undefined
			);
			expect(freeformCalls.length).toBeGreaterThan(0);
			expect(freeformCalls[0][0].freeform).toContain('department=');

			// Should reset taxonomy_0 to the new value
			const tax0Calls = setAttributes.mock.calls.filter(
				(call) => call[0].taxonomy_0 !== undefined
			);
			expect(tax0Calls.some((c) => c[0].taxonomy_0 === 'workflow_state')).toBe(true);

			// Should reset term_0 to 0
			const term0Calls = setAttributes.mock.calls.filter(
				(call) => call[0].term_0 !== undefined
			);
			expect(term0Calls.some((c) => c[0].term_0 === 0)).toBe(true);
		});

		test('should reorder taxonomy_1 when it does not match taxo[1].query', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: 'department',
					term_1: 6,
					taxonomy_2: 'document_type',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const freeformCalls = setAttributes.mock.calls.filter(
				(call) => call[0].freeform !== undefined
			);
			expect(freeformCalls.length).toBeGreaterThan(0);
			expect(freeformCalls[0][0].freeform).toContain('department=');

			const tax1Calls = setAttributes.mock.calls.filter(
				(call) => call[0].taxonomy_1 !== undefined
			);
			expect(tax1Calls.some((c) => c[0].taxonomy_1 === 'document_type')).toBe(true);

			const term1Calls = setAttributes.mock.calls.filter(
				(call) => call[0].term_1 !== undefined
			);
			expect(term1Calls.some((c) => c[0].term_1 === 0)).toBe(true);
		});

		test('should reorder taxonomy_2 when it does not match taxo[2].query', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: 'workflow_state',
					term_2: 1,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const freeformCalls = setAttributes.mock.calls.filter(
				(call) => call[0].freeform !== undefined
			);
			expect(freeformCalls.length).toBeGreaterThan(0);
			expect(freeformCalls[0][0].freeform).toContain('workflow_state=');

			const tax2Calls = setAttributes.mock.calls.filter(
				(call) => call[0].taxonomy_2 !== undefined
			);
			expect(tax2Calls.some((c) => c[0].taxonomy_2 === 'department')).toBe(true);

			const term2Calls = setAttributes.mock.calls.filter(
				(call) => call[0].term_2 !== undefined
			);
			expect(term2Calls.some((c) => c[0].term_2 === 0)).toBe(true);
		});

		test('id_to_slug should return "??" when term id is not found in matching taxonomy', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'department',
					term_0: 999,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: '',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const freeformCalls = setAttributes.mock.calls.filter(
				(call) => call[0].freeform !== undefined
			);
			expect(freeformCalls.length).toBeGreaterThan(0);
			// term 999 doesn't exist in any taxonomy, so id_to_slug returns '??'
			expect(freeformCalls[0][0].freeform).toContain('department="??"');
		});

		test('id_to_slug should return "???" when taxonomy query is not found at other positions', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'nonexistent_tax',
					term_0: 1,
					taxonomy_1: '',
					term_1: 0,
					taxonomy_2: '',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const freeformCalls = setAttributes.mock.calls.filter(
				(call) => call[0].freeform !== undefined
			);
			expect(freeformCalls.length).toBeGreaterThan(0);
			expect(freeformCalls[0][0].freeform).toContain('nonexistent_tax="???"');
		});
	});

	describe('tax_n Function — All Indices', () => {
		let blockConfig;

		beforeEach(() => {
			global.wpdr_data = {
				stmax: 3,
				taxos: [
					{ query: 'workflow_state', label: 'Workflow State', terms: [[0, 'All', 'all'], [1, 'Draft', 'draft']] },
					{ query: 'document_type', label: 'Document Type', terms: [[0, 'All', 'all'], [3, 'Policy', 'policy']] },
					{ query: 'department', label: 'Department', terms: [[0, 'All', 'all'], [5, 'HR', 'hr']] },
				],
				wf_efpp: '0',
			};

			delete require.cache[require.resolve(MODULE_PATH)];
			require(MODULE_PATH);
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should create elements for all 3 taxonomies when stmax is 3', () => {
			wp.element.createElement.mockClear();
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: 'department',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			// PanelBody should be called once for each taxonomy plus Display Settings and Free Form
			const panelBodyCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'PanelBody'
			);
			// 3 taxonomy panels + Display Settings + Free Form = 5
			expect(panelBodyCalls.length).toBe(5);
		});

		test('should set taxonomy_1 attribute via tax_n(1)', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: '',
					term_1: 0,
					taxonomy_2: '',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const tax1Calls = setAttributes.mock.calls.filter(
				(call) => call[0].taxonomy_1 !== undefined
			);
			expect(tax1Calls.some((c) => c[0].taxonomy_1 === 'document_type')).toBe(true);
		});

		test('should set taxonomy_2 attribute via tax_n(2)', () => {
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: '',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const tax2Calls = setAttributes.mock.calls.filter(
				(call) => call[0].taxonomy_2 !== undefined
			);
			expect(tax2Calls.some((c) => c[0].taxonomy_2 === 'department')).toBe(true);
		});

		test('RadioControl onChange for term_1 should call setAttributes', () => {
			wp.element.createElement.mockClear();
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: 'department',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			// Find RadioControl createElement calls for taxonomy panels
			const radioControlCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'RadioControl' && call[1] && call[1].label
			);

			// Find the one for Document Type (taxonomy index 1)
			const docTypeRadio = radioControlCalls.find(
				(call) => call[1].label === 'Document Type'
			);
			expect(docTypeRadio).toBeDefined();

			// Simulate onChange
			setAttributes.mockClear();
			docTypeRadio[1].onChange('3');
			expect(setAttributes).toHaveBeenCalledWith({ term_1: 3 });
		});

		test('RadioControl onChange for term_2 should call setAttributes', () => {
			wp.element.createElement.mockClear();
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: 'workflow_state',
					term_0: 0,
					taxonomy_1: 'document_type',
					term_1: 0,
					taxonomy_2: 'department',
					term_2: 0,
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
				setAttributes,
			};

			blockConfig.edit(props);

			const radioControlCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'RadioControl' && call[1] && call[1].label
			);

			const deptRadio = radioControlCalls.find(
				(call) => call[1].label === 'Department'
			);
			expect(deptRadio).toBeDefined();

			setAttributes.mockClear();
			deptRadio[1].onChange('5');
			expect(setAttributes).toHaveBeenCalledWith({ term_2: 5 });
		});
	});

	describe('No-Taxonomies Path', () => {
		test('should handle zero taxonomies without error', () => {
			global.wpdr_data = { stmax: 0, taxos: [], wf_efpp: '0' };

			delete require.cache[require.resolve(MODULE_PATH)];
			require(MODULE_PATH);

			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			const setAttributes = jest.fn();
			const props = {
				attributes: {
					header: '',
					taxonomy_0: '',
					term_0: 0,
					taxonomy_1: '',
					term_1: 0,
					taxonomy_2: '',
					term_2: 0,
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
				setAttributes,
			};

			wp.element.createElement.mockClear();
			expect(() => blockConfig.edit(props)).not.toThrow();

			// createElement should be called with 'p' for no-taxonomies message
			const pCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'p'
			);
			expect(pCalls.length).toBeGreaterThan(0);
		});
	});

	describe('Block Transforms - From Shortcode (Additional)', () => {
		let blockConfig;
		let fromTransform;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			fromTransform = blockConfig.transforms.from[0];
			wp.blocks.createBlock.mockClear();
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);
		});

		test('should parse show_edit="1"', () => {
			const result = fromTransform.transform({ text: '[documents show_edit="1"]' });
			expect(result.show_edit).toBe('1');
		});

		test('should parse show_thumb as boolean without value', () => {
			const result = fromTransform.transform({ text: '[documents show_thumb]' });
			expect(result.show_thumb).toBe(true);
		});

		test('should parse show_descr=false', () => {
			const result = fromTransform.transform({ text: '[documents show_descr=false]' });
			expect(result.show_descr).toBe(false);
		});

		test('should parse show_pdf as boolean without value', () => {
			const result = fromTransform.transform({ text: '[documents show_pdf]' });
			expect(result.show_pdf).toBe(true);
		});

		test('should parse new_tab=false', () => {
			const result = fromTransform.transform({ text: '[documents new_tab=false]' });
			expect(result.new_tab).toBe(false);
		});

		test('should parse number as alias for numberposts', () => {
			const result = fromTransform.transform({ text: '[documents number=10]' });
			expect(result.numberposts).toBe(10);
		});

		test('should convert workflow_state to post_status when wf_efpp is 1', () => {
			global.wpdr_data = {
				stmax: 2,
				taxos: [
					{
						query: 'post_status',
						label: 'Post Status',
						terms: [
							[0, 'All', 'all'],
							[1, 'Draft', 'draft'],
						],
					},
					{
						query: 'document_category',
						label: 'Category',
						terms: [
							[0, 'All', 'all'],
							[3, 'Reports', 'reports'],
						],
					},
				],
				wf_efpp: '1',
			};

			delete require.cache[require.resolve(MODULE_PATH)];
			require(MODULE_PATH);

			const newBlockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			const newFromTransform = newBlockConfig.transforms.from[0];
			wp.blocks.createBlock.mockClear();
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = newFromTransform.transform({
				text: '[documents workflow_state="draft"]',
			});

			// workflow_state is converted to post_status, which matches taxo[0].query
			expect(result.taxonomy_0).toBe('post_status');
			expect(result.term_0).toBe(1);
		});

		test('should uppercase order value', () => {
			const result = fromTransform.transform({ text: '[documents order=desc]' });
			expect(result.order).toBe('DESC');
		});

		test('should handle text without brackets', () => {
			const result = fromTransform.transform({ text: 'documents show_thumb' });
			expect(result.show_thumb).toBe(true);
		});

		test('should handle show_edit="0"', () => {
			const result = fromTransform.transform({ text: '[documents show_edit="0"]' });
			expect(result.show_edit).toBe('0');
		});

		test('should parse numberposts parameter directly', () => {
			const result = fromTransform.transform({ text: '[documents numberposts=20]' });
			expect(result.numberposts).toBe(20);
		});

		test('should handle show_thumb=true explicitly', () => {
			const result = fromTransform.transform({ text: '[documents show_thumb=true]' });
			expect(result.show_thumb).toBe(true);
		});

		test('should handle show_pdf=true explicitly', () => {
			const result = fromTransform.transform({ text: '[documents show_pdf=true]' });
			expect(result.show_pdf).toBe(true);
		});

		test('should skip empty arguments from extra spaces', () => {
			const result = fromTransform.transform({
				text: '[documents  number=7  show_thumb]',
			});
			expect(result.numberposts).toBe(7);
			expect(result.show_thumb).toBe(true);
		});

		test('should strip single quotes from parameter values', () => {
			const result = fromTransform.transform({
				text: "[documents orderby='post_date']",
			});
			expect(result.orderby).toBe('post_date');
		});
	});

	describe('Block Transforms - To Shortcode (Additional)', () => {
		let blockConfig;
		let toTransform;

		const defaultAttrs = {
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
			freeform: '',
		};

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			toTransform = blockConfig.transforms.to[0];
			wp.blocks.createBlock.mockClear();
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);
		});

		test('should include show_edit="1" in shortcode', () => {
			const result = toTransform.transform({ ...defaultAttrs, show_edit: '1' });
			expect(result.text).toContain('show_edit="1"');
		});

		test('should include show_descr in shortcode when true', () => {
			const result = toTransform.transform({ ...defaultAttrs, show_descr: true });
			expect(result.text).toContain('show_descr');
		});

		test('should include show_pdf in shortcode when true', () => {
			const result = toTransform.transform({ ...defaultAttrs, show_pdf: true });
			expect(result.text).toContain('show_pdf');
		});

		test('should include new_tab in shortcode when true', () => {
			const result = toTransform.transform({ ...defaultAttrs, new_tab: true });
			expect(result.text).toContain('new_tab');
		});

		test('should not include order when orderby is empty', () => {
			const result = toTransform.transform({
				...defaultAttrs,
				order: 'DESC',
				orderby: '',
			});
			expect(result.text).not.toContain('order=');
		});

		test('should handle freeform being undefined', () => {
			const attrs = { ...defaultAttrs, freeform: undefined };
			expect(() => toTransform.transform(attrs)).not.toThrow();
		});

		test('should not include show_edit when empty string', () => {
			const result = toTransform.transform({ ...defaultAttrs, show_edit: '' });
			expect(result.text).not.toContain('show_edit');
		});

		test('should not include show_thumb when false', () => {
			const result = toTransform.transform({ ...defaultAttrs, show_thumb: false });
			expect(result.text).not.toContain('show_thumb');
		});

		test('should include both orderby and order when orderby is set', () => {
			const result = toTransform.transform({
				...defaultAttrs,
				orderby: 'post_title',
				order: 'ASC',
			});
			expect(result.text).toContain('orderby="post_title"');
			expect(result.text).toContain('order="ASC"');
		});

		test('should include taxonomy term slug for taxonomy_1', () => {
			const result = toTransform.transform({
				...defaultAttrs,
				taxonomy_1: 'document_category',
				term_1: 3,
			});
			expect(result.text).toContain('document_category');
			expect(result.text).toContain('reports');
		});

		test('should include show_edit="0" in shortcode', () => {
			const result = toTransform.transform({ ...defaultAttrs, show_edit: '0' });
			expect(result.text).toContain('show_edit="0"');
		});
	});
});
