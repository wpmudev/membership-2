jQuery( document ).ready(function( $ ) {
	$( '.chosen-select' ).chosen();
	$('#ms-authorize-extra-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'card_num': 'required',
				'card_code': 'required',
				'exp_month': 'required',
				'exp_year': 'required',
				'first_name': 'required',
				'last_name': 'required',
			}
		});
});
