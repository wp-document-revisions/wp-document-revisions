// @ts-check
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import { __, sprintf } from '@wordpress/i18n';

const SUBMIT_BUTTONS =
	'#submitpost button, #submitpost [type="submit"], #submitpost [type="button"]';

// Expose class globally so bind_upload_cb inline script can create instances.
window.WPDocumentRevisionsClass = null;

class WPDocumentRevisions {
	hasUpload = false;
	firstcheck = true;
	// Custom media frame that auto-closes after a fresh upload.
	frameRef = null;
	// The plupload instance we've already bound FileUploaded to avoid
	// re-binding when a cached frame's upload tab is re-activated).
	_boundUploader = null;

	constructor() {
		document.querySelectorAll( '.revision' ).forEach( ( el ) => {
			el.addEventListener( 'click', this.restoreRevision );
		} );
		document.getElementById( 'override_link' )?.addEventListener( 'click', this.overrideLock );
		document.querySelectorAll( '#document a' ).forEach( ( el ) => {
			el.addEventListener( 'click', this.requestPermission );
		} );
		document.addEventListener( 'autosaveComplete', this.postAutosaveCallback );
		document
			.querySelectorAll( SUBMIT_BUTTONS )
			.forEach( ( /** @type {HTMLButtonElement} */ el ) => {
				el.disabled = true;
			} );
		document.querySelectorAll( '#misc-publishing-actions a' ).forEach( ( el ) => {
			el.addEventListener( 'click', this.enableSubmit );
		} );
		document.querySelectorAll( 'input, select' ).forEach( ( el ) => {
			el.addEventListener( 'change', this.enableSubmit );
		} );
		document.querySelectorAll( 'input[type=text], textarea' ).forEach( ( el ) => {
			el.addEventListener( 'keyup', this.enableSubmit );
		} );
		document
			.getElementById( 'sample-permalink' )
			?.addEventListener( 'change', this.enableSubmit );
		document
			.getElementById( 'add-document-file' )
			?.addEventListener( 'click', this.openMediaFrame );
		const doc = document.getElementById( 'document' );
		if ( doc ) {
			doc.style.display = 'block';
		}
		const log = document.getElementById( 'revision-log' );
		if ( log ) {
			log.style.display = 'block';
		}
		const sum = document.getElementById( 'revision-summary' );
		if ( sum ) {
			sum.style.display = 'none';
		}
		document.querySelectorAll( '#postimagediv .inside' ).forEach( ( el ) => {
			el.addEventListener( 'click', this.enableSubmit );
		} );

		this.hijackAutosave();
		setInterval( this.updateTimestamps, 60000 );
		setInterval( this.checkUpdate, 1000 );
	}

	hijackAutosave = () => {
		this.autosaveEnableButtonsOriginal = window.autosave_enable_buttons;
		window.autosave_enable_buttons = this.autosaveEnableButtons;
	};

	autosaveEnableButtons = () => {
		document.dispatchEvent( new Event( 'autosaveComplete' ) );
		if ( this.hasUpload ) {
			return this.autosaveEnableButtonsOriginal();
		}
	};

	enableSubmit = () => {
		const sum = document.getElementById( 'revision-summary' );
		if ( sum ) {
			sum.style.display = '';
		}
		document.querySelectorAll( SUBMIT_BUTTONS ).forEach( ( el ) => {
			el.removeAttribute( 'disabled' );
		} );
		const lck = /** @type {HTMLElement | null} */ (
			document.getElementById( 'lock_override' )?.previousElementSibling ?? null
		);
		if ( lck ) {
			lck.style.display = '';
		}
	};

	clearUploadNotices = () => {
		const ids = [ 'wpdr-upload-confirm', 'wpdr-upload-error', 'message' ];
		ids.forEach( ( id ) => {
			const el = document.getElementById( id );
			if ( el ) {
				el.parentNode.removeChild( el );
			}
		} );
	};

