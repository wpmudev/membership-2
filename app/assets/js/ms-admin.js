/*! Membership 2 Pro - v1.0.4
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2017; * Licensed GPLv2+ */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var i;

	window.ms_init._done = window.ms_init._done || {};

	function initialize( callback ) {
		if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
			// Prevent multiple calls to init functions...
			if ( true === window.ms_init._done[callback] ) { return false; }

			window.ms_init._done[callback] = true;
			window.ms_init[callback]();
		}
	}

	if ( undefined === window.ms_data ) { return; }

	if ( undefined !== ms_data.ms_init ) {
		if ( ms_data.ms_init instanceof Array ) {
			for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
				initialize( ms_data.ms_init[i] );
			}
		} else {
			initialize( ms_data.ms_init );
		}

		// Prevent multiple calls to init functions...
		ms_data.ms_init = [];
	}
});

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
		width: 'auto'
	},

	// Initialize some UI components.
	init: function( scope ) {
		var fn = window.ms_functions;

		// Initialize all select boxes.
		jQuery( '.ms-wrap select, .ms-select', scope ).each(function() {
			var el = jQuery( this );
			if ( el.closest( '.no-auto-init' ).length ) { return; }
			if ( el.closest( '.manual-init' ).length ) { return; }

			el.wpmuiSelect( fn.chosen_options );
		});

		// Initialize the datepickers.
		jQuery( '.wpmui-datepicker', scope ).each(function() {
			var sel = jQuery( this );

			if ( sel.closest( '.no-auto-init' ).length ) { return; }
			sel.ms_datepicker();
		});

		window.setTimeout(function(){
			jQuery('body').trigger( 'resize' );
		}, 50 );
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
			} else if ( anim.parents( '.wpmui-select-wrapper' ).length ) {
				anim = anim.parents( '.wpmui-select-wrapper' ).first();
			} else if ( anim.parents( 'label' ).length ) {
				anim = anim.parents( 'label' ).first();
			}

			anim.addClass( 'wpmui-loading' );
			info_field = fn.ajax_show_indicator( field );

			data = field.data( 'wpmui-ajax' );

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
			if ( undefined === data.field ) {
				data.field = field.attr( 'name' );
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
			slider.toggleClass( 'off' );
			slider.parent().toggleClass( 'on' );
			slider.parent().toggleClass( 'off' );
			slider.trigger( 'change' );

			toggle = slider.children( '.wpmui-toggle' );
			data = toggle.data( 'wpmui-ajax' );
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
							slider.toggleClass( 'off' );
						}

						info_field.removeClass( 'ms-processing' );

						slider.removeClass( 'ms-processing wpmui-loading' );
						slider.children( 'input' ).val( slider.hasClass( 'on' ) );
						data.response = response;
						slider.trigger( 'ms-ajax-updated', [data, response, is_err] );
						slider.trigger( 'ms-radio-slider-updated', [data, is_err] );
						// Used for the add-on list (which is a WPMUI module)
						slider.trigger( 'wpmui-radio-slider-updated', [data, is_err] );
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
				if ( ! ( data[field_key] instanceof Array ) ) {
					data[field_key] = [ data[field_key] ];
				}
				data[field_key].push( field_value );
			}
		}
		data['action'] = 'ms_submit';

		popup = me.parents( '.wpmui-popup' );
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
				resp.width = resp.width > 0 ? resp.width : undefined;
				resp.content = resp.content || '';
				resp.modal = resp.modal || true;

				dlg = wpmUi.popup()
					.modal( true, ! resp.modal )
					.title( resp.title )
					.size( resp.width, resp.height )
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

		window.setTimeout(function() {
			jQuery( inst.dpDiv ).css( {'z-index': '10'} );
		}, 20);

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
		'input.wpmui-ajax-update:not([type=number]), select.wpmui-ajax-update, textarea.wpmui-ajax-update',
		function( ev ) { fn.ajax_update( this ); }
	)
	.on(
		'blur',
		'input.wpmui-ajax-update[type="number"]',
		function( ev ) {
			var el = jQuery( this );
			if ( el.val() !== el.data( 'val' ) ) {
				el.data( 'val', el.val() );
				fn.ajax_update( this );
			}
		}
	)
        .on(
		'change',
		'input.wpmui-ajax-update[type="number"]',
		function( ev ) {
			var el = jQuery( this );
			el.focus();
		}
	)
	.on(
		'focus',
		'input.wpmui-ajax-update[type="number"]',
		function( ev ) {
			var el = jQuery( this );
			if ( undefined === el.data( 'val' ) ) {
				el.data( 'val', el.val() );
			}
		}
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

	// Add a global CSS class to the html tag
	jQuery('html').addClass( 'ms-html' );
});

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_inline_editor:false */


/* Membership2 Inline Editor */
(function() {
	var quickedit = null,
		the_item = null,
		template = null;

	window.ms_inline_editor = {

		init: function() {
			template = jQuery( '.ms-wrap #inline-edit' );

			/*
			 * Remove the form-template from the DOM so it does not mess with
			 * the containing form...
			 */
			template.detach();

			// prepare the edit rows
			template.keyup(function(e){
				if ( e.which === 27 ) {
					return ms_inline_editor.revert();
				}
			});

			jQuery( 'a.cancel', template ).click(function(){
				return ms_inline_editor.revert();
			});
			jQuery( 'a.save', template ).click(function(){
				return ms_inline_editor.save( this );
			});
			jQuery( 'td', template ).keydown(function(e){
				if ( e.which === 13 ) {
					return ms_inline_editor.save( this );
				}
			});

			// add events
			jQuery( '.ms-wrap .wp-list-table' ).on('click', 'a.editinline', function() {
				ms_inline_editor.edit( this );
				return false;
			});
		},

		edit: function( id ) {
			var item_data, ind, field_input, field_value, row_data;

			ms_inline_editor.revert();

			if ( typeof( id ) === 'object' ) {
				id = ms_inline_editor.get_id( id );
			}

			// add the new blank row
			quickedit = template.clone( true );
			the_item = jQuery( '#item-' + id );

			jQuery( 'td', quickedit ).attr(
				'colspan',
				jQuery('.widefat:first thead th:visible').length
			);

			if ( the_item.hasClass( 'alternate' ) ) {
				quickedit.addClass('alternate');
			}
			the_item.hide().after( quickedit );

			// populate the data
			row_data = {};
			item_data = the_item.find( '.inline_data' );
			item_data.children().each(function() {
				var field = jQuery( this ),
					inp_name = field.attr( 'class' ),
					input = quickedit.find( ':input[name="' + inp_name + '"]' ),
					label = quickedit.find( '.lbl-' + inp_name );

				row_data[inp_name] = field.text();
				if ( input.length ) {
					input.val( row_data[inp_name] );
				}
				if ( label.length ) {
					label.text( row_data[inp_name] );
				}
			});
			jQuery( document ).trigger( 'ms-inline-editor', [quickedit, the_item, row_data] );

			quickedit.attr( 'id', 'edit-' + id ).addClass( 'inline-editor' ).show();
			quickedit.find( ':input:visible' ).first().focus();

			return false;
		},

		save: function( id ) {
			var params, fields;

			if ( typeof( id ) === 'object' ) {
				id = ms_inline_editor.get_id( id );
			}

			quickedit.find('td').addClass( 'wpmui-loading' );
			params = quickedit.find( ':input' ).serialize();

			// make ajax request
			jQuery.post(
				window.ajaxurl,
				params,
				function( response ) {
					quickedit.find('td').removeClass( 'wpmui-loading' );

					if ( response ) {
						if ( -1 !== response.indexOf( '<tr' ) ) {
							the_item.remove();
							the_item = jQuery( response );
							quickedit.before( the_item ).remove();
							the_item.hide().fadeIn();

							// Update the "alternate" class
							ms_inline_editor.update_alternate( the_item );

							jQuery( document ).trigger( 'ms-inline-editor-updated', [the_item] );
						} else {
							response = response.replace( /<.[^<>]*?>/g, '' );
							quickedit.find( '.error' ).html( response ).show();
						}
					} else {
						quickedit.find( '.error' ).html( ms_data.lang.quickedit_error ).show();
					}

					if ( the_item.prev().hasClass( 'alternate' ) ) {
						the_item.removeClass( 'alternate' );
					}
				},
				'html' // Tell jQuery that we expect HTML code as response
			);

			return false;
		},

		revert: function(){
			if ( quickedit ) {
				quickedit.remove();
				the_item.show();

				quickedit = null;
				the_item = null;
			}

			return false;
		},

		update_alternate: function( element ) {
			var ind, len, row,
				tbody = jQuery( element ).closest( 'tbody' ),
				rows = tbody.find( 'tr:visible' );

			for ( ind = 0, len = rows.length; ind < len; ind++ ) {
				row = jQuery( rows[ind] );
				if ( ind % 2 === 0 ) {
					row.addClass( 'alternate' );
				} else {
					row.removeClass( 'alternate' );
				}
			}
		},

		get_id: function( obj ) {
			var id = jQuery( obj ).closest( 'tr' ).attr( 'id' ),
				parts = id.split( '-' );

			return parts[ parts.length - 1 ];
		}
	};

	// Initialize the inline editor

	jQuery(function() {
		ms_inline_editor.init();
	});
}());

