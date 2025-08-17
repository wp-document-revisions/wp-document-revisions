/**
 * @jest-environment jsdom
 */

jest.mock('@wordpress/blocks', () => ({
	registerBlockType: jest.fn(),
}));

describe('Revisions Shortcode Block', () => {
	const loadBlock = () => {
		jest.resetModules();
		// Re-establish mock after reset
		jest.doMock('@wordpress/blocks', () => ({ registerBlockType: jest.fn() }));
		const block = require('../../src/blocks/wpdr-revisions-shortcode');
		const blocks = require('@wordpress/blocks');
		return { block, blocks };
	};

	it('registers with expected metadata', () => {
		const { blocks } = loadBlock();
		const calls = (blocks.registerBlockType as any).mock.calls;
		expect(calls.length).toBeGreaterThan(0);
		const [name, config] = calls[0];
		expect(name).toBe('wp-document-revisions/revisions-shortcode');
		expect(config).toEqual(
			expect.objectContaining({
				title: 'Document Revisions',
				description: 'Display a list of revisions for your document.',
				category: 'wpdr-category',
				icon: 'list-view',
			})
		);
	});

	it('exposes expected attributes with defaults', () => {
		const { blocks } = loadBlock();
		const config = (blocks.registerBlockType as any).mock.calls[0][1];
		expect(config.attributes).toEqual(
			expect.objectContaining({
				id: { type: 'number', default: 1 },
				numberposts: { type: 'number', default: 5 },
				summary: { type: 'boolean', default: false },
				show_pdf: { type: 'boolean', default: false },
				new_tab: { type: 'boolean', default: true },
			})
		);
	});

	it('declares block supports', () => {
		const { blocks } = loadBlock();
		const config = (blocks.registerBlockType as any).mock.calls[0][1];
		expect(config.supports).toEqual(
			expect.objectContaining({
				align: true,
				color: expect.objectContaining({
					background: true,
					text: true,
					link: true,
					gradients: true,
				}),
				typography: expect.objectContaining({ fontSize: true }),
				spacing: expect.objectContaining({ padding: true, margin: true }),
			})
		);
	});

	it('provides discoverability keywords', () => {
		const { blocks } = loadBlock();
		const config = (blocks.registerBlockType as any).mock.calls[0][1];
		expect(config.keywords).toEqual(
			expect.arrayContaining(['revisions', 'history', 'versions', 'document'])
		);
	});

	it('uses server-side rendering (save returns null)', () => {
		const { blocks } = loadBlock();
		const config = (blocks.registerBlockType as any).mock.calls[0][1];
		expect(config.save()).toBeNull();
	});
});
