jQuery(document).ready( function($) {

	//Revision restore confirmation
	$('.revision').click(function(event){
		event.preventDefault();
		if (confirm( wp_document_revisions.restoreConfirmation ) )
		window.location.href = jQuery(this).attr('href');
	});

	//lock override toggle	
	$('#override_link').click( function() {

	 jQuery.post( ajaxurl, {
			action: 'override_lock',
			post_id: jQuery("#post_ID").val() || 0
		},function(data) {
				if ( data ) {
					$('#lock_override').hide();
					$('.error').not('#lock-notice').hide();
					$('#publish, #add_media, #lock-notice').fadeIn();
					autosave();
				} else {
					alert( wp_document_revisions.lockError );
				}
			}
		);   

	});
	
	//HTML5 Lock Override Notifications permission check on document download
	$('#document a').click( function() {
		if ( window.webkitNotifications )
			window.webkitNotifications.requestPermission( );
	});

	//HTML5 Lock Override Notifications
	function lock_override_notice( notice ) {
		if ( window.webkitNotifications.checkPermission() > 0 ) {
			window.webkitNotifications.RequestPermission( lock_override_notice );
		} else {
			window.webkitNotifications.createNotification(
			'icon.png', wp_document_revisions.lostLockNoticeTitle, notice ).show();
		}
	}
	
	//disbale the update button until a doc has been uploaded
	if ( adminpage && ( adminpage == 'post-php' || adminpage == 'post-new-php' ) && typenow && typenow == 'document' ) {

		//set a flag to let us know if there's been an upload yet
		hasUpload = false;
		
		//disable the button (from autosave.js)
		jQuery(':button, :submit', '#submitpost').prop('disabled', true);

		//rename the function the autosave.js uses to enable the button to check our flag
		wp_document_revisions_autosave_enable_buttons = autosave_enable_buttons;		
		
		//because the default autosave disabled and enabled the button on every ping, we must overwrite it
		autosave_enable_buttons = function() {
			
			//trigger a post-autosave event to check the lock
			$(document).trigger('autosaveComplete');
				
			if ( hasUpload )
				wp_document_revisions_autosave_enable_buttons();
		}
		
	}	
	
	$(document).bind('autosaveComplete', function() {
	
		//look for autosave alert
		//it will be new if lock-notice is still present, also prevents notice from firing on initial load if document is locked
		if ( $('#autosave-alert').length > 0  && $('#lock-notice').length > 0 && $('#lock-notice').is(":visible") ) {
		    
			wp_document_revisions.lostLockNotice = wp_document_revisions.lostLockNotice.replace('%s', $('#title').val() );
						
			if ( window.webkitNotifications ) {
				//browser supports html5 Notifications
				lock_override_notice( wp_document_revisions.lostLockNotice );
			} else {
				//browser does not support lock override notice, send old school alert
				alert( wp_document_revisions.lostLockNotice );
			}
		    
		    //reload the page to lock them out and prevent duplicate alerts
			location.reload(true);
		}
	
	});
	
	//if post status is changed, enable the submit button so the change can be saved
	$('#misc-publishing-actions a').click( function(){

	//re-enabled the submit button
		$(':button, :submit', '#submitpost').removeAttr('disabled');
		
	});
	
	//if any metabox is changed, allow submission
	$('input, select, textarea').live('change', function() {

		//re-enabled the submit button
		$(':button, :submit', '#submitpost').removeAttr('disabled');
		
	});

	$(document).bind('documentUpload', function() {
		
		//Because we're in an iFrame, we need to traverse to parrent
		var win = window.dialogArguments || opener || parent || top;
		
		//prevent from firing more than once
		if ( win.hasUpload )
			return;

		//stuff most recent version URL into hidden content field
		win.jQuery('#content').val( attachmentID );
		
		//kill any "document updated" messages to prevent confusion
		win.jQuery('#message').hide();
		
		//present the user with the revision summary box
		win.jQuery('#revision-summary').show();
		
		//re-enabled the submit button
		win.jQuery(':button, :submit', '#submitpost').removeAttr('disabled');
		
		//flip the upload flag to enable the update button
		win.hasUpload = true;
		
		//close TB
		win.tb_remove();
		
		//notify user of success by adding the post upload notice before the #post div
		//to ensure we get the user's attention, blink once (via fade in, fade out, fade in again).
		win.jQuery('#post').before( wp_document_revisions.postUploadNotice ).prev().fadeIn().fadeOut().fadeIn();
		    		
		//If they already have a permalink, update it with the current extension in case it changed
		if ( win.jQuery('#sample-permalink').length != 0 ) {
		    win.jQuery('#sample-permalink').html( win.jQuery('#sample-permalink').html().replace(/\<\/span>(\.[a-z0-9]{3,4})?$/i, wp_document_revisions.extension ) );
		}

	});
	
	setTimeout( 'updateTimestamps', 3000 );
 	
});

function human_time_diff( from, to  ) {

		//allow $to to be optional; adjust to server's GMT offset so timezones stay in sync
		d = new Date();
		to = to || ( d.getTime() / 1000 ) + parseInt( wp_document_revisions.offset );
		
		//caclulate difference in seconds
		diff = Math.abs(to - from);
		
		//less than one hour; therefore display minutes
		if (diff <= 3600) {
		
			//convert seconds to minutes
			mins = Math.floor(diff / 60);
			
			//roundup 
			if (mins <= 1) {
				mins = 1;
			}
			
			if ( mins == 1) //singular
				return wp_document_revisions.minute.replace( '%d', mins);
			else //plural
				return wp_document_revisions.minutes.replace( '%d', mins);
				
		//if greater than an hour but less than a day, display as hours
		} else if ((diff <= 86400) && (diff > 3600)) {
		
			//convert seconds to hours
			hours = Math.floor(diff / 3600);
	
			//roundup
			if (hours <= 1) {
				hours = 1;
			}
			
			if ( hours == 1) //singular
				return wp_document_revisions.hour.replace( '%d', hours);
			else //plural
				return wp_document_revisions.hours.replace( '%d', hours);
		
		//if it's more than a day, display as days
		} else if (diff >= 86400) {
		
			//convert seconds to days
			days = Math.floor(diff / 86400);
			
			//roundup
			if (days <= 1) {
				days = 1;
			}
			
			if ( days == 1) //singular
 				return wp_document_revisions.day.replace( '%d', days);
			else //plural
				return wp_document_revisions.days.replace( '%d', days);
			}
	}
	
	function updateTimestamps() {
	
		//loop through all timestamps and update the timestamp
		jQuery('.timestamp').each( function(){
			jQuery(this).text( human_time_diff( jQuery(this).attr('id') ) ); 
		});
		
	}
	
