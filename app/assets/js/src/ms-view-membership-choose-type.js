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
