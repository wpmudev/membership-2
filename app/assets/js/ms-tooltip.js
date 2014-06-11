jQuery( document ).ready(function( $ ) {

	$( document ).click( function() {		
		// Hide multiple tooltips
		$( '.ms-tooltip[timestamp]').each( function( index, element ) {
			var stamp = $( element ).attr('timestamp');
			var parent = $( '.ms-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();
			$( element ).hide(100);
			
			// Move tooltip back into the DOM hierarchy
		    $( element ).appendTo( $( parent ) );
		});
	});
	
	$( '.ms-tooltip' ).click( function(e) {
		e.stopPropagation();
	});
	
	$('.ms-tooltip-info').click( function( event ) {				
		
		if( $( this ).hasClass( 'open' ) ) {
			
			$( this ).removeClass( 'open' );
			
			var parent = $( this ).parents('.ms-tooltip-wrapper');
			var stamp = $( parent ).attr( 'timestamp' );
			var sibling = $( '.ms-tooltip[timestamp="' + stamp + '"]' ).first();

			$( sibling ).fadeOut(100);

			// Move tooltip back into the DOM hierarchy
			$( sibling ).appendTo( $( parent ) );	
					
		} else {
			$( this ).addClass( 'open' );
			$( this ).parents('.ms-tooltip-wrapper').attr( 'timestamp', event.timeStamp );
			event.stopPropagation();
			var tooltip = $( this ).siblings( '.ms-tooltip' );
			
			tooltip.attr( 'timestamp', event.timeStamp );
			
			// Move tooltip out of the hierarchy...  
			// This is to avoid situations where large tooltips are cut off by parent elements.
			var newpos = $( this ).offset();
			tooltip.appendTo( '#wpcontent' );
			tooltip.css( "left", newpos.left + 25 );
			tooltip.css( "top", newpos.top - 45 );

			tooltip.fadeIn(300);
		}
	} );
	
	$('.ms-tooltip-button').click( function() {

		var parent = $( this ).parents('.ms-tooltip');
		var stamp = $( parent ).attr( 'timestamp' );
		var super_parent = $( '.ms-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();
		
		$( parent ).fadeOut(100);
		
		// Move tooltip back into the DOM hierarchy
		$( parent ).appendTo( $( super_parent ) );
		
	} );

});
