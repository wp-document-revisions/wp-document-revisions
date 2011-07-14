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
	    RequestPermission( lock_override_notice );
	  } else {
		window.webkitNotifications.createNotification(
        'icon.png', 'Lost Document Lock', notice ).show();
	  }
	}
				
	//if we are on the document edit page, begin autosaving to force autosave ping to lock file
	if ( adminpage && adminpage == 'post-php' && typenow && typenow == 'document' ) {
		//init autosave and watch for lock changes
		setTimeout( function(){
			var blockSave;
			
			if ( blockSave )
				return;
			
		   autosave();
			
			//if lock has been overridden
			if ($('#autosave-alert p').text().indexOf( wp_document_revisions.lockNeedle ) != -1) {
				
				if ( window.webkitNotifications ) {
					//browser supports html5 Notifications
					lock_override_notice( wp_document_revisions.lostLockNotice );
				} else {
					//browser does not support lock override notice, send old school alert
					alert( convertEntities( wp_document_revisions.lostLockNotice ) );
				}
				
				//reload the page to lock them out
				location.reload(true);
			}
		
		}, 200);
	}
	

	$(document).bind('documentUpload', function() {
		
		//Because we're in an iFrame, we need to traverse to parrent
		var win = window.dialogArguments || opener || parent || top;

		//stuff most recent version URL into hidden content field
		win.jQuery('#content').val( attachmentID );
		
		//kill any "document updated" messages to prevent confusion
		win.jQuery('#message').hide();
		
		//close TB
		win.tb_remove();
		
		//notify user of success by adding the post upload notice before the #post div
		//to ensure we get the user's attention, blink once (via fade in, fade out, fade in again).
		win.jQuery('#post').before( convertEntities( wp_document_revisions.postUploadNotice ) ).prev().fadeIn().fadeOut().fadeIn();
		    		
		//If they already have a permalink, update it with the current extension in case it changed
		//otherwise, tell WP that we're ready for it to generate a permalink for the first time
		if ( win.jQuery('#sample-permalink').length == 0 ) {
		    win.autosave_update_slug( post_id );
		} else {
		    win.jQuery('#sample-permalink').html( win.jQuery('#sample-permalink').html().replace(/\<\/span>(\.[a-z0-9]{3,4})?$/i, wp_document_revisions.extension ) );
		}

	});
 	
});
	
