jQuery( document ).ready( function() {

	window.ms_init.ms_metabox = function init() {

		if( jQuery( '.ms-protect-content' ).hasClass( 'on' ) ) {
			jQuery( '#ms-metabox-access-wrapper' ).show();
		}
		else {
			jQuery( '#ms-metabox-access-wrapper' ).hide();
		}
		jQuery( '.dripped' ).click( function() {				
			var tooltip = jQuery( this ).children( '.tooltip' );
			tooltip.toggle(300);
		} );	
	}
	window.ms_init_ms_metabox_event = function( event, data ) {
		jQuery( '#ms-metabox-wrapper' ).replaceWith( data.response );
		window.ms_init.ms_metabox();
		jQuery( '.ms-radio-slider' ).click( function() { window.ms_functions.radio_slider_ajax_update( this ); } );
		jQuery( '.ms-protect-content' ).on( 'ms-radio-slider-updated', function( event, data ) { window.ms_init_ms_metabox_event( event, data ) } );

	}
	
	window.ms_init.ms_metabox();
	
	jQuery( '.ms-protect-content' ).on( 'ms-radio-slider-updated', function( event, data ) { 
		window.ms_init_ms_metabox_event( event, data )
	});

});
