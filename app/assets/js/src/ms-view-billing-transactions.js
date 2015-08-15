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

	// Handle the "Reset" action.
	function clear_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_update]' ).val(),
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

	// Handle the "Ignore" action.
	function ignore_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_update]' ).val(),
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

	// Handle the "Link" action.
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
					show_link_dialog( row, response );
				}
			}
		).always(function() {
			cell.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Display the Transaction-Link popup.
	function show_link_dialog( row, data ) {
		var sel_user, sel_subscription, sel_invoice, nonce_data, nonce_update,
			row_user, row_subscription, row_invoice, btn_submit, log_id,
			popup = wpmUi.popup(),
			wnd = popup.$();

		popup.modal( true );
		popup.title( ms_data.lang.link_title );
		popup.content( data );
		popup.show();

		// Add event handlers inside the popup.
		sel_user = wnd.find( 'select[name=user_id]' );
		sel_subscription = wnd.find( 'select[name=subscription_id]' );
		sel_invoice = wnd.find( 'select[name=invoice_id]' );
		row_user = wnd.find( '.link-member' );
		row_subscription = wnd.find( '.link-subscription' );
		row_invoice = wnd.find( '.link-invoice' );
		nonce_data = wnd.find( 'input[name=nonce_link_data]' );
		nonce_update = wnd.find( 'input[name=nonce_update]' );
		log_id = wnd.find( 'input[name=log_id]' );
		btn_submit = wnd.find( 'button[name=submit]' );

		row_subscription.hide();
		row_invoice.hide();
		btn_submit.prop( 'disabled', true ).addClass( 'disabled' );

		function load_subscriptions() {
			var data,
				user_id = sel_user.val();

			if ( isNaN( user_id ) || user_id < 1 ) {
				row_invoice.find( '.wpmui-label-after' ).hide();
				row_subscription.hide();
				row_invoice.hide();
				return false;
			}

			data = {
				'action': 'transaction_link_data',
				'_wpnonce': nonce_data.val(),
				'get': 'subscriptions',
				'for': user_id
			};

			sel_subscription.empty();
			sel_invoice.empty();
			row_subscription.show().addClass( 'wpmui-loading' );
			row_invoice.find( '.wpmui-label-after' ).hide();
			row_invoice.hide();
			btn_submit.prop( 'disabled', true ).addClass( 'disabled' );

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					jQuery.each( response, function( val, label ) {
						append_option( sel_subscription, val, label );
					});
				},
				'json'
			).always(function() {
				row_subscription.removeClass( 'wpmui-loading' );
			});
		}

		function load_invoices() {
			var data,
				subscription_id = sel_subscription.val();

			if ( isNaN( subscription_id ) || subscription_id < 1 ) {
				row_invoice.find( '.wpmui-label-after' ).hide();
				row_invoice.hide();
				return false;
			}

			data = {
				'action': 'transaction_link_data',
				'_wpnonce': nonce_data.val(),
				'get': 'invoices',
				'for': subscription_id
			};

			sel_invoice.empty();
			row_invoice.show().addClass( 'wpmui-loading' );
			row_invoice.find( '.wpmui-label-after' ).hide();
			btn_submit.prop( 'disabled', true ).addClass( 'disabled' );

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					window.console.log( response );
					jQuery.each( response, function( val, label ) {

					window.console.log( val, label );
						append_option( sel_invoice, val, label );
					});
				},
				'json'
			).always(function() {
				row_invoice.removeClass( 'wpmui-loading' );
			});
		}

		function confirm_data() {
			var inv_id = sel_invoice.val();

			if ( ! isNaN( inv_id ) && inv_id > 0 ) {
				row_invoice.find( '.wpmui-label-after' ).show();
				btn_submit.prop( 'disabled', false ).removeClass( 'disabled' );
			} else {
				row_invoice.find( '.wpmui-label-after' ).hide();
				btn_submit.prop( 'disabled', true ).addClass( 'disabled' );
			}
		}

		function save_link() {
			var data = {
				'action': 'transaction_update',
				'_wpnonce': nonce_update.val(),
				'id': log_id.val(),
				'link': sel_invoice.val()
			};

			if ( ! data.link ) { return false; }
			wnd.addClass( 'wpmui-loading' );

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					if ( '3' === response ) {
						row.removeClass( 'log-err' ).addClass( 'log-ok is-manual' );
						popup.close();
					}
				}
			).always(function() {
				wnd.removeClass( 'wpmui-loading' );
			});
		}

		sel_user.change( load_subscriptions );
		sel_subscription.change( load_invoices );
		sel_invoice.change( confirm_data );
		btn_submit.click( save_link );

		if ( ! isNaN( sel_user.val() ) && sel_user.val() > 0 ) {
			load_subscriptions();
		}
	}

	function append_option( container, val, label ) {
		if ( typeof label === 'object' ) {
			var group = jQuery( '<optgroup></optgroup>' );
			group.attr( 'label', val );
			jQuery.each( label, function( subval, sublabel ) {
				append_option( group, subval, sublabel );
			});
			container.append( group );
		} else {
			container.append(
				jQuery( '<option></option>' )
				.val( val )
				.html( label )
			);
		}
	}

	btn_clear.click(clear_line);
	btn_ignore.click(ignore_line);
	btn_link.click(link_line);
};
