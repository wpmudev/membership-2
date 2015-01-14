/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.shortcode = function init () {
	var args = {
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
				'minlength': 5,
			},
			'password2': {
				'required': true,
				'equalTo': '#password',
			},
		}
	};

	jQuery( '#ms-shortcode-register-user-form' ).validate(args);

	jQuery( '.ms-membership-form .membership_cancel' ).click( function() {
		if ( window.confirm( ms_data.cancel_msg ) ) {
			return true;
		} else {
			return false;
		}
	});
};
