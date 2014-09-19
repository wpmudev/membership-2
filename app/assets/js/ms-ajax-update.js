/** Global functions */

var ms_functions = {
	feedback: function( obj ) {
		var data = [], 
			save_obj_selector = '.ms-save-text-wrapper', 
			processing_class = 'ms-processing', 
			init_class = 'ms-init',
			radio_slider_on_class = 'on';
			value = 0;
		
		if( ! jQuery( obj ).hasClass( processing_class ) ) {
			jQuery( save_obj_selector ).addClass( processing_class );
			jQuery( save_obj_selector ).removeClass( init_class );

			if( jQuery( obj ).hasClass( 'ms-radio-slider' ) ) {
				if( jQuery( obj ).hasClass( radio_slider_on_class ) ) {
		            jQuery( obj ).removeClass( radio_slider_on_class );
		            value = 0;
		        } 
		        else { 
		            jQuery( obj ).addClass( radio_slider_on_class );
		            value = 1;
		        }
				data = jQuery( object ).children( '.ms-toggle' ).data( 'toggle' );

				if( ! data ) {
					data = jQuery( object ).children( '.ms-toggle' ).data( 'ms' );
					data.value = value;
				}
			}
			else {
				data = jQuery( obj ).data( 'ms' );
				if( jQuery( obj ).is( ':checkbox' ) ) {
					if( jQuery( obj ).attr( 'checked' ) ) {
						data.value = true;
					}
					else {
						data.value = false;
					}
				}
				else {
					data.value = jQuery( obj ).val();
				}
			}			
			jQuery.post( ajaxurl, data, function( response ) {
				jQuery( save_obj_selector ).removeClass( processing_class );
				if( jQuery( obj ).hasClass( 'ms-radio-slider' ) ) {
					jQuery( obj ).removeClass( processing_class );
					jQuery( obj ).children( 'input' ).val( jQuery( obj ).hasClass( 'on' ) );
					jQuery( obj ).trigger( 'ms-radio-slider-updated', data );
				}
				jQuery( obj ).trigger( 'ms-ajax-updated', data );
			});
		}
	}
};


jQuery( document ).ready( function( $ ) {

	$( '.ms-radio-slider' ).click( ms_functions.feedback );
	$( '.chosen-select' ).chosen({ disable_search_threshold: 5 });
	$( '.chosen-select.ms-ajax-update' ).chosen().change( function() { ms_functions.feedback( this ) } );
	$( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' ).change( function() { ms_functions.feedback( this ) } );
	
});
