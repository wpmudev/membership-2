jQuery( document ).ready(function( $ ) {
	$( '#search_options').change( function() {
		if( 'membership' == $( '#search_options' ).val() ) {
			$( '#membership_filter' ).show();
			$( '#member-search' ).hide();
		}
		else {
			$( '#membership_filter' ).hide();
			$( '#member-search' ).show();
		}
	} );
	$( '#search_options').change();
});
