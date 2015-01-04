/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_choose_type = function init () {
	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	jQuery( '#private' ).change( function() {
		var me = jQuery( this ),
			is_private = me.prop( 'checked' ),
			types = jQuery( 'input[name="type"]' ),
			cur_type = types.filter( ':checked' ).val();

		if ( is_private ) {
			if ( 'simple' !== cur_type && 'content_type' !== cur_type ) {
				types.filter( '[value="simple"]' )
				.prop( 'checked', true )
				.trigger( 'click' );
			}

			types.filter( '[value="tier"]' ).prop( 'disabled', true );
			types.filter( '[value="dripped"]' ).prop( 'disabled', true );
			jQuery( '.wpmui-tier' ).addClass( 'ms-locked' );
			jQuery( '.wpmui-dripped' ).addClass( 'ms-locked' );
		} else {
			types.filter( '[value="tier"]' ).prop( 'disabled', false );
			types.filter( '[value="dripped"]' ).prop( 'disabled', false );
			jQuery( '.wpmui-tier' ).removeClass( 'ms-locked' );
			jQuery( '.wpmui-dripped' ).removeClass( 'ms-locked' );
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
