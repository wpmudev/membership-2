/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_dripped = function init () {

	//global functions defined in ms-functions.js
	ms_functions.change_dripped_type = function( obj ) {
		var type = jQuery( obj ).val();

		jQuery( '.ms-dripped-edit-wrapper' ).hide();
		jQuery( '.ms-dripped-type-' + type ).show();
	};

	ms_functions.change_access = function( access, slider ) {
		var type = jQuery( 'input[name="dripped_type"]:checked').val();

		if ( access ) {
			jQuery( slider ).parent().parent().find( '.dripped.column-dripped' ).css( 'visibility', 'visible' );
		} else {
			jQuery( slider ).parent().parent().find( '.dripped.column-dripped' ).css( 'visibility', 'hidden' );
		}
	};

	jQuery( 'input[name="dripped_type"]').change( function() { ms_functions.change_dripped_type( this ); } );

	jQuery( '.ms-dripped-spec-date' ).ms_datepicker();

	ms_functions.change_dripped_type( jQuery( 'input[name="dripped_type"]:checked') );

	jQuery( '.ms-dripped-calendar' ).click( function() {
		jQuery( this ).parent().find( '.ms-dripped-spec-date.wpmui-ajax-update' ).datepicker( 'show' );
	});

	jQuery( '.ms-period-desc-wrapper' ).click( function() {
		jQuery( this ).parent().addClass( 'ms-dripped-edit' );
	});

	jQuery( 'input.ms-dripped-edit-ok' ).click( function() {
		var period_unit, period_type;

		jQuery( this ).parent().parent().removeClass( 'ms-dripped-edit' );
		period_unit = jQuery( this ).parent().find( 'input' );
		period_type = jQuery( this ).parent().find( 'select' );
		period_unit.change();
		period_type.change();
		jQuery( this ).parent().parent().find( '.ms-period-unit' ).text( period_unit.val() );
		jQuery( this ).parent().parent().find( '.ms-period-type' ).text( period_type.val() );
	});

	jQuery( '.wpmui-radio-slider' ).on( 'ms-radio-slider-updated', function( event, data ) {
		ms_functions.change_access( data.value, event.target );
	});

	jQuery( '.wpmui-radio-slider' ).each( function() {
		var value = ( jQuery( this ).children( 'input' ).val() ) ? 1 : 0;
		ms_functions.change_access( value, this );
	});
};