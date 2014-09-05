jQuery( document ).ready( function( $ ) {

	$( '.ms-radio-slider' ).on( 'ms-radio-slider-updated', function() {
		var object = this, obj_selector = '#ms-membership-status', active_class = 'ms-active';
		
		if( $( object ).hasClass( 'on' ) ) {
			$( obj_selector ).addClass( active_class );
		}
		else {
			$( obj_selector ).removeClass( active_class );
		}
	});
});
