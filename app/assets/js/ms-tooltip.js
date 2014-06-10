jQuery( document ).ready(function( $ ) {

	$( document ).click( function() {
		$( '.ms-tooltip [display!="none"]' ).parent().fadeOut(100);
	});
	$( '.ms-tooltip' ).click( function(e) {
		e.stopPropagation();
	});
	$('.ms-tooltip-info').click( function( event ) {				
		if ( $( this ).hasClass( 'open' ) ) {
			$( this ).removeClass( 'open' );
			// $( this ).fadeOut(100);
		} else {
			$( this ).addClass( 'open' );
			event.stopPropagation();
			var tooltip = $( this ).siblings( '.ms-tooltip' );
			tooltip.css( "left", $( this ).position().left + 25 );
			tooltip.css( "top", $( this ).position().top - 12 );				
			tooltip.fadeIn(300);
		}
		
	} );
	
	$('.ms-tooltip-button').click( function() {
		$( this ).parent().fadeOut(100);
	} );

});
