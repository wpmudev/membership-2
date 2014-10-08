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
	chosen_options: {
		minimumResultsForSearch: 6,
		dropdownAutoWidth: true,
		dropdownCssClass: 'ms-select2',
		containerCssClass: 'ms-select2'
	},

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
	},

	/**
	 * Select the whole content inside the specified element.
	 */
	select_all: function( el ) {
		var range;
		el = jQuery( el )[0];

		if( document.selection ) {
			range = document.body.createTextRange();
			range.moveToElementText( el );
			range.select();
		} else if( window.getSelection ) {
			range = document.createRange();
			range.selectNode( el );
			window.getSelection().addRange( range );
		}
	},

	/**
	 * Toggle the accordeon box state
	 */
	toggle_box: function( el ) {
		var me = jQuery( el ),
			box = me.parents( '.ms-settings-box' ).first();

		if ( box.hasClass( 'static' ) ) { return false; }
		if ( box.hasClass( 'closed' ) ) {
			box.removeClass( 'closed' ).addClass( 'open' );
		} else {
			box.removeClass( 'open' ).addClass( 'closed' );
		}
	},

	/**
	 * Toggle datepicker when user clicks on icon.
	 */
	toggle_datepicker: function( el ) {
		var me = jQuery( el ),
			dp = me.parents( '.ms-datepicker-wrapper' ).find( '.ms-datepicker' );

		dp.datepicker( 'show' );
	}
};


jQuery( document ).ready( function() {
	var fn = window.ms_functions;

	// Toggle radio-sliders on click.
	jQuery( '.ms-radio-slider' ).click( function() {
		fn.radio_slider_ajax_update( this );
	});

	// Toggle accordeon boxes on click.
	jQuery( '.ms-settings-box .handlediv' ).click( function() {
		fn.toggle_box( this );
	});

	// Toggle datepickers when user clicks on icon.
	jQuery( '.ms-datepicker-wrapper .ms-icon' ).click( function() {
		fn.toggle_datepicker( this );
	});

	// Initialize all select boxes
	jQuery( '.ms-wrap select:not(.manual-init), .ms-wrap .chosen-select' ).select2( fn.chosen_options );

	// Ajax-Submit data when ms-ajax-update fields are changed.
	jQuery( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' ).change( function() {
		fn.ajax_update( this );
	});

	// Select all text inside <code> tags on click.
	jQuery( '.ms-wrap' ).on( 'click', 'code', function() {
		fn.select_all( this );
	});
});
