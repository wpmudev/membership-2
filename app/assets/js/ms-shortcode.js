jQuery( document ).ready(function( $ ) {
	$( '#ms-shortcode-register-user-form' ).validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'username': 'required',
				'email': {
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
	
	$( 'input.membership_cancel' ).click( function() {
		if( window.confirm( ms_shortcode.cancel_msg ) ) {
			return true;
		}
		else {
			return false;
		}
	});
});
