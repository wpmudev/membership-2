/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_inline_editor:false */


/* Protected Content Inline Editor */
(function() {
	var quickedit = null,
		the_item = null,
		template = null;

	window.ms_inline_editor = {

		init: function() {
			template = jQuery( '#inline-edit' );

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
			jQuery( '.wp-list-table' ).on('click', 'a.editinline', function() {
				ms_inline_editor.edit( this );
				return false;
			});
		},

		edit: function( id ) {
			var item_data, ind, field_input, field_value;

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
			item_data = the_item.find( '.inline_data' );
			item_data.children().each(function() {
				var field = jQuery( this ),
					inp_name = field.attr( 'class' ),
					input = quickedit.find( ':input[name="' + inp_name + '"]' ),
					label = quickedit.find( '.lbl-' + inp_name );

				if ( input.length ) {
					input.val( field.text() );
				}
				if ( label.length ) {
					label.text( field.text() );
				}
			});
			jQuery( document ).trigger( 'ms-inline-editor', [quickedit, the_item] );

			quickedit.attr( 'id', 'edit-' + id ).addClass( 'inline-editor' ).show();
			quickedit.find( 'input:visible' ).first().focus();

			return false;
		},

		save: function( id ) {
			var params, fields;

			if ( typeof( id ) === 'object' ) {
				id = ms_inline_editor.get_id( id );
			}

			quickedit.addClass( 'wpmui-loading' );

			params = {
				action: 'ms_inline_edit',
				membership_id: id,
			};

			fields = quickedit.find( ':input' ).serialize();
			params = fields + '&' + jQuery.param( params );

			// make ajax request
			jQuery.post(
				window.ajaxurl,
				params,
				function( response ) {
					quickedit.removeClass( 'wpmui-loading' );

					if ( response ) {
						if ( -1 !== response.indexOf( '<tr' ) ) {
							jQuery( '#item-' + id ).remove();
							jQuery( '#edit-' + id ).before( response ).remove();
							jQuery( '#item-' + id ).hide().fadeIn();
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
