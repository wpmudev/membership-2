/**
 * Add rule 
 * TODO http://stackoverflow.com/questions/2196036/jquery-the-right-way-to-add-a-child-element
 */
jQuery( document ).ready( function( $ ) {
	$( '#rule_type').change( function() {
		rule_type = $( this ).val();
		$( '.ms-rule-type-wrapper' ).hide();
		$( '#ms-wrapper-rule-type-' +  rule_type ).show();
		if( 'page' == rule_type || 'menu' == rule_type ) {
			$('#ms-inherit-rules-wrapper' ).show();
		}
		else {
			$('#ms-inherit-rules-wrapper').hide();
		}
	});
	function show_delayed_access() {
		if( $( '#delay_access_enabled' ).is( ':checked' ) ) {
			$( '#ms-delayed-period-wrapper' ).show();
		}
		else {
			$( '#ms-delayed-period-wrapper' ).hide();
		}
	}
	
	$( '#delay_access_enabled' ).click( show_delayed_access );

	show_delayed_access();
	
	$( '#rule_type').change();
	
	rule_counter = 0;
	$( "#btn_add_rule" ).click( function() {
		var content = [], rule_value = [], rule_type, delayed_period_unit, delayed_period_type, inherit_rules = false;
		$( '.ms-rule-type-wrapper:visible input:checked' ).each( function() {
			rule_value.push( $( this ).val() );
			content.push( $( this ).parent().next('td').text() );
			$( '#delay_access_enabled:checked' ).each( function() {
				console.log("enabled");
				delayed_period_unit = $( '#delayed_period_unit' ).val();
				delayed_period_type = $( '#delayed_period_type' ).val();
			});
			$( '#inherit_rules:checked' ).each( function() {
				inherit_rules = true;
			});
		});
		protect = [
		           	{
		           		content: content, 
		           		rule_value: rule_value,
		           		rule_type: "post",
		           		delayed_period_unit: delayed_period_unit,
		           		delayed_period_type: delayed_period_type,
		           		inherit_rules : inherit_rules,
		           		counter: rule_counter++,
		            },
		          ];
		$( "#rule_template" ).tmpl( protect ).appendTo( "#the-list" );
	});
});
