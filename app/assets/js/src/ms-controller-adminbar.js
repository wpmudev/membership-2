/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.controller_adminbar = function init () {

	function change_membership( ev ) {
		// Get selected Membership ID
		var membership_id = ev.currentTarget.value;
		// Get selected Membership nonce
		var nonce = jQuery( '#wpadminbar #view-as-selector' )
			.find( 'option[value="' + membership_id + '"]' )
			.attr( 'nonce' );

		// Update hidden fields
		jQuery( '#wpadminbar #ab-membership-id' ).val( membership_id );
		jQuery( '#wpadminbar #view-site-as #_wpnonce' ).val( nonce );

		// Submit form
		jQuery( '#wpadminbar #view-site-as' ).submit();
	}

	jQuery('#wp-admin-bar-membership-simulate').find('a').click(function(e){
		jQuery('#wp-admin-bar-membership-simulate')
			.removeClass('hover')
			.find('> div')
			.filter(':first-child')
			.html( ms_data.switching_text );
	});

	jQuery( '.ms-date' ).ms_datepicker();

	jQuery( '#wpadminbar #view-site-as' )
		.parents( '#wpadminbar' )
		.addClass('simulation-mode');

	jQuery( '#wpadminbar #view-as-selector' ).change( change_membership );

};