/*! Membership 2 Pro - v1.1.6
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2018; * Licensed GPLv2+ */
/*! Membership 2 Pro - v1.1.5
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2018; * Licensed GPLv2+ */
/*global jQuery:false */
/*global window:false */
/*global document:false */

jQuery(window).load(function(){
	var pointers = jQuery.parseJSON( window.MS_Admin_Pointers );

	for ( var pointer_key in pointers ) {
		var pointer = pointers[pointer_key];		

		jQuery( pointer.target ).pointer({
			content:		pointer.options.content,
			position:		pointer.options.position,
			pointerWidth:	350,
			close:			function(pointer_key) {
								jQuery.post( window.ajaxurl, {
										pointer: pointer_key, // pointer ID
										action: 'dismiss-wp-pointer'
								});
							}
		}).pointer('open');

	}

});