/*global window:false */
/*global document:false */
/*global ms_data:false */


/* Tooltip component */
jQuery(function init_tooltip () {
	// Hide all tooltips when user clicks anywhere outside a tooltip element.
	jQuery( document ).click(function() {
		function hide_tooltip() {
			var el = jQuery( this ),
				stamp = el.attr('timestamp'),
				parent = jQuery( '.wpmui-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();

			el.hide();

			// Move tooltip back into the DOM hierarchy
			el.appendTo( jQuery( parent ) );
		}

		// Hide multiple tooltips
		jQuery( '.wpmui-tooltip[timestamp]').each( hide_tooltip );
	});

	// Hide single tooltip when Close-Button is clicked.
	jQuery( '.wpmui-tooltip-button' ).click(function() {
		var el = jQuery( this ),
			parent = el.parents( '.wpmui-tooltip' ),
			stamp = jQuery( parent ).attr( 'timestamp' ),
			super_parent = jQuery( '.wpmui-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();

		jQuery( parent ).hide();

		// Move tooltip back into the DOM hierarchy
		jQuery( parent ).appendTo( jQuery( super_parent ) );
	});

	// Don't propagate click events inside the tooltip to the document.
	jQuery( '.wpmui-tooltip' ).click(function(e) {
		e.stopPropagation();
	});

	// Toggle a tooltip
	jQuery('.wpmui-tooltip-info').click(function( event ) {
		var parent, stamp, sibling, newpos, tooltip,
			el = jQuery( this );

		el.toggleClass( 'open' );

		if ( ! el.hasClass( 'open' ) ) {
			// HIDE
			parent = el.parents( '.wpmui-tooltip-wrapper' );
			stamp = jQuery( parent ).attr( 'timestamp' );
			sibling = jQuery( '.wpmui-tooltip[timestamp="' + stamp + '"]' ).first();

			jQuery( sibling ).hide();

			// Move tooltip back into the DOM hierarchy
			jQuery( sibling ).appendTo( jQuery( parent ) );
		} else {
			// SHOW
			el.parents('.wpmui-tooltip-wrapper').attr( 'timestamp', event.timeStamp );
			event.stopPropagation();
			tooltip = el.siblings( '.wpmui-tooltip' );

			tooltip.attr( 'timestamp', event.timeStamp );

			// Move tooltip out of the hierarchy...
			// This is to avoid situations where large tooltips are cut off by parent elements.
			newpos = el.offset();
			tooltip.appendTo( '#wpcontent' );
			tooltip.css({
				'left': newpos.left + 25,
				'top': newpos.top - 40
			});

			tooltip.fadeIn( 300 );
		}
	});

});

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.controller_adminbar = function init () {

	function change_membership( ev ) {
		// Get selected Membership ID
		var membership_id = ev.currentTarget.value;
		// Get selected Membership nonce
		var nonce = jQuery( '#wpadminbar #view-as-selector' )
			.find( 'option[value="' + membership_id + '"]' )
			.attr( 'nonce' );

		// Update hidden fields
		jQuery( '#wpadminbar #ab-membership-id' ).val( membership_id );
		jQuery( '#wpadminbar #view-site-as #_wpnonce' ).val( nonce );

		// Submit form
		jQuery( '#wpadminbar #view-site-as' ).submit();
	}

	jQuery('#wp-admin-bar-membership-simulate').find('a').click(function(e){
		jQuery('#wp-admin-bar-membership-simulate')
			.removeClass('hover')
			.find('> div')
			.filter(':first-child')
			.html( ms_data.switching_text );
	});

	jQuery( '.ms-date' ).ms_datepicker();

	jQuery( '#wpadminbar #view-site-as' )
		.parents( '#wpadminbar' )
		.addClass('simulation-mode');

	jQuery( '#wpadminbar #view-as-selector' ).change( change_membership );

};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_help = function init () {
	function toggle_section() {
		var me = jQuery( this ),
			block = me.parents( '.ms-help-box' ).first(),
			details = block.find( '.ms-help-details' );

		details.toggle();
	}

	jQuery( '.ms-help-toggle' ).click( toggle_section );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_billing_edit = function init () {
	var args = {
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': 'required',
			'user_id': {
				'required': true,
				'min': 1,
			},
			'membership_id': {
				'required': true,
				'min': 1,
			},
			'amount': {
				'required': true,
				'min': 0,
			},
			'due_date': {
				'required': true,
				'dateISO': true,
			},
		}
	};

	jQuery('.ms-form').validate(args);
};

/*global window:false */
/*global document:false */
/*global wpmUi:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_billing_transactions = function init() {
	var table = jQuery( '.wp-list-table.transactions, .wp-list-table.transaction_matches' ),
		frm_match = jQuery( '.transaction-matching' ),
		btn_clear = table.find( '.action-clear' ),
		btn_ignore = table.find( '.action-ignore' ),
		btn_link = table.find( '.action-link' ),
		btn_retry = table.find( '.action-retry' ),
		btn_match = frm_match.find( '.action-match' ),
		retry_transactions, show_link_dialog, append_option;

	// Handle the "Save Matching" action.
	function save_matching( ev ) {
		var ajax = wpmUi.ajax(),
			data = ajax.extract_data( frm_match );

		frm_match.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				if ( response.success ) {
					wpmUi.message( response.data.message );

					// Start to process the transactions.
					retry_transactions();
				}
			},
			'json'
		).always(function() {
			frm_match.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Retry to process all displayed transactions.
	retry_transactions = function() {
		var rows = table.find( '.item' ),
			nonce = frm_match.find( '.retry_nonce' ).val(),
			progress = wpmUi.progressbar(),
			counter = 0,
			ajax_data = {},
			queue = [];

		ajax_data.action = 'transaction_retry';
		ajax_data._wpnonce = nonce;

		// Collect all log-IDs in the queue.
		rows.each(function() {
			var row = jQuery( this ),
				row_id = row.attr( 'id' ).replace( /^item-/, '' );

			row.find( '.column-note' ).addClass( 'wpmui-loading' );
			queue.push( row_id );
		});

		progress.value( 0 );
		progress.max( queue.length );
		progress.$().insertBefore( frm_match );
		frm_match.hide();

		// Process the queue.
		function process_queue() {
			if ( ! queue.length ) {
				progress.$().remove();
				return;
			}

			var id = queue.shift(),
				data = jQuery.extend( {}, ajax_data ),
				row = table.find( '#item-' + id );

			data.id = id;
			counter += 1;
			progress.value( counter );

			jQuery.post(
				window.ajaxurl,
				data,
				function(response) {
					if ( response.success && response.data.desc ) {
						row.removeClass( 'log-err log-ignore log-ok' );
						row.addClass( 'log-' + response.data.state );
						row.find( '.column-note .txt' ).text( response.data.desc );
					}

					window.setTimeout( function() { process_queue(); }, 1 );
				},
				'json'
			).always(function() {
				row.find( '.column-note' ).removeClass( 'wpmui-loading' );
			});
		}

		process_queue();
	};

	// Handle the "Reset" action.
	function clear_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_update]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-ignore' ) ) { return false; }

		data.action = 'transaction_update';
		data._wpnonce = nonce;
		data.id = row_id;
		data.state = 'clear';

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				row.removeClass( 'log-ignore is-manual' ).addClass( 'log-err' );
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Handle the "Ignore" action.
	function ignore_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_update]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-err' ) ) { return false; }

		data.action = 'transaction_update';
		data._wpnonce = nonce;
		data.id = row_id;
		data.state = 'ignore';

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				row.removeClass( 'log-err' ).addClass( 'log-ignore is-manual' );
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Handle the "Retry" action.
	function retry_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_retry]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-err' ) && ! row.hasClass( 'log-ignore' ) ) { return false; }

		data.action = 'transaction_retry';
		data._wpnonce = nonce;
		data.id = row_id;

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				if ( response.success && response.data.desc ) {
					row.removeClass( 'log-err log-ignore log-ok' );
					row.addClass( 'log-' + response.data.state );
					row.find( '.column-note .txt' ).text( response.data.desc );
				}
			},
			'json'
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Handle the "Link" action.
	function link_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_link]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-err' ) ) { return false; }

		data.action = 'transaction_link';
		data._wpnonce = nonce;
		data.id = row_id;

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				if ( response.length ) {
					show_link_dialog( row, response );
				}
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Display the Transaction-Link popup.
	show_link_dialog = function( row, data ) {
		var sel_user, sel_subscription, sel_invoice, nonce_data, nonce_update,
			row_user, row_subscription, row_invoice, btn_submit, log_id,
			popup = wpmUi.popup(),
			wnd = popup.$();

		popup.modal( true );
		popup.title( ms_data.lang.link_title );
		popup.content( data );
		popup.show();

		// Add event handlers inside the popup.
		sel_user = wnd.find( 'select[name=user_id]' );
		sel_subscription = wnd.find( 'select[name=subscription_id]' );
		sel_invoice = wnd.find( 'select[name=invoice_id]' );
		row_user = wnd.find( '.link-member' );
		row_subscription = wnd.find( '.link-subscription' );
		row_invoice = wnd.find( '.link-invoice' );
		nonce_data = wnd.find( 'input[name=nonce_link_data]' );
		nonce_update = wnd.find( 'input[name=nonce_update]' );
		log_id = wnd.find( 'input[name=log_id]' );
		btn_submit = wnd.find( 'button[name=submit]' );

		row_subscription.hide();
		row_invoice.hide();
		btn_submit.prop( 'disabled', true ).addClass( 'disabled' );

		function load_subscriptions() {
			var data,
				user_id = sel_user.val();

			if ( isNaN( user_id ) || user_id < 1 ) {
				row_invoice.find( '.wpmui-label-after' ).hide();
				row_subscription.hide();
				row_invoice.hide();
				return false;
			}

			data = {
				'action': 'transaction_link_data',
				'_wpnonce': nonce_data.val(),
				'get': 'subscriptions',
				'for': user_id
			};

			sel_subscription.empty();
			sel_invoice.empty();
			row_subscription.show().addClass( 'wpmui-loading' );
			row_invoice.find( '.wpmui-label-after' ).hide();
			row_invoice.hide();
			btn_submit.prop( 'disabled', true ).addClass( 'disabled' );

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					jQuery.each( response, function( val, label ) {
						append_option( sel_subscription, val, label );
					});
				},
				'json'
			).always(function() {
				row_subscription.removeClass( 'wpmui-loading' );
			});
		}

		function load_invoices() {
			var data,
				subscription_id = sel_subscription.val();

			if ( isNaN( subscription_id ) || subscription_id < 1 ) {
				row_invoice.find( '.wpmui-label-after' ).hide();
				row_invoice.hide();
				return false;
			}

			data = {
				'action': 'transaction_link_data',
				'_wpnonce': nonce_data.val(),
				'get': 'invoices',
				'for': subscription_id
			};

			sel_invoice.empty();
			row_invoice.show().addClass( 'wpmui-loading' );
			row_invoice.find( '.wpmui-label-after' ).hide();
			btn_submit.prop( 'disabled', true ).addClass( 'disabled' );

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					window.console.log( response );
					jQuery.each( response, function( val, label ) {

					window.console.log( val, label );
						append_option( sel_invoice, val, label );
					});
				},
				'json'
			).always(function() {
				row_invoice.removeClass( 'wpmui-loading' );
			});
		}

		function confirm_data() {
			var inv_id = sel_invoice.val();

			if ( ! isNaN( inv_id ) && inv_id > 0 ) {
				row_invoice.find( '.wpmui-label-after' ).show();
				btn_submit.prop( 'disabled', false ).removeClass( 'disabled' );
			} else {
				row_invoice.find( '.wpmui-label-after' ).hide();
				btn_submit.prop( 'disabled', true ).addClass( 'disabled' );
			}
		}

		function save_link() {
			var data = {
				'action': 'transaction_update',
				'_wpnonce': nonce_update.val(),
				'id': log_id.val(),
				'link': sel_invoice.val()
			};

			if ( ! data.link ) { return false; }
			wnd.addClass( 'wpmui-loading' );

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					if ( '3' === response ) {
						row.removeClass( 'log-err' ).addClass( 'log-ok is-manual' );
						popup.close();
					}
				}
			).always(function() {
				wnd.removeClass( 'wpmui-loading' );
			});
		}

		sel_user.change( load_subscriptions );
		sel_subscription.change( load_invoices );
		sel_invoice.change( confirm_data );
		btn_submit.click( save_link );

		if ( ! isNaN( sel_user.val() ) && sel_user.val() > 0 ) {
			load_subscriptions();
		}
	};

	append_option = function( container, val, label ) {
		if ( typeof label === 'object' ) {
			var group = jQuery( '<optgroup></optgroup>' );
			group.attr( 'label', val );
			jQuery.each( label, function( subval, sublabel ) {
				append_option( group, subval, sublabel );
			});
			container.append( group );
		} else {
			container.append(
				jQuery( '<option></option>' )
				.val( val )
				.html( label )
			);
		}
	};

	btn_clear.click(clear_line);
	btn_ignore.click(ignore_line);
	btn_link.click(link_line);
	btn_retry.click(retry_line);
	btn_match.click(save_matching);
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_date = function init () {
	jQuery( '.ms-date' ).ms_datepicker();
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_editor = function init () {
	var txt_username = jQuery( '#username' ),
		txt_email = jQuery( '#email' ),
		sel_user = jQuery( '.ms-group-select #user_id' ),
		btn_add = jQuery( '#btn_create' ),
		btn_select = jQuery( '#btn_select' ),
		chosen_options = {},
		validate_buttons;

	function validate_field( fieldname, field ) {
		var value = field.val(),
			data = {},
			row = field.closest( '.wpmui-wrapper' );

		data.action = 'member_validate_field';
		data.field = fieldname;
		data.value = value;

		row.addClass( 'wpmui-loading' );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var info = row.find( '.wpmui-label-after' );
				row.removeClass( 'wpmui-loading' );

				if ( '1' === response ) {
					field.removeClass( 'invalid' );
					field.addClass( 'valid' );
					info.html( '' );
				} else {
					field.removeClass( 'valid' );
					field.addClass( 'invalid' );
					info.html( response );
				}

				validate_buttons();
			}
		);
	}

	validate_buttons = function() {
		if ( txt_username.hasClass( 'valid' ) && txt_email.hasClass( 'valid' ) ) {
			btn_add.prop( 'disabled', false );
			btn_add.removeClass( 'disabled' );
		} else {
			btn_add.prop( 'disabled', true );
			btn_add.addClass( 'disabled' );
		}

		if ( sel_user.val() ) {
			btn_select.prop( 'disabled', false );
			btn_select.removeClass( 'disabled' );
		} else {
			btn_select.prop( 'disabled', true );
			btn_select.addClass( 'disabled' );
		}
	};

	txt_username.change(function() {
		validate_field( 'username', txt_username );
	});

	txt_email.change(function() {
		validate_field( 'email', txt_email );
	});

	sel_user.change(validate_buttons);

	chosen_options.minimumInputLength = 3;
	chosen_options.multiple = false;
	chosen_options.ajax = {
		url: window.ajaxurl,
		dataType: "json",
		type: "GET",
		delay: 100,
		data: function( params ) {
			return {
				action: "member_search",
				q: params.term,
				p: params.page
			};
		},
		processResults: function( data, page ) {
			return { results: data.items, more: data.more };
		}
	};

	sel_user.removeClass( 'wpmui-hidden' );
	sel_user.wpmuiSelect( chosen_options );

	validate_buttons();
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_list = function init () {
	window.ms_init.memberships_column( '.column-membership' );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_add = function init () {
	var chk_public = jQuery( 'input#public' ),
		el_public = chk_public.closest( '.opt' ),
		chk_paid = jQuery( 'input#paid' ),
		inp_name = jQuery( 'input#name' ),
		el_name = inp_name.closest( '.opt' ),
		el_paid = chk_paid.closest( '.opt' );

	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	// Lock the options then guest membership is selected.
	jQuery( 'input[name="type"]' ).click(function() {
		var types = jQuery( 'input[name="type"]' ),
			current = types.filter( ':checked' ),
			cur_type = current.val();

		types.closest( '.wpmui-radio-input-wrapper' ).removeClass( 'active' );
		current.closest( '.wpmui-radio-input-wrapper' ).addClass( 'active' );

		if ( 'guest' === cur_type || 'user' === cur_type ) {
			chk_public.prop( 'disabled', true );
			chk_paid.prop( 'disabled', true );
			inp_name.prop( 'readonly', true );
			el_public.addClass( 'disabled ms-locked' );
			el_paid.addClass( 'disabled ms-locked' );
			el_name.addClass( 'disabled ms-locked' );
			inp_name.val( current.next( '.wpmui-radio-caption' ).text() );
		} else {
			chk_public.prop( 'disabled', false );
			chk_paid.prop( 'disabled', false );
			inp_name.prop( 'readonly', false );
			el_public.removeClass( 'disabled ms-locked' );
			el_paid.removeClass( 'disabled ms-locked' );
			el_name.removeClass( 'disabled ms-locked' );
			inp_name.val( '' ).focus();
		}
	}).filter( ':checked' ).trigger( 'click' );

	// Cancel the wizard.
	jQuery( '#cancel' ).click( function() {
		var me = jQuery( this );

		// Simply reload the page after the setting has been changed.
		me.on( 'ms-ajax-updated', function() {
			window.location = ms_data.initial_url;
		} );
		ms_functions.ajax_update( me );
	});
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_membership_list = function init () {
	var table = jQuery( '#the-list-membership' );

	function confirm_delete( ev ) {
		var args,
			me = jQuery( this ),
			row = me.parents( 'tr' ),
			name = row.find( '.column-name .the-name' ).text(),
			delete_url = me.attr( 'href' );

		ev.preventDefault();
		args = {
			message: ms_data.lang.msg_delete.replace( '%s', name ),
			buttons: [
				ms_data.lang.btn_delete,
				ms_data.lang.btn_cancel
			],
			callback: function( key ) {
				if ( key === 0 ) {
					window.location = delete_url;
				}
			}
		};
		wpmUi.confirm( args );

		return false;
	}

	table.on( 'click', '.delete a', confirm_delete );

	// Triggered after any Membership details were modified via the edit popup.
	jQuery( document ).on( 'ms-ajax-form-done', function( ev, form, response, is_err, data ) {
		if ( ! is_err ) {
			// reload the page to reflect the update
			window.location.reload();
		}
	});
};

window.ms_init.bulk_delete_membership = function() {
    
    var delete_url = jQuery( '.bulk_delete_memberships_button' ).attr( 'href' );
    
    var serealize_membership_ids = function() {
        
        var membership_ids = [];
        jQuery( 'input.del_membership_ids:checked' ).each( function() {
            membership_ids.push( jQuery( this ).val() );
        } );
        
        if( membership_ids.length > 0 ){
            return delete_url + '&membership_ids=' + membership_ids.join( '-' );
        }else{
            return delete_url;
        }
        
    };
    
    function confirm_bulk_delete( ev ) {
            var args,
                    me = jQuery( this ),
                    row = me.parents( 'tr' ),
                    delete_url = me.attr( 'href' );

            ev.preventDefault();
            args = {
                    message: ms_data.lang.msg_bulk_delete,
                    buttons: [
                            ms_data.lang.btn_delete,
                            ms_data.lang.btn_cancel
                    ],
                    callback: function( key ) {
                            if ( key === 0 ) {
                                    window.location = serealize_membership_ids();
                            }
                    }
            };
            wpmUi.confirm( args );

            return false;
    }
    
    jQuery( '.bulk_delete_memberships_button' ).click( confirm_bulk_delete );
        
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.metabox = function init() {
	var radio_protection = jQuery( '.ms-protect-content' ),
		radio_rule = jQuery( '.ms-protection-rule' ),
		box_access = jQuery( '#ms-metabox-access-wrapper' );

	if ( radio_protection.hasClass( 'on' ) ) {
		box_access.show();
	} else {
		box_access.hide();
	}

	jQuery( '.dripped' ).click( function() {
		var tooltip = jQuery( this ).children( '.tooltip' );
		tooltip.toggle( 300 );
	} );

	// Callback after the base protection setting was changed.
	window.ms_init.ms_metabox_event = function( event, data ) {
		jQuery( '#ms-metabox-wrapper' ).replaceWith( data.response );
		window.ms_init.metabox();

		jQuery( '.wpmui-radio-slider' ).click( function() {
			window.ms_functions.radio_slider_ajax_update( this );
		} );

		radio_protection.on( 'ms-radio-slider-updated', function( event, data ) {
			window.ms_init.ms_metabox_event( event, data );
		} );
	};

	// Callback after a membership protection setting was changed.
	function rule_updated( event, data ) {
		var active = radio_rule.filter('.on,.wpmui-loading').length;

		if ( active ) {
			box_access.show();
			radio_protection.addClass( 'on' );
		} else {
			box_access.hide();
			radio_protection.removeClass( 'on' );
		}
	}

	radio_protection.on( 'ms-radio-slider-updated', function( event, data ) {
		window.ms_init.ms_metabox_event( event, data );
	});
	radio_rule.on( 'ms-radio-slider-updated', function( event, data ) {
		rule_updated( event, data );
	});
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_overview = function init () {
	jQuery( '.wpmui-radio-slider' ).on( 'ms-radio-slider-updated', function() {
		var object = this,
			obj = jQuery( '#ms-membership-status' );

		if( jQuery( object ).hasClass( 'on' ) ) {
			obj.addClass( 'ms-active' );
		} else {
			obj.removeClass( 'ms-active' );
		}
	});

	// Triggered after the Membership details were modified via the edit popup.
	jQuery( document ).on( 'ms-ajax-form-done', function( ev, form, response, is_err, data ) {
		if ( ! is_err ) {
			// reload the page to reflect the update
			window.location.reload();
		}
	});
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_urlgroup = function init () {
	var timeout = false;

	//global functions defined in ms-functions.js
	ms_functions.test_url = function() {
		if ( timeout ) {
			window.clearTimeout( timeout );
		}

		timeout = window.setTimeout(function() {
			var container = jQuery( '#url-test-results-wrapper' ),
				url = jQuery.trim(jQuery( '#url_test' ).val() ),
				rules = jQuery( '#rule_value' ).val().split( "\n" ),
				is_regex = jQuery( '#is_regex' ).val();

			if ( is_regex === 'true' || is_regex === '1' ) {
				is_regex = true;
			} else {
				is_regex = false;
			}

			container.empty().hide();

			if ( url === '' ) {
				return;
			}

			jQuery.each( rules, function( i, rule ) {
				var line, result, ruleurl, reg, match;

				rule = jQuery.trim(rule);
				if ( rule === '' ) {
					return;
				}

				line = jQuery( '<div />' ).addClass( 'ms-rule-test' );
				ruleurl = jQuery( '<span />' ).appendTo( line ).text( rule ).addClass( 'ms-test-url' );
				result = jQuery( '<span />' ).appendTo( line ).addClass( 'ms-test-result' );

				match = false;
				if ( is_regex ) {
					reg = new RegExp( rule, 'i' );
					match = reg.test( url );
				} else {
					match = url.indexOf( rule ) >= 0;
				}

				if ( match ) {
					line.addClass( 'ms-rule-valid' );
					result.text( ms_data.valid_rule_msg );
				} else {
					line.addClass( 'ms-rule-invalid' );
					result.text( ms_data.invalid_rule_msg );
				}

				container.append( line );
			});

			if ( ! container.find( '> div' ).length ) {
				container.html( '<div><i>' + ms_data.empty_msg + '</i></div>' );
			}

			container.show();
		}, 500);
	};

	jQuery( '#url_test, #rule_value' ).keyup( ms_functions.test_url );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_payment = function init () {

	function payment_type() {
		var me = jQuery( this ),
			block = me.closest( '.ms-payment-form' ),
			pay_type = me.val(),
			all_settings = block.find( '.ms-payment-type-wrapper' ),
			active_settings = block.find( '.ms-payment-type-' + pay_type ),
			pay_types_block = block.find( '.ms-payment-types-wrapper' );

		all_settings.hide();
		active_settings.show();

		if ( 'permanent' === pay_type ) {
			pay_types_block.hide();
		} else {
			pay_types_block.show();
		}
	}

	function show_currency() {
		var currency = jQuery( this ).val(),
			items = jQuery( '.ms-payment-structure-wrapper' );

		// Same translation table in:
		// -> class-ms-model-settings.php
		switch ( currency ) {
			case 'USD': currency = '$'; break;
			case 'EUR': currency = '&euro;'; break;
			case 'JPY': currency = '&yen;'; break;
		}

		items.find( '.wpmui-label-before' ).html( currency );
	}

	function toggle_trial( ev, data, is_err ) {
		if ( data.value ) {
			jQuery( '.ms-trial-period-details' ).show();
		} else {
			jQuery( '.ms-trial-period-details' ).hide();
		}
	}

	function reload_page( ev, data, response, is_err ) {
		if ( ! is_err ) {
			jQuery( '.ms-specific-payment-wrapper' ).addClass( 'wpmui-loading' );
			window.location.reload();
		}
	}

	// Show the correct payment options
	jQuery( '#payment_type' ).change( payment_type );
	jQuery( '#payment_type' ).each( payment_type );

	// Update currency symbols in payment descriptions.
	jQuery( '#currency' ).change( show_currency );

	jQuery( '.wpmui-slider-trial_period_enabled' ).on( 'ms-radio-slider-updated', toggle_trial );
	jQuery(document).on( 'ms-ajax-updated', '#enable_trial_addon', reload_page );
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_protected_content = function init () {
	var table = jQuery( '.wp-list-table' ),
		sel_network_site = jQuery( '#select-site' ),
		setup_editor;

	window.ms_init.memberships_column( '.column-access' );

	// After a membership was added or removed. Check if there are dripped memberships.
	function check_if_dripped( ev ) {
		var ind, membership_id,
			cell = jQuery( this ).closest( '.column-access' ),
			row = cell.closest( 'tr.item' ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.val(),
			num_dripped = 0;

		if ( memberships && memberships.length ) {
			for ( ind in memberships ) {
				membership_id = memberships[ind];
				if ( undefined !== ms_data.dripped[membership_id] ) {
					num_dripped += 1;
				}
			}
		}

		if ( num_dripped > 1 ) {
			// Multiple dripped memberships. Inline editor required.
			row.addClass( 'ms-dripped' );
		} else if ( num_dripped === 1 ) {
			// Single dripped membership. No inline editor required.
			row.addClass( 'ms-dripped' );
		} else {
			row.removeClass( 'ms-dripped' );
		}
	}

	// Right before the inline editor is displayed. We can prepare the form.
	function populate_inline_editor( ev, editor, row, item_data ) {
		var ind, len,
			memberships = row.find( 'select.ms-memberships option:selected' ),
			form = editor.find( '.dripped-form' ),
			target = editor.find( '.dynamic-form' );

		for ( ind = 0, len = memberships.length; ind < len; ind++ ) {
			var item = jQuery( memberships[ind] ),
				membership_id = item.val(),
				color = item.data( 'color' ),
				form_row = form.clone( false ),
				base = 'ms_' + membership_id;

			if ( undefined !== ms_data.dripped[membership_id] ) {
				// Create input fields for the dripped membership
				form_row.find( '.the-name' )
					.text( ms_data.dripped[membership_id] )
					.css( {'background': color} );

				form_row.find( '[name=membership_ids]' )
					.attr( 'name', 'membership_ids[]' )
					.val( membership_id );

				form_row.find( '[name=item_id]' )
					.val( item_data.item_id );

				form_row.find( '[name=offset]' )
					.val( item_data.offset );

				form_row.find( '[name=number]' )
					.val( item_data.number );

				form_row.find( '[name=dripped_type]' )
					.attr( 'name', base + '[dripped_type]' )
					.val( item_data[ base + '[dripped_type]' ] );

				form_row.find( '[name=date]' )
					.attr( 'name', base + '[date]' )
					.val( item_data[ base + '[date]' ] );

				form_row.find( '[name=delay_unit]' )
					.attr( 'name', base + '[delay_unit]' )
					.val( item_data[ base + '[delay_unit]' ] );

				form_row.find( '[name=delay_type]' )
					.attr( 'name', base + '[delay_type]' )
					.val( item_data[ base + '[delay_type]' ] );

				// Add the membership form to the inline editor
				form_row.appendTo( target ).removeClass( 'hidden' );

				setup_editor( form_row );
			}
		}

		// Remove the form-template from the inline editor.
		form.remove();
	}

	// Set up the event-handlers of the inline editor.
	setup_editor = function( form ) {
		var sel_type = form.find( 'select.dripped_type' ),
			inp_date = form.find( '.wpmui-datepicker' );

		// Type-selection
		sel_type.change(function() {
			var me = jQuery( this ),
				val = me.val(),
				types = me.closest( '.dripped-form' ).find( '.drip-option' );

			types.removeClass( 'active' );
			types.filter( '.' + val ).addClass( 'active' );
		}).trigger( 'change' );

		// Datepicker
		inp_date.ms_datepicker();
	};

	// The table was updated, at least one row needs to be re-initalized.
	function update_table( ev, row ) {
		window.ms_init.memberships_column( '.column-access' );

		row.find( '.ms-memberships' ).each(function() {
			check_if_dripped.apply( this );
		});
	}

	// Network-wide protection
	function refresh_site_data( ev ) {
		var url = sel_network_site.val();

		window.location.href = url;
		sel_network_site.addClass( 'wpmui-loading' );
	}

	// Add event hooks.

	table.on( 'ms-ajax-updated', '.ms-memberships', check_if_dripped );
	table.find( '.ms-memberships' ).each(function() {
		check_if_dripped.apply( this );
	});

	jQuery( document ).on( 'ms-inline-editor', populate_inline_editor );
	jQuery( document ).on( 'ms-inline-editor-updated', update_table );

	sel_network_site.on( 'change', refresh_site_data );
};


// -----------------------------------------------------------------------------


// This is also used on the Members page
window.ms_init.memberships_column = function init_column( column_class ) {
	var table = jQuery( '.wp-list-table' );

	// Change the table row to "protected"
	function protect_item( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			row = cell.closest( 'tr.item' ),
			inp = cell.find( 'select.ms-memberships' );

		row.removeClass( 'ms-empty' )
			.addClass( 'ms-assigned' );

		cell.addClass( 'ms-focused' );

		inp.wpmuiSelect( 'focus' );
		inp.wpmuiSelect( 'open' );
	}

	// If the item is not protected by any membership it will chagne to public
	function maybe_make_public( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			row = cell.closest( 'tr.item' ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.val();

		cell.removeClass( 'ms-focused' );

		if ( memberships && memberships.length ) { return; }
		row.removeClass( 'ms-assigned' ).addClass( 'ms-empty' );
	}

	// Format the memberships in the dropdown list (= unselected items)
	function format_result( state ) {
		var attr,
			original_option = state.element;

		attr = 'class="val" style="background: ' + jQuery( original_option ).data( 'color' ) + '"';
		return '<span ' + attr + '>&emsp;</span> ' + state.text;
	}

	// Format the memberships in the tag list (= selected items)
	function format_tag( state, container ) {
		var attr,
			original_option = state.element;

		container.css({ background: jQuery( original_option ).data( 'color' ) });
		container.addClass( 'val' );

		return '<span class="txt">' + state.text + '</span>';
	}

	// add hooks

	table.on( 'click', '.ms-empty-note-wrapper .wpmui-label-after', protect_item );

	table.on( 'ms-ajax-updated', '.ms-memberships', maybe_make_public );
	table.on( 'blur', '.ms-memberships', function( ev ) {
		var me = jQuery( this );
		// We need a delay here to allow select2 to forward the selection to us.
		window.setTimeout(
			function() { maybe_make_public.apply( me, ev ); },
			250
		);
	});

	jQuery( 'select.ms-memberships' ).wpmuiSelect({
		templateResult: format_result,
		templateSelection: format_tag,
		escapeMarkup: function( m ) { return m; },
		dropdownCssClass: 'ms-memberships wpmui-select2',
		width: '100%'
	});
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

//
// JS for the Membership > Edit > Upgrade Paths page.
//
window.ms_init.view_membership_upgrade = function init () {
	var slider_allow = jQuery( '.ms-allow .wpmui-radio-slider' );

	function slider_updated() {
		var me = jQuery( this ),
			denied = me.hasClass( 'on' ),
			row = me.closest( '.ms-allow' ),
			upd_replace = row.next( '.ms-update-replace' );

		if ( ! upd_replace.length ) { return; }

		if ( denied ) {
			upd_replace.hide();
		} else {
			upd_replace.show();
		}
	}

	slider_allow.on( 'ms-radio-slider-updated', slider_updated );

	slider_allow.each(function() {
		slider_updated.apply( this );
	});
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_addons = function init () {

	var list = jQuery( '.ms-addon-list' );

	// Apply the custom list-filters
	function filter_addons( event, filter, items ) {
		switch ( filter ) {
			case 'options':
				items.hide().filter( '.ms-options' ).show();
				break;
		}
	}

	// Show an overlay when ajax update starts (prevent multiple ajax calls at once!)
	function ajax_start( event, data, status, animation ) {
		animation.removeClass( 'wpmui-loading' );
		list.addClass( 'wpmui-loading' );
	}

	// Remove the overlay after ajax update is done
	function ajax_done( event, data, status, animation ) {
		list.removeClass( 'wpmui-loading' );
	}

	// After an add-on was activated or deactivated
	function addon_toggle( event ) {
		var el = jQuery( event.target ),
			card = el.closest( '.list-card-top' ),
			details = card.find( '.details' ),
			fields = details.find( '.wpmui-ajax-update-wrapper' );

		if ( ! el.hasClass( 'wpmui-ajax-update' ) ) {
			el = el.find( '.wpmui-ajax-update' );
		}

		if ( el.closest( '.details' ).length ) { return; } // A detail setting was updated; add-on status was not changed...

		if ( el.hasClass( 'on' ) ) {
			fields.removeClass( 'disabled' );
		} else {
			fields.addClass( 'disabled' );
		}
	}

	jQuery( document ).on( 'list-filter', filter_addons );
	jQuery( document ).on( 'ms-ajax-start', ajax_start );
	jQuery( document ).on( 'ms-ajax-updated', addon_toggle );
	jQuery( document ).on( 'ms-ajax-done', ajax_done );

	jQuery( '.list-card-top .wpmui-ajax-update-wrapper' ).each(function() {
		jQuery( this ).trigger( 'ms-ajax-updated' );
	});
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings = function init () {
	function page_changed( event, data, response, is_err ) {
		var lists = jQuery( 'select.wpmui-wp-pages' ),
			cur_pages = lists.map(function() { return jQuery(this).val(); });

		lists.each(function() {
			var ind,
				me = jQuery( this ),
				options = me.find( 'option' ),
				row = me.parents( '.ms-settings-page-wrapper' ).first(),
				actions = row.find( '.ms-action a' ),
				val = me.val();

			// Disable the pages that are used already.
			options.prop( 'disabled', false );
			for ( ind = 0; ind < cur_pages.length; ind += 1 ) {
				if ( val === cur_pages[ind] ) { continue; }
				options.filter( '[value="' + cur_pages[ind] + '"]' )
					.prop( 'disabled', true );
			}

			// Update the view/edit links
			actions.each(function() {
				var link = jQuery( this ),
					data = link.data( 'wpmui-ajax' ),
					url = data.base + val;

				if ( undefined === val || isNaN(val) || val < 1 ) {
					link.addClass( 'disabled' );
					link.attr( 'href', '' );
				} else {
					link.removeClass( 'disabled' );
					link.attr( 'href', url );
				}
			});
		});
	}

	function ignore_disabled( ev ) {
		var me = jQuery( this );

		if ( me.hasClass( 'disabled' ) || ! me.attr( 'href' ).length ) {
			ev.preventDefault();
			return false;
		}
	}

	function reload_window() {
		window.location = ms_data.initial_url;
	}

	function update_toolbar( ev, data ) {
		// Show/Hide the Toolbar menu for Membership2.
		if ( data.value ) {
			jQuery( '#wp-admin-bar-ms-unprotected' ).hide();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).show();
		} else {
			jQuery( '#wp-admin-bar-ms-unprotected' ).show();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).hide();
		}
	}

	function hide_footer( ev, data ) {
		// Show/Hide the footer for Membership2.
		if ( !data.value ) {
			jQuery( '.ms-settings-email-cron' ).hide();
		} else {
			jQuery( '.ms-settings-email-cron' ).show();
		}
		var ajax_data = jQuery( '.wpmui-slider-enable_cron_use .wpmui-toggle').attr('data-wpmui-ajax');
		ajax_data = JSON.parse(ajax_data);
		jQuery.post(window.ajaxurl,{'action' : 'toggle_cron', '_wpnonce' : ajax_data._wpnonce }, function(){});
	}

	// Reload the page when Wizard mode is activated.
	jQuery( '#initial_setup' ).on( 'ms-ajax-updated', reload_window );

	// Hide/Show the "Test Membership" button in the toolbar.
	jQuery( '.wpmui-slider-plugin_enabled').on( 'ms-radio-slider-updated', update_toolbar );
	//Hide/Show footer when the cron is enabled or disabled
	jQuery( '.wpmui-slider-enable_cron_use').on( 'ms-radio-slider-updated', hide_footer );

	// Membership Pages: Update contents after a page was saved
	jQuery( '.wpmui-wp-pages' ).on( 'ms-ajax-updated', page_changed );
	jQuery( '.ms-action a' ).on( 'click', ignore_disabled );
	jQuery(function() { page_changed(); });
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_automated_msg = function init () {
	var is_dirty = false;

	function change_comm_type() {
		var me = jQuery( this ),
			form = me.closest( 'form' ),
			ind = 0;

		for ( ind = 0; ind < window.tinymce.editors.length; ind += 1 ) {
			if ( window.tinymce.editors[ind].isDirty() ) { is_dirty = true; break; }
		}

		if ( is_dirty ) {
			if ( ! window.confirm( ms_data.lang_confirm ) ) {
				return false;
			}
		}

		form.submit();
	}

	function make_dirty() {
		is_dirty = true;
	}

	function toggle_override() {
		var toggle = jQuery( this ),
			block = toggle.closest( '.ms-settings-wrapper' ),
			form = block.find( '.ms-editor-form' );

		if ( toggle.hasClass( 'on' ) ) {
			form.show();
		} else {
			form.hide();
		}
	}

	jQuery( '#switch_comm_type' ).click( change_comm_type );
	jQuery( 'input, select, textarea', '.ms-editor-form' ).change( make_dirty );
	jQuery( '.override-slider' )
		.each(function() { toggle_override.apply( this ); })
		.on( 'ms-ajax-done', toggle_override );

	/**
	 * Add the javascript for our custom TinyMCE button
	 *
	 * @see class-ms-controller-settings.php (function add_mce_buttons)
	 * @see class-ms-view-settings-edit.php (function render_tab_messages_automated)
	 */
	window.tinymce.PluginManager.add(
		'ms_variable',
		function membership2_variables( editor, url ) {
			var key, item, items = [];

			// This function inserts the variable to the current cursor position.
			function insert_variable() {
				editor.insertContent( this.value() );
			}
			// Build the list of available variabled (defined in the view!)
			for ( key in ms_data.var_button.items ) {
				if ( ! ms_data.var_button.items.hasOwnProperty( key ) ) {
					continue;
				}

				item = ms_data.var_button.items[key];
				items.push({
					text: item,
					value: key,
					onclick: insert_variable
				});
			}

			// Add the custom button to the editor.
			editor.addButton( 'ms_variable', {
				text: ms_data.var_button.title,
				icon: false,
				type: 'menubutton',
				menu: items
			});
		}
	);
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_settings_import = function init() {

	var form_import = jQuery( '.ms-settings-import' ),
		btn_download = form_import.find( '#btn-download' ),
		btn_import = form_import.find( '#btn-import' ),
		chk_clear = form_import.find( '#clear_all' ),
		sel_batchsize = form_import.find( '#batchsize' ),
		the_popup = null,
		the_progress = null,
		queue = [],
		queue_count = 0;

	/**
	 * Checks if the browser supports downloading js-created files.
	 */
	function support_download() {
		var a = document.createElement( 'a' );
		if ( undefined === a.download ) { return false; }
		if ( undefined === window.Blob ) { return false; }
		if ( undefined === window.JSON ) { return false; }
		if ( undefined === window.JSON.stringify ) { return false; }

		return true;
	}

	/**
	 * Tries to provide the specified data as a file-download.
	 */
	function download( content, filename, contentType ) {
		var a, blob;
		if ( ! support_download() ) { return false; }

		if ( ! contentType ) { contentType = 'application/octet-stream'; }
		a = document.createElement( 'a' );
		blob = new window.Blob([content], {'type':contentType});

		a.href = window.URL.createObjectURL(blob);
		a.download = filename;
		a.click();
	}

	/**
	 * Provides the import data object as file-download.
	 */
	function download_import_data() {
		var content;

		if ( undefined === window._ms_import_obj ) { return; }

		content = window.JSON.stringify( window._ms_import_obj );
		download( content, 'protected-content.json' );
	}

	/**
	 * Displays the Import-Progress popup
	 */
	function show_popup() {
		var content = jQuery( '<div></div>' );

		the_progress = wpmUi.progressbar();

		content.append( the_progress.$() );
		the_popup = wpmUi.popup()
			.title( ms_data.lang.progress_title, false )
			.modal( true, false )
			.content( content, true )
			.size( 600, 140 )
			.show();
	}

	/**
	 * Hides the Import-Progress popup
	 */
	function allow_hide_popup() {
		var el = jQuery( '<div style="text-align:center"></div>' ),
			btn = jQuery( '<a href="#" class="close"></a>' );

		btn.text( ms_data.lang.close_progress );
		if ( ms_data.close_link ) {
			btn.attr( 'href', ms_data.close_link );
		}
		btn.addClass( 'button-primary' );
		btn.appendTo( el );

		the_popup.content( el, true )
			.modal( true, true )
			.title( ms_data.lang.import_done );
	}

	/**
	 * Returns the next batch for import.
	 */
	function get_next_batch( max_items ) {
		var batch = {},
			count = 0,
			item;

		batch.items = [];
		batch.item_count = 0;
		batch.label = '';
		batch.source = window._ms_import_obj.source_key;

		for ( count = 0; count < max_items; count += 1 ) {
			item = queue.shift();

			if ( undefined === item ) {
				// Whole queue is processed.
				break;
			}

			batch.label = item.label;
			delete item.label;

			batch.items.push( item );
			batch.item_count += 1;
		}

		return batch;
	}

	/**
	 * Send the next item from the import queue to the ajax handler.
	 */
	function process_queue() {
		var icon ='<i class="wpmui-loading-icon"></i> ',
			batchsize = sel_batchsize.val(),
			batch = get_next_batch( batchsize );

		if ( ! batch.item_count ) {
			// All items were sent - hide the progress bar and show close button.
			allow_hide_popup();
			return;
		}

		// Update the progress bar.
		the_progress
			.value( queue_count - queue.length )
			.label( icon + '<span>' + batch.label + '</span>' );

		// Prepare the ajax payload.
		batch.action = btn_import.val();
		delete batch.label;

		// Send the ajax request and call this function again when done.
		jQuery.post(
			window.ajaxurl,
			batch,
			process_queue
		);
	}

	/**
	 * Starts the import process: A popup is opened to display the progress and
	 * then all import items are individually sent to the plugin via Ajax.
	 */
	function start_import() {
		var k, data, count,
			lang = ms_data.lang;

		queue = [];

		// This will prepare the import process
		queue.push({
			'task': 'start',
			'clear': chk_clear.is(':checked'),
			'label': lang.task_start
		});

		// _ms_import_obj is a JSON object, so we skip the .hasOwnProperty() check.
		count = 0;
		for ( k in window._ms_import_obj.memberships ) {
			data = window._ms_import_obj.memberships[k];
			count += 1;
			queue.push({
				'task': 'import-membership',
				'data': data,
				'label': lang.task_import_membership +  ': ' + count + '...'
			});
		}

		count = 0;
		for ( k in window._ms_import_obj.members ) {
			data = window._ms_import_obj.members[k];
			count += 1;
			queue.push({
				'task': 'import-member',
				'data': data,
				'label': lang.task_import_member +  ': ' + count + '...'
			});
		}

		for ( k in window._ms_import_obj.settings ) {
			data = window._ms_import_obj.settings[k];
			queue.push({
				'task': 'import-settings',
				'setting': k,
				'value': data,
				'label': lang.task_import_settings + '...'
			});
		}

		// Finally clean up after the import
		queue.push({
			'task': 'done',
			'label': lang.task_done
		});

		// Display the import progress bar
		show_popup();
		queue_count = queue.length;
		the_progress.max( queue_count );

		// Start to process the import queue
		process_queue();
	}

	if ( support_download() ) {
		btn_download.click( download_import_data );
	} else {
		btn_download.hide();
	}

	btn_import.click( start_import );

};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_mailchimp = function init() {
	jQuery( '#mailchimp_api_key' ).on( 'ms-ajax-updated', ms_functions.reload );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_payment = function init() {
	function toggle_status( ev, data, response, is_err ) {
		if ( undefined === data.gateway_id ) { return; }
		if ( 'update_gateway' !== data.action ) { return; }

		var row = jQuery( '.gateway-' + data.gateway_id );

		if ( ! is_err ) {
			row.removeClass( 'not-configured' )
				.addClass( 'is-configured' );

			if ( 'sandbox' === data.value ) {
				row.removeClass( 'is-live' ).addClass( 'is-sandbox' );
			} else if ( 'live' === data.value ) {
				row.removeClass( 'is-sandbox' ).addClass( 'is-live' );
			}
		} else {
			row.removeClass( 'is-configured is-live is-sandbox' )
				.addClass( 'not-configured' );
		}
	}

	function change_icon( ev ) {
		var el = jQuery( this ),
			row = el.closest( '.ms-gateway-item' );

		if ( el.prop( 'checked' ) ) {
			row.addClass( 'open' );
		} else {
			row.removeClass( 'open' );
		}
	}

	function toggle_description() {
		var secure_cc = jQuery( '#secure_cc' ).val();

		if ( 'false' === secure_cc || ! secure_cc ) {
			jQuery( '.secure_cc_on' ).hide();
			jQuery( '.secure_cc_off' ).removeClass( 'hidden' ).show();
		} else {
			jQuery( '.secure_cc_off' ).hide();
			jQuery( '.secure_cc_on' ).removeClass( 'hidden' ).show();
		}
	}

	jQuery( document ).on( 'ms-ajax-updated', toggle_status );
	jQuery( document ).on( 'click', '.show-settings', change_icon );

	jQuery( '.wpmui-slider-secure_cc' ).on( 'ms-ajax-done', toggle_description );
	toggle_description();
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_protection = function init () {
	function before_ajax( data, el ) {
		var textarea = jQuery( '#' + data.type ),
			container = textarea.closest( '.wp-editor-wrap' ),
			editor = window.tinyMCE.get( data.type );

		if ( editor && container.hasClass( 'tmce-active' ) ) {
			editor.save(); // Update the textarea content.
		}

		data.value = textarea.val();

		return data;
	}

	function toggle_override() {
		var toggle = jQuery( this ),
			block = toggle.closest( '.inside' ),
			content = block.find( '.wp-editor-wrap' ),
			button = block.find( '.button-primary' );

		if ( toggle.hasClass( 'on' ) ) {
			button.show();
			content.show();
		} else {
			button.hide();
			content.hide();
		}
	}

	jQuery( '.button-primary.wpmui-ajax-update' ).data( 'before_ajax', before_ajax );

	jQuery( '.override-slider' )
		.each(function() { toggle_override.apply( this ); })
		.on( 'ms-ajax-done', toggle_override );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_setup = function init () {
	var site_block = jQuery( '.ms-setup-pages-site' ),
		site_form = site_block.find( '.ms-setup-pages-site-form' ),
		btn_site_edit = site_block.find( '.ms-setup-pages-change-site' ),
		btn_site_cancel = site_block.find( '.ms-setup-pages-cancel' );

	function menu_created( event, data, response, is_err ) {
		var parts;

		if ( ! is_err ) {
			parts = response.split( ':' );
			if ( undefined !== parts[1] ) {
				parts.shift();
				jQuery( '.ms-nav-controls' ).replaceWith( parts.join( ':' ) );
			}
		}
	}

	function show_site_form( ev ) {
		site_form.show();
		btn_site_edit.hide();
		return false;
	}

	function hide_site_form( ev ) {
		site_form.hide();
		btn_site_edit.show();
		return false;
	}

	// Reload the page when Wizard mode is activated.
	jQuery(document).on( 'ms-ajax-updated', '#create_menu', menu_created );

	btn_site_edit.click( show_site_form );
	btn_site_cancel.click( hide_site_form );
};
