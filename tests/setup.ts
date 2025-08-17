import '@testing-library/jest-dom';

// Mock WordPress globals
global.wp = {
	i18n: {
		__: (text: string) => text,
		_x: (text: string) => text,
		_n: (single: string, plural: string, number: number) => (number === 1 ? single : plural),
		sprintf: (format: string, ...args: any[]) => format,
	},
	blocks: {
		registerBlockType: jest.fn(),
	},
	element: {
		createElement: jest.fn(),
	},
	blockEditor: {
		InspectorControls: jest.fn(),
		BlockControls: jest.fn(),
		useBlockProps: jest.fn(() => ({})),
	},
	components: {
		PanelBody: jest.fn(),
		RangeControl: jest.fn(),
		TextControl: jest.fn(),
		SelectControl: jest.fn(),
		ToggleControl: jest.fn(),
		TextareaControl: jest.fn(),
		RadioControl: jest.fn(),
		Button: jest.fn(),
		Panel: jest.fn(),
		Placeholder: jest.fn(),
	},
	serverSideRender: jest.fn(),
};

// Mock jQuery with ready method
const createMockElement = (): any => ({
	on: jest.fn().mockReturnThis(),
	off: jest.fn().mockReturnThis(),
	click: jest.fn().mockReturnThis(),
	val: jest.fn().mockReturnThis(),
	hide: jest.fn().mockReturnThis(),
	show: jest.fn().mockReturnThis(),
	removeAttr: jest.fn().mockReturnThis(),
	each: jest.fn().mockReturnThis(),
	prop: jest.fn().mockReturnThis(),
	attr: jest.fn().mockReturnThis(),
	data: jest.fn().mockReturnThis(),
	find: jest.fn().mockReturnThis(),
	closest: jest.fn().mockReturnThis(),
	addClass: jest.fn().mockReturnThis(),
	removeClass: jest.fn().mockReturnThis(),
	toggleClass: jest.fn().mockReturnThis(),
	hasClass: jest.fn().mockReturnValue(false),
	html: jest.fn().mockReturnThis(),
	text: jest.fn().mockReturnThis(),
	append: jest.fn().mockReturnThis(),
	prepend: jest.fn().mockReturnThis(),
	remove: jest.fn().mockReturnThis(),
	empty: jest.fn().mockReturnThis(),
	focus: jest.fn().mockReturnThis(),
	blur: jest.fn().mockReturnThis(),
	change: jest.fn().mockReturnThis(),
	submit: jest.fn().mockReturnThis(),
	ready: jest.fn((callback?: any): any => {
		// Execute callback immediately in tests, providing the jQuery function as the $ parameter
		if (typeof callback === 'function') {
			// Pass the mock jQuery function so code using function ( $ ) { ... } works
			callback.call(createMockElement(), mockJQuery);
		}
		return createMockElement();
	}),
	length: 0,
});

const mockJQuery: any = jest.fn((_selector?: any, _context?: any) => {
	return createMockElement();
});

// Add static jQuery methods
mockJQuery.ajax = jest.fn();

(global as any).$ = mockJQuery;
(global as any).jQuery = mockJQuery;

// Mock WordPress Document Revisions globals (deduplicated)
global.window = global.window || {};
Object.assign(global.window, {
	wp_document_revisions: {
		nonce: 'test-nonce',
		restoreConfirmation: 'Are you sure?',
		lockError: 'Lock error',
		lostLockNotice: 'Lost lock notice',
		lostLockNoticeLogo: '/logo.png',
		lostLockNoticeTitle: 'Lock Lost',
		postUploadNotice: 'Upload complete',
		extension: 'pdf',
	},
	wpApiSettings: {
		root: 'https://example.com/wp-json/',
		nonce: 'api-nonce',
	},
	ajaxurl: 'https://example.com/wp-admin/admin-ajax.php',
	wpCookies: {
		set: jest.fn(),
	},
	autosave: jest.fn(),
	convertEntities: jest.fn((text: string) => text),
	uploader: {
		bind: jest.fn(),
		unbind: jest.fn(),
		trigger: jest.fn(),
	},
	tb_remove: jest.fn(),
});

// Mock Notification API
global.Notification = class MockNotification {
	static permission = 'granted';
	static requestPermission = jest.fn(() => Promise.resolve('granted'));
} as any;

// Mock console methods to reduce noise in tests
global.console = {
	...console,
	warn: jest.fn(),
	error: jest.fn(),
};
