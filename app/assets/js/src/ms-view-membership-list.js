/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_membership_list = function init () {
	var table = jQuery( '#the-list-membership' ),
		toggles = jQuery( '.column-collapse', table );

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

	function confirm_delete( ev ) {
		var args,
			me = jQuery( this ),
			row = me.parents( 'tr' ),
			name = row.find( '.column-name .the-name' ).text(),
			delete_url = me.attr( 'href' );

		ev.preventDefault();
		args = {
			message: ms_data.lang.msg_delete.replace( '%s', name ),
			buttons: [
				ms_data.lang.btn_delete,
				ms_data.lang.btn_cancel
			],
			callback: function( key ) {
				if ( key === 0 ) {
					window.location = delete_url;
				}
			}
		};
		wpmUi.confirm( args );

		return false;
	}

	table.on( 'click', '.delete a', confirm_delete );

	toggles.each(add_handler);

};