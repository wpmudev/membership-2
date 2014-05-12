jQuery( document ).ready( function( $ ) {

	$( '.ms-radio-slider' ).click( function() {
        if ( $( this ).hasClass( 'on' ) ) {
            $( this ).removeClass( 'on' );
            $( this ).children('input').val( 0 );
        } 
        else { 
            $( this ).addClass( 'on' );
            $( this ).children('input').val( 1 );
        } 
	});
});
