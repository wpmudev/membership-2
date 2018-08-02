/*! Membership 2 Pro - v1.1.5
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2018; * Licensed GPLv2+ */

(function(w,$){
	$(w).load(function(){
		var pointers = $.parseJSON( MS_Admin_Pointers );

		for ( var pointer_key in pointers ) {
			pointer = pointers[pointer_key];

			$( pointer.target ).pointer({
				content:		pointer.options.content,
				position:		pointer.options.position,
				pointerWidth:	350,
				close:			function() {
									$.post( ajaxurl, {
											pointer: pointer_key, // pointer ID
											action: 'dismiss-wp-pointer'
									});
								}
			}).pointer('open');

		}

	});
})(window,jQuery);