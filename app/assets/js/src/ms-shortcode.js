/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.shortcode = function init () {
	jQuery( '.ms-membership-form .membership_cancel' ).click( function() {
		if ( window.confirm( ms_data.cancel_msg ) ) {
			return true;
		} else {
			return false;
		}
	});
};
