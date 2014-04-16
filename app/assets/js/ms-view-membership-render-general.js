/**
 * Add rule 
 * TODO http://stackoverflow.com/questions/2196036/jquery-the-right-way-to-add-a-child-element
 */
jQuery( document ).ready(function( $ ) {
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
