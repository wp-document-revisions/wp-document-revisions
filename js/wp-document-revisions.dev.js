(function () {
	'use strict';

	class WPDocumentRevisions {
		hasUpload = false;
		secure = 'https:' === window.location.protocol;
		window = window.dialogArguments || opener || parent || top;

		constructor($) {
			this.$ = $;
			this.$('.revision').click(this.restoreRevision);
			this.$('#override_link').click(this.overrideLock);
			this.$('#document a').click(this.requestPermission);
			this.$(document).bind('autosaveComplete', this.postAutosaveCallback);
			this.$(document).bind('documentUpload', this.legacyPostDocumentUpload);
			this.$(':button, :submit', '#submitpost').prop('disabled', true);
			this.$('#misc-publishing-actions a').click(this.enableSubmit);
			this.$('input, select').on('change', this.enableSubmit);
			this.$('input[type=text], textarea').on('keyup', this.enableSubmit);
			this.$('#sample-permalink').on('change', this.enableSubmit);
			this.$('#content-add_media').click(this.cookieFalse);
			this.$('#postimagediv .inside').click(this.cookieTrue);
			this.$('#publishing-action').click(this.buildContent);
			this.$('#submitdiv .inside').click(this.cookieDelete);
			this.$('#adminmenumain').click(this.cookieDelete);
			this.$('#wpadminbar').click(this.cookieDelete);
			this.$('#document').show();
			this.$('#revision-log').show();
			this.$('#revision-summary').hide();
			this.bindPostDocumentUploadCB();
			this.hijackAutosave();
			this.checkUpdate();
			setInterval(this.updateTimestamps, 60000);
			setInterval(this.checkUpdate, 1000);
		}

		hijackAutosave = () => {
			this.autosaveEnableButtonsOriginal = window.autosave_enable_buttons;
			return (window.autosave_enable_buttons = this.autosaveEnableButtons);
		};

		autosaveEnableButtons = () => {
			this.window.document.$(document).trigger('autosaveComplete');
			if (this.hasUpload) {
				return this.autosaveEnableButtonsOriginal();
			}
		};

		enableSubmit = () => {
			this.$('#revision-summary').show();
			this.$(':button, :submit', '#submitpost').removeAttr('disabled');
			return this.$('#lock_override').prev().fadeIn();
		};

		restoreRevision = (e) => {
			e.preventDefault();
			if (confirm(wp_document_revisions.restoreConfirmation)) {
				return (window.location.href = this.$(e.target).attr('href'));
			}
		};

		overrideLock = () => {
			return this.$.post(
				ajaxurl,
				{
					action: 'override_lock',
					post_id: this.$('#post_ID').val() || 0,
					nonce: wp_document_revisions.nonce,
				},
				function (data) {
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

		requestPermission() {
			if (window.webkitNotifications != null) {
				return window.webkitNotifications.requestPermission();
			}
		}

		lockOverrideNotice(notice) {
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
		}

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

		legacyPostDocumentUpload(attachmentID, extension) {
			return this.postDocumentUpload(attachmentID, extension);
		}

		human_time_diff(from, to) {
			const d = new Date();
			to = to || d.getTime() / 1000 + parseInt(wp_document_revisions.offset);
			const diff = Math.abs(to - from);
			if (diff <= 3600) {
				const mins = this.roundUp(Math.floor(diff / 60));
				if (mins === 1) {
					return wp_document_revisions.minute.replace('%d', mins);
				} else {
					return wp_document_revisions.minutes.replace('%d', mins);
				}
			} else if (diff <= 86400 && diff > 3600) {
				const hours = this.roundUp(Math.floor(diff / 3600));
				if (hours === 1) {
					return wp_document_revisions.hour.replace('%d', hours);
				} else {
					return wp_document_revisions.hours.replace('%d', hours);
				}
			} else if (diff >= 86400) {
				const days = this.roundUp(Math.floor(diff / 86400));
				if (days === 1) {
					return wp_document_revisions.day.replace('%d', days);
				} else {
					return wp_document_revisions.days.replace('%d', days);
				}
			}
		}

		roundUp(n) {
			if (n < 1) {
				n = 1;
			}
			return n;
		}

		bindPostDocumentUploadCB() {
			if (typeof uploader === 'undefined' || uploader === null) {
				return;
			}
			return uploader.bind('FileUploaded', (up, file, response) => {
				if (response.response.match('media-upload-error')) {
					return;
				}
				return this.postDocumentUpload(file.name, response.response);
			});
		}

		cookieFalse = () => {
			wpCookies.set('doc_image', 'false', 60 * 60, '/wp-admin', false, this.secure);
		};

		cookieTrue = () => {
			wpCookies.set('doc_image', 'true', 60 * 60, '/wp-admin', false, this.secure);
			this.$(':button, :submit', '#submitpost').removeAttr('disabled');
			// Propagation will be stopped in postimagediv to stop document event setting cookie false.
		};

		cookieDelete = () => {
			wpCookies.set('doc_image', 'true', -60, '/wp-admin', false, this.secure);
		};

		updateTimestamps = () => {
			return this.$('.timestamp').each(() => {
				return this.$(this).text(this.human_time_diff(this.$(this).attr('id')));
			});
		};

		getDescr() {
			// Extract data from TinyMCE window and clean up text.
			// On starting, the post_content is set to BOTH fields content and post_content.
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
			text = text.replace(/<br data-mce-bogus="1">/g, '');
			text = text.replace(/<br><\/p>/g, '</p>');
			text = text.replace(/<p>\s*<\/p>/g, '');
			return text;
		}

		buildContent = () => {
			// Create the desired content for post_content.
			// Will be the combination of document id from field post_content and description from content.
			const content = this.$('#post_content').val();
			let newtext = this.getDescr();
			let attach;
			if ('' === content) {
				attach = [''];
			} else if (/^\d+$/.test(content)) {
				attach = [`<!-- WPDR ${content} -->`];
			} else {
				// match returns array, so ensure all return array.
				attach = content.match(/<!-- WPDR \s*\d+ -->/);
			}
			// might have an extra space includes in the id provided.
			newtext = newtext.replace(/<!-- WPDR \s*\d+ -->/, '');
			newtext = attach[0] + newtext;
			if (content !== newtext) {
				this.enableSubmit();
			}
			// set the desired text eeverywhere.
			this.window.jQuery('#curr_content').val(newtext);
			this.window.jQuery('#post_content').val(newtext);
			this.window.jQuery('#content').val(newtext);
		};

		postDocumentUpload(file, attachmentID) {
			if (typeof attachmentID === 'string' && attachmentID.indexOf('error') !== -1) {
				return this.$('.media-item:first').html(attachmentID);
			}
			if (file instanceof Object) {
				file = file.name.split('.').pop();
			}
			if (this.hasUpload) {
				return;
			}
			// On upload set the document identifier in the new format.
			const docID = /\d+$/.exec(attachmentID);
			// This will throw away the description for an existing post - but it is in content.
			this.window.jQuery('#post_content').val(`<!-- WPDR ${docID} -->`);
			this.window.jQuery('#message').hide();
			this.hasUpload = true;
			this.window.tb_remove();
			this.window
				.jQuery('#post')
				.before(wp_document_revisions.postUploadNotice)
				.prev()
				.fadeIn()
				.fadeOut()
				.fadeIn();
			this.enableSubmit();
			if (this.window.jQuery('#sample-permalink').length !== 0) {
				return this.window.jQuery('#sample-permalink').html(
					this.window
						.jQuery('#sample-permalink')
						.html()
						.replace(/\<\/span>(\.[a-z0-9]{1,7})?@$/i, wp_document_revisions.extension)
				);
			}
		}

		checkUpdate = () => {
			// Check whether an update is needed - via a 3rd field as amalgam of two input fields.
			const curr_content = this.$('#curr_content').val();
			if (undefined === curr_content) {
				return;
			}
			const post_content = this.$('#post_content').val();
			if (curr_content === 'Unset') {
				// Clunky process to miss the first update (and keep the save button inactive).
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

	jQuery(function ($) {
		return (window.WPDocumentRevisions = new WPDocumentRevisions($));
	});
}).call(this);
