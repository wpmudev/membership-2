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
		close_gateway_settings: function() {
			self.parent.tb_remove();
		},
	}
	$( '.chosen-select' ).chosen({disable_search_threshold: 5});
	
	$( '#currency' ).chosen().change( function() { ms_functions.feedback( this ) } ); 
	
	$( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' ).change( function() { ms_functions.feedback( this ) } );
	
	$( '.ms-datepicker' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	$( '.ms-gateway-setings-form' ).each( function(){
		$( this ).validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			submitHandler: function( form ) {
				gateway = $( form ).data( 'ms');
				
				console.log( gateway );
				ms_functions.close_gateway_settings();
				wrapper = '.ms-active-wrapper-' + gateway;
				console.log( wrapper );
				$( wrapper ).removeClass( 'ms-gateway-not-configured' );
				$( wrapper ).addClass( 'ms-gateway-configured' ); 
			}
		});
	});
	
	$( '.ms-close-button' ).click( ms_functions.close_gateway_settings );
});
