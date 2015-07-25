/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global wpmUi:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_billing_transactions = function init() {
	var table = jQuery( '.wp-list-table.transactions' ),
		btn_clear = table.find( '.action-clear' ),
		btn_ignore = table.find( '.action-ignore' ),
		btn_link = table.find( '.action-link' );

	function clear_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_link]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-ignore' ) ) { return false; }

		data.action = 'transaction_update';
		data._wpnonce = nonce;
		data.id = row_id;
		data.state = 'clear';

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				row.removeClass( 'log-ignore is-manual' ).addClass( 'log-err' );
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	function ignore_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_link]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-err' ) ) { return false; }

		data.action = 'transaction_update';
		data._wpnonce = nonce;
		data.id = row_id;
		data.state = 'ignore';

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				row.removeClass( 'log-err' ).addClass( 'log-ignore is-manual' );
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	function link_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_link]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-err' ) ) { return false; }

		data.action = 'transaction_link';
		data._wpnonce = nonce;
		data.id = row_id;

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function( response ) {
				if ( response.length ) {
					show_dialog( response );
				}
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	function show_dialog( data ) {
		var popup = wpmUi.popup();

		popup.modal( true );
		popup.title( ms_data.lang.link_title );
		popup.content( data );
		popup.show();
	}

	btn_clear.click(clear_line);
	btn_ignore.click(ignore_line);
	btn_link.click(link_line);
};
