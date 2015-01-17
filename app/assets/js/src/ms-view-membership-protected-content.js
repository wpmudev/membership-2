/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_protected_content = function init () {
	function format( state ) {
		var attr,
			original_option = state.element;

		attr = 'class="val" style="background: ' + jQuery( original_option ).data( 'color' ) + '"';
		return '<span ' + attr + '>' + state.text + '</span>';
	}

	jQuery( 'select.ms-memberships' ).select2({
		//formatResult: format,
		formatSelection: format,
		escapeMarkup: function( m ) { return m; },
		dropdownCssClass: 'ms-memberships'
	});
};
