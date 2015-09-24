/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_add = function init () {
	var chk_public = jQuery( 'input#public' ),
		el_public = chk_public.closest( '.opt' ),
		chk_paid = jQuery( 'input#paid' ),
		inp_name = jQuery( 'input#name' ),
		el_name = inp_name.closest( '.opt' ),
		el_paid = chk_paid.closest( '.opt' );

	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	// Lock the options then guest membership is selected.
	jQuery( 'input[name="type"]' ).click(function() {
		var types = jQuery( 'input[name="type"]' ),
			current = types.filter( ':checked' ),
			cur_type = current.val();

		types.closest( '.wpmui-radio-input-wrapper' ).removeClass( 'active' );
		current.closest( '.wpmui-radio-input-wrapper' ).addClass( 'active' );

		if ( 'guest' === cur_type || 'user' === cur_type ) {
			chk_public.prop( 'disabled', true );
			chk_paid.prop( 'disabled', true );
			inp_name.prop( 'readonly', true );
			el_public.addClass( 'disabled ms-locked' );
			el_paid.addClass( 'disabled ms-locked' );
			el_name.addClass( 'disabled ms-locked' );
			inp_name.val( current.next( '.wpmui-radio-caption' ).text() );
		} else {
			chk_public.prop( 'disabled', false );
			chk_paid.prop( 'disabled', false );
			inp_name.prop( 'readonly', false );
			el_public.removeClass( 'disabled ms-locked' );
			el_paid.removeClass( 'disabled ms-locked' );
			el_name.removeClass( 'disabled ms-locked' );
			inp_name.val( '' ).focus();
		}
	}).filter( ':checked' ).trigger( 'click' );

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
