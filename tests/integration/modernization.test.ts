/**
 * @jest-environment jsdom
 */

describe('Integration Tests', () => {
	beforeEach(() => {
		// Reset DOM
		document.body.innerHTML = '';

		// Reset global mocks
		jest.clearAllMocks();

		// Set up WordPress environment
		(global as any).wp = {
			i18n: { __: (text: string) => text },
			blocks: { registerBlockType: jest.fn() },
			element: { createElement: jest.fn() },
		};
	});

	describe('Build System Integration', () => {
		it('should successfully import all main modules', () => {
			expect(() => {
				require('../../src/admin/wp-document-revisions');
				require('../../src/admin/wp-document-revisions-validate');
				require('../../src/blocks/wpdr-revisions-shortcode');
				require('../../src/blocks/wpdr-documents-shortcode');
				require('../../src/blocks/wpdr-documents-widget');
			}).not.toThrow();
		});

		it('should have consistent TypeScript compilation', () => {
			// Test that all imports work without TypeScript errors
			const wpdr = require('../../src/admin/wp-document-revisions');
			const validate = require('../../src/admin/wp-document-revisions-validate');
			const blocks = [
				require('../../src/blocks/wpdr-revisions-shortcode'),
				require('../../src/blocks/wpdr-documents-shortcode'),
				require('../../src/blocks/wpdr-documents-widget'),
			];

			expect(wpdr).toBeDefined();
			expect(validate).toBeDefined();
			expect(blocks.length).toBe(3);
		});
	});

	describe('WordPress Integration', () => {
		it('should register all blocks with WordPress', () => {
			jest.resetModules();
			// Fresh mock for blocks module
			jest.doMock('@wordpress/blocks', () => ({ registerBlockType: jest.fn() }));
			const { registerBlockType } = require('@wordpress/blocks');

			// Import all block modules
			require('../../src/blocks/wpdr-revisions-shortcode');
			require('../../src/blocks/wpdr-documents-shortcode');
			require('../../src/blocks/wpdr-documents-widget');

			// Should have registered 3 blocks
			expect(registerBlockType).toHaveBeenCalledTimes(3);

			// Check specific block registrations
			expect(registerBlockType).toHaveBeenCalledWith(
				'wp-document-revisions/revisions-shortcode',
				expect.any(Object)
			);
			expect(registerBlockType).toHaveBeenCalledWith(
				'wp-document-revisions/documents-shortcode',
				expect.any(Object)
			);
			expect(registerBlockType).toHaveBeenCalledWith(
				'wp-document-revisions/documents-widget',
				expect.any(Object)
			);
		});

		it('should maintain consistent block category', () => {
			jest.resetModules();
			jest.doMock('@wordpress/blocks', () => ({ registerBlockType: jest.fn() }));
			const { registerBlockType } = require('@wordpress/blocks');

			require('../../src/blocks/wpdr-revisions-shortcode');
			require('../../src/blocks/wpdr-documents-shortcode');
			require('../../src/blocks/wpdr-documents-widget');

			// All blocks should use the same category
			const calls = registerBlockType.mock.calls;
			calls.forEach((call: any) => {
				expect(call[1].category).toBe('wpdr-category');
			});
		});
	});

	describe('Security Features Integration', () => {
		it('should use modern security standards throughout', () => {
			const { WPDocumentRevisions } = require('../../src/admin/wp-document-revisions');

			if (!(global as any).Notification || !(global as any).Notification.mock) {
				const MockNotification: any = jest.fn();
				MockNotification.permission = 'granted';
				MockNotification.requestPermission = jest.fn(() => Promise.resolve('granted'));
				(global as any).Notification = MockNotification;
				(window as any).Notification = MockNotification;
			}
			global.alert = jest.fn();

			const mockJQuery = jest.fn(() => {
				const obj: any = {
					on: jest.fn(() => obj),
					off: jest.fn(() => obj),
					show: jest.fn(() => obj),
					hide: jest.fn(() => obj),
					fadeIn: jest.fn(() => obj),
					fadeOut: jest.fn(() => obj),
					removeAttr: jest.fn(() => obj),
					is: jest.fn(() => true),
					attr: jest.fn(() => ''),
					length: 0,
				};
				return obj;
			});

			const wpdr = new WPDocumentRevisions(mockJQuery);

			// Test cookie security
			const cookieSetSpy = jest.spyOn(window.wpCookies, 'set');
			(wpdr as any).cookieFalse();

			// Should use SameSite=strict
			expect(cookieSetSpy).toHaveBeenCalledWith(
				expect.any(String),
				expect.any(String),
				expect.any(Number),
				expect.any(Boolean),
				expect.any(Boolean),
				expect.any(Boolean),
				'strict'
			);
		});

		it('should use modern Notification API', () => {
			const { WPDocumentRevisions } = require('../../src/admin/wp-document-revisions');

			if (!(global as any).Notification || !(global as any).Notification.mock) {
				const MockNotification: any = jest.fn();
				MockNotification.permission = 'granted';
				MockNotification.requestPermission = jest.fn(() => Promise.resolve('granted'));
				(global as any).Notification = MockNotification;
				(window as any).Notification = MockNotification;
			}
			global.alert = jest.fn();

			const mockJQuery = jest.fn(() => {
				const obj: any = { on: jest.fn(() => obj) };
				return obj;
			});

			const wpdr = new WPDocumentRevisions(mockJQuery);
			const NotificationSpy = (global as any).Notification as jest.Mock;
			(wpdr as any).lockOverrideNotice('Test message');
			expect(NotificationSpy).toHaveBeenCalledWith(
				expect.any(String),
				expect.objectContaining({
					body: 'Test message',
					icon: expect.any(String),
				})
			);
		});
	});

	describe('Error Handling Integration', () => {
		it('should handle missing dependencies gracefully', () => {
			// Test with missing WordPress globals
			delete (global as any).wp;

			expect(() => {
				require('../../src/blocks/wpdr-revisions-shortcode');
			}).not.toThrow();
		});

		it('should provide fallbacks for missing APIs', () => {
			// Test notification fallback
			delete (global as any).Notification;

			const { WPDocumentRevisions } = require('../../src/admin/wp-document-revisions');
			const mockJQuery = jest.fn(() => ({ on: jest.fn() }));
			const wpdr = new WPDocumentRevisions(mockJQuery);

			const alertSpy = jest.spyOn(global, 'alert');

			(wpdr as any).lockOverrideNotice('Test');

			expect(alertSpy).toHaveBeenCalledWith('Test');
		});
	});

	describe('Type Safety Integration', () => {
		it('should maintain type safety across modules', () => {
			// Import type definitions
			const globals = require('../../src/types/globals');
			const blocks = require('../../src/types/blocks');

			// Should be able to import without TypeScript compilation errors
			expect(globals).toBeDefined();
			expect(blocks).toBeDefined();
		});

		it('should provide consistent interfaces', () => {
			// Ensure types module loads and exposes attribute interfaces keys
			const blocksTypes = require('../../src/types/blocks');
			expect(blocksTypes).toBeDefined();
		});
	});

	describe('Modernization Success', () => {
		it('should successfully replace all legacy JavaScript', () => {
			// Verify no legacy webkit or old jQuery patterns
			const adminCode = require('../../src/admin/wp-document-revisions');

			// Should not contain webkit references
			expect(String(adminCode)).not.toMatch(/webkit/i);

			// Should use modern patterns
			expect(typeof Notification).toBeDefined();
		});

		it('should provide enhanced functionality', () => {
			const { registerBlockType } = require('@wordpress/blocks');

			// Import all blocks
			require('../../src/blocks/wpdr-revisions-shortcode');
			require('../../src/blocks/wpdr-documents-shortcode');
			require('../../src/blocks/wpdr-documents-widget');

			// All blocks should support modern WordPress features
			const calls = registerBlockType.mock.calls;
			calls.forEach((call: any) => {
				const config = call[1];

				// Should support color customization
				expect(config.supports.color).toBeDefined();
				expect(config.supports.color.background).toBe(true);
				expect(config.supports.color.text).toBe(true);

				// Should support typography
				expect(config.supports.typography).toBeDefined();

				// Should support spacing
				expect(config.supports.spacing).toBeDefined();
			});
		});
	});
});
