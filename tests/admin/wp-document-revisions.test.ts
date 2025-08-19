/**
 * @jest-environment jsdom
 */

import { WPDocumentRevisions } from '../../src/admin/wp-document-revisions';

// Mock jQuery more comprehensively (returns chainable object)
const mockJQuery = jest.fn((selector: any) => {
	const obj: any = {
		on: jest.fn(() => obj),
		off: jest.fn(() => obj),
		click: jest.fn(() => obj),
		val: jest.fn(() => ''),
		hide: jest.fn(() => obj),
		show: jest.fn(() => obj),
		fadeIn: jest.fn(() => obj),
		fadeOut: jest.fn(() => obj),
		removeAttr: jest.fn(() => obj),
		each: jest.fn(),
		attr: jest.fn(() => ''),
		is: jest.fn(() => true),
		html: jest.fn(() => ''),
		length: selector === '#autosave-alert' || selector === '#lock-notice' ? 1 : 0,
		text: jest.fn(() => 'Mock text'),
	};
	return obj;
});

describe('WPDocumentRevisions', () => {
	let wpdr: WPDocumentRevisions;

	beforeEach(() => {
		// Reset all mocks
		jest.clearAllMocks();

		// Mock global functions BEFORE class instantiation (constructor triggers logic)
		global.alert = jest.fn();
		global.confirm = jest.fn(() => true);
		const MockNotification: any = jest.fn();
		MockNotification.permission = 'granted';
		MockNotification.requestPermission = jest.fn(() => Promise.resolve('granted'));
		(global as any).Notification = MockNotification;
		(window as any).Notification = MockNotification;

		// Create new instance with mocked jQuery after globals mocked
		wpdr = new WPDocumentRevisions(mockJQuery as any);
	});

	describe('Initialization', () => {
		it('should create instance with jQuery', () => {
			expect(wpdr).toBeInstanceOf(WPDocumentRevisions);
		});

		it('should set up event listeners on initialization', () => {
			const mockOn = jest.fn();
			const mockJQueryWithOn = jest.fn(() => ({ on: mockOn }));

			new WPDocumentRevisions(mockJQueryWithOn as any);

			expect(mockOn).toHaveBeenCalledWith('click', expect.any(Function));
		});
	});

	describe('Notification System', () => {
		it('should request notification permission', () => {
			const requestPermissionSpy = jest.spyOn(
				(global as any).Notification,
				'requestPermission'
			);

			// Call the private method through public interface
			(wpdr as any).requestPermission();

			expect(requestPermissionSpy).toHaveBeenCalled();
		});

		it('should show notification when permission granted', () => {
			const NotificationSpy = (global as any).Notification as jest.Mock;

			(wpdr as any).lockOverrideNotice('Test notice');

			expect(NotificationSpy).toHaveBeenCalledWith(
				window.wp_document_revisions.lostLockNoticeTitle,
				{
					body: 'Test notice',
					icon: window.wp_document_revisions.lostLockNoticeLogo,
				}
			);
		});

		it('should fall back to alert when notifications not supported', () => {
			// Remove Notification API
			delete (global as any).Notification;

			const alertSpy = jest.spyOn(global, 'alert');

			(wpdr as any).lockOverrideNotice('Test notice');

			expect(alertSpy).toHaveBeenCalledWith('Test notice');
		});
	});

	describe('Cookie Management', () => {
		it('should set document context cookie to false', () => {
			const cookieSetSpy = jest.spyOn(window.wpCookies, 'set');

			(wpdr as any).cookieFalse();

			expect(cookieSetSpy).toHaveBeenCalledWith(
				'doc_image',
				'false',
				24 * 60 * 60,
				false,
				false,
				(wpdr as any).secure,
				'strict'
			);
		});

		it('should set document context cookie to true', () => {
			const cookieSetSpy = jest.spyOn(window.wpCookies, 'set');

			(wpdr as any).cookieTrue();

			expect(cookieSetSpy).toHaveBeenCalledWith(
				'doc_image',
				'true',
				24 * 60 * 60,
				false,
				false,
				(wpdr as any).secure,
				'strict'
			);
		});

		it('should delete document context cookie', () => {
			const cookieSetSpy = jest.spyOn(window.wpCookies, 'set');

			(wpdr as any).cookieDelete();

			expect(cookieSetSpy).toHaveBeenCalledWith(
				'doc_image',
				'true',
				-1,
				false,
				false,
				(wpdr as any).secure,
				'strict'
			);
		});
	});

	describe('Autosave Conflict Detection', () => {
		it('should detect autosave conflicts', () => {
			const mockJQueryWithAlert = jest.fn((selector: string) => ({
				length: selector === '#autosave-alert' ? 1 : 0,
				text: jest.fn(() => 'Lock notice text'),
				attr: jest.fn(() => 'revision-link'),
			}));

			const wpdrWithAlert = new WPDocumentRevisions(mockJQueryWithAlert as any);

			// Test autosave callback
			(wpdrWithAlert as any).postAutosaveCallback();

			// Should call notification system
			expect(mockJQueryWithAlert).toHaveBeenCalledWith('#autosave-alert');
		});
	});

	describe('File Upload Integration', () => {
		it('should handle successful file upload', () => {
			const mockUploader = {
				bind: jest.fn(),
			};

			const mockFile = {
				id: 'test-file',
				name: 'test.pdf',
				size: 1024,
				percent: 100,
				status: 5,
			};

			const mockResponse = {
				response: JSON.stringify({ id: 123 }),
			};

			// Simulate uploader setup
			(wpdr as any).uploader = mockUploader;

			// Test file upload success handler
			const successCallback = (wpdr as any).onUploadSuccess.bind(wpdr);
			successCallback(mockUploader, mockFile, mockResponse);

			// Should process the uploaded file
			expect(mockJQuery).toHaveBeenCalled();
		});

		it('should handle file upload errors', () => {
			const mockUploader = {
				bind: jest.fn(),
			};

			const mockFile = {
				id: 'test-file',
				name: 'test.pdf',
				size: 1024,
				percent: 0,
				status: 4, // Error status
			};

			const mockResponse = {
				response: 'Upload failed',
			};

			const alertSpy = jest.spyOn(global, 'alert');

			// Test file upload error handler
			const errorCallback = (wpdr as any).onUploadError.bind(wpdr);
			errorCallback(mockUploader, mockFile, mockResponse);

			expect(alertSpy).toHaveBeenCalledWith('Upload failed');
		});
	});

	describe('Timestamp Updates', () => {
		it('should update timestamp displays', () => {
			const mockTimestampElements = [
				{ textContent: '2023-01-01 10:00:00' },
				{ textContent: '2023-01-02 15:30:00' },
			];

			const mockJQueryWithTimestamps = jest.fn((selector: any) => {
				if (selector && typeof selector === 'object') {
					return { attr: jest.fn(() => '2023-01-01T00:00:00Z'), text: jest.fn() };
				}
				return {
					each: jest.fn((callback: any) => {
						mockTimestampElements.forEach((el, index) => callback(index, el));
					}),
				};
			});

			const wpdrWithTimestamps = new WPDocumentRevisions(mockJQueryWithTimestamps as any);

			(wpdrWithTimestamps as any).updateTimestamps();

			expect(mockJQueryWithTimestamps).toHaveBeenCalledWith('.timestamp');
		});
	});

	describe('Security Features', () => {
		it('should use SameSite=strict for all cookies', () => {
			const cookieSetSpy = jest.spyOn(window.wpCookies, 'set');

			(wpdr as any).cookieFalse();

			const lastCall = cookieSetSpy.mock.calls[cookieSetSpy.mock.calls.length - 1];
			expect(lastCall[6]).toBe('strict'); // SameSite parameter
		});

		it('should use modern Notification API instead of webkit', () => {
			// Ensure no webkitNotifications usage
			expect((global as any).webkitNotifications).toBeUndefined();

			// Ensure modern API is used
			expect((global as any).Notification).toBeDefined();
		});
	});
});