	showUploadError = ( errorText ) => {
		this.clearUploadNotices();
		const post = document.getElementById( 'post' );
		if ( post ) {
			const safeText = errorText
				? String( errorText ).replace(
						/[<>&"]/g,
						( c ) =>
							( {
								'<': '&lt;',
								'>': '&gt;',
								'&': '&amp;',
								'"': '&quot;',
							} )[ c ]
				  )
				: '';
			const notice =
				'<div id="wpdr-upload-error" class="error"><p>' +
				__( 'Upload failed.', 'wp-document-revisions' ) +
				'</p></div>';
			// Insert localized notice, appending escaped error detail if available.
			let html = notice;
			if ( safeText ) {
				html = html.replace( '</p></div>', ' ' + safeText + '</p></div>' );
			}
			post.insertAdjacentHTML( 'beforebegin', html );
		}
	};

	documentUpload = ( attachmentId, extension ) => {
		this.hasUpload = true;
		this.clearUploadNotices();
		// Fields used only for test purposes with no functional use.
		/** @type {HTMLInputElement} */ ( document.getElementById( 'curr_attach' ) ).value =
			attachmentId;
		/** @type {HTMLInputElement} */ ( document.getElementById( 'attach_ext' ) ).value =
			extension;

		const message = document.getElementById( 'message' );
		if ( message ) {
			message.style.display = 'none';
		}

		const post = document.getElementById( 'post' );
		if ( post ) {
			const postUploadNotice =
				'<div id="message" class="updated"><p>' +
				__(
					'File uploaded successfully. Add a revision summary below (optional) and press <strong>Update</strong> to save your changes.',
					'wp-document-revisions'
				) +
				'</p></div>';
			post.insertAdjacentHTML( 'beforebegin', postUploadNotice );
		}
		// Show upload confirmation in the document metabox.
		const docMetabox =
			typeof document.querySelector === 'function'
				? document.querySelector( '#document .inside' )
				: null;
		if ( docMetabox ) {
			const uploadConfirm = document.createElement( 'p' );
			uploadConfirm.id = 'wpdr-upload-confirm';
			uploadConfirm.innerHTML =
				'<strong>&#10003; ' +
				__( 'New version uploaded. Press Update to save.', 'wp-document-revisions' ) +
				'</strong>';
			uploadConfirm.style.cssText = 'color:#00a32a;margin:8px 0;';
			const clearDiv = docMetabox.querySelector( '.clear' );
			if ( clearDiv ) {
				docMetabox.insertBefore( uploadConfirm, clearDiv );
			} else {
				docMetabox.appendChild( uploadConfirm );
			}
		}
		this.enableSubmit();
	};

	restoreRevision = ( e ) => {
		e.preventDefault();
		if (
			confirm(
				__(
					'Are you sure you want to restore this revision? If you do, no history will be lost. This revision will be copied and become the most recent revision.',
					'wp-document-revisions'
				)
			)
		) {
			window.location.href = e.target.getAttribute( 'href' );
		}
	};

	overrideLock = ( e ) => {
		if ( e ) {
			e.preventDefault();
		}
		const postId = /** @type {HTMLInputElement | null} */ (
			document.getElementById( 'post_ID' )
		);

		return apiFetch( {
			url: ajaxurl,
			method: 'POST',
			// admin-ajax expects form-encoded body, not JSON.
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams( {
				action: 'override_lock',
				post_id: postId ? postId.value : '0',
				nonce: wp_document_revisions.nonce,
			} ),
			parse: false,
		} )
			.then( ( response ) => response.text() )
			.then( ( data ) => {
				if ( data.trim() === '1' ) {
					const override = document.getElementById( 'lock_override' );
					if ( override ) {
						override.style.display = 'none';
					}
					document
						.querySelectorAll( '.error:not(#lock-notice)' )
						.forEach( ( /** @type {HTMLElement} */ el ) => {
							el.style.display = 'none';
						} );
					document
						.querySelectorAll( '#publish, .add_media, #lock-notice' )
						.forEach( ( /** @type {HTMLElement} */ el ) => {
							el.style.display = '';
						} );
					autosave();
				} else {
					alert(
						__(
							'An error has occurred, please try reloading the page.',
							'wp-document-revisions'
						)
					);
				}
			} )
			.catch( () => {
				alert(
					__(
						'An error has occurred, please try reloading the page.',
						'wp-document-revisions'
					)
				);
			} );
	};

	requestPermission = () => {
		if ( window.webkitNotifications != null ) {
			return window.webkitNotifications.requestPermission();
		}
	};

	lockOverrideNotice = ( notice ) => {
		if ( window.webkitNotifications.checkPermission() > 0 ) {
			return window.webkitNotifications.RequestPermission( lock_override_notice );
		}
		return window.webkitNotifications
			.createNotification(
				wp_document_revisions.lostLockNoticeLogo,
				__( 'Lost Document Lock', 'wp-document-revisions' ),
				notice
			)
			.show();
	};

	postAutosaveCallback = () => {
		const autosaveAlert = document.getElementById( 'autosave-alert' );
		const lockNotice = document.getElementById( 'lock-notice' );
		if ( autosaveAlert && lockNotice && lockNotice.offsetParent !== null ) {
			const title = /** @type {HTMLInputElement | null} */ (
				document.getElementById( 'title' )
			);
			const lostLockNotice = sprintf(
				// translators: %s is the title of the document.
				__(
					'Your lock on the document %s has been overridden. Any changes will be lost.',
					'wp-document-revisions'
				),
				title ? title.value : ''
			);
			if ( window.webkitNotifications ) {
				lock_override_notice( lostLockNotice );
			} else {
				alert( lostLockNotice );
			}
			// The legacy forceReload arg is a no-op in modern browsers; the DOM
			// typings declare reload() as zero-arg, so cast to preserve the call.
			return /** @type {( forceReload?: boolean ) => void } */ ( location.reload )( true );
		}
	};

	human_time_diff = ( from, to ) => {
		const d = new Date();
		to = to || d.getTime() / 1000 + parseInt( String( wp_document_revisions.offset ), 10 );
		const diff = Math.abs( to - from );
		if ( diff < 3600 ) {
			// Singular and plural share the "%d mins" msgid (matches the prior
			// localized strings), so a single form covers both.
			const mins = this.roundUp( diff / 60 );
			// translators: %d is the number of minutes.
			return sprintf( __( '%d mins', 'wp-document-revisions' ), mins );
		} else if ( diff < 86400 && diff >= 3600 ) {
			const hours = this.roundUp( diff / 3600 );
			if ( hours === 1 ) {
				// translators: %d is the number of hours (singular).
				return sprintf( __( '%d hour', 'wp-document-revisions' ), hours );
			}
			// translators: %d is the number of hours (plural).
			return sprintf( __( '%d hours', 'wp-document-revisions' ), hours );
		} else if ( diff >= 86400 ) {
			const days = this.roundUp( diff / 86400 );
			if ( days === 1 ) {
				// translators: %d is the number of days (singular).
				return sprintf( __( '%d day', 'wp-document-revisions' ), days );
			}
			// translators: %d is the number of days (plural).
			return sprintf( __( '%d days', 'wp-document-revisions' ), days );
		}
	};

	roundUp = ( n ) => {
		if ( n < 1 ) {
			n = 1;
		}
		return Math.round( n );
	};

	updateTimestamps = () => {
		document.querySelectorAll( '.timestamp' ).forEach( ( /** @type {HTMLElement} */ el ) => {
			const from = new Date( String( el.title ) );
			el.textContent = this.human_time_diff( from.getTime() / 1000 );
		} );
	};

	getDescr = () => {
		// Extract data from TinyMCE window and clean up text.
		// On starting, the post_content is set to BOTH fields content and post_content.
		const iframe = /** @type {HTMLIFrameElement | null} */ (
			document.getElementById( 'content_ifr' )
		);
		if ( null === iframe ) {
			const el = /** @type {HTMLTextAreaElement | null} */ (
				document.getElementById( 'post_content' )
			);
			return el ? el.value : '';
		}
		let text = iframe.contentWindow.document.getElementById( 'tinymce' ).innerHTML;
		if ( undefined === text ) {
			const el = /** @type {HTMLTextAreaElement | null} */ (
				document.getElementById( 'post_content' )
			);
			return el ? el.value : '';
		}
		text = text.replace( /<br data-mce-bogus="1">/g, '' );
		text = text.replace( /<br>\s*<\/p>/g, '</p>' );
		text = text.replace( /<p>\s*<\/p>/g, '' );
		return text;
	};

	checkUpdate = () => {
		const el = /** @type {HTMLTextAreaElement | null} */ (
			document.getElementById( 'post_content' )
		);
		if ( el == null ) {
			return;
		}
		const tinymce = this.getDescr();
		if ( this.firstcheck ) {
			el.value = tinymce;
			this.firstcheck = false;
		} else if ( tinymce !== el.value ) {
			// Check whether an update happened - via a temporary field.
			el.value = tinymce;
			this.enableSubmit();
		}
	};

	onSelectMedia = ( media ) => {
		// Placeholder hook for an explicitly selected attachment. The functional
		// upload handling is performed in documentUpload(); kept so the frame's
		// 'select' and post-upload handlers have a single extension point.
		void media;
	};

	openMediaFrame = ( e ) => {
		e.preventDefault();
		// Reuse existing frame if already created.
		if ( this.frameRef ) {
			this.frameRef.open();
			return;
		}

		// define a library with no content so no download or display.
		const restrictedLibrary = new wp.media.model.Attachments( [], {
			props: { orderby: 'date', order: 'DESC', query: true, uploadedTo: -1 },
		} );

		// Media upload with no existing uploads as an option and only allow one file to be loaded.
		const frame = wp.media( {
			title: __( 'Upload Document', 'wp-document-revisions' ),
			multiple: false,
			button: {
				text: __( 'Select Document', 'wp-document-revisions' ),
			},
			states: [
				new wp.media.controller.Library( {
					title: __( 'Upload Document', 'wp-document-revisions' ),
					filterable: 'uploaded',
					multiple: false,
					library: restrictedLibrary,
				} ),
			],
		} );

		// Remove the library tab.
		frame.on( 'menu:render:default', ( menu ) => {
			menu.unset( 'library' ); // remove the library tab
		} );

		// Open on upload tab.
		frame.on( 'open', () => {
			frame.content.mode( 'upload' ); // jump directly to upload tab#.
			frame.$el.find( '.media-router' ).addClass( 'hidden' ); // Hide tab bar
		} );

		// Standard select handler (user clicks "Select" button).
		frame.on( 'select', () => {
			const selected = frame.state().get( 'selection' ).first()?.toJSON();
			if ( selected ) {
				this.onSelectMedia( selected );
			}
		} );

		// When the uploader is ready, bind to uploader to auto-close and send an identifier for document.
		frame.on( 'uploader:ready', () => {
			const uploader = frame.uploader?.uploader?.uploader;

			// add an indicator that this is for a document (if not present, then a featured image).
			if ( uploader && uploader.settings && uploader.settings.multipart_params ) {
				uploader.settings.multipart_params.upload_source = 'wp-document-revisions';
			}
			// Bind once per uploader instance. This event can fire again when a
			// cached frame is reopened; without the guard the FileUploaded
			// handlers stack up and documentUpload() fires multiple times.
			if ( uploader && this._boundUploader !== uploader ) {
				this._boundUploader = uploader;
				uploader.bind( 'FileUploaded', ( up, file, response ) => {
					try {
						const data = JSON.parse( response.response );
						if ( data?.success && data?.data?.id ) {
							this.onSelectMedia( data.data );
							this.frameRef.close();
							// use legacy callback.
							const ext = data.data.filename
								? '.' + data.data.filename.split( '.' ).pop()
								: '';
							this.documentUpload( data.data.id, ext );
						}
					} catch {
						// Fall through to manual selection.
					}
				} );
			}
		} );

		frame.open();
		this.frameRef = frame;
	};
}

window.WPDocumentRevisionsClass = WPDocumentRevisions;

domReady( () => {
	window.WPDocumentRevisions = new WPDocumentRevisions();
} );
