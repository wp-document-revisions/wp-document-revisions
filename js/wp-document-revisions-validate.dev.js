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
			clear_line( id, code );
		},
		error: function( response ) {
			alert( response.failureMessage );
		}
	});
}

function clear_line( id, code ) {
	var line = document.getElementById( 'Line' + id );
	// remove the class so that hide_show doesn't touch it.
	line.classList.remove( "wpdr_" + code );
	var td = line.getElementsByTagName('td');
	td[3].innerHTML = processed;
	td[4].innerHTML = '';
	// may not match.
	document.getElementById('on_' + id).style.display = "none";
	document.getElementById('off' + id).style.display = "block";
}

function hide_show( id ) {
	var inp  = document.getElementById( id );
	var line = document.getElementsByClassName( id );
	for ( var i = 0; i < line.length; i++ ) {
		if ( inp.checked ) {
			line[i].style.display = "table-row";
		} else {
			line[i].style.display = "none";
		}
	}
}
