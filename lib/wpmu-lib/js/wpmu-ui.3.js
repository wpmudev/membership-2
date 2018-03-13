/*! WPMU Dev code library - v3.0.4
 * http://premium.wpmudev.org/
 * Copyright (c) 2017; * Licensed GPLv2+ */
/*!
 * WPMU Dev UI library
 * (Philipp Stracker for WPMU Dev)
 *
 * This library provides a Javascript API via the global wpmUi object.
 *
 * @version  1.0.0
 * @author   Philipp Stracker for WPMU Dev
 * @link     http://appendto.com/2010/10/how-good-c-habits-can-encourage-bad-javascript-habits-part-1/
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global XMLHttpRequest:false */
/*global console:false */
/*global wp:false */

(function( wpmUi ) {

	/**
	 * The document element.
	 *
	 * @type   jQuery object
	 * @since  1.0.0
	 * @private
	 */
	var _doc = null;

	/**
	 * The html element.
	 *
	 * @type   jQuery object
	 * @since  1.0.0
	 * @private
	 */
	var _html = null;

	/**
	 * The body element.
	 *
	 * @type   jQuery object
	 * @since  1.0.0
	 * @private
	 */
	var _body = null;

	/**
	 * Modal overlay, created by this object.
	 *
	 * @type   jQuery object
	 * @since  1.0.0
	 * @private
	 */
	var _modal_overlay = null;


	// ==========
	// == Public UI functions ==================================================
	// ==========


	/**
	 * Creates a new popup window.
	 *
	 * @since  1.0.0
	 * @param  string template Optional. The HTML template of the popup.
	 *         Note: The template should contain an element ".popup"
	 * @param  string css CSS styles to attach to the popup.
	 * @return WpmUiWindow A new popup window.
	 */
	wpmUi.popup = function popup( template, css ) {
		_init();
		return new wpmUi.WpmUiWindow( template, css );
	};

	/**
	 * Creates a new progress bar element.
	 *
	 * @since  2.0.2
	 * @return WpmUiProgress A new progress bar element.
	 */
	wpmUi.progressbar = function progressbar() {
		_init();
		return new wpmUi.WpmUiProgress();
	};

	/**
	 * Creates a new formdata object.
	 * With this object we can load or submit data via ajax.
	 *
	 * @since  1.0.0
	 * @param  string ajaxurl URL to the ajax handler.
	 * @param  string default_action The action to use when an ajax function
	 *                does not specify an action.
	 * @return WpmUiAjaxData A new formdata object.
	 */
	wpmUi.ajax = function ajax( ajaxurl, default_action ) {
		_init();
		return new wpmUi.WpmUiAjaxData( ajaxurl, default_action );
	};

	/**
	 * Upgrades normal multiselect fields to chosen-input fields.
	 *
	 * This function is a bottle-neck in Firefox -> el.chosen() takes quite long
	 *
	 * @since  1.0.0
	 * @param  jQuery|string base All children of this base element will be
	 *                checked. If empty then the body element is used.
	 */
	wpmUi.upgrade_multiselect = function upgrade_multiselect( base ) {
		_init();
		base = jQuery( base || _body );

		var items = base.find( 'select[multiple]' ).not( 'select[data-select-ajax]' ),
		ajax_items = base.find( 'select[data-select-ajax]' );

		// When an DOM container is *cloned* it may contain markup for a select2
		// listbox that is not attached to any event handler. Clean this up.
		var clean_ghosts = function clean_ghosts( el ) {
			var id = el.attr( 'id' ),
				s2id = '#s2id_' + id,
				ghosts = el.parent().find( s2id );

			ghosts.remove();
		};

		// Initialize normal select or multiselect list.
		var upgrade_item = function upgrade_item() {
			var el = jQuery( this ),
				options = {
					'closeOnSelect': false,
					'width': '100%'
				};

			// Prevent double initialization (i.e. conflict with other plugins)
			if ( typeof el.data( 'select2' ) === 'object' ) { return; }
			if ( typeof el.data( 'chosen' ) === 'object' ) { return; }
			if ( el.filter( '[class*=acf-]' ).length ) { return; }

			// Prevent double initialization (with other WPMU LIB plugin)
			if ( el.data( 'wpmui-select' ) === '1' ) { return; }

			// Prevent auto-initialization when manually disabled.
			if ( el.closest( '.no-auto-init', base[0] ).length ) { return; }

			el.data( 'wpmui-select', '1' );
			clean_ghosts( el );

			// Prevent lags during page load by making this asynchronous.
			if ( "function" === typeof( el.wpmuiSelect ) ) {
				window.setTimeout( function() {
					el.wpmuiSelect(options);
				}, 1);
			}
		};

		// Initialize select list with ajax source.
		var upgrade_ajax = function upgrade_ajax() {
			var format_item = function format_item( item ) {
				return item.text;
			};

			var el = jQuery( this ),
				options = {
					'closeOnSelect': false,
					'width': '100%',
					'multiple': true,
					'minimumInputLength': 1,
					'ajax': {
						url: el.attr( 'data-select-ajax' ),
						dataType: 'json',
						quietMillis: 100,
						cache: true,
						data: function(params) {
							return {
								q: params.term,
								page: params.page,
							};
						},
						processResults: function(data, params) {
							return {
								results: data.items
							};
						}
					},
					'templateResult': format_item,
					'templateSelection': format_item
				};

			// Prevent double initialization (i.e. conflict with other plugins)
			if ( typeof el.data( 'select2' ) === 'object' ) { return; }
			if ( typeof el.data( 'chosen' ) === 'object' ) { return; }
			if ( el.filter( '[class*=acf-]' ).length ) { return; }

			// Prevent double initialization (with other WPMU LIB plugin)
			if ( el.data( 'wpmui-select' ) === '1' ) { return; }

			// Prevent auto-initialization when manually disabled
			if ( el.closest( '.no-auto-init', base[0] ).length ) { return; }

			el.data( 'wpmui-select', '1' );
			clean_ghosts( el );

			// Prevent lags during page load by making this asynchronous.
			if ( "function" === typeof( el.wpmuiSelect ) ) {
				window.setTimeout( function() {
					el.wpmuiSelect(options);
				}, 1);
			}
		};

		if ( 'function' === typeof jQuery.fn.each2 ) {
			items.each2( upgrade_item );
			ajax_items.each2( upgrade_ajax );
		} else {
			items.each( upgrade_item );
			ajax_items.each( upgrade_ajax );
		}

	};

	/**
	 * Displays a WordPress-like message to the user.
	 *
	 * @since  1.0.0
	 * @param  string|object args Message options object or message-text.
	 *             args: {
	 *               'message': '...'
	 *               'type': 'ok|err'  // Style
	 *               'close': true     // Show close button?
	 *               'parent': '.wrap' // Element that displays the message
	 *               'insert_after': 'h2' // Inside the parent the message
	 *                                    // will be displayed after the
	 *                                    // first element of this type.
	 *                                    // Set to false to insert at top.
	 *                'id': 'msg-ok'   // When set to a string value then the
	 *                                 // the first call to "message()" will
	 *                                 // insert a new message and the next
	 *                                 // call will update the existing element.
	 *                'class': 'msg1'  // Additional CSS class.
	 *                'details': obj   // Details for error-type message.
	 *             }
	 */
	wpmUi.message = function message( args ) {
		var parent, msg_box, btn_close, need_insert, debug;
		_init();

		// Hides the message again, e.g. when user clicks the close icon.
		var hide_message = function hide_message( ev ) {
			ev.preventDefault();
			msg_box.remove();
			return false;
		};

		// Toggle the error-details
		var toggle_debug = function toggle_debug( ev ) {
			var me = jQuery( this ).closest( '.wpmui-msg' );
			me.find( '.debug' ).toggle();
		};

		if ( 'undefined' === typeof args ) { return false; }

		if ( 'string' === typeof args || args instanceof Array ) {
			args = { 'message': args };
		}

		if ( args['message'] instanceof Array ) {
			args['message'] = args['message'].join( '<br />' );
		}

		if ( ! args['message'] ) { return false; }

		args['type'] = undefined === args['type'] ? 'ok' : args['type'].toString().toLowerCase();
		args['close'] = undefined === args['close'] ? true : args['close'];
		args['parent'] = undefined === args['parent'] ? '.wrap' : args['parent'];
		args['insert_after'] = undefined === args['insert_after'] ? 'h2' : args['insert_after'];
		args['id'] = undefined === args['id'] ? '' : args['id'].toString().toLowerCase();
		args['class'] = undefined === args['class'] ? '' : args['class'].toString().toLowerCase();
		args['details'] = undefined === args['details'] ? false : args['details'];

		if ( args['type'] === 'error' || args['type'] === 'red' ) { args['type'] = 'err'; }
		if ( args['type'] === 'success' || args['type'] === 'green' ) { args['type'] = 'ok'; }

		parent = jQuery( args['parent'] ).first();
		if ( ! parent.length ) { return false; }

		if ( args['id'] && jQuery( '.wpmui-msg[data-id="' + args['id'] + '"]' ).length ) {
			msg_box = jQuery( '.wpmui-msg[data-id="' + args['id'] + '"]' ).first();
			need_insert = false;
		} else {
			msg_box = jQuery( '<div><p></p></div>' );
			if ( args['id'] ) { msg_box.attr( 'data-id', args['id'] ); }
			need_insert = true;
		}
		msg_box.find( 'p' ).html( args['message'] );

		if ( args['type'] === 'err' && args['details'] && window.JSON ) {
			jQuery( '<div class="debug" style="display:none"></div>' )
				.appendTo( msg_box )
				.text( JSON.stringify( args['details'] ) );
			jQuery( '<i class="dashicons dashicons-editor-help light"></i>' )
				.prependTo( msg_box.find( 'p:first' ) )
				.click( toggle_debug )
				.after( ' ' );
		}

		msg_box.removeClass().addClass( 'updated wpmui-msg ' + args['class'] );
		if ( 'err' === args['type'] ) {
			msg_box.addClass( 'error' );
		}

		if ( need_insert ) {
			if ( args['close'] ) {
				btn_close = jQuery( '<a href="#" class="notice-dismiss"></a>' );
				btn_close.prependTo( msg_box );

				btn_close.click( hide_message );
			}

			if ( args['insert_after'] && parent.find( args['insert_after'] ).length ) {
				parent = parent.find( args['insert_after'] ).first();
				parent.after( msg_box );
			} else {
				parent.prepend( msg_box );
			}
		}

		return true;
	};

	/**
	 * Displays confirmation box to the user.
	 *
	 * The layer is displayed in the upper half of the parent element and is by
	 * default modal.
	 * Note that the confirmation is asynchronous and the functions return value
	 * only indicates if the confirmation message was created, and not the users
	 * response!
	 *
	 * Also this is a "disponsable" function which does not create DOM elements
	 * that can be re-used. All elements are temporary and are removed when the
	 * confirmation is closed. Only 1 confirmation should be displayed at a time.
	 *
	 * @since  1.0.14
	 * @param  object args {
	 *     Confirmation options.
	 *
	 *     string message
	 *     bool modal
	 *     string layout 'fixed' or 'absolute'
	 *     jQuery parent A jQuery object or selector
	 *     array buttons Default is ['OK']
	 *     function(key) callback Receives array-index of the pressed button
	 * }
	 * @return bool True if the confirmation is created correctly.
	 */
	wpmUi.confirm = function confirm( args ) {
		var parent, modal, container, el_msg, el_btn, ind, item, primary_button;

		if ( ! args instanceof Object ) { return false; }
		if ( undefined === args['message'] ) { return false; }

		args['modal'] = undefined === args['modal'] ? true : args['modal'];
		args['layout'] = undefined === args['layout'] ? 'fixed' : args['layout'];
		args['parent'] = undefined === args['parent'] ? _body : args['parent'];
		args['buttons'] = undefined === args['buttons'] ? ['OK'] : args['buttons'];
		args['callback'] = undefined === args['callback'] ? false : args['callback'];

		parent = jQuery( args['parent'] );

		function handle_close() {
			var me = jQuery( this ),
				key = parseInt( me.data( 'key' ) );

			if ( args['modal'] ) {
				if ( args['layout'] === 'fixed' ) {
					wpmUi._close_modal();
				} else {
					modal.remove();
				}
			}
			container.remove();

			if ( 'function' === typeof args['callback'] ) {
				args['callback']( key );
			}
		}

		if ( args['modal'] ) {
			if ( args['layout'] === 'fixed' ) {
				wpmUi._make_modal( 'wpmui-confirm-modal' );
			} else {
				modal = jQuery( '<div class="wpmui-confirm-modal"></div>' )
					.css( { 'position': args['layout'] } )
					.appendTo( parent );
			}
		}

		container = jQuery( '<div class="wpmui-confirm-box"></div>' )
			.css( { 'position': args['layout'] } )
			.appendTo( parent );

		el_msg = jQuery( '<div class="wpmui-confirm-msg"></div>' )
			.html( args['message'] );

		el_btn = jQuery( '<div class="wpmui-confirm-btn"></div>' );
		primary_button = true;
		for ( ind = 0; ind < args['buttons'].length; ind += 1 ) {
			item = jQuery( '<button></button>' )
				.html( args['buttons'][ind] )
				.addClass( primary_button ? 'button-primary' : 'button-secondary' )
				.data( 'key', ind )
				.click( handle_close )
				.prependTo( el_btn );
			primary_button = false;
		}

		el_msg.appendTo( container );
		el_btn.appendTo( container )
			.find( '.button-primary' )
			.focus();

		return true;
	};

	/**
	 * Attaches a tooltip to the specified element.
	 *
	 * @since  1.0.0
	 * @param jQuery el The host element that receives the tooltip.
	 * @param object|string args The tooltip options. Either a string containing
	 *                the toolip message (HTML code) or an object with details:
	 *                - content
	 *                - trigger [hover|click]
	 *                - pos [top|bottom|left|right]
	 *                - class
	 */
	wpmUi.tooltip = function tooltip( el, args ) {
		var tip, parent;
		_init();

		// Positions the tooltip according to the function args.
		var position_tip = function position_tip( tip ) {
			var tip_width = tip.outerWidth(),
				tip_height = tip.outerHeight(),
				tip_padding = 5,
				el_width = el.outerWidth(),
				el_height = el.outerHeight(),
				pos = {};

			pos['left'] = (el_width - tip_width) / 2;
			pos['top'] = (el_height - tip_height) / 2;
			pos[ args['pos'] ] = 'auto';

			switch ( args['pos'] ) {
				case 'top':    pos['bottom'] = el_height + tip_padding; break;
				case 'bottom': pos['top'] = el_height + tip_padding; break;
				case 'left':   pos['right'] = el_width + tip_padding; break;
				case 'right':  pos['left'] = el_width + tip_padding; break;
			}
			tip.css(pos);
		};

		// Make the tooltip visible.
		var show_tip = function show_tip( ev ) {
			var tip = jQuery( this )
				.closest( '.wpmui-tip-box' )
				.find( '.wpmui-tip' );

			tip.addClass( 'wpmui-visible' );
			tip.show();
			position_tip( tip );
			window.setTimeout( function() { position_tip( tip ); }, 35 );
		};

		// Hide the tooltip.
		var hide_tip = function hide_tip( ev ) {
			var tip = jQuery( this )
				.closest( '.wpmui-tip-box' )
				.find( '.wpmui-tip' );

			tip.removeClass( 'wpmui-visible' );
			tip.hide();
		};

		// Toggle the tooltip state.
		var toggle_tip = function toggle_tip( ev ) {
			if ( tip.hasClass( 'wpmui-visible' ) ) {
				hide_tip.call(this, ev);
			} else {
				show_tip.call(this, ev);
			}
		};

		if ( 'string' === typeof args ) {
			args = { 'content': args };
		}
		if ( undefined === args['content'] ) {
			return false;
		}
		el = jQuery( el );
		if ( ! el.length ) {
			return false;
		}

		args['trigger'] = undefined === args['trigger'] ? 'hover' : args['trigger'].toString().toLowerCase();
		args['pos'] = undefined === args['pos'] ? 'top' : args['pos'].toString().toLowerCase();
		args['class'] = undefined === args['class'] ? '' : args['class'].toString().toLowerCase();

		parent = el.parent();
		if ( ! parent.hasClass( 'wpmui-tip-box' ) ) {
			parent = el
				.wrap( '<span class="wpmui-tip-box"></span>' )
				.parent()
				.addClass( args['class'] + '-box' );
		}

		tip = parent.find( '> .wpmui-tip' );
		el.off();

		if ( ! tip.length ) {
			tip = jQuery( '<div class="wpmui-tip"></div>' );
			tip
				.addClass( args['class'] )
				.addClass( args['pos'] )
				.appendTo( el.parent() )
				.hide();

			if ( ! isNaN( args['width'] ) ) {
				tip.width( args['width'] );
			}
		}

		if ( 'hover' === args['trigger'] ) {
			el.on( 'mouseenter', show_tip ).on( 'mouseleave', hide_tip );
		} else if ( 'click' === args['trigger'] ) {
			el.on( 'click', toggle_tip );
		}

		tip.html( args['content'] );

		return true;
	};

	/**
	 * Checks the DOM and creates tooltips for the DOM Elements that specify
	 * tooltip details.
	 *
	 * Function can be called repeatedly and will refresh the tooltip contents
	 * if they changed since last call.
	 *
	 * @since  1.0.8
	 */
	wpmUi.upgrade_tooltips = function upgrade_tooltips() {
		var el = jQuery( '[data-wpmui-tooltip]' );

		el.each(function() {
			var me = jQuery( this ),
				args = {
					'content': me.attr( 'data-wpmui-tooltip' ),
					'pos': me.attr( 'data-pos' ),
					'trigger': me.attr( 'data-trigger' ),
					'class': me.attr( 'data-class' ),
					'width': me.attr( 'data-width' )
				};

			wpmUi.tooltip( me, args );
		});
	};

	/*
	 * Converts any value to an object.
	 * Typically used to convert an array to an object.
	 *
	 * @since  1.0.6
	 * @param  mixed value This value is converted to an JS-object.
	 * @return object
	 */
	wpmUi.obj = function( value ) {
		var obj = {};

		if ( value instanceof Object ) {
			obj = value;
		}
		else if ( value instanceof Array ) {
			if ( typeof value.reduce === 'function' ) {
				obj = value.reduce(function(o, v, i) {
					o[i] = v;
					return o;
				}, {});
			} else {
				for ( var i = value.length - 1; i > 0; i -= 1 ) {
					if ( value[i] !== undefined ) {
						obj[i] = value[i];
					}
				}
			}
		}
		else if ( typeof value === 'string' ) {
			obj.scalar = value;
		}
		else if ( typeof value === 'number' ) {
			obj.scalar = value;
		}
		else if ( typeof value === 'boolean' ) {
			obj.scalar = value;
		}

		return obj;
	};

	/**
	 * Initialize the Radio Sliders
	 *
	 * @since  3.0.5
	 */
	wpmUi.bind_radio_sliders = function() {
		jQuery('.wpmui-radio-slider').on('click', function( ev ) {
			var radio = jQuery(this);
			var data = jQuery('.wpmui-toggle', radio).data('states');
			var field = jQuery('input.wpmui-hidden[type=hidden]', radio );
			var active = Object.keys( data )[0];
			var value = 0;
			if ( 1 === parseInt(field.val()) || active === field.val() )  {
				radio.removeClass( 'on' );
				value = data[Object.keys( data )[1]];
			} else {
				radio.addClass( 'on' );
				value = data[Object.keys( data )[0]];
			}
			if ( 'boolean' === typeof( value ) ) {
				value = value? 1:0;
			}
			field.val( value );
		});
	};

	/**
	 * Initialize the wpColorPicker
	 *
	 * @since  3.0.5
	 */
	wpmUi.bind_wp_color_picker = function() {
		if ( jQuery.fn.wpColorPicker ) {
			jQuery('.wpmui-color-field').wpColorPicker();
		}
	};

	/**
	 * Initialize the wp_media
	 *
	 * @since  3.0.5
	 */
	wpmUi.bind_wp_media = function() {
		jQuery(".option-wp_media .image-reset").on("click", function( event ){
			var container = jQuery(this).closest(".option-wp_media");
			jQuery(".filename", container ).html( "" );
			jQuery(".image-preview", container ).removeAttr( "src" );
			jQuery(".attachment-id", container ).removeAttr("value");
			jQuery(this).addClass("disabled");
			jQuery('.wp-media-wrapper', container ).addClass('hidden');
		});
		jQuery('.option-wp_media .button-select-image').on('click', function( event ){
			var file_frame;
			var wp_media_post_id;
			var container = jQuery(this).closest('.option-wp_media');
			var set_to_post_id = jQuery('.attachment-id', container ).val();
			event.preventDefault();
			// If the media frame already exists, reopen it.
			if ( file_frame ) {
				// Set the post ID to what we want
				file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
				// Open frame
				file_frame.open();
				return;
			} else {
				// Set the wp.media post id so the uploader grabs the ID we want when initialised
				wp.media.model.settings.post.id = set_to_post_id;
			}
			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media({
				title: 'Select a image to upload',
				button: {
					text: 'Use this image',
				},
				multiple: false	// Set to true to allow multiple files to be selected
			});
			// When an image is selected, run a callback.
			file_frame.on( 'select', function( wp_media_post_id ) {
				// We set multiple to false so only get one image from the uploader
				var attachment = file_frame.state().get('selection').first().toJSON();
				// Do something with attachment.id and/or attachment.url here
				jQuery('.filename', container).html(attachment.filename);
				jQuery('.image-preview', container ).attr( 'src', attachment.url ).css( 'width', 'auto' );
				jQuery('.attachment-id', container ).val( attachment.id );
				jQuery(".image-reset", container).removeClass("disabled");
				jQuery('.wp-media-wrapper', container ).removeClass('hidden');
				// Restore the main post ID
				wp.media.model.settings.post.id = wp_media_post_id;
			});
			// Finally, open the modal
			file_frame.open();
		});
		// Restore the main ID when the add media button is pressed
		jQuery( 'a.add_media' ).on( 'click', function( wp_media_post_id ) {
			wp.media.model.settings.post.id = wp_media_post_id;
		});
	};

	// ==========
	// == Private helper functions =============================================
	// ==========


	/**
	 * Initialize the object
	 *
	 * @since  1.0.0
	 * @private
	 */
	function _init() {
		if ( null !== _html ) { return; }

		_doc = jQuery( document );
		_html = jQuery( 'html' );
		_body = jQuery( 'body' );

		_init_boxes();
		_init_tabs();

		if ( ! _body.hasClass( 'no-auto-init' ) ) {
			/**
			 * Do the auto-initialization stuff after a short delay, so other
			 * scripts can run first.
			 */
			window.setTimeout(function() {
				wpmUi.upgrade_multiselect();
				wpmUi.upgrade_tooltips();
				wpmUi.bind_radio_sliders();
				wpmUi.bind_wp_color_picker();
				wpmUi.bind_wp_media();
			}, 20);
		}

		wpmUi.binary = new wpmUi.WpmUiBinary();
	}

	/**
	 * Returns the modal overlay object
	 *
	 * @since  1.1.0
	 * @private
	 */
	wpmUi._modal_overlay = function() {
		if ( null === _modal_overlay ) {
			_modal_overlay = jQuery( '<div></div>' )
				.addClass( 'wpmui-overlay' )
				.appendTo( _body );
		}
		return _modal_overlay;
	};

	/**
	 * Shows a modal background layer
	 *
	 * @since  1.0.0
	 * @param  string the_class CSS class added to the overlay.
	 * @param  string html_classes Additional CSS classes added to the HTML tag.
	 * @private
	 */
	wpmUi._make_modal = function( the_class, html_classes ) {
		var overlay = wpmUi._modal_overlay();

		overlay.removeClass().addClass( 'wpmui-overlay' );
		if ( the_class ) {
			overlay.addClass( the_class );
		}

		_body.addClass( 'wpmui-has-overlay' );
		_html.addClass( 'wpmui-no-scroll' );
		if ( html_classes ) {
			_html.addClass( html_classes );
		}

		return overlay;
	};

	/**
	 * Closes the modal background layer again.
	 *
	 * @since  1.0.0
	 * @param  string html_classes Additional CSS classes to remove from HTML tag.
	 * @private
	 */
	wpmUi._close_modal = function( html_classes ) {
		_body.removeClass( 'wpmui-has-overlay' );
		_html.removeClass( 'wpmui-no-scroll' );
		if ( html_classes) {
			_html.removeClass( html_classes );
		}
	};

	/**
	 * Initialize the WordPress-ish accordeon boxes:
	 * Open or close boxes when user clicks the toggle icon.
	 *
	 * @since  1.0.0
	 */
	function _init_boxes() {
		// Toggle the box state (open/closed)
		var toggle_box = function toggle_box( ev ) {
			var box = jQuery( this ).closest( '.wpmui-box' );
			ev.preventDefault();

			// Don't toggle the box if it is static.
			if ( box.hasClass( 'static' ) ) { return false; }

			box.toggleClass( 'closed' );
			return false;
		};

		_body.on( 'click', '.wpmui-box > h3', toggle_box );
		_body.on( 'click', '.wpmui-box > h3 > .toggle', toggle_box );
	}

	/**
	 * Initialize the WordPress-ish tab navigation:
	 * Change the tab on click.
	 *
	 * @since  1.0.0
	 */
	function _init_tabs() {
		// Toggle the box state (open/closed)
		var activate_tab = function activate_tab( ev ) {
			var tab = jQuery( this ),
				all_tabs = tab.closest( '.wpmui-tabs' ),
				content = all_tabs.next( '.wpmui-tab-contents' ),
				active = all_tabs.find( '.active.tab' ),
				sel_tab = tab.attr( 'href' ),
				sel_active = active.attr( 'href' ),
				content_tab = content.find( sel_tab ),
				content_active = content.find( sel_active );

			// Close previous tab.
			if ( ! tab.hasClass( 'active' ) ) {
				active.removeClass( 'active' );
				content_active.removeClass( 'active' );
			}

			// Open selected tab.
			tab.addClass( 'active' );
			content_tab.addClass( 'active' );

			ev.preventDefault();
			return false;
		};

		_body.on( 'click', '.wpmui-tabs .tab', activate_tab );
	}

	// Initialize the object.
	jQuery(function() {
		_init();
	});

}( window.wpmUi = window.wpmUi || {} ));


