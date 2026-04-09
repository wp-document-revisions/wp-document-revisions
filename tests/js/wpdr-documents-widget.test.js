/**
 * Tests for wpdr-documents-widget.dev.js
 * 
 * This file tests the Latest Documents Gutenberg block that displays
 * a list of the most recent documents.
 */

const path = require('path');

const MODULE_PATH = path.resolve(__dirname, '../../js/wpdr-documents-widget.dev.js');

describe('wpdr-documents-widget block', () => {
	beforeEach(() => {
		// Load the documents widget script
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

	describe('Edit Function - createElement calls', () => {
		let blockConfig, editFunction;

		beforeEach(() => {
			jest.clearAllMocks();
			jest.resetModules();
			require(MODULE_PATH);
			blockConfig = wp.blocks.registerBlockType.mock.calls[0][1];
			editFunction = blockConfig.edit;
		});

		afterEach(() => {
			delete require.cache[require.resolve(MODULE_PATH)];
		});

		function getDefaultProps() {
			return {
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
				setAttributes: jest.fn(),
				className: 'wp-block',
			};
		}

		test('should call createElement for div wrapper', () => {
			editFunction(getDefaultProps());
			expect(wp.element.createElement).toHaveBeenCalled();
			// Outer div is the last call since inner children evaluate first
			const calls = wp.element.createElement.mock.calls;
			const lastCall = calls[calls.length - 1];
			expect(lastCall[0]).toBe('div');
		});

		test('should render ServerSideRender for preview', () => {
			editFunction(getDefaultProps());
			const ssrCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === wp.serverSideRender
			);
			expect(ssrCall).toBeDefined();
			expect(ssrCall[1].block).toBe('wp-document-revisions/documents-widget');
		});

		test('should pass attributes to ServerSideRender', () => {
			const props = getDefaultProps();
			editFunction(props);
			const ssrCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === wp.serverSideRender
			);
			expect(ssrCall[1].attributes).toBe(props.attributes);
		});

		test('should render InspectorControls', () => {
			editFunction(getDefaultProps());
			const icCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'InspectorControls'
			);
			expect(icCall).toBeDefined();
		});

		test('should render PanelBody with correct title', () => {
			editFunction(getDefaultProps());
			const pbCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'PanelBody'
			);
			expect(pbCall).toBeDefined();
			expect(pbCall[1].title).toBe('Latest Documents Settings');
			expect(pbCall[1].initialOpen).toBe(true);
		});

		test('should render TextControl for header', () => {
			editFunction(getDefaultProps());
			const tcCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'TextControl'
			);
			expect(tcCall).toBeDefined();
			expect(tcCall[1].label).toBe('Latest Documents List Heading');
		});

		test('should render RangeControl for numberposts', () => {
			editFunction(getDefaultProps());
			const rcCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'RangeControl'
			);
			expect(rcCall).toBeDefined();
			expect(rcCall[1].label).toBe('Documents to Display');
			expect(rcCall[1].min).toBe(1);
			expect(rcCall[1].max).toBe(25);
		});

		test('should render three CheckboxControls for post statuses', () => {
			editFunction(getDefaultProps());
			const cbCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'CheckboxControl'
			);
			expect(cbCalls).toHaveLength(3);
		});

		test('should render CheckboxControl labels for Publish, Private, Draft', () => {
			editFunction(getDefaultProps());
			const cbCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'CheckboxControl'
			);
			const labels = cbCalls.map((call) => call[1].label);
			expect(labels).toContain('Publish');
			expect(labels).toContain('Private');
			expect(labels).toContain('Draft');
		});

		test('should render five ToggleControls', () => {
			editFunction(getDefaultProps());
			const tgCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'ToggleControl'
			);
			expect(tgCalls).toHaveLength(5);
		});

		test('should render ToggleControl labels for all toggle options', () => {
			editFunction(getDefaultProps());
			const tgCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'ToggleControl'
			);
			const labels = tgCalls.map((call) => call[1].label);
			expect(labels).toContain('Show featured image?');
			expect(labels).toContain('Show document description?');
			expect(labels).toContain('Show author name?');
			expect(labels).toContain('Show PDF File indication?');
			expect(labels).toContain('Open documents in new tab?');
		});

		test('should render Document Statuses paragraph', () => {
			editFunction(getDefaultProps());
			const pCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'p'
			);
			expect(pCall).toBeDefined();
		});

		test('should render statuses wrapper div with className', () => {
			const props = getDefaultProps();
			editFunction(props);
			const divCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'div'
			);
			const statusDiv = divCalls.find(
				(call) => call[1] && call[1].className === 'wp-block'
			);
			expect(statusDiv).toBeDefined();
		});

		test('should render ToggleControls with help text for show_thumb and new_tab', () => {
			editFunction(getDefaultProps());
			const tgCalls = wp.element.createElement.mock.calls.filter(
				(call) => call[0] === 'ToggleControl'
			);
			const thumbCall = tgCalls.find((call) => call[1].label === 'Show featured image?');
			expect(thumbCall[1].help).toBeDefined();
			expect(thumbCall[1].help.length).toBeGreaterThan(0);

			const newTabCall = tgCalls.find((call) => call[1].label === 'Open documents in new tab?');
			expect(newTabCall[1].help).toBeDefined();
			expect(newTabCall[1].help.length).toBeGreaterThan(0);
		});

		test('should bind current attribute values to controls', () => {
			const props = getDefaultProps();
			props.attributes.header = 'My Docs';
			props.attributes.numberposts = 10;
			props.attributes.post_stat_publish = false;
			props.attributes.show_thumb = true;
			editFunction(props);

			const tcCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'TextControl'
			);
			expect(tcCall[1].value).toBe('My Docs');

			const rcCall = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'RangeControl'
			);
			expect(rcCall[1].value).toBe(10);

			const publishCb = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'CheckboxControl' && call[1].label === 'Publish'
			);
			expect(publishCb[1].checked).toBe(false);

			const thumbTg = wp.element.createElement.mock.calls.find(
				(call) => call[0] === 'ToggleControl' && call[1].label === 'Show featured image?'
			);
			expect(thumbTg[1].checked).toBe(true);
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

		afterEach(() => {
			delete require.cache[require.resolve(MODULE_PATH)];
		});

		function getDefaultProps() {
			return {
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
				setAttributes: jest.fn(),
				className: 'wp-block',
			};
		}

		test('should update header via TextControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const textControlCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'TextControl' && c[1] && c[1].label && c[1].label.includes('Heading')
			);
			expect(textControlCall).toBeDefined();
			textControlCall[1].onChange('New Header');
			expect(props.setAttributes).toHaveBeenCalledWith({ header: 'New Header' });
		});

		test('should update numberposts via RangeControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const rangeControlCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'RangeControl'
			);
			expect(rangeControlCall).toBeDefined();
			rangeControlCall[1].onChange('10');
			expect(props.setAttributes).toHaveBeenCalledWith({ numberposts: 10 });
		});

		test('should parseInt numberposts value', () => {
			const props = getDefaultProps();
			editFunction(props);
			const rangeControlCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'RangeControl'
			);
			rangeControlCall[1].onChange(7);
			expect(props.setAttributes).toHaveBeenCalledWith({ numberposts: 7 });
		});

		test('should update post_stat_publish via CheckboxControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const cbCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'CheckboxControl' && c[1] && c[1].label === 'Publish'
			);
			expect(cbCall).toBeDefined();
			cbCall[1].onChange(false);
			expect(props.setAttributes).toHaveBeenCalledWith({ post_stat_publish: false });
		});

		test('should update post_stat_private via CheckboxControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const cbCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'CheckboxControl' && c[1] && c[1].label === 'Private'
			);
			expect(cbCall).toBeDefined();
			cbCall[1].onChange(false);
			expect(props.setAttributes).toHaveBeenCalledWith({ post_stat_private: false });
		});

		test('should update post_stat_draft via CheckboxControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const cbCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'CheckboxControl' && c[1] && c[1].label === 'Draft'
			);
			expect(cbCall).toBeDefined();
			cbCall[1].onChange(true);
			expect(props.setAttributes).toHaveBeenCalledWith({ post_stat_draft: true });
		});

		test('should update show_thumb via ToggleControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const tgCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show featured image?'
			);
			expect(tgCall).toBeDefined();
			tgCall[1].onChange(true);
			expect(props.setAttributes).toHaveBeenCalledWith({ show_thumb: true });
		});

		test('should update show_descr via ToggleControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const tgCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show document description?'
			);
			expect(tgCall).toBeDefined();
			tgCall[1].onChange(false);
			expect(props.setAttributes).toHaveBeenCalledWith({ show_descr: false });
		});

		test('should update show_author via ToggleControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const tgCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show author name?'
			);
			expect(tgCall).toBeDefined();
			tgCall[1].onChange(false);
			expect(props.setAttributes).toHaveBeenCalledWith({ show_author: false });
		});

		test('should update show_pdf via ToggleControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const tgCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Show PDF File indication?'
			);
			expect(tgCall).toBeDefined();
			tgCall[1].onChange(true);
			expect(props.setAttributes).toHaveBeenCalledWith({ show_pdf: true });
		});

		test('should update new_tab via ToggleControl onChange', () => {
			const props = getDefaultProps();
			editFunction(props);
			const tgCall = wp.element.createElement.mock.calls.find(
				(c) => c[0] === 'ToggleControl' && c[1] && c[1].label === 'Open documents in new tab?'
			);
			expect(tgCall).toBeDefined();
			tgCall[1].onChange(true);
			expect(props.setAttributes).toHaveBeenCalledWith({ new_tab: true });
		});
	});
});
