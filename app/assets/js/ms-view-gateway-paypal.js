jQuery( document ).ready(function( $ ) {
	locked = false;
	$( '#submit' ).click( function(e) {	
		if( locked ) {
			return false;
		}

		locked = true;
		$( 'html' ).css( 'cursor', 'wait' );
		
		$.ajax( {
			url: ms_paypal.return_url,
			type: 'POST',
			dataType: 'json',
			data: $( '#pre-create-transaction-form' ).serialize(),
			success: function( data ) {
				locked = false;
				$( 'html' ).css( 'cursor', 'default' );
				if ( data ) {
					$( '#custom' ).val( $( '#custom' ).val() + ':' + data );
					$( '#ms-paypal-form' ).submit();
				}
				else {
					alert( ms_paypal.error_msg );
					return false;
				}
			},
			error: function() {
				locked = false;
				$( 'html' ).css( 'cursor', 'default') ;
				alert( ms_paypal.error_msg );
				return false;
			}
		} );
		return false;
	} );
} );
