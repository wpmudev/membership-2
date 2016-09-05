/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.metabox = function init() {
	var radio_protection = jQuery( '.ms-protect-content' ),
		radio_rule = jQuery( '.ms-protection-rule' ),
		box_access = jQuery( '#ms-metabox-access-wrapper' );

	if ( radio_protection.hasClass( 'on' ) ) {
		box_access.show();
	} else {
		box_access.hide();
	}

	jQuery( '.dripped' ).click( function() {
		var tooltip = jQuery( this ).children( '.tooltip' );
		tooltip.toggle( 300 );
	} );

	// Callback after the base protection setting was changed.
	window.ms_init.ms_metabox_event = function( event, data ) {
		jQuery( '#ms-metabox-wrapper' ).replaceWith( data.response );
		window.ms_init.metabox();

		jQuery( '.wpmui-radio-slider' ).click( function() {
			window.ms_functions.radio_slider_ajax_update( this );
		} );

		radio_protection.on( 'ms-radio-slider-updated', function( event, data ) {
			window.ms_init.ms_metabox_event( event, data );
		} );
	};

	// Callback after a membership protection setting was changed.
	function rule_updated( event, data ) {
		var active = radio_rule.filter('.on,.wpmui-loading').length;

		if ( active ) {
			box_access.show();
			radio_protection.addClass( 'on' );
		} else {
			box_access.hide();
			radio_protection.removeClass( 'on' );
		}
	}

	radio_protection.on( 'ms-radio-slider-updated', function( event, data ) {
		window.ms_init.ms_metabox_event( event, data );
	});
	radio_rule.on( 'ms-radio-slider-updated', function( event, data ) {
		rule_updated( event, data );
	});
};
