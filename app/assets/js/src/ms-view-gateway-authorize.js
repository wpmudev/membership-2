/*global jQuery:false */
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
