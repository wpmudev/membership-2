jQuery( document ).ready( function( $ ) {

	var ms_functions = {
		feedback: function( obj ) {
			var data = [], save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
			
			if( ! $( obj ).hasClass( processing_class ) ) {
				$( save_obj_selector ).addClass( processing_class );
				$( save_obj_selector ).removeClass( init_class );

				data = $( obj ).data( 'ms' );
				if( $( obj ).is( ':checkbox' ) ) {
					if( $( obj ).attr( 'checked' ) ) {
						data.value = true;
					}
					else {
						data.value = false;
					}
				}
				else {
					data.value = $( obj ).val();
				}
				
				$.post( ajaxurl, data, function( response ) {
					$( save_obj_selector ).removeClass( processing_class );
				});
			}
		},
		change_dripped_type: function( obj ) {
			console.log("djo");
		}
	}
	
	$( 'input.ms-ajax-update, select.ms-ajax-update' ).change( function() { ms_functions.feedback( this ) } );
	
	$( 'input[name="dripped_type"]').change( function() { ms_functions.change_dripped_type( this ) } );

	$( '.ms-dripped-spec-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	ms_functions.change_dripped_type( $( 'input[name="dripped_type"]') );

	$( '.ms-dripped-calendar' ).click( function() {
		$( this ).parent().find( '.ms-dripped-spec-date.ms-ajax-update' ).datepicker( 'show' );
	});
	
	$( '.ms-dripped-pen' ).click( function() {
		$( this ).parent().parent().addClass( 'ms-dripped-edit' );
	});
	$( '.ms-dripped-edit-ok' ).click( function() {
		$( this ).parent().parent().removeClass( 'ms-dripped-edit' );
		period_unit = $( this ).parent().find( 'input' ).val();
		period_type = $( this ).parent().find( 'select' ).val();
		$( this ).parent().parent().find( '.ms-period-unit' ).text( period_unit );
		$( this ).parent().parent().find( '.ms-period-type' ).text( period_type );
	});
});