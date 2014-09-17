jQuery( document ).ready( function( $ ) {
	var timeout = false;

	var ms_functions = {
		feedback: function( obj ) {
			var data = [], save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
			
			if( ! $( obj ).hasClass( processing_class ) ) {
				$( save_obj_selector ).addClass( processing_class );
				$( save_obj_selector ).removeClass( init_class );

				data = $( obj ).data( 'ms' );
				if( $( obj ).is( ':checkbox' ) ) {
					if( $( obj ).attr( 'checked' ) ) {
						data.value = true;
					}
					else {
						data.value = false;
					}
				}
				else {
					data.value = $( obj ).val();
				}
				
				$.post( ajaxurl, data, function( response ) {
					$( save_obj_selector ).removeClass( processing_class );
				});
			}
		},
		test_url: function() {
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
	}
	$( '#url_test, #rule_value' ).keyup( ms_functions.test_url );
	
	$( 'textarea.ms-ajax-update' ).change( function() { ms_functions.feedback( this ) } );
});
