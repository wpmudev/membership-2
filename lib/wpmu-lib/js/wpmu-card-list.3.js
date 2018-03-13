/*! WPMU Dev code library - v3.0.4
 * http://premium.wpmudev.org/
 * Copyright (c) 2017; * Licensed GPLv2+ */
/*!
 * WPMU Dev Card List
 * (Philipp Stracker for WPMU Dev)
 *
 * @since    1.1.0
 * @author   Philipp Stracker for WPMU Dev
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global XMLHttpRequest:false */

(jQuery(function() {
	function adjust_height( event ) {
		var list = jQuery( '.wpmui-list-table.has-details' ),
			item = list.find( '.list-card.detail-mode' ),
			item_height = jQuery( window ).innerHeight() - 132;

		if ( item.length ) {
			item.height( item_height );
		}
	}

	function toggle_details( event ) {
		var item = jQuery( this ).parents( '.list-card' ),
			list = item.parents( '.wpmui-list-table' ),
			item_height = jQuery( window ).innerHeight() - 132;

		if ( list.hasClass( 'has-details' ) ) {
			close_details();
		} else {
			window.scrollTo( 0, 0 );
			list.addClass( 'has-details' );
			item.addClass( 'detail-mode' );
			item.height( item_height );
			item.hide();

			jQuery( window ).on( 'resize', adjust_height );
			adjust_height();

			window.setTimeout(function(){
				item.show();
			}, 10);
		}
	}

	function close_details( event ) {
		var close_it = false;

		// Function was called directly without param.
		if ( undefined === event ) { close_it = true; }
		if ( undefined !== event && undefined !== event.target ) {
			var target = jQuery( event.target );
			// User clicked on the modal background behind the card.
			if ( target.hasClass( 'wpmui-list-table' ) ) { close_it = true; }
		}

		if ( ! close_it ) { return; }

		var item = jQuery( '.list-card.detail-mode' ),
			list = jQuery( '.wpmui-list-table.has-details' );

		list.removeClass( 'has-details' );
		item.removeClass( 'detail-mode' );
		item.height( 'auto' );

		jQuery( window ).off( 'resize', adjust_height );
	}

	function update_status( event, data, is_err ) {
		var me = jQuery( this ),
			item = me.parents( '.list-card' );

		// Ignore state changes of detail elements.
		if ( true === data._is_detail ) { return; }

		if ( data.value ) {
			item.addClass( 'active' );
		} else {
			item.removeClass( 'active' );
		}
	}

	function filter_items( event ) {
		var me = jQuery( this ),
			filter = me.data( 'filter' ),
			items = jQuery( '.list-card' ),
			current = me.parents( '.wp-filter' ).find( '.current' );

		current.removeClass( 'current' );
		me.addClass( 'current' );

		switch ( filter ) {
			case 'active':
				items.hide().filter( '.active' ).show();
				break;

			case 'inactive':
				items.show().filter( '.active' ).hide();
				break;

			case 'all':
				items.show();
				break;

			default:
				/**
				 * Allow custom filtering by observing the event.
				 *
				 * @since  1.1.0
				 *
				 * @param string filter The filter-value.
				 * @param jQuery[] items A list of all items in the list.
				 */
				jQuery( document ).trigger( 'list-filter', [filter, items] );
				break;
		}

		return false;
	}

	jQuery( document ).on( 'wpmui-radio-slider-updated', '.wpmui-radio-slider', update_status );
	jQuery( document ).on( 'click', '.toggle-details', toggle_details );
	jQuery( document ).on( 'click', '.has-details', close_details );

	jQuery( document ).on( 'click', '.wp-filter .filter', filter_items );

}));

