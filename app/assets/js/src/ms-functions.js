/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */

/* Global functions */

window.ms_functions = {
	data: [],
	save_obj_selector: '.ms-save-text-wrapper',
	processing_class: 'ms-processing',
	init_class: 'ms-init',
	radio_slider_on_class: 'on',
	value: 0,
	chosen_options: {
		minimumResultsForSearch: 6,
		dropdownAutoWidth: true,
		dropdownCssClass: 'ms-select2',
		containerCssClass: 'ms-select2'
	},

	ajax_update: function( obj ) {
		var data,
			fn = window.ms_functions;

		if( ! jQuery( obj ).hasClass( fn.processing_class ) ) {
			jQuery( fn.save_obj_selector ).addClass( fn.processing_class );
			jQuery( fn.save_obj_selector ).removeClass( fn.init_class );

			data = jQuery( obj ).data( 'ms' );

			if( jQuery( obj ).is( ':checkbox' ) ) {
				if( jQuery( obj ).attr( 'checked' ) ) {
					data.value = true;
				}
				else {
					data.value = false;
				}
			}
			else {
				data.value = jQuery( obj ).val();
			}

			jQuery.post(
				window.ajaxurl,
				data,
				function( response ) {
					jQuery( fn.save_obj_selector ).removeClass( fn.processing_class );
					jQuery( obj ).trigger( 'ms-ajax-updated', data );
				}
			);
		}
	},

	radio_slider_ajax_update: function( obj ) {
		var value, data,
			fn = window.ms_functions;

		if( ! jQuery( obj ).hasClass( fn.processing_class ) ) {
			jQuery( obj ).addClass( fn.processing_class );
			jQuery( fn.save_obj_selector ).addClass( fn.processing_class );
			jQuery( fn.save_obj_selector ).removeClass( fn.init_class );
			if( jQuery( obj ).hasClass( fn.radio_slider_on_class ) ) {
				jQuery( obj ).removeClass( fn.radio_slider_on_class );
				value = 0;
			}
			else {
				jQuery( obj ).addClass( fn.radio_slider_on_class );
				value = 1;
			}

			data = jQuery( obj ).children( '.ms-toggle' ).data( 'ms' );
			if( null != data ) {
				data.value = value;
				jQuery.post(
					window.ajaxurl,
					data,
					function( response ) {
						jQuery( fn.save_obj_selector ).removeClass( fn.processing_class );
						jQuery( obj ).removeClass( fn.processing_class );
						jQuery( obj ).children( 'input' ).val( jQuery( obj ).hasClass( fn.radio_slider_on_class ) );
						jQuery( obj ).trigger( 'ms-radio-slider-updated', data );
					}
				);
			}
		}
	},

	/**
	 * Select the whole content inside the specified element.
	 */
	select_all: function( el ) {
		var range;
		el = jQuery( el )[0];

		if( document.selection ) {
			range = document.body.createTextRange();
			range.moveToElementText( el );
			range.select();
		} else if( window.getSelection ) {
			range = document.createRange();
			range.selectNode( el );
			window.getSelection().addRange( range );
		}
	},

	/**
	 * Toggle the accordeon box state
	 */
	toggle_box: function( el ) {
		var me = jQuery( el ),
			box = me.parents( '.ms-settings-box' ).first();

		if ( box.hasClass( 'static' ) ) { return false; }
		if ( box.hasClass( 'closed' ) ) {
			box.removeClass( 'closed' ).addClass( 'open' );
		} else {
			box.removeClass( 'open' ).addClass( 'closed' );
		}
	},

	/**
	 * Toggle datepicker when user clicks on icon.
	 */
	toggle_datepicker: function( el ) {
		var me = jQuery( el ),
			dp = me.parents( '.ms-datepicker-wrapper' ).find( '.ms-datepicker' );

		dp.datepicker( 'show' );
	},

	/**
	 * Tag-Selector component:
	 * Add new tag to the selected-tags list.
	 */
	tag_selector_add: function( ev ) {
		var fn = window.ms_functions,
			me = jQuery( this ).parents( '.ms-tag-selector' ).first(),
			el_src = me.find( 'select.ms-tag-source' ),
			el_dst = me.find( 'select.ms-tag-data' ),
			list = el_dst.val() || [];

		if ( ! el_src.val().length ) { return; }

		list.push( el_src.val() );
		el_dst.val( list ).trigger( 'change' );
		el_src.val( '' ).trigger( 'change' );

		fn.tag_selector_refresh_source( this );
	},

	/**
	 * Tag-Selector component:
	 * Disable or Enable options in the source list.
	 */
	tag_selector_refresh_source: function( el ) {
		var i = 0, item = null,
			me = jQuery( el ).parents( '.ms-tag-selector' ).first(),
			el_src = me.find( 'select.ms-tag-source' ),
			el_src_items = el_src.find( 'option' ),
			el_dst = me.find( 'select.ms-tag-data' ),
			list = el_dst.val() || [];

		for ( i = 0; i < el_src_items.length; i += 1 ) {
			item = jQuery( el_src_items[i] );
			if ( -1 !== jQuery.inArray( item.val(), list ) ) {
				item.prop( 'disabled', true );
			} else {
				item.prop( 'disabled', false );
			}
		}
		el_src.trigger( 'change' );
	}
};


jQuery( document ).ready( function() {
	var fn = window.ms_functions;

	// Toggle radio-sliders on click.
	jQuery( '.ms-radio-slider' )
		.click( function() { fn.radio_slider_ajax_update( this ); } );

	// Toggle accordeon boxes on click.
	jQuery( '.ms-settings-box .handlediv' )
		.click( function() { fn.toggle_box( this ); } );

	// Toggle datepickers when user clicks on icon.
	jQuery( '.ms-datepicker-wrapper .ms-icon' )
		.click( function() { fn.toggle_datepicker( this ); } );

	// Initialize all select boxes.
	jQuery( '.ms-wrap select:not(.manual-init), .ms-wrap .chosen-select' )
		.select2( fn.chosen_options );

	// Initialize the tag-select components.
	jQuery( '.ms-tag-selector .ms-tag-data ' )
		.on( 'select2-opening', function( ev ) { ev.preventDefault(); } )
		.on( 'change', function( ev ) { fn.tag_selector_refresh_source( this ); } );
	jQuery( '.ms-tag-selector .ms-tag-button' )
		.click( fn.tag_selector_add );

	// Ajax-Submit data when ms-ajax-update fields are changed.
	jQuery( 'input.ms-ajax-update, select.ms-ajax-update, textarea.ms-ajax-update' )
		.change( function() { fn.ajax_update( this ); } );

	// Select all text inside <code> tags on click.
	jQuery( '.ms-wrap' )
		.on( 'click', 'code', function() { fn.select_all( this ); } );
});
