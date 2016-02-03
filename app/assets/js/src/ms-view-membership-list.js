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

	// Triggered after any Membership details were modified via the edit popup.
	jQuery( document ).on( 'ms-ajax-form-done', function( ev, form, response, is_err, data ) {
		if ( ! is_err ) {
			// reload the page to reflect the update
			window.location.reload();
		}
	});
};

window.ms_init.bulk_delete_membership = function() {
    
    var delete_url = jQuery( '.bulk_delete_memberships_button' ).attr( 'href' );
    
    var serealize_membership_ids = function() {
        
        var membership_ids = [];
        jQuery( 'input.del_membership_ids:checked' ).each( function() {
            membership_ids.push( jQuery( this ).val() );
        } );
        
        if( membership_ids.length > 0 ){
            return delete_url + '&membership_ids=' + membership_ids.join( '-' );
        }else{
            return delete_url;
        }
        
    };
    
    function confirm_bulk_delete( ev ) {
            var args,
                    me = jQuery( this ),
                    row = me.parents( 'tr' ),
                    delete_url = me.attr( 'href' );

            ev.preventDefault();
            args = {
                    message: ms_data.lang.msg_bulk_delete,
                    buttons: [
                            ms_data.lang.btn_delete,
                            ms_data.lang.btn_cancel
                    ],
                    callback: function( key ) {
                            if ( key === 0 ) {
                                    window.location = serealize_membership_ids();
                            }
                    }
            };
            wpmUi.confirm( args );

            return false;
    }
    
    jQuery( '.bulk_delete_memberships_button' ).click( confirm_bulk_delete );
        
};