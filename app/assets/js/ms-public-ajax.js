/*! Protected Content - v1.1.01
 * https://premium.wpmudev.org/project/membership/
 * Copyright (c) 2015; * Licensed GPLv2+ */
/*global window:false */
/*global document:false */
/*global ms_data:false */

jQuery( function() {
	var ms_ajax = window.ms_ajax_login,
		frm_login = jQuery( 'form[action="login"]' ),
		frm_lost = jQuery( 'form[action="lostpassword"]' ),
		sts_login = frm_login.find( '.status' ),
		sts_lost = frm_lost.find( '.status' ),
		show_login = jQuery( 'a.login', 'form' ),
		show_lost = jQuery( 'a.lost', 'form' );

	// Auto-Focus on the user-name field.
	function set_focus() {
		if ( frm_login.is( ':visible' ) ) {
			frm_login.find( 'input[name="log"]' ).focus();
		} else if ( frm_lost.is( ':visible' ) ) {
			frm_lost.find( 'input[name="user_login"]' ).focus();
		}
	}

	// Disable all fields inside the form.
	function disable_form( form ) {
		form.addClass( 'progress' );
		form.find( 'input, textarea, select, button' ).each( function() {
			jQuery( this ).data( 'ms-disabled', jQuery( this ).prop( 'disabled' ) );
			jQuery( this ).prop( 'disabled', true ).addClass( 'disabled' );
		});
	}

	// Re-Enable all fields inside the form.
	function enable_form( form ) {
		form.removeClass( 'progress' ).prop( 'disabled', false );
		form.find( 'input, textarea, select, button' ).each( function() {
			if ( jQuery( this ).data( 'ms-disabled' ) ) { return; }
			jQuery( this ).prop( 'disabled', false ).removeClass( 'disabled' );
		});
	}

	// Display the Ajax response message.
	function show_message( label, data ) {
		if ( undefined !== data.error ) {
			label.addClass( 'error' ).text( data.error );
		} else if ( undefined !== data.success ) {
			label.removeClass( 'error' ).text( data.success );
		}
	}

	// Switch between the forms.
	show_lost.on( 'click', function() {
		frm_login.hide();
		frm_lost.show();
		sts_lost.removeClass( 'error' ).text( '' );
		set_focus();
	});

	show_login.on( 'click', function() {
		frm_lost.hide();
		frm_login.show();
		sts_login.removeClass( 'error' ).text( '' );
		set_focus();
	});

	// Login Handler
	frm_login.on( 'submit', function( ev ){
		var key, data = {},
			frm_current = jQuery( this ),
			fields = frm_current.serializeArray(),
			redirect = frm_current.find( 'input[name="redirect_to"]' );

		sts_login.removeClass( 'error' ).show().text( ms_ajax.loadingmessage );
		disable_form( frm_current );

		// Very simple serialization. Since the form is simple it will work...
		for ( key in fields ) {
			if ( fields.hasOwnProperty( key ) ) {
				data[fields[key].name] = fields[key].value;
			}
		}
		data['action'] = 'ms_login'; // calls wp_ajax_nopriv_ms_login

		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: ms_ajax.ajaxurl,
			data: data,
			success: function( data ) {
				enable_form( frm_current );
				show_message( sts_login, data );

				if ( data.loggedin ) {
					document.location.href = redirect.val();
				}
			},
			error: function() {
				var data = { error: ms_ajax.errormessage };
				enable_form( frm_current );
				show_message( sts_login, data );
			}
		});

		ev.preventDefault();
		return false;
	});

	// Lost-Pass Handler
	frm_lost.on( 'submit', function( ev ){
		var key, data = {},
			fields = frm_lost.serializeArray();

		sts_lost.removeClass( 'error' ).show().text( ms_ajax.loadingmessage );
		disable_form( frm_lost );

		// Very simple serialization. Since the form is simple it will work...
		for ( key in fields ) {
			if ( fields.hasOwnProperty( key ) ) {
				data[fields[key].name] = fields[key].value;
			}
		}
		data['action'] = 'ms_lostpass'; // calls wp_ajax_nopriv_ms_login

		jQuery.ajax({
			type: 'POST',
			dataType: 'json',
			url: ms_ajax.ajaxurl,
			data: data,
			success: function( data ) {
				enable_form( frm_lost );
				show_message( sts_lost, data );
			},
			error: function() {
				var data = { error: ms_ajax.errormessage };
				enable_form( frm_lost );
				show_message( sts_lost, data );
			}
		});

		ev.preventDefault();
		return false;
	});

	set_focus();
});