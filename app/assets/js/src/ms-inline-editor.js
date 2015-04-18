/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_inline_editor:false */


/* Membership2 Inline Editor */
(function() {
	var quickedit = null,
		the_item = null,
		template = null;

	window.ms_inline_editor = {

		init: function() {
			template = jQuery( '.ms-wrap #inline-edit' );

			/*
			 * Remove the form-template from the DOM so it does not mess with
			 * the containing form...
			 */
			template.detach();

			// prepare the edit rows
			template.keyup(function(e){
				if ( e.which === 27 ) {
					return ms_inline_editor.revert();
				}
			});

			jQuery( 'a.cancel', template ).click(function(){
				return ms_inline_editor.revert();
			});
			jQuery( 'a.save', template ).click(function(){
				return ms_inline_editor.save( this );
			});
			jQuery( 'td', template ).keydown(function(e){
				if ( e.which === 13 ) {
					return ms_inline_editor.save( this );
				}
			});

			// add events
			jQuery( '.ms-wrap .wp-list-table' ).on('click', 'a.editinline', function() {
				ms_inline_editor.edit( this );
				return false;
			});
		},

		edit: function( id ) {
			var item_data, ind, field_input, field_value, row_data;

			ms_inline_editor.revert();

			if ( typeof( id ) === 'object' ) {
				id = ms_inline_editor.get_id( id );
			}

			// add the new blank row
			quickedit = template.clone( true );
			the_item = jQuery( '#item-' + id );

			jQuery( 'td', quickedit ).attr(
				'colspan',
				jQuery('.widefat:first thead th:visible').length
			);

			if ( the_item.hasClass( 'alternate' ) ) {
				quickedit.addClass('alternate');
			}
			the_item.hide().after( quickedit );

			// populate the data
			row_data = {};
			item_data = the_item.find( '.inline_data' );
			item_data.children().each(function() {
				var field = jQuery( this ),
					inp_name = field.attr( 'class' ),
					input = quickedit.find( ':input[name="' + inp_name + '"]' ),
					label = quickedit.find( '.lbl-' + inp_name );

				row_data[inp_name] = field.text();
				if ( input.length ) {
					input.val( row_data[inp_name] );
				}
				if ( label.length ) {
					label.text( row_data[inp_name] );
				}
			});
			jQuery( document ).trigger( 'ms-inline-editor', [quickedit, the_item, row_data] );

			quickedit.attr( 'id', 'edit-' + id ).addClass( 'inline-editor' ).show();
			quickedit.find( ':input:visible' ).first().focus();

			return false;
		},

		save: function( id ) {
			var params, fields;

			if ( typeof( id ) === 'object' ) {
				id = ms_inline_editor.get_id( id );
			}

			quickedit.find('td').addClass( 'wpmui-loading' );
			params = quickedit.find( ':input' ).serialize();

			// make ajax request
			jQuery.post(
				window.ajaxurl,
				params,
				function( response ) {
					quickedit.find('td').removeClass( 'wpmui-loading' );

					if ( response ) {
						if ( -1 !== response.indexOf( '<tr' ) ) {
							the_item.remove();
							the_item = jQuery( response );
							quickedit.before( the_item ).remove();
							the_item.hide().fadeIn();

							// Update the "alternate" class
							ms_inline_editor.update_alternate( the_item );

							jQuery( document ).trigger( 'ms-inline-editor-updated', [the_item] );
						} else {
							response = response.replace( /<.[^<>]*?>/g, '' );
							quickedit.find( '.error' ).html( response ).show();
						}
					} else {
						quickedit.find( '.error' ).html( ms_data.lang.quickedit_error ).show();
					}

					if ( the_item.prev().hasClass( 'alternate' ) ) {
						the_item.removeClass( 'alternate' );
					}
				},
				'html' // Tell jQuery that we expect HTML code as response
			);

			return false;
		},

		revert: function(){
			if ( quickedit ) {
				quickedit.remove();
				the_item.show();

				quickedit = null;
				the_item = null;
			}

			return false;
		},

		update_alternate: function( element ) {
			var ind, len, row,
				tbody = jQuery( element ).closest( 'tbody' ),
				rows = tbody.find( 'tr:visible' );

			for ( ind = 0, len = rows.length; ind < len; ind++ ) {
				row = jQuery( rows[ind] );
				if ( ind % 2 === 0 ) {
					row.addClass( 'alternate' );
				} else {
					row.removeClass( 'alternate' );
				}
			}
		},

		get_id: function( obj ) {
			var id = jQuery( obj ).closest( 'tr' ).attr( 'id' ),
				parts = id.split( '-' );

			return parts[ parts.length - 1 ];
		}
	};

	// Initialize the inline editor

	jQuery(function() {
		ms_inline_editor.init();
	});
}());
