/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_setup = function init () {
	function menu_created( event, data, response, is_err ) {
		var parts;

		if ( ! is_err ) {
			parts = response.split( ':' );
			if ( undefined !== parts[1] ) {
				parts.shift();
				jQuery( '.ms-nav-controls' ).replaceWith( parts.join( ':' ) );
			}
		}
	}

	// Reload the page when Wizard mode is activated.
	jQuery(document).on( 'ms-ajax-updated', '#create_menu', menu_created );
};