/*!
 * WPMU Dev UI library
 * (Philipp Stracker for WPMU Dev)
 *
 * This module provides the WpmUiWindow object which is a smart and easy to use
 * Pop-up.
 *
 * @version  3.0.0
 * @author   Philipp Stracker for WPMU Dev
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */

(function( wpmUi ) {

	/*============================*\
	================================
	==                            ==
	==           WINDOW           ==
	==                            ==
	================================
	\*============================*/

	/**
	 * The next popup ID to use
	 *
	 * @type int
	 * @since  1.1.0
	 * @internal
	 */
	var _next_id = 1;

	/**
	 * A list of all popups
	 *
	 * @type array
	 * @since  1.1.0
	 * @internal
	 */
	var _all_popups = {};

	/**
	 * Returns a list with all currently open popups.
	 *
	 * When a popup is created it is added to the list.
	 * When it is closed (not hidden!) it is removed.
	 *
	 * @since  1.1.0
	 * @return WpmUiWindow[]
	 */
	wpmUi.popups = function() {
		return _all_popups;
	};

	/**
	 * Popup window.
	 *
	 * @type   WpmUiWindow
	 * @since  1.0.0
	 */
	wpmUi.WpmUiWindow = function( _template, _css ) {

		/**
		 * Backreference to the WpmUiWindow object.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _me = this;

		/**
		 * Stores the state of the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _visible = false;

		/**
		 * Defines if a modal background should be visible.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _modal = false;

		/**
		 * Defines if the dialog title should contain a close button.
		 *
		 * @since  1.1.2
		 * @internal
		 */
		var _title_close = true;

		/**
		 * Defines if clicking in the modal background closes the dialog.
		 *
		 * @since  1.1.2
		 * @internal
		 */
		var _background_close = true;

		/**
		 * Size of the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _width = 740;

		/**
		 * Size of the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _height = 400;

		/**
		 * Title of the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _title = 'Window';

		/**
		 * Content of the window. Either a jQuery selector/object or HTML code.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _content = '';

		/**
		 * Class names to add to the popup window
		 *
		 * @since  1.0.14
		 * @internal
		 */
		var _classes = '';

		/**
		 * Opening animation - triggered when .show() is called.
		 *
		 * @since  3.0.0
		 * @internal
		 */
		var _animation_in = '';

		/**
		 * Closing animation - triggered when .hide() or .close() is called.
		 *
		 * @since  3.0.0
		 * @internal
		 */
		var _animation_out = '';

		/**
		 * Is set to true when new content is assigned to the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _content_changed = false;

		/**
		 * Flag is set to true when the window size was changed.
		 * After the window was updated we will additionally check if it is
		 * visible in the current viewport.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		var _need_check_size = false;

		/**
		 * Position of the popup, can contain one or more of these flags:
		 * 'none', 'left', 'right', 'top', 'bottom'
		 *
		 * @since  2.0.0
		 * @internal
		 */
		var _snap = { top: false, left: false, right: false, bottom: false };

		/**
		 * Define closing-behavior of the popup to be a slide-in:
		 * 'none', 'up', 'down'
		 *
		 * @since  2.0.0
		 * @internal
		 */
		var _slidein = 'none';

		/**
		 * Called after the window is made visible.
		 *
		 * @type  Callback function.
		 * @since  1.0.0
		 * @internal
		 */
		var _onshow = null;

		/**
		 * Called after the window was hidden.
		 *
		 * @type  Callback function.
		 * @since  1.0.0
		 * @internal
		 */
		var _onhide = null;

		/**
		 * Called after the window was hidden + destroyed.
		 *
		 * @type  Callback function.
		 * @since  1.0.0
		 * @internal
		 */
		var _onclose = null;

		/**
		 * Custom resize handler.
		 *
		 * @type  Callback function.
		 * @since  1.0.0
		 * @internal
		 */
		var _onresize = null;

		/**
		 * The popup container element. This is the outermost DOM element of the
		 * popup. The _wnd element might contain additional data, such as a
		 * CSS <style> tag that belongs to the popup.
		 *
		 * The _wnd element
		 * - is attached/detached from the DOM on show/hide
		 * - is positioned during resize
		 * - is positioned during open/close of slide-in
		 * - can contain <style> tag or hidden .buttons element
		 *
		 * @type  jQuery object.
		 * @since  1.0.0
		 * @internal
		 */
		var _wnd = null;

		/**
		 * The popup window element.
		 * By default this is identical to _wnd, but might be different when
		 * using a custom template. This is the element with class .popup
		 *
		 * The _popup element
		 * - is displayed/hidden during show/hide
		 * - is animated during show/hide
		 * - contains the loading-animation via .loading(true)
		 * - all dynamic classes are added to this element
		 *
		 * @type  jQuery object.
		 * @since  3.0.0
		 * @internal
		 */
		var _popup = null;

		/**
		 * Window status: visible, hidden, closing
		 *
		 * @type   string
		 * @since  1.0.14
		 * @internal
		 */
		var _status = 'hidden';


		/**
		 * Slide-in status: collapsed, collapsing, expanded, expaning
		 *
		 * @type   string
		 * @since  2.0.0
		 * @internal
		 */
		var _slidein_status = 'none';

		/**
		 * Slide-in Icon for collapsed state
		 *
		 * @type   string
		 * @since  2.0.0
		 * @internal
		 */
		var _icon_collapse = '';

		/**
		 * Slide-in Icon for expanded state
		 *
		 * @type   string
		 * @since  2.0.0
		 * @internal
		 */
		var _icon_expand = '';

		/**
		 * Slide-in option that defines the speed to expand/collapse the popup.
		 *
		 * @type   number
		 * @since  2.0.0
		 * @internal
		 */
		var _slidein_speed = 400;


		// ==============================
		// == Public functions ==========

		/**
		 * The official popup ID
		 *
		 * @since 1.1.0
		 * @type  int
		 */
		this.id = 0;

		/**
		 * Returns the modal property.
		 *
		 * @since  2.0.0
		 */
		this.is_modal = function is_modal() {
			return _modal;
		};

		/**
		 * Returns the visible-state property.
		 *
		 * @since  2.0.0
		 */
		this.is_visible = function is_visible() {
			return _visible;
		};

		/**
		 * Returns the slidein property.
		 *
		 * @since  2.0.0
		 */
		this.is_slidein = function is_slidein() {
			return _slidein;
		};

		/**
		 * Returns the _snap property.
		 *
		 * @since  2.0.0
		 */
		this.get_snap = function get_snap() {
			return _snap;
		};

		/**
		 * Sets the modal property.
		 *
		 * @since  1.0.0
		 */
		this.modal = function modal( state, background_close ) {
			if ( undefined === background_close ) {
				background_close = true;
			}

			_modal = ( state ? true : false );
			_background_close = ( background_close ? true : false );

			_update_window();
			return _me;
		};

		/**
		 * Sets the window size.
		 *
		 * @since  1.0.0
		 */
		this.size = function size( width, height ) {
			var new_width = parseFloat( width ),
				new_height = parseFloat( height );

			if ( isNaN( new_width ) ) { new_width = 0; }
			if ( isNaN( new_height ) ) { new_height = 0; }
			if ( new_width >= 0 ) { _width = new_width; }
			if ( new_height >= 0 ) { _height = new_height; }

			_need_check_size = true;
			_update_window();
			return _me;
		};

		/**
		 * Sets the snap-constraints of the popup.
		 *
		 * @since  2.0.0
		 */
		this.snap = function snap() {
			var is_middle = false;
			_snap = { top: false, left: false, right: false, bottom: false };

			for ( var i = 0; i < arguments.length && ! is_middle; i += 1 ) {
				var snap_to = arguments[i].toLowerCase();

				switch(snap_to) {
					case 'top':
					case 'left':
					case 'right':
					case 'bottom':
						_snap[snap_to] = true;
						break;

					case 'none':
					case 'center':
						is_middle = true;
						break;
				}
			}

			if ( is_middle ) {
				_snap = { top: false, left: false, right: false, bottom: false };
			}

			_need_check_size = true;
			_update_window();
			return _me;
		};

		/**
		 * Enables or disables the slide-in function of the popup.
		 *
		 * @since  2.0.0
		 */
		this.slidein = function slidein( option, duration ) {
			option = option.toLowerCase();
			_slidein = 'none';

			switch ( option ) {
				case 'down':
					_slidein = 'down';
					_icon_collapse = 'dashicons-arrow-down-alt2';
					_icon_expand = 'dashicons-arrow-up-alt2';
					break;

				case 'up':
					_slidein = 'up';
					_icon_collapse = 'dashicons-arrow-up-alt2';
					_icon_expand = 'dashicons-arrow-down-alt2';
					break;
			}

			if ( ! isNaN( duration ) && duration >= 0 ) {
				_slidein_speed = duration;
			}

			_need_check_size = true;
			_update_window();
			return _me;
		};

		/**
		 * Define the opening and closing animation for the popup.
		 *
		 * @since  3.0.0
		 */
		this.animate = function animate( anim_in, anim_out ) {
			var can_animate = false,
				domPrefixes = 'Webkit Moz O ms Khtml'.split( ' ' );

			if ( _popup[0].style.animationName !== undefined ) { can_animate = true; }

			if ( can_animate === false ) {
				for ( var i = 0; i < domPrefixes.length; i++ ) {
					if ( _popup[0].style[ domPrefixes[i] + 'AnimationName' ] !== undefined ) {
						can_animate = true;
						break;
					}
				}
			}

			if ( ! can_animate ) {
				// Sorry guys, CSS animations are not supported...
				anim_in = '';
				anim_out = '';
			}

			_animation_in = anim_in;
			_animation_out = anim_out;

			return _me;
		};

		/**
		 * Sets optional classes for the main window element.
		 *
		 * @since  1.0.14
		 */
		this.set_class = function set_class( class_names ) {
			_classes = class_names;
			_content_changed = true;

			_update_window();
			return _me;
		};

		/**
		 * Define a callback that is executed when the popup needs to be moved
		 * or resized.
		 *
		 * @since  3.0.0
		 */
		this.onresize = function onresize( callback ) {
			_onresize = callback;
			return _me;
		};

		/**
		 * Define a callback that is executed after popup is made visible.
		 *
		 * @since  1.0.0
		 */
		this.onshow = function onshow( callback ) {
			_onshow = callback;
			return _me;
		};

		/**
		 * Define a callback that is executed after popup is hidden.
		 *
		 * @since  1.0.0
		 */
		this.onhide = function onhide( callback ) {
			_onhide = callback;
			return _me;
		};

		/**
		 * Define a callback that is executed after popup was destroyed.
		 *
		 * @since  1.0.0
		 */
		this.onclose = function onclose( callback ) {
			_onclose = callback;
			return _me;
		};

		/**
		 * Add a loading-overlay to the popup or remove the overlay again.
		 *
		 * @since  1.0.0
		 * @param  bool state True will add the overlay, false removes it.
		 */
		this.loading = function loading( state ) {
			if ( state ) {
				_popup.addClass( 'wpmui-loading' );
			} else {
				_popup.removeClass( 'wpmui-loading' );
			}
			return _me;
		};

		/**
		 * Shows a confirmation box inside the popup
		 *
		 * @since  1.0.14
		 * @param  object args Message options
		 */
		this.confirm = function confirm( args ) {
			if ( _status !== 'visible' ) { return _me; }
			if ( ! args instanceof Object ) { return _me; }

			args['layout'] = 'absolute';
			args['parent'] = _popup;

			wpmUi.confirm( args );

			return _me;
		};

		/**
		 * Sets the window title.
		 *
		 * @since  1.0.0
		 */
		this.title = function title( new_title, can_close ) {
			if ( undefined === can_close ) {
				can_close = true;
			}

			_title = new_title;
			_title_close = ( can_close ? true : false );

			_update_window();
			return _me;
		};

		/**
		 * Sets the window content.
		 *
		 * @since  1.0.0
		 */
		this.content = function content( data, move ) {
			if ( data instanceof jQuery ) {
				if ( move ) {
					// Move the object into the popup.
					_content = data;
				} else {
					// Create a copy of the object inside the popup.
					_content = data.html();
				}
			} else {
				// Content is text, will always be a copy.
				_content = data;
			}

			_need_check_size = true;
			_content_changed = true;

			_update_window();
			return _me;
		};

		/**
		 * Show the popup window.
		 *
		 * @since  1.0.0
		 */
		this.show = function show() {
			// Add the DOM elements to the document body and add event handlers.
			_wnd.appendTo( jQuery( 'body' ) );
			_popup.hide();
			_hook();

			_visible = true;
			_need_check_size = true;
			_status = 'visible';

			_update_window();

			// Fix issue where Buttons are not available in Chrome
			// https://app.asana.com/0/11388810124414/18688920614102
			_popup.hide();
			window.setTimeout(function() {
				// The timeout is so short that the element will *not* be
				// hidden but webkit will still redraw the element.
				_popup.show();
			}, 2);

			if ( 'none' === _slidein && _animation_in ) {
				_popup.addClass( _animation_in + ' animated' );
				_popup.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function() {
					_popup.removeClass( 'animated' );
					_popup.removeClass( _animation_in );
				});
			}

			if ( typeof _onshow === 'function' ) {
				_onshow.apply( _me, [ _me.$() ] );
			}
			return _me;
		};

		/**
		 * Hide the popup window.
		 *
		 * @since  1.0.0
		 */
		this.hide = function hide() {
			function hide_popup() {
				if ( 'none' === _slidein ) {
					// Remove the popup from the DOM (but keep it in memory)
					_wnd.detach();
					_unhook();
				}

				_visible = false;
				_status = 'hidden';
				_update_window();

				if ( typeof _onhide === 'function' ) {
					_onhide.apply( _me, [ _me.$() ] );
				}
			}

			if ( 'none' === _slidein && _animation_out ) {
				_popup.addClass( _animation_out + ' animated' );
				_popup.one(
					'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend',
					function() {
						_popup.removeClass( 'animated' );
						_popup.removeClass( _animation_out );
						hide_popup();
					}
				);
			} else {
				hide_popup();
			}

			return _me;
		};

		/**
		 * Completely removes the popup window.
		 * The popup object cannot be re-used after calling this function.
		 *
		 * @since  1.0.0
		 */
		this.destroy = function destroy() {
			var orig_onhide = _onhide;

			// Prevent infinite loop when calling .destroy inside onclose handler.
			if ( _status === 'closing' ) { return; }

			_onhide = function() {
				if ( typeof orig_onhide === 'function' ) {
					orig_onhide.apply( _me, [ _me.$() ] );
				}

				_status = 'closing';

				if ( typeof _onclose === 'function' ) {
					_onclose.apply( _me, [ _me.$() ] );
				}

				// Completely remove the popup from the memory.
				_wnd.remove();
				_wnd = null;
				_popup = null;

				delete _all_popups[_me.id];

				_me = null;
			};

			_me.hide();
		};

		/**
		 * Adds an event handler to the dialog.
		 *
		 * @since  2.0.1
		 */
		this.on = function on( event, selector, callback ) {
			_wnd.on( event, selector, callback );

			if ( _wnd.filter( selector ).length ) {
				_wnd.on( event, callback );
			}

			return _me;
		};

		/**
		 * Removes an event handler from the dialog.
		 *
		 * @since  2.0.1
		 */
		this.off = function off( event, selector, callback ) {
			_wnd.off( event, selector, callback );

			if ( _wnd.filter( selector ).length ) {
				_wnd.off( event, callback );
			}

			return _me;
		};

		/**
		 * Returns the jQuery object of the window
		 *
		 * @since  1.0.0
		 */
		this.$ = function $( selector ) {
			if ( selector ) {
				return _wnd.find( selector );
			} else {
				return _wnd;
			}
		};


		// ==============================
		// == Private functions =========


		/**
		 * Create the DOM elements for the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _init() {
			_me.id = _next_id;
			_next_id += 1;
			_all_popups[_me.id] = _me;

			if ( ! _template ) {
				// Defines the default popup template.
				_template = '<div class="wpmui-popup">' +
					'<div class="popup-title">' +
						'<span class="the-title"></span>' +
						'<span class="popup-close"><i class="dashicons dashicons-no-alt"></i></span>' +
					'</div>' +
					'<div class="popup-content"></div>' +
					'</div>';
			}

			// Create the DOM elements.
			_wnd = jQuery( _template );

			// Add custom CSS.
			if ( _css ) {
				jQuery( '<style>' + _css + '</style>' ).prependTo( _wnd );
			}

			// Add default selector class to the base element if the class is missing.
			if ( ! _wnd.filter( '.popup' ).length && ! _wnd.find( '.popup' ).length ) {
				_wnd.addClass( 'popup' );
			}

			// See comments in top section for difference between _wnd and _popup.
			if ( _wnd.hasClass( 'popup' ) ) {
				_popup = _wnd;
			} else {
				_popup = _wnd.find( '.popup' ).first();
			}

			// Add supported content modification methods.
			if ( ! _popup.find( '.popup-title' ).length ) {
				_me.title = function() { return _me; };
			}

			if ( ! _popup.find( '.popup-content' ).length ) {
				_me.content = function() { return _me; };
			}

			if ( ! _popup.find( '.slidein-toggle' ).length ) {
				if (  _popup.find( '.popup-title .popup-close' ).length ) {
					_popup.find( '.popup-title .popup-close' ).addClass( 'slidein-toggle' );
				} else if ( _popup.find( '.popup-title' ).length ) {
					_popup.find( '.popup-title' ).addClass( 'slidein-toggle' );
				} else {
					_popup.prepend( '<span class="slidein-toggle only-slidein"><i class="dashicons"></i></span>' );
				}
			}

			_visible = false;
		}

		/**
		 * Add event listeners.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _hook() {
			if ( _popup && ! _popup.data( 'hooked' ) ) {
				_popup.data( 'hooked', true );
				_popup.on( 'click', '.popup-close', _click_close );
				_popup.on( 'click', '.popup-title', _click_title );
				_popup.on( 'click', '.close', _me.hide );
				_popup.on( 'click', '.destroy', _me.destroy );
				_popup.on( 'click', 'thead .check-column :checkbox', _toggle_checkboxes );
				_popup.on( 'click', 'tfoot .check-column :checkbox', _toggle_checkboxes );
				_popup.on( 'click', 'tbody .check-column :checkbox', _check_checkboxes );
				jQuery( window ).on( 'resize', _resize_and_move );

				if ( jQuery().draggable !== undefined ) {
					_popup.draggable({
						containment: jQuery( 'body' ),
						scroll: false,
						handle: '.popup-title'
					});
				}
			}
		}

		/**
		 * Remove all event listeners.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _unhook() {
			if ( _popup && _popup.data( 'hooked' ) ) {
				_popup.data( 'hooked', false );
				_popup.off( 'click', '.popup-close', _click_close );
				_popup.off( 'click', '.popup-title', _click_title );
				_popup.off( 'click', '.close', _me.hide );
				_popup.off( 'click', '.check-column :checkbox', _toggle_checkboxes );
				jQuery( window ).off( 'resize', _resize_and_move );
			}
		}

		/**
		 * Updates the size and position of the window.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _update_window() {
			if ( ! _wnd ) { return false; }
			if ( ! _popup ) { return false; }

			var _overlay = wpmUi._modal_overlay(),
				_el_title = _popup.find( '.popup-title' ),
				_el_content = _popup.find( '.popup-content' ),
				_title_span = _el_title.find( '.the-title' );

			// Window title.
			if ( _template && ! _title_span.length ) {
				_title_span = _el_title;
			}
			_title_span.html( _title );

			if ( _title_close ) {
				_popup.removeClass( 'no-close' );
			} else {
				_popup.addClass( 'no-close' );
			}

			// Display a copy of the specified content.
			if ( _content_changed ) {
				// Remove the current button bar.
				_wnd.find( '.buttons' ).remove();
				_popup.addClass( 'no-buttons' );

				// Update the content.
				if ( _content instanceof jQuery ) {
					// _content is a jQuery element.
					_el_content.empty().append( _content );
				} else {
					// _content is a HTML string.
					_el_content.html( _content );
				}

				// Move the buttons out of the content area.
				var buttons = _el_content.find( '.buttons' );
				if ( buttons.length ) {
					buttons.appendTo( _popup );
					_popup.removeClass( 'no-buttons' );
				}

				// Add custom class to the popup.
				_popup.addClass( _classes );

				_content_changed = false;
			}

			if ( _overlay instanceof jQuery ) {
				_overlay.off( 'click', _modal_close );
			}

			// Show or hide the window and modal background.
			if ( _visible ) {
				_show_the_popup();

				if ( _modal ) {
					wpmUi._make_modal( '', 'has-popup' );
				}

				if ( _background_close ) {
					_overlay.on( 'click', _modal_close );
				}

				if ( _need_check_size ) {
					_need_check_size = false;
					_resize_and_move();
				}

				// Allow the browser to display + render the title first.
				window.setTimeout(function() {
					if ( 'down' === _slidein ) {
						_el_content.css({ bottom: _el_title.height() + 1 });
					} else {
						_el_content.css({ top: _el_title.height() + 1 });
					}
					if ( ! _height ) {
						window.setTimeout(_resize_and_move, 5);
					}
				}, 5);
			} else {
				_hide_the_popup();

				var wnd, remove_modal = true;
				for ( wnd in _all_popups ) {
					if ( _all_popups[wnd] === _me ) { continue; }
					if ( ! _all_popups[wnd].is_visible() ) { continue; }
					if ( _all_popups[wnd].is_modal() ) {
						remove_modal = false;
						break;
					}
				}

				if ( remove_modal ) {
					wpmUi._close_modal( 'has-popup no-scroll can-scroll' );
				}
			}

			// Adjust the close-icon according to slide-in state.
			var icon = _popup.find('.popup-close .dashicons');
			if ( icon.length ) {
				if ( 'none' === _slidein ) {
					icon.removeClass().addClass('dashicons dashicons-no-alt');
				} else {
					if ( 'collapsed' === _slidein_status ) {
						icon.removeClass().addClass('dashicons').addClass(_icon_collapse);
					} else if ( 'expanded' === _slidein_status ) {
						icon.removeClass().addClass('dashicons').addClass(_icon_expand);
					}
				}
			}

			// Remove all "slidein-..." classes from the popup.
			_popup[0].className = _popup[0].className.replace(/\sslidein-.+?\b/g, '');

			if ( 'none' === _slidein ) {
				_popup.removeClass( 'slidein' );
				_popup.removeClass( 'wdev-slidein' );
				_popup.addClass( 'wdev-window' );
			} else {
				_popup.addClass( 'slidein' );
				_popup.addClass( 'slidein-' + _slidein );
				_popup.addClass( 'slidein-' + _slidein_status );
				_popup.addClass( 'wdev-slidein' );
				_popup.removeClass( 'wdev-window' );
			}
			if ( _snap.top ) { _popup.addClass('snap-top'); }
			if ( _snap.left ) { _popup.addClass('snap-left'); }
			if ( _snap.right ) { _popup.addClass('snap-right'); }
			if ( _snap.bottom ) { _popup.addClass('snap-bottom'); }
		}

		/**
		 * Displays the popup while considering the slidein option
		 *
		 * @since  2.0.0
		 */
		function _show_the_popup() {
			_popup.show();

			// We have a collapsed slide-in. Animate it.
			var have_slidein = 'none' !== _slidein,
				can_expand = ('collapsed' === _slidein_status);

			if ( have_slidein ) {
				// First time the slide in is opened? Animate it.
				if ( ! can_expand && 'none' === _slidein_status ) {
					var styles = {};
					_slidein_status = 'collapsed';
					styles = _get_popup_size( styles );
					styles = _get_popup_pos( styles );
					_popup.css(styles);

					can_expand = true;
				}

				if ( can_expand ) {
					_slidein_status = 'expanding';
					_resize_and_move( _slidein_speed );
					_need_check_size = false;

					window.setTimeout(function() {
						_slidein_status = 'expanded';
						_update_window();
						window.setTimeout( _resize_and_move, 10 );
					}, _slidein_speed);
				}
			}
		}

		/**
		 * Hides the popup while considering the slidein option to either
		 * completely hide the popup or to keep the title visible.
		 *
		 * @since  2.0.0
		 */
		function _hide_the_popup() {
			switch ( _slidein ) {
				case 'up':
				case 'down':
					var can_collapse = ('expanded' === _slidein_status);

					if ( can_collapse ) {
						var wnd = jQuery( window ),
							window_height = wnd.innerHeight(),
							popup_pos = _popup.position(),
							styles = {};

						// First position the popup using the `top` property only.
						styles['margin-top'] = 0;
						styles['margin-bottom'] = 0;
						styles['bottom'] = 'auto';
						styles['top'] = popup_pos.top;
						_popup.css( styles );

						// Calculate the destination position of the popup and animate.
						_slidein_status = 'collapsing';
						styles = _get_popup_pos();
						_popup.animate(styles, _slidein_speed, function() {
							_slidein_status = 'collapsed';
							_update_window();
							window.setTimeout( _resize_and_move, 10 );
						});
					}
					break;

				default:
					_popup.hide();
					break;
			}
		}

		/**
		 * When the popup has slide-in behavior then the close button acts as
		 * a toggle-visiblity button.
		 *
		 * @since  1.0.0
		 */
		function _click_close( ev ) {
			if ( 'none' === _slidein ) {
				_me.hide();
			} else {
				if ( _visible ) {
					_me.hide();
				} else {
					_me.show();
				}
			}
			ev.stopPropagation();
		}

		/**
		 * Slide-ins also react when the user clicks the title.
		 *
		 * @since  1.0.0
		 */
		function _click_title( ev ) {
			if ( 'none' !== _slidein ) {
				if ( _visible ) {
					_me.hide();
				} else {
					_me.show();
				}
				ev.stopPropagation();
			}
		}

		/**
		 * Closes the window when user clicks on the modal overlay
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _modal_close() {
			var _overlay = wpmUi._modal_overlay();
			if ( ! _wnd ) { return false; }
			if ( ! _overlay instanceof jQuery ) { return false; }

			_overlay.off( 'click', _modal_close );
			_me.hide();
		}

		/**
		 * Makes sure that the popup window is not bigger than the viewport.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _resize_and_move(duration) {
			if ( ! _popup ) { return false; }

			if ( typeof _onresize === 'function' ) {
				_onresize.apply( _me, [ _me.$() ] );
			} else {
				var styles = {};

				styles = _get_popup_size( styles );
				styles = _get_popup_pos( styles );

				// Size and position.
				if ( ! isNaN( duration ) && duration > 0 ) {
					_popup.animate(styles, duration);
				} else {
					_popup.css(styles);
				}
			}
		}

		/**
		 * A helper function for the resize/slidein functions that returns the
		 * actual size (width and height) of the popup.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		function _get_popup_size( size ) {
			var wnd = jQuery( window ),
				window_width = wnd.innerWidth(),
				window_height = wnd.innerHeight(),
				border_x = parseInt( _popup.css('border-left-width') ) +
					parseInt( _popup.css('border-right-width') ),
				border_y = parseInt( _popup.css('border-top-width') ) +
					parseInt( _popup.css('border-bottom-width') ),
				real_width = _width + border_x,
				real_height = _height + border_y;

			if ( 'object' !== typeof size ) { size = {}; }

			// Calculate the width and height ------------------------------

			if ( ! _height || ! _width ) {
				var get_width = ! _width,
					get_height = ! _height,
					new_width = 0, new_height = 0;

				_popup.find('*').each(function() {
					var el = jQuery( this ),
						pos = el.position(),
						el_width = el.outerWidth() + pos.left,
						el_height = el.outerHeight() + pos.top;

					if ( get_width && new_width < el_width ) {
						new_width = el_width;
					}
					if ( get_height && new_height < el_height ) {
						new_height = el_height;
					}
				});

				if ( get_width ) { real_width = new_width + border_x; }
				if ( get_height ) { real_height = new_height + border_y; }
			}

			if ( _snap.left && _snap.right ) {
				// Snap to 2 sides: full width.
				size['width'] = window_width - border_x;
			} else {
				if ( window_width < real_width ) {
					real_width = window_width;
				}
				size['width'] = real_width - border_x;
			}

			if ( _snap.top && _snap.bottom ) {
				// Snap to 2 sides: full height.
				size['height'] = window_height - border_y;
			} else {
				if ( window_height < real_height ) {
					real_height = window_height;
				}
				size['height'] = real_height - border_y;
			}

			return size;
		}

		/**
		 * Helper function used for positioning the popup, it will return the
		 * x/y positioning styles.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		function _get_popup_pos( styles ) {
			var wnd = jQuery( window ),
				el_toggle = _popup.find( '.slidein-toggle' ),
				window_width = wnd.innerWidth(),
				window_height = wnd.innerHeight(),
				border_x = parseInt( _popup.css('border-left-width') ) +
					parseInt( _popup.css('border-right-width') ),
				border_y = parseInt( _popup.css('border-top-width') ) +
					parseInt( _popup.css('border-bottom-width') );

			if ( 'object' !== typeof styles ) { styles = {}; }
			if ( undefined === styles['width'] || undefined === styles['height'] ) {
				styles = _get_popup_size( styles );
			}

			// Position X: (empty) / left / right / left + right
			if ( ! _snap.left && ! _snap.right ) {
				// Center X.
				styles['left'] = (window_width - styles['width']) / 2;
			} else if ( _snap.left && _snap.right ) {
				// Snap to 2 sides.
				styles['left'] = 0;
			} else {
				// Snap to one side.
				if ( _snap.left ) {
					styles['left'] = 0;
				}
				if ( _snap.right ) {
					styles['left'] = window_width - styles['width'] - border_x;
				}
			}

			if ( 'none' !== _slidein && ('collapsed' === _slidein_status || 'collapsing' === _slidein_status) ) {
				// We have a collapsed slide-in. Y-position is fixed.
				if ( 'down' === _slidein ) {
					styles['top'] = el_toggle.outerHeight() - styles['height'];
				} else {
					styles['top'] = window_height - el_toggle.outerHeight();
				}
			} else {
				// Position Y: (empty) / top / bottom / top + bottom
				if ( ! _snap.top && ! _snap.bottom ) {
					// Center Y.
					styles['top'] = (window_height - styles['height']) / 2;
				} else if ( _snap.top && _snap.bottom ) {
					// Snap to 2 sides.
					styles['top'] = 0;
				} else {
					// Snap to one side.
					if ( _snap.top ) {
						styles['top'] = 0;
					}
					if ( _snap.bottom ) {
						styles['top'] = window_height - styles['height'] - border_y;
					}
				}
			}

			styles['margin-top'] = 0;
			styles['margin-bottom'] = 0;
			styles['bottom'] = 'auto';
			styles['right'] = 'auto';

			if ( undefined === styles['top'] ) { styles['top'] = 'auto'; }
			if ( undefined === styles['left'] ) { styles['left'] = 'auto'; }

			return styles;
		}

		/**
		 * Toggle all checkboxes in a WordPress-ish table when the user clicks
		 * the check-all checkbox in the header or footer.
		 *
		 * @since  1.0.0
		 * @internal
		 */
		function _toggle_checkboxes( ev ) {
			var chk = jQuery( this ),
				c = chk.prop( 'checked' ),
				toggle = (ev.shiftKey);

			// Toggle checkboxes inside the table body
			chk
				.closest( 'table' )
				.children( 'tbody, thead, tfoot' )
				.filter( ':visible' )
				.children()
				.children( '.check-column' )
				.find( ':checkbox' )
				.prop( 'checked', c);
		}

		/**
		 * Toggle the check-all checkexbox in the header/footer in a
		 * WordPress-ish table when a single checkbox in the body is changed.
		 *
		 * @since  1.0.0
		 */
		function _check_checkboxes( ev ) {
			var chk = jQuery( this ),
				unchecked = chk
					.closest( 'tbody' )
					.find( ':checkbox' )
					.filter( ':visible' )
					.not( ':checked' );

			chk
				.closest( 'table' )
				.children( 'thead, tfoot' )
				.find( ':checkbox' )
				.prop( 'checked',  ( 0 === unchecked.length ) );

			return true;
		}

		// Initialize the popup window.
		_me = this;
		_init();

	}; /* ** End: WpmUiWindow ** */

}( window.wpmUi = window.wpmUi || {} ));
/*!
 * WPMU Dev UI library
 * (Philipp Stracker for WPMU Dev)
 *
 * This module provides the WpmUiProgress object which is a smart and easy to use
 * Pop-up.
 *
 * @version  2.0.2
 * @author   Philipp Stracker for WPMU Dev
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global XMLHttpRequest:false */

