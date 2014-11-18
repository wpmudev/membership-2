/*global jQuery:false */
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
