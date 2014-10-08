/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_list = function init () {
	var s2_config = jQuery.extend( {}, window.ms_functions.chosen_options );

	function change_search_options() {
		if ( 'membership' === jQuery( '#search_options' ).val() ) {
			jQuery( '#membership_filter' ).show();
			jQuery( '#member-search' ).hide();
		}
		else {
			jQuery( '#membership_filter' ).hide();
			jQuery( '#member-search' ).show();
		}
	}

	jQuery( '#search_options').change( change_search_options );
	change_search_options();

	// Initialize the User-List select field

	function submit_add_form() {
		var sel = jQuery( '#new_member' ).val();
		if ( ! sel.length ) { return false; }

		jQuery( '#form_add_member' ).submit();
	}

	function enable_add_button() {
		var sel = jQuery( '#new_member' ).val();
		if ( ! sel.length ) {
			jQuery( '#add_member' ).addClass( 'disabled' );
		} else {
			jQuery( '#add_member' ).removeClass( 'disabled' );
		}
	}

	s2_config.minimumResultsForSearch = 0;
	s2_config.placeholder = window.ms_data.lang.select_user;
	s2_config.allowClear = true;
	s2_config.ajax = {
		url: window.ajaxurl,
		dataType: 'jsonp',
		quietMillis: 200,
		data: function (term, page) {
			return {
				filter: term, // search term
				action: 'get_users'
			};
		},
		results: function (data, page) {
			return {results: data};
		}
	};
	jQuery( '#new_member' ).select2( s2_config ).change( enable_add_button );
	jQuery( '#add_member' ).click( submit_add_form );
	enable_add_button();
};
