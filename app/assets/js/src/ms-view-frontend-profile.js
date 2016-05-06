/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.frontend_profile = function init () {
	var args = {
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
				'equalTo': '.ms-form-element #password',
			},
		},
	};

	jQuery( '#ms-view-frontend-profile-form' ).validate(args);
};
