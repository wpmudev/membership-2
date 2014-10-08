/*global jQuery:false */
/*global window:false */
/*global document:false */
/*global ms_data:false */
/*global ms_functions:false */

window.ms_init.view_member_date = function init () {
	var dp_config = {
        dateFormat: 'yy-mm-dd' //TODO get wp configured date format
    };

	jQuery( '.ms-date' ).datepicker( dp_config );
};
