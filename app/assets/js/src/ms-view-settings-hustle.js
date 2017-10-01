/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings_hustle = function init() {
	jQuery( '#hustle_provider' ).on( 'ms-ajax-updated', ms_functions.reload );
};
