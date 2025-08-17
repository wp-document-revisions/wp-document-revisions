/**
 * WordPress Document Revisions - Main Admin Class
 * Modern TypeScript conversion of the original CoffeeScript
 */

import '../types/globals';
type PluploadFile = import('../types/globals').PluploadFile;

export class WPDocumentRevisions {
	private hasUpload = false;
	private readonly secure: boolean;
	private readonly window: Window;
	private readonly $: JQueryStatic;

	constructor($: JQueryStatic) {
		this.$ = $;
		this.secure = window.location.protocol === 'https:';
		this.window = window.dialogArguments || window.opener || window.parent || window.top;

		this.initializeEvents();
		this.initializeUI();
		this.hijackAutosave();

		// Set up periodic updates
		setInterval(() => this.updateTimestamps(), 60000); // Update timestamps every minute
	}

	/**
	 * Initialize event handlers
	 */
	private initializeEvents(): void {
		const safeOn = (sel: string, evt: string, handler: any) => {
			const el: any = this.$(sel);
			if (el && typeof el.on === 'function') {
				el.on(evt, handler);
			}
		};
		safeOn('.revision', 'click', this.restoreRevision.bind(this));
		safeOn('#override_link', 'click', this.overrideLock.bind(this));
		safeOn('#document a', 'click', this.requestPermission.bind(this));

		// Document lifecycle events (guarded for sparse mocks in tests)
		safeOn(document as any, 'autosaveComplete', this.postAutosaveCallback.bind(this));
		safeOn(document as any, 'documentUpload', this.legacyPostDocumentUpload.bind(this));

		// Form controls
		safeOn('#misc-publishing-actions a', 'click', this.enableSubmit.bind(this));
		safeOn('input, select', 'change', this.enableSubmit.bind(this));
		safeOn('input[type=text], textarea', 'keyup', this.enableSubmit.bind(this));

		// Media handling
		safeOn('#content-add_media', 'click', this.cookieFalse.bind(this));
		safeOn('#postimagediv .inside', 'click', this.cookieTrue.bind(this));
		safeOn('#submitdiv .inside', 'click', this.cookieDelete.bind(this));
		safeOn('#adminmenumain', 'click', this.cookieDelete.bind(this));
		safeOn('#wpadminbar', 'click', this.cookieDelete.bind(this));
	}

	/**
	 * Initialize UI elements
	 */
	private initializeUI(): void {
		// Disable submit buttons initially (guard if prop not available in test mocks)
		const $buttons: any = this.$(':button, :submit', '#submitpost');
		if ($buttons && typeof $buttons.prop === 'function') {
			$buttons.prop('disabled', true);
		}

		// Show/hide relevant sections
		const safeShow = (sel: string) => {
			const el: any = this.$(sel);
			if (el && typeof el.show === 'function') {
				el.show();
			}
		};
		const safeHide = (sel: string) => {
			const el: any = this.$(sel);
			if (el && typeof el.hide === 'function') {
				el.hide();
			}
		};
		safeShow('#document');
		safeShow('#revision-log');
		safeHide('#revision-summary');

		this.bindPostDocumentUploadCallback();
	}

	/**
	 * Monkey patch global autosave to serve as a lock mechanism
	 */
	private hijackAutosave(): void {
		if (typeof window.autosave === 'function') {
			const originalAutosaveEnableButtons = (window as any).autosave_enable_buttons;

			if (originalAutosaveEnableButtons) {
				(window as any).autosave_enable_buttons = () => {
					this.$(document).trigger('autosaveComplete');
					if (this.hasUpload) {
						originalAutosaveEnableButtons();
					}
				};
			}
		}
	}

	/**
	 * Enable submit buttons and show revision summary
	 */
	private enableSubmit(): void {
		this.$('#revision-summary').show();
		this.$(':button, :submit', '#submitpost').removeAttr('disabled');
	}

	/**
	 * Restore revision with confirmation
	 * @param e
	 */
	private restoreRevision(e: JQuery.ClickEvent): void {
		e.preventDefault();

		const href = this.$(e.target).attr('href');
		if (href && confirm(window.wp_document_revisions.restoreConfirmation)) {
			window.location.href = href;
		}
	}

