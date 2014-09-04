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
});
