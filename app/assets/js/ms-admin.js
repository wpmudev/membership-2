/*! Protected Content - v1.0.0
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2014; * Licensed GPLv2+ */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var i;

	function initialize( callback ) {
		if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
			window.ms_init[callback]();
		}
	}

	if ( ms_data.ms_init instanceof Array ) {
		for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
			initialize( ms_data.ms_init[i] );
		}
	} else {
		initialize( ms_data.ms_init[i] );
	}
});

/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
	data: [],
	processing_class: 'ms-processing',
	radio_slider_on_class: 'on',
	value: 0,
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

		if( ! slider.hasClass( fn.processing_class ) ) {
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
});

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_date = function init () {
	var dp_config = {
        dateFormat: 'yy-mm-dd' //TODO get wp configured date format
    };

	jQuery( '.ms-date' ).datepicker( dp_config );
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

	jQuery( '.ms-datepicker' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });

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
	jQuery( '.ms-slider-initial_setup' ).on( 'ms-radio-slider-updated', function() {
		window.location = ms_data.initial_url;
	});

	jQuery( '.chosen-select.ms-ajax-update' ).on( 'ms-ajax-updated', function( event, data ) {
		var page_id = jQuery( this ).val(), page_url = null, page_edit_url = null;

		page_url = jQuery( '#page_urls option[value="' + page_id + '"]' ).text();
		page_url = ( page_url ) ? page_url : '#';
		jQuery( '#url_' + data.field ).attr( 'href', page_url );

		page_edit_url = jQuery( '#page_edit_urls option[value="' + page_id + '"]' ).text();
		page_edit_url = ( page_edit_url ) ? page_edit_url : '#';

		jQuery( '#edit_url_' + data.field ).attr( 'href', page_edit_url );
	});
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
	function close_gateway_settings() {
		window.self.parent.tb_remove();
	}

	function setting_submit( form ) {
		var gateway, wrapper;

		gateway = jQuery( form ).data( 'ms');
		wrapper = jQuery( '.ms-active-wrapper-' + gateway );
		wrapper.removeClass( 'ms-gateway-not-configured' );
		wrapper.addClass( 'ms-gateway-configured' );

		close_gateway_settings();
	}

	function setting_init() {
		jQuery( this ).validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			submitHandler: setting_submit
		});
	}

	jQuery( '.ms-datepicker' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });

	jQuery( '.ms-gateway-setings-form' ).each( setting_init );

	jQuery( '.ms-close-button' ).click( close_gateway_settings );
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
