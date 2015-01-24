/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global wpmUi:false */

/* Global functions */

window.ms_functions = {
	dp_config: {
        dateFormat: 'yy-mm-dd', //TODO get wp configured date format
        dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thy', 'Fri', 'Sat'],
        custom_class: 'wpmui-datepicker' // Not a jQuery argument!
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
		jQuery( '.ms-wrap select, .ms-wrap .chosen-select', scope ).each(function() {
			var el = jQuery( this );
			if ( el.closest( '.no-auto-init' ).length ) { return; }
			if ( el.closest( '.manual-init' ).length ) { return; }

			el.select2( fn.chosen_options );
		});

		// Initialize the datepickers.
		jQuery( '.wpmui-datepicker', scope ).ms_datepicker();
	},

	ajax_update: function( obj ) {
		var data, val, info_field,
			field = jQuery( obj ),
			fn = window.ms_functions,
			anim = field;

		if ( ! field.hasClass( 'ms-processing' ) ) {
			if ( anim.parents( '.wpmui-radio-wrapper' ).length ) {
				anim = anim.parents( '.wpmui-radio-wrapper' ).first();
			} else if ( anim.parents( '.wpmui-radio-slider-wrapper' ).length ) {
				anim = anim.parents( '.wpmui-radio-slider-wrapper' ).first();
			} else if ( anim.parents( '.wpmui-input-wrapper' ).length ) {
				anim = anim.parents( '.wpmui-input-wrapper' ).first();
			} else if ( anim.parents( 'label' ).length ) {
				anim = anim.parents( 'label' ).first();
			}

			anim.addClass( 'wpmui-loading' );
			info_field = fn.ajax_show_indicator( field );

			data = field.data( 'ajax' );

			if ( field.is( ':checkbox' ) ) {
				data.value = field.prop( 'checked' );
			} else {
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

			field.trigger( 'ms-ajax-start', [data, info_field, anim] );
			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					var is_err = fn.ajax_error( response, info_field );
					if ( is_err ) {
						// Reset the input control to previous value...
					}

					anim.removeClass( 'wpmui-loading' );
					info_field.removeClass( 'ms-processing' );
					field.trigger( 'ms-ajax-updated', [data, response, is_err] );
				}
			).always(function() {
				field.trigger( 'ms-ajax-done', [data, info_field, anim] );
			});
		}
	},

	radio_slider_ajax_update: function( obj ) {
		var data, info_field, toggle, states, state,
			slider = jQuery( obj ),
			fn = window.ms_functions;

		if ( ! slider.hasClass( 'ms-processing' ) && ! slider.attr( 'readonly' ) ) {
			slider.toggleClass( 'on' );
			slider.parent().toggleClass( 'on' );
			slider.trigger( 'change' );

			toggle = slider.children( '.wpmui-toggle' );
			data = toggle.data( 'ajax' );
			states = toggle.data( 'states' );

			if ( null !== data && undefined !== data ) {
				info_field = fn.ajax_show_indicator( slider );
				slider.addClass( 'ms-processing wpmui-loading' );
				state = slider.hasClass( 'on' );

				if ( undefined !== states.active && state ) {
					data.value = states.active;
				} else if ( undefined !== states.inactive && ! state ) {
					data.value = states.inactive;
				} else {
					data.value = state;
				}

				// Allow fields to pre-process the data before sending it.
				if ( 'function' === typeof slider.data( 'before_ajax' ) ) {
					data = slider.data( 'before_ajax' )( data, slider );
				}

				slider.trigger( 'ms-ajax-start', [data, info_field, slider] );
				jQuery.post(
					window.ajaxurl,
					data,
					function( response ) {
						var is_err = fn.ajax_error( response, info_field );
						if ( is_err ) {
							slider.toggleClass( 'on' );
						}

						info_field.removeClass( 'ms-processing' );

						slider.removeClass( 'ms-processing wpmui-loading' );
						slider.children( 'input' ).val( slider.hasClass( 'on' ) );
						data.response = response;
						slider.trigger( 'ms-ajax-updated', [data, response, is_err] );
						slider.trigger( 'ms-radio-slider-updated', [data, is_err] );
					}
				).always(function() {
					slider.trigger( 'ms-ajax-done', [data, info_field, slider] );
				});
			} else {
				slider.children( 'input' ).val( slider.hasClass( 'on' ) );
			}
		}
	},

	dynamic_form_submit: function( ev, el ) {
		var i, field_value, field_key, is_popup, info_field, popup,
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

		popup = me.parents( '.wpmui-wnd' );
		is_popup = popup.length;
		if ( ! is_popup ) {
			info_field = fn.ajax_show_indicator( me );
		} else {
			popup.addClass( 'wpmui-loading' );
		}

		jQuery( document ).trigger( 'ms-ajax-form-send', [me, data, is_popup, info_field] );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var is_err = fn.ajax_error( response, info_field );

				if ( popup.length ) {
					popup.removeClass( 'wpmui-loading' );
				}

				if ( is_err ) {
					// Reset the input control to previous value...
				} else {
					if ( is_popup ) {
						fn.close_dialogs();
					}
				}
				jQuery( document ).trigger( 'ms-ajax-form-done', [me, response, is_err, data, is_popup, info_field] );
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
			if ( info_field ) {
				info_field.removeClass( 'okay' ).addClass( 'error' );
				info_field.find( '.err-code' ).text( msg );

				// Automatically hide success message after a longer timeout.
				fn.ajax_hide_message( 8000, info_field );
			}
			return true;
		} else {
			if ( info_field ) {
				// No response code or positive number is interpreted as success.
				info_field.removeClass( 'error' ).addClass( 'okay' );
				info_field.find( '.err-code' ).text( '' );

				// Automatically hide success message after short timeout.
				fn.ajax_hide_message( 4000, info_field );
			}
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

		info_field.addClass( 'ms-processing' );
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

		if ( document.selection ) {
			range = document.body.createTextRange();
			range.moveToElementText( el );
			range.select();
		} else if ( window.getSelection ) {
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
			dp = me.closest( '.wpmui-datepicker-wrapper' ).find( '.wpmui-datepicker' );

		dp.datepicker( 'show' );
	},

	/**
	 * Tag-Selector component:
	 * Add new tag to the selected-tags list.
	 */
	tag_selector_add: function( ev ) {
		var fn = window.ms_functions,
			me = jQuery( this ).closest( '.wpmui-tag-selector-wrapper' ),
			el_src = me.find( 'select.wpmui-tag-source' ),
			el_dst = me.find( 'select.wpmui-tag-data' ),
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
			me = jQuery( el ).closest( '.wpmui-tag-selector-wrapper' ),
			el_src = me.find( 'select.wpmui-tag-source' ),
			el_src_items = el_src.find( 'option' ),
			el_dst = me.find( 'select.wpmui-tag-data' ),
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
			data = { },
			manual_data;

		ev.preventDefault();

		manual_data = me.attr( 'data-ms-data' );
		if ( undefined !== manual_data ) {
			try { data = jQuery.parseJSON( manual_data ); }
			catch( err ) { data = {}; }
		}

		data['action'] = 'ms_dialog';
		data['dialog'] = me.attr( 'data-ms-dialog' );
		jQuery( document ).trigger( 'ms-load-dialog', [data] );
		me.addClass( 'wpmui-loading' );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var dlg, resp = false;

				me.removeClass( 'wpmui-loading' );

				try { resp = jQuery.parseJSON( response ); }
				catch( err ) { resp = false; }

				resp.title = resp.title || 'Dialog';
				resp.height = resp.height || 100;
				resp.content = resp.content || '';
				resp.modal = resp.modal || true;

				dlg = wpmUi.popup()
					.modal( true, ! resp.modal )
					.title( resp.title )
					.size( undefined, resp.height )
					.content( resp.content )
					.show();
			}
		);

		return false;
	},

	/**
	 * Closes all open dialogs.
	 */
	close_dialogs: function() {
		var id, popups = wpmUi.popups();

		for ( id in popups ) {
			popups[id].close();
		}
	},

	/**
	 * Update the view-counter when protection inside a view list-table is changed
	 */
	update_view_count: function( event, data, is_err ) {
		var me = jQuery( this ),
			table = me.closest( '.wp-list-table' ),
			form = table.closest( 'form' ),
			box = form.parent(),
			views = box.find( '.subsubsub' ).first(),
			el_open = views.find( '.has_access .count' ),
			el_locked = views.find( '.no_access .count' ),
			num_open = parseInt( el_open.text().replace(/\D/, '') ),
			num_locked = parseInt( el_locked.text().replace(/\D/, '') );

		if ( isNaN( num_open ) ) { num_open = 0; }
		if ( isNaN( num_locked ) ) { num_locked = 0; }

		if ( data.value ) {
			num_locked -= 1;
			num_open += 1;
		} else {
			num_locked += 1;
			num_open -= 1;
		}

		if ( num_open < 0 ) { num_open = 0; }
		if ( num_locked < 0 ) { num_locked = 0; }

		if ( num_open === 0 ) {
			el_open.text( '' );
		} else {
			el_open.text( '(' + num_open + ')' );
		}

		if ( num_locked === 0 ) {
			el_locked.text( '' );
		} else {
			el_locked.text( '(' + num_locked + ')' );
		}
	},

	// Submit a form from outside the form tag:
	// <span class="ms-submit-form" data-form="class-of-the-form">Submit</span>
	submit_form: function() {
		var me = jQuery( this ),
			selector = me.data( 'form' ),
			form = jQuery( 'form.' + selector );

		if ( form.length ) {
			form.submit();
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
		'.wpmui-radio-slider',
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
		'.wpmui-datepicker-wrapper .wpmui-icon',
		function( ev ) { fn.toggle_datepicker( this ); }
	)
	// Initialize the tag-select components.
	.on(
		'select2-opening',
		'.wpmui-tag-selector-wrapper .wpmui-tag-data',
		function( ev ) { ev.preventDefault(); }
	)
	.on(
		'change',
		'.wpmui-tag-selector-wrapper .wpmui-tag-data',
		function( ev ) { fn.tag_selector_refresh_source( ev, this ); }
	)
	.on(
		'click',
		'.wpmui-tag-selector-wrapper .wpmui-tag-button',
		fn.tag_selector_add
	)
	// Ajax-Submit data when ms-ajax-update fields are changed.
	.on(
		'change',
		'input.wpmui-ajax-update, select.wpmui-ajax-update, textarea.wpmui-ajax-update',
		function( ev ) { fn.ajax_update( this ); }
	)
	.on(
		'click',
		'button.wpmui-ajax-update',
		function( ev ) { fn.ajax_update( this ); }
	)
	.on(
		'submit',
		'form.wpmui-ajax-update',
		function( ev ) { fn.dynamic_form_submit( ev, this ); }
	)
	// Initialize popup dialogs.
	.on(
		'click',
		'[data-ms-dialog]',
		fn.show_dialog
	)
	// Update counter of the views in rule list-tables
	.on(
		'ms-radio-slider-updated',
		'.wp-list-table.rules .wpmui-radio-slider',
		fn.update_view_count
	)
	.on(
		'click',
		'.ms-submit-form',
		fn.submit_form
	)
	;

	// Select all text inside <code> tags on click.
	jQuery( '.ms-wrap' ).on(
		'click',
		'code',
		function() { fn.select_all( this ); }
	);

	fn.init( 'body' );
});
