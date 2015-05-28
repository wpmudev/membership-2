/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_payment = function init() {
	function toggle_status( ev, data, response, is_err ) {
		if ( undefined === data.gateway_id ) { return; }

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

	jQuery( document ).on( 'ms-ajax-updated', toggle_status );

	jQuery( document ).on( 'click', '.show-settings', change_icon );
};
