/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_list = function init () {
	var toggles = jQuery( '.column-collapse', 'table.memberships' );

	function toggle_membership( event ) {
		var me = jQuery( this ),
			row = me.closest( 'tr' ),
			icon = me.find( '.toggle-children' ),
			is_alt = row.hasClass( 'alternate' ),
			action = icon.hasClass( 'wpmui-fa-caret-right' ) ? 'show' : 'hide';

		if ( action === 'show' ) {
			icon.removeClass( 'wpmui-fa-caret-right' );
			icon.addClass( 'wpmui-fa-caret-down' );
		} else {
			icon.removeClass( 'wpmui-fa-caret-down' );
			icon.addClass( 'wpmui-fa-caret-right' );
		}

		while( row = row.next() ) {
			if ( is_alt !== row.hasClass( 'alternate' ) ) { break; }
			if ( action === 'show' ) {
				row.show();
			} else {
				row.hide();
			}
		}
	}

	function add_handler() {
		var me = jQuery( this ),
			row = me.closest( 'tr' ),
			icon = me.find( '.toggle-children' );

		if ( ! icon.length ) { return; }
		if ( row.hasClass( 'ms-child-row' ) ) { return; }

		me.click( toggle_membership );
		me.addClass( 'ms-pointer' );
	}

	toggles.each(add_handler);

};