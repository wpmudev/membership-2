/*global jQuery:false */
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
			} else {
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
