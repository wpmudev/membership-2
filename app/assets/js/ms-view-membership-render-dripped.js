jQuery( document ).ready(function( $ ) {
	$( 'input[name="membership_section[type]"]:radio').change( function() {
		$( '.ms-rule-type-wrapper' ).hide();
		type = $( this ).val();
		$( '#ms-rule-type-' + type + '-wrapper').show();
	});
	$( '#type' ).change();
});
