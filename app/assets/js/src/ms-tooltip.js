/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */


/* Tooltip component */
jQuery(function init_tooltip () {
	// Hide all tooltips when user clicks anywhere outside a tooltip element.
	jQuery( document ).click(function() {
		function hide_tooltip() {
			var el = jQuery( this ),
				stamp = el.attr('timestamp'),
				parent = jQuery( '.wpmui-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();

			el.hide();

			// Move tooltip back into the DOM hierarchy
			el.appendTo( jQuery( parent ) );
		}

		// Hide multiple tooltips
		jQuery( '.wpmui-tooltip[timestamp]').each( hide_tooltip );
	});

	// Hide single tooltip when Close-Button is clicked.
	jQuery( '.wpmui-tooltip-button' ).click(function() {
		var el = jQuery( this ),
			parent = el.parents( '.wpmui-tooltip' ),
			stamp = jQuery( parent ).attr( 'timestamp' ),
			super_parent = jQuery( '.wpmui-tooltip-wrapper[timestamp="' + stamp + '"]' ).first();

		jQuery( parent ).hide();

		// Move tooltip back into the DOM hierarchy
		jQuery( parent ).appendTo( jQuery( super_parent ) );
	});

	// Don't propagate click events inside the tooltip to the document.
	jQuery( '.wpmui-tooltip' ).click(function(e) {
		e.stopPropagation();
	});

	// Toggle a tooltip
	jQuery('.wpmui-tooltip-info').click(function( event ) {
		var parent, stamp, sibling, newpos, tooltip,
			el = jQuery( this );

		el.toggleClass( 'open' );

		if ( ! el.hasClass( 'open' ) ) {
			// HIDE
			parent = el.parents( '.wpmui-tooltip-wrapper' );
			stamp = jQuery( parent ).attr( 'timestamp' );
			sibling = jQuery( '.wpmui-tooltip[timestamp="' + stamp + '"]' ).first();

			jQuery( sibling ).hide();

			// Move tooltip back into the DOM hierarchy
			jQuery( sibling ).appendTo( jQuery( parent ) );
		} else {
			// SHOW
			el.parents('.wpmui-tooltip-wrapper').attr( 'timestamp', event.timeStamp );
			event.stopPropagation();
			tooltip = el.siblings( '.wpmui-tooltip' );

			tooltip.attr( 'timestamp', event.timeStamp );

			// Move tooltip out of the hierarchy...
			// This is to avoid situations where large tooltips are cut off by parent elements.
			newpos = el.offset();
			tooltip.appendTo( '#wpcontent' );
			tooltip.css({
				'left': newpos.left + 25,
				'top': newpos.top - 40
			});

			tooltip.fadeIn( 300 );
		}
	});

});
