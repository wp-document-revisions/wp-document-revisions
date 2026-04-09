/**
 * Tests for wpdr-revisions-shortcode.dev.js
 * 
 * This file tests the Document Revisions Gutenberg block that displays
 * a list of revisions for a specific document.
 */

const path = require('path');

const MODULE_PATH = path.resolve(__dirname, '../../js/wpdr-revisions-shortcode.dev.js');

describe('wpdr-revisions-shortcode block', () => {
	beforeEach(() => {
		// Load the revisions shortcode script
		// Clear the module from cache to ensure fresh execution
		delete require.cache[require.resolve(MODULE_PATH)];

		// Execute the code in the test environment by requiring the module
		require(MODULE_PATH);
	});

	afterEach(() => {
		// Clean up the module cache after each test to prevent side effects between tests
		delete require.cache[require.resolve(MODULE_PATH)];
	});

	describe('Block Registration', () => {
		test('should register block with correct name', () => {
			expect(wp.blocks.registerBlockType).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.any(Object)
			);
		});

		test('should have correct block title', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.title).toBe('Document Revisions');
		});

		test('should have correct block description', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.description).toBe('Display a list of revisions for your document.');
		});

		test('should be in wpdr-category', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.category).toBe('wpdr-category');
		});

		test('should have list-view icon', () => {
			const blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			expect(blockConfig.icon).toBe('list-view');
		});
	});

	describe('Block Attributes', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should have id attribute with default 1', () => {
			expect(blockConfig.attributes.id).toEqual({
				type: 'number',
				default: 1,
			});
		});

		test('should have numberposts attribute with default 5', () => {
			expect(blockConfig.attributes.numberposts).toEqual({
				type: 'number',
				default: 5,
			});
		});

		test('should have summary attribute with default false', () => {
			expect(blockConfig.attributes.summary).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have show_pdf attribute with default false', () => {
			expect(blockConfig.attributes.show_pdf).toEqual({
				type: 'boolean',
				default: false,
			});
		});

		test('should have new_tab attribute with default true', () => {
			expect(blockConfig.attributes.new_tab).toEqual({
				type: 'boolean',
				default: true,
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
					id: 1,
					numberposts: 5,
					summary: false,
					show_pdf: false,
					new_tab: true,
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
			wp.blocks.createBlock.mockClear();
		});

		test('should have transform from core/shortcode', () => {
			expect(fromTransform.type).toBe('block');
			expect(fromTransform.blocks).toEqual(['core/shortcode']);
		});

		test('should match document_revisions shortcode', () => {
			expect(fromTransform.isMatch({ text: '[document_revisions]' })).toBe(true);
			expect(fromTransform.isMatch({ text: '[document_revisions id=5]' })).toBe(true);
			expect(fromTransform.isMatch({ text: 'document_revisions' })).toBe(true);
		});

		test('should not match non-document_revisions shortcodes', () => {
			expect(fromTransform.isMatch({ text: '[documents]' })).toBe(false);
			expect(fromTransform.isMatch({ text: '[gallery]' })).toBe(false);
		});

		test('should transform shortcode with id parameter', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions id=10]' });

			expect(result.id).toBe(10);
		});

		test('should transform shortcode with number parameter', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions number=10]' });

			expect(result.numberposts).toBe(10);
		});

		test('should transform shortcode with numberposts parameter', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions numberposts=15]' });

			expect(result.numberposts).toBe(15);
		});

		test('should transform shortcode with summary parameter', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions summary]' });

			expect(result.summary).toBe(true);
		});

		test('should transform shortcode with summary=true', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions summary=true]' });

			expect(result.summary).toBe(true);
		});

		test('should transform shortcode with show_pdf parameter', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions show_pdf]' });

			expect(result.show_pdf).toBe(true);
		});

		test('should transform shortcode with new_tab=false', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions new_tab=false]' });

			expect(result.new_tab).toBe(false);
		});

		test('should handle multiple parameters', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({
				text: '[document_revisions id=20 numberposts=10 summary show_pdf]',
			});

			expect(result.id).toBe(20);
			expect(result.numberposts).toBe(10);
			expect(result.summary).toBe(true);
			expect(result.show_pdf).toBe(true);
		});

		test('should handle quoted parameter values', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: "[document_revisions id='25']" });

			expect(result.id).toBe(25);
		});

		test('should use default values for missing parameters', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const result = fromTransform.transform({ text: '[document_revisions]' });

			expect(result.id).toBe(1);
			expect(result.numberposts).toBe(5);
			expect(result.summary).toBe(false);
			expect(result.show_pdf).toBe(false);
			expect(result.new_tab).toBe(true);
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

		test('should create shortcode with id', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 10,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: true,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('id=10');
		});

		test('should create shortcode with numberposts', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 1,
				numberposts: 10,
				summary: false,
				show_pdf: false,
				new_tab: true,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('numberposts=10');
		});

		test('should include summary=false when false', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: true,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('summary=false');
		});

		test('should include summary=true when true', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 1,
				numberposts: 5,
				summary: true,
				show_pdf: false,
				new_tab: true,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('summary=true');
		});

		test('should include show_pdf when true', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: true,
				new_tab: true,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('show_pdf');
		});

		test('should include new_tab=false when false', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: false,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('new_tab=false');
		});

		test('should include new_tab=true when true', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 1,
				numberposts: 5,
				summary: false,
				show_pdf: false,
				new_tab: true,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('new_tab=true');
		});

		test('should create complete shortcode with all parameters', () => {
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);

			const attributes = {
				id: 25,
				numberposts: 15,
				summary: true,
				show_pdf: true,
				new_tab: false,
			};

			const result = toTransform.transform(attributes);

			expect(result.text).toContain('document_revisions');
			expect(result.text).toContain('id=25');
			expect(result.text).toContain('numberposts=15');
			expect(result.text).toContain('summary=true');
			expect(result.text).toContain('show_pdf');
			expect(result.text).toContain('new_tab=false');
		});
	});

	describe('Attribute Defaults', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should default to document ID 1', () => {
			expect(blockConfig.attributes.id.default).toBe(1);
		});

		test('should default to showing 5 revisions', () => {
			expect(blockConfig.attributes.numberposts.default).toBe(5);
		});

		test('should default to not showing summaries', () => {
			expect(blockConfig.attributes.summary.default).toBe(false);
		});

		test('should default to not showing PDF indicator', () => {
			expect(blockConfig.attributes.show_pdf.default).toBe(false);
		});

		test('should default to opening in new tab', () => {
			expect(blockConfig.attributes.new_tab.default).toBe(true);
		});
	});

	describe('Edit Function - createElement rendering', () => {
		let blockConfig, editFunction;

		beforeEach(() => {
			jest.clearAllMocks();
			jest.resetModules();
			require(MODULE_PATH);
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			editFunction = blockConfig.edit;
		});

		test('should render ServerSideRender with correct block name', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const ssrCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === wp.serverSideRender
			);
			expect(ssrCall).toBeDefined();
			expect(ssrCall[1].block).toBe('wp-document-revisions/revisions-shortcode');
			expect(ssrCall[1].attributes).toBe(props.attributes);
		});

		test('should render InspectorControls', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const inspectorCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'InspectorControls'
			);
			expect(inspectorCall).toBeDefined();
		});

		test('should render PanelBody with Selection Criteria title', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const panelCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'PanelBody' && c[1] && c[1].title === 'Selection Criteria'
			);
			expect(panelCall).toBeDefined();
			expect(panelCall[1].initialOpen).toBe(true);
		});

		test('should render TextControl for Document Id', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const textCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'TextControl' && c[1] && c[1].label === 'Document Id'
			);
			expect(textCall).toBeDefined();
			expect(textCall[1].type).toBe('number');
			expect(textCall[1].value).toBe(1);
		});

		test('should render RangeControl for Revisions to Display', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const rangeCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'RangeControl' && c[1] && c[1].label === 'Revisions to Display'
			);
			expect(rangeCall).toBeDefined();
			expect(rangeCall[1].value).toBe(5);
			expect(rangeCall[1].min).toBe(1);
			expect(rangeCall[1].max).toBe(20);
		});

		test('should render ToggleControl for summary', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const toggleCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show Revision Summaries?'
			);
			expect(toggleCall).toBeDefined();
			expect(toggleCall[1].checked).toBe(false);
		});

		test('should render ToggleControl for show_pdf', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const toggleCall = wp.element.createElement.mock.calls.find(
				(c) =>
					c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show PDF File indication?'
			);
			expect(toggleCall).toBeDefined();
			expect(toggleCall[1].checked).toBe(false);
		});

		test('should render ToggleControl for new_tab with help text', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const toggleCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Open in New Tab?'
			);
			expect(toggleCall).toBeDefined();
			expect(toggleCall[1].checked).toBe(true);
			expect(toggleCall[1].help).toBeDefined();
			expect(toggleCall[1].help.length).toBeGreaterThan(0);
		});

		test('should wrap everything in a div element', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const divCall = wp.element.createElement.mock.calls.find((c) => c[0] === 'div');
			expect(divCall).toBeDefined();
		});
	});

	describe('Edit Function - onChange callbacks', () => {
		let blockConfig, editFunction;

		beforeEach(() => {
			jest.clearAllMocks();
			jest.resetModules();
			require(MODULE_PATH);
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			editFunction = blockConfig.edit;
		});

		test('should update id via TextControl onChange', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const textCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'TextControl' && c[1] && c[1].label === 'Document Id'
			);
			textCall[1].onChange('42');
			expect(props.setAttributes).toHaveBeenCalledWith({ id: 42 });
		});

		test('should update numberposts via RangeControl onChange', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const rangeCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'RangeControl' && c[1] && c[1].label === 'Revisions to Display'
			);
			rangeCall[1].onChange(10);
			expect(props.setAttributes).toHaveBeenCalledWith({ numberposts: 10 });
		});

		test('should update summary via ToggleControl onChange', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const toggleCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show Revision Summaries?'
			);
			toggleCall[1].onChange(true);
			expect(props.setAttributes).toHaveBeenCalledWith({ summary: true });
		});

		test('should update show_pdf via ToggleControl onChange', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const toggleCall = wp.element.createElement.mock.calls.find(
				(c) =>
					c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show PDF File indication?'
			);
			toggleCall[1].onChange(true);
			expect(props.setAttributes).toHaveBeenCalledWith({ show_pdf: true });
		});

		test('should update new_tab via ToggleControl onChange', () => {
			const props = {
				attributes: { id: 1, numberposts: 5, summary: false, show_pdf: false, new_tab: true },
				setAttributes: jest.fn(),
			};
			editFunction(props);
			const toggleCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Open in New Tab?'
			);
			toggleCall[1].onChange(false);
			expect(props.setAttributes).toHaveBeenCalledWith({ new_tab: false });
		});
	});

	describe('Block Transforms - From Shortcode Edge Cases', () => {
		let blockConfig, fromTransform;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			fromTransform = blockConfig.transforms.from[0];
			wp.blocks.createBlock.mockClear();
			wp.blocks.createBlock.mockImplementation((blockName, attrs) => attrs);
		});

		test('should handle show_pdf without value in from transform', () => {
			const result = fromTransform.transform({ text: '[document_revisions id=5 show_pdf]' });
			expect(result.show_pdf).toBe(true);
			expect(result.id).toBe(5);
		});

		test('should handle new_tab=false specifically', () => {
			const result = fromTransform.transform({
				text: '[document_revisions id=5 new_tab=false]',
			});
			expect(result.new_tab).toBe(false);
			expect(result.id).toBe(5);
		});

		test('should handle new_tab without value (bare flag sets to false)', () => {
			const result = fromTransform.transform({
				text: '[document_revisions id=5 new_tab]',
			});
			expect(result.new_tab).toBe(false);
		});

		test('should skip empty args from consecutive spaces', () => {
			const result = fromTransform.transform({
				text: '[document_revisions  id=5  numberposts=3]',
			});
			expect(result.id).toBe(5);
			expect(result.numberposts).toBe(3);
		});

		test('should handle show_pdf=true explicitly', () => {
			const result = fromTransform.transform({
				text: '[document_revisions show_pdf=true]',
			});
			expect(result.show_pdf).toBe(true);
		});

		test('should not set show_pdf true when show_pdf=false', () => {
			const result = fromTransform.transform({
				text: '[document_revisions show_pdf=false]',
			});
			expect(result.show_pdf).toBe(false);
		});

		test('should not set summary true when summary=false', () => {
			const result = fromTransform.transform({
				text: '[document_revisions summary=false]',
			});
			expect(result.summary).toBe(false);
		});

		test('should handle new_tab=true keeping default', () => {
			const result = fromTransform.transform({
				text: '[document_revisions new_tab=true]',
			});
			expect(result.new_tab).toBe(true);
		});
	});

	describe('Edge Cases', () => {
		let blockConfig;

		beforeEach(() => {
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
		});

		test('should handle zero document ID', () => {
			const props = {
				attributes: {
					id: 0,
					numberposts: 5,
					summary: false,
					show_pdf: false,
					new_tab: true,
				},
				setAttributes: jest.fn(),
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle negative document ID', () => {
			const props = {
				attributes: {
					id: -1,
					numberposts: 5,
					summary: false,
					show_pdf: false,
					new_tab: true,
				},
				setAttributes: jest.fn(),
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle very large document ID', () => {
			const props = {
				attributes: {
					id: 999999999,
					numberposts: 5,
					summary: false,
					show_pdf: false,
					new_tab: true,
				},
				setAttributes: jest.fn(),
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle extreme numberposts values', () => {
			const props = {
				attributes: {
					id: 1,
					numberposts: 1000,
					summary: false,
					show_pdf: false,
					new_tab: true,
				},
				setAttributes: jest.fn(),
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});

		test('should handle all boolean attributes toggled', () => {
			const props = {
				attributes: {
					id: 1,
					numberposts: 5,
					summary: true,
					show_pdf: true,
					new_tab: false,
				},
				setAttributes: jest.fn(),
			};

			expect(() => {
				blockConfig.edit(props);
			}).not.toThrow();
		});
	});
});
