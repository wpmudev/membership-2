jQuery(document).ready(function($){
	$('#wp-admin-bar-membership-simulate').find('a').click(function(e){
		$('#wp-admin-bar-membership-simulate').removeClass('hover').find('> div').filter(':first-child').html( ms.switching_text );
	});
	$( '.ms-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });

	$( '#wpadminbar #view-site-as' ).parents( '#wpadminbar' ).addClass('simulation-mode');

	$( '#wpadminbar #view-as-selector' ).change( function( element ) { 

		// Get selected Membership ID
		var membership_id = element.currentTarget.value;
		// Get selected Membership nonce
		var nonce = $( '#wpadminbar #view-as-selector' ).find( 'option[value="' + membership_id + '"]' ).attr( 'nonce' );

		// // Update hidden fields
		$( '#wpadminbar #ab-membership-id' ).val( membership_id );
		$( '#wpadminbar #view-site-as #_wpnonce' ).val( nonce );

		// Submit form
		$( '#wpadminbar #view-site-as' ).submit();
		
	} );

});