/*! Protected Content - v1.0.43
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2014; * Licensed GPLv2+ */
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
	if ( undefined === ms_data.ms_init ) { return; }

	if ( ms_data.ms_init instanceof Array ) {
		for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
			initialize( ms_data.ms_init[i] );
		}
	} else {
		initialize( ms_data.ms_init );
	}

	// Prevent multiple calls to init functions...
	ms_data.ms_init = [];
});

/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
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

		if( ! field.hasClass( 'ms-processing' ) ) {
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
					var is_err = fn.ajax_error( response, info_field );
					if ( is_err ) {
						// Reset the input control to previous value...
					}

					info_field.removeClass( 'ms-processing wpmui-loading' );
					field.trigger( 'ms-ajax-updated', [data, response, is_err] );
				}
			);
		}
	},

	radio_slider_ajax_update: function( obj ) {
		var data, info_field,
			slider = jQuery( obj ),
			fn = window.ms_functions;

		if( ! slider.hasClass( 'ms-processing' ) && ! slider.attr( 'readonly' ) ) {
			info_field = fn.ajax_show_indicator( slider );

			slider.addClass( 'ms-processing wpmui-loading' );
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
						var is_err = fn.ajax_error( response, info_field );
						if ( is_err ) {
							slider.togglesClass( 'on' );
						}

						info_field.removeClass( 'ms-processing' );

						slider.removeClass( 'ms-processing wpmui-loading' );
						slider.children( 'input' ).val( slider.hasClass( 'on' ) );
						data.response = response;
						slider.trigger( 'ms-radio-slider-updated', [data, is_err] );
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
		jQuery( document ).trigger( 'ms-ajax-form-send', [me, data, is_popup, info_field] );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var is_err = fn.ajax_error( response, info_field );

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
		jQuery( document ).trigger( 'ms-load-dialog', [data] );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var dlg, dlg_title, dlg_close, dlg_content, resp = false;

				try { resp = jQuery.parseJSON( response ); }
				catch( err ) { resp = false; }

				resp.title = resp.title || 'Dialog';
				resp.height = resp.height || 100;
				resp.content = resp.content || '';

				if ( resp !== false ) {
					if ( ! isNaN( resp.height ) ) {
						resp.height += 51;
					}

					// Close button
					dlg_close = jQuery( '<div class="ms-dlg-close"></div>' );
					dlg_close.html( '<i class="dashicons dashicons-no-alt"></i>' );

					// Title
					dlg_title = jQuery( '<div class="ms-dlg-title"></div>' );
					dlg_title.append( '<span></span>' ).append( dlg_close );
					dlg_title.find( 'span' ).html( resp.title );

					// Content
					dlg_content = jQuery( '<div class="ms-dlg-content"></div>' );
					dlg_content.html( resp.content );

					// Combine all dialog elements
					dlg = jQuery( '<div class="ms-dlg"></div>' );
					dlg.append( dlg_title ).append( dlg_content ).height( resp.height );

					fn.dlg_wrap.append( dlg ).show();

					// Initialize UI components.
					fn.init( dlg );

					jQuery( document ).trigger( 'ms-open-dialog', [dlg, resp, data] );
				}
			}
		);

		return false;
	},

	/**
	 * Closes all open dialogs.
	 */
	close_dialogs: function() {
		var fn = window.ms_functions,
			dlgs = [];

		if ( undefined !== fn.dlg_wrap ) {
			dlgs = fn.dlg_wrap.find( '.ms-dlg' );
			jQuery( document ).trigger( 'ms-close-dialogs', [dlgs] );

			// Hide all dialogs.
			fn.dlg_wrap.hide();

			// Remove all dialogs from DOM.
			dlgs.remove();
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
				parent = jQuery( '.ms-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();

			el.hide();

			// Move tooltip back into the DOM hierarchy
			el.appendTo( jQuery( parent ) );
		}

		// Hide multiple tooltips
		jQuery( '.ms-tooltip[timestamp]').each( hide_tooltip );
	});

	// Hide single tooltip when Close-Button is clicked.
	jQuery( '.ms-tooltip-button' ).click(function() {
		var el = jQuery( this ),
			parent = el.parents( '.ms-tooltip' ),
			stamp = jQuery( parent ).attr( 'timestamp' ),
			super_parent = jQuery( '.ms-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();

		jQuery( parent ).hide();

		// Move tooltip back into the DOM hierarchy
		jQuery( parent ).appendTo( jQuery( super_parent ) );
	});

	// Don't propagate click events inside the tooltip to the document.
	jQuery( '.ms-tooltip' ).click(function(e) {
		e.stopPropagation();
	});

	// Toggle a tooltip
	jQuery('.ms-tooltip-info').click(function( event ) {
		var parent, stamp, sibling, newpos, tooltip,
			el = jQuery( this );

		el.toggleClass( 'open' );

		if ( ! el.hasClass( 'open' ) ) {
			// HIDE
			parent = el.parents( '.ms-tooltip-wrapper' );
			stamp = jQuery( parent ).attr( 'timestamp' );
			sibling = jQuery( '.ms-tooltip[timestamp="' + stamp + '"]' ).first();

			jQuery( sibling ).hide();

			// Move tooltip back into the DOM hierarchy
			jQuery( sibling ).appendTo( jQuery( parent ) );
		} else {
			// SHOW
			el.parents('.ms-tooltip-wrapper').attr( 'timestamp', event.timeStamp );
			event.stopPropagation();
			tooltip = el.siblings( '.ms-tooltip' );

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
	s2_config.ajax = {
		url: window.ajaxurl,
		dataType: 'jsonp',
		quietMillis: 200,
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
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_choose_type = function init () {
	var el_private = jQuery( '.ms-private-wrapper' ),
		ms_pointer = ms_data.ms_pointer;

	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	jQuery( 'input[name="type"]' ).click( function() {
		if( jQuery.inArray( jQuery( this ).val(), ms_data.ms_private_types ) > -1 ) {
			el_private.removeClass( 'disabled' );
			el_private.find( 'input' ).prop( 'disabled', false );
		}
		else {
			el_private.addClass( 'disabled' );
			el_private.find( 'input' ).prop( 'disabled', true ).prop( 'checked', false );
		}
	});

	jQuery( 'input[name="type"]' ).first().click();

	// Cancel the wizard.
	jQuery( '#cancel' ).click( function() {
		var me = jQuery( this );

		// Simply reload the page after the setting has been changed.
		me.on( 'ms-ajax-updated', function() {
			window.location = ms_data.initial_url;
		} );
		ms_functions.ajax_update( me );
	});

	if( ! ms_pointer.hide_wizard_pointer ) {
		jQuery( '#adminmenu li' ).find( 'a[href="admin.php?page=protected-content-setup"]' ).pointer({
			content: ms_pointer.message,
			pointerClass: ms_pointer.pointer_class,
			position: {
				edge: 'left',
				align: 'center'
			},
			buttons: function( event, t ) {
				var close  = ( window.wpPointerL10n ) ? window.wpPointerL10n.dismiss : 'Dismiss',
					button = jQuery('<a class="close" href="#">' + close + '</a>');

				return button.bind( 'click.pointer', function(e) {
					e.preventDefault();
					t.element.pointer('close');
				});
			},
			close: function() {
				jQuery.post( window.ajaxurl, {
					field: ms_pointer.field,
					value: ms_pointer.value,
					action: ms_pointer.action,
					_wpnonce: ms_pointer.nonce,
				});
			}
		}).pointer( 'open' );
	}
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_overview = function init () {
	var ms_desc = jQuery( '.membership-description' ),
		ms_show_editor = ms_desc.find( '.show-editor' ),
		ms_readonly = ms_desc.find( '.readonly' ),
		ms_editor = ms_desc.find( '.editor' ),
		txt_editor = ms_editor.find( 'textarea' );

	jQuery( '.ms-radio-slider' ).on( 'ms-radio-slider-updated', function() {
		var object = this,
			obj = jQuery( '#ms-membership-status' );

		if( jQuery( object ).hasClass( 'on' ) ) {
			obj.addClass( 'ms-active' );
		}
		else {
			obj.removeClass( 'ms-active' );
		}
	});

	// Click on Read-Only description: Show the input field.
	ms_show_editor.click( function() {
		ms_readonly.addClass( 'hidden' );
		ms_editor.removeClass( 'hidden' );
		txt_editor.focus().data( 'dirty', false );
	});

	// When the editor loses focus: Hide the input field again.
	txt_editor
		.change(function(){
			txt_editor.data( 'dirty', true );
		})
		.blur(function() {
			if ( txt_editor.data( 'dirty' ) === true ) {
				return false;
			} else {
				ms_readonly.removeClass( 'hidden' );
				ms_editor.addClass( 'hidden' );
			}
		})
		.on(
			'ms-ajax-updated',
			function( ev, data, response, is_err ) {
				var desc = txt_editor.val();

				if ( is_err ) { return false; }

				ms_readonly.find( '.value' ).html( desc );
				ms_readonly.removeClass( 'hidden' );
				ms_editor.addClass( 'hidden' );
				ms_editor.find( '.okay, .error' ).removeClass( 'okay error' );

				if ( desc.length ) {
					ms_readonly.find( '.empty' ).addClass( 'hidden' );
				} else {
					ms_readonly.find( '.empty' ).removeClass( 'hidden' );
				}
			}
		);
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_setup_payment = function init () {

	//global functions defined in ms-functions.js
	ms_functions.payment_type = function( obj ) {
		var payment_type, after_end;

		jQuery( obj ).parent().parent().find( '.ms-payment-type-wrapper' ).hide();
		payment_type = jQuery( obj ).val();
		jQuery( obj ).parent().parent().find( '.ms-payment-type-' + payment_type ).show();

		after_end = jQuery( obj ).parent().parent().find( '.ms-after-end-wrapper' );
		if( 'permanent' === payment_type ) {
			after_end.hide();
		}
		else {
			after_end.show();
		}
	};

	ms_functions.is_free = function() {
		if( '0' === jQuery( 'input[name="is_free"]:checked' ).val() ) {
			jQuery( '#ms-payment-settings-wrapper' ).show();
		}
		else {
			jQuery( '#ms-payment-settings-wrapper' ).hide();
		}
	};

	jQuery( 'input[name="is_free"]' ).change( function() {
		ms_functions.is_free();
	});

	jQuery( '.ms-payment-type' ).change( function() {
		ms_functions.payment_type( this );
	});

	// initial event fire
	jQuery( '.ms-payment-type' ).each( function() {
		ms_functions.payment_type( this );
	});

	ms_functions.is_free();

	// Update currency symbols in payment descriptions.
	jQuery( '#currency' ).change(function() {
		var currency = jQuery( this ).val(),
			items = jQuery( '.ms-payment-structure-wrapper' );

		// Same translation table in:
		// -> class-ms-model-settings.php
		switch ( currency ) {
			case 'USD': currency = '$'; break;
			case 'EUR': currency = '&euro;'; break;
			case 'JPY': currency = '&yen;'; break;
		}

		items.find( '.ms-field-description' ).html( currency );
	});

};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings = function init () {
	jQuery( '#comm_type' ).change( function() {
		jQuery( '#ms-comm-type-form' ).submit();
	});

	// Reload the page when Wizard mode is activated.
	jQuery( '#initial_setup' ).on( 'ms-ajax-updated', function() {
		window.location = ms_data.initial_url;
	});

	jQuery( '.ms-slider-plugin_enabled').on( 'ms-radio-slider-updated', function(ev, data) {
		// Show/Hide the Toolbar menu for protected content.
		if ( data.value ) {
			jQuery( '#wp-admin-bar-ms-unprotected' ).hide();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).show();
		} else {
			jQuery( '#wp-admin-bar-ms-unprotected' ).show();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).hide();
		}
	});

	jQuery( '.ms-edit-url' ).click( function() {
		var text_id = jQuery( this ).prop( 'id' );

		text_id = '#' + text_id.replace( 'edit_slug_', '' );

		jQuery( text_id ).prop( 'readonly', false );
		jQuery( text_id ).focus();

		jQuery( text_id ).change( function() {
			jQuery( this ).prop( 'readonly', true );
		});

		jQuery( text_id ).focusout( function() {
			jQuery( this ).prop( 'readonly', true );
		});
	});
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

	jQuery( '.button-primary.ms-ajax-update' ).data( 'before_ajax', before_ajax );
};
