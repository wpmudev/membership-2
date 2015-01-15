/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.metabox = function init() {
	if ( jQuery( '.ms-protect-content' ).hasClass( 'on' ) ) {
		jQuery( '#ms-metabox-access-wrapper' ).show();
	} else {
		jQuery( '#ms-metabox-access-wrapper' ).hide();
	}

	jQuery( '.dripped' ).click( function() {
		var tooltip = jQuery( this ).children( '.tooltip' );
		tooltip.toggle(300);
	} );

	window.ms_init.ms_metabox_event = function( event, data ) {
		jQuery( '#ms-metabox-wrapper' ).replaceWith( data.response );
		window.ms_init.ms_metabox();
		jQuery( '.wpmui-radio-slider' ).click( function() { window.ms_functions.radio_slider_ajax_update( this ); } );
		jQuery( '.ms-protect-content' ).on( 'wpmui-radio-slider-updated', function( event, data ) { window.ms_init.ms_metabox_event( event, data ); } );
	};

	jQuery( '.ms-protect-content' ).on( 'wpmui-radio-slider-updated', function( event, data ) {
		window.ms_init.ms_metabox_event( event, data );
	});
};
