/**
 * Javascript used by the taxamo front-end.
 * This adds functions to allow users to change the tax-country and vat number.
 */
jQuery(function() {
	var profile_wrapper = jQuery( '#ms-taxamo-wrapper' ),
		profile_form = jQuery( '.ms-wrap', profile_wrapper );

	function show_edit_form( ev ) {
		profile_wrapper.show();
		return false;
	}

	function close_edit_form( ev ) {
		profile_wrapper.hide();
		return false;
	}

	if ( ! profile_wrapper.length ) { return false; }

	jQuery( '.ms-tax-editor' ).click( show_edit_form );
	profile_wrapper.click( close_edit_form );
	jQuery( '.close', profile_wrapper ).click( close_edit_form );
	profile_form.click( function( ev ) { ev.stopPropagation(); } );
});