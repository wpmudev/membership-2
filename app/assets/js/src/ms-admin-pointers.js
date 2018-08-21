/**
 * Membership 2 Pro - v1.1.5
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2018; * Licensed GPLv2+
 *
 * global jQuery:false
 * global window:false
 * global document:false
 */

/**
 * Initialize all admin pointer.
 */
jQuery( window ).load( function () {
	var pointers = jQuery.parseJSON( window.MS_Admin_Pointers );

	for ( var pointer_key in pointers ) {
		var pointer = pointers[pointer_key];

		jQuery( document ).ready( function( $ ) {
			// jQuery selector to point the message to.
			$( pointer.target ).pointer({
				content: pointer.options.content,
				position: pointer.options.position,
				pointerWidth: 350,
				close: function() {
					$.post( ajaxurl, {
						pointer: pointer_key,
						action: 'dismiss-wp-pointer'
					});
				}
			}).pointer( 'open' );
		});
	}
} );
