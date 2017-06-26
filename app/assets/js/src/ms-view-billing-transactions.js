/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global wpmUi:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_billing_transactions = function init() {
	var table = jQuery( '.wp-list-table.transactions, .wp-list-table.transaction_matches' ),
		frm_match = jQuery( '.transaction-matching' ),
		btn_clear = table.find( '.action-clear' ),
		btn_ignore = table.find( '.action-ignore' ),
		btn_link = table.find( '.action-link' ),
		btn_retry = table.find( '.action-retry' ),
		btn_match = frm_match.find( '.action-match' ),
		retry_transactions, show_link_dialog, append_option;

	// Handle the "Save Matching" action.
	function save_matching( ev ) {
		var ajax = wpmUi.ajax(),
			data = ajax.extract_data( frm_match );

		frm_match.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				if ( response.success ) {
					wpmUi.message( response.data.message );

					// Start to process the transactions.
					retry_transactions();
				}
			},
			'json'
		).always(function() {
			frm_match.removeClass( 'wpmui-loading' );
		});

		return false;
	}

	// Retry to process all displayed transactions.
	retry_transactions = function() {
		var rows = table.find( '.item' ),
			nonce = frm_match.find( '.retry_nonce' ).val(),
			progress = wpmUi.progressbar(),
			counter = 0,
			ajax_data = {},
			queue = [];

		ajax_data.action = 'transaction_retry';
		ajax_data._wpnonce = nonce;

		// Collect all log-IDs in the queue.
		rows.each(function() {
			var row = jQuery( this ),
				row_id = row.attr( 'id' ).replace( /^item-/, '' );

			row.find( '.column-note' ).addClass( 'wpmui-loading' );
			queue.push( row_id );
		});

		progress.value( 0 );
		progress.max( queue.length );
		progress.$().insertBefore( frm_match );
		frm_match.hide();

		// Process the queue.
		function process_queue() {
			if ( ! queue.length ) {
				progress.$().remove();
				return;
			}

			var id = queue.shift(),
				data = jQuery.extend( {}, ajax_data ),
				row = table.find( '#item-' + id );

			data.id = id;
			counter += 1;
			progress.value( counter );

			jQuery.post(
				window.ajaxurl,
				data,
				function(response) {
					if ( response.success && response.data.desc ) {
						row.removeClass( 'log-err log-ignore log-ok' );
						row.addClass( 'log-' + response.data.state );
						row.find( '.column-note .txt' ).text( response.data.desc );
					}

					window.setTimeout( function() { process_queue(); }, 1 );
				},
				'json'
			).always(function() {
				row.find( '.column-note' ).removeClass( 'wpmui-loading' );
			});
		}

		process_queue();
	};

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

	// Handle the "Retry" action.
	function retry_line( ev ) {
		var cell = jQuery( this ).closest( 'td' ),
			nonce = cell.find( 'input[name=nonce_retry]' ).val(),
			row = cell.closest( '.item' ),
			row_id = row.attr( 'id' ).replace( /^item-/, '' ),
			data = {};

		if ( ! row.hasClass( 'log-err' ) && ! row.hasClass( 'log-ignore' ) ) { return false; }

		data.action = 'transaction_retry';
		data._wpnonce = nonce;
		data.id = row_id;

		cell.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				if ( response.success && response.data.desc ) {
					row.removeClass( 'log-err log-ignore log-ok' );
					row.addClass( 'log-' + response.data.state );
					row.find( '.column-note .txt' ).text( response.data.desc );
				}
			},
			'json'
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
	show_link_dialog = function( row, data ) {
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
	};

	append_option = function( container, val, label ) {
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
	};

	btn_clear.click(clear_line);
	btn_ignore.click(ignore_line);
	btn_link.click(link_line);
	btn_retry.click(retry_line);
	btn_match.click(save_matching);
};
