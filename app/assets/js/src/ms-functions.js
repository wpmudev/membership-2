/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
	data: [],
	processing_class: 'ms-processing',
	radio_slider_on_class: 'on',
	value: 0,
	dp_config: {
        dateFormat: 'yy-mm-dd', //TODO get wp configured date format
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thy', 'Fri', 'Sat'],
        custom_class: 'ms-datepicker' // Not a jQuery argument!
    },
	chosen_options: {
		minimumResultsForSearch: 6,
		dropdownAutoWidth: true,
		dropdownCssClass: 'ms-select2',
		containerCssClass: 'ms-select2'
	},

	ajax_update: function( obj ) {
		var data, val, info_field,
			field = jQuery( obj ),
			fn = window.ms_functions;

		if( ! field.hasClass( fn.processing_class ) ) {
			info_field = fn.ajax_show_indicator( field );

			data = field.data( 'ms' );

			if( field.is( ':checkbox' ) ) {
				data.value = field.prop( 'checked' );
			}
			else {
				val = field.val();
				if ( val instanceof Array || val instanceof Object || null === val ) {
					data.values = val;
				} else {
					data.value = val;
				}
			}

			// Allow fields to pre-process the data before sending it.
			if ( 'function' === typeof field.data( 'before_ajax' ) ) {
				data = field.data( 'before_ajax' )( data, field );
			}

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					if ( fn.ajax_error( response, info_field ) ) {
						// Reset the input control to previous value...
					}

					info_field.removeClass( fn.processing_class );
					field.trigger( 'ms-ajax-updated', data, response );
				}
			);
		}
	},

	radio_slider_ajax_update: function( obj ) {
		var data, info_field,
			slider = jQuery( obj ),
			fn = window.ms_functions;

		if( ! slider.hasClass( fn.processing_class ) && ! slider.attr( 'readonly' ) ) {
			info_field = fn.ajax_show_indicator( slider );

			slider.addClass( fn.processing_class );
			slider.toggleClass( fn.radio_slider_on_class );

			data = slider.children( '.ms-toggle' ).data( 'ms' );

			if( null != data ) {
				data.value = slider.hasClass( fn.radio_slider_on_class );

				// Allow fields to pre-process the data before sending it.
				if ( 'function' === typeof slider.data( 'before_ajax' ) ) {
					data = slider.data( 'before_ajax' )( data, slider );
				}

				jQuery.post(
					window.ajaxurl,
					data,
					function( response ) {
						if ( fn.ajax_error( response, info_field ) ) {
							slider.togglesClass( fn.radio_slider_on_class );
						}

						info_field.removeClass( fn.processing_class );

						slider.removeClass( fn.processing_class );
						slider.children( 'input' ).val( slider.hasClass( fn.radio_slider_on_class ) );
						slider.trigger( 'ms-radio-slider-updated', data );
					}
				);
			}
		}
	},

	/**
	 * Receives the ajax response string and checks if the response starts with
	 * an error code.
	 * An error code is a negative number at the start of the response.
	 *
	 * Returns true when an error code is found.
	 * When no numeric code is found the function returns false (no error)
	 */
	ajax_error: function( response, info_field ) {
		var code = 0,
			parts = [],
			msg = '',
			fn = window.ms_functions;

		if ( isNaN( response ) ) {
			parts = response.split( ':', 2 );
			if ( ! isNaN( parts[0] ) ) { code = parts[0]; }
			if ( undefined !== parts[1] ) { msg = parts[1]; }
		} else {
			code = response;
		}

		if ( code < 0 ) {
			// Negative number as response code is an error-indicator.
			info_field.removeClass( 'okay' ).addClass( 'error' );
			info_field.find( '.err-code' ).text( msg );

			// Automatically hide success message after a longer timeout.
			fn.ajax_hide_message( 8000, info_field );
			return true;
		} else {
			// No response code or positive number is interpreted as success.
			info_field.removeClass( 'error' ).addClass( 'okay' );
			info_field.find( '.err-code' ).text( '' );

			// Automatically hide success message after short timeout.
			fn.ajax_hide_message( 4000, info_field );
			return false;
		}
	},

	/**
	 * Displays the ajax progress message and cancels the hide-timeout if required.
	 */
	ajax_show_indicator: function( field ) {
		var info_field,
			fn = window.ms_functions;

		info_field = field.nearest( '.ms-save-text-wrapper' );

		if ( null !== info_field.data( 'msg_timeout' ) ) {
			window.clearTimeout( info_field.data( 'msg_timeout' ) );
			info_field.data( 'msg_timeout', null );
		}

		info_field.addClass( fn.processing_class );
		info_field.removeClass( 'error okay' );
		return info_field;
	},

	/**
	 * Hides the ajax response message after a short timeout
	 */
	ajax_hide_message: function( timeout, info_field ) {
		var tmr_id,
			fn = window.ms_functions;

		if ( isNaN( timeout ) ) { timeout = 4000; }
		if ( timeout < 0 ) { timeout = 0; }

		tmr_id = window.setTimeout( function() {
			var field = info_field;
			field.removeClass( 'error okay' );
		}, timeout );

		info_field.data( 'msg_timeout', tmr_id );
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
			box = me.closest( '.ms-settings-box' );

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
			dp = me.closest( '.ms-datepicker-wrapper' ).find( '.ms-datepicker' );

		dp.datepicker( 'show' );
	},

	/**
	 * Tag-Selector component:
	 * Add new tag to the selected-tags list.
	 */
	tag_selector_add: function( ev ) {
		var fn = window.ms_functions,
			me = jQuery( this ).closest( '.ms-tag-selector-wrapper' ),
			el_src = me.find( 'select.ms-tag-source' ),
			el_dst = me.find( 'select.ms-tag-data' ),
			list = el_dst.val() || [];

		if ( ! el_src.val().length ) { return; }

		list.push( el_src.val() );
		el_dst.val( list ).trigger( 'change' );
		el_src.val( '' ).trigger( 'change' );

		fn.tag_selector_refresh_source( this );
	},

	/**
	 * Tag-Selector component:
	 * Disable or Enable options in the source list.
	 */
	tag_selector_refresh_source: function( el ) {
		var i = 0, item = null,
			me = jQuery( el ).closest( '.ms-tag-selector-wrapper' ),
			el_src = me.find( 'select.ms-tag-source' ),
			el_src_items = el_src.find( 'option' ),
			el_dst = me.find( 'select.ms-tag-data' ),
			list = el_dst.val() || [];

		for ( i = 0; i < el_src_items.length; i += 1 ) {
			item = jQuery( el_src_items[i] );
			if ( -1 !== jQuery.inArray( item.val(), list ) ) {
				item.prop( 'disabled', true );
			} else {
				item.prop( 'disabled', false );
			}
		}
		el_src.trigger( 'change' );
	},

	/**
	 * Reload the current page.
	 */
	reload: function() {
		window.location.reload();
	}
};

