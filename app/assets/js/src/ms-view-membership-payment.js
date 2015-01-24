/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_payment = function init () {

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

	function toggle_trial( ev, data, is_err ) {
		if ( data.value ) {
			jQuery( '.ms-trial-period-details' ).show();
		} else {
			jQuery( '.ms-trial-period-details' ).hide();
		}
	}

	// Show the correct payment options
	jQuery( '#payment_type' ).change( payment_type );
	jQuery( '#payment_type' ).each( payment_type );

	// Update currency symbols in payment descriptions.
	jQuery( '#currency' ).change( show_currency );

	jQuery( '.wpmui-slider-trial_period_enabled' ).on( 'ms-radio-slider-updated', toggle_trial );
};