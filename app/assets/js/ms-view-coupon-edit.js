jQuery( document ).ready(function( $ ) {
	$( '.ms-date' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	$('.ms-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'coupon_section[code]': 'required',
				'coupon_section[discount]': {
					'required': true,
					'min': 0,
				},
				'coupon_section[max_uses]': {
					'min': 0,
				},
				'coupon_section[start_date]': {
					'required': true,
					'dateISO': true,
				},
				'coupon_section[expire_date]': {
					'dateISO': true,
				},
			}
		});
});
