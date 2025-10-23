/**
 * Jest setup file for WP Document Revisions JavaScript tests
 * 
 * This file sets up the testing environment with WordPress and jQuery globals
 */

// Mock jQuery
global.jQuery = jest.fn((selector) => {
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
		each: jest.fn(() => element),
		before: jest.fn(() => element),
		prev: jest.fn(() => element),
		not: jest.fn(() => element),
		is: jest.fn(() => false),
		length: 0,
	};

	// Allow chaining
	if (typeof selector === 'function') {
		// jQuery ready function
		selector(jQuery);
	}

	return element;
});

global.jQuery.ajax = jest.fn();
global.jQuery.post = jest.fn();

// Alias for jQuery
global.$ = global.jQuery;

// Mock WordPress globals
global.wp = {
	blocks: {
		registerBlockType: jest.fn(),
		createBlock: jest.fn(),
	},
	element: {
		createElement: jest.fn(),
	},
	blockEditor: {
		InspectorControls: 'InspectorControls',
	},
	components: {
		PanelBody: 'PanelBody',
		RadioControl: 'RadioControl',
		RangeControl: 'RangeControl',
		TextControl: 'TextControl',
		TextareaControl: 'TextareaControl',
		ToggleControl: 'ToggleControl',
		CheckboxControl: 'CheckboxControl',
	},
	compose: {},
	serverSideRender: jest.fn(),
	i18n: {
		__: jest.fn((text) => text),
		_e: jest.fn((text) => text),
	},
};

// Mock WordPress API settings
global.wpApiSettings = {
	root: 'https://example.com/wp-json/',
	nonce: 'test-nonce',
};

// Mock window.location
delete window.location;
window.location = {
	href: 'https://example.com',
	protocol: 'https:',
	reload: jest.fn(),
};

// Mock window.dialogArguments (used by IE) - must be explicitly undefined
window.dialogArguments = undefined;

// Mock document methods FIRST before assigning to parent/top
const mockGetElementById = jest.fn((id) => ({
	id,
	innerHTML: '',
	style: { display: 'block' },
	classList: {
		remove: jest.fn(),
	},
	getElementsByTagName: jest.fn(() => []),
	contentWindow: {
		document: {
			getElementById: jest.fn(() => ({ innerHTML: '' })),
		},
	},
}));

const mockGetElementsByClassName = jest.fn(() => []);

global.document.getElementById = mockGetElementById;
global.document.getElementsByClassName = mockGetElementsByClassName;

// Add document to window mock
window.document = global.document;

// Mock window parent/opener/top for iframe context
// These need to be set BEFORE any code evals that references them
// The WPDocumentRevisions code does: window.dialogArguments || opener || parent || top
// So we need opener to be null, and parent/top to have document
global.opener = null;
global.parent = {
	document: global.document,
	jQuery: global.jQuery,
};
global.top = {
	document: global.document,
	jQuery: global.jQuery,
};

// Mock alert and confirm
global.alert = jest.fn();
global.confirm = jest.fn(() => true);

// Mock wpCookies
global.wpCookies = {
	set: jest.fn(),
	get: jest.fn(),
};

// Mock WordPress document revisions object
global.wp_document_revisions = {
	restoreConfirmation: 'Are you sure you want to restore this revision?',
	nonce: 'test-nonce',
	lockError: 'Unable to override lock',
	lostLockNoticeLogo: 'logo.png',
	lostLockNoticeTitle: 'Lock Override',
	lostLockNotice: 'The lock for %s has been overridden',
	postUploadNotice: '<div>File uploaded successfully</div>',
	extension: '.docx',
	offset: 0,
	minute: '%d minute',
	minutes: '%d minutes',
	hour: '%d hour',
	hours: '%d hours',
	day: '%d day',
	days: '%d days',
};

// Mock wpdr_data for Gutenberg blocks
global.wpdr_data = {
	stmax: 0,
	taxos: [],
	wf_efpp: '0',
};

// Mock ajaxurl
global.ajaxurl = '/wp-admin/admin-ajax.php';

// Mock nonce and user for validation script
global.nonce = 'test-nonce';
global.user = 1;
global.processed = 'Processed';

// Mock autosave functions
global.autosave = jest.fn();
global.autosave_enable_buttons = jest.fn();

// Mock uploader
global.uploader = null;

// Mock setInterval and clearInterval
global.setInterval = jest.fn((callback, delay) => {
	return 1; // Return fake timer ID
});

global.clearInterval = jest.fn();

// Mock Date for consistent testing
global.Date.now = jest.fn(() => 1609459200000); // 2021-01-01 00:00:00 UTC
