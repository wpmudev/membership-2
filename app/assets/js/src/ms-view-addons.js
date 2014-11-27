/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_addons = function init () {

	function filter_addons( event, filter, items ) {
		switch ( filter ) {
			case 'options':
				items.hide().filter( '.ms-options' ).show();
				break;
		}
	}

	jQuery( document ).on( 'list-filter', filter_addons );

};