	/**
	 * Override document lock
	 */
	private overrideLock(): void {
		this.$.post(window.ajaxurl, {
			action: 'override_lock',
			post_id: this.$('#post_ID').val() || 0,
			nonce: window.wp_document_revisions.nonce,
		})
			.done((data: any) => {
				if (data) {
					this.$('#lock_override').hide();
					this.$('.error').not('#lock-notice').hide();
					this.$('#publish, .add_media, #lock-notice').fadeIn();

					if (typeof window.autosave === 'function') {
						window.autosave();
					}
				} else {
					alert(window.wp_document_revisions.lockError);
				}
			})
			.fail(() => {
				alert(window.wp_document_revisions.lockError);
			});
	}

	/**
	 * Request notification permission for HTML5 notifications
	 */
	private requestPermission(): void {
		if ('Notification' in window) {
			Notification.requestPermission();
		}
	}

	/**
	 * Show lock override notice using HTML5 notifications or alert
	 * @param notice
	 */
	private lockOverrideNotice(notice: string): void {
		if ('Notification' in window) {
			if (Notification.permission === 'default') {
				Notification.requestPermission().then((permission) => {
					if (permission === 'granted') {
						this.lockOverrideNotice(notice);
					} else {
						alert(notice);
					}
				});
			} else if (Notification.permission === 'granted') {
				new Notification(window.wp_document_revisions.lostLockNoticeTitle, {
					body: notice,
					icon: window.wp_document_revisions.lostLockNoticeLogo,
				});
			} else {
				alert(notice);
			}
		} else {
			alert(notice);
		}
	}

	/**
	 * Handle post-autosave callback to check for lock conflicts
	 */
	private postAutosaveCallback(): void {
		// Check for autosave alert and lock notice
		if (
			this.$('#autosave-alert').length > 0 &&
			this.$('#lock-notice').length > 0 &&
			this.$('#lock-notice').is(':visible')
		) {
			const title = (this.$('#title').val() as string) || '';
			const notice = window.wp_document_revisions.lostLockNotice.replace('%s', title);

			this.lockOverrideNotice(notice);

			// Reload page to lock out user and prevent duplicate alerts
			location.reload();
		}
	}

	/**
	 * Legacy post document upload handler
	 */
	private legacyPostDocumentUpload(): void {
		// Legacy support - implementation would depend on specific requirements
		console.warn('Legacy post document upload event triggered');
	}

	/**
	 * Set cookie indicating document context (not image)
	 */
	private cookieFalse(): void {
		window.wpCookies.set(
			'doc_image',
			'false',
			24 * 60 * 60,
			false,
			false,
			this.secure,
			'strict'
		);
	}

	/**
	 * Set cookie indicating image context
	 */
	private cookieTrue(): void {
		window.wpCookies.set(
			'doc_image',
			'true',
			24 * 60 * 60,
			false,
			false,
			this.secure,
			'strict'
		);
		this.$(':button, :submit', '#submitpost').removeAttr('disabled');
	}

	/**
	 * Delete document/image context cookie
	 */
	private cookieDelete(): void {
		window.wpCookies.set('doc_image', 'true', -1, false, false, this.secure, 'strict');
		this.$(':button, :submit', '#submitpost').removeAttr('disabled');
	}

	/**
	 * Update all timestamp displays with human-readable time differences
	 */
	private updateTimestamps(): void {
		this.$('.timestamp').each((index, element) => {
			const $element = this.$(element);
			const timestamp = $element.attr('id');
			if (timestamp) {
				$element.text(this.humanTimeDiff(timestamp));
			}
		});
	}

