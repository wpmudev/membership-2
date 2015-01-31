/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_protected_content = function init () {
	var table = jQuery( '.wp-list-table' );

	window.ms_init.memberships_column( '.column-access' );

	// After a membership was added or removed. Check if there are dripped memberships.
	function check_if_dripped( ev ) {
		var ind, membership_id,
			cell = jQuery( this ).closest( '.column-access' ),
			row = cell.closest( 'tr.item' ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.select2( 'val' ),
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
	function populate_inline_editor( ev, editor, row ) {
		var ind, len,
			items = row.find( 'select.ms-memberships option:selected' ),
			form = editor.find( '.dripped-form' ),
			target = editor.find( '.dynamic-form' );

		for ( ind = 0, len = items.length; ind < len; ind++ ) {
			var item = jQuery( items[ind] ),
				item_id = item.val(),
				color = item.data( 'color' ),
				form_row = form.clone( false ),
				key = '[' + item_id + ']';

			if ( undefined !== ms_data.dripped[item_id] ) {
				// Create input fields for the dripped membership
				form_row.find( '.the-name' )
					.text( ms_data.dripped[item_id] )
					.css( {'background': color} );

				form_row.find( '[data-name=membership_id]' )
					.attr( 'name', 'membership_id' + key )
					.val( item_id );

				// Add the membership form to the inline editor
				form_row.appendTo( target ).removeClass( 'hidden' );

				setup_editor( form_row );
			}
		}
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

	// Add event hooks.

	table.on( 'ms-ajax-updated', '.ms-memberships', check_if_dripped );
	table.find( '.ms-memberships' ).each(function() {
		check_if_dripped.apply( this );
	});

	jQuery( document ).on( 'ms-inline-editor', populate_inline_editor );
};

// This is also used on the Members page
window.ms_init.memberships_column = function init_column( column_class ) {
	var table = jQuery( '.wp-list-table' );

	// Change the table row to "protected"
	function protect_item( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			row = cell.closest( 'tr.item' );

		row.removeClass( 'ms-empty' )
			.addClass( 'ms-assigned' );

		cell.addClass( 'ms-focused' )
			.find( 'select.ms-memberships' )
			.select2( 'focus' )
			.select2( 'open' );
	}

	// If the item is not protected by any membership it will chagne to public
	function maybe_make_public( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			row = cell.closest( 'tr.item' ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.select2( 'val' );

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
	function format_tag( state ) {
		var attr,
			original_option = state.element;

		attr = 'class="val" style="background: ' + jQuery( original_option ).data( 'color' ) + '"';
		return '<span ' + attr + '><span class="txt">' + state.text + '</span></span>';
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

	jQuery( 'select.ms-memberships' ).select2({
		formatResult: format_result,
		formatSelection: format_tag,
		escapeMarkup: function( m ) { return m; },
		dropdownCssClass: 'ms-memberships'
	});
};
