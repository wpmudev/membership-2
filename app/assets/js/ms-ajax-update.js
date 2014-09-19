/** Global functions */

var ms_functions = {
	data: [], 
	save_obj_selector: '.ms-save-text-wrapper', 
	processing_class: 'ms-processing', 
	init_class: 'ms-init',
	radio_slider_on_class: 'on',
	value: 0,
	ajax_update: function( obj ) {
		if( ! jQuery( obj ).hasClass( this.processing_class ) ) {
			jQuery( this.save_obj_selector ).addClass( this.processing_class );
			jQuery( this.save_obj_selector ).removeClass( this.init_class );

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
						
			jQuery.post( ajaxurl, data, function( response ) {
				jQuery( ms_functions.save_obj_selector ).removeClass( ms_functions.processing_class );
				jQuery( obj ).trigger( 'ms-ajax-updated', data );
			});
		}
	},
	radio_slider_ajax_update: function( obj ) {
		
		if( ! jQuery( obj ).hasClass( this.processing_class ) ) {
			jQuery( obj ).addClass( this.processing_class );
			jQuery( this.save_obj_selector ).addClass( this.processing_class );
			jQuery( this.save_obj_selector ).removeClass( this.init_class );
			if( jQuery( obj ).hasClass( this.radio_slider_on_class ) ) {
	            jQuery( obj ).removeClass( this.radio_slider_on_class );
	            value = 0;
	        } 
	        else { 
	            jQuery( obj ).addClass( this.radio_slider_on_class );
	            value = 1;
	        }
			
			//TODO change all radio sliders to use ms_data instead of ms-toggle 
			data = jQuery( obj ).children( '.ms-toggle' ).data( 'toggle' );
			if( null == data ) {
				data = jQuery( obj ).children( '.ms-toggle' ).data( 'ms' );
			}
			
			if( null != data ) {
				jQuery.post( ajaxurl, data, function( response ) {
					data.value = value;
					jQuery( ms_functions.save_obj_selector ).removeClass( ms_functions.processing_class );
					jQuery( obj ).removeClass( ms_functions.processing_class );
					jQuery( obj ).children( 'input' ).val( jQuery( obj ).hasClass( ms_functions.radio_slider_on_class ) );
					jQuery( obj ).trigger( 'ms-radio-slider-updated', data );
				});
			}
		}
	}
};


jQuery( document ).ready( function( $ ) {

	$( 'div.ms-radio-slider' ).click( function() { ms_functions.radio_slider_ajax_update( this ) });
	$( '.chosen-select' ).chosen({ disable_search_threshold: 5 });
//	$( '.chosen-select.ms-ajax-update' ).chosen().change( function() { ms_functions.ajax_update( this ) } );
	$( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' ).change( function() { ms_functions.ajax_update( this ) } );
	
});
