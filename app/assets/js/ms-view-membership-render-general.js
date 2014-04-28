jQuery( document ).ready(function( $ ) {
	$( '#membership_type').change( function() {
		$( '.ms-membership-type' ).hide();
		membership_type = $( this ).val();
		$( '#ms-membership-type-' + membership_type + '-wrapper').show();
		if( 'finite' == membership_type || 'date-range' == membership_type ) {
			$( '#ms-membership-on-end-membership-wrapper' ).show();
		}
		else {
			$( '#ms-membership-on-end-membership-wrapper' ).hide();
		}
	});
	$( '#period_date_start' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	$( '#period_date_end' ).datepicker({
        dateFormat : 'yy-mm-dd' //TODO get wp configured date format
    });
	function show_trial_period() {
		if( $( '#trial_period_enabled' ).is( ':checked' ) ) {
			$( '#ms-trial-period-wrapper' ).show();
		}
		else {
			$( '#ms-trial-period-wrapper' ).hide();
		}
	}
	
	$( '#trial_period_enabled' ).click( show_trial_period );

	show_trial_period();
	$( '#membership_type' ).change();
});
