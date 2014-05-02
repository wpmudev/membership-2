jQuery( document ).ready(function( $ ) {
	ms = { 
			counter: $( '#the-list-rule_dripped tr' ).size(),
			id: 0,
			full_id: '',
			title: '',
			type: '',
			period_unit: 0,
			period_type: '',
			css_class: 'alternate',
		};
	$( 'input[name="item[type]"]').change( function() {
		$( '.ms-rule-type-wrapper' ).hide();
		ms.type = $( this ).val();
		$( '#ms-rule-type-' + ms.type + '-wrapper').show();
	});
	$( 'input[name="item[type]"]:checked' ).change();
	
	$( '#btn_add' ).click( function() {
		ms.id = $( '.ms-rule-type-wrapper:visible select' ).val();
		ms.full_id = ms.type + '_' + ms.id;
		ms.title = $( '.ms-rule-type-wrapper:visible option[value="' + ms.id + '"]' ).text();
		ms.period_unit = $( '#period_unit' ).val();
		ms.period_type = $( '#period_type' ).val();
		
		dripped = [
			           {
			        	   id: ms.id,
			        	   full_id: ms.full_id,
			        	   title: ms.title,
			        	   type: ms.type,
			        	   period_unit: ms.period_unit,
			        	   period_type: ms.period_type,
			        	   counter: ms.counter++,
			        	   css_class: ( ms.counter % 2 ) ? ms.css_class : '',
			           }
		           ];
		if( ms.period_unit && ! $( '#' + ms.full_id ).attr( 'id' ) ) {
			$( 'tr' ).remove( '.no-items' );
			$( '#dripped_template' ).tmpl( dripped ).appendTo( '#the-list-rule_dripped' );
		}
	});
	
	$( '.ms-delete' ).live( 'click', function() {
		$( this ).parent().parent().remove();
	});
});
