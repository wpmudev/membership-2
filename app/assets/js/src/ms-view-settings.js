/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings = function init () {
	jQuery( '#comm_type' ).change( function() {
		jQuery( '#ms-comm-type-form' ).submit();
	});

	// Reload the page when Wizard mode is activated.
	jQuery( '.ms-slider-initial_setup' ).on( 'ms-radio-slider-updated', function() {
		window.location = ms_data.initial_url;
	});

	jQuery( '.chosen-select.ms-ajax-update' ).on( 'ms-ajax-updated', function( event, data ) {
		var page_id = jQuery( this ).val(), page_url = null, page_edit_url = null;

		page_url = jQuery( '#page_urls option[value="' + page_id + '"]' ).text();
		page_url = ( page_url ) ? page_url : '#';
		jQuery( '#url_' + data.field ).attr( 'href', page_url );

		page_edit_url = jQuery( '#page_edit_urls option[value="' + page_id + '"]' ).text();
		page_edit_url = ( page_edit_url ) ? page_edit_url : '#';

		jQuery( '#edit_url_' + data.field ).attr( 'href', page_edit_url );
	});
};
