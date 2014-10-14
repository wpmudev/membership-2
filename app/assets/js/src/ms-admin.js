/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var i;

	window.ms_init._done = window.ms_init._done || {};

	function initialize( callback ) {
		if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
			// Prevent multiple calls to init functions...
			if ( true === window.ms_init._done[callback] ) { return false; }

			window.ms_init._done[callback] = true;
			window.ms_init[callback]();
		}
	}

	if ( undefined === window.ms_data ) { return; }
	if ( undefined === ms_data.ms_init ) { return; }

	if ( ms_data.ms_init instanceof Array ) {
		for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
			initialize( ms_data.ms_init[i] );
		}
	} else {
		initialize( ms_data.ms_init );
	}

	// Prevent multiple calls to init functions...
	ms_data.ms_init = [];
});
