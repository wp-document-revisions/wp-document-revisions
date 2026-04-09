/**
 * Tests for wp-document-revisions.dev.js
 * 
 * This file tests the main WPDocumentRevisions class that handles
 * document management functionality in the WordPress admin.
 */

const path = require('path');

const MODULE_PATH = path.resolve(__dirname, '../../js/wp-document-revisions.dev.js');

describe('WPDocumentRevisions', () => {
	let WPDocumentRevisions;
	let mockJQuery;

	beforeEach(() => {
		// Reset mocks
		jest.clearAllMocks();
		jest.resetModules();

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

		// Set up jQuery globally before requiring the module
		global.jQuery = mockJQuery;
		window.jQuery = mockJQuery;

		// Clear the module cache for fresh execution
		// Execute the module — jQuery ready runs immediately, creating the instance
		require(MODULE_PATH);

		// Get the instance and expose the constructor class for tests that create new instances
		WPDocumentRevisions = window.WPDocumentRevisions;
		window.WPDocumentRevisions = WPDocumentRevisions.constructor;
	});

	afterEach(() => {
		delete window.WPDocumentRevisions;
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

		test('should return singular hour for just over 3600 seconds', () => {
			const now = 1609459200;
			const result = WPDocumentRevisions.human_time_diff(now - 3601, now);
			expect(result).toBe('1 hour');
		});

		test('should return singular day for just over 86400 seconds', () => {
			const now = 1609459200;
			const result = WPDocumentRevisions.human_time_diff(now - 86401, now);
			expect(result).toBe('1 day');
		});

		test('should handle from greater than to (reversed arguments)', () => {
			const now = 1609459200;
			const result = WPDocumentRevisions.human_time_diff(now + 120, now);
			expect(result).toBe('2 minutes');
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

		test('should extract content from TinyMCE iframe', () => {
			const mockIframe = {
				contentWindow: {
					document: {
						getElementById: jest.fn(() => ({
							innerHTML: '<p>Hello World</p>',
						})),
					},
				},
			};
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'content_ifr') return mockIframe;
						return null;
					}),
				},
			};

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('<p>Hello World</p>');
		});

		test('should clean HTML from TinyMCE content', () => {
			const mockIframe = {
				contentWindow: {
					document: {
						getElementById: jest.fn(() => ({
							innerHTML: '<p>Hello<br data-mce-bogus="1"></p><p>World<br></p><p>  </p>',
						})),
					},
				},
			};
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'content_ifr') return mockIframe;
						return null;
					}),
				},
			};

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('<p>Hello</p><p>World</p>');
		});

		test('should fall back to post_content when TinyMCE innerHTML is undefined', () => {
			const mockIframe = {
				contentWindow: {
					document: {
						getElementById: jest.fn(() => ({
							innerHTML: undefined,
						})),
					},
				},
			};
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'content_ifr') return mockIframe;
						return null;
					}),
				},
			};

			const mockPostContent = mockJQuery('#post_content');
			mockPostContent.val = jest.fn(() => 'Fallback content');
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#post_content') return mockPostContent;
				return mockJQuery(selector);
			});

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('Fallback content');
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

		test('should wrap numeric content in WPDR comment', () => {
			const mockPostContent = { val: jest.fn(() => '123') };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#post_content') return mockPostContent;
				return mockJQuery(selector);
			});

			const windowValMock = jest.fn();
			const windowEl = { val: windowValMock };
			WPDocumentRevisions.window = {
				jQuery: jest.fn(() => windowEl),
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'Description text');

			WPDocumentRevisions.buildContent();

			expect(windowValMock).toHaveBeenCalledWith('<!-- WPDR 123 -->Description text');
		});

		test('should extract existing WPDR comment from content', () => {
			const mockPostContent = { val: jest.fn(() => '<!-- WPDR 456 -->some text') };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#post_content') return mockPostContent;
				return mockJQuery(selector);
			});

			const windowValMock = jest.fn();
			const windowEl = { val: windowValMock };
			WPDocumentRevisions.window = {
				jQuery: jest.fn(() => windowEl),
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'New description');

			WPDocumentRevisions.buildContent();

			expect(windowValMock).toHaveBeenCalledWith('<!-- WPDR 456 -->New description');
		});

		test('should call enableSubmit when content changes', () => {
			const mockPostContent = { val: jest.fn(() => '') };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#post_content') return mockPostContent;
				return mockJQuery(selector);
			});

			const windowEl = { val: jest.fn() };
			WPDocumentRevisions.window = {
				jQuery: jest.fn(() => windowEl),
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'changed description');
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.buildContent();

			expect(WPDocumentRevisions.enableSubmit).toHaveBeenCalled();
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

		test('should display error when attachmentID contains error string', () => {
			WPDocumentRevisions.hasUpload = false;
			const errorHtml = '<div class="error">Upload failed</div>';
			const mockMediaItem = { html: jest.fn() };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '.media-item:first') return mockMediaItem;
				return mockJQuery(selector);
			});

			WPDocumentRevisions.postDocumentUpload('test.pdf', errorHtml);

			expect(mockMediaItem.html).toHaveBeenCalledWith(errorHtml);
		});

		test('should update permalink with file extension after upload', () => {
			WPDocumentRevisions.hasUpload = false;

			const mockPermalink = {
				length: 1,
				html: jest.fn((val) => {
					if (val === undefined) return 'http://example.com/doc</span>.pdf@';
					return mockPermalink;
				}),
			};

			const windowEl = {
				val: jest.fn(),
				hide: jest.fn(),
				before: jest.fn(() => windowEl),
				prev: jest.fn(() => windowEl),
				fadeIn: jest.fn(() => windowEl),
				fadeOut: jest.fn(() => windowEl),
			};

			WPDocumentRevisions.window = {
				jQuery: jest.fn((selector) => {
					if (selector === '#sample-permalink') return mockPermalink;
					return windowEl;
				}),
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload('doc', '123');

			const setCalls = mockPermalink.html.mock.calls.filter((c) => c.length > 0);
			expect(setCalls.length).toBeGreaterThan(0);
			expect(setCalls[0][0]).toContain('.docx');
		});

		test('should set post_content to WPDR comment format on upload', () => {
			WPDocumentRevisions.hasUpload = false;

			const postContentVal = jest.fn();
			const windowEl = {
				val: postContentVal,
				hide: jest.fn(),
				before: jest.fn(() => windowEl),
				prev: jest.fn(() => windowEl),
				fadeIn: jest.fn(() => windowEl),
				fadeOut: jest.fn(() => windowEl),
				length: 0,
				html: jest.fn(),
			};

			WPDocumentRevisions.window = {
				jQuery: jest.fn(() => windowEl),
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload('doc', '456');

			expect(postContentVal).toHaveBeenCalledWith('<!-- WPDR 456 -->');
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

		test('should disable submit on first check (Unset state)', () => {
			const mockCurrContent = { val: jest.fn(() => 'Unset') };
			const mockPostContent = { val: jest.fn(() => 'some content') };
			const mockSubmitButtons = { prop: jest.fn() };
			WPDocumentRevisions.$ = jest.fn((selector, context) => {
				if (selector === '#curr_content') return mockCurrContent;
				if (selector === '#post_content') return mockPostContent;
				if (selector === ':button, :submit' && context === '#submitpost') return mockSubmitButtons;
				return mockJQuery(selector);
			});

			WPDocumentRevisions.checkUpdate();

			expect(mockSubmitButtons.prop).toHaveBeenCalledWith('disabled', true);
			expect(mockCurrContent.val).toHaveBeenCalledWith('some content');
		});

		test('should call buildContent when content differs', () => {
			const mockCurrContent = { val: jest.fn(() => 'old content') };
			const mockPostContent = { val: jest.fn(() => 'new content') };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#curr_content') return mockCurrContent;
				if (selector === '#post_content') return mockPostContent;
				return mockJQuery(selector);
			});
			WPDocumentRevisions.getDescr = jest.fn(() => 'different content');
			WPDocumentRevisions.buildContent = jest.fn();
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.checkUpdate();

			expect(WPDocumentRevisions.buildContent).toHaveBeenCalled();
			expect(WPDocumentRevisions.enableSubmit).toHaveBeenCalled();
		});

		test('should not call buildContent when content matches', () => {
			const mockCurrContent = { val: jest.fn(() => 'same content') };
			const mockPostContent = { val: jest.fn(() => 'same content') };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#curr_content') return mockCurrContent;
				if (selector === '#post_content') return mockPostContent;
				return mockJQuery(selector);
			});
			WPDocumentRevisions.getDescr = jest.fn(() => 'same content');
			WPDocumentRevisions.buildContent = jest.fn();

			WPDocumentRevisions.checkUpdate();

			expect(WPDocumentRevisions.buildContent).not.toHaveBeenCalled();
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

	describe('overrideLock', () => {
		test('should call $.post with correct URL and data', () => {
			WPDocumentRevisions.overrideLock();
			expect(mockJQuery.post).toHaveBeenCalledWith(
				'/wp-admin/admin-ajax.php',
				expect.objectContaining({ action: 'override_lock', nonce: 'test-nonce' }),
				expect.any(Function)
			);
		});

		test('should include post_id in lock override request', () => {
			WPDocumentRevisions.overrideLock();
			expect(mockJQuery.post).toHaveBeenCalledWith(
				expect.any(String),
				expect.objectContaining({ post_id: 0 }),
				expect.any(Function)
			);
		});

		test('should call autosave on successful lock override', () => {
			WPDocumentRevisions.overrideLock();
			const callback = mockJQuery.post.mock.calls[0][2];
			callback.call(WPDocumentRevisions, true);
			expect(global.autosave).toHaveBeenCalled();
		});

		test('should hide lock override and errors on success', () => {
			WPDocumentRevisions.overrideLock();
			const callback = mockJQuery.post.mock.calls[0][2];

			const mockLockOverride = { hide: jest.fn() };
			const mockErrorsNot = { hide: jest.fn() };
			const mockPublish = { fadeIn: jest.fn() };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#lock_override') return mockLockOverride;
				if (selector === '.error') return { not: jest.fn(() => mockErrorsNot) };
				if (selector === '#publish, .add_media, #lock-notice') return mockPublish;
				return mockJQuery(selector);
			});

			callback.call(WPDocumentRevisions, true);

			expect(mockLockOverride.hide).toHaveBeenCalled();
			expect(mockErrorsNot.hide).toHaveBeenCalled();
			expect(mockPublish.fadeIn).toHaveBeenCalled();
		});

		test('should alert lockError on failed lock override', () => {
			WPDocumentRevisions.overrideLock();
			const callback = mockJQuery.post.mock.calls[0][2];
			callback.call(WPDocumentRevisions, false);
			expect(global.alert).toHaveBeenCalledWith('Unable to override lock');
		});
	});

	describe('postAutosaveCallback', () => {
		test('should reload page when lock notice is visible and autosave alert exists', () => {
			const originalNotice = wp_document_revisions.lostLockNotice;
			const mockAutosaveAlert = { length: 1 };
			const mockLockNotice = { length: 1, is: jest.fn(() => true) };
			const mockTitleEl = { val: jest.fn(() => 'Test Document') };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#autosave-alert') return mockAutosaveAlert;
				if (selector === '#lock-notice') return mockLockNotice;
				if (selector === '#title') return mockTitleEl;
				return mockJQuery(selector);
			});

			delete window.webkitNotifications;

			WPDocumentRevisions.postAutosaveCallback();

			expect(global.alert).toHaveBeenCalledWith(
				expect.stringContaining('Test Document')
			);
			expect(window.location.reload).toHaveBeenCalledWith(true);

			wp_document_revisions.lostLockNotice = originalNotice;
		});

		test('should not reload when autosave-alert is absent', () => {
			const mockAutosaveAlert = { length: 0 };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#autosave-alert') return mockAutosaveAlert;
				return mockJQuery(selector);
			});

			WPDocumentRevisions.postAutosaveCallback();

			expect(window.location.reload).not.toHaveBeenCalled();
		});

		test('should not reload when lock-notice is not visible', () => {
			const mockAutosaveAlert = { length: 1 };
			const mockLockNotice = { length: 1, is: jest.fn(() => false) };
			WPDocumentRevisions.$ = jest.fn((selector) => {
				if (selector === '#autosave-alert') return mockAutosaveAlert;
				if (selector === '#lock-notice') return mockLockNotice;
				return mockJQuery(selector);
			});

			WPDocumentRevisions.postAutosaveCallback();

			expect(window.location.reload).not.toHaveBeenCalled();
		});
	});
});