(function( wpmUi ) {

	/*==============================*\
	==================================
	==                              ==
	==           PROGRESS           ==
	==                              ==
	==================================
	\*==============================*/

	/**
	 * The progress bar element.
	 *
	 * @type   WpmUiProgress
	 * @since  2.0.2
	 */
	wpmUi.WpmUiProgress = function() {

		/**
		 * Backreference to the WpmUiWindow object.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _me = this;

		/**
		 * Current value of the progress bar.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _current = 0;

		/**
		 * Max value of the progress bar.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _max = 100;

		/**
		 * The label text
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _label = '';

		/**
		 * The wrapper around the progress bar elements.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _el = null;

		/**
		 * The progress bar.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _el_bar = null;

		/**
		 * The progress bar full width indicator.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _el_full = null;

		/**
		 * The progress bar title.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _el_label = null;

		/**
		 * Label that displays the current progress percent value.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		var _el_percent = null;

		/**
		 * Change the value of the progress bar.
		 *
		 * @since  2.0.2
		 * @api
		 */
		this.value = function value( val ) {
			if ( ! isNaN( val ) ) {
				_current = parseInt( val );
				_update();
			}
			return _me;
		};

		/**
		 * Set the max value of the progess bar.
		 *
		 * @since  2.0.2
		 * @api
		 */
		this.max = function max( val ) {
			if ( ! isNaN( val ) ) {
				_max = parseInt( val );
				_update();
			}
			return _me;
		};

		/**
		 * Set the contents of the label.
		 *
		 * @since  2.0.2
		 * @api
		 */
		this.label = function label( val ) {
			_label = val;
			_update();
			return _me;
		};

		/**
		 * Adds an event handler to the element.
		 *
		 * @since  2.0.1
		 */
		this.on = function on( event, selector, callback ) {
			_el.on( event, selector, callback );
			return _me;
		};

		/**
		 * Removes an event handler from the element.
		 *
		 * @since  2.0.1
		 */
		this.off = function off( event, selector, callback ) {
			_el.off( event, selector, callback );
			return _me;
		};

		/**
		 * Returns the jQuery object of the main element
		 *
		 * @since  1.0.0
		 */
		this.$ = function $() {
			return _el;
		};

		// ==============================
		// == Private functions =========


		/**
		 * Create the DOM elements for the window.
		 *
		 * @since  2.0.2
		 * @internal
		 */
		function _init() {
			_max = 100;
			_current = 0;

			_el = jQuery( '<div class="wpmui-progress-wrap"></div>' );
			_el_full = jQuery( '<div class="wpmui-progress-full"></div>' );
			_el_bar = jQuery( '<div class="wpmui-progress"></div>' );
			_el_label = jQuery( '<div class="wpmui-progress-label"></div>' );
			_el_percent = jQuery( '<div class="wpmui-progress-percent"></div>' );

			// Attach the window to the current page.
			_el_bar.appendTo( _el_full );
			_el_percent.appendTo( _el_full );
			_el_full.appendTo( _el );
			_el_label.appendTo( _el );

			_update();
		}

		/**
		 * Updates the progress bar
		 *
		 * @since  2.0.2
		 */
		function _update() {
			var percent = _current / _max * 100;
			if ( percent < 0 ) { percent = 0; }
			if ( percent > 100 ) { percent = 100; }

			_el_bar.width( percent + '%' );
			_el_percent.text( parseInt( percent ) + ' %' );

			if ( _label && _label.length ) {
				_el_label.html( _label );
				_el_label.show();
			} else {
				_el_label.hide();
			}
		}

		// Initialize the progress bar.
		_me = this;
		_init();

	}; /* ** End: WpmUiProgress ** */

}( window.wpmUi = window.wpmUi || {} ));
/*!
 * WPMU Dev UI library
 * (Rheinard Korf, Philipp Stracker for WPMU Dev)
 *
 * This module adds a WordPress-like hook system in javascript that makes it
 * easier to expose actions/filters to other developers.
 *
 * ----------------------------------------------------------------------------
 * @file A WordPress-like hook system for JavaScript.
 *
 * This file demonstrates a simple hook system for JavaScript based on the hook
 * system in WordPress. The purpose of this is to make your code extensible and
 * allowing other developers to hook into your code with their own callbacks.
 *
 * There are other ways to do this, but this will feel right at home for
 * WordPress developers.
 *
 * @author Rheinard Korf
 * @license GPL2 (https://www.gnu.org/licenses/gpl-2.0.html)
 *
 * @requires underscore.js (http://underscorejs.org/)
 * ----------------------------------------------------------------------------
 *
 * @version  3.0.0
 * @author   Philipp Stracker for WPMU Dev
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */

