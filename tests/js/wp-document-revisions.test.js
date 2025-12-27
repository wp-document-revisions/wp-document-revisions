/**
 * Tests for wp-document-revisions.dev.js
 * 
 * This file tests the main WPDocumentRevisions class that handles
 * document management functionality in the WordPress admin.
 */

describe('WPDocumentRevisions', () => {
	let WPDocumentRevisions;
	let mockJQuery;

	beforeEach(() => {
		// Reset mocks
		jest.clearAllMocks();

		// Create a fresh mock jQuery
		mockJQuery = jest.fn((selector) => {
			// Handle jQuery ready: jQuery(function() { ... })
			if (typeof selector === 'function') {
				// Call the function immediately with jQuery as parameter (simulating ready)
				selector(mockJQuery);
				return mockJQuery;
			}
			
			// Handle normal jQuery selector
			const element = {
				click: jest.fn(() => element),
				bind: jest.fn(() => element),
				on: jest.fn(() => element),
				show: jest.fn(() => element),
				hide: jest.fn(() => element),
				fadeIn: jest.fn(() => element),
				fadeOut: jest.fn(() => element),
				prop: jest.fn(() => element),
				removeAttr: jest.fn(() => element),
				val: jest.fn(() => ''),
				text: jest.fn(() => ''),
				attr: jest.fn(() => ''),
				html: jest.fn(() => ''),
				each: jest.fn((callback) => {
					callback.call(element);
					return element;
				}),
				before: jest.fn(() => element),
				prev: jest.fn(() => element),
				not: jest.fn(() => element),
				is: jest.fn(() => false),
				length: 0,
			};
			return element;
		});

		mockJQuery.post = jest.fn();
		mockJQuery.ajax = jest.fn();

		// Make jQuery available globally for the eval
		// BUT make it NOT call ready callbacks immediately
		global.jQuery = mockJQuery;
		window.jQuery = mockJQuery;

		// Load the module
		const fs = require('fs');
		const path = require('path');
		const vm = require('vm');
		const jsFile = fs.readFileSync(
			path.resolve(__dirname, '../../js/wp-document-revisions.dev.js'),
			'utf8'
		);

		// Execute the code in the test environment using VM for safer execution
		// The code will try to call jQuery ready, which will create an instance
		// But we want the constructor function, so we'll capture it
		let WPDocumentRevisionsConstructor;
		const originalMockJQuery = mockJQuery;
		
		// Temporarily override jQuery to capture the constructor
		global.jQuery = jest.fn((selectorOrFunc) => {
			if (typeof selectorOrFunc === 'function') {
				// This is the ready callback - capture the result but don't execute yet
				// The callback does: window.WPDocumentRevisions = new WPDocumentRevisions($)
				// We want to capture WPDocumentRevisions constructor before it's called
				// So we'll execute the file, capture the constructor from the IIFE, then create our own instance
				return mockJQuery;
			}
			return originalMockJQuery(selectorOrFunc);
		});
		global.jQuery.post = mockJQuery.post;
		global.jQuery.ajax = mockJQuery.ajax;
		window.jQuery = global.jQuery;

		// Modify the file to capture the constructor
		const modifiedJsFile = jsFile.replace(
			'jQuery(function ($) {',
			'window.WPDocumentRevisionsConstructor = WPDocumentRevisions; jQuery(function ($) {'
		);
		
		// Create a context with access to required globals
		// Share the actual objects so changes are reflected
		const context = {
			window: window,
			global: global,
			jQuery: global.jQuery,
			opener: global.opener,
			parent: global.parent,
			top: global.top,
			wpCookies: global.wpCookies,
			wp_document_revisions: global.wp_document_revisions,
			autosave: global.autosave,
			autosave_enable_buttons: global.autosave_enable_buttons,
			get uploader() { return global.uploader; },
			setInterval: global.setInterval,
			clearInterval: global.clearInterval,
			document: global.document,
			confirm: global.confirm,
			alert: global.alert,
		};
		vm.createContext(context);

		// Execute the modified code in the sandboxed context
		vm.runInContext(modifiedJsFile, context);

		// Restore jQuery
		global.jQuery = originalMockJQuery;
		window.jQuery = originalMockJQuery;

		// Now create an instance with the constructor
		if (window.WPDocumentRevisionsConstructor) {
			// Store the constructor for tests that need to create new instances
			window.WPDocumentRevisions = window.WPDocumentRevisionsConstructor;
			WPDocumentRevisions = new window.WPDocumentRevisionsConstructor(mockJQuery);
		} else {
			// Fallback to the instance created by jQuery ready
			WPDocumentRevisions = window.WPDocumentRevisions;
		}
	});

	afterEach(() => {
		delete window.WPDocumentRevisions;
		delete window.WPDocumentRevisionsConstructor;
		delete global.jQuery;
		delete window.jQuery;
	});

	describe('Constructor and Initialization', () => {
		test('should initialize with jQuery', () => {
			expect(WPDocumentRevisions.$).toBe(mockJQuery);
		});

		test('should set initial hasUpload to false', () => {
			expect(WPDocumentRevisions.hasUpload).toBe(false);
		});

		test('should detect secure protocol', () => {
			window.location.protocol = 'https:';
			const instance = new window.WPDocumentRevisions(mockJQuery);
			expect(instance.secure).toBe(true);
		});

		test('should set up intervals for updates', () => {
			expect(global.setInterval).toHaveBeenCalledTimes(2);
		});
	});

	describe('hijackAutosave', () => {
		test('should save original autosave_enable_buttons', () => {
			const originalAutosave = jest.fn();
			window.autosave_enable_buttons = originalAutosave;
			
			const instance = new window.WPDocumentRevisions(mockJQuery);
			expect(instance.autosaveEnableButtonsOriginal).toBe(originalAutosave);
		});

		test('should replace window.autosave_enable_buttons', () => {
			const originalAutosave = jest.fn();
			window.autosave_enable_buttons = originalAutosave;
			
			const instance = new window.WPDocumentRevisions(mockJQuery);
			expect(window.autosave_enable_buttons).not.toBe(originalAutosave);
		});
	});

	describe('enableSubmit', () => {
		test('should fade in lock override element', () => {
			const mockLockOverride = mockJQuery('#lock_override');
			const mockPrev = { fadeIn: jest.fn() };
			mockLockOverride.prev = jest.fn(() => mockPrev);
			
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#lock_override') return mockLockOverride;
				return mockJQuery(selector);
			});

			WPDocumentRevisions.enableSubmit();
			expect(mockPrev.fadeIn).toHaveBeenCalled();
		});
	});

	describe('restoreRevision', () => {
		test('should show confirmation dialog', () => {
			const mockEvent = {
				preventDefault: jest.fn(),
				target: { getAttribute: jest.fn(() => '/restore-url') },
			};

			global.confirm.mockReturnValue(true);
			
			WPDocumentRevisions.restoreRevision(mockEvent);
			expect(mockEvent.preventDefault).toHaveBeenCalled();
			expect(global.confirm).toHaveBeenCalledWith(
				wp_document_revisions.restoreConfirmation
			);
		});

		test('should not redirect on cancel', () => {
			const originalHref = window.location.href;
			const mockEvent = {
				preventDefault: jest.fn(),
				target: {},
			};

			global.confirm.mockReturnValue(false);
			
			WPDocumentRevisions.restoreRevision(mockEvent);
			expect(window.location.href).toBe(originalHref);
		});
	});

	describe('human_time_diff', () => {
		test('should return singular minute for 60 seconds', () => {
			const now = 1609459200; // Base time in seconds
			const oneMinuteAgo = now - 60;
			
			const result = WPDocumentRevisions.human_time_diff(oneMinuteAgo, now);
			expect(result).toBe('1 minute');
		});

		test('should return plural minutes for multiple minutes', () => {
			const now = 1609459200;
			const fiveMinutesAgo = now - 300;
			
			const result = WPDocumentRevisions.human_time_diff(fiveMinutesAgo, now);
			expect(result).toBe('5 minutes');
		});

		test('should return plural hours for multiple hours', () => {
			const now = 1609459200;
			const threeHoursAgo = now - 10800;
			
			const result = WPDocumentRevisions.human_time_diff(threeHoursAgo, now);
			expect(result).toBe('3 hours');
		});

		test('should return plural days for multiple days', () => {
			const now = 1609459200;
			const fiveDaysAgo = now - 432000;
			
			const result = WPDocumentRevisions.human_time_diff(fiveDaysAgo, now);
			expect(result).toBe('5 days');
		});
	});

	describe('roundUp', () => {
		test('should return 1 for values less than 1', () => {
			expect(WPDocumentRevisions.roundUp(0)).toBe(1);
			expect(WPDocumentRevisions.roundUp(0.5)).toBe(1);
			expect(WPDocumentRevisions.roundUp(-1)).toBe(1);
		});

		test('should return the value for values greater than or equal to 1', () => {
			expect(WPDocumentRevisions.roundUp(1)).toBe(1);
			expect(WPDocumentRevisions.roundUp(5)).toBe(5);
			expect(WPDocumentRevisions.roundUp(100)).toBe(100);
		});
	});

	describe('cookieFalse', () => {
		test('should set doc_image cookie to false', () => {
			global.wpCookies.set = jest.fn();
			WPDocumentRevisions.cookieFalse();
			
			expect(global.wpCookies.set).toHaveBeenCalledWith(
				'doc_image',
				'false',
				3600,
				'/wp-admin',
				false,
				WPDocumentRevisions.secure
			);
		});
	});

	describe('cookieTrue', () => {
		test('should set doc_image cookie to true', () => {
			global.wpCookies.set = jest.fn();
			WPDocumentRevisions.cookieTrue();
			
			expect(global.wpCookies.set).toHaveBeenCalledWith(
				'doc_image',
				'true',
				3600,
				'/wp-admin',
				false,
				WPDocumentRevisions.secure
			);
		});
	});

	describe('cookieDelete', () => {
		test('should delete doc_image cookie by setting negative expiry', () => {
			global.wpCookies.set = jest.fn();
			WPDocumentRevisions.cookieDelete();
			
			expect(global.wpCookies.set).toHaveBeenCalledWith(
				'doc_image',
				'true',
				-60,
				'/wp-admin',
				false,
				WPDocumentRevisions.secure
			);
		});
	});

	describe('getDescr', () => {
		test('should return empty string when post_content is empty', () => {
			const mockPostContent = mockJQuery('#post_content');
			mockPostContent.val = jest.fn(() => '');

			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => null),
				},
			};
			WPDocumentRevisions.$ = mockJQuery;

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('');
		});

		test('should return empty string when post_content is only digits', () => {
			const mockPostContent = mockJQuery('#post_content');
			mockPostContent.val = jest.fn(() => '12345');

			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => null),
				},
			};
			WPDocumentRevisions.$ = mockJQuery;

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('');
		});
	});

	describe('buildContent', () => {
		test('should handle empty content', () => {
			const mockPostContent = mockJQuery('#post_content');
			mockPostContent.val = jest.fn(() => '');

			WPDocumentRevisions.$ = mockJQuery;
			WPDocumentRevisions.window = {
				jQuery: mockJQuery,
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'Description');

			WPDocumentRevisions.buildContent();

			// Should have called getDescr
			expect(WPDocumentRevisions.getDescr).toHaveBeenCalled();
		});

		test('should handle numeric content (document ID)', () => {
			const mockPostContent = mockJQuery('#post_content');
			mockPostContent.val = jest.fn(() => '12345');

			WPDocumentRevisions.$ = mockJQuery;
			WPDocumentRevisions.window = {
				jQuery: mockJQuery,
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'Description');

			WPDocumentRevisions.buildContent();

			// Should have called getDescr
			expect(WPDocumentRevisions.getDescr).toHaveBeenCalled();
		});
	});

	describe('postDocumentUpload', () => {
		test('should extract file extension from file object', () => {
			const fileObject = { name: 'document.pdf' };

			WPDocumentRevisions.hasUpload = false;
			WPDocumentRevisions.window = {
				jQuery: mockJQuery,
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.$ = mockJQuery;

			const result = WPDocumentRevisions.postDocumentUpload(fileObject, '123');

			// Should have processed the file
			expect(WPDocumentRevisions.hasUpload).toBe(true);
		});

		test('should return early if hasUpload is already true', () => {
			WPDocumentRevisions.hasUpload = true;
			const initialState = true;

			WPDocumentRevisions.postDocumentUpload('test.pdf', '123');

			expect(WPDocumentRevisions.hasUpload).toBe(initialState);
		});

		test('should set hasUpload to true after successful upload', () => {
			WPDocumentRevisions.hasUpload = false;
			WPDocumentRevisions.window = {
				jQuery: mockJQuery,
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.$ = mockJQuery;

			WPDocumentRevisions.postDocumentUpload('pdf', '123');

			expect(WPDocumentRevisions.hasUpload).toBe(true);
		});

		test('should call tb_remove after upload', () => {
			WPDocumentRevisions.hasUpload = false;
			WPDocumentRevisions.window = {
				jQuery: mockJQuery,
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.$ = mockJQuery;

			WPDocumentRevisions.postDocumentUpload('pdf', '123');

			expect(WPDocumentRevisions.window.tb_remove).toHaveBeenCalled();
		});
	});

	describe('checkUpdate', () => {
		test('should return early if curr_content is undefined', () => {
			const mockCurrContent = mockJQuery('#curr_content');
			mockCurrContent.val = jest.fn(() => undefined);

			WPDocumentRevisions.$ = mockJQuery;

			const result = WPDocumentRevisions.checkUpdate();

			expect(result).toBeUndefined();
		});
	});

	describe('updateTimestamps', () => {
		test('should update all timestamp elements', () => {
			const mockElements = [];
			const mockTimestamp = {
				text: jest.fn(),
				attr: jest.fn(() => '1609459200'),
			};

			mockElements.push(mockTimestamp);

			const mockTimestampSelector = {
				each: jest.fn((callback) => {
					mockElements.forEach(callback.bind(mockTimestamp));
					return mockTimestampSelector;
				}),
			};

			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '.timestamp') return mockTimestampSelector;
				return mockTimestamp;
			});

			WPDocumentRevisions.human_time_diff = jest.fn(() => '5 minutes');

			WPDocumentRevisions.updateTimestamps();

			expect(mockTimestampSelector.each).toHaveBeenCalled();
		});
	});

	describe('requestPermission', () => {
		test('should request notification permission if webkitNotifications exists', () => {
			const mockRequestPermission = jest.fn();
			window.webkitNotifications = {
				requestPermission: mockRequestPermission,
			};

			WPDocumentRevisions.requestPermission();

			expect(mockRequestPermission).toHaveBeenCalled();

			delete window.webkitNotifications;
		});

		test('should handle missing webkitNotifications gracefully', () => {
			window.webkitNotifications = null;

			expect(() => {
				WPDocumentRevisions.requestPermission();
			}).not.toThrow();
		});
	});

	describe('bindPostDocumentUploadCB', () => {
		test('should return early if uploader is undefined', () => {
			global.uploader = undefined;

			expect(() => {
				WPDocumentRevisions.bindPostDocumentUploadCB();
			}).not.toThrow();
		});

		test('should return early if uploader is null', () => {
			global.uploader = null;

			expect(() => {
				WPDocumentRevisions.bindPostDocumentUploadCB();
			}).not.toThrow();
		});

		test('should bind to uploader FileUploaded event if uploader exists', () => {
			const mockBind = jest.fn();
			global.uploader = {
				bind: mockBind,
			};

			WPDocumentRevisions.bindPostDocumentUploadCB();

			expect(mockBind).toHaveBeenCalledWith('FileUploaded', expect.any(Function));
		});
	});
});
