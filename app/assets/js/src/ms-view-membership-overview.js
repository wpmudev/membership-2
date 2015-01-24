/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_overview = function init () {
	jQuery( '.wpmui-radio-slider' ).on( 'ms-radio-slider-updated', function() {
		var object = this,
			obj = jQuery( '#ms-membership-status' );

		if( jQuery( object ).hasClass( 'on' ) ) {
			obj.addClass( 'ms-active' );
		} else {
			obj.removeClass( 'ms-active' );
		}
	});

	// Triggered after the Membership details were modified via the edit popup.
	jQuery( document ).on( 'ms-ajax-form-done', function( ev, form, response, is_err, data ) {
		if ( ! is_err ) {
			// reload the page to reflect the update
			window.location.reload();
		}
	});
};