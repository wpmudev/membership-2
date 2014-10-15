jQuery( document ).ready(function( $ ) {
	$( '.ms-date' ).ms_datepicker();

	$('.ms-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'name': 'required',
				'user_id': {
					'required': true,
					'min': 1,
				},
				'membership_id': {
					'required': true,
					'min': 1,
				},
				'amount': {
					'required': true,
					'min': 0,
				},
				'due_date': {
					'required': true,
					'dateISO': true,
				},
			}
		});
});
