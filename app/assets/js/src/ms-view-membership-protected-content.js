/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_protected_content = function init () {
	var table = jQuery( '.wp-list-table' ),
		sel_network_site = jQuery( '#select-site' );

	window.ms_init.memberships_column( '.column-access' );

	// After a membership was added or removed. Check if there are dripped memberships.
	function check_if_dripped( ev ) {
		var ind, membership_id,
			cell = jQuery( this ).closest( '.column-access' ),
			row = cell.closest( 'tr.item' ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.val(),
			num_dripped = 0;

		if ( memberships && memberships.length ) {
			for ( ind in memberships ) {
				membership_id = memberships[ind];
				if ( undefined !== ms_data.dripped[membership_id] ) {
					num_dripped += 1;
				}
			}
		}

		if ( num_dripped > 1 ) {
			// Multiple dripped memberships. Inline editor required.
			row.addClass( 'ms-dripped' );
		} else if ( num_dripped === 1 ) {
			// Single dripped membership. No inline editor required.
			row.addClass( 'ms-dripped' );
		} else {
			row.removeClass( 'ms-dripped' );
		}
	}

	// Right before the inline editor is displayed. We can prepare the form.
	function populate_inline_editor( ev, editor, row, item_data ) {
		var ind, len,
			memberships = row.find( 'select.ms-memberships option:selected' ),
			form = editor.find( '.dripped-form' ),
			target = editor.find( '.dynamic-form' );

		for ( ind = 0, len = memberships.length; ind < len; ind++ ) {
			var item = jQuery( memberships[ind] ),
				membership_id = item.val(),
				color = item.data( 'color' ),
				form_row = form.clone( false ),
				base = 'ms_' + membership_id;

			if ( undefined !== ms_data.dripped[membership_id] ) {
				// Create input fields for the dripped membership
				form_row.find( '.the-name' )
					.text( ms_data.dripped[membership_id] )
					.css( {'background': color} );

				form_row.find( '[name=membership_ids]' )
					.attr( 'name', 'membership_ids[]' )
					.val( membership_id );

				form_row.find( '[name=item_id]' )
					.val( item_data.item_id );

				form_row.find( '[name=offset]' )
					.val( item_data.offset );

				form_row.find( '[name=number]' )
					.val( item_data.number );

				form_row.find( '[name=dripped_type]' )
					.attr( 'name', base + '[dripped_type]' )
					.val( item_data[ base + '[dripped_type]' ] );

				form_row.find( '[name=date]' )
					.attr( 'name', base + '[date]' )
					.val( item_data[ base + '[date]' ] );

				form_row.find( '[name=delay_unit]' )
					.attr( 'name', base + '[delay_unit]' )
					.val( item_data[ base + '[delay_unit]' ] );

				form_row.find( '[name=delay_type]' )
					.attr( 'name', base + '[delay_type]' )
					.val( item_data[ base + '[delay_type]' ] );

				// Add the membership form to the inline editor
				form_row.appendTo( target ).removeClass( 'hidden' );

				setup_editor( form_row );
			}
		}

		// Remove the form-template from the inline editor.
		form.remove();
	}

	// Set up the event-handlers of the inline editor.
	function setup_editor( form ) {
		var sel_type = form.find( 'select.dripped_type' ),
			inp_date = form.find( '.wpmui-datepicker' );

		// Type-selection
		sel_type.change(function() {
			var me = jQuery( this ),
				val = me.val(),
				types = me.closest( '.dripped-form' ).find( '.drip-option' );

			types.removeClass( 'active' );
			types.filter( '.' + val ).addClass( 'active' );
		}).trigger( 'change' );

		// Datepicker
		inp_date.ms_datepicker();
	}

	// The table was updated, at least one row needs to be re-initalized.
	function update_table( ev, row ) {
		window.ms_init.memberships_column( '.column-access' );

		row.find( '.ms-memberships' ).each(function() {
			check_if_dripped.apply( this );
		});
	}

	// Network-wide protection
	function refresh_site_data( ev ) {
		var url = sel_network_site.val();

		window.location.href = url;
		sel_network_site.addClass( 'wpmui-loading' );
	}

	// Add event hooks.

	table.on( 'ms-ajax-updated', '.ms-memberships', check_if_dripped );
	table.find( '.ms-memberships' ).each(function() {
		check_if_dripped.apply( this );
	});

	jQuery( document ).on( 'ms-inline-editor', populate_inline_editor );
	jQuery( document ).on( 'ms-inline-editor-updated', update_table );

	sel_network_site.on( 'change', refresh_site_data );
};


// -----------------------------------------------------------------------------


// This is also used on the Members page
window.ms_init.memberships_column = function init_column( column_class ) {
	var table = jQuery( '.wp-list-table' );

	// Change the table row to "protected"
	function protect_item( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			row = cell.closest( 'tr.item' ),
			inp = cell.find( 'select.ms-memberships' );

		row.removeClass( 'ms-empty' )
			.addClass( 'ms-assigned' );

		cell.addClass( 'ms-focused' );

		inp.wpmuiSelect( 'focus' );
		inp.wpmuiSelect( 'open' );
	}

	// If the item is not protected by any membership it will chagne to public
	function maybe_make_public( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			row = cell.closest( 'tr.item' ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.val();

		cell.removeClass( 'ms-focused' );

		if ( memberships && memberships.length ) { return; }
		row.removeClass( 'ms-assigned' ).addClass( 'ms-empty' );
	}

	// Format the memberships in the dropdown list (= unselected items)
	function format_result( state ) {
		var attr,
			original_option = state.element;

		attr = 'class="val" style="background: ' + jQuery( original_option ).data( 'color' ) + '"';
		return '<span ' + attr + '>&emsp;</span> ' + state.text;
	}

	// Format the memberships in the tag list (= selected items)
	function format_tag( state, container ) {
		var attr,
			original_option = state.element;

		container.css({ background: jQuery( original_option ).data( 'color' ) });
		container.addClass( 'val' );

		return '<span class="txt">' + state.text + '</span>';
	}

	// add hooks

	table.on( 'click', '.ms-empty-note-wrapper .wpmui-label-after', protect_item );

	table.on( 'ms-ajax-updated', '.ms-memberships', maybe_make_public );
	table.on( 'blur', '.ms-memberships', function( ev ) {
		var me = jQuery( this );
		// We need a delay here to allow select2 to forward the selection to us.
		window.setTimeout(
			function() { maybe_make_public.apply( me, ev ); },
			250
		);
	});

	jQuery( 'select.ms-memberships' ).wpmuiSelect({
		templateResult: format_result,
		templateSelection: format_tag,
		escapeMarkup: function( m ) { return m; },
		dropdownCssClass: 'ms-memberships wpmui-select2',
		width: '100%'
	});
};
