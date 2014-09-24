jQuery( document ).ready( function( $ ) {

	//global functions defined in ms-functions.js
	ms_functions.change_dripped_type = function( obj ) {
		var type = $( obj ).val();
		
		$( '.ms-dripped-edit-wrapper' ).hide();
		$( '.ms-dripped-type-' + type ).show();
	}
		
	$( 'input[name="dripped_type"]').change( function() { ms_functions.change_dripped_type( this ) } );

	$( '.ms-dripped-spec-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	ms_functions.change_dripped_type( $( 'input[name="dripped_type"]:checked') );

	$( '.ms-dripped-calendar' ).click( function() {
		$( this ).parent().find( '.ms-dripped-spec-date.ms-ajax-update' ).datepicker( 'show' );
	});
	
	$( '.ms-period-desc-wrapper' ).click( function() {
		$( this ).parent().addClass( 'ms-dripped-edit' );
	});
	
	$( 'input.ms-dripped-edit-ok' ).click( function() {
		$( this ).parent().parent().removeClass( 'ms-dripped-edit' );
		period_unit = $( this ).parent().find( 'input' ).val();
		period_type = $( this ).parent().find( 'select' ).val();
		$( this ).parent().parent().find( '.ms-period-unit' ).text( period_unit );
		$( this ).parent().parent().find( '.ms-period-type' ).text( period_type );
	});
	
});