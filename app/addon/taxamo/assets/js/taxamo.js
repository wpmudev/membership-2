/**
 * Javascript used by the taxamo front-end.
 * This adds functions to allow users to change the tax-country and vat number.
 */
jQuery(function() {
	window.ms_taxamo = window.ms_taxamo || {};
	window.ms_taxamo.init = init;
	init();

	function init () {
		var profile_wrapper = jQuery( '#ms-taxamo-wrapper' ),
			profile_form = jQuery( '.ms-wrap', profile_wrapper ),
			chk_manual = jQuery( '#declare_manually', profile_form ),
			sel_billing_country = jQuery( '#billing_country', profile_form ),
			inp_detected_country = jQuery( '#detected_country', profile_form ),
			vat_number_field = jQuery( '.vat_number_field', profile_form ),
			inp_vat_number = jQuery( '#vat_number', profile_form ),
			btn_save = jQuery( '.save', profile_form ),
			btn_close = jQuery( '.close', profile_form ),
			fields = jQuery( ':input', profile_form );

		function show_edit_form( ev ) {
			profile_wrapper.show();
			return false;
		}

		function close_edit_form( ev ) {
			profile_wrapper.hide();
			return false;
		}

		function set_manual_state( ev ) {
			if ( chk_manual.prop( 'checked' ) ) {
				profile_form.addClass( 'ms-tax-manual' );
			} else {
				profile_form.removeClass( 'ms-tax-manual' );
				sel_billing_country.val( inp_detected_country.val() );
			}
			set_billing_country( ev );
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
				if ( field.is( ':checkbox' ) ) {
					data[key] = field.prop( 'checked' );
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

		// Toggle the visual "Declare manual" state.
		chk_manual.click( set_manual_state );
		sel_billing_country.change( set_billing_country );

		// Update the settings data via Ajax.
		btn_save.click( ajax_update );
	}
});