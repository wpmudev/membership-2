/**
 * Custom JS used in the new plugin settings page.
 */

/*global jQuery:false */

jQuery(function() {
	var popup = null,
		form = jQuery( '.ms-group-editor' ).detach()
		list = jQuery( '.ms-group-fields .field-list tbody' );

	// Displays the attribute editor.
	function show_editor( ev ) {
		var wnd, row,
			me = jQuery( this );

		if ( popup ) { return false; }

		popup = wpmUi.popup();
		popup.onclose( destroy_editor );
		popup.set_class( 'attribute-editor' );
		popup.modal( true );
		popup.size( null, 410 );
		popup.title( ms_data.lang.edit_title );
		popup.content( form );
		popup.show();

		wnd = popup.$();
		wnd.find( '.buttons-wrapper' ).remove()
		wnd.find( '.btn_save' ).click( save_attribute );
		wnd.find( '.btn_delete' ).click( delete_attribute );

		if ( me.is( 'td' ) ) {
			row = me.closest( 'tr' );
			wnd.find( '#title' ).val( row.find( 'td:nth-child(1)' ).text() );
			wnd.find( '#slug' ).val( row.find( 'td:nth-child(2)' ).text() );
			wnd.find( '#old_slug' ).val( row.find( 'td:nth-child(2)' ).text() );
			wnd.find( '#type' ).val( row.find( 'td:nth-child(3)' ).text() );
			wnd.find( '#info' ).val( row.find( 'td:nth-child(4)' ).text() );
			wnd.find( '.btn_delete' ).show();
		} else {
			wnd.find( '.btn_delete' ).hide();
		}
	}

	// Reset the editor state.
	function destroy_editor() {
		popup = null;
	}

	// Save the current attribute details and close the editor.
	function save_attribute() {
		var wnd, action, nonce, title, slug, type, info, old_slug,
			data = {};

		if ( ! popup ) { return false; }

		wnd = popup.$();
		action = wnd.find( 'input.action_save' );
		nonce = wnd.find( 'input.nonce_save' );
		title = wnd.find( '#title' );
		slug = wnd.find( '#slug' );
		old_slug = wnd.find( '#old_slug' );
		type = wnd.find( '#type' );
		info = wnd.find( '#info' );

		if ( ! title.val().length ) {
			title.focus();
			return false;
		}
		if ( ! slug.val().length ) {
			slug.focus();
			return false;
		}

		data.action = action.val();
		data._wpnonce = nonce.val();
		data.title = title.val();
		data.slug = slug.val();
		data.old_slug = old_slug.val();
		data.type = type.val();
		data.info = info.val();

		wnd.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				if ( ! response.ok ) { return false; }

				refresh_list( response.items );
				popup.close();
			},
			'json'
		).complete(function() {
			wnd.removeClass( 'wpmui-loading' );
		});
	}

	// Delete the current attribute and close the editor.
	function delete_attribute() {
		var wnd,
			data = {};

		if ( ! popup ) { return false; }

		wnd = popup.$();
		data.action = wnd.find( 'input.action_delete' ).val();
		data._wpnonce = wnd.find( 'input.nonce_delete' ).val();
		data.slug = wnd.find( '#old_slug' ).val();

		wnd.addClass( 'wpmui-loading' );
		jQuery.post(
			window.ajaxurl,
			data,
			function(response) {
				if ( ! response.ok ) { return false; }

				refresh_list( response.items );
				popup.close();
			},
			'json'
		).complete(function() {
			wnd.removeClass( 'wpmui-loading' );
		});
	}

	// Re-create the field list.
	function refresh_list( items ) {
		var row_class = 'alternate';

		list.empty();
		jQuery.each( items, function(index, item) {
			var row = jQuery( '<tr></tr>' );
			row.addClass( row_class );
			row.append( jQuery( '<td></td>' ).html( item.title ) );
			row.append( jQuery( '<td></td>' ).html( '<code>' + item.slug + '</code>' ) );
			row.append( jQuery( '<td></td>' ).text( item.type ) );
			row.append( jQuery( '<td></td>' ).html( item.info ) );
			list.append( row );

			row_class = 'alternate' === row_class ? '' : 'alternate';
		});
	}

	jQuery( '.add_field' ).click( show_editor );
	list.on( 'click', 'td:first-child', show_editor );
});