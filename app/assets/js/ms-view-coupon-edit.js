jQuery( document ).ready(function( $ ) {
	$( '.ms-date' ).ms_datepicker();

	$('.ms-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'code': 'required',
				'discount': {
					'required': true,
					'min': 0,
				},
				'max_uses': {
					'min': 0,
				},
				'start_date': {
					'required': true,
					'dateISO': true,
				},
				'expire_date': {
					'dateISO': true,
				},
			}
		});
});
