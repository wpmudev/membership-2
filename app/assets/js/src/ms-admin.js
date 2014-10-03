/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var callback = ms_data.ms_init;
	if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
		window.ms_init[callback]();
	}
});
