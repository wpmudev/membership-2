jQuery( document ).ready(function( $ ) {
	$( '#ms-view-frontend-profile-form' ).validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'email': {
					'required': true,
					'email': true,
				},
				'password': {
					'minlength': 5,
				},
				'password2': {
					'equalTo': '#password',
				},
			},
	});
});
