/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_setup = function init () {
	var site_block = jQuery( '.ms-setup-pages-site' ),
		site_form = site_block.find( '.ms-setup-pages-site-form' ),
		btn_site_edit = site_block.find( '.ms-setup-pages-change-site' ),
		btn_site_cancel = site_block.find( '.ms-setup-pages-cancel' );

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

	function show_site_form( ev ) {
		site_form.show();
		btn_site_edit.hide();
		return false;
	}

	function hide_site_form( ev ) {
		site_form.hide();
		btn_site_edit.show();
		return false;
	}

	// Reload the page when Wizard mode is activated.
	jQuery(document).on( 'ms-ajax-updated', '#create_menu', menu_created );

	btn_site_edit.click( show_site_form );
	btn_site_cancel.click( hide_site_form );
};
