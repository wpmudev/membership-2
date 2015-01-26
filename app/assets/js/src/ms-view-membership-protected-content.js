/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_protected_content = function init () {
	window.ms_init.memberships_column( '.column-access' );
};

// This is also used on the Members page
window.ms_init.memberships_column = function init_column( column_class ) {
	var table = jQuery( '.wp-list-table' );

	// Change the table row to "protected"
	function protect_item( ev ) {
		var cell = jQuery( this ).closest( column_class );

		cell.find( '.ms-empty' )
			.removeClass( 'ms-empty' )
			.addClass( 'ms-assigned ms-focused' )
			.find( 'select.ms-memberships' )
			.select2( 'focus' )
			.select2( 'open' );
	}

	// If the item is not protected by any membership it will chagne to public
	function maybe_make_public( ev ) {
		var cell = jQuery( this ).closest( column_class ),
			list = cell.find( 'select.ms-memberships' ),
			memberships = list.select2( 'val' );

		cell.find( '.ms-focused' ).removeClass( 'ms-focused' );

		if ( memberships && memberships.length ) { return; }
		cell.find( '.ms-assigned' ).removeClass( 'ms-assigned' ).addClass( 'ms-empty' );
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
