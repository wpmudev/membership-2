/*global jQuery:false */
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