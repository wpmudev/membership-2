/**
 * Javascript used by the taxamo front-end.
 * This adds functions to allow users to change the tax-country and vat number.
 */
jQuery(function() {
	var need_refresh = false;

	window.ms_taxamo = window.ms_taxamo || {};
	window.ms_taxamo.init = init;
	init();

	function init () {
		var
			body = jQuery( 'body' ),
			profile_wrapper = jQuery( '#ms-taxamo-wrapper' ),
			profile_form = jQuery( '.ms-wrap', profile_wrapper ),
			opt_choice = jQuery( 'input.country_choice', profile_form ),
			sel_billing_country = jQuery( '#billing_country', profile_form ),
			inp_detected_country = jQuery( '#detected_country', profile_form ),
			vat_number_field = jQuery( '.vat_number_field', profile_form ),
			inp_vat_number = jQuery( '#vat_number', profile_form ),
			btn_save = jQuery( '.save', profile_form ),
			btn_close = jQuery( '.close', profile_form ),
			fields = jQuery( ':input', profile_form )
			body_messages = jQuery( '.body-messages', profile_wrapper );

		body_messages.detach();

		function show_edit_form( ev ) {
			profile_wrapper.show();
			return false;
		}

		function close_edit_form( ev ) {
			profile_wrapper.hide();

			if ( need_refresh ) {
				body.addClass( 'ms-tax-is-loading' );
				body_messages.appendTo( body );
				window.location.reload();
			}
			return false;
		}

		function set_country_choice( ev ) {
			var choice = opt_choice.filter( ':checked' ).val();

			profile_form.removeClass( 'ms-tax-declared ms-tax-vat ms-tax-auto' );
			profile_form.addClass( 'ms-tax-' + choice );
		}

		function set_billing_country( ev ) {
			if ( 'XX' === sel_billing_country.val() ) {
				profile_form.addClass( 'ms-no-tax' );
			} else {
				profile_form.removeClass( 'ms-no-tax' );
			}
		}

		function ajax_response( response ) {
			var new_form;

			btn_save.removeProp( 'disabled' );
			btn_close.removeProp( 'disabled' );
			profile_form.removeClass( 'is-loading' );

			new_form = jQuery( response );
			if ( ! new_form.length ) { return; }

			profile_form.remove();
			new_form.appendTo( profile_wrapper );
			need_refresh = true;
			init();
		}

		function ajax_update( ev ) {
			var data = {};

			if ( ! ms_taxamo.ajax_url ) { return; }
			btn_save.prop( 'disabled', 'disabled' );
			btn_close.prop( 'disabled', 'disabled' );
			profile_form.addClass( 'is-loading' );

			for ( var i = 0; i < fields.length; i += 1 ) {
				var field = jQuery( fields[i] ),
					key = field.attr( 'name' );

				if ( ! key ) { continue; }
				if ( undefined === data[key] ) { data[key] = ''; }
				if ( field.is( ':checkbox' ) ) {
					data[key] = field.prop( 'checked' );
				} else if ( field.is( ':radio' ) ) {
					if ( field.is( ':checked' ) ) {
						data[key] = field.val();
					}
				} else {
					data[key] = field.val();
				}
			}

			jQuery.post( ms_taxamo.ajax_url, data, ajax_response, 'html' );
		}

		if ( ! profile_wrapper.length ) { return false; }

		// Show / Hide the settings form.
		jQuery( '.ms-tax-editor' ).click( show_edit_form );

		// Closing the settings form.
		profile_form.click( function( ev ) { ev.stopPropagation(); } );
		profile_wrapper.click( close_edit_form );
		btn_close.click( close_edit_form );

		// Toggle the visible tax country options.
		opt_choice.click( set_country_choice );
		sel_billing_country.change( set_billing_country );

		// Update the settings data via Ajax.
		btn_save.click( ajax_update );
	}
});