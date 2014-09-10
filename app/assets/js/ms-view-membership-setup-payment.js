jQuery( document ).ready( function( $ ) {

	var ms_functions = {
		feedback: function( obj ) {
			var data = [], save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
			
			if( ! $( obj ).hasClass( processing_class ) ) {
				$( save_obj_selector ).addClass( processing_class );
				$( save_obj_selector ).removeClass( init_class );

				data = $( obj ).data( 'ms' );
				if( $( obj ).is( ':checkbox' ) ) {
					if( $( obj ).attr( 'checked' ) ) {
						data.value = true;
					}
					else {
						data.value = false;
					}
				}
				else {
					data.value = $( obj ).val();
				}
				
				$.post( ajaxurl, data, function( response ) {
					$( save_obj_selector ).removeClass( processing_class );
				});
			}
		},
		payment_type: function( obj ) {
			$( obj ).parent().parent().find( '.ms-payment-type-wrapper' ).hide();
			payment_type = $( obj ).val();
			$( obj ).parent().parent().find( '.ms-payment-type-' + payment_type).show();
			
			after_end = $( obj ).parent().parent().find( '.ms-after-end-wrapper' );
			if( 'permanent' == payment_type ) {
				after_end.hide();
			}
			else {
				after_end.show();
			}
		},
		is_free: function() {
			if( 1 == $( 'input[name="is_free"]:checked' ).val() ) {
				$( '#ms-payment-settings-wrapper' ).show();
			}
			else {
				$( '#ms-payment-settings-wrapper' ).hide();
			}
			
		}
	}
	$( 'input[name="is_free"]' ).change( function() { ms_functions.is_free() } );

	$( '.chosen-select' ).chosen({disable_search_threshold: 5});
	
	$( '#currency' ).chosen().change( function() { ms_functions.feedback( this ) } ); 
	
	$( 'input.ms-ajax-update' ).change( function() { ms_functions.feedback( this ) } );
	
	$( '.ms-payment-type').change( function() { ms_functions.payment_type( this ) } );

	$( '#period_date_start' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	$( '#period_date_end' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	ms_functions.payment_type( $( '.ms-payment-type' ) );
	ms_functions.is_free();
});