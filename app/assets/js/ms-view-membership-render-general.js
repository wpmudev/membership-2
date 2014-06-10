jQuery( document ).ready(function( $ ) {
			
	function ms_show_trial_period() {
		if( $( '#trial_period_enabled' ).is( ':checked' ) ) {
			$( '#ms-trial-period-wrapper' ).show();
		}
		else {
			$( '#ms-trial-period-wrapper' ).hide();
		}
	}

	$( '#membership_type').change( function() {
		$( '.ms-membership-type' ).hide();
		membership_type = $( this ).val();

		if ( 'permanent' == membership_type || 'recurring' == membership_type ) {
			$( '.ms-membership-type' ).parents('tr').hide();
		} else {
			$( '.ms-membership-type' ).parents('tr').show();
		}
		
		$( '#ms-membership-type-' + membership_type + '-wrapper').show();
		if( 'finite' == membership_type || 'date-range' == membership_type ) {
			$( '#ms-membership-on-end-membership-wrapper' ).parents('tr').show();
		}
		else {
			$( '#ms-membership-on-end-membership-wrapper' ).parents('tr').hide();
		}
	});
	$( '#period_date_start' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	$( '#period_date_end' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	
	$( '#trial_period_enabled' ).click( ms_show_trial_period );

	ms_show_trial_period();
	$( '#membership_type' ).change();
	
	$('.ms-form').validate({
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'membership_section[name]': 'required',
				'membership_section[price]': {
					'required': true,
					'min': 0,
				},
				'membership_section[period][period_unit]': {
					'required': true,
					'min': 1,
				},
				'membership_section[period_date_start]': {
					'required': true,
					'dateISO': true,
				},
				'membership_section[period_date_end]': {
					'required': true,
					'dateISO': true,
				},
				'membership_section[pay_cycle][period_unit]': {
					'required': true,
					'min': 1,
				},
				'membership_section[trial_price]': {
					'required': true,
					'min': 0,
				},
				'membership_section[trial_period][period_unit]': {
					'required': true,
					'min': 1,
				}
			}
		});
});
