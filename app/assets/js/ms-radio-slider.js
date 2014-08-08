jQuery( document ).ready( function( $ ) {

	$( '.ms-radio-slider' ).click( function() {
		var object = this;
		var child = $( object ).find( ".toggle a" ).first();
        var value = child.attr( "href" );
		
		if( ! $( object ).hasClass( 'processing' ) ) {
			
			$( object ).addClass( 'processing' );
			
			if( $( object ).hasClass( 'on' ) ) {
	            $( object ).removeClass( 'on' );
	        } 
	        else { 
	            $( object ).addClass( 'on' );
	        }			
	        
			data = $( object ).children( '.ms-toggle' ).data( 'toggle' );
	        
			$.post( ajaxurl, data, function( response ) {
				$( object ).removeClass( 'processing' );
				$( object ).children( 'input' ).val( $( object ).hasClass( 'on' ) );
			});
		}
		
	});
	
});
