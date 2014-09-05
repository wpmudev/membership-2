jQuery( document ).ready( function( $ ) {
	var ms_feedback = {
		feedback: function( obj ) {
			var data = [], save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
			
			if( ! $( obj ).hasClass( processing_class ) ) {
				$( save_obj_selector ).addClass( processing_class );
				$( save_obj_selector ).removeClass( init_class );
				
				data = $( obj ).data( 'ms' );
				data.rule_value = $( obj ).val();
				$.post( ajaxurl, data, function( response ) {
					$( save_obj_selector ).removeClass( processing_class );
				});
			}
		}
	}
	$( '.chosen-select' ).chosen({disable_search_threshold: 5});
	$( '#category, #cpt_group' ).chosen().change( function() { 
		var data = [], select_obj = this, save_obj_selector = '.ms-save-text-wrapper', processing_class = 'ms-processing', init_class = 'ms-init';
		
		if( ! $( select_obj ).hasClass( processing_class ) ) {
			$( save_obj_selector ).addClass( processing_class );
			$( save_obj_selector ).removeClass( init_class );
			
			data = $( select_obj ).data( 'ms' );
			data.rule_ids = $( select_obj ).val();
			$.post( ajaxurl, data, function( response ) {
				$( save_obj_selector ).removeClass( processing_class );
			});
		}
	});
	
	$( 'input[name="more_tag"]' ).change( function() { ms_feedback.feedback( this ) } );
	$( '#comment' ).chosen().change( function() { ms_feedback.feedback( this ) } );
	
	$( '#menu_id' ).change( function() {
		$( '#ms-menu-form' ).submit();
	});

	
});
