/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_help = function init () {
	function toggle_section() {
		var me = jQuery( this ),
			block = me.parents( '.ms-help-box' ).first(),
			details = block.find( '.ms-help-details' );

		details.toggle();
	}

	jQuery( '.ms-help-toggle' ).click( toggle_section );
};
