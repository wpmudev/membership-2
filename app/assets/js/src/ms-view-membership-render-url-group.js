/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_membership_urlgroup = function init () {
	var timeout = false;

	//global functions defined in ms-functions.js
	ms_functions.test_url = function() {
		if ( timeout ) {
			window.clearTimeout( timeout );
		}

		timeout = window.setTimeout(function() {
			var container = jQuery( '#url-test-results-wrapper' ),
				url = jQuery.trim(jQuery( '#url_test' ).val() ),
				rules = jQuery( '#rule_value' ).val().split( "\n" ),
				is_regex = jQuery( '#is_regex' ).val();

			if ( is_regex === 'true' || is_regex === '1' ) {
				is_regex = true;
			} else {
				is_regex = false;
			}

			container.empty().hide();

			if ( url === '' ) {
				return;
			}

			jQuery.each( rules, function( i, rule ) {
				var line, result, ruleurl, reg, match;

				rule = jQuery.trim(rule);
				if ( rule === '' ) {
					return;
				}

				line = jQuery( '<div />' ).addClass( 'ms-rule-test' );
				ruleurl = jQuery( '<span />' ).appendTo( line ).text( rule ).addClass( 'ms-test-url' );
				result = jQuery( '<span />' ).appendTo( line ).addClass( 'ms-test-result' );

				match = false;
				if ( is_regex ) {
					reg = new RegExp( rule, 'i' );
					match = reg.test( url );
				} else {
					match = url.indexOf( rule ) >= 0;
				}

				if ( match ) {
					line.addClass( 'ms-rule-valid' );
					result.text( ms_data.valid_rule_msg );
				} else {
					line.addClass( 'ms-rule-invalid' );
					result.text( ms_data.invalid_rule_msg );
				}

				container.append( line );
			});

			if ( ! container.find( '> div' ).length ) {
				container.html( '<div><i>' + ms_data.empty_msg + '</i></div>' );
			}

			container.show();
		}, 500);
	};

	jQuery( '#url_test, #rule_value' ).keyup( ms_functions.test_url );
};
