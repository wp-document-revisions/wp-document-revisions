/**
 * WP Document Revisions - Admin JavaScript
 *
 * Handles document management functionality in the WordPress admin including:
 * - Document upload handling and form submission control
 * - Revision restoration with user confirmation
 * - Lock override functionality for concurrent editing
 * - Human-readable time difference calculations
 * - Cookie management for document/image state
 * - TinyMCE content integration
 *
 * @package WP_Document_Revisions
 * @since 1.0.0
 */

(function () {
	'use strict';

	/**
	 * Time constants in seconds for human_time_diff calculations
	 */
	const SECONDS_PER_MINUTE = 60;
	const SECONDS_PER_HOUR = 60 * SECONDS_PER_MINUTE;
	const SECONDS_PER_DAY = 24 * SECONDS_PER_HOUR;

	/**
	 * Cookie expiry time in seconds (1 hour)
	 */
	const COOKIE_EXPIRY = 3600;

	/**
	 * Interval timers in milliseconds
	 */
	const TIMESTAMP_UPDATE_INTERVAL = 60000;
	const CHECK_UPDATE_INTERVAL = 1000;

	/**
	 * WPDocumentRevisions class
	 *
	 * Main class for handling document management functionality in the WordPress admin.
	 * Uses ES6 class syntax with arrow functions for proper `this` binding.
	 */
	class WPDocumentRevisions {
		/**
		 * Track whether a document has been uploaded
		 * @type {boolean}
		 */
		hasUpload = false;

		/**
		 * Whether the current connection is secure (HTTPS)
		 * @type {boolean}
		 */
		secure = window.location.protocol === 'https:';

		/**
		 * Reference to the parent window (for iframe context)
		 * @type {Window}
		 */
		window = window.dialogArguments || opener || parent || top;

		/**
		 * Original autosave_enable_buttons function reference
		 * @type {Function|undefined}
		 */
		autosaveEnableButtonsOriginal = undefined;

		/**
		 * Constructor - Initialize the WPDocumentRevisions instance
		 *
		 * @param {jQuery} $ - jQuery instance
		 */
		constructor($) {
			this.$ = $;

			// Set up event handlers
			this.$('.revision').click(this.restoreRevision);
			this.$('#override_link').click(this.overrideLock);
			this.$('#document a').click(this.requestPermission);
			this.$(document).bind('autosaveComplete', this.postAutosaveCallback);
			this.$(document).bind('documentUpload', this.legacyPostDocumentUpload);

			// Disable submit buttons initially
			this.$(':button, :submit', '#submitpost').prop('disabled', true);

			// Enable submit on user actions
			this.$('#misc-publishing-actions a').click(this.enableSubmit);
			this.$('input, select').on('change', this.enableSubmit);
			this.$('input[type=text], textarea').on('keyup', this.enableSubmit);
			this.$('#sample-permalink').on('change', this.enableSubmit);

			// Cookie management for document/image distinction
			this.$('#content-add_media').click(this.cookieFalse);
			this.$('#postimagediv .inside').click(this.cookieTrue);
			this.$('#publishing-action').click(this.buildContent);
			this.$('#submitdiv .inside').click(this.cookieDelete);
			this.$('#adminmenumain').click(this.cookieDelete);
			this.$('#wpadminbar').click(this.cookieDelete);

			// Show/hide UI elements
			this.$('#document').show();
			this.$('#revision-log').show();
			this.$('#revision-summary').hide();

			// Initialize
			this.bindPostDocumentUploadCB();
			this.hijackAutosave();
			this.checkUpdate();

			// Set up periodic updates
			setInterval(this.updateTimestamps, TIMESTAMP_UPDATE_INTERVAL);
			setInterval(this.checkUpdate, CHECK_UPDATE_INTERVAL);
		}

		/**
		 * Hijack the WordPress autosave function to integrate with document revisions
		 *
		 * Saves the original autosave_enable_buttons function and replaces it with
		 * our custom version that respects the hasUpload flag.
		 */
		hijackAutosave = () => {
			this.autosaveEnableButtonsOriginal = window.autosave_enable_buttons;
			window.autosave_enable_buttons = this.autosaveEnableButtons;
		};

		/**
		 * Custom autosave enable buttons function
		 *
		 * Triggers the autosaveComplete event and only enables buttons if
		 * a document has been uploaded.
		 */
		autosaveEnableButtons = () => {
			this.window.document.$(document).trigger('autosaveComplete');
			if (this.hasUpload) {
				return this.autosaveEnableButtonsOriginal();
			}
		};

		/**
		 * Enable submit buttons and show revision summary
		 *
		 * Called when the user makes changes that should enable form submission.
		 */
		enableSubmit = () => {
			this.$('#revision-summary').show();
			this.$(':button, :submit', '#submitpost').removeAttr('disabled');
			return this.$('#lock_override').prev().fadeIn();
		};

		/**
		 * Handle revision restoration with confirmation
		 *
		 * @param {Event} e - Click event
		 */
		restoreRevision = (e) => {
			e.preventDefault();
			if (confirm(wp_document_revisions.restoreConfirmation)) {
				return (window.location.href = this.$(e.target).attr('href'));
			}
		};

		/**
		 * Handle lock override via AJAX
		 *
		 * Sends an AJAX request to override the document lock and updates
		 * the UI accordingly.
		 */
		overrideLock = () => {
			return this.$.post(
				ajaxurl,
				{
					action: 'override_lock',
					post_id: this.$('#post_ID').val() || 0,
					nonce: wp_document_revisions.nonce,
				},
				(data) => {
					if (data) {
						this.$('#lock_override').hide();
						this.$('.error').not('#lock-notice').hide();
						this.$('#publish, .add_media, #lock-notice').fadeIn();
						return autosave();
					} else {
						return alert(wp_document_revisions.lockError);
					}
				}
			);
		};

		/**
		 * Request notification permission for lock override notices
		 *
		 * Uses the webkit notifications API if available.
		 */
		requestPermission = () => {
			if (window.webkitNotifications != null) {
				return window.webkitNotifications.requestPermission();
			}
		};

		/**
		 * Display lock override notice using HTML5 notifications
		 *
		 * @param {string} notice - The notice message to display
		 */
		lockOverrideNotice = (notice) => {
			if (window.webkitNotifications.checkPermission() > 0) {
				return window.webkitNotifications.RequestPermission(lock_override_notice);
			} else {
				return window.webkitNotifications
					.createNotification(
						wp_document_revisions.lostLockNoticeLogo,
						wp_document_revisions.lostLockNoticeTitle,
						notice
					)
					.show();
			}
		};

		/**
		 * Callback for post-autosave actions
		 *
		 * Checks for lock override situations and displays appropriate notices.
		 */
		postAutosaveCallback = () => {
			if (
				this.$('#autosave-alert').length > 0 &&
				this.$('#lock-notice').length > 0 &&
				this.$('#lock-notice').is(':visible')
			) {
				wp_document_revisions.lostLockNotice = wp_document_revisions.lostLockNotice.replace(
					'%s',
					this.window.document.$('#title').val()
				);
				if (window.webkitNotifications) {
					lock_override_notice(wp_document_revisions.lostLockNotice);
				} else {
					alert(wp_document_revisions.lostLockNotice);
				}
				return location.reload(true);
			}
		};

		/**
		 * Legacy handler for document upload events (pre-3.3 compatibility)
		 *
		 * @param {string} attachmentID - The attachment ID
		 * @param {string} extension - The file extension
		 */
		legacyPostDocumentUpload = (attachmentID, extension) => {
			return this.postDocumentUpload(attachmentID, extension);
		};

		/**
		 * Calculate human-readable time difference
		 *
		 * JavaScript implementation of WordPress's human_time_diff() function.
		 * Converts timestamp differences to human-readable strings like "5 minutes".
		 *
		 * @param {number} from - Start timestamp in seconds
		 * @param {number} [to] - End timestamp in seconds (defaults to current time)
		 * @returns {string} Human-readable time difference
		 */
		human_time_diff = (from, to) => {
			const d = new Date();
			to = to || d.getTime() / 1000 + parseInt(wp_document_revisions.offset);
			const diff = Math.abs(to - from);

			if (diff <= SECONDS_PER_HOUR) {
				let mins = Math.floor(diff / SECONDS_PER_MINUTE);
				mins = this.roundUp(mins);
				if (mins === 1) {
					return wp_document_revisions.minute.replace('%d', mins);
				} else {
					return wp_document_revisions.minutes.replace('%d', mins);
				}
			} else if (diff <= SECONDS_PER_DAY && diff > SECONDS_PER_HOUR) {
				let hours = Math.floor(diff / SECONDS_PER_HOUR);
				hours = this.roundUp(hours);
				if (hours === 1) {
					return wp_document_revisions.hour.replace('%d', hours);
				} else {
					return wp_document_revisions.hours.replace('%d', hours);
				}
			} else if (diff >= SECONDS_PER_DAY) {
				let days = Math.floor(diff / SECONDS_PER_DAY);
				days = this.roundUp(days);
				if (days === 1) {
					return wp_document_revisions.day.replace('%d', days);
				} else {
					return wp_document_revisions.days.replace('%d', days);
				}
			}
		};

		/**
		 * Round up a number to at least 1
		 *
		 * @param {number} n - Number to round up
		 * @returns {number} The number, or 1 if less than 1
		 */
		roundUp = (n) => {
			if (n < 1) {
				n = 1;
			}
			return n;
		};

		/**
		 * Bind callback for document upload completion
		 *
		 * Registers a callback with the plupload uploader if available.
		 */
		bindPostDocumentUploadCB = () => {
			if (typeof uploader === 'undefined' || uploader === null) {
				return;
			}
			return uploader.bind('FileUploaded', (up, file, response) => {
				if (response.response.match('media-upload-error')) {
					return;
				}
				return this.postDocumentUpload(file.name, response.response);
			});
		};

		/**
		 * Set cookie indicating we're outside the image area (is a document)
		 */
		cookieFalse = () => {
			wpCookies.set('doc_image', 'false', COOKIE_EXPIRY, '/wp-admin', false, this.secure);
		};

		/**
		 * Set cookie indicating we're inside the image area (use image library)
		 *
		 * Also enables submit buttons since featured image selection should allow saving.
		 */
		cookieTrue = () => {
			wpCookies.set('doc_image', 'true', COOKIE_EXPIRY, '/wp-admin', false, this.secure);
			this.$(':button, :submit', '#submitpost').removeAttr('disabled');
		};

		/**
		 * Delete the doc_image cookie
		 *
		 * Removes the cookie when navigating away from document-related areas.
		 */
		cookieDelete = () => {
			wpCookies.set('doc_image', 'true', -60, '/wp-admin', false, this.secure);
		};

		/**
		 * Update all timestamp elements with human-readable time differences
		 *
		 * Called periodically to keep displayed timestamps current.
		 */
		updateTimestamps = () => {
			return this.$('.timestamp').each((index, element) => {
				return this.$(element).text(this.human_time_diff(this.$(element).attr('id')));
			});
		};

		/**
		 * Extract description content from TinyMCE or post_content field
		 *
		 * Handles both visual editor (TinyMCE iframe) and text editor modes.
		 * Cleans up TinyMCE-specific markup.
		 *
		 * @returns {string} The description content
		 */
		getDescr = () => {
			const iframe = this.window.document.getElementById('content_ifr');
			if (null === iframe) {
				const content = this.$('#post_content').val();
				if (undefined === content || '' === content || /^\d+$/.test(content)) {
					return '';
				}
				return content;
			}
			let text = iframe.contentWindow.document.getElementById('tinymce').innerHTML;
			if (undefined === text) {
				const content = this.$('#post_content').val();
				if ('' === content || /^\d+$/.test(content)) {
					return '';
				}
				return content;
			}
			// Clean up TinyMCE markup
			text = text.replace(/<br data-mce-bogus="1">/g, '');
			text = text.replace(/<br><\/p>/g, '</p>');
			text = text.replace(/<p>\s*<\/p>/g, '');
			return text;
		};

		/**
		 * Build the combined content for post_content field
		 *
		 * Combines the document ID (as HTML comment) with the description content.
		 */
		buildContent = () => {
			const content = this.$('#post_content').val();
			let newtext = this.getDescr();
			let attach;

			if ('' === content) {
				attach = [''];
			} else if (/^\d+$/.test(content)) {
				attach = ['<!-- WPDR ' + content + ' -->'];
			} else {
				// match returns array, so ensure all return array
				attach = content.match(/<!-- WPDR \s*\d+ -->/);
			}

			// Remove any existing WPDR comment from description
			newtext = newtext.replace(/<!-- WPDR \s*\d+ -->/, '');
			newtext = attach[0] + newtext;

			if (content !== newtext) {
				this.enableSubmit();
			}

			// Set the combined content everywhere
			this.window.jQuery('#curr_content').val(newtext);
			this.window.jQuery('#post_content').val(newtext);
			this.window.jQuery('#content').val(newtext);
		};

		/**
		 * Handle document upload completion
		 *
		 * Updates the UI after a document has been successfully uploaded.
		 *
		 * @param {string|Object} file - File name or file object
		 * @param {string} attachmentID - The attachment ID or URL
		 */
		postDocumentUpload = (file, attachmentID) => {
			// Check for upload errors
			if (typeof attachmentID === 'string' && attachmentID.indexOf('error') !== -1) {
				return this.$('.media-item:first').html(attachmentID);
			}

			// Extract file extension if file is an object
			if (file instanceof Object) {
				file = file.name.split('.').pop();
			}

			// Prevent multiple upload handling
			if (this.hasUpload) {
				return;
			}

			// Extract document ID from attachment
			const docID = /\d+$/.exec(attachmentID);

			// Set the document identifier in the new format
			this.window.jQuery('#post_content').val('<!-- WPDR ' + docID + ' -->');
			this.window.jQuery('#message').hide();
			this.hasUpload = true;
			this.window.tb_remove();

			// Show upload success notification
			this.window
				.jQuery('#post')
				.before(wp_document_revisions.postUploadNotice)
				.prev()
				.fadeIn()
				.fadeOut()
				.fadeIn();

			this.enableSubmit();

			// Update permalink with file extension if present
			if (this.window.jQuery('#sample-permalink').length !== 0) {
				return this.window.jQuery('#sample-permalink').html(
					this.window
						.jQuery('#sample-permalink')
						.html()
						.replace(/\<\/span>(\.[a-z0-9]{1,7})?@$/i, wp_document_revisions.extension)
				);
			}
		};

		/**
		 * Check whether an update is needed
		 *
		 * Monitors content fields for changes and triggers content building when needed.
		 */
		checkUpdate = () => {
			const curr_content = this.$('#curr_content').val();
			if (undefined === curr_content) {
				return;
			}

			const post_content = this.$('#post_content').val();
			if (curr_content === 'Unset') {
				// Skip first update and keep save button inactive
				this.$(':button, :submit', '#submitpost').prop('disabled', true);
				this.$('#curr_content').val(post_content);
				return;
			}

			const curr_text = this.getDescr();
			if (curr_text !== curr_content || post_content !== curr_content) {
				this.buildContent();
				this.enableSubmit();
			}
		};
	}

	// Initialize when DOM is ready
	jQuery(($) => {
		window.WPDocumentRevisions = new WPDocumentRevisions($);
	});
}).call(this);
