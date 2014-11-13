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
	jQuery( '#initial_setup' ).on( 'ms-ajax-updated', function() {
		window.location = ms_data.initial_url;
	});

	jQuery( '.ms-slider-plugin_enabled').on( 'ms-radio-slider-updated', function(ev, data) {
		// Show/Hide the Toolbar menu for protected content.
		if ( data.value ) {
			jQuery( '#wp-admin-bar-ms-unprotected' ).hide();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).show();
		} else {
			jQuery( '#wp-admin-bar-ms-unprotected' ).show();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).hide();
		}
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
