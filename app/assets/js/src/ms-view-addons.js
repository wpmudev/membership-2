/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_addons = function init () {

	var list = jQuery( '.ms-addon-list' );

	// Apply the custom list-filters
	function filter_addons( event, filter, items ) {
		switch ( filter ) {
			case 'options':
				items.hide().filter( '.ms-options' ).show();
				break;
		}
	}

	// Show an overlay when ajax update starts (prevent multiple ajax calls at once!)
	function ajax_start( event, data, status, animation ) {
		animation.removeClass( 'wpmui-loading' );
		list.addClass( 'wpmui-loading' );
	}

	// Remove the overlay after ajax update is done
	function ajax_done( event, data, status, animation ) {
		list.removeClass( 'wpmui-loading' );
	}

	// After an add-on was activated or deactivated
	function addon_toggle( event ) {
		var el = jQuery( event.target ),
			card = el.closest( '.list-card-top' ),
			details = card.find( '.details' ),
			fields = details.find( '.wpmui-ajax-update-wrapper' );

		if ( el.closest( '.details' ).length ) { return; } // A detail setting was updated; add-on status was not changed...

		if ( el.hasClass( 'on' ) ) {
			fields.removeClass( 'disabled' );
		} else {
			fields.addClass( 'disabled' );
		}
	}

	jQuery( document ).on( 'list-filter', filter_addons );
	jQuery( document ).on( 'ms-ajax-start', ajax_start );
	jQuery( document ).on( 'ms-ajax-updated', addon_toggle );
	jQuery( document ).on( 'ms-ajax-done', ajax_done );

	jQuery( '.list-card-top .wpmui-ajax-update-wrapper' ).each(function() {
		jQuery( this ).trigger( 'ms-ajax-updated' );
	});
};
