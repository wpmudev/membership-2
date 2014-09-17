jQuery( document ).ready( function( $ ) {

	$( '.ms-radio-slider' ).click( function() {
		var object = this, save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init', value = 0, data = null;
		
		if( ! $( object ).hasClass( 'processing' ) ) {
			$( save_obj_selector ).addClass( processing_class );
			$( save_obj_selector ).removeClass( init_class );

			$( object ).addClass( processing_class );
			
			if( $( object ).hasClass( 'on' ) ) {
	            $( object ).removeClass( 'on' );
	            value = 0;
	        } 
	        else { 
	            $( object ).addClass( 'on' );
	            value = 1;
	        }			
	        
			data = $( object ).children( '.ms-toggle' ).data( 'toggle' );

			if( ! data ) {
				data = $( object ).children( '.ms-toggle' ).data( 'ms' );
				data.value = value;
			}
	        
			$.post( ajaxurl, data, function( response ) {
				$( object ).removeClass( processing_class );
				$( save_obj_selector ).removeClass( processing_class );
				$( object ).children( 'input' ).val( $( object ).hasClass( 'on' ) );
				$( object ).trigger( "ms-radio-slider-updated" );
			});
		}
		
	});
	
});
