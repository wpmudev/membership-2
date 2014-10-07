/*! Protected Content - v1.0.0
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2014; * Licensed GPLv2+ */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var callback = ms_data.ms_init;
	if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
		window.ms_init[callback]();
	}
});

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
