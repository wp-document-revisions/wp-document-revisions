/**
 * WP Document Revisions - Main Admin JavaScript
 * Converted from CoffeeScript to Modern ES6+ JavaScript
 */
(function () {
	'use strict';

	/**
	 * Main class for WP Document Revisions admin functionality
	 */
	class WPDocumentRevisions {
		/**
		 * Constructor
		 * @param {jQuery} $ - jQuery instance
		 */
		constructor($) {
			// Instance properties
			this.$ = $;
			this.hasUpload = false;
			this.secure = window.location.protocol === 'https:';
			// Because we're in an iframe, we need to traverse to parent
			this.window = window.dialogArguments || window.opener || window.parent || window.top;

			// Bind event handlers
			this.$('.revision').click((e) => this.restoreRevision(e));
			this.$('#override_link').click(() => this.overrideLock());
			this.$('#document a').click(() => this.requestPermission());
			this.$(document).bind('autosaveComplete', () => this.postAutosaveCallback());
			this.$(document).bind('documentUpload', (attachmentID, extension) =>
				this.legacyPostDocumentUpload(attachmentID, extension)
			);
			this.$(':button, :submit', '#submitpost').prop('disabled', true);
			this.$('#misc-publishing-actions a').click(() => this.enableSubmit());
			this.$('input, select').on('change', () => this.enableSubmit());
			this.$('input[type=text], textarea').on('keyup', () => this.enableSubmit());
			this.$('#sample-permalink').on('change', () => this.enableSubmit());
			this.$('#content-add_media').click(() => this.cookieFalse());
			this.$('#postimagediv .inside').click(() => this.cookieTrue());
			this.$('#publishing-action').click(() => this.buildContent());
			this.$('#submitdiv .inside').click(() => this.cookieDelete());
			this.$('#adminmenumain').click(() => this.cookieDelete());
			this.$('#wpadminbar').click(() => this.cookieDelete());

			// Show/hide elements
			this.$('#document').show();
			this.$('#revision-log').show();
			this.$('#revision-summary').hide();

			// Initialize
			this.bindPostDocumentUploadCB();
			this.hijackAutosave();
			this.checkUpdate();

			// Set up intervals
			setInterval(() => this.updateTimestamps(), 60000);
			setInterval(() => this.checkUpdate(), 1000);
		}

		/**
		 * Monkey patch global autosave to our autosave
		 * Hijack autosave to serve as a lock
		 */
		hijackAutosave() {
			this.autosaveEnableButtonsOriginal = window.autosave_enable_buttons;
			window.autosave_enable_buttons = () => this.autosaveEnableButtons();
		}

		/**
		 * Ensure buttons remain disabled unless user has uploaded something
		 */
		autosaveEnableButtons() {
			this.window.document.$(document).trigger('autosaveComplete');
			if (this.hasUpload) {
				this.autosaveEnableButtonsOriginal();
			}
		}

		/**
		 * Enable submit buttons
		 */
		enableSubmit() {
			this.$('#revision-summary').show();
			this.$(':button, :submit', '#submitpost').removeAttr('disabled');
			this.$('#lock_override').prev().fadeIn();
		}

		/**
		 * Restore revision confirmation
		 * @param {Event} e - Click event
		 */
		restoreRevision(e) {
			e.preventDefault();
			if (confirm(wp_document_revisions.restoreConfirmation)) {
				window.location.href = this.$(e.target).attr('href');
			}
		}

		/**
		 * Lock override toggle
		 */
		overrideLock() {
			this.$.post(
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
						autosave();
					} else {
						alert(wp_document_revisions.lockError);
					}
				}
			);
		}

		/**
		 * HTML5 Lock Override Notifications permission check on document download
		 */
		requestPermission() {
			if (window.webkitNotifications) {
				window.webkitNotifications.requestPermission();
			}
		}

		/**
		 * HTML5 Lock Override Notifications
		 * @param {string} notice - Notice message
		 */
		lockOverrideNotice(notice) {
			if (window.webkitNotifications.checkPermission() > 0) {
				window.webkitNotifications.RequestPermission(() => this.lockOverrideNotice(notice));
			} else {
				window.webkitNotifications
					.createNotification(
						wp_document_revisions.lostLockNoticeLogo,
						wp_document_revisions.lostLockNoticeTitle,
						notice
					)
					.show();
			}
		}

		/**
		 * Callback to handle post autosave action
		 */
		postAutosaveCallback() {
			// Look for autosave alert
			// It will be new if lock-notice is still present, also prevents notice from firing on initial load if document is locked
			if (
				this.$('#autosave-alert').length > 0 &&
				this.$('#lock-notice').length > 0 &&
				this.$('#lock-notice').is(':visible')
			) {
				const lostLockNotice = wp_document_revisions.lostLockNotice.replace(
					'%s',
					this.window.document.$('#title').val()
				);

				if (window.webkitNotifications) {
					this.lockOverrideNotice(lostLockNotice);
				} else {
					alert(lostLockNotice);
				}

				location.reload(true);
			}
		}

		/**
		 * Backwards compatibility for pre-3.3 versions
		 * Variables are passed as globals and event is triggered inline
		 * @param {string|number} attachmentID - Attachment ID
		 * @param {string} extension - File extension
		 */
		legacyPostDocumentUpload(attachmentID, extension) {
			this.postDocumentUpload(attachmentID, extension);
		}

		/**
		 * JavaScript version of the WP human time diff PHP function
		 * Allows timestamps to be dynamically updated
		 * @param {number} from - From timestamp in seconds
		 * @param {number} to - To timestamp in seconds (optional, defaults to current time)
		 * @returns {string} Human readable time difference
		 */
		human_time_diff(from, to) {
			const d = new Date();
			// Allow to be optional; adjust to server's GMT offset so timezones stay in sync
			to = to || d.getTime() / 1000 + parseInt(wp_document_revisions.offset, 10);

			// Calculate difference in seconds
			const diff = Math.abs(to - from);

			if (diff <= 3600) {
				// Less than one hour; display minutes
				let mins = Math.floor(diff / 60);
				mins = this.roundUp(mins);

				if (mins === 1) {
					return wp_document_revisions.minute.replace('%d', mins);
				} else {
					return wp_document_revisions.minutes.replace('%d', mins);
				}
			} else if (diff <= 86400 && diff > 3600) {
				// Greater than an hour but less than a day, display as hours
				let hours = Math.floor(diff / 3600);
				hours = this.roundUp(hours);

				if (hours === 1) {
					return wp_document_revisions.hour.replace('%d', hours);
				} else {
					return wp_document_revisions.hours.replace('%d', hours);
				}
			} else if (diff >= 86400) {
				// More than a day, display as days
				let days = Math.floor(diff / 86400);
				days = this.roundUp(days);

				if (days === 1) {
					return wp_document_revisions.day.replace('%d', days);
				} else {
					return wp_document_revisions.days.replace('%d', days);
				}
			}
		}

		/**
		 * Round up to at least 1
		 * @param {number} n - Number to round up
		 * @returns {number} Rounded number
		 */
		roundUp(n) {
			return n < 1 ? 1 : n;
		}

		/**
		 * Registers our callback with plupload on media-upload.php
		 */
		bindPostDocumentUploadCB() {
			if (typeof uploader === 'undefined' || uploader === null) {
				return; // Prevent errors pre-3.3
			}

			uploader.bind('FileUploaded', (up, file, response) => {
				if (response.response.match('media-upload-error')) {
					return; // If error, kick
				}

				this.postDocumentUpload(file.name, response.response);
			});
		}

		/**
		 * Sets cookie as being outside image area, so is a document
		 */
		cookieFalse() {
			wpCookies.set('doc_image', 'false', 60 * 60, '/wp-admin', false, this.secure);
		}

		/**
		 * Sets cookie as being inside image area, so use the image library
		 */
		cookieTrue() {
			wpCookies.set('doc_image', 'true', 60 * 60, '/wp-admin', false, this.secure);
			this.$(':button, :submit', '#submitpost').removeAttr('disabled');
			// Propagation will be stopped in postimagediv to stop document event setting cookie false
		}

		/**
		 * Delete cookie
		 */
		cookieDelete() {
			wpCookies.set('doc_image', 'true', -60, '/wp-admin', false, this.secure);
		}

		/**
		 * Loop through all timestamps and update
		 */
		updateTimestamps() {
			this.$('.timestamp').each((index, element) => {
				this.$(element).text(this.human_time_diff(this.$(element).attr('id')));
			});
		}

		/**
		 * Extract description data from TinyMCE window and clean up text
		 * On starting, the post_content is set to BOTH fields content and post_content
		 * @returns {string} Description content
		 */
		getDescr() {
			const iframe = this.window.document.getElementById('content_ifr');

			if (iframe === null) {
				const content = this.$('#post_content').val();
				if (typeof content === 'undefined' || content === '' || /^\d+$/.test(content)) {
					return '';
				}
				return content;
			}

			let text = iframe.contentWindow.document.getElementById('tinymce').innerHTML;
			if (typeof text === 'undefined') {
				const content = this.$('#post_content').val();
				if (content === '' || /^\d+$/.test(content)) {
					return '';
				}
				return content;
			}

			text = text.replace(/<br data-mce-bogus="1">/g, '');
			text = text.replace(/<br><\/p>/g, '</p>');
			text = text.replace(/<p>\s*<\/p>/g, '');
			return text;
		}

		/**
		 * Create the desired content for post_content
		 * Will be the combination of document id from field post_content and description from content
		 */
		buildContent() {
			const content = this.$('#post_content').val();
			let newtext = this.getDescr();
			let attach;

			if (content === '') {
				attach = [''];
			} else if (/^\d+$/.test(content)) {
				attach = [`<!-- WPDR ${content} -->`];
			} else {
				// Match returns array, so ensure all return array
				attach = content.match(/<!-- WPDR \s*\d+ -->/);
			}

			// Might have an extra space included in the id provided
			newtext = newtext.replace(/<!-- WPDR \s*\d+ -->/, '');
			newtext = attach[0] + newtext;

			if (content !== newtext) {
				this.enableSubmit();
			}

			// Set the desired text everywhere
			this.window.jQuery('#curr_content').val(newtext);
			this.window.jQuery('#post_content').val(newtext);
			this.window.jQuery('#content').val(newtext);
		}

		/**
		 * Callback to handle post document upload event
		 * @param {Object|string} file - File object or extension string
		 * @param {string} attachmentID - Attachment ID
		 */
		postDocumentUpload(file, attachmentID) {
			// 3.3+ verify the upload was successful
			if (typeof attachmentID === 'string' && attachmentID.indexOf('error') !== -1) {
				this.$('.media-item:first').html(attachmentID);
				return;
			}

			// If this is 3.3+, we are getting the file and attachment directly from the postUpload hook
			// Must convert the file object into an extension for backwards compatibility
			if (file instanceof Object) {
				file = file.name.split('.').pop();
			}

			if (this.hasUpload) {
				return; // Prevent from firing more than once
			}

			// On upload set the document identifier in the new format
			const docID = /\d+$/.exec(attachmentID);
			// This will throw away the description for an existing post - but it is in content
			this.window.jQuery('#post_content').val(`<!-- WPDR ${docID} -->`);

			this.window.jQuery('#message').hide();

			this.hasUpload = true;

			this.window.tb_remove();

			// Notify user of success by adding the post upload notice before the #post div
			// To ensure we get the user's attention, blink once (via fade in, fade out, fade in again)
			this.window
				.jQuery('#post')
				.before(wp_document_revisions.postUploadNotice)
				.prev()
				.fadeIn()
				.fadeOut()
				.fadeIn();

			this.enableSubmit();

			// If they already have a permalink, update it with the current extension in case it changed
			if (this.window.jQuery('#sample-permalink').length !== 0) {
				this.window.jQuery('#sample-permalink').html(
					this.window
						.jQuery('#sample-permalink')
						.html()
						.replace(/\<\/span>(\.[a-z0-9]{1,7})?@$/i, wp_document_revisions.extension)
				);
			}
		}

		/**
		 * Check whether an update is needed - via a 3rd field as amalgam of two input fields
		 */
		checkUpdate() {
			const curr_content = this.$('#curr_content').val();
			if (typeof curr_content === 'undefined') {
				return;
			}

			const post_content = this.$('#post_content').val();

			if (curr_content === 'Unset') {
				// Clunky process to miss the first update (and keep the save button inactive)
				this.$(':button, :submit', '#submitpost').prop('disabled', true);
				this.$('#curr_content').val(post_content);
				return;
			}

			const curr_text = this.getDescr();
			if (curr_text !== curr_content || post_content !== curr_content) {
				this.buildContent();
				this.enableSubmit();
			}
		}
	}

	// Initialize when document is ready
	jQuery(($) => {
		window.WPDocumentRevisions = new WPDocumentRevisions($);
	});
})();
