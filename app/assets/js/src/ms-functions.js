/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
	processing_class: 'ms-processing',

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

	// Initialize some UI components.
	init: function( scope ) {
		var fn = window.ms_functions;

		// Initialize all select boxes.
		jQuery( '.ms-wrap select:not(.manual-init), .ms-wrap .chosen-select', scope )
			.select2( fn.chosen_options );

		// Initialize the datepickers.
		jQuery( '.ms-datepicker', scope ).ms_datepicker();
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
			slider.toggleClass( 'on' );

			data = slider.children( '.ms-toggle' ).data( 'ms' );

			if( null != data ) {
				data.value = slider.hasClass( 'on' );

				// Allow fields to pre-process the data before sending it.
				if ( 'function' === typeof slider.data( 'before_ajax' ) ) {
					data = slider.data( 'before_ajax' )( data, slider );
				}

				jQuery.post(
					window.ajaxurl,
					data,
					function( response ) {
						if ( fn.ajax_error( response, info_field ) ) {
							slider.togglesClass( 'on' );
						}

						info_field.removeClass( fn.processing_class );

						slider.removeClass( fn.processing_class );
						slider.children( 'input' ).val( slider.hasClass( 'on' ) );
						data.response = response;
						slider.trigger( 'ms-radio-slider-updated', data );
					}
				);
			}
		}
	},

	dynamic_form_submit: function( ev, el ) {
		var i, field_value, field_key, is_popup, info_field,
			fn = window.ms_functions,
			me = jQuery( el ),
			fields = me.serializeArray(),
			data = {};

		ev.preventDefault();

		// Convert the form-data into an object.
		for ( i = 0; i < fields.length; i += 1 ) {
			field_key = fields[i].name;
			field_value = fields[i].value;

			if ( undefined === data[field_key] ) {
				data[field_key] = field_value;
			} else {
				if ( ! data[field_key] instanceof Array ) {
					data[field_key] = [ data[field_key] ];
				}
				data[field_key].push( field_value );
			}
		}
		data['action'] = 'ms_submit';

		info_field = fn.ajax_show_indicator( me );
		is_popup = me.parents( '.ms-dlg-wrap' ).length;

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				if ( fn.ajax_error( response, info_field ) ) {
					// Reset the input control to previous value...
				} else {
					if ( is_popup ) {
						fn.close_dialogs();
					}
				}
			}
		);
		return false;
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
	toggle_box: function( ev, el ) {
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

		fn.tag_selector_refresh_source( ev, this );
	},

	/**
	 * Tag-Selector component:
	 * Disable or Enable options in the source list.
	 */
	tag_selector_refresh_source: function( ev, el ) {
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
	},

	/**
	 * Load a popup dialog via ajax.
	 */
	show_dialog: function( ev ) {
		var me = jQuery( this ),
			fn = window.ms_functions,
			data = { };

		ev.preventDefault();

		/**
		 * Create container elements for the dialog.
		 * This is done only when the first dialog is opened
		 */
		if ( undefined === fn.dlg_wrap ) {
			fn.dlg_wrap = jQuery( '<div class="ms-dlg-wrap"></div>' );
			fn.dlg_wrap.appendTo( 'body' );

			fn.dlg_back = jQuery( '<div class="ms-dlg-back"></div>' );
			fn.dlg_back.appendTo( fn.dlg_wrap ).click( fn.close_dialogs );

			fn.dlg_wrap.on( 'click', '.ms-dlg-close', fn.close_dialogs );
		}

		data['action'] = 'ms_dialog';
		data['dialog'] = me.attr( 'data-ms-dialog' );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var dlg, dlg_title, dlg_close, dlg_content, data = false;

				try { data = jQuery.parseJSON( response ); }
				catch( err ) { data = false; }

				data.title = data.title || 'Dialog';
				data.height = data.height || 100;
				data.content = data.content || '';

				if ( data !== false ) {
					if ( ! isNaN( data.height ) ) {
						data.height += 51;
					}

					// Close button
					dlg_close = jQuery( '<div class="ms-dlg-close"></div>' );
					dlg_close.html( '<i class="dashicons dashicons-no-alt"></i>' );

					// Title
					dlg_title = jQuery( '<div class="ms-dlg-title"></div>' );
					dlg_title.append( '<span></span>' ).append( dlg_close );
					dlg_title.find( 'span' ).html( data.title );

					// Content
					dlg_content = jQuery( '<div class="ms-dlg-content"></div>' );
					dlg_content.html( data.content );

					// Combine all dialog elements
					dlg = jQuery( '<div class="ms-dlg"></div>' );
					dlg.append( dlg_title ).append( dlg_content ).height( data.height );

					fn.dlg_wrap.append( dlg ).show();

					// Initialize UI components.
					fn.init( dlg );
				}
			}
		);

		return false;
	},

	/**
	 * Closes all open dialogs.
	 */
	close_dialogs: function() {
		var fn = window.ms_functions;

		if ( undefined !== fn.dlg_wrap ) {
			// Hide all dialogs.
			fn.dlg_wrap.hide();

			// Remove all dialogs from DOM.
			fn.dlg_wrap.find( '.ms-dlg' ).remove();
		}
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

/**
 * Do general initialization:
 * Hook up various events with the plugin callback functions.
 */
jQuery( document ).ready( function() {
	var fn = window.ms_functions;

	jQuery( 'body' )
	// Toggle radio-sliders on click.
	.on(
		'click',
		'.ms-radio-slider',
		function( ev ) { fn.radio_slider_ajax_update( this ); }
	)
	// Toggle accordeon boxes on click.
	.on(
		'click',
		'.ms-settings-box .handlediv',
		function( ev ) { fn.toggle_box( ev, this ); }
	)
	// Toggle datepickers when user clicks on icon.
	.on(
		'click',
		'.ms-datepicker-wrapper .ms-icon',
		function( ev ) { fn.toggle_datepicker( this ); }
	)
	// Initialize the tag-select components.
	.on(
		'select2-opening',
		'.ms-tag-selector-wrapper .ms-tag-data',
		function( ev ) { ev.preventDefault(); }
	)
	.on(
		'change',
		'.ms-tag-selector-wrapper .ms-tag-data',
		function( ev ) { fn.tag_selector_refresh_source( ev, this ); }
	)
	.on(
		'click',
		'.ms-tag-selector-wrapper .ms-tag-button',
		fn.tag_selector_add
	)
	// Ajax-Submit data when ms-ajax-update fields are changed.
	.on(
		'change',
		'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update',
		function( ev ) { fn.ajax_update( this ); }
	)
	.on(
		'click',
		'button.ms-ajax-update',
		function( ev ) { fn.ajax_update( this ); }
	)
	.on(
		'submit',
		'form.ms-ajax-update',
		function( ev ) { fn.dynamic_form_submit( ev, this ); }
	)
	// Initialize popup dialogs.
	.on(
		'click',
		'[data-ms-dialog]',
		fn.show_dialog
	);

	// Select all text inside <code> tags on click.
	jQuery( '.ms-wrap' ).on(
		'click',
		'code',
		function() { fn.select_all( this ); }
	);

	fn.init( 'body' );
});
