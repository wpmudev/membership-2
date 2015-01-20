/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_add = function init () {
	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	jQuery( 'input[name="type"]' ).click(function() {
		var types = jQuery( 'input[name="type"]' ),
			cur_type = types.filter( ':checked' );

		types.closest( '.wpmui-radio-input-wrapper' ).removeClass( 'active' );
		cur_type.closest( '.wpmui-radio-input-wrapper' ).addClass( 'active' );
	}).first().trigger( 'click' );

	// Cancel the wizard.
	jQuery( '#cancel' ).click( function() {
		var me = jQuery( this );

		// Simply reload the page after the setting has been changed.
		me.on( 'ms-ajax-updated', function() {
			window.location = ms_data.initial_url;
		} );
		ms_functions.ajax_update( me );
	});

};
