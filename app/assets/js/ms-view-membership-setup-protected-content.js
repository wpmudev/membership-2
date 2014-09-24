jQuery( document ).ready( function( $ ) {
	//global functions defined in ms-functions.js
	ms_functions.bulk_ajax_update = function( obj ) {
		var data = [], select_obj = obj, save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
		
		if( ! $( select_obj ).hasClass( processing_class ) ) {
			$( save_obj_selector ).addClass( processing_class );
			$( save_obj_selector ).removeClass( init_class );
			
			data = $( select_obj ).data( 'ms' );
			data.rule_ids = $( select_obj ).val();
			$.post( ajaxurl, data, function( response ) {
				$( save_obj_selector ).removeClass( processing_class );
			});
		}
	};
	
	$( '#category, #cpt_group' ).chosen().change( function() { ms_functions.bulk_ajax_update( this ) });
	
	$( '#comment' ).chosen().change( function() { ms_functions.ajax_update( this ) } );
	
	$( '#menu_id' ).change( function() {
		$( '#ms-menu-form' ).submit();
	});

	
});
