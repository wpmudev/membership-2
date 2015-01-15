/*! Protected Content - v1.1.0
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2015; * Licensed GPLv2+ */
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
			);
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
						slider.trigger( 'wpmui-radio-slider-updated', [data, is_err] );
					}
				);
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
		'wpmui-radio-slider-updated',
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
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_date = function init () {
	jQuery( '.ms-date' ).ms_datepicker();
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_list = function init () {
	var s2_config = jQuery.extend( {}, window.ms_functions.chosen_options );

	function change_search_options() {
		if ( 'membership' === jQuery( '#search_options' ).val() ) {
			jQuery( '#membership_filter' ).show();
			jQuery( '#member-search' ).hide();
		}
		else {
			jQuery( '#membership_filter' ).hide();
			jQuery( '#member-search' ).show();
		}
	}

	jQuery( '#search_options').change( change_search_options );
	change_search_options();

	// Initialize the User-List select field

	function submit_add_form() {
		var sel = jQuery( '#new_member' ).val();
		if ( ! sel.length ) { return false; }

		jQuery( '#form_add_member' ).submit();
	}

	function enable_add_button() {
		var sel = jQuery( '#new_member' ).val();
		if ( ! sel.length ) {
			jQuery( '#add_member' ).addClass( 'disabled' );
		} else {
			jQuery( '#add_member' ).removeClass( 'disabled' );
		}
	}

	s2_config.minimumResultsForSearch = 0;
	s2_config.placeholder = window.ms_data.lang.select_user;
	s2_config.allowClear = true;
	s2_config.multiple = true;
	s2_config.minimumInputLength = 1;
	s2_config.closeOnSelect = false;
	s2_config.ajax = {
		url: window.ajaxurl,
		dataType: 'jsonp',
		cache: true,
		quietMillis: 500,
		data: function (term, page) {
			return {
				filter: term, // search term
				action: 'get_users'
			};
		},
		results: function (data, page) {
			return {results: data};
		}
	};

	jQuery( '#new_member' ).select2( s2_config ).change( enable_add_button );
	jQuery( '#add_member' ).click( submit_add_form );
	enable_add_button();

	// Change the view (show members of different membership)
	function change_view() {
		var list = jQuery( '#view_membership' ),
			new_id = parseInt( list.val() ),
			data = list.data('ajax'),
			url = data.url + new_id;

		if ( new_id <= 0 ) { return; }
		window.location = url;
	}

	jQuery('#view_membership').change( change_view );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_choose_type = function init () {
	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	jQuery( '#private' ).change( function() {
		var me = jQuery( this ),
			is_private = me.prop( 'checked' ),
			types = jQuery( 'input[name="type"]' ),
			cur_type = types.filter( ':checked' ).val();

		if ( is_private ) {
			if ( 'simple' !== cur_type && 'content_type' !== cur_type ) {
				types.filter( '[value="simple"]' )
				.prop( 'checked', true )
				.trigger( 'click' );
			}

			types.filter( '[value="tier"]' ).prop( 'disabled', true );
			types.filter( '[value="dripped"]' ).prop( 'disabled', true );
			jQuery( '.wpmui-tier' ).addClass( 'ms-locked' );
			jQuery( '.wpmui-dripped' ).addClass( 'ms-locked' );
		} else {
			types.filter( '[value="tier"]' ).prop( 'disabled', false );
			types.filter( '[value="dripped"]' ).prop( 'disabled', false );
			jQuery( '.wpmui-tier' ).removeClass( 'ms-locked' );
			jQuery( '.wpmui-dripped' ).removeClass( 'ms-locked' );
		}
	});

	jQuery( 'input[name="type"]' ).click(function() {
		var types = jQuery( 'input[name="type"]' ),
			cur_type = types.filter( ':checked' );

		types.closest( '.wpmui-radio-input-wrapper' ).removeClass( 'active' );
		cur_type.closest( '.wpmui-radio-input-wrapper' ).addClass( 'active' );
	}).first().trigger( 'click' );

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

window.ms_init.view_membership_create_child = function init () {
	var args = {
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	};

	jQuery( '#ms-create-child-form' ).validate(args);
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_membership_list = function init () {
	var table = jQuery( '#the-list-membership' ),
		toggles = jQuery( '.column-collapse', table );

	function toggle_membership( event ) {
		var me = jQuery( this ),
			row = me.closest( 'tr' ),
			icon = me.find( '.toggle-children' ),
			is_alt = row.hasClass( 'alternate' ),
			action = icon.hasClass( 'wpmui-fa-caret-right' ) ? 'show' : 'hide';

		if ( action === 'show' ) {
			icon.removeClass( 'wpmui-fa-caret-right' );
			icon.addClass( 'wpmui-fa-caret-down' );
		} else {
			icon.removeClass( 'wpmui-fa-caret-down' );
			icon.addClass( 'wpmui-fa-caret-right' );
		}

		while( row = row.next() ) {
			if ( is_alt !== row.hasClass( 'alternate' ) ) { break; }
			if ( action === 'show' ) {
				row.show();
			} else {
				row.hide();
			}
		}
	}

	function add_handler() {
		var me = jQuery( this ),
			row = me.closest( 'tr' ),
			icon = me.find( '.toggle-children' );

		if ( ! icon.length ) { return; }
		if ( row.hasClass( 'ms-child-row' ) ) { return; }

		me.click( toggle_membership );
		me.addClass( 'ms-pointer' );
	}

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

	toggles.each(add_handler);

};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.metabox = function init() {
	if ( jQuery( '.ms-protect-content' ).hasClass( 'on' ) ) {
		jQuery( '#ms-metabox-access-wrapper' ).show();
	} else {
		jQuery( '#ms-metabox-access-wrapper' ).hide();
	}

	jQuery( '.dripped' ).click( function() {
		var tooltip = jQuery( this ).children( '.tooltip' );
		tooltip.toggle(300);
	} );

	window.ms_init.ms_metabox_event = function( event, data ) {
		jQuery( '#ms-metabox-wrapper' ).replaceWith( data.response );
		window.ms_init.ms_metabox();
		jQuery( '.wpmui-radio-slider' ).click( function() { window.ms_functions.radio_slider_ajax_update( this ); } );
		jQuery( '.ms-protect-content' ).on( 'wpmui-radio-slider-updated', function( event, data ) { window.ms_init.ms_metabox_event( event, data ); } );
	};

	jQuery( '.ms-protect-content' ).on( 'wpmui-radio-slider-updated', function( event, data ) {
		window.ms_init.ms_metabox_event( event, data );
	});
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_overview = function init () {
	jQuery( '.wpmui-radio-slider' ).on( 'wpmui-radio-slider-updated', function() {
		var object = this,
			obj = jQuery( '#ms-membership-status' );

		if( jQuery( object ).hasClass( 'on' ) ) {
			obj.addClass( 'ms-active' );
		} else {
			obj.removeClass( 'ms-active' );
		}
	});

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

window.ms_init.view_membership_dripped = function init () {

	//global functions defined in ms-functions.js
	ms_functions.change_dripped_type = function( obj ) {
		var type = jQuery( obj ).val();

		jQuery( '.ms-dripped-edit-wrapper' ).hide();
		jQuery( '.ms-dripped-type-' + type ).show();
	};

	ms_functions.change_access = function( access, slider ) {
		var type = jQuery( 'input[name="dripped_type"]:checked').val();

		if ( access ) {
			jQuery( slider ).parent().parent().find( '.dripped.column-dripped' ).css( 'visibility', 'visible' );
		} else {
			jQuery( slider ).parent().parent().find( '.dripped.column-dripped' ).css( 'visibility', 'hidden' );
		}
	};

	jQuery( 'input[name="dripped_type"]').change( function() { ms_functions.change_dripped_type( this ); } );

	jQuery( '.ms-dripped-spec-date' ).ms_datepicker();

	ms_functions.change_dripped_type( jQuery( 'input[name="dripped_type"]:checked') );

	jQuery( '.ms-dripped-calendar' ).click( function() {
		jQuery( this ).parent().find( '.ms-dripped-spec-date.wpmui-ajax-update' ).datepicker( 'show' );
	});

	jQuery( '.ms-period-desc-wrapper' ).click( function() {
		jQuery( this ).parent().addClass( 'ms-dripped-edit' );
	});

	jQuery( 'input.ms-dripped-edit-ok' ).click( function() {
		var period_unit, period_type;

		jQuery( this ).parent().parent().removeClass( 'ms-dripped-edit' );
		period_unit = jQuery( this ).parent().find( 'input' );
		period_type = jQuery( this ).parent().find( 'select' );
		period_unit.change();
		period_type.change();
		jQuery( this ).parent().parent().find( '.ms-period-unit' ).text( period_unit.val() );
		jQuery( this ).parent().parent().find( '.ms-period-type' ).text( period_type.val() );
	});

	jQuery( '.wpmui-radio-slider' ).on( 'wpmui-radio-slider-updated', function( event, data ) {
		ms_functions.change_access( data.value, event.target );
	});

	jQuery( '.wpmui-radio-slider' ).each( function() {
		var value = ( jQuery( this ).children( 'input' ).val() ) ? 1 : 0;
		ms_functions.change_access( value, this );
	});
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_setup_payment = function init () {

	function payment_type() {
		var me = jQuery( this ),
			block = me.closest( '.inside' ),
			pay_type = me.val(),
			all_settings = block.find( '.ms-payment-type-wrapper' ),
			active_settings = block.find( '.ms-payment-type-' + pay_type ),
			after_end = block.find( '.ms-after-end-wrapper' );

		all_settings.hide();
		active_settings.show();

		if ( 'permanent' === pay_type ) {
			after_end.hide();
		} else {
			after_end.show();
		}
	}

	function is_free() {
		var pay_type = jQuery( '.ms-payments-choice' ).hasClass( 'on' ),
			pay_settings = jQuery( '#ms-payment-settings-wrapper' );

		if ( pay_type ) {
			pay_settings.show();
		} else {
			pay_settings.hide();
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

		items.find( '.wpmui-field-description' ).html( currency );
	}


	// Show the correct payment options
	jQuery( '.ms-payment-type' ).change( payment_type );
	jQuery( '.ms-payment-type' ).each( payment_type );

	// Change the "Free/Paid" flag
	jQuery( '.ms-payments-choice' ).change( is_free );
	is_free();

	// Update currency symbols in payment descriptions.
	jQuery( '#currency' ).change( show_currency );

};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_setup = function init () {
	//global functions defined in ms-functions.js
	jQuery( '#comment' ).change( function() { ms_functions.ajax_update( this ); } );

	jQuery( '#menu_id' ).change( function() {
		jQuery( '#ms-menu-form' ).submit();
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
					data = link.data('ajax'),
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

	function submit_comm_change() {
		jQuery( '#ms-comm-type-form' ).submit();
	}

	function reload_window() {
		window.location = ms_data.initial_url;
	}

	function update_toolbar( ev, data ) {
		// Show/Hide the Toolbar menu for protected content.
		if ( data.value ) {
			jQuery( '#wp-admin-bar-ms-unprotected' ).hide();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).show();
		} else {
			jQuery( '#wp-admin-bar-ms-unprotected' ).show();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).hide();
		}
	}

	// Reload the page when Wizard mode is activated.
	jQuery( '#initial_setup' ).on( 'ms-ajax-updated', reload_window );

	// Hide/Show the "Test Membership" button in the toolbar.
	jQuery( '.wpmui-slider-plugin_enabled').on( 'wpmui-radio-slider-updated', update_toolbar );

	// Membership Pages: Update contents after a page was saved
	jQuery( '.wpmui-wp-pages' ).on( 'ms-ajax-updated', page_changed );
	jQuery( '.ms-action a' ).on( 'click', ignore_disabled );
	jQuery(function() { page_changed(); });

	// Select new Communication type
	jQuery( '#comm_type' ).change( submit_comm_change );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_addons = function init () {

	function filter_addons( event, filter, items ) {
		switch ( filter ) {
			case 'options':
				items.hide().filter( '.ms-options' ).show();
				break;
		}
	}

	jQuery( document ).on( 'list-filter', filter_addons );

};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_automated_msg = function init () {
	var is_dirty = false;

	jQuery( '#switch_comm_type' ).click(function() {
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
	});

	jQuery( 'input, select, textarea', '.ms-editor-form' ).change(function() {
		is_dirty = true;
	});

	/**
	 * Add the javascript for our custom TinyMCE button
	 *
	 * @see class-ms-controller-settings.php (function add_mce_buttons)
	 * @see class-ms-view-settings-edit.php (function render_tab_messages_automated)
	 */
	window.tinymce.PluginManager.add(
		'ms_variable',
		function( editor, url ) {
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

window.ms_init.view_settings_mailchimp = function init() {
	jQuery( '#mailchimp_api_key' ).on( 'ms-ajax-updated', ms_functions.reload );
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_payment = function init() {
	function toggle_status( ev, form, response, is_err, data, is_popup, info_field ) {
		var row = jQuery( '.gateway-' + data.gateway_id );

		if ( ! is_err ) {
			row.removeClass( 'not-configured' )
				.addClass( 'is-configured' );

			if ( undefined !== data.mode && 'sandbox' === data.mode ) {
				row.removeClass( 'is-live' ).addClass( 'is-sandbox' );
			} else {
				row.removeClass( 'is-sandbox' ).addClass( 'is-live' );
			}
		}
	}

	jQuery( document ).on( 'ms-ajax-form-done', toggle_status );
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

	jQuery( '.button-primary.wpmui-ajax-update' ).data( 'before_ajax', before_ajax );
};
