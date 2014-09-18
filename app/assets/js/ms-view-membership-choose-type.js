jQuery( document ).ready(function( $ ) {
	
	$( '#ms-choose-type-form' ).validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'name': {
				'required': true,
			}
		}
	});
	
	$( 'input[name="type"]' ).click( function() {
		if( $.inArray( $( this ).val(), ms_data.ms_private_types ) > -1 ) {
			$( '.ms-private-wrapper' ).show();
		}
		else {
			$( '.ms-private-wrapper' ).hide();
		}
	});
	
	$( 'input[name="type"]' ).first().click();
	
	var ms_pointer = ms_data.ms_pointer;
	if( ! ms_pointer.hide_wizard_pointer ) {
		
	    $( '#adminmenu li' ).find( 'a[href="admin.php?page=protected-content-setup"]' ).pointer({
	        content: ms_pointer.message,
	        pointerClass: ms_pointer.pointer_class,
	        position: {
	            edge: 'left',
	            align: 'center'
	        },
	        buttons: function( event, t ) {
				var close  = ( wpPointerL10n ) ? wpPointerL10n.dismiss : 'Dismiss',
					button = $('<a class="close" href="#">' + close + '</a>');
	
				return button.bind( 'click.pointer', function(e) {
					e.preventDefault();
					t.element.pointer('close');
				});
			},
	        close: function() {
	        	$.post( ajaxurl, {
		            field: ms_pointer.field,
		            value: ms_pointer.value,
		            action: ms_pointer.action,
		            _wpnonce: ms_pointer.nonce,
	        	});
	        }
	    }).pointer( 'open' );
	}
});
