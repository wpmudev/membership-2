/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings = function init () {
	jQuery( '#comm_type' ).change( function() {
		jQuery( '#ms-comm-type-form' ).submit();
	});

	// Reload the page when Wizard mode is activated.
	jQuery( '.ms-slider-initial_setup' ).on( 'ms-radio-slider-updated', function() {
		window.location = ms_data.initial_url;
	});

	jQuery( '.ms-edit-url' ).click( function() {
		var text_id = jQuery( this ).prop( 'id' );

		text_id = '#' + text_id.replace( 'edit_slug_', '' );

		jQuery( text_id ).prop( 'readonly', false );
		jQuery( text_id ).focus();

		jQuery( text_id ).change( function() {
			jQuery( this ).prop( 'readonly', true );
		});

		jQuery( text_id ).focusout( function() {
			jQuery( this ).prop( 'readonly', true );
		});
	});
};
