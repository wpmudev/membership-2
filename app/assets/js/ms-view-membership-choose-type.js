jQuery( document ).ready(function( $ ) {
	
	$( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});
	
	$( 'input[name="type"]' ).click( function() {
		if( $.inArray( $( this ).val(), ms_private_types ) > -1 ) {
			$( '.ms-private-wrapper' ).show();
		}
		else {
			$( '.ms-private-wrapper' ).hide();
		}
	});
	
	$( 'input[name="type"]' ).first().click();
});
