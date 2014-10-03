jQuery( document ).ready(function( $ ) {
	$( '.chosen-select' ).select2( ms_functions.chosen_options );
	if( $( '#ms-authorize-cim-profiles-wrapper' ).is(':visible') ) {
		$( 'input[name="profile"]').change( function() {
			console.log("djaos:" + $( this ).val());
			if( $( this ).val() == 0 ) {
				console.log("val!");
				$( '#ms-authorize-card-wrapper' ).show();
			}
			else {
				console.log("not val!");
				$( '#ms-authorize-card-wrapper' ).hide();
			}
		});

		$( 'input[name="profile"]').first().change();
	}

	$('#ms-authorize-extra-form').validate({
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
		});
});
