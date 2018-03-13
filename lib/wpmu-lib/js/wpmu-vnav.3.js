/*! WPMU Dev code library - v3.0.4
 * http://premium.wpmudev.org/
 * Copyright (c) 2017; * Licensed GPLv2+ */
/*!
 * UI Pattern: Vertical navigation.
 * Version: 1.0.0
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */

jQuery(function() {
	var referer = '';

	// Runs when page is loaded.
	function init() {
		var elements = jQuery( '.vnav' );

		referer = jQuery( 'input[name=_wp_http_referer]' );

		elements.wpmui_vnav();
	}

	// Initializes a single vnav container.
	function init_vnav() {
		var context = jQuery( this );

		// We look for all h2/h3 tags inside the context element.
		// Each h2/h3 element starts a new navigation section.
		var ind, section, parts, title, body, list, key,
			list_height = 0,
			wnd = jQuery( window ),
			html = context.html(),
			sections = html.replace( '<h3>', '<h2>' ).split( '<h2>' ),
			act_key = window.location.hash.replace(/^#/, ''),
			act_class = (! act_key.length ? ' active' : '');

		// Now we have split the sections, we can build the vnav-layout.
		html = '<ul class="lst-vnav">';
		for ( ind = 0; ind < sections.length; ind += 1 ) {
			section = sections[ ind ];
			// Split section title from section body.
			parts = section.replace( '</h3>', '</h2>' ).split( '</h2>' );

			if ( 2 === parts.length && parts[0].length ) {
				if ( parts[0] === '-' ) {
					html += '<li class="lst-vnav-sep"></li>';
				} else {
					key = parts[0]
						.toLowerCase()
						.replace( /\W\W*/g, ' ' )
						.replace( /^\s|\s$/g, '' )
						.replace( /\s/g, '-' );

					if ( act_key.length && act_key === key ) {
						act_class = ' active';
					}
					title = '<h2 data-key="' + key + '">' + parts[0] + '</h2>';
					body = '<div class="data">' + parts[1] + '</div>';
					html += '<li class="lst-vnav-item' + act_class + '">' + title + body + '</li>';
					act_class = '';
				}
			}
		}
		html += '</ul>';

		// Update the settings with new vertical navigation code!
		context.html( html );
		list = jQuery( '.lst-vnav', context ).first();

		// Remove row-header columns when all row-headers of a section are empty.
		context.find( '.lst-vnav-item > .data > table' ).each(function() {
			var me = jQuery( this ),
				all_th = me.find( '> tbody > tr > th, > tr > th' ),
				empty_th = all_th.filter( ':empty' );

			if ( all_th.length === empty_th.length ) {
				all_th.remove();
			}
		});

		// Define click handler.
		var activate_section = function activate_section( ev ) {
			var me = jQuery( this ),
				item = me.parents( '.lst-vnav-item' ).first(),
				prev_item = jQuery( '.lst-vnav-item.active', list ),
				key = me.data( 'key' ),
				new_referer = '';

			window.location.hash = key;
			referer.each( function() {
				var $ref = jQuery( this );
				new_referer = $ref.val().split( '#' ).shift();
				new_referer += '#' + key;
				$ref.val( new_referer );
			});
			prev_item.removeClass( 'active' );
			item.addClass( 'active' );

			resize_content();
		};

		// Resize the content area.
		var resize_content = function resize_content( ev ) {
			var item = jQuery( '.lst-vnav-item.active', list ),
				item_data = jQuery( '> .data', item ).first(),
				data_height = item_data.outerHeight();

			if ( ! list_height ) {
				list_height = list.outerHeight();
			}

			list.css( {
				"min-height": data_height + "px"
			} );
			item_data.css( {
				"min-height": list_height + 'px'
			} );
		};

		// Mobile screen size functions.
		var toggle_sections = function toggle_sections( ev ) {
			if ( list.hasClass( 'open' ) ) {
				close_sections( ev );
			} else {
				open_sections( ev );
			}
		};

		var open_sections = function open_sections( ev ) {
			list.addClass( 'open' );
		};

		var close_sections = function close_sections( ev ) {
			list.removeClass( 'open' );
		};

		// Add click hander to change the section.
		context.on( 'click', 'h2,h3', activate_section );
		context.on( 'click', 'h2,h3', toggle_sections );

		// Hide the section list on window resize (mobile screens only).
		wnd.resize( resize_content );
		wnd.resize( close_sections );

		// Timeout of 50ms: Screen needs to refresh once before this works.
		window.setTimeout( function() {
			var ctx_active = jQuery( '.active', context );
			jQuery( 'h2,h3', ctx_active ).click();
		}, 50 );
	}

	// Add a new jQuery function to init a vnav container
	jQuery.fn.extend({
		wpmui_vnav: function() {
			return this.each(function() {
				init_vnav.apply( this );
				return this;
			});
		}
	});

	// Call the init method when page is loaded.
	init();

});