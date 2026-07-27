/**
 * Component tests for the block-editor sidebar panels in
 * src/editor-document-upload/index.js.
 *
 * The pure helpers are covered in editor-document-upload.test.js; this file
 * renders the actual React panels (via @testing-library/react) to exercise the
 * branching that only happens at render/effect time: the post-type guards, the
 * save-lock effect, the lock indicator, file-info vs upload-button states, the
 * stale-attachment notice, and the revision-log fetch/empty/table states.
 *
 * The @wordpress/* runtime packages are webpack externals (not installed), so
 * they are mocked here. @wordpress/element is mapped to real React so the
 * component hooks actually run; @wordpress/data's useSelect/useDispatch and
 * core-data's useEntityProp read from the mutable fixtures the tests configure.
 */

import { render, screen, waitFor } from '@testing-library/react';

// jest.mock factories may only reference out-of-scope vars prefixed with `mock`.
let mockSelectStores = {};
let mockEntityProps = {};
let mockDispatch = {};
const mockRegisterPlugin = jest.fn();

// @wordpress/element and @wordpress/i18n are installed; the rest are webpack
// externals, so virtual-mock them.
jest.mock( '@wordpress/element', () => require( 'react' ) );
jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ) );
jest.mock(
	'@wordpress/plugins',
	() => ( { registerPlugin: ( name, config ) => mockRegisterPlugin( name, config ) } ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/editor',
	() => ( { PluginDocumentSettingPanel: ( { children } ) => <div>{ children }</div> } ),
	{ virtual: true }
);
jest.mock( '@wordpress/block-editor', () => ( { MediaUploadCheck: ( { children } ) => children } ), {
	virtual: true,
} );
jest.mock(
	'@wordpress/components',
	() => ( {
		Button: ( { children, onClick, disabled } ) => (
			<button onClick={ onClick } disabled={ disabled }>
				{ children }
			</button>
		),
		Spinner: () => <span role="progressbar" />,
		TextareaControl: ( { label, value, onChange, disabled } ) => (
			<textarea
				aria-label={ label }
				value={ value ?? '' }
				disabled={ disabled }
				onChange={ ( e ) => onChange( e.target.value ) }
			/>
		),
	} ),
	{ virtual: true }
);
jest.mock( '@wordpress/notices', () => ( { store: 'core/notices' } ), { virtual: true } );
jest.mock(
	'@wordpress/core-data',
	() => ( { useEntityProp: ( kind, type, prop ) => mockEntityProps[ prop ] } ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/data',
	() => ( {
		useSelect: ( cb ) => cb( ( storeName ) => mockSelectStores[ storeName ] || {} ),
		useDispatch: ( storeName ) => mockDispatch[ storeName ] || {},
	} ),
	{ virtual: true }
);

// Importing the module registers the two plugins; capture their render fns.
require( '../../src/editor-document-upload/index.js' );
const panelByName = ( name ) =>
	mockRegisterPlugin.mock.calls.find( ( c ) => c[ 0 ] === name )[ 1 ].render;
const UploadPanel = panelByName( 'wp-document-revisions-upload' );
const RevisionLogPanel = panelByName( 'wp-document-revisions-revision-log' );

// Fixture builders -------------------------------------------------------

const noticeMocks = () => ( {
	createSuccessNotice: jest.fn(),
	createErrorNotice: jest.fn(),
} );

function configureUpload( {
	postType = 'document',
	isNewPost = false,
	attachmentId = 0,
	attachment = null,
	isResolving = false,
	isLocked = false,
	lockUser = null,
	excerpt = '',
} = {} ) {
	mockSelectStores = {
		'core/editor': {
			getCurrentPostType: () => postType,
			isEditedPostNew: () => isNewPost,
			isPostLocked: () => isLocked,
			getPostLockUser: () => lockUser,
		},
		core: {
			getMedia: () => attachment,
		},
		'core/data': {
			isResolving: () => isResolving,
		},
	};
	mockEntityProps = {
		meta: [ { document_attachment_id: attachmentId }, jest.fn() ],
		excerpt: [ excerpt, jest.fn() ],
	};
	const dispatch = {
		lockPostSaving: jest.fn(),
		unlockPostSaving: jest.fn(),
	};
	const notices = noticeMocks();
	mockDispatch = { 'core/editor': dispatch, 'core/notices': notices };
	return { dispatch, notices };
}

beforeEach( () => {
	jest.clearAllMocks();
	mockSelectStores = {};
	mockEntityProps = {};
	mockDispatch = {};
} );

// DocumentUploadPanel ----------------------------------------------------

describe( 'DocumentUploadPanel', () => {
	test( 'renders nothing for a non-document post type', () => {
		configureUpload( { postType: 'post' } );
		const { container } = render( <UploadPanel /> );
		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'locks saving for a new document with no file, unlocks otherwise', () => {
		const { dispatch } = configureUpload( { isNewPost: true, attachmentId: 0 } );
		render( <UploadPanel /> );
		expect( dispatch.lockPostSaving ).toHaveBeenCalledWith( 'wp-document-revisions-upload' );

		const second = configureUpload( { isNewPost: true, attachmentId: 42, attachment: {} } );
		render( <UploadPanel /> );
		expect( second.dispatch.unlockPostSaving ).toHaveBeenCalledWith(
			'wp-document-revisions-upload'
		);
	} );

	test( 'shows the upload button labelled for a first upload', () => {
		configureUpload( { attachmentId: 0 } );
		render( <UploadPanel /> );
		expect(
			screen.getByRole( 'button', { name: 'Upload Document' } )
		).toBeInTheDocument();
	} );

	test( 'labels the button "Upload New Version" and shows file info when a file exists', () => {
		configureUpload( {
			attachmentId: 7,
			attachment: {
				title: { raw: 'contract.pdf' },
				source_url: 'https://x.test/contract.pdf',
				filename: 'contract.pdf',
			},
		} );
		render( <UploadPanel /> );
		expect(
			screen.getByRole( 'button', { name: 'Upload New Version' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'contract.pdf' ) ).toBeInTheDocument();
		expect( screen.getByText( 'PDF' ) ).toBeInTheDocument(); // extension badge
		expect( screen.getByRole( 'link', { name: 'Download' } ) ).toHaveAttribute(
			'href',
			'https://x.test/contract.pdf'
		);
	} );

	test( 'renders the lock indicator and disables the button when locked', () => {
		configureUpload( { isLocked: true, lockUser: { name: 'Ada' } } );
		render( <UploadPanel /> );
		expect( screen.getByText( 'Ada is currently editing this document.' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'button' ) ).toBeDisabled();
	} );

	test( 'shows a spinner while the attachment is resolving', () => {
		configureUpload( { attachmentId: 7, isResolving: true } );
		render( <UploadPanel /> );
		expect( screen.getByRole( 'progressbar' ) ).toBeInTheDocument();
	} );

	test( 'raises an error notice when the attachment is missing (deleted)', () => {
		const { notices } = configureUpload( {
			attachmentId: 7,
			attachment: null,
			isResolving: false,
		} );
		render( <UploadPanel /> );
		expect( notices.createErrorNotice ).toHaveBeenCalledWith(
			expect.stringContaining( 'could not be found' ),
			expect.objectContaining( { id: 'wpdr-stale-attachment' } )
		);
	} );

	test( 'reflects the current excerpt in the revision summary field', () => {
		configureUpload( { excerpt: 'Fixed section 4.2' } );
		render( <UploadPanel /> );
		expect( screen.getByLabelText( 'Revision Summary' ) ).toHaveValue( 'Fixed section 4.2' );
	} );
} );

// RevisionLogPanel -------------------------------------------------------

function configureRevisionLog( { postType = 'document', postId = 5, users = {} } = {} ) {
	mockSelectStores = {
		'core/editor': {
			getCurrentPostType: () => postType,
			getCurrentPostId: () => postId,
			isSavingPost: () => false,
		},
		core: {
			getUser: ( id ) => users[ id ] || null,
		},
	};
	mockEntityProps = {};
	mockDispatch = {};
}

describe( 'RevisionLogPanel', () => {
	beforeEach( () => {
		global.wp = global.wp || {};
		global.wp.apiFetch = jest.fn( () => Promise.resolve( [] ) );
		global.window.wpDocumentRevisions = { restBase: 'documents' };
	} );

	test( 'renders nothing without a document post + id', () => {
		configureRevisionLog( { postType: 'post' } );
		const { container } = render( <RevisionLogPanel /> );
		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'fetches revisions for the current document via the REST base', async () => {
		configureRevisionLog( { postId: 9 } );
		render( <RevisionLogPanel /> );
		await waitFor( () => expect( global.wp.apiFetch ).toHaveBeenCalled() );
		expect( global.wp.apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: expect.stringContaining( '/wp/v2/documents/9/revisions' ),
			} )
		);
	} );

	test( 'shows the empty state when there are no revisions', async () => {
		configureRevisionLog();
		global.wp.apiFetch = jest.fn( () => Promise.resolve( [] ) );
		render( <RevisionLogPanel /> );
		expect( await screen.findByText( 'No revisions yet.' ) ).toBeInTheDocument();
	} );

	test( 'renders a row per revision with resolved author and summary', async () => {
		configureRevisionLog( { users: { 3: { name: 'Grace' } } } );
		global.wp.apiFetch = jest.fn( () =>
			Promise.resolve( [
				{ id: 1, date: '2021-01-01T00:00:00', author: 3, excerpt: { raw: 'Initial draft' } },
			] )
		);
		render( <RevisionLogPanel /> );
		expect( await screen.findByText( 'Initial draft' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Grace' ) ).toBeInTheDocument();
	} );
} );
