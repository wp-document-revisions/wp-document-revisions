(function () {
	'use strict';

	const SUBMIT_BUTTONS =
		'#submitpost button, #submitpost [type="submit"], #submitpost [type="button"]';

	class WPDocumentRevisions {
		hasUpload = false;
		secure = 'https:' === window.location.protocol;
		window = window.dialogArguments || opener || parent || top;

		constructor() {
			document.querySelectorAll('.revision').forEach((el) => {
				el.addEventListener('click', this.restoreRevision);
			});
			document.getElementById('override_link')?.addEventListener('click', this.overrideLock);
			document.querySelectorAll('#document a').forEach((el) => {
				el.addEventListener('click', this.requestPermission);
			});
			document.addEventListener('autosaveComplete', this.postAutosaveCallback);
			document.addEventListener('documentUpload', this.legacyPostDocumentUpload);
			document.querySelectorAll(SUBMIT_BUTTONS).forEach((el) => {
				el.disabled = true;
			});
			document.querySelectorAll('#misc-publishing-actions a').forEach((el) => {
				el.addEventListener('click', this.enableSubmit);
			});
			document.querySelectorAll('input, select').forEach((el) => {
				el.addEventListener('change', this.enableSubmit);
			});
			document.querySelectorAll('input[type=text], textarea').forEach((el) => {
				el.addEventListener('keyup', this.enableSubmit);
			});
			document.getElementById('sample-permalink')?.addEventListener('change', this.enableSubmit);
			document.getElementById('content-add_media')?.addEventListener('click', this.cookieFalse);
			document.querySelector('#postimagediv .inside')?.addEventListener('click', this.cookieTrue);
			document.getElementById('publishing-action')?.addEventListener('click', this.buildContent);
			document.querySelector('#submitdiv .inside')?.addEventListener('click', this.cookieDelete);
			document.getElementById('adminmenumain')?.addEventListener('click', this.cookieDelete);
			document.getElementById('wpadminbar')?.addEventListener('click', this.cookieDelete);
			const docEl = document.getElementById('document');
			if (docEl) {
				docEl.style.display = '';
			}
			const revLog = document.getElementById('revision-log');
			if (revLog) {
				revLog.style.display = '';
			}
			const revSummary = document.getElementById('revision-summary');
			if (revSummary) {
				revSummary.style.display = 'none';
			}
			this.bindPostDocumentUploadCB();
			this.hijackAutosave();
			this.checkUpdate();
			setInterval(this.updateTimestamps, 60000);
			setInterval(this.checkUpdate, 1000);
		}

		hijackAutosave = () => {
			this.autosaveEnableButtonsOriginal = window.autosave_enable_buttons;
			window.autosave_enable_buttons = this.autosaveEnableButtons;
		};

		autosaveEnableButtons = () => {
			document.dispatchEvent(new Event('autosaveComplete'));
			if (this.hasUpload) {
				return this.autosaveEnableButtonsOriginal();
			}
		};

		enableSubmit = () => {
			const revSummary = document.getElementById('revision-summary');
			if (revSummary) {
				revSummary.style.display = '';
			}
			document.querySelectorAll(SUBMIT_BUTTONS).forEach((el) => {
				el.removeAttribute('disabled');
			});
			const lockOverride = document.getElementById('lock_override');
			if (lockOverride) {
				const prev = lockOverride.previousElementSibling;
				if (prev) {
					prev.style.display = '';
				}
			}
		};

		restoreRevision = (e) => {
			e.preventDefault();
			if (confirm(wp_document_revisions.restoreConfirmation)) {
				window.location.href = e.target.getAttribute('href');
			}
		};

		overrideLock = (e) => {
			if (e) {
				e.preventDefault();
			}
			const postId = document.getElementById('post_ID');

			return wp.apiFetch({
				url: ajaxurl,
				method: 'POST',
				// admin-ajax expects form-encoded body, not JSON.
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'override_lock',
					post_id: postId ? postId.value : 0,
					nonce: wp_document_revisions.nonce,
				}),
				parse: false,
			})
				.then((response) => response.text())
				.then((data) => {
					if (data.trim() === '1') {
						const lockOverride = document.getElementById('lock_override');
						if (lockOverride) {
							lockOverride.style.display = 'none';
						}
						document.querySelectorAll('.error:not(#lock-notice)').forEach((el) => {
							el.style.display = 'none';
						});
						document.querySelectorAll('#publish, .add_media, #lock-notice').forEach((el) => {
							el.style.display = '';
						});
						autosave();
					} else {
						alert(wp_document_revisions.lockError);
					}
				})
				.catch(() => {
					alert(wp_document_revisions.lockError);
				});
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
			const autosaveAlert = document.getElementById('autosave-alert');
			const lockNotice = document.getElementById('lock-notice');
			if (autosaveAlert && lockNotice && lockNotice.offsetParent !== null) {
				const title = document.getElementById('title');
				wp_document_revisions.lostLockNotice = wp_document_revisions.lostLockNotice.replace(
					'%s',
					title ? title.value : ''
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
			document.querySelectorAll(SUBMIT_BUTTONS).forEach((el) => {
				el.removeAttribute('disabled');
			});
		};

		cookieDelete = () => {
			wpCookies.set('doc_image', 'true', -60, '/wp-admin', false, this.secure);
		};

		updateTimestamps = () => {
			document.querySelectorAll('.timestamp').forEach((el) => {
				el.textContent = this.human_time_diff(el.id);
			});
		};

		getDescr() {
			// Extract data from TinyMCE window and clean up text.
			// On starting, the post_content is set to BOTH fields content and post_content.
			const iframe = this.window.document.getElementById('content_ifr');
			if (null === iframe) {
				const el = document.getElementById('post_content');
				const content = el ? el.value : '';
				if (undefined === content || '' === content || /^\d+$/.test(content)) {
					return '';
				}
				return content;
			}
			let text = iframe.contentWindow.document.getElementById('tinymce').innerHTML;
			if (undefined === text) {
				const el = document.getElementById('post_content');
				const content = el ? el.value : '';
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
			const postContentEl = document.getElementById('post_content');
			const content = postContentEl ? postContentEl.value : '';
			let newtext = this.getDescr();
			let attach;
			if ('' === content) {
				attach = [''];
			} else if (/^\d+$/.test(content)) {
				attach = [`<!-- WPDR ${content} -->`];
			} else {
				// match returns array or null, so ensure all return array.
				attach = content.match(/<!-- WPDR \s*\d+ -->/) || [''];
			}
			// might have an extra space includes in the id provided.
			newtext = newtext.replace(/<!-- WPDR \s*\d+ -->/, '');
			newtext = attach[0] + newtext;
			if (content !== newtext) {
				this.enableSubmit();
			}
			// Set the desired text in the parent window fields.
			const wDoc = this.window.document;
			const currContent = wDoc.getElementById('curr_content');
			if (currContent) {
				currContent.value = newtext;
			}
			const postContent = wDoc.getElementById('post_content');
			if (postContent) {
				postContent.value = newtext;
			}
			const contentEl = wDoc.getElementById('content');
			if (contentEl) {
				contentEl.value = newtext;
			}
		};

		postDocumentUpload(file, attachmentID) {
			if (typeof attachmentID === 'string' && attachmentID.indexOf('error') !== -1) {
				const mediaItem = document.querySelector('.media-item');
				if (mediaItem) {
					mediaItem.innerHTML = attachmentID;
				}
				return;
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
			const wDoc = this.window.document;
			const postContent = wDoc.getElementById('post_content');
			if (postContent) {
				postContent.value = `<!-- WPDR ${docID} -->`;
			}
			const message = wDoc.getElementById('message');
			if (message) {
				message.style.display = 'none';
			}
			this.hasUpload = true;
			this.window.tb_remove();
			const post = wDoc.getElementById('post');
			if (post) {
				post.insertAdjacentHTML('beforebegin', wp_document_revisions.postUploadNotice);
			}
			this.enableSubmit();
			const samplePermalink = wDoc.getElementById('sample-permalink');
			if (samplePermalink) {
				samplePermalink.innerHTML = samplePermalink.innerHTML.replace(
					/\<\/span>(\.[a-z0-9]{1,7})?@$/i,
					wp_document_revisions.extension
				);
			}
		}

		checkUpdate = () => {
			// Check whether an update is needed - via a 3rd field as amalgam of two input fields.
			const currContentEl = document.getElementById('curr_content');
			if (!currContentEl) {
				return;
			}
			const curr_content = currContentEl.value;
			if (undefined === curr_content) {
				return;
			}
			const postContentEl = document.getElementById('post_content');
			const post_content = postContentEl ? postContentEl.value : '';
			if (curr_content === 'Unset') {
				// Clunky process to miss the first update (and keep the save button inactive).
				document.querySelectorAll(SUBMIT_BUTTONS).forEach((el) => {
					el.disabled = true;
				});
				currContentEl.value = post_content;
				return;
			}
			const curr_text = this.getDescr();
			if (curr_text !== curr_content || post_content !== curr_content) {
				this.buildContent();
				this.enableSubmit();
			}
		};
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			window.WPDocumentRevisions = new WPDocumentRevisions();
		});
	} else {
		window.WPDocumentRevisions = new WPDocumentRevisions();
	}
}).call(this);
