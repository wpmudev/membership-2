/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_overview = function init () {
	var ms_desc = jQuery( '.membership-description' ),
		ms_show_editor = ms_desc.find( '.show-editor' ),
		ms_readonly = ms_desc.find( '.readonly' ),
		ms_editor = ms_desc.find( '.editor' ),
		txt_editor = ms_editor.find( 'textarea' );

	jQuery( '.wpmui-radio-slider' ).on( 'wpmui-radio-slider-updated', function() {
		var object = this,
			obj = jQuery( '#ms-membership-status' );

		if( jQuery( object ).hasClass( 'on' ) ) {
			obj.addClass( 'ms-active' );
		}
		else {
			obj.removeClass( 'ms-active' );
		}
	});

	// Click on Read-Only description: Show the input field.
	ms_show_editor.click( function() {
		ms_readonly.addClass( 'hidden' );
		ms_editor.removeClass( 'hidden' );
		txt_editor.focus().data( 'dirty', false );
	});

	// When the editor loses focus: Hide the input field again.
	txt_editor
		.change(function(){
			txt_editor.data( 'dirty', true );
		})
		.blur(function() {
			if ( txt_editor.data( 'dirty' ) === true ) {
				return false;
			} else {
				ms_readonly.removeClass( 'hidden' );
				ms_editor.addClass( 'hidden' );
			}
		})
		.on(
			'ms-ajax-updated',
			function( ev, data, response, is_err ) {
				var desc = txt_editor.val();

				if ( is_err ) { return false; }

				ms_readonly.find( '.value' ).html( desc );
				ms_readonly.removeClass( 'hidden' );
				ms_editor.addClass( 'hidden' );
				ms_editor.find( '.okay, .error' ).removeClass( 'okay error' );

				if ( desc.length ) {
					ms_readonly.find( '.empty' ).addClass( 'hidden' );
				} else {
					ms_readonly.find( '.empty' ).removeClass( 'hidden' );
				}
			}
		);
};