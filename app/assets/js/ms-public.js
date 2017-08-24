/*! Membership 2 Pro - v1.0.4
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2017; * Licensed GPLv2+ */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init = window.ms_init || {};

jQuery(function() {
	var i;

	window.ms_init._done = window.ms_init._done || {};

	function initialize( callback ) {
		if ( undefined !== callback && undefined !== window.ms_init[callback] ) {
			// Prevent multiple calls to init functions...
			if ( true === window.ms_init._done[callback] ) { return false; }

			window.ms_init._done[callback] = true;
			window.ms_init[callback]();
		}
	}

	if ( undefined === window.ms_data ) { return; }

	if ( undefined !== ms_data.ms_init ) {
		if ( ms_data.ms_init instanceof Array ) {
			for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
				initialize( ms_data.ms_init[i] );
			}
		} else {
			initialize( ms_data.ms_init );
		}

		// Prevent multiple calls to init functions...
		ms_data.ms_init = [];
	}
});

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.shortcode = function init () {
	jQuery( '.ms-membership-form .membership_cancel' ).click( function() {
		if ( window.confirm( ms_data.cancel_msg ) ) {
			return true;
		} else {
			return false;
		}
	});
};

/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.frontend_profile = function init () {
	var args = {
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			'email': {
				'required': true,
				'email': true,
			},
			'password': {
				'minlength': 5,
			},
			'password2': {
				'equalTo': '.ms-form-element #password',
			},
		},
	};

	jQuery( '#ms-view-frontend-profile-form' ).validate(args);
};

window.ms_init.frontend_register = function init()
{
    var $ = jQuery,
        _options = '',
		first_last_name;
        
    if( $( '#ms-shortcode-register-user-form #display_name' ).length )
    {
        $( document ).on( 'blur', '#username', function() {
            var username = $( this ).val();
            if( username.trim() === '' )
            {
                $( '#display_username_option' ).remove();
            }
            else
            {
                if( $( '#display_username_option' ).length )
                {
                    $( '#display_username_option' ).attr( 'value', username ).text( username );
                }
                else
                {
                    $( '#display_name' ).append( '<option id="display_username_option" value="' + username + '">' + username + '</option>' );
                }
            }
        } );
        
        if( $( '#first_name' ).length )
        {
            $( document ).on( 'blur', '#first_name', function() {
                var first_name = $( this ).val();
                if( first_name.trim() === '' )
                {
                    $( '#display_first_name_option' ).remove();
                }
                else
                {
                    if( $( '#display_first_name_option' ).length )
                    {
                        $( '#display_first_name_option' ).attr( 'value', first_name ).text( first_name );
                    }
                    else
                    {
                        $( '#display_name' ).append( '<option id="display_first_name_option" value="' + first_name + '">' + first_name + '</option>' );
                    }
                }
                first_last_name();
            } );
        }
        
        if( $( '#last_name' ).length )
        {
            $( document ).on( 'blur', '#last_name', function() {
                var last_name = $( this ).val();
                if( last_name.trim() === '' )
                {
                    $( '#display_last_name_option' ).remove();
                }
                else
                {
                    if( $( '#display_last_name_option' ).length )
                    {
                        $( '#display_last_name_option' ).attr( 'value', last_name ).text( last_name );
                    }
                    else
                    {
                        $( '#display_name' ).append( '<option id="display_last_name_option" value="' + last_name + '">' + last_name + '</option>' );
                    }
                }
                first_last_name();
            } );
        }
        
        first_last_name = function()
        {
            var fname = $( '#first_name' ).val(),
                lname = $( '#last_name' ).val();
                
            if( fname.trim() === '' || lname.trim() === '' )
            {
                $( '#display_first_last_option' ).remove();
                return;
            }
            
            var name = fname + ' ' + lname,
                rname = lname + ' ' + fname;
            
            $( '#display_name' ).append( '<option id="display_first_last_option" value="' + name + '">' + name + '</option>' );
            $( '#display_name' ).append( '<option id="display_first_last_option" value="' + rname + '">' + rname + '</option>' );
        };
        
    }
    
};
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.gateway_authorize = function init () {
	var last_cc_num = '',
		profiles = jQuery( '#ms-authorize-cim-profiles-wrapper' ),
		new_card = jQuery( '#ms-authorize-card-wrapper' ),
		args = {
			onkeyup: false,
			errorClass: 'ms-validation-error',
			rules: {
				'card_num': 'required',
				'card_code': 'required',
				'exp_month': 'required',
				'exp_year': 'required',
				'first_name': 'required',
				'last_name': 'required',
			}
		};

	jQuery( '.ms-select' ).wpmuiSelect({
		width: 'auto',
		minimumResultsForSearch: 30
	});

	// Insert a space after 4 digits of the CC number
	jQuery( '.wpmui-input-card_num' ).keyup(function() {
		var cc_num = jQuery( this ).val().replace( /\D/g, "" );
		if ( cc_num !== last_cc_num && cc_num.length > 0 ) {
			last_cc_num = cc_num;
			cc_num = cc_num.match( /.{1,4}/g ).join( " " );
			jQuery( this ).val( cc_num );
		}
	});

	if ( profiles.is(':visible') ) {
		jQuery( 'input[name="profile"]').change( function() {
			if ( jQuery( this ).val() === '0' ) {
				new_card.show();
				profiles.find('.ms-row-card_cvc').hide();
				profiles.find('.ms-row-card_cvc input').val('');
				profiles.find('.ms-row-submit').hide();
			} else {
				new_card.hide();
				profiles.find('.ms-row-card_cvc').show();
				profiles.find('.ms-row-submit').show();
			}
		});

		jQuery( 'input[name="profile"]').first().change();
	}

	jQuery('#ms-authorize-extra-form').validate(args);
};
