/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
	data: [],
	save_obj_selector: '.ms-save-text-wrapper',
	processing_class: 'ms-processing',
	radio_slider_on_class: 'on',
	value: 0,
	msg_timeout: null,
	chosen_options: {
		minimumResultsForSearch: 6,
		dropdownAutoWidth: true,
		dropdownCssClass: 'ms-select2',
		containerCssClass: 'ms-select2'
	},

	ajax_update: function( obj ) {
		var data, val,
			field = jQuery( obj ),
			fn = window.ms_functions;

		if( ! field.hasClass( fn.processing_class ) ) {
			fn.ajax_show_indicator();

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

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					if ( fn.ajax_error( response ) ) {
						// Reset the input control to previous value...
					}

					jQuery( fn.save_obj_selector ).removeClass( fn.processing_class );
					field.trigger( 'ms-ajax-updated', data, response );
				}
			);
		}
	},

	radio_slider_ajax_update: function( obj ) {
		var data,
			slider = jQuery( obj ),
			fn = window.ms_functions;

		if( ! slider.hasClass( fn.processing_class ) ) {
			fn.ajax_show_indicator();

			slider.addClass( fn.processing_class );
			slider.toggleClass( fn.radio_slider_on_class );

			data = slider.children( '.ms-toggle' ).data( 'ms' );

			if( null != data ) {
				data.value = slider.hasClass( fn.radio_slider_on_class );

				jQuery.post(
					window.ajaxurl,
					data,
					function( response ) {
						if ( fn.ajax_error( response ) ) {
							slider.togglesClass( fn.radio_slider_on_class );
						}

						jQuery( fn.save_obj_selector ).removeClass( fn.processing_class );
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
	ajax_error: function( response ) {
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
			jQuery( fn.save_obj_selector ).removeClass( 'okay' ).addClass( 'error' );
			jQuery( fn.save_obj_selector ).find( '.err-code' ).text( msg );

			// Automatically hide success message after a longer timeout.
			fn.ajax_hide_message( 8000 );
			return true;
		} else {
			// No response code or positive number is interpreted as success.
			jQuery( fn.save_obj_selector ).removeClass( 'error' ).addClass( 'okay' );
			jQuery( fn.save_obj_selector ).find( '.err-code' ).text( '' );

			// Automatically hide success message after short timeout.
			fn.ajax_hide_message( 4000 );
			return false;
		}
	},

	/**
	 * Displays the ajax progress message and cancels the hide-timeout if required.
	 */
	ajax_show_indicator: function() {
		var fn = window.ms_functions;

		if ( null !== fn.msg_timeout ) {
			window.clearTimeout( fn.msg_timeout );
			fn.msg_timeout = null;
		}

		jQuery( fn.save_obj_selector ).addClass( fn.processing_class );
	},

	/**
	 * Hides the ajax response message after a short timeout
	 */
	ajax_hide_message: function( timeout ) {
		var fn = window.ms_functions;

		if ( isNaN( timeout ) ) { timeout = 4000; }
		if ( timeout < 0 ) { timeout = 0; }

		fn.msg_timeout = window.setTimeout( function() {
			jQuery( fn.save_obj_selector ).removeClass( 'error okay' );
		}, timeout );
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
	},

	/**
	 * Tag-Selector component:
	 * Add new tag to the selected-tags list.
	 */
	tag_selector_add: function( ev ) {
		var fn = window.ms_functions,
			me = jQuery( this ).parents( '.ms-tag-selector-wrapper' ).first(),
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
			me = jQuery( el ).parents( '.ms-tag-selector-wrapper' ).first(),
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
	}
};


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

	// Select all text inside <code> tags on click.
	jQuery( '.ms-wrap' )
		.on( 'click', 'code', function() { fn.select_all( this ); } );
});