(function( wpmUi ) {

	if (wpmUi.add_action) { return; }

	/*===========================*\
	===============================
	==                           ==
	==           HOOKS           ==
	==                           ==
	===============================
	\*===========================*/

	/**
	 * All actions/filters are stored in the filters object.
	 *
	 * In WordPress actions and filters are synonyms - only difference is, that
	 * a filter will return a value, while an action does not return a value.
	 */
	wpmUi.filters = wpmUi.filters || {};

	/**
	 * Add a new Action callback to wpmUi.filters
	 *
	 * This function is an alias to wpmUi.add_filter
	 *
	 * @param tag The tag specified by do_action()
	 * @param callback The callback function to call when do_action() is called
	 * @param priority The order in which to call the callbacks. Default: 10 (like WordPress)
	 */
	wpmUi.add_action = function( tag, callback, priority ) {
		wpmUi.add_filter( tag, callback, priority );
	};

	/**
	 * Add a new Filter callback to wpmUi.filters
	 *
	 * @param tag The tag specified by apply_filters()
	 * @param callback The callback function to call when apply_filters() is called
	 * @param priority Priority of filter to apply. Default: 10 (like WordPress)
	 */
	wpmUi.add_filter = function( tag, callback, priority ) {
		if( undefined === callback ) {
			return;
		}

		if( undefined === priority ) {
			priority = 10;
		}

		// If the tag doesn't exist, create it.
		wpmUi.filters[ tag ] = wpmUi.filters[ tag ] || [];
		wpmUi.filters[ tag ].push( { priority: priority, callback: callback } );
	};

	/**
	 * Remove an Action callback from wpmUi.filters
	 *
	 * This function is an Alias to wpmUi.remove_filter
	 *
	 * Must be the exact same callback signature.
	 * Warning: Anonymous functions can not be removed.
	 * @param tag The tag specified by do_action()
	 * @param callback The callback function to remove
	 */
	wpmUi.remove_action = function( tag, callback ) {
		wpmUi.remove_filter( tag, callback );
	};

	/**
	 * Remove a Filter callback from wpmUi.filters
	 *
	 * Must be the exact same callback signature.
	 * Warning: Anonymous functions can not be removed.
	 * @param tag The tag specified by apply_filters()
	 * @param callback The callback function to remove
	 */
	wpmUi.remove_filter = function( tag, callback ) {
		wpmUi.filters[ tag ] = wpmUi.filters[ tag ] || [];

		wpmUi.filters[ tag ].forEach( function( filter, i ) {
			if( filter.callback === callback ) {
				wpmUi.filters[ tag ].splice(i, 1);
			}
		} );
	};

	/**
	 * Remove all Action callbacks for the specified tag.
	 *
	 * This function is an Alias to wpmUi.remove_all_filters
	 *
	 * @param tag The tag specified by do_action()
	 * @param priority Only remove actions with the specified priority
	 */
	wpmUi.remove_all_actions = function( tag, priority ) {
		wpmUi.remove_all_filters( tag, priority );
	};

	/**
	 * Remove all Filter callbacks for the specified tag
	 *
	 * @param tag The tag specified by do_action()
	 * @param priority Only remove actions with the specified priority
	 */
	wpmUi.remove_all_filters = function( tag, priority ) {
		wpmUi.filters[ tag ] = wpmUi.filters[ tag ] || [];

		if ( undefined === priority ) {
			wpmUi.filters[ tag ] = [];
		} else {
			wpmUi.filters[ tag ].forEach( function( filter, i ) {
				if( filter.priority === priority ) {
					wpmUi.filters[ tag ].splice(i, 1);
				}
			} );
		}
	};

	/**
	 * Calls actions that are stored in wpmUi.actions for a specific tag or nothing
	 * if there are no actions to call.
	 *
	 * @param tag A registered tag in Hook.actions
	 * @options Optional JavaScript object to pass to the callbacks
	 */
	wpmUi.do_action = function( tag, options ) {
		var actions = [];

		if( undefined !== wpmUi.filters[ tag ] && wpmUi.filters[ tag ].length > 0 ) {

			wpmUi.filters[ tag ].forEach( function( hook ) {

				actions[ hook.priority ] = actions[ hook.priority ] || [];
				actions[ hook.priority ].push( hook.callback );

			} );

			actions.forEach( function( hooks ) {

				hooks.forEach( function( callback ) {
					callback( options );
				} );

			} );
		}
	};

	/**
	 * Calls filters that are stored in wpmUi.filters for a specific tag or return
	 * original value if no filters exist.
	 *
	 * @param tag A registered tag in Hook.filters
	 * @options Optional JavaScript object to pass to the callbacks
	 */
	wpmUi.apply_filters = function( tag, value, options ) {
		var filters = [];

		if( undefined !== wpmUi.filters[ tag ] && wpmUi.filters[ tag ].length > 0 ) {

			wpmUi.filters[ tag ].forEach( function( hook ) {

				filters[ hook.priority ] = filters[ hook.priority ] || [];
				filters[ hook.priority ].push( hook.callback );
			} );

			filters.forEach( function( hooks ) {

				hooks.forEach( function( callback ) {
					value = callback( value, options );
				} );

			} );
		}

		return value;
	};

/* ** End: Hooks integration in wpmUi ** */

}( window.wpmUi = window.wpmUi || {} ));
/*!
 * WPMU Dev UI library
 * (Philipp Stracker for WPMU Dev)
 *
 * This module provides the WpmUiAjaxData object that is used to serialize whole
 * forms and submit then via Ajax. Even file uploads are possibly with this
 * object.
 *
 * @version  1.0.0
 * @author   Philipp Stracker for WPMU Dev
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global XMLHttpRequest:false */

