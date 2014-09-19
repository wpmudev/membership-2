jQuery( document ).ready( function( $ ) {

	//global functions defined in ms-ajax-update.js
	ms_functions.close_gateway_settings = function() {
		self.parent.tb_remove();
	};
		
	$( '.ms-datepicker' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	$( '.ms-gateway-setings-form' ).each( function(){
		$( this ).validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			submitHandler: function( form ) {
				gateway = $( form ).data( 'ms');
				
				ms_functions.close_gateway_settings();
				wrapper = '.ms-active-wrapper-' + gateway;
				$( wrapper ).removeClass( 'ms-gateway-not-configured' );
				$( wrapper ).addClass( 'ms-gateway-configured' ); 
			}
		});
	});
	
	$( '.ms-close-button' ).click( ms_functions.close_gateway_settings );
});
