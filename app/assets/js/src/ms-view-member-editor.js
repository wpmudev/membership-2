/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_editor = function init () {
	var txt_username = jQuery( '#username' ),
		txt_email = jQuery( '#email' ),
		sel_user = jQuery( '#select_user' ),
		btn_add = jQuery( '#btn_create' ),
		btn_select = jQuery( '#btn_select' ),
		chosen_options = {};

	function validate_field( fieldname, field ) {
		var value = field.val(),
			data = {},
			row = field.closest( '.wpmui-wrapper' );

		data.action = 'member_validate_field';
		data.field = fieldname;
		data.value = value;

		row.addClass( 'wpmui-loading' );

		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				var info = row.find( '.wpmui-label-after' );
				row.removeClass( 'wpmui-loading' );

				if ( '1' === response ) {
					field.removeClass( 'invalid' );
					field.addClass( 'valid' );
					info.html( '' );
				} else {
					field.removeClass( 'valid' );
					field.addClass( 'invalid' );
					info.html( response );
				}

				validate_buttons();
			}
		);
	}

	function validate_buttons() {
		if ( txt_username.hasClass( 'valid' ) && txt_email.hasClass( 'valid' ) ) {
			btn_add.prop( 'disabled', false );
			btn_add.removeClass( 'disabled' );
		} else {
			btn_add.prop( 'disabled', true );
			btn_add.addClass( 'disabled' );
		}

		if ( sel_user.val() ) {
			btn_select.prop( 'disabled', false );
			btn_select.removeClass( 'disabled' );
		} else {
			btn_select.prop( 'disabled', true );
			btn_select.addClass( 'disabled' );
		}
	}

	txt_username.change(function() {
		validate_field( 'username', txt_username );
	});

	txt_email.change(function() {
		validate_field( 'email', txt_email );
	});

	sel_user.change(validate_buttons);

	chosen_options.minimumInputLength = 3;
	chosen_options.multiple = false;
	chosen_options.dropdownAutoWidth = true;
	chosen_options.dropdownCssClass = 'ms-select2';
	chosen_options.containerCssClass = 'ms-select2';
	chosen_options.ajax = {
		url: window.ajaxurl,
		dataType: "json",
		type: "GET",
		quietMillis: 100,
		data: function( term, page ) {
			return {
				action: "member_search",
				q: term,
				p: page
			};
		},
		results: function( data, page ) {
			return { results: data.items, more: data.more };
		}
	};
	sel_user.removeClass( 'wpmui-hidden' );
	window.console.log( chosen_options );
	sel_user.select2( chosen_options );

	validate_buttons();
};
