/*global jQuery:false */

jQuery(function() {
	var args = {
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
	};

	jQuery('.ms-form').validate(args);
});
