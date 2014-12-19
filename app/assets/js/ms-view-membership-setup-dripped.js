jQuery( document ).ready( function( $ ) {

	//global functions defined in ms-functions.js
	ms_functions.change_dripped_type = function( obj ) {
		var type = $( obj ).val();

		$( '.ms-dripped-edit-wrapper' ).hide();
		$( '.ms-dripped-type-' + type ).show();
	};
	ms_functions.change_access = function( access, slider ) {
		var type = $( 'input[name="dripped_type"]:checked').val();

		if( 1 == access ) {
			$( slider ).parent().parent().find( '.dripped.column-dripped' ).css( 'visibility', 'visible' );
		}
		else {
			$( slider ).parent().parent().find( '.dripped.column-dripped' ).css( 'visibility', 'hidden' );
		}
	};

	$( 'input[name="dripped_type"]').change( function() { ms_functions.change_dripped_type( this ) } );

	$( '.ms-dripped-spec-date' ).ms_datepicker();

	ms_functions.change_dripped_type( $( 'input[name="dripped_type"]:checked') );

	$( '.ms-dripped-calendar' ).click( function() {
		$( this ).parent().find( '.ms-dripped-spec-date.ms-ajax-update' ).datepicker( 'show' );
	});

	$( '.ms-period-desc-wrapper' ).click( function() {
		$( this ).parent().addClass( 'ms-dripped-edit' );
	});

	$( 'input.ms-dripped-edit-ok' ).click( function() {
		$( this ).parent().parent().removeClass( 'ms-dripped-edit' );
		period_unit = $( this ).parent().find( 'input' );
		period_type = $( this ).parent().find( 'select' );
		period_unit.change();
		period_type.change();
		$( this ).parent().parent().find( '.ms-period-unit' ).text( period_unit.val() );
		$( this ).parent().parent().find( '.ms-period-type' ).text( period_type.val() );
	});

	$( '.wpmui-radio-slider' ).on( 'wpmui-radio-slider-updated', function( event, data ) {
		ms_functions.change_access( data.value, event.target );
	});

	$( '.wpmui-radio-slider' ).each( function() {
		var value = ( true == $( this ).children( 'input' ).val() ) ? 1 : 0;
		ms_functions.change_access( value, this );
	});
});