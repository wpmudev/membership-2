jQuery( document ).ready( function( $ ) {

	var ms_feedback = {
		feedback: function( obj ) {
			var data = [], save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
			
			if( ! $( obj ).hasClass( processing_class ) ) {
				$( save_obj_selector ).addClass( processing_class );
				$( save_obj_selector ).removeClass( init_class );
				
				data = $( obj ).data( 'ms' );
				data.value = $( obj ).val();
				$.post( ajaxurl, data, function( response ) {
					$( save_obj_selector ).removeClass( processing_class );
				});
			}
		}
	}

	$( '.chosen-select' ).chosen({disable_search_threshold: 5});
	
	$( '#currency' ).chosen().change( function() { ms_feedback.feedback( this ) } ); 
	
	$( '#invoice_sender_name' ).change( function() { ms_feedback.feedback( this ) } );
});