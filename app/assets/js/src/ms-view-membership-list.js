/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */
/*global wpmUi:false */

window.ms_init.view_membership_list = function init () {
	var table = jQuery( '#the-list-membership' );

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

};