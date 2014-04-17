/**
 * Add rule 
 * TODO http://stackoverflow.com/questions/2196036/jquery-the-right-way-to-add-a-child-element
 */
jQuery( document ).ready(function( $ ) {
	$( '#membership_type').change( function() {
		$( '.ms-membership-type' ).hide();
		membership_type = $( this ).val();
		$( '#ms-membership-type-' + membership_type ).show();
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
