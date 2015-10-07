/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.gateway_authorize = function init () {
	var last_cc_num = '',
		profiles = jQuery( '#ms-authorize-cim-profiles-wrapper' ),
		new_card = jQuery( '#ms-authorize-card-wrapper' ),
		args = {
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

	jQuery( '.ms-select' ).wpmuiSelect({
		width: 'auto',
		minimumResultsForSearch: 30
	});

	// Insert a space after 4 digits of the CC number
	jQuery( '.wpmui-input-card_num' ).keyup(function() {
		var cc_num = jQuery( this ).val().replace( /\D/g, "" );
		if ( cc_num !== last_cc_num && cc_num.length > 0 ) {
			last_cc_num = cc_num;
			cc_num = cc_num.match( /.{1,4}/g ).join( " " );
			jQuery( this ).val( cc_num );
		}
	});

	if ( profiles.is(':visible') ) {
		jQuery( 'input[name="profile"]').change( function() {
			if ( jQuery( this ).val() === '0' ) {
				new_card.show();
				profiles.find('.ms-row-submit').hide();
			} else {
				new_card.hide();
				profiles.find('.ms-row-submit').show();
			}
		});

		jQuery( 'input[name="profile"]').first().change();
	}

	jQuery('#ms-authorize-extra-form').validate(args);
};
