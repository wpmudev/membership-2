/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings = function init () {
	function edit_url() {
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
	}

	function submit_comm_change() {
		jQuery( '#ms-comm-type-form' ).submit();
	}

	function reload_window() {
		window.location = ms_data.initial_url;
	}

	function update_toolbar( ev, data ) {
		// Show/Hide the Toolbar menu for protected content.
		if ( data.value ) {
			jQuery( '#wp-admin-bar-ms-unprotected' ).hide();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).show();
		} else {
			jQuery( '#wp-admin-bar-ms-unprotected' ).show();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).hide();
		}
	}

	jQuery( '#comm_type' ).change( submit_comm_change );

	// Reload the page when Wizard mode is activated.
	jQuery( '#initial_setup' ).on( 'ms-ajax-updated', reload_window );

	// Hide/Show the "Test Membership" button in the toolbar.
	jQuery( '.ms-slider-plugin_enabled').on( 'ms-radio-slider-updated', update_toolbar );

	jQuery( '.ms-edit-url' ).click( edit_url );
};
