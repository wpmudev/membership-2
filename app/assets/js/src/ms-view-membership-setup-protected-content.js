/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_setup = function init () {
	//global functions defined in ms-functions.js
	jQuery( '#comment' ).change( function() { ms_functions.ajax_update( this ); } );

	jQuery( '#menu_id' ).change( function() {
		jQuery( '#ms-menu-form' ).submit();
	});
};
