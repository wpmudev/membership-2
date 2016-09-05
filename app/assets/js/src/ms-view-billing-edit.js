/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_billing_edit = function init () {
	var args = {
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
	};

	jQuery('.ms-form').validate(args);
};
