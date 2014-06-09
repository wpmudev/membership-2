jQuery( document ).ready(function( $ ) {
	$('#ms-shortcode-register-user-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'user_login': 'required',
				'user_email': {
					'required': true,
					'email': true,
				},
				'password': {
					'required': true,
					'minlenght': 5,
				},
				'password2': {
					'required': true,
					'equalTo': '#password',
				},
			}
		});
});
