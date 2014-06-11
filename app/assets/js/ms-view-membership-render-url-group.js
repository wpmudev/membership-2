jQuery( document ).ready( function( $ ) {
	var timeout = false;

	function test_url() {
		if( timeout ) {
			clearTimeout( timeout );
		}

		timeout = setTimeout(function() {
			var container = $( '#url-test-results-wrapper' ),
				url = $.trim($( '#url_test' ).val() ),
				rules = $( '#rule_value' ).val().split( "\n" );

			if ( url == '' ) {
				container.html('<div><i>' + ms.nothing_msg + '</i></div>');
				return;
			}

			container.empty();
			
			$.each( rules, function( i, rule ) {
				var line, result, reg;

				rule = $.trim(rule);
				if (rule == '') {
					return;
				}

				result = $( '<span></span>' );

				line = $( '<div></div>' );
				line.html( rule );
				line.append( result );

				reg = new RegExp( rule, 'i' );
				if ( reg.test( url ) ) {
					line.addClass( 'ms-rule-valid' );
					result.text( ms.valid_rule_msg );
				} 
				else {
					line.addClass( 'ms-rule-invalid' );
					result.text( ms.invalid_rule_msg );
				}

				container.append( line );
			});

			if ( container.find( '> div' ).length == 0 ) {
				container.html( '<div><i>' + ms.empty_msg + '</i></div>' );
				return;
			}
		}, 500);
	}
	$( '#url_test, #rule_value' ).keyup( test_url );
	
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
