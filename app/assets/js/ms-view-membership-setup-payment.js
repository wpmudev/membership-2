jQuery( document ).ready( function( $ ) {
	
	//global functions defined in ms-functions.js
	ms_functions.payment_type = function( obj ) {

		$( obj ).parent().parent().find( '.ms-payment-type-wrapper' ).hide();
		payment_type = $( obj ).val();
		$( obj ).parent().parent().find( '.ms-payment-type-' + payment_type ).show();

		after_end = $( obj ).parent().parent().find( '.ms-after-end-wrapper' );
		if( 'permanent' == payment_type ) {
			after_end.hide();
		}
		else {
			after_end.show();
		}
	};
	ms_functions.is_free = function() {
		if( 0 == $( 'input[name="is_free"]:checked' ).val() ) {
			$( '#ms-payment-settings-wrapper' ).show();
		}
		else {
			$( '#ms-payment-settings-wrapper' ).hide();
		}
	};

	$( '.ms-datepicker' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	$( 'input[name="is_free"]' ).change( function() { ms_functions.is_free() } );

	$( '.ms-payment-type' ).change( function() { ms_functions.payment_type( this ) } );

	// initial event fire
	$( '.ms-payment-type' ).each( function() { ms_functions.payment_type( this ) } );
	ms_functions.is_free();
	
});