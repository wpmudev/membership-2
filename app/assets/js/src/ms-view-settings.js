/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_settings = function init () {
	function page_changed( event, data, response, is_err ) {
		var lists = jQuery( 'select.wpmui-wp-pages' ),
			cur_pages = lists.map(function() { return jQuery(this).val(); });

		lists.each(function() {
			var ind,
				me = jQuery( this ),
				options = me.find( 'option' ),
				row = me.parents( '.ms-settings-page-wrapper' ).first(),
				actions = row.find( '.ms-action a' ),
				val = me.val();

			// Disable the pages that are used already.
			options.prop( 'disabled', false );
			for ( ind = 0; ind < cur_pages.length; ind += 1 ) {
				if ( val === cur_pages[ind] ) { continue; }
				options.filter( '[value="' + cur_pages[ind] + '"]' )
					.prop( 'disabled', true );
			}

			// Update the view/edit links
			actions.each(function() {
				var link = jQuery( this ),
					data = link.data( 'wpmui-ajax' ),
					url = data.base + val;

				if ( undefined === val || isNaN(val) || val < 1 ) {
					link.addClass( 'disabled' );
					link.attr( 'href', '' );
				} else {
					link.removeClass( 'disabled' );
					link.attr( 'href', url );
				}
			});
		});
	}

	function ignore_disabled( ev ) {
		var me = jQuery( this );

		if ( me.hasClass( 'disabled' ) || ! me.attr( 'href' ).length ) {
			ev.preventDefault();
			return false;
		}
	}

	function reload_window() {
		window.location = ms_data.initial_url;
	}

	function update_toolbar( ev, data ) {
		// Show/Hide the Toolbar menu for Membership2.
		if ( data.value ) {
			jQuery( '#wp-admin-bar-ms-unprotected' ).hide();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).show();
		} else {
			jQuery( '#wp-admin-bar-ms-unprotected' ).show();
			jQuery( '#wp-admin-bar-ms-test-memberships' ).hide();
		}
	}

	function hide_footer( ev, data ) {
		// Show/Hide the footer for Membership2.
		if ( !data.value ) {
			jQuery( '.ms-settings-footer' ).hide();
		} else {
			jQuery( '.ms-settings-footer' ).show();
		}
		var ajax_data = jQuery( '.wpmui-slider-enable_cron_use .wpmui-toggle').attr('data-wpmui-ajax');
		ajax_data = JSON.parse(ajax_data);
		jQuery.post(window.ajaxurl,{'action' : 'toggle_cron', '_wpnonce' : ajax_data._wpnonce }, function(){});
	}

	// Reload the page when Wizard mode is activated.
	jQuery( '#initial_setup' ).on( 'ms-ajax-updated', reload_window );

	// Hide/Show the "Test Membership" button in the toolbar.
	jQuery( '.wpmui-slider-plugin_enabled').on( 'ms-radio-slider-updated', update_toolbar );
	//Hide/Show footer when the cron is enabled or disabled
	jQuery( '.wpmui-slider-enable_cron_use').on( 'ms-radio-slider-updated', hide_footer );

	// Membership Pages: Update contents after a page was saved
	jQuery( '.wpmui-wp-pages' ).on( 'ms-ajax-updated', page_changed );
	jQuery( '.ms-action a' ).on( 'click', ignore_disabled );
	jQuery(function() { page_changed(); });
};
