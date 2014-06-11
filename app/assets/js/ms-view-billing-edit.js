jQuery( document ).ready(function( $ ) {
	$( '.ms-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	$('.ms-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'membership_section[name]': 'required',
				'billing_section[amount]': {
					'required': true,
					'min': 0,
				},
				'billing_section[tax_rate]': {
					'min': 0,
				},
				'billing_section[due_date]': {
					'required': true,
					'dateISO': true,
				},
			}
		});
});
