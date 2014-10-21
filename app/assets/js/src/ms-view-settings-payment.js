/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_payment = function init() {
	function toggle_status( ev, form, response, is_err, data, is_popup, info_field ) {
		var active = jQuery( '.ms-active-wrapper-' + data.gateway_id );

		if ( ! is_err ) {
			active.removeClass( 'ms-gateway-not-configured' )
				.addClass( 'ms-gateway-configured' );
		}
	}

	jQuery( document ).on( 'ms-ajax-form-done', toggle_status );
	/*
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

	jQuery( '.ms-gateway-setings-form' ).each( setting_init );

	jQuery( '.ms-close-button' ).click( close_gateway_settings );
	*/
};
