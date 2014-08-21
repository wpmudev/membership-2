jQuery( document ).ready( function( $ ) {

	$( '.dripped' ).click( function() {				
		var tooltip = $( this ).children( '.tooltip' );
		tooltip.toggle(300);
	} );	
	
});
