/*! Protected Content - v1.1.13
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2015; * Licensed GPLv2+ */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var i;

	window.ms_init._done = window.ms_init._done || {};

	function initialize( callback ) {
		if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
			// Prevent multiple calls to init functions...
			if ( true === window.ms_init._done[callback] ) { return false; }

			window.ms_init._done[callback] = true;
			window.ms_init[callback]();
		}
	}

	if ( undefined === window.ms_data ) { return; }

	if ( undefined !== ms_data.ms_init ) {
		if ( ms_data.ms_init instanceof Array ) {
			for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
				initialize( ms_data.ms_init[i] );
			}
		} else {
			initialize( ms_data.ms_init );
		}

		// Prevent multiple calls to init functions...
		ms_data.ms_init = [];
	}
});

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
				'equalTo': '#password',
			},
		},
	};

	jQuery( '#ms-view-frontend-profile-form' ).validate(args);
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.gateway_authorize = function init () {
	var args = {
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'card_num': 'required',
			'card_code': 'required',
			'exp_month': 'required',
			'exp_year': 'required',
			'first_name': 'required',
			'last_name': 'required',
		}
	};

	jQuery( '.chosen-select' ).select2();

	if ( jQuery( '#ms-authorize-cim-profiles-wrapper' ).is(':visible') ) {
		jQuery( 'input[name="profile"]').change( function() {
			if ( jQuery( this ).val() === '0' ) {
				jQuery( '#ms-authorize-card-wrapper' ).show();
			} else {
				jQuery( '#ms-authorize-card-wrapper' ).hide();
			}
		});

		jQuery( 'input[name="profile"]').first().change();
	}

	jQuery('#ms-authorize-extra-form').validate(args);
};
