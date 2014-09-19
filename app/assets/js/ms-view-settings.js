jQuery( document ).ready( function( $ ) {
	$( '#comm_type' ).change( function() {
		$( '#ms-comm-type-form' ).submit();
	});
	$( '.chosen-select.ms-ajax-update' ).on( 'ms-ajax-updated', function( event, data ) {
		var page_id = $(this).val(), page_url = null, page_edit_url = null;

		page_url = $( '#page_urls option[value="' + page_id + '"]' ).text();
		page_url = ( page_url ) ? page_url : '#'; 
		$( '#url_' + data.field ).attr( 'href', page_url );

		page_edit_url = $( '#page_edit_urls option[value="' + page_id + '"]' ).text();
		page_edit_url = ( page_url ) ? page_url : '#'; 

		$( '#edit_url_' + data.field ).attr( 'href', page_edit_url );
	});
});