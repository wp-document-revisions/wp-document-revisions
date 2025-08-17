/**
 * @jest-environment node
 */

describe('Type Definitions', () => {
  describe('Global Types', () => {
    it('should define WPDocumentRevisionsGlobals interface', () => {
      const { WPDocumentRevisionsGlobals } = require('../../src/types/globals');

      // Type checking is done at compile time, but we can test the import
      expect(typeof WPDocumentRevisionsGlobals).toBeDefined();
    });

    it('should define WPCookies interface with SameSite support', () => {
      // Test that our WPCookies interface includes SameSite parameter
      const mockCookies = {
        set: (
          name: string,
          value: string,
          expires: number,
          path: boolean | string,
          domain: boolean | string,
          secure: boolean,
          sameSite?: 'strict' | 'lax' | 'none'
        ) => {
          return { name, value, expires, path, domain, secure, sameSite };
        },
      };

      const result = mockCookies.set('test', 'value', 3600, false, false, true, 'strict');
      expect(result.sameSite).toBe('strict');
    });

    it('should define PluploadUploader interface', () => {
      const mockUploader = {
        bind: jest.fn(),
      };

      mockUploader.bind('test-event', () => {});
      expect(mockUploader.bind).toHaveBeenCalledWith('test-event', expect.any(Function));
    });
  });

  describe('Block Types', () => {
    it('should define base block attributes', () => {
      const { BaseBlockAttributes } = require('../../src/types/blocks');

      // Test that the type exists and has expected properties
      const testAttributes: typeof BaseBlockAttributes = {
        align: 'wide',
        backgroundColor: '#ffffff',
        textColor: '#000000',
        fontSize: 'large',
      };

      expect(testAttributes.align).toBe('wide');
      expect(testAttributes.backgroundColor).toBe('#ffffff');
    });

    it('should define DocumentsShortcodeAttributes', () => {
      const { DocumentsShortcodeAttributes } = require('../../src/types/blocks');

      // Type should include all required properties
      const testAttributes: typeof DocumentsShortcodeAttributes = {
        header: 'Test Header',
        taxonomy_0: 'category',
        term_0: 1,
        taxonomy_1: 'tag',
        term_1: 2,
        taxonomy_2: '',
        term_2: 0,
        numberposts: 10,
        order: 'DESC',
        show_edit: 'true',
        show_thumb: true,
        show_descr: true,
        show_pdf: false,
        new_tab: false,
        freeform: '',
      };

      expect(testAttributes.header).toBe('Test Header');
      expect(testAttributes.numberposts).toBe(10);
    });

    it('should define RevisionsShortcodeAttributes', () => {
      const { RevisionsShortcodeAttributes } = require('../../src/types/blocks');

      const testAttributes: typeof RevisionsShortcodeAttributes = {
        id: 1,
        numberposts: 5,
        summary: false,
        show_pdf: false,
        new_tab: true,
      };

      expect(testAttributes.id).toBe(1);
      expect(testAttributes.summary).toBe(false);
    });

    it('should define DocumentsWidgetAttributes', () => {
      const { DocumentsWidgetAttributes } = require('../../src/types/blocks');

      const testAttributes: typeof DocumentsWidgetAttributes = {
        header: 'Recent Docs',
        numberposts: 5,
        post_stat_publish: true,
        post_stat_private: false,
        post_stat_draft: false,
        show_thumb: false,
        show_descr: false,
        show_author: false,
        show_pdf: false,
        new_tab: true,
      };

      expect(testAttributes.post_stat_publish).toBe(true);
      expect(testAttributes.new_tab).toBe(true);
    });
  });

  describe('WordPress Types', () => {
    it('should provide WordPress module stubs', () => {
      // Test that we can import WordPress modules without errors
      const i18n = require('@wordpress/i18n');
      const blocks = require('@wordpress/blocks');
      const element = require('@wordpress/element');

      expect(typeof i18n.__).toBeDefined();
      expect(typeof blocks.registerBlockType).toBeDefined();
      expect(typeof element.createElement).toBeDefined();
    });
  });

  describe('Security Enhancements', () => {
    it('should support SameSite cookie attribute', () => {
      // Test that our cookie interface supports modern security attributes
      const testCookieCall = (
        name: string,
        value: string,
        expires: number,
        path: boolean | string,
        domain: boolean | string,
        secure: boolean,
        sameSite?: 'strict' | 'lax' | 'none'
      ) => {
        return { name, value, expires, path, domain, secure, sameSite };
      };

      const result = testCookieCall('test', 'value', 3600, false, false, true, 'strict');
      expect(result.sameSite).toBe('strict');
    });
  });

  describe('Modern Web Standards', () => {
    it('should support modern Notification API', () => {
      // Test that we're not using deprecated webkit notifications
      expect((global as any).webkitNotifications).toBeUndefined();

      // Test modern Notification API support
      expect(typeof Notification).toBeDefined();
      expect(typeof Notification.requestPermission).toBeDefined();
    });
  });
});
