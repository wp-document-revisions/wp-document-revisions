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

	// Default mock element factory for document.getElementById
	const mockElement = (id) => ({
		id,
		innerHTML: '',
		value: '',
		style: { display: '' },
		classList: { remove: jest.fn() },
		getElementsByTagName: jest.fn(() => []),
		addEventListener: jest.fn(),
		removeAttribute: jest.fn(),
		getAttribute: jest.fn(() => ''),
		querySelector: jest.fn(() => null),
		querySelectorAll: jest.fn(() => []),
		insertAdjacentHTML: jest.fn(),
		previousElementSibling: null,
		offsetParent: null,
		disabled: false,
		contentWindow: {
			document: {
				getElementById: jest.fn(() => ({ innerHTML: '' })),
			},
		},
	});

	beforeEach(() => {
		// Reset mocks
		jest.clearAllMocks();
		jest.resetModules();

		// Reset document mocks to defaults (tests may override these)
		document.getElementById = jest.fn((id) => mockElement(id));
		document.querySelector = jest.fn(() => null);
		document.querySelectorAll = jest.fn(() => []);

		// Mock wp.apiFetch — overrideLock uses parse:false so returns a Response-like object
		global.wp.apiFetch = jest.fn(() =>
			Promise.resolve({ text: () => Promise.resolve('1') })
		);

		// Execute the module — readyState is 'complete' in jsdom, so constructor runs immediately
		require(MODULE_PATH);

		// Get the instance and expose the constructor class for tests that create new instances
		WPDocumentRevisions = window.WPDocumentRevisions;
		window.WPDocumentRevisions = WPDocumentRevisions.constructor;
	});

	afterEach(() => {
		delete window.WPDocumentRevisions;
	});

	describe('Constructor and Initialization', () => {
		test('should create an instance on window', () => {
			expect(WPDocumentRevisions).toBeDefined();
			expect(WPDocumentRevisions.constructor.name).toBe('WPDocumentRevisions');
		});

		test('should set initial hasUpload to false', () => {
			expect(WPDocumentRevisions.hasUpload).toBe(false);
		});

		test('should detect secure protocol', () => {
			window.location.protocol = 'https:';
			const instance = new window.WPDocumentRevisions();
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
			
			const instance = new window.WPDocumentRevisions();
			expect(instance.autosaveEnableButtonsOriginal).toBe(originalAutosave);
		});

		test('should replace window.autosave_enable_buttons', () => {
			const originalAutosave = jest.fn();
			window.autosave_enable_buttons = originalAutosave;
			
			new window.WPDocumentRevisions();
			expect(window.autosave_enable_buttons).not.toBe(originalAutosave);
		});
	});

	describe('enableSubmit', () => {
		test('should show lock override previous sibling', () => {
			const mockPrev = { style: { display: 'none' } };
			const mockLockOverride = { previousElementSibling: mockPrev };
			document.getElementById = jest.fn((id) => {
				if (id === 'lock_override') return mockLockOverride;
				if (id === 'revision-summary') return { style: { display: 'none' } };
				return null;
			});
			document.querySelectorAll = jest.fn(() => []);

			WPDocumentRevisions.enableSubmit();
			expect(mockPrev.style.display).toBe('');
		});

		test('should show revision summary', () => {
			const mockRevSummary = { style: { display: 'none' } };
			document.getElementById = jest.fn((id) => {
				if (id === 'revision-summary') return mockRevSummary;
				return null;
			});
			document.querySelectorAll = jest.fn(() => []);

			WPDocumentRevisions.enableSubmit();
			expect(mockRevSummary.style.display).toBe('');
		});

		test('should remove disabled from submit buttons', () => {
			const mockButton = { removeAttribute: jest.fn() };
			document.getElementById = jest.fn(() => null);
			document.querySelectorAll = jest.fn(() => [mockButton]);

			WPDocumentRevisions.enableSubmit();
			expect(mockButton.removeAttribute).toHaveBeenCalledWith('disabled');
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
			document.querySelectorAll = jest.fn(() => []);
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
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => null),
				},
			};
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '' };
				return null;
			});

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('');
		});

		test('should return empty string when post_content is only digits', () => {
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => null),
				},
			};
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '12345' };
				return null;
			});

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

			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: 'Fallback content' };
				return null;
			});

			const result = WPDocumentRevisions.getDescr();
			expect(result).toBe('Fallback content');
		});
	});

	describe('buildContent', () => {
		test('should handle empty content', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '' };
				return null;
			});
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => ({ value: '' })),
				},
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'Description');

			WPDocumentRevisions.buildContent();

			// Should have called getDescr
			expect(WPDocumentRevisions.getDescr).toHaveBeenCalled();
		});

		test('should handle numeric content (document ID)', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '12345' };
				return null;
			});
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => ({ value: '' })),
				},
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'Description');

			WPDocumentRevisions.buildContent();

			// Should have called getDescr
			expect(WPDocumentRevisions.getDescr).toHaveBeenCalled();
		});

		test('should wrap numeric content in WPDR comment', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '123' };
				return null;
			});

			const currContentEl = { value: '' };
			const postContentEl = { value: '' };
			const contentEl = { value: '' };
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'curr_content') return currContentEl;
						if (id === 'post_content') return postContentEl;
						if (id === 'content') return contentEl;
						return null;
					}),
				},
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'Description text');
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.buildContent();

			expect(currContentEl.value).toBe('<!-- WPDR 123 -->Description text');
		});

		test('should extract existing WPDR comment from content', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '<!-- WPDR 456 -->some text' };
				return null;
			});

			const currContentEl = { value: '' };
			const postContentEl = { value: '' };
			const contentEl = { value: '' };
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'curr_content') return currContentEl;
						if (id === 'post_content') return postContentEl;
						if (id === 'content') return contentEl;
						return null;
					}),
				},
			};
			WPDocumentRevisions.getDescr = jest.fn(() => 'New description');
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.buildContent();

			expect(currContentEl.value).toBe('<!-- WPDR 456 -->New description');
		});

		test('should call enableSubmit when content changes', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'post_content') return { value: '' };
				return null;
			});

			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => ({ value: '' })),
				},
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
				document: {
					getElementById: jest.fn(() => ({ value: '', style: { display: '' }, innerHTML: '', insertAdjacentHTML: jest.fn() })),
				},
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload(fileObject, '123');

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
				document: {
					getElementById: jest.fn(() => ({ value: '', style: { display: '' }, innerHTML: '', insertAdjacentHTML: jest.fn() })),
				},
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload('pdf', '123');

			expect(WPDocumentRevisions.hasUpload).toBe(true);
		});

		test('should call tb_remove after upload', () => {
			WPDocumentRevisions.hasUpload = false;
			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn(() => ({ value: '', style: { display: '' }, innerHTML: '', insertAdjacentHTML: jest.fn() })),
				},
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload('pdf', '123');

			expect(WPDocumentRevisions.window.tb_remove).toHaveBeenCalled();
		});

		test('should display error when attachmentID contains error string', () => {
			WPDocumentRevisions.hasUpload = false;
			const errorHtml = '<div class="error">Upload failed</div>';
			const mockMediaItem = { innerHTML: '' };
			document.querySelector = jest.fn((sel) => {
				if (sel === '.media-item') return mockMediaItem;
				return null;
			});

			WPDocumentRevisions.postDocumentUpload('test.pdf', errorHtml);

			expect(mockMediaItem.innerHTML).toBe(errorHtml);
		});

		test('should update permalink with file extension after upload', () => {
			WPDocumentRevisions.hasUpload = false;

			const mockPermalink = {
				innerHTML: 'http://example.com/doc</span>.pdf@',
			};

			const defaultEl = { value: '', style: { display: '' }, innerHTML: '', insertAdjacentHTML: jest.fn() };

			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'sample-permalink') return mockPermalink;
						return defaultEl;
					}),
				},
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload('doc', '123');

			expect(mockPermalink.innerHTML).toContain('.docx');
		});

		test('should set post_content to WPDR comment format on upload', () => {
			WPDocumentRevisions.hasUpload = false;

			const postContentEl = { value: '' };
			const defaultEl = { value: '', style: { display: '' }, innerHTML: '', insertAdjacentHTML: jest.fn() };

			WPDocumentRevisions.window = {
				document: {
					getElementById: jest.fn((id) => {
						if (id === 'post_content') return postContentEl;
						return defaultEl;
					}),
				},
				tb_remove: jest.fn(),
			};
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.postDocumentUpload('doc', '456');

			expect(postContentEl.value).toBe('<!-- WPDR 456 -->');
		});
	});

	describe('checkUpdate', () => {
		test('should return early if curr_content element does not exist', () => {
			document.getElementById = jest.fn(() => null);

			const result = WPDocumentRevisions.checkUpdate();

			expect(result).toBeUndefined();
		});

		test('should disable submit on first check (Unset state)', () => {
			const mockCurrContent = { value: 'Unset' };
			const mockPostContent = { value: 'some content' };
			const mockButton = { disabled: false };
			document.getElementById = jest.fn((id) => {
				if (id === 'curr_content') return mockCurrContent;
				if (id === 'post_content') return mockPostContent;
				return null;
			});
			document.querySelectorAll = jest.fn(() => [mockButton]);

			WPDocumentRevisions.checkUpdate();

			expect(mockButton.disabled).toBe(true);
			expect(mockCurrContent.value).toBe('some content');
		});

		test('should call buildContent when content differs', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'curr_content') return { value: 'old content' };
				if (id === 'post_content') return { value: 'new content' };
				return null;
			});
			WPDocumentRevisions.getDescr = jest.fn(() => 'different content');
			WPDocumentRevisions.buildContent = jest.fn();
			WPDocumentRevisions.enableSubmit = jest.fn();

			WPDocumentRevisions.checkUpdate();

			expect(WPDocumentRevisions.buildContent).toHaveBeenCalled();
			expect(WPDocumentRevisions.enableSubmit).toHaveBeenCalled();
		});

		test('should not call buildContent when content matches', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'curr_content') return { value: 'same content' };
				if (id === 'post_content') return { value: 'same content' };
				return null;
			});
			WPDocumentRevisions.getDescr = jest.fn(() => 'same content');
			WPDocumentRevisions.buildContent = jest.fn();

			WPDocumentRevisions.checkUpdate();

			expect(WPDocumentRevisions.buildContent).not.toHaveBeenCalled();
		});
	});

	describe('updateTimestamps', () => {
		test('should update all timestamp elements', () => {
			const mockEl = { id: '1609459200', textContent: '' };
			document.querySelectorAll = jest.fn((sel) => {
				if (sel === '.timestamp') return [mockEl];
				return [];
			});

			WPDocumentRevisions.human_time_diff = jest.fn(() => '5 minutes');

			WPDocumentRevisions.updateTimestamps();

			expect(mockEl.textContent).toBe('5 minutes');
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
		test('should call wp.apiFetch with correct URL and data', () => {
			document.getElementById = jest.fn(() => null);

			WPDocumentRevisions.overrideLock();

			expect(global.wp.apiFetch).toHaveBeenCalledWith(
				expect.objectContaining({
					url: '/wp-admin/admin-ajax.php',
					method: 'POST',
					parse: false,
				})
			);

			const opts = global.wp.apiFetch.mock.calls[0][0];
			expect(opts.body.get('action')).toBe('override_lock');
			expect(opts.body.get('nonce')).toBe('test-nonce');
		});

		test('should include post_id in lock override request', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'post_ID') return { value: '42' };
				return null;
			});

			WPDocumentRevisions.overrideLock();

			const opts = global.wp.apiFetch.mock.calls[0][0];
			expect(opts.body.get('post_id')).toBe('42');
		});

		test('should call preventDefault when event is provided', () => {
			const mockEvent = { preventDefault: jest.fn() };

			WPDocumentRevisions.overrideLock(mockEvent);

			expect(mockEvent.preventDefault).toHaveBeenCalled();
		});

		test('should call autosave on successful lock override', async () => {
			document.getElementById = jest.fn(() => null);
			document.querySelectorAll = jest.fn(() => []);

			await WPDocumentRevisions.overrideLock();
			expect(global.autosave).toHaveBeenCalled();
		});

		test('should hide lock override and errors on success', async () => {
			const mockLockOverride = { style: { display: '' } };
			const mockError = { style: { display: '' } };
			const mockPublish = { style: { display: 'none' } };

			document.getElementById = jest.fn((id) => {
				if (id === 'lock_override') return mockLockOverride;
				return null;
			});
			document.querySelectorAll = jest.fn((sel) => {
				if (sel === '.error:not(#lock-notice)') return [mockError];
				if (sel === '#publish, .add_media, #lock-notice') return [mockPublish];
				return [];
			});

			await WPDocumentRevisions.overrideLock();

			expect(mockLockOverride.style.display).toBe('none');
			expect(mockError.style.display).toBe('none');
			expect(mockPublish.style.display).toBe('');
		});

		test('should alert lockError on empty response', async () => {
			global.wp.apiFetch = jest.fn(() =>
				Promise.resolve({ text: () => Promise.resolve('') })
			);

			await WPDocumentRevisions.overrideLock();
			expect(global.alert).toHaveBeenCalledWith('Unable to override lock');
		});

		test('should alert lockError on non-1 response (e.g. -1)', async () => {
			global.wp.apiFetch = jest.fn(() =>
				Promise.resolve({ text: () => Promise.resolve('-1') })
			);

			await WPDocumentRevisions.overrideLock();
			expect(global.alert).toHaveBeenCalledWith('Unable to override lock');
		});

		test('should alert lockError on network error', async () => {
			global.wp.apiFetch = jest.fn(() =>
				Promise.reject(new Error('Network failure'))
			);

			await WPDocumentRevisions.overrideLock();
			expect(global.alert).toHaveBeenCalledWith('Unable to override lock');
		});
	});

	describe('autosaveEnableButtons', () => {
		test('should dispatch autosaveComplete event', () => {
			const dispatchSpy = jest.spyOn(document, 'dispatchEvent');

			WPDocumentRevisions.autosaveEnableButtons();

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'autosaveComplete' })
			);

			dispatchSpy.mockRestore();
		});

		test('should call original autosave when hasUpload is true', () => {
			WPDocumentRevisions.hasUpload = true;
			WPDocumentRevisions.autosaveEnableButtonsOriginal = jest.fn();

			WPDocumentRevisions.autosaveEnableButtons();

			expect(WPDocumentRevisions.autosaveEnableButtonsOriginal).toHaveBeenCalled();
		});

		test('should not call original autosave when hasUpload is false', () => {
			WPDocumentRevisions.hasUpload = false;
			WPDocumentRevisions.autosaveEnableButtonsOriginal = jest.fn();

			WPDocumentRevisions.autosaveEnableButtons();

			expect(WPDocumentRevisions.autosaveEnableButtonsOriginal).not.toHaveBeenCalled();
		});
	});

	describe('postAutosaveCallback', () => {
		test('should reload page when lock notice is visible and autosave alert exists', () => {
			const originalNotice = wp_document_revisions.lostLockNotice;
			document.getElementById = jest.fn((id) => {
				if (id === 'autosave-alert') return {};
				if (id === 'lock-notice') return { offsetParent: document.body };
				if (id === 'title') return { value: 'Test Document' };
				return null;
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
			document.getElementById = jest.fn(() => null);

			WPDocumentRevisions.postAutosaveCallback();

			expect(window.location.reload).not.toHaveBeenCalled();
		});

		test('should not reload when lock-notice is not visible', () => {
			document.getElementById = jest.fn((id) => {
				if (id === 'autosave-alert') return {};
				if (id === 'lock-notice') return { offsetParent: null };
				return null;
			});

			WPDocumentRevisions.postAutosaveCallback();

			expect(window.location.reload).not.toHaveBeenCalled();
		});
	});
});