// Add our own Datepicker-init function which extends the jQuery Datepicker.
jQuery.fn.ms_datepicker = function( args ) {
	var bs_callback = null,
		fn = window.ms_functions,
		config = jQuery.extend( fn.dp_config, args );

	if ( 'function' === typeof config.beforeShow ) {
		bs_callback = config.beforeShow;
	}

	config.beforeShow = function(input, inst) {
		if ( undefined !== inst && undefined !== inst.dpDiv ) {
			jQuery( inst.dpDiv ).addClass( config.custom_class );
		}

		if ( null !== bs_callback ) {
			bs_callback( input, inst );
		}
	};

	return this.each(function() {
		jQuery( this ).datepicker( config );
	});
};

// Do general initialization.
jQuery( document ).ready( function() {
	var fn = window.ms_functions;

	// Toggle radio-sliders on click.
	jQuery( '.ms-radio-slider' )
		.click( function() { fn.radio_slider_ajax_update( this ); } );

	// Toggle accordeon boxes on click.
	jQuery( '.ms-settings-box .handlediv' )
		.click( function() { fn.toggle_box( this ); } );

	// Toggle datepickers when user clicks on icon.
	jQuery( '.ms-datepicker-wrapper .ms-icon' )
		.click( function() { fn.toggle_datepicker( this ); } );

	// Initialize all select boxes.
	jQuery( '.ms-wrap select:not(.manual-init), .ms-wrap .chosen-select' )
		.select2( fn.chosen_options );

	// Initialize the tag-select components.
	jQuery( '.ms-tag-selector-wrapper .ms-tag-data ' )
		.on( 'select2-opening', function( ev ) { ev.preventDefault(); } )
		.on( 'change', function( ev ) { fn.tag_selector_refresh_source( this ); } );
	jQuery( '.ms-tag-selector-wrapper .ms-tag-button' )
		.click( fn.tag_selector_add );

	// Ajax-Submit data when ms-ajax-update fields are changed.
	jQuery( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' )
		.change( function() { fn.ajax_update( this ); } );
	jQuery( 'button.ms-ajax-update' )
		.click( function() { fn.ajax_update( this ); } );

	// Select all text inside <code> tags on click.
	jQuery( '.ms-wrap' )
		.on( 'click', 'code', function() { fn.select_all( this ); } );

	// Initialize the datepickers.
	jQuery( '.ms-datepicker' ).ms_datepicker();
});