(function( wpmUi ) {

	/*===============================*\
	===================================
	==                               ==
	==           AJAX-DATA           ==
	==                               ==
	===================================
	\*===============================*/


	/**
	 * Form Data object that is used to load or submit data via ajax.
	 *
	 * @type   WpmUiAjaxData
	 * @since  1.0.0
	 */
	wpmUi.WpmUiAjaxData = function( _ajaxurl, _default_action ) {

		/**
		 * Backreference to the WpmUiAjaxData object.
		 *
		 * @since  1.0.0
		 * @private
		 */
		var _me = this;

		/**
		 * An invisible iframe with name "wpmui_void", created by this object.
		 *
		 * @type   jQuery object
		 * @since  1.0.0
		 * @private
		 */
		var _void_frame = null;

		/**
		 * Data that is sent to the server.
		 *
		 * @type   Object
		 * @since  1.0.0
		 * @private
		 */
		var _data = {};

		/**
		 * Progress handler during upload/download.
		 * Signature function( progress )
		 *     - progress .. Percentage complete or "-1" for "unknown"
		 *
		 * @type  Callback function.
		 * @since  1.0.0
		 * @private
		 */
		var _onprogress = null;

		/**
		 * Receives the server response after ajax call is finished.
		 * Signature: function( response, okay, xhr )
		 *     - response .. Data received from the server.
		 *     - okay .. bool; false means an error occured.
		 *     - xhr .. XMLHttpRequest object.
		 *
		 * @type  Callback function.
		 * @since  1.0.0
		 * @private
		 */
		var _ondone = null;

		/**
		 * Feature detection: HTML5 upload/download progress events.
		 *
		 * @type  bool
		 * @since  1.0.0
		 * @private
		 */
		var _support_progress = false;

		/**
		 * Feature detection: HTML5 file API.
		 *
		 * @type  bool
		 * @since  1.0.0
		 * @private
		 */
		var _support_file_api = false;

		/**
		 * Feature detection: HTML5 FormData object.
		 *
		 * @type  bool
		 * @since  1.0.0
		 * @private
		 */
		var _support_form_data = false;


		// ==============================
		// == Public functions ==========


		/**
		 * Define the data that is sent to the server.
		 *
		 * @since  1.0.0
		 * @param  mixed Data that is sent to the server. Either:
		 *                - Normal javascript object interpreted as key/value pairs.
		 *                - A jQuery object of the whole form element
		 *                - An URL-encoded string ("key=val&key2=val2")
		 */
		this.data = function data( obj ) {
			_data = obj;
			return _me;
		};

		/**
		 * Returns an ajax-compatible version of the data object passed in.
		 * This data object can be any of the values that is recognized by the
		 * data() method above.
		 *
		 * @since  1.0.7
		 * @param  mixed obj
		 * @return Object
		 */
		this.extract_data = function extract_data( obj ) {
			_data = obj;
			return _get_data( undefined, false );
		};

		/**
		 * Define the upload/download progress callback.
		 *
		 * @since  1.0.0
		 * @param  function callback Progress handler.
		 */
		this.onprogress = function onprogress( callback ) {
			_onprogress = callback;
			return _me;
		};

		/**
		 * Callback that receives the server response of the ajax request.
		 *
		 * @since  1.0.0
		 * @param  function callback
		 */
		this.ondone = function ondone( callback ) {
			_ondone = callback;
			return _me;
		};

		/**
		 * Reset all configurations.
		 *
		 * @since  1.0.0
		 */
		this.reset = function reset() {
			_data = {};
			_onprogress = null;
			_ondone = null;
			return _me;
		};

		/**
		 * Submit the specified data to the ajaxurl and pass the response to a
		 * callback function. Server response can be any string.
		 *
		 * @since  1.0.0
		 * @param  action string The ajax action to execute.
		 */
		this.load_text = function load_text( action ) {
			action = action || _default_action;
			_load( action, 'text' );

			return _me;
		};

		/**
		 * Submit the specified data to the ajaxurl and pass the response to a
		 * callback function. Server response must be a valid JSON string!
		 *
		 * @since  1.0.0
		 * @param  action string The ajax action to execute.
		 */
		this.load_json = function load_json( action ) {
			action = action || _default_action;
			_load( action, 'json' );

			return _me;
		};

		/**
		 * Submit the specified data to the ajaxurl and let the browser process
		 * the response.
		 * Use this function for example when the server returns a file that
		 * should be downloaded.
		 *
		 * @since  1.0.0
		 * @param  string target Optional. The frame to target.
		 * @param  string action Optional. The ajax action to execute.
		 */
		this.load_http = function load_http( target, action ) {
			target = target || 'wpmui_void';
			action = action || _default_action;
			_form_submit( action, target );

			return _me;
		};


		// ==============================
		// == Private functions =========


		/**
		 * Initialize the formdata object
		 *
		 * @since  1.0.0
		 * @private
		 */
		function _init() {
			// Initialize missing Ajax-URL: Use WordPress ajaxurl if possible.
			if ( ! _ajaxurl && typeof window.ajaxurl === 'string') {
				_ajaxurl = window.ajaxurl;
			}

			// Initialize an invisible iframe for file downloads.
			_void_frame = jQuery( 'body' ).find( '#wpmui_void' );

			if ( ! _void_frame.length ) {
				/**
				 * Create the invisible iframe.
				 * Usage: <form target="wpmui_void">...</form>
				 */
				_void_frame = jQuery('<iframe></iframe>')
					.attr( 'name', 'wpmui_void' )
					.attr( 'id', 'wpmui_void' )
					.css({
						'width': 1,
						'height': 1,
						'display': 'none',
						'visibility': 'hidden',
						'position': 'absolute',
						'left': -1000,
						'top': -1000
					})
					.hide()
					.appendTo( jQuery( 'body' ) );
			}

			// Find out what HTML5 feature we can use.
			_what_is_supported();

			// Reset all configurations.
			_me.reset();
		}

		/**
		 * Feature detection
		 *
		 * @since  1.0.0
		 * @private
		 * @return bool
		 */
		function _what_is_supported() {
			var inp = document.createElement( 'INPUT' );
			var xhr = new XMLHttpRequest();

			// HTML 5 files API
			inp.type = 'file';
			_support_file_api = 'files' in inp;

			// HTML5 ajax upload "progress" events
			_support_progress = !! (xhr && ( 'upload' in xhr ) && ( 'onprogress' in xhr.upload ));

			// HTML5 FormData object
			_support_form_data = !! window.FormData;
		}

		/**
		 * Creates the XMLHttpReqest object used for the jQuery ajax calls.
		 *
		 * @since  1.0.0
		 * @private
		 * @return XMLHttpRequest
		 */
		function _create_xhr() {
			var xhr = new window.XMLHttpRequest();

			if ( _support_progress ) {
				// Upload progress
				xhr.upload.addEventListener( "progress", function( evt ) {
					if ( evt.lengthComputable ) {
						var percentComplete = evt.loaded / evt.total;
						_call_progress( percentComplete );
					} else {
						_call_progress( -1 );
					}
				}, false );

				// Download progress
				xhr.addEventListener( "progress", function( evt ) {
					if ( evt.lengthComputable ) {
						var percentComplete = evt.loaded / evt.total;
						_call_progress( percentComplete );
					} else {
						_call_progress( -1 );
					}
				}, false );
			}

			return xhr;
		}

		/**
		 * Calls the "onprogress" callback
		 *
		 * @since  1.0.0
		 * @private
		 * @param  float value Percentage complete / -1 for "unknown"
		 */
		function _call_progress( value ) {
			if ( _support_progress && typeof _onprogress === 'function' ) {
				_onprogress( value );
			}
		}

		/**
		 * Calls the "onprogress" callback
		 *
		 * @since  1.0.0
		 * @private
		 * @param  response mixed The parsed server response.
		 * @param  okay bool False means there was an error.
		 * @param  xhr XMLHttpRequest
		 */
		function _call_done( response, okay, xhr ) {
			_call_progress( 100 );
			if ( typeof _ondone === 'function' ) {
				_ondone( response, okay, xhr );
			}
		}

		/**
		 * Returns data object containing the data to submit.
		 * The data object is either a plain javascript object or a FormData
		 * object; this depends on the parameter "use_formdata" and browser-
		 * support for FormData.
		 *
		 * @since  1.0.0
		 * @private
		 * @param  string action
		 * @param  boolean use_formdata If set to true then we return FormData
		 *                when the browser supports it. If support is missing or
		 *                use_formdata is not true then the response is an object.
		 * @return Object or FormData
		 */
		function _get_data( action, use_formdata ) {
			var data = {};
			use_formdata = use_formdata && _support_form_data;

			if ( _data instanceof jQuery ) {

				// ===== CONVERT <form> to data object.

				// WP-Editor needs some special attention first:
				_data.find( '.wp-editor-area' ).each(function() {
					var id = jQuery( this ).attr( 'id' ),
						sel = '#wp-' + id + '-wrap',
						container = jQuery( sel ),
						editor = window.tinyMCE.get( id );

					if ( editor && container.hasClass( 'tmce-active' ) ) {
						editor.save(); // Update the textarea content.
					}
				});

				if ( use_formdata ) {
					data = new window.FormData( _data[0] );
				} else {
					data = {};

					// Convert a jQuery object to data object.

					// ----- Start: Convert FORM to OBJECT
					// http://stackoverflow.com/a/8407771/313501
					var push_counters = {},
						patterns = {
							"validate": /^[a-zA-Z_][a-zA-Z0-9_-]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
							"key":      /[a-zA-Z0-9_-]+|(?=\[\])/g,
							"push":     /^$/,
							"fixed":    /^\d+$/,
							"named":    /^[a-zA-Z0-9_-]+$/
						};

					var _build = function( base, key, value ) {
						base[key] = value;
						return base;
					};

					var _push_counter = function( key ) {
						if ( push_counters[key] === undefined ) {
							push_counters[key] = 0;
						}
						return push_counters[key]++;
					};

					jQuery.each( _data.serializeArray(), function() {
						// skip invalid keys
						if ( ! patterns.validate.test( this.name ) ) { return; }

						var k,
							keys = this.name.match(patterns.key),
							merge = this.value,
							reverse_key = this.name;

						while ( ( k = keys.pop() ) !== undefined ) {

							// adjust reverse_key
							reverse_key = reverse_key.replace( new RegExp( "\\[" + k + "\\]$" ), '' );

							// push
							if ( k.match( patterns.push ) ) {
								merge = _build( [], _push_counter( reverse_key ), merge );
							}

							// fixed
							else if ( k.match( patterns.fixed ) ) {
								merge = _build([], k, merge);
							}

							// named
							else if ( k.match( patterns.named ) ) {
								merge = _build( {}, k, merge );
							}
						}

						data = jQuery.extend( true, data, merge );
					});

					// ----- End: Convert FORM to OBJECT

					// Add file fields
					_data.find( 'input[type=file]' ).each( function() {
						var me = jQuery( this ),
							name = me.attr( 'name' ),
							inp = me.clone( true )[0];
						data[':files'] = data[':files'] || {};
						data[':files'][name] = inp;
					});
				}
			} else if ( typeof _data === 'string' ) {

				// ===== PARSE STRING to data object.

				var temp = _data.split( '&' ).map( function (kv) {
					return kv.split( '=', 2 );
				});

				data = ( use_formdata ? new window.FormData() : {} );
				for ( var ind in temp ) {
					var name = decodeURI( temp[ind][0] ),
						val = decodeURI( temp[ind][1] );

					if ( use_formdata ) {
						data.append( name, val );
					} else {
						if ( undefined !== data[name]  ) {
							if ( 'object' !== typeof data[name] ) {
								data[name] = [ data[name] ];
							}
							data[name].push( val );
						} else {
							data[name] = val;
						}
					}
				}
			} else if ( typeof _data === 'object' ) {

				// ===== USE OBJECT to populate data object.

				if ( use_formdata ) {
					data = new window.FormData();
					for ( var data_key in _data ) {
						if ( _data.hasOwnProperty( data_key ) ) {
							data.append( data_key, _data[data_key] );
						}
					}
				} else {
					data = jQuery.extend( {}, _data );
				}
			}

			if ( undefined !== action ) {
				if ( data instanceof window.FormData ) {
					data.append('action', action);
				} else {
					data.action = action;
				}
			}

			return data;
		}

		/**
		 * Submit the data.
		 *
		 * @since  1.0.0
		 * @private
		 * @param  string action The ajax action to execute.
		 */
		function _load( action, type ) {
			var data = _get_data( action, true ),
				ajax_args = {},
				response = null,
				okay = false;

			if ( type !== 'json' ) { type = 'text'; }

			_call_progress( -1 );

			ajax_args = {
				url: _ajaxurl,
				type: 'POST',
				dataType: 'html',
				data: data,
				xhr: _create_xhr,
				success: function( resp, status, xhr ) {
					okay = true;
					response = resp;
					if ( 'json' === type ) {
						try {
							response = jQuery.parseJSON( resp );
						} catch(ignore) {
							response = { 'status': 'ERR', 'data': resp };
						}
					}
				},
				error: function( xhr, status, error ) {
					okay = false;
					response = error;
				},
				complete: function( xhr, status ) {
					if ( response instanceof Object && 'ERR' === response.status ) {
						okay = false;
					}
					_call_done( response, okay, xhr );
				}
			};

			if ( data instanceof window.FormData ) {
				ajax_args.processData = false;  // tell jQuery not to process the data
				ajax_args.contentType = false;  // tell jQuery not to set contentType
			}

			jQuery.ajax(ajax_args);
		}

		/**
		 * Send data via a normal form submit targeted at the invisible iframe.
		 *
		 * @since  1.0.0
		 * @private
		 * @param  string action The ajax action to execute.
		 * @param  string target The frame to refresh.
		 */
		function _form_submit( action, target ) {
			var data = _get_data( action, false ),
				form = jQuery( '<form></form>' ),
				ajax_action = '';

			// Append all data fields to the form.
			for ( var name in data ) {
				if ( data.hasOwnProperty( name ) ) {
					if ( name === ':files' ) {
						for ( var file in data[name] ) {
							var inp = data[name][file];
							form.append( inp );
						}
					} else if ( name === 'action') {
						ajax_action = name + '=' + data[name].toString();
					} else {
						jQuery('<input type="hidden" />')
							.attr( 'name', name )
							.attr( 'value', data[name] )
							.appendTo( form );
					}
				}
			}

			if ( _ajaxurl.indexOf( '?' ) === -1 ) {
				ajax_action = '?' + ajax_action;
			} else {
				ajax_action = '&' + ajax_action;
			}

			// Set correct form properties.
			form.attr( 'action', _ajaxurl + ajax_action )
				.attr( 'method', 'POST' )
				.attr( 'enctype', 'multipart/form-data' )
				.attr( 'target', target )
				.hide()
				.appendTo( jQuery( 'body' ) );

			// Submit the form.
			form.submit();
		}


		// Initialize the formdata object
		_me = this;
		_init();

	}; /* ** End: WpmUiAjaxData ** */

}( window.wpmUi = window.wpmUi || {} ));
/*!
 * WPMU Dev UI library
 * (Philipp Stracker for WPMU Dev)
 *
 * This module provides the WpmUiBinary object that is used to
 * serialize/deserialize data in base64.
 *
 * @version  1.0.0
 * @author   Philipp Stracker for WPMU Dev
 * @requires jQuery
 */
