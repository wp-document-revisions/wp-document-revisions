function wpdr_valid_fix( id, code, parm ) {
	var URL = wpApiSettings.root + 'wpdr/v1/correct/' + id + '/type/' + code + '/attach/' + parm;
	jQuery.ajax({
		type:"PUT",
		url: URL,
		beforeSend: function ( xhr ) {
			xhr.setRequestHeader( 'X-WP-Nonce', nonce );
		},
		data:{
			userid: user
		},
		success: function( response ) {
			clear_line( id );
		},
		error: function( response ) {
			alert( response.failureMessage );
		}
	});
}

function clear_line( id ) {
	var line = document.getElementById('Line' + id );
	var td = line.getElementsByTagName('td');
	td[3].innerHTML = processed;
	td[4].innerHTML = '';
	// may not match.
	document.getElementById('on_' + id).style.display = "none";
	document.getElementById('off' + id).style.display = "block";
}
