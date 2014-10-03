/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
	data: [],
	save_obj_selector: '.ms-save-text-wrapper',
	processing_class: 'ms-processing',
	init_class: 'ms-init',
	radio_slider_on_class: 'on',
	value: 0,

	ajax_update: function( obj ) {
		var data,
			fn = window.ms_functions;

		if( ! jQuery( obj ).hasClass( fn.processing_class ) ) {
			jQuery( fn.save_obj_selector ).addClass( fn.processing_class );
			jQuery( fn.save_obj_selector ).removeClass( fn.init_class );

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

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					jQuery( fn.save_obj_selector ).removeClass( fn.processing_class );
					jQuery( obj ).trigger( 'ms-ajax-updated', data );
				}
			);
		}
	},

	radio_slider_ajax_update: function( obj ) {
		var value, data,
			fn = window.ms_functions;

		if( ! jQuery( obj ).hasClass( fn.processing_class ) ) {
			jQuery( obj ).addClass( fn.processing_class );
			jQuery( fn.save_obj_selector ).addClass( fn.processing_class );
			jQuery( fn.save_obj_selector ).removeClass( fn.init_class );
			if( jQuery( obj ).hasClass( fn.radio_slider_on_class ) ) {
				jQuery( obj ).removeClass( fn.radio_slider_on_class );
				value = 0;
			}
			else {
				jQuery( obj ).addClass( fn.radio_slider_on_class );
				value = 1;
			}

			data = jQuery( obj ).children( '.ms-toggle' ).data( 'ms' );
			if( null != data ) {
				data.value = value;
				jQuery.post(
					window.ajaxurl,
					data,
					function( response ) {
						jQuery( fn.save_obj_selector ).removeClass( fn.processing_class );
						jQuery( obj ).removeClass( fn.processing_class );
						jQuery( obj ).children( 'input' ).val( jQuery( obj ).hasClass( fn.radio_slider_on_class ) );
						jQuery( obj ).trigger( 'ms-radio-slider-updated', data );
					}
				);
			}
		}
	}
};


jQuery( document ).ready( function() {
	var fn = window.ms_functions;

	jQuery( 'div.ms-radio-slider' ).click( function() {
		fn.radio_slider_ajax_update( this );
	});

	jQuery( '.chosen-select' ).chosen({
		disable_search_threshold: 5
	});

	jQuery( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' ).change( function() {
		fn.ajax_update( this );
	});
});
