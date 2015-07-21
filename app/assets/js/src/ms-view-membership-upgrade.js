/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

//
// JS for the Membership > Edit > Upgrade Paths page.
//
window.ms_init.view_membership_upgrade = function init () {
	var slider_allow = jQuery( '.ms-allow .wpmui-radio-slider' );

	function slider_updated() {
		var me = jQuery( this ),
			denied = me.hasClass( 'on' ),
			row = me.closest( '.ms-allow' ),
			upd_replace = row.next( '.ms-update-replace' );

		if ( ! upd_replace.length ) { return; }

		if ( denied ) {
			upd_replace.hide();
		} else {
			upd_replace.show();
		}
	}

	slider_allow.on( 'ms-radio-slider-updated', slider_updated );

	slider_allow.each(function() {
		slider_updated.apply( this );
	});
};