/**
 * Add rule 
 * TODO http://stackoverflow.com/questions/2196036/jquery-the-right-way-to-add-a-child-element
 */
jQuery( document ).ready( function( $ ) {
	$( '#rule_type').change( function() {
		rule_type = $( this ).val();
		$( '.ms-select-rule-type' ).hide();
		$( '#rule_value_' +  rule_type ).show();
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
		content = 'content';
		rule_type = 'post';
		delayed_period_unit = '2';
		delayed_period_type = 'days';
		inherit_rules = 'no';
		input = "<input type='hidden' name='ms_rule[" + rule_counter + "][rule_type]' value='" + rule_type + "' />";
		input += "<input type='hidden' name='ms_rule[" + rule_counter + "][rule_value]' value='" + content + "' />";
		input += "<input type='hidden' name='ms_rule[" + rule_counter + "][delayed_period_unit]' value='" + delayed_period_unit + "' />";
		input += "<input type='hidden' name='ms_rule[" + rule_counter + "][delayed_period_type]' value='" + delayed_period_type + "' />";
		input += "<input type='hidden' name='ms_rule[" + rule_counter + "][inherit_rules]' value='" + inherit_rules + "' />";
		row = 	$("<tr class='alternate'>" + input +  
				"<td class='content column-content'>" + content + "</td>" +
				"<td class='rule_type column-rule_type'>" + rule_type + "</td>" + 
				"<td class='delayed_period column-delayed_period'>"+ delayed_period_unit + delayed_period_type + "</td>"  + 
				"<td class='inherit column-inherit'>" + inherit_rules + "</td>" +
				"<td class='actions column-actions'>delete | edit</td>" +
				"</tr>");
		$( "#the-list" ).append( row );
		rule_counter++;
	});
});
