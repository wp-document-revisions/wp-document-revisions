(function () {
	'use strict';

	window.wpdr_valid_fix = function wpdr_valid_fix(id, code, parm) {
		const url = `${wpApiSettings.root}wpdr/v1/correct/${id}/type/${code}/attach/${parm}`;
		jQuery.ajax({
			type: 'PUT',
			url: url,
			beforeSend: (xhr) => {
				xhr.setRequestHeader('X-WP-Nonce', nonce);
			},
			data: {
				userid: user,
			},
			success: (response) => {
				window.clear_line(id, code);
			},
			error: (response) => {
				alert(response.failureMessage);
			},
		});
	};

	window.clear_line = function clear_line(id, code) {
		const line = document.getElementById(`Line${id}`);
		// remove the class so that hide_show doesn't touch it.
		line.classList.remove(`wpdr_${code}`);
		const td = line.getElementsByTagName('td');
		td[3].innerHTML = processed;
		td[4].innerHTML = '';
		// may not match.
		document.getElementById(`on_${id}`).style.display = 'none';
		document.getElementById(`off${id}`).style.display = 'block';
	};

	window.hide_show = function hide_show(id) {
		const inp = document.getElementById(id);
		const line = document.getElementsByClassName(id);
		Array.from(line).forEach((el) => {
			if (inp.checked) {
				el.style.display = 'table-row';
			} else {
				el.style.display = 'none';
			}
		});
	};
})();
