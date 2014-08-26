jQuery( document ).ready( function( $ ) {
	
	$( '.chosen-select' ).chosen();
	$( '#category, #cpt_group' ).chosen().change( function() {
		var data = [], select_obj = this, save_obj_id = '#ms-save-text', processing_class = 'ms-processing';
		
		if( ! $( select_obj ).hasClass( processing_class ) ) {
			$( save_obj_id ).addClass( processing_class );

			data = $( select_obj ).data( 'ms' );
			data.rule_ids = $( select_obj ).val();
			data.rule_value = false;
			$.post( ajaxurl, data, function( response ) {
				$( save_obj_id ).removeClass( processing_class );
			});
		}
	});
	
	
});
