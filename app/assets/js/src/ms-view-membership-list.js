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

/*jshint browser: true */
jQuery( function( $ ){
    
    var href = $( '.bulk_delete_memberships_button' ).attr( 'href' );
    var serealize_membership_ids = function() {
        setTimeout( function() {
            var membership_ids = [];
            $( 'input.del_membership_ids:checked' ).each( function() {
                membership_ids.push( $( this ).val() );
            } );
            
            if( membership_ids.length > 0 ){
                $( '.bulk_delete_memberships_button' ).attr( 'href', href + '&membership_ids=' + membership_ids.join( '-' ) );
            }else{
                $( '.bulk_delete_memberships_button' ).attr( 'href', href );
            }
            
        }, 500 );
    };
    
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
    
    $( '.del_membership_ids' ).click( serealize_membership_ids );
    $( '.toplevel_page_membership2 th.column-cb input' ).click( serealize_membership_ids );
    $( document ).on( 'click', '.bulk_delete_memberships_button', confirm_delete );
        
} );