/**
 * @jest-environment jsdom
 */

describe('WP Document Revisions Validate', () => {
	// Mock jQuery AJAX
	const mockAjax = jest.fn();
	(global as any).jQuery = {
		ajax: mockAjax,
	};

	beforeEach(() => {
		jest.clearAllMocks();
		jest.resetModules();

		// Set up global variables that the validate script expects
		(global as any).nonce = 'test-nonce';
		(global as any).user = 'test-user';
		(global as any).processed = 'false';

		// Clear module cache so IIFE re-runs for each test
		try {
			delete require.cache[require.resolve('../../src/admin/wp-document-revisions-validate')];
		} catch (e) {
			// ignore if not cached
		}
	});

	describe('AJAX Validation Request', () => {
		it('should make AJAX call with correct parameters', () => {
			// Import the module to trigger the AJAX call
			require('../../src/admin/wp-document-revisions-validate');

			expect(mockAjax).toHaveBeenCalledWith({
				url: window.ajaxurl,
				type: 'POST',
				data: {
					action: 'validate_structure',
					nonce: 'test-nonce',
					user: 'test-user',
				},
				beforeSend: expect.any(Function),
				success: expect.any(Function),
				error: expect.any(Function),
			});
		});

		it('should set security headers in beforeSend', () => {
			require('../../src/admin/wp-document-revisions-validate');

			const ajaxCall = mockAjax.mock.calls[0][0];
			const beforeSendCallback = ajaxCall.beforeSend;

			const mockXHR = {
				setRequestHeader: jest.fn(),
			};

			beforeSendCallback(mockXHR);

			expect(mockXHR.setRequestHeader).toHaveBeenCalledWith('X-WP-Nonce', 'test-nonce');
		});

		it('should handle successful response', () => {
			const consoleSpy = jest.spyOn(console, 'log').mockImplementation();

			require('../../src/admin/wp-document-revisions-validate');

			const ajaxCall = mockAjax.mock.calls[0][0];
			const successCallback = ajaxCall.success;

			const mockResponse = {
				success: true,
				data: {
					message: 'Validation complete',
					issues: [],
				},
			};

			successCallback(mockResponse);

			expect(consoleSpy).toHaveBeenCalledWith('Validation complete', mockResponse.data);

			consoleSpy.mockRestore();
		});

		it('should handle error response', () => {
			const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation();

			require('../../src/admin/wp-document-revisions-validate');

			const ajaxCall = mockAjax.mock.calls[0][0];
			const errorCallback = ajaxCall.error;

			const mockErrorResponse = {
				status: 500,
				statusText: 'Internal Server Error',
				responseText: 'Validation failed',
			};

			errorCallback(mockErrorResponse);

			expect(consoleErrorSpy).toHaveBeenCalledWith(
				'Validation request failed:',
				mockErrorResponse
			);

			consoleErrorSpy.mockRestore();
		});

		it('should only run validation once', () => {
			// Set processed to true to simulate already processed
			(global as any).processed = 'true';

			// Clear previous calls
			mockAjax.mockClear();

			// Re-import the module (cache already cleared in beforeEach won't run because we manually set processed 'true' before requiring)
			require('../../src/admin/wp-document-revisions-validate');

			// Should not make AJAX call when already processed
			expect(mockAjax).not.toHaveBeenCalled();
		});

		it('should handle missing nonce gracefully', () => {
			(global as any).nonce = undefined;

			const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation();

			require('../../src/admin/wp-document-revisions-validate');

			// Should warn about missing nonce but still attempt validation
			expect(consoleWarnSpy).toHaveBeenCalledWith('Security nonce not available');

			consoleWarnSpy.mockRestore();
		});
	});

	describe('Data Validation', () => {
		it('should validate required fields are present', () => {
			const originalUser = (global as any).user;
			(global as any).user = '';

			const consoleWarnSpy = jest.spyOn(console, 'warn').mockImplementation();

			require('../../src/admin/wp-document-revisions-validate');

			expect(consoleWarnSpy).toHaveBeenCalledWith('User identifier not available');

			(global as any).user = originalUser;
			consoleWarnSpy.mockRestore();
		});
	});
});
