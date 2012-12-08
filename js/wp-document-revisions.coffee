class WPDocumentRevisions

  hasUpload: false
  window: window.dialogArguments || opener || parent || top  #Because we're in an iframe, we need to traverse to parent

  constructor: ->
    jQuery('.revision').click @restoreRevision
    jQuery('#override_link').click @overrideLock
    jQuery('#document a').click @requestPermission
    jQuery(document).bind 'autosaveComplete', @postAutosaveCallback
    jQuery(document).bind 'documentUpload', @legacyPostDocumentUpload
    @bindPostDocumentUploadCB()
    
    setInterval @updateTimestamps, 60000 #automatically refresh all timestamps every minute with actual human time diff
  
    jQuery(':button, :submit', '#submitpost').prop 'disabled', true  #disable the button (from autosave.js)

    @autosaveEnableButtonsOriginal = autosave_enable_buttons #rename the function the autosave.js uses to enable the button to check our flag

    window.autosave_enable_buttons = WPDocumentRevisions.autosave_enable_buttons #because the default autosave disabled and enabled the button on every ping, we must overwrite it
    
    jQuery('#misc-publishing-actions a').click @enableSubmit #if post status is changed, enable the submit button so the change can be saved
        
    jQuery('input, select').live 'change', @enableSubmit #if any metabox is changed, allow submission

    jQuery('input[type=text], textarea').live 'keyup', @enableSubmit #if any metabox is changed, allow submission


  #hijack autosave to serve as a lock
  #ensure buttons remain disabled unless user has uploaded something
  autosaveEnableButtons: ->
    jQuery(document).trigger 'autosaveComplete' #trigger a post-autosave event to check the lock
    @autosaveEnableButtonsOriginal() if ( @hasUpload )


  #enable submit buttons    
  enableSubmit: ->
    jQuery(':button, :submit', '#submitpost').removeAttr 'disabled'
  
  
  #restore revision confirmation
  restoreRevision: (e) ->
    e.preventDefault()
    window.location.href = jQuery(this).attr 'href' if confirm wp_document_revisions.restoreConfirmation

    
  #lock override toggle
  overrideLock: ->
    jQuery.post ajaxurl, 
      action: 'override_lock'
      post_id: jQuery("#post_ID").val() || 0
    , (data) ->
      if (data)
        jQuery('#lock_override').hide();
        jQuery('.error').not('#lock-notice').hide();
        jQuery('#publish, .add_media, #lock-notice').fadeIn();
        autosave();
      else
        alert wp_document_revisions.lockError


  #HTML5 Lock Override Notifications permission check on document download
  requestPermission: ->
    window.webkitNotifications.requestPermission() if window.webkitNotifications?

      
  #HTML5 Lock Override Notifications
  lockOverrideNotice: (notice) ->
    if window.webkitNotifications.checkPermission() > 0
      window.webkitNotifications.RequestPermission lock_override_notice
    else
      window.webkitNotifications.createNotification( wp_document_revisions.lostLockNoticeLogo, wp_document_revisions.lostLockNoticeTitle, notice ).show()

      
  #Callback to handle post autosave action
  postAutosaveCallback: ->
  
    #look for autosave alert
    #it will be new if lock-notice is still present, also prevents notice from firing on initial load if document is locked
    if jQuery('#autosave-alert').length > 0  && jQuery('#lock-notice').length > 0 && jQuery('#lock-notice').is(":visible")
      wp_document_revisions.lostLockNotice = wp_document_revisions.lostLockNotice.replace '%s', jQuery('#title').val()
            
      if ( window.webkitNotifications ) 
        lock_override_notice wp_document_revisions.lostLockNotice #browser supports html5 Notifications
      else
        alert wp_document_revisions.lostLockNotice #browser does not support lock override notice, send old school alert

      location.reload true  #reload the page to lock them out and prevent duplicate alerts


  #backwards compatibility for pre, 3.3 versions
  #variables are passed as globals and jQuery event is triggered inline
  legacyPostDocumentUpload: (attachmentID, extension) ->
    @postDocumentUpload attachmentID, extension #call 3.3+ post upload callback

    
  #Javascript version of the WP human time diff PHP function, allows time stamps to by dynamically updated
  human_time_diff: ( from, to  ) ->

    d = new Date(); #allow jQueryto to be optional; adjust to server's GMT offset so timezones stay in sync
    to = to || ( d.getTime() / 1000 ) + parseInt wp_document_revisions.offset
    
    diff = Math.abs to - from #calculate difference in seconds

    if diff <= 3600 #less than one hour; therefore display minutes
    
      mins = Math.floor diff / 60 #convert seconds to minutes
      
      mins = @roundUp mins #roundup  
      
      if mins == 1 #singular
        return wp_document_revisions.minute.replace '%d', mins
      else #plural
        return wp_document_revisions.minutes.replace '%d', mins
        
    else if (diff <= 86400) && (diff > 3600) #if greater than an hour but less than a day, display as hours
    
      hours = Math.floor diff / 3600 #convert seconds to hours
      
      hours = @roundUp hours #roundup
      
      if hours == 1 #singular
        return wp_document_revisions.hour.replace '%d', hours
      else #plural
        return wp_document_revisions.hours.replace '%d', hours

    else if diff >= 86400 #if it's more than a day, display as days
    
      days = Math.floor diff / 86400 #convert seconds to days
      
      days = @roundUp days #roundup
      
      if days == 1 #singular
         return wp_document_revisions.day.replace '%d', days
      else #plural
        return wp_document_revisions.days.replace '%d', days
  
  roundUp: (n) ->
    n = 1 if n < 1
    n


  #registers our callback with plupload on media-upload.php
  bindPostDocumentUploadCB: ->
    
    if !uploader? 
      return #prevent errors pre-3.3
    
    uploader.bind 'FileUploaded', ( up, file, response ) =>
            
      if response.response.match('media-upload-error')
        return; #if error, kick
      
      @postDocumentUpload file.name, response.response 

            
  #loop through all timestamps and update
  updateTimestamps: ->
    jQuery( '.timestamp').each -> #loop through all timestamps and update the timestamp
      jQuery(this).text @human_time_diff( jQuery(this).attr('id') )


  postDocumentUpload: (file, attachmentID) -> #callback to handle post document upload event
    
    #3.3+ verify the uploaded was successful
    if typeof( attachmentID ) == 'string' && attachmentID.indexOf( 'error' ) != -1
      return jQuery('.media-item:first').html attachmentID
    
    #if this is 3.3+, we are getting the file and attachment directly from the postUpload hook
    #must convert the file object into an extension for backwards compatibility
    if file instanceof Object
      file = file.name.split('.').pop()
      
    return if @hasUpload  #prevent from firing more than once
  
    @window.jQuery('#content').val attachmentID  #stuff most recent version URL into hidden content field
    
    @window.jQuery('#message').hide() #kill any "document updated" messages to prevent confusion

    @window.jQuery('#revision-summary').show() #present the user with the revision summary box
    
    @window.jQuery(':button, :submit', '#submitpost').removeAttr 'disabled' #re-enabled the submit button
    
    @hasUpload = true #flip the upload flag to enable the update button
    
    @window.tb_remove() #close TB
    
    if typeof convertEntities == 'function' #3.2 requires convertEntities, 3.3 doesn't
       wp_document_revisions.postUploadNotice = convertEntities wp_document_revisions.postUploadNotice
    
    #notify user of success by adding the post upload notice before the #post div
    #to ensure we get the user's attention, blink once (via fade in, fade out, fade in again).
    @window.jQuery('#post').before( wp_document_revisions.postUploadNotice ).prev().fadeIn().fadeOut().fadeIn()
            
    #If they already have a permalink, update it with the current extension in case it changed
    if @window.jQuery('#sample-permalink').length != 0 
      @window.jQuery('#sample-permalink').html @window.jQuery('#sample-permalink').html().replace(/\<\/span>(\.[a-z0-9]{3,4})?jQuery/i, wp_document_revisions.extension )


jQuery(document).ready ($) ->
  
  if !adminpage? || !typenow?
    return
  
  if typenow is not 'document'
    return 
  
  if adminpage is not 'post-php' and adminpage is not 'post-new-php'
    return
    
  window.WPDocumentRevisions = new WPDocumentRevisions($)