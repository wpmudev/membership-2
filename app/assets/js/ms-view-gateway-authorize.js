jQuery( document ).ready(function( $ ) {
	$( '.chosen-select' ).chosen();
	$('#ms-authorize-extra-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'number': 'required',
				'security_code': 'required',
				'month': 'required',
				'year': 'required',
				'first_name': 'required',
				'last_name': 'required',
			}
		});
});
