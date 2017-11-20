/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_media = function init () {
	jQuery( '#direct_access' ).on( 'ms-ajax-updated', function(){
		//update nginx rules
	} );

	jQuery( '#application_server' ).on( 'ms-ajax-updated', function(){
		//show server div
		var $selected = jQuery( '#application_server' ).val();
		jQuery('.application-servers').each(function(){
			jQuery(this).hide();
		});
		if ( jQuery('#' + $selected).length ) {
			jQuery('#' + $selected).show();
		}
	} );
};