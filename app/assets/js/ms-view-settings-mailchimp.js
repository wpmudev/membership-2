jQuery( document ).ready( function( $ ) {
	$( '#mailchimp_api_key' ).on( 'ms-ajax-updated', function() {
		location.reload();
	});
});
