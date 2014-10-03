/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_choose_type = function init () {
	var el_private = jQuery( '.ms-private-wrapper' ),
		ms_pointer = ms_data.ms_pointer;

	jQuery( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});

	jQuery( 'input[name="type"]' ).click( function() {
		if( jQuery.inArray( jQuery( this ).val(), ms_data.ms_private_types ) > -1 ) {
			el_private.removeClass( 'disabled' );
			el_private.find( 'input' ).prop( 'disabled', false );
		}
		else {
			el_private.addClass( 'disabled' );
			el_private.find( 'input' ).prop( 'disabled', true ).prop( 'checked', false );
		}
	});

	jQuery( 'input[name="type"]' ).first().click();

	// Cancel the wizard.
	jQuery( '#cancel' ).click( function() {
		var me = jQuery( this );

		// Simply reload the page after the setting has been changed.
		me.on( 'ms-ajax-updated', function() { window.location = window.location; } );
		ms_functions.ajax_update( me );
	});

	if( ! ms_pointer.hide_wizard_pointer ) {
		jQuery( '#adminmenu li' ).find( 'a[href="admin.php?page=protected-content-setup"]' ).pointer({
			content: ms_pointer.message,
			pointerClass: ms_pointer.pointer_class,
			position: {
				edge: 'left',
				align: 'center'
			},
			buttons: function( event, t ) {
				var close  = ( window.wpPointerL10n ) ? window.wpPointerL10n.dismiss : 'Dismiss',
					button = jQuery('<a class="close" href="#">' + close + '</a>');

				return button.bind( 'click.pointer', function(e) {
					e.preventDefault();
					t.element.pointer('close');
				});
			},
			close: function() {
				jQuery.post( window.ajaxurl, {
					field: ms_pointer.field,
					value: ms_pointer.value,
					action: ms_pointer.action,
					_wpnonce: ms_pointer.nonce,
				});
			}
		}).pointer( 'open' );
	}
};
