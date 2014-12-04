/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_setup_payment = function init () {

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

	function is_free() {
		var pay_type = jQuery( '.ms-payments-choice' ).hasClass( 'on' ),
			pay_settings = jQuery( '#ms-payment-settings-wrapper' );

		if ( pay_type ) {
			pay_settings.show();
		} else {
			pay_settings.hide();
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


	// Show the correct payment options
	jQuery( '.ms-payment-type' ).change( payment_type );
	jQuery( '.ms-payment-type' ).each( payment_type );

	// Change the "Free/Paid" flag
	jQuery( '.ms-payments-choice' ).change( is_free );
	is_free();

	// Update currency symbols in payment descriptions.
	jQuery( '#currency' ).change( show_currency );

};