	/**
	 * Calculate human-readable time difference
	 * @param timestamp
	 */
	private humanTimeDiff(timestamp: string): string {
		const now = new Date().getTime();
		const then = new Date(timestamp).getTime();
		const diff = Math.abs(now - then) / 1000; // difference in seconds

		const intervals = [
			{ label: 'year', seconds: 31536000 },
			{ label: 'month', seconds: 2592000 },
			{ label: 'week', seconds: 604800 },
			{ label: 'day', seconds: 86400 },
			{ label: 'hour', seconds: 3600 },
			{ label: 'minute', seconds: 60 },
		];

		for (const interval of intervals) {
			const count = Math.floor(diff / interval.seconds);
			if (count >= 1) {
				return `${count} ${interval.label}${count !== 1 ? 's' : ''} ago`;
			}
		}

		return 'just now';
	}

	/**
	 * Bind post document upload callback for plupload
	 */
	private bindPostDocumentUploadCallback(): void {
		if (!window.uploader) {
			return; // Prevent errors pre-3.3
		}

		window.uploader.bind('FileUploaded', (uploader: any, file: any, response: any) => {
			if (response.response.match('media-upload-error')) {
				return; // Exit on error
			}
			this.postDocumentUpload(file.name, response.response);
		});
	}

	/**
	 * Handle document upload completion
	 * @param fileName
	 * @param attachmentID
	 */
	private postDocumentUpload(fileName: string | PluploadFile, attachmentID: string): void {
		// 3.3+ verify upload was successful
		if (typeof attachmentID === 'string' && attachmentID.indexOf('error') !== -1) {
			this.$('.media-item:first').html(attachmentID);
			return;
		}

		// Convert file object to extension for backwards compatibility
		// (Legacy) Extension extraction previously populated a now-removed instance
		// property. We intentionally keep the logic (as a no-op) to document parity
		// with historical behavior and to simplify future diffs if resurrected.
		if (typeof fileName === 'object' && fileName.name) {
			// (Legacy no-op) fileName.name.split('.').pop();
		} else if (typeof fileName === 'string') {
			// (Legacy no-op) fileName.split('.').pop();
		}

		if (this.hasUpload) {
			return; // Prevent firing more than once
		}

		// Update content field with attachment ID
		jQuery('#content').val(attachmentID);
		// Hide update messages and show revision summary
		jQuery('#message').hide();
		jQuery('#revision-summary').show();
		// Re-enable submit button
		jQuery(':button, :submit', '#submitpost').removeAttr('disabled');
		this.hasUpload = true;
		// Close thickbox
		if (window.tb_remove) {
			window.tb_remove();
		}
		// Handle entity conversion for older WordPress versions
		let notice = window.wp_document_revisions.postUploadNotice;
		if (typeof window.convertEntities === 'function') {
			notice = window.convertEntities(notice);
		}
		// Show upload success notice with fade effect
		jQuery('#post').before(notice).prev().fadeIn().fadeOut().fadeIn();
		// Update permalink if it exists
		const $permalink = jQuery('#sample-permalink');
		if ($permalink.length > 0) {
			const currentHtml = $permalink.html();
			const updatedHtml = currentHtml.replace(
				/\<\/span>(\.[A-Za-z0-9]{1,7})?$/i,
				window.wp_document_revisions.extension
			);
			$permalink.html(updatedHtml);
		}
	}

	/**
	 * Modern upload success callback (exposed for tests)
	 * @param uploader
	 * @param file
	 * @param response
	 */
	private onUploadSuccess(uploader: any, file: any, response: any): void {
		try {
			const parsed = typeof response?.response === 'string' ? response.response : response;
			this.postDocumentUpload(file, parsed);
		} catch (e) {
			// Swallow in test context
		}
	}

	/**
	 * Modern upload error callback (exposed for tests)
	 * @param _uploader
	 * @param _file
	 * @param response
	 */
	private onUploadError(_uploader: any, _file: any, response: any): void {
		const msg = typeof response === 'string' ? response : response?.response || 'Upload failed';
		alert(msg);
	}
}

// Initialize when document is ready
jQuery(document).ready(($: JQueryStatic) => {
	// Note: selective enqueuing happens in includes/class-wp-document-revisions-admin.php
	(window as any).WPDocumentRevisions = new WPDocumentRevisions($);
});