/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global XMLHttpRequest:false */

(function( wpmUi ) {

	/*===============================*\
	===================================
	==                               ==
	==           UTF8-DATA           ==
	==                               ==
	===================================
	\*===============================*/




	/**
	 * Handles conversions of binary <-> text.
	 *
	 * @type   WpmUiBinary
	 * @since  1.0.0
	 */
	wpmUi.WpmUiBinary = function() {
		var map = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

		wpmUi.WpmUiBinary.utf8_encode = function utf8_encode( string ) {
			if ( typeof string !== 'string' ) {
				return string;
			} else {
				string = string.replace(/\r\n/g, "\n");
			}
			var output = '', i = 0, charCode;

			for ( i; i < string.length; i++ ) {
				charCode = string.charCodeAt(i);

				if ( charCode < 128 ) {
					output += String.fromCharCode( charCode );
				} else if ( (charCode > 127) && (charCode < 2048) ) {
					output += String.fromCharCode( (charCode >> 6) | 192 );
					output += String.fromCharCode( (charCode & 63) | 128 );
				} else {
					output += String.fromCharCode( (charCode >> 12) | 224 );
					output += String.fromCharCode( ((charCode >> 6) & 63) | 128 );
					output += String.fromCharCode( (charCode & 63) | 128 );
				}
			}

			return output;
		};

		wpmUi.WpmUiBinary.utf8_decode = function utf8_decode( string ) {
			if ( typeof string !== 'string' ) {
				return string;
			}

			var output = '', i = 0, charCode = 0;

			while ( i < string.length ) {
				charCode = string.charCodeAt(i);

				if ( charCode < 128 ) {
					output += String.fromCharCode( charCode );
					i += 1;
				} else if ( (charCode > 191) && (charCode < 224) ) {
					output += String.fromCharCode(((charCode & 31) << 6) | (string.charCodeAt(i + 1) & 63));
					i += 2;
				} else {
					output += String.fromCharCode(((charCode & 15) << 12) | ((string.charCodeAt(i + 1) & 63) << 6) | (string.charCodeAt(i + 2) & 63));
					i += 3;
				}
			}

			return output;
		};

		/**
		 * Converts a utf-8 string into an base64 encoded string
		 *
		 * @since  1.0.15
		 * @param  string input A string with any encoding.
		 * @return string
		 */
		wpmUi.WpmUiBinary.base64_encode = function base64_encode( input ) {
			if ( typeof input !== 'string' ) {
				return input;
			} else {
				input = wpmUi.WpmUiBinary.utf8_encode( input );
			}
			var output = '', a, b, c, d, e, f, g, i = 0;

			while ( i < input.length ) {
				a = input.charCodeAt(i++);
				b = input.charCodeAt(i++);
				c = input.charCodeAt(i++);
				d = a >> 2;
				e = ((a & 3) << 4) | (b >> 4);
				f = ((b & 15) << 2) | (c >> 6);
				g = c & 63;

				if ( isNaN( b ) ) {
					f = g = 64;
				} else if ( isNaN( c ) ) {
					g = 64;
				}

				output += map.charAt( d ) + map.charAt( e ) + map.charAt( f ) + map.charAt( g );
			}

			return output;
		};

		/**
		 * Converts a base64 string into the original (binary) data
		 *
		 * @since  1.0.15
		 * @param  string input Base 64 encoded text
		 * @return string
		 */
		wpmUi.WpmUiBinary.base64_decode = function base64_decode( input ) {
			if ( typeof input !== 'string' ) {
				return input;
			} else {
				input.replace(/[^A-Za-z0-9\+\/\=]/g, '');
			}
			var output = '', a, b, c, d, e, f, g, i = 0;

			while ( i < input.length ) {
				d = map.indexOf( input.charAt( i++ ) );
				e = map.indexOf( input.charAt( i++ ) );
				f = map.indexOf( input.charAt( i++ ) );
				g = map.indexOf( input.charAt( i++ ) );

				a = (d << 2) | (e >> 4);
				b = ((e & 15) << 4) | (f >> 2);
				c = ((f & 3) << 6) | g;

				output += String.fromCharCode( a );
				if ( f !== 64 ) {
					output += String.fromCharCode( b );
				}
				if ( g !== 64 ) {
					output += String.fromCharCode( c );
				}
			}

			return wpmUi.WpmUiBinary.utf8_decode( output );
		};

	}; /* ** End: WpmUiBinary ** */

}( window.wpmUi = window.wpmUi || {} ));