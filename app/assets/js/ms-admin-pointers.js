/*! Membership 2 Pro - v1.1.6
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2019; * Licensed GPLv2+ */
/*! Membership 2 Pro - v1.1.5
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2018; * Licensed GPLv2+ */
/*global jQuery:false */
/*global window:false */
/*global document:false */

/**
 * Close admin pointer and set flag.
 *
 * @param pointer_key
 */
function ms_close_admin_pointer( pointer_key ) {
	jQuery.post( window.ajaxurl, {
		pointer: pointer_key, // Pointer ID.
		action: 'dismiss-wp-pointer'
	} );
}

// After window loaded, initialize all admin pointers.
jQuery( window ).load( function () {
	var pointers = jQuery.parseJSON( window.MS_Admin_Pointers );

	for ( var pointer_key in pointers ) {
		var pointer = pointers[pointer_key];

		// Initialize all admin pointer.
		jQuery( pointer.target ).pointer( {
			content: pointer.options.content,
			position: pointer.options.position,
			pointerWidth: 350,
			close: ms_close_admin_pointer( pointer_key )
		} ).pointer( 'open' );
	}
} );
